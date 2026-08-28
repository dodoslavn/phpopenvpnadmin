#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Writing OpenVPN server configuration..."

TEMPLATE_DIR="$(dirname "$0")/../../config"
PORT=1194

if [ ! -f "${TEMPLATE_DIR}/server.conf.template" ]; then
    error "Template not found: ${TEMPLATE_DIR}/server.conf.template"
fi

sed "s/{{PORT}}/${PORT}/g" "${TEMPLATE_DIR}/server.conf.template" \
    > /etc/openvpn/server/server.conf || error "Failed to write server.conf"

log "Written /etc/openvpn/server/server.conf (port ${PORT})"
log "Note: wizard will start OpenVPN after generating PKI"

complete_step
