#!/bin/bash
source "$(dirname "$0")/../lib/common.sh"
skip_if_done

info "Installing Redis and php-redis..."
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq redis-server php-redis \
    || error "Failed to install Redis"

# Localhost only, cap memory, disable persistence (not needed for rate limiting)
REDIS_CONF=/etc/redis/redis.conf
sed -i 's/^bind .*/bind 127.0.0.1/' "$REDIS_CONF"
grep -q "^maxmemory " "$REDIS_CONF" \
    || echo "maxmemory 16mb" >> "$REDIS_CONF"
grep -q "^maxmemory-policy " "$REDIS_CONF" \
    || echo "maxmemory-policy allkeys-lru" >> "$REDIS_CONF"
# Disable RDB snapshots
sed -i 's/^save /# save /' "$REDIS_CONF"

systemctl enable redis-server  >/dev/null 2>&1
systemctl restart redis-server || error "Failed to start Redis"

redis-cli ping 2>/dev/null | grep -q PONG || error "Redis not responding"
php -m 2>/dev/null | grep -qi redis || error "php-redis module not loaded"
log "Redis running (16 MB cap, localhost only), php-redis loaded"

complete_step
