#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Enabling IPv4 forwarding..."

# Set persistently in sysctl.conf
if ! grep -q "^net.ipv4.ip_forward=1" /etc/sysctl.conf; then
    # Remove any existing ip_forward lines then add the correct one
    sed -i '/net\.ipv4\.ip_forward/d' /etc/sysctl.conf
    echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf
    log "Added net.ipv4.ip_forward=1 to /etc/sysctl.conf"
else
    log "net.ipv4.ip_forward=1 already in /etc/sysctl.conf"
fi

# Apply immediately
sysctl -w net.ipv4.ip_forward=1 >/dev/null || error "Failed to apply ip_forward"

val=$(cat /proc/sys/net/ipv4/ip_forward)
[ "$val" = "1" ] || error "ip_forward is not 1 (got: ${val})"
log "IPv4 forwarding enabled"

complete_step
