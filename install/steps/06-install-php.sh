#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing PHP and required modules..."
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    php \
    libapache2-mod-php \
    php-sqlite3 \
    php-cli \
    || error "Failed to install PHP packages"

php -v | grep -q "PHP" || error "php binary not working"
php -m | grep -q "sqlite3" || error "php-sqlite3 module not loaded"
log "PHP installed: $(php -v | head -1)"

complete_step
