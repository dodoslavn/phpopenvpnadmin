<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/openvpn.php';
require_once __DIR__ . '/../includes/template.php';

$admin   = require_admin();
$message = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverIp = trim($_POST['server_ip'] ?? '');
    $vpnPort  = (int) ($_POST['vpn_port'] ?? 1194);

    if (!filter_var($serverIp, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $serverIp)) {
        $message = t('settings.err.ip');
        $msgType = 'error';
    } elseif ($vpnPort < 1 || $vpnPort > 65535) {
        $message = t('settings.err.port');
        $msgType = 'error';
    } else {
        setting_set('server_ip', $serverIp);
        setting_set('vpn_port',  (string) $vpnPort);
        apply_server_conf($vpnPort);
        $message = t('settings.saved.ok');
        $msgType = 'success';
    }
}

$serverIp = setting('server_ip', '');
$vpnPort  = setting('vpn_port', '1194');

html_head(t('settings.title'));
html_nav($admin);
?>
<main>
    <h2><?= t('settings.title') ?></h2>

    <?php if ($message): flash($message, $msgType); endif; ?>

    <div class="section">
        <h3><?= t('settings.vpn') ?></h3>
        <form method="post" class="form-grid">
            <label><?= t('settings.server_ip') ?>
                <input type="text" name="server_ip" value="<?= h($serverIp) ?>"
                       placeholder="<?= h(t('settings.server_ip.placeholder')) ?>" required>
            </label>
            <label><?= t('settings.port') ?>
                <input type="number" name="vpn_port" value="<?= h($vpnPort) ?>"
                       min="1" max="65535" required>
            </label>
            <button type="submit"><?= t('settings.save') ?></button>
        </form>
    </div>

    <div class="section">
        <h3><?= t('settings.ca') ?></h3>
        <p class="muted"><?= t('settings.ca.desc') ?></p>
        <textarea readonly rows="12"><?= h(setting('ca_cert', t('settings.ca.empty'))) ?></textarea>
    </div>
</main>
<?php html_foot(); ?>
