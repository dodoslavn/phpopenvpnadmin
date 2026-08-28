#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Configuring sudoers for www-data..."

SUDOERS_FILE="/etc/sudoers.d/vpnadmin"

cat > "$SUDOERS_FILE" << 'EOF'
# phpopenvpnadmin — allow www-data to run specific privileged commands only
# DO NOT add wildcards or shell escapes here

Defaults:www-data !requiretty

# OpenVPN service management
www-data ALL=(root) NOPASSWD: /bin/systemctl start openvpn-server@server
www-data ALL=(root) NOPASSWD: /bin/systemctl stop openvpn-server@server
www-data ALL=(root) NOPASSWD: /bin/systemctl restart openvpn-server@server
www-data ALL=(root) NOPASSWD: /bin/systemctl status openvpn-server@server

# openssl — PKI operations (specific subcommands only)
www-data ALL=(root) NOPASSWD: /usr/bin/openssl genrsa -out /var/lib/vpnadmin/pki/*
www-data ALL=(root) NOPASSWD: /usr/bin/openssl req -new -key /var/lib/vpnadmin/pki/* -out /var/lib/vpnadmin/pki/*
www-data ALL=(root) NOPASSWD: /usr/bin/openssl x509 -req -in /var/lib/vpnadmin/pki/* -CA /var/lib/vpnadmin/pki/ca.crt -CAkey /var/lib/vpnadmin/pki/ca.key *
www-data ALL=(root) NOPASSWD: /usr/bin/openssl ca -config /var/lib/vpnadmin/pki/openssl.cnf *
www-data ALL=(root) NOPASSWD: /usr/bin/openssl dhparam *
www-data ALL=(root) NOPASSWD: /usr/bin/openssl req -x509 *

# openvpn — key generation only
www-data ALL=(root) NOPASSWD: /usr/sbin/openvpn --genkey secret /var/lib/vpnadmin/pki/ta.key

# Read OpenVPN status log
www-data ALL=(root) NOPASSWD: /usr/bin/cat /var/log/vpnadmin/openvpn-status.log

# Set file ownership after PKI operations
www-data ALL=(root) NOPASSWD: /bin/chown root\:root /var/lib/vpnadmin/pki/*
www-data ALL=(root) NOPASSWD: /bin/chmod 600 /var/lib/vpnadmin/pki/*
www-data ALL=(root) NOPASSWD: /bin/chmod 644 /var/lib/vpnadmin/pki/ca.crt
www-data ALL=(root) NOPASSWD: /bin/chmod 644 /var/lib/vpnadmin/pki/server.crt
EOF

chmod 440 "$SUDOERS_FILE"

# Validate
visudo -c -f "$SUDOERS_FILE" || error "sudoers file is invalid!"
log "sudoers configured at ${SUDOERS_FILE}"

complete_step
