#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Writing Apache vhost configuration..."

cat > /etc/apache2/sites-available/vpnadmin.conf << 'EOF'
<VirtualHost *:80>
    ServerName _

    DocumentRoot /var/www/vpnadmin
    DirectoryIndex index.php

    <Directory /var/www/vpnadmin>
        Options -Indexes -FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Block direct access to includes
    <Directory /var/www/vpnadmin/includes>
        Require all denied
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/vpnadmin-error.log
    CustomLog ${APACHE_LOG_DIR}/vpnadmin-access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName _

    DocumentRoot /var/www/vpnadmin
    DirectoryIndex index.php

    SSLEngine on
    SSLCertificateFile    /etc/vpnadmin/ssl/server.crt
    SSLCertificateKeyFile /etc/vpnadmin/ssl/server.key

    <Directory /var/www/vpnadmin>
        Options -Indexes -FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <Directory /var/www/vpnadmin/includes>
        Require all denied
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/vpnadmin-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/vpnadmin-ssl-access.log combined
</VirtualHost>
EOF

a2ensite vpnadmin.conf >/dev/null 2>&1 || error "Failed to enable vpnadmin site"
log "Apache vhost enabled"

# Write catch-all vhost (NOT enabled by default).
# To enable: set ServerName in vpnadmin.conf to your real domain, then:
#   sudo a2ensite 000-catchall && sudo systemctl reload apache2
cat > /etc/apache2/sites-available/000-catchall.conf << 'CATCHALL'
# Default catch-all vhost — rejects requests for unrecognised domains.
#
# HOW TO ENABLE:
#   1. Set ServerName in /etc/apache2/sites-available/vpnadmin.conf
#      to your actual domain or IP (e.g. ServerName vpn.example.com).
#   2. sudo a2ensite 000-catchall
#   3. sudo systemctl reload apache2
#
# This file is loaded first (000-) and catches anything that does not
# match vpnadmin.conf's ServerName, returning 403.

<VirtualHost *:80>
    ServerName catchall
    <Location />
        Require all denied
    </Location>
    ErrorLog ${APACHE_LOG_DIR}/catchall-error.log
</VirtualHost>

<VirtualHost *:443>
    ServerName catchall
    SSLEngine on
    SSLCertificateFile    /etc/vpnadmin/ssl/server.crt
    SSLCertificateKeyFile /etc/vpnadmin/ssl/server.key
    <Location />
        Require all denied
    </Location>
    ErrorLog ${APACHE_LOG_DIR}/catchall-ssl-error.log
</VirtualHost>
CATCHALL
log "Catch-all vhost written to sites-available/000-catchall.conf (not enabled)"

# Generate self-signed cert if not already present — user can replace later
mkdir -p /etc/vpnadmin/ssl
if [ ! -f /etc/vpnadmin/ssl/server.crt ]; then
    openssl req -x509 -nodes -newkey rsa:2048 -days 3650 \
        -keyout /etc/vpnadmin/ssl/server.key \
        -out    /etc/vpnadmin/ssl/server.crt \
        -subj   "/CN=$(hostname)/O=vpnadmin/C=EU" \
        >/dev/null 2>&1 || error "Failed to generate self-signed SSL cert"
    chmod 600 /etc/vpnadmin/ssl/server.key
    chmod 644 /etc/vpnadmin/ssl/server.crt
    log "Generated self-signed SSL cert — replace with your own in /etc/vpnadmin/ssl/"
fi

complete_step
