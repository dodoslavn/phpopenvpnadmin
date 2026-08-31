<?php
define('RATE_LIMIT_MAX',    10);   // attempts
define('RATE_LIMIT_WINDOW', 900);  // seconds (15 min)

function rl_redis(): ?Redis {
    static $r = null;
    if ($r !== null) return $r;
    try {
        $r = new Redis();
        $r->connect('127.0.0.1', 6379, 0.5);
        return $r;
    } catch (Exception $e) {
        return null;
    }
}

function rate_limit_check(string $action, string $ip): bool {
    $redis = rl_redis();
    if (!$redis) return true; // fail open if Redis unavailable
    $count = (int) $redis->get("rl:{$action}:{$ip}");
    return $count < RATE_LIMIT_MAX;
}

function rate_limit_hit(string $action, string $ip): void {
    $redis = rl_redis();
    if (!$redis) return;
    $key   = "rl:{$action}:{$ip}";
    $count = $redis->incr($key);
    if ($count === 1) $redis->expire($key, RATE_LIMIT_WINDOW);
}

function rate_limit_clear(string $action, string $ip): void {
    $redis = rl_redis();
    if (!$redis) return;
    $redis->del("rl:{$action}:{$ip}");
}
