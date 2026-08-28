#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Creating application directories..."

dirs=(
    "/etc/vpnadmin"
    "/etc/openvpn/server"
    "/var/lib/vpnadmin/pki"
    "/var/lib/vpnadmin/clients"
    "/var/lib/vpnadmin/db"
    "/var/lib/vpnadmin/.install"
    "/var/log/vpnadmin"
)

for dir in "${dirs[@]}"; do
    mkdir -p "$dir"
    log "Created: ${dir}"
done

# PKI must be root-only — very sensitive
chmod 700 /var/lib/vpnadmin/pki
chown root:root /var/lib/vpnadmin/pki
log "Secured /var/lib/vpnadmin/pki (700 root:root)"

# DB dir writable by www-data
chown www-data:www-data /var/lib/vpnadmin/db
chmod 750 /var/lib/vpnadmin/db
log "Set /var/lib/vpnadmin/db ownership to www-data"

# Clients dir: root owns, www-data can read (for download)
chown root:www-data /var/lib/vpnadmin/clients
chmod 750 /var/lib/vpnadmin/clients
log "Set /var/lib/vpnadmin/clients ownership"

complete_step
