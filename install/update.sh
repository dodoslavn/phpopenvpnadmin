#!/bin/bash
# Redeploy web files and auth script after a git pull.
# Does NOT re-run the full installer — just updates the deployed files.

set -euo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [ "$EUID" -ne 0 ]; then
    echo "Run as root: sudo bash install/update.sh"
    exit 1
fi

echo "Updating PHP OpenVPN Admin..."

# Web app
cp -a "${REPO}/web/." /var/www/vpnadmin/
cp -a "${REPO}/assets/." /var/www/vpnadmin/assets/

# Fix permissions
chown -R root:www-data /var/www/vpnadmin
find /var/www/vpnadmin -type d -exec chmod 750 {} \;
find /var/www/vpnadmin -type f -exec chmod 640 {} \;

# Auth script
cp "${REPO}/auth/check-password.sh" /etc/openvpn/server/check-password.sh
chown root:root /etc/openvpn/server/check-password.sh
chmod 755 /etc/openvpn/server/check-password.sh

# Cert signing helper
cp "${REPO}/auth/sign-cert.sh" /usr/local/bin/vpnadmin-sign-cert
chown root:root /usr/local/bin/vpnadmin-sign-cert
chmod 755 /usr/local/bin/vpnadmin-sign-cert

echo "Done. Reload Apache: systemctl reload apache2"
