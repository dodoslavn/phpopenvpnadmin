#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Setting up NAT masquerade for VPN traffic..."

IFACE=$(cat /etc/vpnadmin/iface 2>/dev/null) || error "Interface file not found — run step 13 first"
[ -n "$IFACE" ] || error "Interface name is empty"

# Add MASQUERADE rule if not already present
if ! iptables -t nat -C POSTROUTING -s 10.8.0.0/24 -o "$IFACE" -j MASQUERADE 2>/dev/null; then
    iptables -t nat -A POSTROUTING -s 10.8.0.0/24 -o "$IFACE" -j MASQUERADE || error "Failed to add MASQUERADE rule"
    log "Added MASQUERADE rule for 10.8.0.0/24 → ${IFACE}"
else
    log "MASQUERADE rule already exists"
fi

complete_step
