#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing automatic CRL renewal timer..."

# openssl.cnf (step 18) sets default_crl_days = 30. The CRL itself is only
# regenerated when a cert is revoked (see revoke_client_cert() in openvpn.php) —
# on a server where nobody is ever revoked, nothing else ever touches it, and
# it silently expires. An expired CRL makes OpenVPN reject every client's TLS
# handshake, not just revoked ones. This timer renews it on a fixed schedule
# regardless of revocation activity, and runs independently of Apache/PHP so
# a broken web app can't take the CRL down with it.
cat > /usr/local/bin/vpnadmin-renew-crl << 'EOF'
#!/bin/bash
# Regenerates the OpenVPN CRL. Run periodically by vpnadmin-renew-crl.timer.
set -uo pipefail

PKI="/var/lib/vpnadmin/pki"

# PKI isn't created until the setup wizard runs — nothing to renew yet.
if [ ! -f "${PKI}/ca.key" ] || [ ! -f "${PKI}/openssl.cnf" ]; then
    logger -t vpnadmin-crl "PKI not initialized yet, skipping CRL renewal"
    exit 0
fi

output=$(openssl ca -config "${PKI}/openssl.cnf" -gencrl \
    -keyfile "${PKI}/ca.key" -cert "${PKI}/ca.crt" \
    -out "${PKI}/crl.pem" 2>&1)
code=$?

if [ "$code" -eq 0 ]; then
    chmod 644 "${PKI}/crl.pem"
    next_update=$(openssl crl -in "${PKI}/crl.pem" -noout -nextupdate 2>/dev/null | cut -d= -f2)
    logger -t vpnadmin-crl "CRL renewed, next update: ${next_update}"
else
    logger -p auth.err -t vpnadmin-crl "CRL renewal FAILED, VPN clients will be locked out once the current CRL expires: ${output}"
    exit 1
fi
EOF
chmod 755 /usr/local/bin/vpnadmin-renew-crl
chown root:root /usr/local/bin/vpnadmin-renew-crl
log "Installed CRL renewal script at /usr/local/bin/vpnadmin-renew-crl"

cat > /etc/systemd/system/vpnadmin-renew-crl.service << 'EOF'
[Unit]
Description=Renew phpopenvpnadmin OpenVPN CRL

[Service]
Type=oneshot
ExecStart=/usr/local/bin/vpnadmin-renew-crl
EOF

cat > /etc/systemd/system/vpnadmin-renew-crl.timer << 'EOF'
[Unit]
Description=Periodic renewal of phpopenvpnadmin OpenVPN CRL

[Timer]
# default_crl_days is 30 (see openssl.cnf); renew weekly so a single missed
# run still leaves roughly three weeks of slack before the CRL expires.
OnBootSec=10min
OnUnitActiveSec=7d
RandomizedDelaySec=30min
Persistent=true

[Install]
WantedBy=timers.target
EOF

systemctl daemon-reload
systemctl enable --now vpnadmin-renew-crl.timer || error "Failed to enable vpnadmin-renew-crl.timer"
log "Enabled vpnadmin-renew-crl.timer (renews weekly; CRL validity is 30 days)"

complete_step
