#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Updating package lists..."
apt-get update -qq || error "apt-get update failed"
log "Package lists updated"

info "Upgrading existing packages..."
DEBIAN_FRONTEND=noninteractive apt-get upgrade -y -qq || error "apt-get upgrade failed"
log "Packages upgraded"

complete_step
