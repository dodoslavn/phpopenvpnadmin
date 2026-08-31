#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Hardening SSH configuration..."

# Warn if no authorized_keys exist — applying this without keys = lockout
keys_found=0
while IFS= read -r home; do
    [ -f "${home}/.ssh/authorized_keys" ] && keys_found=1 && break
done < <(awk -F: '$3 >= 1000 && $3 < 65534 {print $6}' /etc/passwd)
[ -f /root/.ssh/authorized_keys ] && keys_found=1

if [ "$keys_found" -eq 0 ]; then
    warn "No authorized_keys found for any user."
    warn "Make sure your SSH public key is in place BEFORE rebooting or"
    warn "closing this session — password auth will be disabled."
fi

# Write hardened sshd_config
cat > /etc/ssh/sshd_config << 'EOF'
# phpopenvpnadmin — hardened sshd_config
# Generated during installation. SSH key auth only; root login disabled.

Port 22
AddressFamily any
ListenAddress 0.0.0.0
ListenAddress ::

# Host keys
HostKey /etc/ssh/ssh_host_rsa_key
HostKey /etc/ssh/ssh_host_ecdsa_key
HostKey /etc/ssh/ssh_host_ed25519_key

# Ciphers and algorithms
KexAlgorithms curve25519-sha256,curve25519-sha256@libssh.org,diffie-hellman-group14-sha256,diffie-hellman-group16-sha512
Ciphers aes256-gcm@openssh.com,chacha20-poly1305@openssh.com,aes128-gcm@openssh.com
MACs hmac-sha2-256-etm@openssh.com,hmac-sha2-512-etm@openssh.com

# Authentication
LoginGraceTime 30
PermitRootLogin no
StrictModes yes
MaxAuthTries 4
MaxSessions 5

PubkeyAuthentication yes
AuthorizedKeysFile .ssh/authorized_keys

PasswordAuthentication no
PermitEmptyPasswords no
ChallengeResponseAuthentication no
KbdInteractiveAuthentication no
UsePAM yes

# Disable unused auth methods
GSSAPIAuthentication no
HostbasedAuthentication no

# Forwarding
AllowTcpForwarding no
X11Forwarding no
PrintMotd no

# Keep-alive
ClientAliveInterval 120
ClientAliveCountMax 2

# Logging
SyslogFacility AUTH
LogLevel VERBOSE

# Allow env for locale only
AcceptEnv LANG LC_*

# SFTP
Subsystem sftp /usr/lib/openssh/sftp-server
EOF

# Lock root password (no password login even on console by default)
passwd -l root >/dev/null 2>&1 && log "Root account password locked" || warn "Could not lock root password"

# Validate config before restarting
sshd -t || error "sshd_config validation failed — not restarting SSH"

systemctl restart ssh || error "Failed to restart SSH"
log "SSH hardened: key-only auth, root login disabled"

complete_step
