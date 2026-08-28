#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Setting up iptables rules for OpenVPN and web UI..."

# Allow established/related connections
iptables -C INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT 2>/dev/null || \
    iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

# Allow loopback
iptables -C INPUT -i lo -j ACCEPT 2>/dev/null || \
    iptables -A INPUT -i lo -j ACCEPT

# Allow SSH (don't lock ourselves out)
iptables -C INPUT -p tcp --dport 22 -j ACCEPT 2>/dev/null || \
    iptables -A INPUT -p tcp --dport 22 -j ACCEPT

# Allow OpenVPN UDP 1194
iptables -C INPUT -p udp --dport 1194 -j ACCEPT 2>/dev/null || \
    iptables -A INPUT -p udp --dport 1194 -j ACCEPT
log "Allowed UDP 1194 (OpenVPN)"

# Allow HTTP/HTTPS for web UI
iptables -C INPUT -p tcp --dport 80 -j ACCEPT 2>/dev/null || \
    iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -C INPUT -p tcp --dport 443 -j ACCEPT 2>/dev/null || \
    iptables -A INPUT -p tcp --dport 443 -j ACCEPT
log "Allowed TCP 80/443 (web UI)"

# Allow DNS from VPN clients only
iptables -C INPUT -i tun0 -p udp --dport 53 -j ACCEPT 2>/dev/null || \
    iptables -A INPUT -i tun0 -p udp --dport 53 -j ACCEPT
iptables -C INPUT -i tun0 -p tcp --dport 53 -j ACCEPT 2>/dev/null || \
    iptables -A INPUT -i tun0 -p tcp --dport 53 -j ACCEPT
log "Allowed DNS from VPN interface (tun0)"

# Allow VPN tunnel forwarding
iptables -C FORWARD -i tun0 -j ACCEPT 2>/dev/null || \
    iptables -A FORWARD -i tun0 -j ACCEPT
iptables -C FORWARD -o tun0 -j ACCEPT 2>/dev/null || \
    iptables -A FORWARD -o tun0 -j ACCEPT
log "Allowed FORWARD for tun0"

log "iptables rules configured"

complete_step
