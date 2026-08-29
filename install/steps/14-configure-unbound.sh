#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Configuring unbound DNS resolver..."

cat > /etc/unbound/unbound.conf.d/vpnadmin.conf << 'EOF'
server:
    # Listen only on VPN tunnel interface
    interface: 10.8.0.1
    port: 53
    do-ip4: yes
    do-ip6: no
    do-udp: yes
    do-tcp: yes

    # Only allow queries from VPN subnet
    access-control: 0.0.0.0/0 refuse
    access-control: 10.8.0.0/24 allow

    # Recursive resolver — no forwarders
    do-not-query-localhost: no

    # Security
    hide-identity: yes
    hide-version: yes
    harden-glue: yes
    harden-dnssec-stripped: yes
    use-caps-for-id: yes

    # Cache
    cache-min-ttl: 300
    cache-max-ttl: 86400

    # Logging (minimal)
    verbosity: 1
    logfile: /var/log/unbound.log
EOF

log "Written /etc/unbound/unbound.conf.d/vpnadmin.conf"

# Validate config
unbound-checkconf /etc/unbound/unbound.conf.d/vpnadmin.conf 2>/dev/null || \
    unbound-checkconf || warn "unbound config check had warnings (may need VPN interface to exist)"

log "unbound configured to listen on 10.8.0.1 for VPN clients"

complete_step
