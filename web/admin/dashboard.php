<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/openvpn.php';
require_once __DIR__ . '/../includes/template.php';

$user = require_admin();
purge_expired_sessions();

$clients    = openvpn_status();
$totalUsers = db()->query('SELECT COUNT(*) FROM users WHERE enabled = 1')->fetchColumn();
$totalProfiles = db()->query('SELECT COUNT(*) FROM profiles WHERE revoked = 0')->fetchColumn();
$serverIp   = setting('server_ip', 'not set');
$vpnPort    = setting('vpn_port', '1194');

html_head('Server Status');
html_nav($user);
?>
<main>
    <h2>Server Status</h2>

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
        <h3>Connected Clients</h3>
        <?php if (empty($clients)): ?>
            <p class="muted">No clients connected.</p>
        <?php else: ?>
            <div class="table-wrap"><table>
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
            </table></div>
        <?php endif; ?>
    </div>

</main>
<?php html_foot(); ?>
