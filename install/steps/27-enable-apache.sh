#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Enabling and starting Apache2..."

systemctl enable apache2 || error "Failed to enable apache2"
systemctl restart apache2 || error "Failed to start apache2"

systemctl is-active apache2 | grep -q "active" || error "Apache2 is not running"
log "Apache2 running"

complete_step
