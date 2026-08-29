<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/openvpn.php';
require_once __DIR__ . '/../includes/template.php';

$user = require_admin();
purge_expired_sessions();

$action  = $_POST['action'] ?? '';
$message = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (in_array($action, ['start', 'stop', 'restart'], true)) {
        if (openvpn_service_action($action)) {
            $message = "OpenVPN {$action}ed successfully.";
            $msgType = 'success';
        } else {
            $message = "Failed to {$action} OpenVPN.";
            $msgType = 'error';
        }
    }
}

$clients    = openvpn_status();
$totalUsers = db()->query('SELECT COUNT(*) FROM users WHERE enabled = 1')->fetchColumn();
$totalProfiles = db()->query('SELECT COUNT(*) FROM profiles WHERE revoked = 0')->fetchColumn();
$serverIp   = setting('server_ip', 'not set');
$vpnPort    = setting('vpn_port', '1194');

$services = [
    'OpenVPN'       => service_status('openvpn-server@server'),
    'Unbound DNS'   => service_status('unbound'),
    'Apache'        => service_status('apache2'),
];
$ipForward = ip_forward_enabled();

html_head('Server Status');
html_nav($user);
?>
<main>
    <h2>Server Status</h2>

    <?php if ($message): flash($message, $msgType); endif; ?>

    <div class="stat-row">
        <div class="stat">
            <span class="stat-value"><?= count($clients) ?></span>
            <span class="stat-label">Connected Clients</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= h((string)$totalUsers) ?></span>
            <span class="stat-label">Active Users</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= h((string)$totalProfiles) ?></span>
            <span class="stat-label">Active Profiles</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= h($serverIp) ?>:<?= h($vpnPort) ?></span>
            <span class="stat-label">VPN Endpoint</span>
        </div>
    </div>

    <div class="section">
        <h3>Services</h3>
        <table>
            <thead>
                <tr><th>Service</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($services as $name => $status): ?>
                <tr>
                    <td><?= h($name) ?></td>
                    <td>
                        <?php if ($status === 'active'): ?>
                            <span class="badge badge-ok">Running</span>
                        <?php else: ?>
                            <span class="badge badge-off">Stopped</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td>IP Forwarding</td>
                    <td>
                        <?php if ($ipForward): ?>
                            <span class="badge badge-ok">Enabled</span>
                        <?php else: ?>
                            <span class="badge badge-off">Disabled</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Connected Clients</h3>
        <?php if (empty($clients)): ?>
            <p class="muted">No clients connected.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Name</th><th>Remote IP</th><th>Bytes RX</th><th>Bytes TX</th><th>Connected Since</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): ?>
                    <tr>
                        <td><?= h($c['name']) ?></td>
                        <td><?= h($c['remote_ip']) ?></td>
                        <td><?= h($c['bytes_rx']) ?></td>
                        <td><?= h($c['bytes_tx']) ?></td>
                        <td><?= h($c['connected']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Service Control</h3>
        <form method="post" class="inline-form">
            <button name="action" value="start">Start</button>
            <button name="action" value="stop" class="btn-danger">Stop</button>
            <button name="action" value="restart" class="btn-warning">Restart</button>
        </form>
    </div>
</main>
<?php html_foot(); ?>
