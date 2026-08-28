#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Detecting primary network interface..."

# Get the interface used for the default route
IFACE=$(ip route show default | awk '/default/ {print $5}' | head -1)

[ -n "$IFACE" ] || error "Could not detect primary network interface"

# Verify it exists and is up
ip link show "$IFACE" | grep -q "UP" || warn "Interface ${IFACE} may not be UP"

log "Primary interface: ${IFACE}"

# Save for use by later steps
mkdir -p /etc/vpnadmin
echo "$IFACE" > /etc/vpnadmin/iface
log "Saved to /etc/vpnadmin/iface"

complete_step
