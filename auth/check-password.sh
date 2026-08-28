#!/bin/bash
# Called by OpenVPN to verify username + password against SQLite DB.
# OpenVPN passes credentials via environment variables (via-env mode).
# Exit 0 = allow, Exit 1 = deny.

DB="/var/lib/vpnadmin/db/vpnadmin.db"

if [ -z "$username" ] || [ -z "$password" ]; then
    exit 1
fi

# Sanitize — only allow safe characters in username
if ! echo "$username" | grep -qE '^[a-z0-9_]{1,32}$'; then
    exit 1
fi

# Pass credentials via env to avoid injecting them into PHP code
result=$(VPN_USER="$username" VPN_PASS="$password" VPN_DB="$DB" php -r "
    try {
        \$db = new PDO('sqlite:' . getenv('VPN_DB'));
        \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$stmt = \$db->prepare('SELECT password_hash, enabled FROM users WHERE username = ?');
        \$stmt->execute([strtolower(trim(getenv('VPN_USER')))]);
        \$row = \$stmt->fetch(PDO::FETCH_ASSOC);
        if (\$row && \$row['enabled'] && password_verify(getenv('VPN_PASS'), \$row['password_hash'])) {
            echo 'ok';
        } else {
            echo 'fail';
        }
    } catch (Exception \$e) {
        echo 'fail';
    }
" 2>/dev/null)

if [ "$result" = "ok" ]; then
    exit 0
else
    exit 1
fi
