#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Enabling OpenVPN service (not starting — wizard will start it after PKI setup)..."

# OpenVPN drops privileges to nobody after startup. nobody needs to be in the
# www-data group to traverse the PKI dir (750 root:www-data) for CRL reloads
# and to read the SQLite DB for the auth script.
usermod -aG www-data nobody
log "Added nobody to www-data group"

systemctl enable openvpn-server@server || error "Failed to enable openvpn-server@server"
log "OpenVPN service enabled (will start after first-run wizard completes PKI setup)"

complete_step
