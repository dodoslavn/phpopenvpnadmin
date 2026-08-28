#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing iptables packages..."
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    iptables \
    iptables-persistent \
    netfilter-persistent \
    arptables \
    ebtables \
    || error "Failed to install iptables packages"

log "iptables packages installed"

complete_step
