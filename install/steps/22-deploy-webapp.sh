#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Deploying web application..."

REPO_ROOT="$(dirname "$0")/../.."
WEB_SRC="${REPO_ROOT}/web"
WEB_DST="/var/www/vpnadmin"
ASSETS_SRC="${REPO_ROOT}/assets"

[ -d "$WEB_SRC" ] || error "Web source directory not found: ${WEB_SRC}"

mkdir -p "$WEB_DST"

rsync -a --delete "${WEB_SRC}/" "${WEB_DST}/" || error "Failed to deploy web files"
log "Deployed web/ → ${WEB_DST}"

if [ -d "$ASSETS_SRC" ]; then
    rsync -a "${ASSETS_SRC}/" "${WEB_DST}/assets/" || error "Failed to deploy assets"
    log "Deployed assets/"
fi

complete_step
