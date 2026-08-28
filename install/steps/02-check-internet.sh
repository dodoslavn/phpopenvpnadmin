#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Checking internet connectivity..."

hosts=("deb.debian.org" "security.debian.org")
for host in "${hosts[@]}"; do
    if ! ping -c 1 -W 3 "$host" &>/dev/null; then
        error "Cannot reach ${host} — check internet connection"
    fi
    log "Reachable: ${host}"
done

complete_step
