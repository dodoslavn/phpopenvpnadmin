#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing VPN auth script..."

REPO_ROOT="$(dirname "$0")/../.."
SRC="${REPO_ROOT}/auth/check-password.sh"
DST="/etc/openvpn/server/check-password.sh"

[ -f "$SRC" ] || error "Auth script not found: ${SRC}"

cp "$SRC" "$DST" || error "Failed to copy auth script"
chown root:root "$DST"
chmod 755 "$DST"  # must be executable by nobody (OpenVPN drops privs before calling it)
log "Installed ${DST}"

complete_step
