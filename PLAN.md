# phpopenvpnadmin — Project Plan

## Goal
Replace paid OpenVPN Access Server with a free self-hosted PHP web UI on a bare Debian VM.
Cost: 2€/month Aruba VM. No Docker in production. No FPM. No easy-rsa.

## Stack
| Component | Choice | Reason |
|---|---|---|
| OS | Debian 13 (Trixie) | Latest stable Debian |
| Web server | Apache2 + mod_php | Single process, no FPM overhead |
| Database | SQLite | No separate DB process |
| PKI | openssl directly | No easy-rsa abstraction needed |
| VPN | OpenVPN Community Edition | Industry standard, free |
| DNS | unbound | Private recursive resolver for VPN clients |
| Firewall | iptables-legacy | Debian 13 defaults to nftables — we switch |

## Key Decisions
- **CA Mode A** — app generates its own dedicated VPN CA on first run (isolated from web CA)
- **Auth Method 2** — certificate + username/password (double factor)
- **Roles** — admin (manage accounts) + user (generate/download .ovpn profiles)
- **Multi-device** — users can have multiple profiles (one per device)
- **HTTPS** — self-signed *.fordo.eu CA (separate from VPN CA)

## Repository Structure
```
phpopenvpnadmin/
├── PLAN.md
├── install/
│   ├── install.sh                          # Orchestrator — loops steps/*.sh in order
│   ├── lib/
│   │   └── common.sh                       # log(), error(), check_done(), mark_done()
│   └── steps/
│       ├── 00-check-root.sh
│       ├── 01-check-os.sh                  # Must be Debian 13 (Trixie)
│       ├── 02-check-internet.sh
│       ├── 03-apt-update.sh
│       ├── 04-install-openvpn.sh
│       ├── 05-install-apache.sh
│       ├── 06-install-php.sh               # php, libapache2-mod-php, php-sqlite3, php-cli
│       ├── 07-verify-openssl.sh
│       ├── 08-install-iptables.sh          # iptables, iptables-persistent, arptables, ebtables
│       ├── 09-install-unbound.sh           # Local recursive DNS resolver
│       ├── 10-disable-nftables.sh          # Disable nftables, flush ruleset
│       ├── 11-switch-iptables-legacy.sh    # update-alternatives to iptables-legacy
│       ├── 12-enable-ip-forward.sh         # net.ipv4.ip_forward=1
│       ├── 13-detect-interface.sh          # Detect primary NIC → /etc/vpnadmin/iface
│       ├── 14-configure-unbound.sh         # Listen on 10.8.0.1 only, recursive
│       ├── 15-iptables-nat.sh              # MASQUERADE on detected interface
│       ├── 16-iptables-openvpn.sh          # Allow UDP 1194 + forwarding
│       ├── 17-iptables-persist.sh          # netfilter-persistent save
│       ├── 18-create-dirs.sh               # /etc/vpnadmin, /var/lib/vpnadmin/{pki,clients,db}
│       ├── 19-openvpn-server-conf.sh       # Write server.conf from template
│       ├── 20-apache-disable-default.sh
│       ├── 21-apache-modules.sh            # a2enmod php rewrite ssl headers
│       ├── 22-deploy-webapp.sh             # rsync web/ → /var/www/vpnadmin
│       ├── 23-webapp-permissions.sh        # chown www-data, chmod, lock pki
│       ├── 24-sqlite-init.sh               # Create DB + schema
│       ├── 25-apache-vhost.sh              # Write + enable vpnadmin.conf
│       ├── 26-sudoers.sh                   # www-data whitelist for openssl/openvpn/systemctl
│       ├── 27-enable-apache.sh
│       ├── 28-enable-openvpn.sh            # enable but don't start — wizard does that
│       ├── 29-enable-unbound.sh
│       └── 30-verify.sh                    # Check all services/ports/files, print summary
├── web/
│   ├── index.php                           # Router
│   ├── login.php
│   ├── logout.php
│   ├── setup/
│   │   └── wizard.php                      # First-run: generate VPN CA, server cert, DH, ta.key
│   ├── admin/
│   │   ├── dashboard.php                   # Status, connected clients
│   │   ├── users.php                       # Add/disable/delete users
│   │   └── settings.php                    # OpenVPN settings, restart service
│   ├── user/
│   │   ├── dashboard.php                   # Own profiles list
│   │   └── profile.php                     # Generate/download/revoke .ovpn
│   └── includes/
│       ├── config.php                      # Paths, app constants
│       ├── db.php                          # SQLite PDO connection
│       ├── auth.php                        # Session, login, role checks
│       ├── openvpn.php                     # Shell wrappers (generate cert, revoke, status)
│       └── template.php                    # Shared HTML header/footer
├── assets/
│   ├── style.css
│   └── logo.svg
├── config/
│   └── server.conf.template                # OpenVPN server.conf with placeholders
└── auth/
    └── check-password.sh                   # Called by OpenVPN to verify user/pass vs SQLite
```

## SQLite Schema
```sql
users     (id, username, password_hash, role, enabled, created_at)
profiles  (id, user_id, name, cert_serial, revoked, created_at)
sessions  (id, user_id, token, expires_at)
settings  (key, value)   -- vpn_ca_cert, server_ip, port, dns, etc.
```

## First-run Wizard
1. Check if already set up (settings table has ca_cert) — redirect if yes
2. Form: admin username, admin password, server public IP, port (1194), DNS choice
3. On submit:
   - `openssl genrsa` → VPN CA key
   - `openssl req -x509` → VPN CA cert (self-signed, 10yr)
   - `openssl genrsa` → server key
   - `openssl req` → server CSR
   - `openssl x509 -CA` → server cert signed by VPN CA
   - `openssl dhparam 2048` → DH params
   - `openvpn --genkey` → TLS auth key
   - Save CA cert to settings table
   - Start OpenVPN service
4. Create first admin account
5. Redirect to admin dashboard

## .ovpn Profile Structure
Each profile is self-contained (no external files needed by the client):
```
client
dev tun
proto udp
remote {SERVER_IP} {PORT}
resolv-retry infinite
nobind
persist-key
persist-tun
auth-user-pass
auth SHA256
cipher AES-256-GCM
verb 3
<ca>...VPN CA cert...</ca>
<cert>...client cert...</cert>
<key>...client key...</key>
<tls-auth>...ta.key...</tls-auth>
key-direction 1
```

## OpenVPN Password Auth Flow
- `server.conf`: `auth-user-pass-verify /usr/local/bin/vpn-check-password.sh via-env`
- OpenVPN sets `$username` and `$password` env vars, calls the script
- Script queries SQLite, runs `password_verify()` equivalent in bash (via php-cli)
- Exits 0 = allow, 1 = deny

## Security
- `pki/` dir: `chmod 700 root:root`, www-data accesses only via sudo whitelist
- sudoers: named commands only, no wildcards, no shell escape
- No user input ever interpolated directly into shell commands
- Sessions: random 64-char token, SQLite-backed, 8h expiry
- Passwords: bcrypt via `password_hash()`

## DNS (unbound)
- Listens only on `10.8.0.1` (VPN tunnel interface)
- Recursive resolver — no upstream forwarder, resolves from root
- DNSSEC validation enabled
- Pushed to VPN clients via `push "dhcp-option DNS 10.8.0.1"` in server.conf

## Firewall (iptables-legacy)
- Debian 13 defaults to nftables — disabled and replaced
- MASQUERADE on primary interface for VPN traffic NAT
- UDP 1194 open for VPN connections
- TCP 80/443 open for web UI
- All else default DROP on INPUT (configured in verify step)
