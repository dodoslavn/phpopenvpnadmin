#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing and configuring fail2ban..."
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq fail2ban \
    || error "Failed to install fail2ban"

# Combined jail config: SSH + OpenVPN auth failures
cat > /etc/fail2ban/jail.d/vpnadmin.conf << 'EOF'
[sshd]
enabled  = true
port     = ssh
logpath  = %(sshd_log)s
backend  = %(sshd_backend)s
maxretry = 5
findtime = 600
bantime  = 3600

[openvpn-auth]
enabled  = true
port     = 1194
protocol = udp
filter   = openvpn-auth
backend  = systemd
maxretry = 5
findtime = 600
bantime  = 3600
EOF

# Filter matching journal entries logged by check-password.sh via logger
cat > /etc/fail2ban/filter.d/openvpn-auth.conf << 'EOF'
[Definition]
failregex = AUTH_FAILED user=\S+ src=<HOST>$
ignoreregex =

[Init]
journalmatch = SYSLOG_IDENTIFIER=openvpn-auth
EOF

systemctl enable fail2ban >/dev/null 2>&1
systemctl restart fail2ban || error "Failed to start fail2ban"

# fail2ban-server daemonizes and can take a second or two to open its socket
# after "systemctl restart" returns — poll instead of checking immediately,
# otherwise this step intermittently fails even though fail2ban comes up fine.
ready=0
for _ in $(seq 1 20); do
    fail2ban-client status >/dev/null 2>&1 && { ready=1; break; }
    sleep 0.5
done
[ "$ready" -eq 1 ] || error "fail2ban not responding"
log "fail2ban running (SSH + OpenVPN jails active)"

complete_step
