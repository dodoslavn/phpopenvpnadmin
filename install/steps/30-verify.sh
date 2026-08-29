#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"

# Verify step does not use skip_if_done — always runs to show current state

echo ""
echo "============================================"
echo "  phpopenvpnadmin — Installation Summary"
echo "============================================"
echo ""

pass() { echo -e "  \033[0;32m[OK]\033[0m $*"; }
fail() { echo -e "  \033[0;31m[!!]\033[0m $*"; }
info2() { echo -e "  \033[0;34m[--]\033[0m $*"; }

# Services
echo "Services:"
for svc in apache2 openvpn-server@server unbound; do
    if systemctl is-enabled "$svc" &>/dev/null; then
        status=$(systemctl is-active "$svc" 2>/dev/null)
        if [ "$status" = "active" ]; then
            pass "${svc}: running"
        else
            info2 "${svc}: enabled but not running (${status})"
        fi
    else
        fail "${svc}: not enabled"
    fi
done

echo ""
echo "Firewall:"
if iptables --version 2>&1 | grep -q "legacy"; then
    pass "iptables-legacy active"
else
    fail "iptables-legacy NOT active"
fi
if iptables -t nat -L POSTROUTING | grep -q "MASQUERADE"; then
    pass "NAT MASQUERADE rule present"
else
    fail "NAT MASQUERADE rule missing"
fi

echo ""
echo "Files:"
files=(
    "/etc/openvpn/server/server.conf"
    "/etc/openvpn/server/check-password.sh"
    "/etc/openvpn/server/up.sh"
    "/etc/openvpn/server/down.sh"
    "/usr/local/bin/vpnadmin-sign-cert"
    "/var/lib/vpnadmin/db/vpnadmin.db"
    "/var/www/vpnadmin/index.php"
    "/etc/apache2/sites-enabled/vpnadmin.conf"
    "/etc/sudoers.d/vpnadmin"
    "/etc/unbound/unbound.conf.d/vpnadmin.conf"
)
for f in "${files[@]}"; do
    [ -f "$f" ] && pass "$f" || fail "$f (missing)"
done

echo ""
echo "PHP modules:"
for mod in sqlite3 pdo_sqlite; do
    php -m | grep -qi "$mod" && pass "php-${mod}" || fail "php-${mod} missing"
done

echo ""
echo "Network:"
ip_addr=$(ip route get 1.1.1.1 2>/dev/null | awk '/src/ {print $7}' | head -1)
iface=$(cat /etc/vpnadmin/iface 2>/dev/null || echo "unknown")
info2 "Primary interface: ${iface}"
info2 "Server IP: ${ip_addr}"

echo ""
echo "Next steps:"
echo "  1. Copy your SSL cert/key to /etc/vpnadmin/ssl/"
echo "     - server.crt (your *.fordo.eu cert)"
echo "     - server.key (private key)"
echo "  2. Open https://<server-ip>/ in your browser"
echo "  3. Complete the first-run wizard to generate VPN PKI and start OpenVPN"
echo ""

mark_done "$(step_name)"
