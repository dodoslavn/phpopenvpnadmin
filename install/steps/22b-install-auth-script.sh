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
chmod 750 "$DST"
log "Installed ${DST}"

complete_step
