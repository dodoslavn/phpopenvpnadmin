#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Switching to iptables-legacy..."

update-alternatives --set iptables  /usr/sbin/iptables-legacy  || error "Failed to set iptables-legacy"
update-alternatives --set ip6tables /usr/sbin/ip6tables-legacy || error "Failed to set ip6tables-legacy"
update-alternatives --set arptables /usr/sbin/arptables-legacy 2>/dev/null || warn "arptables-legacy not available"
update-alternatives --set ebtables  /usr/sbin/ebtables-legacy  2>/dev/null || warn "ebtables-legacy not available"

version=$(iptables --version)
echo "$version" | grep -q "legacy" || error "iptables is not legacy: ${version}"
log "iptables switched to legacy: ${version}"

complete_step
