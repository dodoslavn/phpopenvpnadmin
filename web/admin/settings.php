<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/template.php';

$admin   = require_admin();
$message = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverIp = trim($_POST['server_ip'] ?? '');
    $vpnPort  = (int) ($_POST['vpn_port'] ?? 1194);

    if (!filter_var($serverIp, FILTER_VALIDATE_IP)) {
        $message = 'Invalid IP address.';
        $msgType = 'error';
    } elseif ($vpnPort < 1 || $vpnPort > 65535) {
        $message = 'Invalid port.';
        $msgType = 'error';
    } else {
        setting_set('server_ip', $serverIp);
        setting_set('vpn_port',  (string) $vpnPort);
        $message = 'Settings saved. New profiles will use the updated values.';
        $msgType = 'success';
    }
}

$serverIp = setting('server_ip', '');
$vpnPort  = setting('vpn_port', '1194');

html_head('Settings');
html_nav($admin);
?>
<main>
    <h2>Settings</h2>

    <?php if ($message): flash($message, $msgType); endif; ?>

    <div class="section">
        <h3>VPN Server</h3>
        <form method="post" class="form-grid">
            <label>Server Public IP
                <input type="text" name="server_ip" value="<?= h($serverIp) ?>" required>
            </label>
            <label>VPN Port (UDP)
                <input type="number" name="vpn_port" value="<?= h($vpnPort) ?>"
                       min="1" max="65535" required>
            </label>
            <button type="submit">Save Settings</button>
        </form>
    </div>

    <div class="section">
        <h3>VPN CA Certificate</h3>
        <p class="muted">This is your VPN CA cert — clients embed this to verify the server.</p>
        <textarea readonly rows="12"><?= h(setting('ca_cert', 'Not generated yet.')) ?></textarea>
    </div>
</main>
<?php html_foot(); ?>
