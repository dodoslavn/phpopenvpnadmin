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
        $errors[] = t('wizard.err.user');
    if (strlen($adminPass) < 8)
        $errors[] = t('wizard.err.password');
    if ($adminPass !== $adminPass2)
        $errors[] = t('wizard.err.password2');
    if (!filter_var($serverIp, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $serverIp))
        $errors[] = t('wizard.err.ip');
    if ($port < 1 || $port > 65535)
        $errors[] = t('wizard.err.port');

    if (empty($errors)) {
        $step = 'generating';
    }
}

html_head(t('wizard.title'));
?>
<div class="wizard-wrap">
    <div class="wizard-box">
        <img src="/assets/logo.svg" alt="logo" height="48">
        <h1><?= t('wizard.title') ?></h1>

<?php if ($step === 'form'): ?>
        <p><?= t('wizard.desc') ?></p>
        <?php foreach ($errors as $e): ?>
            <?php flash($e, 'error'); ?>
        <?php endforeach; ?>
        <?= _lang_switcher_html() ?>
        <form method="post">
            <fieldset>
                <legend><?= t('wizard.admin') ?></legend>
                <label><?= t('wizard.username') ?>
                    <input type="text" name="admin_user" required
                           value="<?= h($_POST['admin_user'] ?? '') ?>">
                </label>
                <label><?= t('wizard.password') ?>
                    <input type="password" name="admin_pass" required minlength="8">
                </label>
                <label><?= t('wizard.password2') ?>
                    <input type="password" name="admin_pass2" required minlength="8">
                </label>
            </fieldset>
            <fieldset>
                <legend><?= t('wizard.vpn') ?></legend>
                <label><?= t('wizard.server_ip') ?>
                    <input type="text" name="server_ip" required
                           placeholder="<?= h(t('wizard.server_ip.placeholder')) ?>"
                           value="<?= h($_POST['server_ip'] ?? '') ?>">
                </label>
                <label><?= t('wizard.port') ?>
                    <input type="number" name="port" value="<?= h((string)($_POST['port'] ?? 1194)) ?>"
                           min="1" max="65535" required>
                </label>
            </fieldset>
            <button type="submit"><?= t('wizard.submit') ?></button>
        </form>

<?php elseif ($step === 'generating'): ?>
        <p><?= t('wizard.generating') ?></p>
        <ul class="progress-log" id="log">
            <li><?= t('wizard.log.start') ?></li>
        </ul>
<?php
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

        $log(t('wizard.log.ca'));
        if (!pki_generate_ca($serverIp)) $fail(t('wizard.fail.ca'));

        $log(t('wizard.log.servercert'));
        if (!pki_generate_server_cert($serverIp)) $fail(t('wizard.fail.servercert'));

        $log(t('wizard.log.dh'));
        if (!pki_generate_dh()) $fail(t('wizard.fail.dh'));

        $log(t('wizard.log.ta'));
        if (!pki_generate_ta_key()) $fail(t('wizard.fail.ta'));

        $log(t('wizard.log.crl'));
        pki_generate_crl();

        $log(t('wizard.log.settings'));
        $caCert = file_get_contents(PKI_DIR . '/ca.crt');
        setting_set('ca_cert',    $caCert ?: '');
        setting_set('server_ip',  $serverIp);
        setting_set('vpn_port',   (string) $port);
        setting_set('setup_done', '1');

        $log(t('wizard.log.admin'));
        create_user($adminUser, $adminPass, 'admin');

        $log(t('wizard.log.conf'));
        if (!apply_server_conf($port)) {
            $log(t('wizard.log.conf_warn'));
        }

        $log(t('wizard.log.start_ovpn'));
        if (!openvpn_service_action('start')) {
            $log(t('wizard.log.start_warn'));
        }

        $step = 'done';
?>

<?php endif; ?>

<?php if ($step === 'done'): ?>
        <?php flash(t('wizard.done'), 'success'); ?>
        <p><?= t('wizard.done.login') ?></p>
        <p><?= t('wizard.done.ssl') ?></p>
<?php endif; ?>

    </div>
</div>
<?php html_foot(); ?>
