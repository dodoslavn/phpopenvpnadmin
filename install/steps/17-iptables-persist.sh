#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Saving iptables rules..."

netfilter-persistent save || error "Failed to save iptables rules"
log "iptables rules saved (will persist across reboots)"

complete_step
