#!/bin/bash
# Usage: sign-cert.sh server|client CSR_PATH CERT_PATH [SERIAL_HEX]
# Signs a CSR with the VPN CA, adding proper keyUsage/extendedKeyUsage extensions.
set -e

TYPE="$1"
CSR="$2"
CERT="$3"
SERIAL="${4:-}"
PKI="/var/lib/vpnadmin/pki"

case "$TYPE" in
server)
    EXT_FILE=$(mktemp /tmp/vpnadmin-ext.XXXXXX)
    cat > "$EXT_FILE" << 'EOF'
[v3_server]
keyUsage = critical, digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always
EOF
    openssl x509 -req \
        -in "$CSR" -CA "$PKI/ca.crt" -CAkey "$PKI/ca.key" \
        -CAcreateserial -out "$CERT" -days 3650 -sha256 \
        -extfile "$EXT_FILE" -extensions v3_server
    rm -f "$EXT_FILE"
    ;;
client)
    EXT_FILE=$(mktemp /tmp/vpnadmin-ext.XXXXXX)
    cat > "$EXT_FILE" << 'EOF'
[v3_client]
keyUsage = critical, digitalSignature
extendedKeyUsage = clientAuth
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always
EOF
    openssl x509 -req \
        -in "$CSR" -CA "$PKI/ca.crt" -CAkey "$PKI/ca.key" \
        -set_serial "0x$SERIAL" -out "$CERT" -days 3650 -sha256 \
        -extfile "$EXT_FILE" -extensions v3_client
    rm -f "$EXT_FILE"
    ;;
*)
    echo "Usage: $0 server|client CSR CERT [SERIAL]" >&2
    exit 1
    ;;
esac
