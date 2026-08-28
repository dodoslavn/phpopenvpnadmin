#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Disabling nftables..."

if systemctl is-active nftables &>/dev/null; then
    nft flush ruleset 2>/dev/null || warn "Could not flush nftables ruleset"
    systemctl stop nftables || warn "Could not stop nftables"
    log "nftables stopped"
else
    log "nftables was not active"
fi

if systemctl is-enabled nftables &>/dev/null; then
    systemctl disable nftables || warn "Could not disable nftables"
    log "nftables disabled"
else
    log "nftables was not enabled"
fi

complete_step
