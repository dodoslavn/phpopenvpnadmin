#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Creating application directories..."

dirs=(
    "/etc/vpnadmin"
    "/etc/openvpn/server"
    "/var/lib/vpnadmin/pki"
    "/var/lib/vpnadmin/clients"
    "/var/lib/vpnadmin/db"
    "/var/lib/vpnadmin/.install"
    "/var/log/vpnadmin"
)

for dir in "${dirs[@]}"; do
    mkdir -p "$dir"
    log "Created: ${dir}"
done

# PKI: root owns, www-data group for web app access.
# 711 (not 750) so OpenVPN's nobody user can also traverse the dir to stat crl.pem —
# without this, OpenVPN loads the CRL at startup but can't reload it after revocation.
chown root:www-data /var/lib/vpnadmin/pki
chmod 711 /var/lib/vpnadmin/pki
log "Set /var/lib/vpnadmin/pki (711 root:www-data)"

# DB dir: owned by nobody so OpenVPN auth script (runs as nobody with no supplementary groups)
# can access it directly. setgid ensures new files (WAL, SHM) inherit www-data group so
# the web app (www-data) can also read/write them. 2770 = setgid + 770.
chown nobody:www-data /var/lib/vpnadmin/db
chmod 2770 /var/lib/vpnadmin/db
log "Set /var/lib/vpnadmin/db (2770 nobody:www-data)"

# Clients dir: www-data can create subdirs (for cert generation) and list (for glob)
chown root:www-data /var/lib/vpnadmin/clients
chmod 770 /var/lib/vpnadmin/clients
log "Set /var/lib/vpnadmin/clients (770 root:www-data)"

# Create openssl.cnf for CRL generation — used by both setup wizard and revocation
PKI="/var/lib/vpnadmin/pki"
cat > "${PKI}/openssl.cnf" << 'OPENSSL_EOF'
[ca]
default_ca = CA_default

[CA_default]
database    = /var/lib/vpnadmin/pki/index.txt
crlnumber   = /var/lib/vpnadmin/pki/crlnumber
default_md  = sha256
default_crl_days = 30

[crl_ext]
authorityKeyIdentifier = keyid:always
OPENSSL_EOF

# Create empty CRL database files
touch "${PKI}/index.txt"
echo '01' > "${PKI}/crlnumber"

chown root:root "${PKI}/openssl.cnf" "${PKI}/index.txt" "${PKI}/crlnumber"
chmod 644 "${PKI}/openssl.cnf" "${PKI}/index.txt" "${PKI}/crlnumber"
log "Created PKI support files (openssl.cnf, index.txt, crlnumber)"

complete_step
