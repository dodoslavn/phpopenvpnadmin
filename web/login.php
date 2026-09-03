<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/template.php';
require_once __DIR__ . '/includes/ratelimit.php';

if (!is_setup()) {
    header('Location: /setup');
    exit;
}

if (current_user()) {
    header('Location: /');
    exit;
}

$error = '';
$ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!rate_limit_check('login', $ip)) {
        $error = t('login.err.ratelimit');
    } elseif ($username === '' || $password === '') {
        $error = t('login.err.required');
    } elseif (!login($username, $password)) {
        rate_limit_hit('login', $ip);
        $error = t('login.err.invalid');
    } else {
        rate_limit_clear('login', $ip);
        header('Location: /');
        exit;
    }
}

html_head(t('login.title'));
?>
<div class="login-wrap">
    <div class="login-outer">
        <div class="login-header">
            <img src="/assets/logo.svg" alt="logo" height="48">
            <div class="login-app-name"><?= h(APP_NAME) ?></div>
        </div>
        <div class="login-box">
            <h1><?= t('login.title') ?></h1>
            <?php if ($error): ?>
                <?php flash($error, 'error'); ?>
            <?php endif; ?>
            <form method="post">
                <label><?= t('login.username') ?>
                    <input type="text" name="username" autocomplete="username" required
                           value="<?= h($_POST['username'] ?? '') ?>">
                </label>
                <label><?= t('login.password') ?>
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>
                <button type="submit"><?= t('login.submit') ?></button>
            </form>
        </div>
    </div>
</div>
<?php html_foot(); ?>
