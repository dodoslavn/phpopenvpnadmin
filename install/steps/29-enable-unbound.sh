#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Enabling unbound DNS resolver..."

systemctl enable unbound || error "Failed to enable unbound"

# Don't start yet — it listens on 10.8.0.1 which doesn't exist until OpenVPN runs
# OpenVPN creates tun0 on start, then unbound can bind to 10.8.0.1
log "unbound enabled (will start after OpenVPN creates the VPN tunnel interface)"

# Add unbound to openvpn up/down scripts instead
cat > /etc/openvpn/server/up.sh << 'EOF'
#!/bin/bash
systemctl start unbound
EOF

cat > /etc/openvpn/server/down.sh << 'EOF'
#!/bin/bash
systemctl stop unbound
EOF

chmod +x /etc/openvpn/server/up.sh /etc/openvpn/server/down.sh
log "Created OpenVPN up/down scripts to manage unbound lifecycle"

complete_step
