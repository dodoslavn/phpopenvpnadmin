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
