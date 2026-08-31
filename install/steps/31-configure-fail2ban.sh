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
logpath  = /var/log/auth.log
filter   = openvpn-auth
maxretry = 5
findtime = 600
bantime  = 3600
EOF

# Filter matching lines logged by check-password.sh via logger
cat > /etc/fail2ban/filter.d/openvpn-auth.conf << 'EOF'
[Definition]
failregex = ^.* openvpn-auth: AUTH_FAILED user=\S+ src=<HOST>$
ignoreregex =
EOF

systemctl enable fail2ban >/dev/null 2>&1
systemctl restart fail2ban || error "Failed to start fail2ban"

fail2ban-client status >/dev/null 2>&1 || error "fail2ban not responding"
log "fail2ban running (SSH + OpenVPN jails active)"

complete_step
