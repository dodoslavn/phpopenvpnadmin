#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Enabling unbound DNS resolver..."

# Disable unbound from autostarting at boot — it binds to 10.8.0.1 which only exists
# after OpenVPN creates the tun interface. OpenVPN's up/down scripts manage it instead.
systemctl disable unbound 2>/dev/null || true
systemctl stop unbound 2>/dev/null || true
log "unbound disabled from autostart (managed by OpenVPN up/down scripts)"

# Add unbound to openvpn up/down scripts instead
cat > /etc/openvpn/server/up.sh << 'EOF'
#!/bin/bash
systemctl reset-failed unbound 2>/dev/null || true
systemctl start unbound || true
EOF

cat > /etc/openvpn/server/down.sh << 'EOF'
#!/bin/bash
systemctl stop unbound 2>/dev/null || true
EOF

chmod +x /etc/openvpn/server/up.sh /etc/openvpn/server/down.sh
log "Created OpenVPN up/down scripts to manage unbound lifecycle"

complete_step
