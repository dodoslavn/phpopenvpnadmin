#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing Apache2..."
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq apache2 || error "Failed to install apache2"

apache2 -v | grep -q "Apache" || error "apache2 binary not working"
log "Apache2 installed: $(apache2 -v 2>&1 | head -1)"

complete_step
