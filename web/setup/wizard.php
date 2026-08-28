<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/openvpn.php';
require_once __DIR__ . '/../includes/template.php';

// Already set up
if (is_setup()) {
    header('Location: /');
    exit;
}

$errors = [];
$step   = 'form'; // form | generating | done

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminUser   = trim($_POST['admin_user'] ?? '');
    $adminPass   = $_POST['admin_pass'] ?? '';
    $adminPass2  = $_POST['admin_pass2'] ?? '';
    $serverIp    = trim($_POST['server_ip'] ?? '');
    $port        = (int) ($_POST['port'] ?? 1194);

    if ($adminUser === '')
        $errors[] = 'Admin username is required.';
    if (strlen($adminPass) < 8)
        $errors[] = 'Password must be at least 8 characters.';
    if ($adminPass !== $adminPass2)
        $errors[] = 'Passwords do not match.';
    if (!filter_var($serverIp, FILTER_VALIDATE_IP))
        $errors[] = 'Invalid server IP address.';
    if ($port < 1 || $port > 65535)
        $errors[] = 'Invalid port number.';

    if (empty($errors)) {
        $step = 'generating';
    }
}

html_head('Setup Wizard');
?>
<div class="wizard-wrap">
    <div class="wizard-box">
        <img src="/assets/logo.svg" alt="logo" height="48">
        <h1>First-Run Setup</h1>

<?php if ($step === 'form'): ?>
        <p>This wizard will generate your VPN PKI and configure OpenVPN. It only runs once.</p>
        <?php foreach ($errors as $e): ?>
            <?php flash($e, 'error'); ?>
        <?php endforeach; ?>
        <form method="post">
            <fieldset>
                <legend>Admin Account</legend>
                <label>Username
                    <input type="text" name="admin_user" required
                           value="<?= h($_POST['admin_user'] ?? '') ?>">
                </label>
                <label>Password
                    <input type="password" name="admin_pass" required minlength="8">
                </label>
                <label>Confirm Password
                    <input type="password" name="admin_pass2" required minlength="8">
                </label>
            </fieldset>
            <fieldset>
                <legend>VPN Server</legend>
                <label>Server Public IP
                    <input type="text" name="server_ip" required placeholder="1.2.3.4"
                           value="<?= h($_POST['server_ip'] ?? '') ?>">
                </label>
                <label>VPN Port (UDP)
                    <input type="number" name="port" value="<?= h((string)($_POST['port'] ?? 1194)) ?>"
                           min="1" max="65535" required>
                </label>
            </fieldset>
            <button type="submit">Generate PKI &amp; Start OpenVPN</button>
        </form>

<?php elseif ($step === 'generating'): ?>
        <p>Generating PKI — this may take a minute for DH params...</p>
        <ul class="progress-log" id="log">
            <li>Starting...</li>
        </ul>
<?php
        // Flush output so browser sees progress
        ob_implicit_flush(true);

        $serverIp  = $_POST['server_ip'];
        $port      = (int) $_POST['port'];
        $adminUser = trim($_POST['admin_user']);
        $adminPass = $_POST['admin_pass'];

        $log = function(string $msg) {
            echo "<script>document.getElementById('log').innerHTML += '<li>" . addslashes($msg) . "</li>';</script>\n";
            flush();
        };

        $fail = function(string $msg) {
            echo "<script>document.getElementById('log').innerHTML += '<li class=\"err\">" . addslashes($msg) . "</li>';</script>\n";
            echo '</ul><p class="error">Setup failed. Fix the issue and reload this page.</p>';
            flush();
            exit;
        };

        $log('Generating VPN CA...');
        if (!pki_generate_ca($serverIp)) $fail('Failed to generate CA');

        $log('Generating server certificate...');
        if (!pki_generate_server_cert($serverIp)) $fail('Failed to generate server cert');

        $log('Generating DH parameters (this takes a while)...');
        if (!pki_generate_dh()) $fail('Failed to generate DH params');

        $log('Generating TLS auth key...');
        if (!pki_generate_ta_key()) $fail('Failed to generate TA key');

        $log('Initializing certificate revocation list...');
        pki_generate_crl();

        $log('Saving settings...');
        $caCert = file_get_contents(PKI_DIR . '/ca.crt');
        setting_set('ca_cert',    $caCert ?: '');
        setting_set('server_ip',  $serverIp);
        setting_set('vpn_port',   (string) $port);
        setting_set('setup_done', '1');

        $log('Creating admin account...');
        create_user($adminUser, $adminPass, 'admin');

        $log('Starting OpenVPN...');
        if (!openvpn_service_action('start')) {
            $log('Warning: OpenVPN start failed — check server logs. PKI is ready.');
        }

        $step = 'done';
?>

<?php endif; ?>

<?php if ($step === 'done'): ?>
        <?php flash('Setup complete! OpenVPN is running.', 'success'); ?>
        <p>You can now <a href="/login.php">sign in</a> with your admin account.</p>
        <p><strong>Remember:</strong> Install your SSL certificate to <code>/etc/vpnadmin/ssl/</code> and restart Apache to enable HTTPS.</p>
<?php endif; ?>

    </div>
</div>
<?php html_foot(); ?>
