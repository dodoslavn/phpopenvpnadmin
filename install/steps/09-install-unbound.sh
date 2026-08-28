#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing unbound DNS resolver..."
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq unbound || error "Failed to install unbound"

unbound -h 2>&1 | grep -q "unbound" || error "unbound binary not working"
log "unbound installed: $(unbound -h 2>&1 | head -1)"

complete_step
