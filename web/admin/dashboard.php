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

html_head(t('dashboard.title'));
html_nav($user);
?>
<main>
    <h2><?= t('dashboard.title') ?></h2>

    <div class="stat-row">
        <div class="stat">
            <span class="stat-value"><?= count($clients) ?></span>
            <span class="stat-label"><?= t('dashboard.connected') ?></span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= h((string)$totalUsers) ?></span>
            <span class="stat-label"><?= t('dashboard.active_users') ?></span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= h((string)$totalProfiles) ?></span>
            <span class="stat-label"><?= t('dashboard.active_profiles') ?></span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= h($serverIp) ?>:<?= h($vpnPort) ?></span>
            <span class="stat-label"><?= t('dashboard.endpoint') ?></span>
        </div>
    </div>

    <div class="section">
        <h3><?= t('dashboard.connected') ?></h3>
        <?php if (empty($clients)): ?>
            <p class="muted"><?= t('dashboard.no_clients') ?></p>
        <?php else: ?>
            <div class="table-wrap"><table>
                <thead>
                    <tr>
                        <th><?= t('dashboard.col.name') ?></th>
                        <th><?= t('dashboard.col.profile') ?></th>
                        <th><?= t('dashboard.col.remote_ip') ?></th>
                        <th><?= t('dashboard.col.bytes_rx') ?></th>
                        <th><?= t('dashboard.col.bytes_tx') ?></th>
                        <th><?= t('dashboard.col.connected') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): ?>
                    <tr>
                        <td><?= h($c['name']) ?></td>
                        <td><?= h($c['profile']) ?: '<span style="color:var(--muted)">—</span>' ?></td>
                        <td><?= h($c['remote_ip']) ?></td>
                        <td><?= fmt_bytes($c['bytes_rx']) ?></td>
                        <td><?= fmt_bytes($c['bytes_tx']) ?></td>
                        <td><?= fmt_connected($c['connected_ts'], $c['connected']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</main>
<?php html_foot(); ?>
