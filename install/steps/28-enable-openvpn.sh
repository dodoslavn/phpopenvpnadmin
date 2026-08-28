#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Enabling OpenVPN service (not starting — wizard will start it after PKI setup)..."

systemctl enable openvpn-server@server || error "Failed to enable openvpn-server@server"
log "OpenVPN service enabled (will start after first-run wizard completes PKI setup)"

complete_step
