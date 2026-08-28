#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Setting web application permissions..."

WEB_DST="/var/www/vpnadmin"
[ -d "$WEB_DST" ] || error "Web directory not found: ${WEB_DST}"

# All files owned by root, readable by www-data
chown -R root:www-data "$WEB_DST"
find "$WEB_DST" -type d -exec chmod 750 {} \;
find "$WEB_DST" -type f -exec chmod 640 {} \;
log "Set ${WEB_DST} permissions (root:www-data, 750/640)"

# index.php must be readable
chmod 644 "${WEB_DST}/index.php" 2>/dev/null || true

log "Web application permissions set"

complete_step
