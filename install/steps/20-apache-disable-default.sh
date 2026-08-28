#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Disabling Apache default site..."

a2dissite 000-default.conf 2>/dev/null && log "Disabled 000-default.conf" || log "000-default.conf was not enabled"
a2dissite default-ssl.conf 2>/dev/null && log "Disabled default-ssl.conf" || true

complete_step
