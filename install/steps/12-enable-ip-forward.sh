#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Enabling IPv4 forwarding..."

# Write to /etc/sysctl.d/ — this is what systemd-sysctl reads reliably at boot on Debian 13.
# Also touch sysctl.conf for compatibility, but sysctl.d/ is the authoritative source.
echo "net.ipv4.ip_forward=1" > /etc/sysctl.d/99-vpnadmin-ipforward.conf
log "Written /etc/sysctl.d/99-vpnadmin-ipforward.conf"

# Also ensure sysctl.conf doesn't have a conflicting 0 setting
sed -i 's/^#*net\.ipv4\.ip_forward\s*=.*/net.ipv4.ip_forward=1/' /etc/sysctl.conf 2>/dev/null || true

# Apply immediately
sysctl -w net.ipv4.ip_forward=1 >/dev/null || error "Failed to apply ip_forward"

val=$(cat /proc/sys/net/ipv4/ip_forward)
[ "$val" = "1" ] || error "ip_forward is not 1 (got: ${val})"
log "IPv4 forwarding enabled"

complete_step
