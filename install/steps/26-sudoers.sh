#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Configuring sudoers for www-data..."

SUDOERS_FILE="/etc/sudoers.d/vpnadmin"

cat > "$SUDOERS_FILE" << 'EOF'
# phpopenvpnadmin — allow www-data to run specific privileged commands only

Defaults:www-data !requiretty

# OpenVPN service management
www-data ALL=(root) NOPASSWD: /bin/systemctl start openvpn-server@server
www-data ALL=(root) NOPASSWD: /bin/systemctl stop openvpn-server@server
www-data ALL=(root) NOPASSWD: /bin/systemctl restart openvpn-server@server
www-data ALL=(root) NOPASSWD: /bin/systemctl status openvpn-server@server

# PKI setup — CA, server cert, DH, TA key
www-data ALL=(root) NOPASSWD: /usr/bin/openssl genrsa -out /var/lib/vpnadmin/pki/* *
www-data ALL=(root) NOPASSWD: /usr/bin/openssl req -x509 *
www-data ALL=(root) NOPASSWD: /usr/bin/openssl req -new -key /var/lib/vpnadmin/pki/* -out /var/lib/vpnadmin/pki/* *
www-data ALL=(root) NOPASSWD: /usr/bin/openssl dhparam *
www-data ALL=(root) NOPASSWD: /usr/sbin/openvpn --genkey secret /var/lib/vpnadmin/pki/ta.key

# Cert signing with extensions (server and client)
www-data ALL=(root) NOPASSWD: /usr/local/bin/vpnadmin-sign-cert server /var/lib/vpnadmin/pki/* /var/lib/vpnadmin/pki/*
www-data ALL=(root) NOPASSWD: /usr/local/bin/vpnadmin-sign-cert client /var/lib/vpnadmin/clients/* /var/lib/vpnadmin/clients/* *

# Client certificate generation
www-data ALL=(root) NOPASSWD: /usr/bin/openssl genrsa -out /var/lib/vpnadmin/clients/* *
www-data ALL=(root) NOPASSWD: /usr/bin/openssl req -new -key /var/lib/vpnadmin/clients/* -out /var/lib/vpnadmin/clients/* *

# CRL operations
www-data ALL=(root) NOPASSWD: /usr/bin/openssl ca *

# File permissions after PKI operations (PKI dir)
www-data ALL=(root) NOPASSWD: /bin/chmod 600 /var/lib/vpnadmin/pki/*
www-data ALL=(root) NOPASSWD: /bin/chmod 640 /var/lib/vpnadmin/pki/*
www-data ALL=(root) NOPASSWD: /bin/chmod 644 /var/lib/vpnadmin/pki/*
www-data ALL=(root) NOPASSWD: /bin/chown root\:www-data /var/lib/vpnadmin/pki/*

# File permissions after client cert operations (clients dir)
www-data ALL=(root) NOPASSWD: /bin/chmod 640 /var/lib/vpnadmin/clients/*
www-data ALL=(root) NOPASSWD: /bin/chmod 644 /var/lib/vpnadmin/clients/*
www-data ALL=(root) NOPASSWD: /bin/chown root\:www-data /var/lib/vpnadmin/clients/*

# Write OpenVPN server.conf (www-data writes to /tmp then cp into place)
www-data ALL=(root) NOPASSWD: /bin/cp /tmp/vpnadmin-server.conf /etc/openvpn/server/server.conf

# Read OpenVPN status log
www-data ALL=(root) NOPASSWD: /usr/bin/cat /var/log/vpnadmin/openvpn-status.log

# fail2ban status (read-only, for dashboard display)
www-data ALL=(root) NOPASSWD: /usr/bin/fail2ban-client status
www-data ALL=(root) NOPASSWD: /usr/bin/fail2ban-client status sshd
www-data ALL=(root) NOPASSWD: /usr/bin/fail2ban-client status openvpn-auth
EOF

chmod 440 "$SUDOERS_FILE"

# Validate
visudo -c -f "$SUDOERS_FILE" || error "sudoers file is invalid!"
log "sudoers configured at ${SUDOERS_FILE}"

complete_step
