<?php
define('APP_NAME',    'PHP OpenVPN Admin');
define('APP_VERSION', '1.0.0');

define('DB_PATH',      '/var/lib/vpnadmin/db/vpnadmin.db');
define('PKI_DIR',      '/var/lib/vpnadmin/pki');
define('CLIENTS_DIR',  '/var/lib/vpnadmin/clients');
define('OVPN_STATUS',  '/var/log/vpnadmin/openvpn-status.log');
define('OVPN_CONF',    '/etc/openvpn/server/server.conf');

require_once __DIR__ . '/lang.php';

define('SESSION_LIFETIME', 8 * 3600); // 8 hours
define('SESSION_COOKIE',   'vpnadmin_session');

define('VPN_SUBNET',  '10.8.0.0');
define('VPN_NETMASK', '255.255.255.0');
define('VPN_DNS',     '10.8.0.1');
