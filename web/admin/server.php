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
            $message = "OpenVPN {$action}ed successfully.";
            $msgType = 'success';
        } else {
            $message = "Failed to {$action} OpenVPN.";
            $msgType = 'error';
        }
    }
}

$services = [
    'OpenVPN'     => service_status('openvpn-server@server'),
    'Unbound DNS' => service_status('unbound'),
    'Apache'      => service_status('apache2'),
];
$ipForward = ip_forward_enabled();

html_head('Server');
html_nav($user);
?>
<main>
    <h2>Server</h2>

    <?php if ($message): flash($message, $msgType); endif; ?>

    <div class="section">
        <h3>Services</h3>
        <div class="table-wrap"><table>
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
        </table></div>
    </div>

    <div class="section">
        <h3>OpenVPN Control</h3>
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <?php $ovpnRunning = ($services['OpenVPN'] === 'active'); ?>
            <span>Status: <?= $ovpnRunning
                ? '<span class="badge badge-ok">Running</span>'
                : '<span class="badge badge-off">Stopped</span>' ?></span>
            <form method="post" class="inline-form">
                <?php if (!$ovpnRunning): ?>
                    <button name="action" value="start">Start</button>
                <?php else: ?>
                    <button name="action" value="stop" class="btn-danger">Stop</button>
                    <button name="action" value="restart" class="btn-warning">Restart</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="section">
        <h3>System Information</h3>
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
