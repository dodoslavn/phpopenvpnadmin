#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Enabling Apache modules..."

modules=(rewrite ssl headers)
for mod in "${modules[@]}"; do
    a2enmod "$mod" >/dev/null 2>&1 && log "Enabled mod_${mod}" || warn "mod_${mod} may already be enabled"
done

# mod_php is enabled automatically when libapache2-mod-php is installed
php_mod=$(ls /etc/apache2/mods-enabled/php*.load 2>/dev/null | head -1)
[ -n "$php_mod" ] && log "mod_php active: $(basename $php_mod)" || warn "mod_php not found — check PHP installation"

complete_step
