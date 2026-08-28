#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Checking OS..."

[ -f /etc/os-release ] || error "Cannot detect OS — /etc/os-release missing"
source /etc/os-release

[ "$ID" = "debian" ] || error "Unsupported OS: ${ID}. Debian 13 required."
[ "$VERSION_ID" = "13" ] || error "Unsupported Debian version: ${VERSION_ID}. Debian 13 (Trixie) required."

log "Detected: ${PRETTY_NAME}"

complete_step
