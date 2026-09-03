<?php
require_once __DIR__ . '/config.php';

/**
 * Run a whitelisted privileged command via sudo.
 * NEVER pass user input directly — only use pre-validated, sanitized values.
 */
function run_privileged(array $cmd): array {
    $escaped = array_map('escapeshellarg', $cmd);
    $cmdline = 'sudo ' . implode(' ', $escaped);
    exec($cmdline . ' 2>&1', $output, $code);
    return ['code' => $code, 'output' => implode("\n", $output)];
}

function safe_filename(string $name): string {
    $clean = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);
    if (strlen($clean) < 1 || strlen($clean) > 64) {
        throw new InvalidArgumentException('Invalid profile name');
    }
    return $clean;
}

// ── PKI: First-run setup ──────────────────────────────────────────────────

function pki_generate_ca(string $serverIp): bool {
    $pki = PKI_DIR;

    // Generate CA key
    $r = run_privileged(['/usr/bin/openssl', 'genrsa', '-out', "{$pki}/ca.key", '4096']);
    if ($r['code'] !== 0) return false;

    // Generate CA cert (self-signed, 10 years)
    $r = run_privileged([
        '/usr/bin/openssl', 'req', '-x509', '-new', '-nodes',
        '-key', "{$pki}/ca.key",
        '-sha256', '-days', '3650',
        '-out', "{$pki}/ca.crt",
        '-subj', "/CN=phpopenvpnadmin-CA/O=vpnadmin/C=EU"
    ]);
    if ($r['code'] !== 0) return false;

    run_privileged(['/bin/chmod', '600', "{$pki}/ca.key"]);
    run_privileged(['/bin/chmod', '644', "{$pki}/ca.crt"]);

    return true;
}

function pki_generate_server_cert(string $serverIp): bool {
    $pki = PKI_DIR;

    // Server key
    $r = run_privileged(['/usr/bin/openssl', 'genrsa', '-out', "{$pki}/server.key", '4096']);
    if ($r['code'] !== 0) return false;

    // Server CSR
    $r = run_privileged([
        '/usr/bin/openssl', 'req', '-new',
        '-key', "{$pki}/server.key",
        '-out', "{$pki}/server.csr",
        '-subj', "/CN=vpn-server/O=vpnadmin/C=EU"
    ]);
    if ($r['code'] !== 0) return false;

    // Sign with CA — wrapper adds keyUsage/extendedKeyUsage extensions
    $r = run_privileged([
        '/usr/local/bin/vpnadmin-sign-cert', 'server',
        "{$pki}/server.csr",
        "{$pki}/server.crt",
    ]);
    if ($r['code'] !== 0) return false;

    run_privileged(['/bin/chmod', '600', "{$pki}/server.key"]);
    run_privileged(['/bin/chmod', '644', "{$pki}/server.crt"]);

    return true;
}

function pki_generate_dh(): bool {
    $r = run_privileged([
        '/usr/bin/openssl', 'dhparam',
        '-out', PKI_DIR . '/dh.pem',
        '2048'
    ]);
    return $r['code'] === 0;
}

function pki_generate_ta_key(): bool {
    $pki = PKI_DIR;
    $r = run_privileged(['/usr/sbin/openvpn', '--genkey', 'secret', "{$pki}/ta.key"]);
    if ($r['code'] !== 0) return false;

    // 640 root:www-data so PHP can read it for .ovpn generation without sudo
    run_privileged(['/bin/chown', 'root:www-data', "{$pki}/ta.key"]);
    run_privileged(['/bin/chmod', '640', "{$pki}/ta.key"]);
    return true;
}

function pki_generate_crl(): bool {
    // openssl.cnf, index.txt, crlnumber are created by the installer in PKI_DIR
    $pki = PKI_DIR;
    $r = run_privileged([
        '/usr/bin/openssl', 'ca',
        '-config', "{$pki}/openssl.cnf",
        '-gencrl',
        '-keyfile', "{$pki}/ca.key",
        '-cert', "{$pki}/ca.crt",
        '-out', "{$pki}/crl.pem"
    ]);
    return $r['code'] === 0;
}

// ── Client certificate generation ────────────────────────────────────────

function generate_client_cert(string $name, int $userId, string $username): ?array {
    $name  = safe_filename($name);
    $pki   = PKI_DIR;
    $dir   = CLIENTS_DIR . "/{$userId}_{$name}";

    // Prevent overwrite
    if (is_dir($dir)) return null;
    // CLIENTS_DIR is 770 root:www-data so www-data can create subdirs
    if (!mkdir($dir, 0750, true)) return null;

    // Client key
    $r = run_privileged(['/usr/bin/openssl', 'genrsa', '-out', "{$dir}/client.key", '4096']);
    if ($r['code'] !== 0) return null;

    // CSR
    $r = run_privileged([
        '/usr/bin/openssl', 'req', '-new',
        '-key', "{$dir}/client.key",
        '-out', "{$dir}/client.csr",
        '-subj', "/CN={$username}_{$name}/O=vpnadmin/C=EU"
    ]);
    if ($r['code'] !== 0) return null;

    // Sign — wrapper adds keyUsage/extendedKeyUsage extensions
    $serial = strtoupper(bin2hex(random_bytes(8)));
    $r = run_privileged([
        '/usr/local/bin/vpnadmin-sign-cert', 'client',
        "{$dir}/client.csr",
        "{$dir}/client.crt",
        $serial,
    ]);
    if ($r['code'] !== 0) return null;

    // 640 root:www-data so PHP can read the key for .ovpn generation without sudo
    run_privileged(['/bin/chown', 'root:www-data', "{$dir}/client.key"]);
    run_privileged(['/bin/chmod', '640', "{$dir}/client.key"]);
    run_privileged(['/bin/chmod', '644', "{$dir}/client.crt"]);

    return [
        'serial' => $serial,
        'dir'    => $dir,
    ];
}

function build_ovpn(string $name, int $userId, string $serverIp, int $port = 1194): ?string {
    $name = safe_filename($name);
    $pki  = PKI_DIR;
    $dir  = CLIENTS_DIR . "/{$userId}_{$name}";

    $ca     = file_get_contents("{$pki}/ca.crt");
    $cert   = file_get_contents("{$dir}/client.crt");
    $key    = file_get_contents("{$dir}/client.key");
    $taKey  = file_get_contents("{$pki}/ta.key");

    if (!$ca || !$cert || !$key || !$taKey) return null;

    // Accept IP or hostname; sanitize to safe characters only
    $serverHost = preg_replace('/[^a-zA-Z0-9.\-]/', '', $serverIp);
    if ($serverHost === '') $serverHost = 'INVALID';
    $port = max(1, min(65535, $port));

    return <<<OVPN
client
dev tun
proto udp
remote {$serverHost} {$port}
resolv-retry infinite
nobind
persist-key
persist-tun
auth-user-pass
remote-cert-tls server
verify-x509-name vpn-server name
auth SHA256
cipher AES-256-GCM
tls-version-min 1.2
verb 3

<ca>
{$ca}</ca>

<cert>
{$cert}</cert>

<key>
{$key}</key>

<tls-auth>
{$taKey}</tls-auth>
key-direction 1
OVPN;
}

// ── Certificate revocation ────────────────────────────────────────────────

function revoke_client_cert(string $serial): bool {
    $pki  = PKI_DIR;

    // Find the cert file by serial
    $certFile = null;
    foreach (glob(CLIENTS_DIR . '/*/client.crt') as $f) {
        $out = shell_exec("openssl x509 -in " . escapeshellarg($f) . " -noout -serial 2>/dev/null");
        if ($out && strpos(strtoupper($out), strtoupper($serial)) !== false) {
            $certFile = $f;
            break;
        }
    }

    if (!$certFile) return false;

    $r = run_privileged([
        '/usr/bin/openssl', 'ca',
        '-config', "{$pki}/openssl.cnf",
        '-revoke', $certFile,
        '-keyfile', "{$pki}/ca.key",
        '-cert', "{$pki}/ca.crt"
    ]);
    if ($r['code'] !== 0) return false;

    // Regenerate CRL
    return pki_generate_crl();
}

// ── OpenVPN status ────────────────────────────────────────────────────────

function openvpn_status(): array {
    $r = run_privileged(['/usr/bin/cat', OVPN_STATUS]);
    if ($r['code'] !== 0) return [];

    $clients  = [];
    $newFmt   = false; // OpenVPN 2.6+ CSV format (lines prefixed with CLIENT_LIST,)
    $inClients = false;

    foreach (explode("\n", $r['output']) as $line) {
        // OpenVPN 2.6+: detect format from TITLE line
        if (str_starts_with($line, 'TITLE,'))             { $newFmt = true; continue; }

        if ($newFmt) {
            if (!str_starts_with($line, 'CLIENT_LIST,'))  continue;
            $parts = explode(',', $line);
            // CLIENT_LIST,cn,real_addr,virtual_addr,virtual_addr6,bytes_rx,bytes_tx,connected_since,connected_since_t,...
            if (count($parts) < 8) continue;
            $cn  = $parts[1];
            $sep = strrpos($cn, '_');
            $clients[] = [
                'name'         => $sep !== false ? substr($cn, 0, $sep) : $cn,
                'profile'      => $sep !== false ? substr($cn, $sep + 1) : '',
                'remote_ip'    => $parts[2],
                'bytes_rx'     => $parts[5],
                'bytes_tx'     => $parts[6],
                'connected_ts' => isset($parts[8]) && is_numeric($parts[8]) ? (int)$parts[8] : null,
                'connected'    => $parts[7],
            ];
        } else {
            // Legacy format
            if (str_starts_with($line, 'Common Name')) { $inClients = true; continue; }
            if (str_starts_with($line, 'ROUTING'))     { $inClients = false; continue; }
            if (!$inClients || trim($line) === '')      continue;
            $parts = explode(',', $line);
            if (count($parts) >= 4) {
                $clients[] = [
                    'name'         => $parts[0],
                    'profile'      => '',
                    'remote_ip'    => $parts[1],
                    'bytes_rx'     => $parts[2],
                    'bytes_tx'     => $parts[3],
                    'connected_ts' => null,
                    'connected'    => $parts[4] ?? '',
                ];
            }
        }
    }

    return $clients;
}

function apply_server_conf(int $port): bool {
    $port = max(1, min(65535, $port));
    $conf = <<<CONF
# OpenVPN Server Configuration — managed by phpopenvpnadmin
port {$port}
proto udp
dev tun

ca   /var/lib/vpnadmin/pki/ca.crt
cert /var/lib/vpnadmin/pki/server.crt
key  /var/lib/vpnadmin/pki/server.key
dh   /var/lib/vpnadmin/pki/dh.pem

tls-auth /var/lib/vpnadmin/pki/ta.key 0
key-direction 0

cipher AES-256-GCM
auth SHA256
tls-version-min 1.2

server 10.8.0.0 255.255.255.0

push "redirect-gateway def1 bypass-dhcp"
push "dhcp-option DNS 10.8.0.1"

keepalive 10 120

user nobody
group nogroup
persist-key
persist-tun

status      /var/log/vpnadmin/openvpn-status.log
log-append  /var/log/vpnadmin/openvpn.log
verb 3

auth-user-pass-verify /etc/openvpn/server/check-password.sh via-env
script-security 3

up   /etc/openvpn/server/up.sh
down /etc/openvpn/server/down.sh

crl-verify /var/lib/vpnadmin/pki/crl.pem
CONF;

    // Write to /tmp then sudo cp into place — avoids complex sed sudoers rules
    $tmp = '/tmp/vpnadmin-server.conf';
    if (file_put_contents($tmp, $conf) === false) return false;
    $r = run_privileged(['/bin/cp', $tmp, OVPN_CONF]);
    unlink($tmp);
    return $r['code'] === 0;
}

function service_status(string $service): string {
    exec('systemctl is-active ' . escapeshellarg($service) . ' 2>/dev/null', $out, $code);
    return $code === 0 ? 'active' : 'inactive';
}

function ip_forward_enabled(): bool {
    $val = trim((string) @file_get_contents('/proc/sys/net/ipv4/ip_forward'));
    return $val === '1';
}

function openvpn_service_action(string $action): bool {
    $allowed = ['start', 'stop', 'restart'];
    if (!in_array($action, $allowed, true)) return false;

    $r = run_privileged(['/bin/systemctl', $action, 'openvpn-server@server']);
    return $r['code'] === 0;
}

function redis_info(): array {
    try {
        $r = new Redis();
        $r->connect('127.0.0.1', 6379, 0.5);
        $info = $r->info();
        return [
            'version' => $info['redis_version'] ?? '?',
            'memory'  => $info['used_memory_human'] ?? '?',
            'keys'    => $r->dbSize(),
        ];
    } catch (Exception $e) {
        return [];
    }
}

function fail2ban_stats(): array {
    $stats = [];
    foreach (['sshd', 'openvpn-auth'] as $jail) {
        $r = run_privileged(['/usr/bin/fail2ban-client', 'status', $jail]);
        if ($r['code'] !== 0) {
            $stats[$jail] = null;
            continue;
        }
        preg_match('/Currently banned:\s*(\d+)/', $r['output'], $cur);
        preg_match('/Total banned:\s*(\d+)/',     $r['output'], $tot);
        $stats[$jail] = [
            'current' => (int) ($cur[1] ?? 0),
            'total'   => (int) ($tot[1] ?? 0),
        ];
    }
    return $stats;
}

function system_info(): array {
    $read = fn(string $cmd) => trim((string) shell_exec($cmd . ' 2>/dev/null')) ?: '—';

    // OS name from /etc/os-release
    $osRelease = @parse_ini_file('/etc/os-release') ?: [];
    $os = $osRelease['PRETTY_NAME'] ?? $read('uname -s');

    // OpenVPN version — first line, strip "OpenVPN " prefix
    $ovpnRaw = $read('openvpn --version');
    preg_match('/OpenVPN (\S+)/', $ovpnRaw, $m);
    $ovpn = $m[1] ?? $ovpnRaw;

    // Apache version
    $apacheRaw = $read('apache2 -v');
    preg_match('#Apache/(\S+)#', $apacheRaw, $m);
    $apache = $m[1] ?? $apacheRaw;

    // Redis version
    $redisInfo = redis_info();
    $redis = $redisInfo['version'] ?? '—';

    // Uptime human-readable
    $uptimeRaw = $read('uptime -p');
    $uptime = str_replace('up ', '', $uptimeRaw);

    return [
        'App version' => APP_VERSION,
        'OS'          => $os,
        'Kernel'      => $read('uname -r'),
        'Uptime'      => $uptime,
        'OpenVPN'     => $ovpn,
        'Apache'      => $apache,
        'PHP'         => PHP_VERSION,
        'Redis'       => $redis,
    ];
}
