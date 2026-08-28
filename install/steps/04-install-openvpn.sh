#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing OpenVPN..."
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq openvpn || error "Failed to install openvpn"

openvpn --version | head -1 | grep -q "OpenVPN" || error "openvpn binary not working"
log "OpenVPN installed: $(openvpn --version | head -1)"

complete_step
