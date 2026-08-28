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

# Create SSL dir (certs installed separately by user)
mkdir -p /etc/vpnadmin/ssl
log "Created /etc/vpnadmin/ssl — place your server.crt and server.key here"

complete_step
