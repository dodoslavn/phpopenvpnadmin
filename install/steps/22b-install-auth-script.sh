#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing VPN auth script..."

REPO_ROOT="$(dirname "$0")/../.."
SRC="${REPO_ROOT}/auth/check-password.sh"
DST="/usr/local/bin/vpn-check-password.sh"

[ -f "$SRC" ] || error "Auth script not found: ${SRC}"

cp "$SRC" "$DST" || error "Failed to copy auth script"
chown root:root "$DST"
chmod 755 "$DST"  # must be executable by nobody (OpenVPN drops privs before calling it)
log "Installed ${DST}"

# OpenVPN runs auth script as nobody — give nobody read access to the SQLite DB
# by adding nobody to the www-data group
usermod -aG www-data nobody
log "Added nobody to www-data group (needed to read SQLite DB)"

complete_step
