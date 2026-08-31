<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/openvpn.php';
require_once __DIR__ . '/../includes/template.php';

$user = require_admin();

$action  = $_POST['action'] ?? '';
$message = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (in_array($action, ['start', 'stop', 'restart'], true)) {
        if (openvpn_service_action($action)) {
            $message = t('server.action.ok', ['action' => $action]);
            $msgType = 'success';
        } else {
            $message = t('server.action.fail', ['action' => $action]);
            $msgType = 'error';
        }
    }
}

$services = [
    'OpenVPN'     => service_status('openvpn-server@server'),
    'Unbound DNS' => service_status('unbound'),
    'Apache'      => service_status('apache2'),
    'Redis'       => service_status('redis-server'),
    'fail2ban'    => service_status('fail2ban'),
];
$ipForward   = ip_forward_enabled();
$redisInfo   = redis_info();
$f2bStats    = fail2ban_stats();

html_head(t('server.title'));
html_nav($user);
?>
<main>
    <h2><?= t('server.title') ?></h2>

    <?php if ($message): flash($message, $msgType); endif; ?>

    <div class="section">
        <h3><?= t('server.services') ?></h3>
        <div class="table-wrap"><table>
            <thead>
                <tr><th><?= t('server.col.service') ?></th><th><?= t('server.col.status') ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($services as $name => $status): ?>
                <tr>
                    <td><?= h($name) ?></td>
                    <td>
                        <?php if ($status === 'active'): ?>
                            <span class="badge badge-ok"><?= t('server.status.running') ?></span>
                        <?php else: ?>
                            <span class="badge badge-off"><?= t('server.status.stopped') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><?= t('server.ip_forward') ?></td>
                    <td>
                        <?php if ($ipForward): ?>
                            <span class="badge badge-ok"><?= t('server.ip_forward.on') ?></span>
                        <?php else: ?>
                            <span class="badge badge-off"><?= t('server.ip_forward.off') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table></div>
    </div>

    <div class="section">
        <h3><?= t('server.security') ?></h3>
        <div class="table-wrap"><table>
            <thead>
                <tr>
                    <th><?= t('server.sec.jail') ?></th>
                    <th><?= t('server.sec.banned_now') ?></th>
                    <th><?= t('server.sec.banned_total') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($f2bStats as $jail => $s): ?>
                <tr>
                    <td><?= h($jail) ?></td>
                    <?php if ($s === null): ?>
                        <td colspan="2" style="color:var(--muted)"><?= t('server.sec.unavailable') ?></td>
                    <?php else: ?>
                        <td><?= h((string) $s['current']) ?></td>
                        <td><?= h((string) $s['total']) ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php if ($redisInfo): ?>
        <p style="margin-top:.75rem;font-size:13px;color:var(--muted)">
            <?= t('server.redis.info', [
                'version' => h($redisInfo['version']),
                'memory'  => h($redisInfo['memory']),
                'keys'    => h((string) $redisInfo['keys']),
            ]) ?>
        </p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3><?= t('server.control') ?></h3>
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <?php $ovpnRunning = ($services['OpenVPN'] === 'active'); ?>
            <span><?= t('server.control.status') ?>: <?= $ovpnRunning
                ? '<span class="badge badge-ok">' . t('server.status.running') . '</span>'
                : '<span class="badge badge-off">' . t('server.status.stopped') . '</span>' ?></span>
            <form method="post" class="inline-form">
                <?php if (!$ovpnRunning): ?>
                    <button name="action" value="start"><?= t('server.control.start') ?></button>
                <?php else: ?>
                    <button name="action" value="stop" class="btn-danger"><?= t('server.control.stop') ?></button>
                    <button name="action" value="restart" class="btn-warning"><?= t('server.control.restart') ?></button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="section">
        <h3><?= t('server.sysinfo') ?></h3>
        <div class="table-wrap"><table>
            <tbody>
                <?php foreach (system_info() as $label => $value): ?>
                <tr><td style="width:40%;color:var(--muted)"><?= h($label) ?></td><td><?= h($value) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
</main>
<?php html_foot(); ?>
