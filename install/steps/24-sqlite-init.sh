#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Initializing SQLite database..."

DB="/var/lib/vpnadmin/db/vpnadmin.db"

if [ -f "$DB" ]; then
    log "Database already exists at ${DB}"
    complete_step
    exit 0
fi

SCHEMA="$(dirname "$0")/../schema.sql"
[ -f "$SCHEMA" ] || error "Schema file not found: ${SCHEMA}"

php -r "\$db = new PDO('sqlite:${DB}'); \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); \$db->exec(file_get_contents('${SCHEMA}')); echo 'Schema created' . PHP_EOL;" \
    || error "Failed to create database schema"

chown www-data:www-data "$DB"
chmod 660 "$DB"
log "Database created at ${DB}"

complete_step
