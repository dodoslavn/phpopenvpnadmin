#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing cert signing helper script..."

REPO="$(cd "$(dirname "$0")/../.." && pwd)"
DST="/usr/local/bin/vpnadmin-sign-cert"

cp "${REPO}/auth/sign-cert.sh" "$DST"
chmod 755 "$DST"
log "Installed sign-cert helper at ${DST}"

complete_step
