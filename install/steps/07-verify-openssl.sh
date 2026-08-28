#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Verifying openssl..."
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq openssl || error "Failed to install openssl"

openssl version | grep -q "OpenSSL" || error "openssl not working"
log "openssl: $(openssl version)"

complete_step
