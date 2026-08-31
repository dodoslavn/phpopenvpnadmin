<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/template.php';

if (!is_setup()) {
    header('Location: /setup/wizard.php');
    exit;
}

if (current_user()) {
    header('Location: /');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = t('login.err.required');
    } elseif (!login($username, $password)) {
        $error = t('login.err.invalid');
    } else {
        header('Location: /');
        exit;
    }
}

html_head(t('login.title'));
?>
<div class="login-wrap">
    <div class="login-box">
        <img src="/assets/logo.svg" alt="logo" height="48">
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
        <?php if (count(lang_list()) > 1): ?>
            <div class="lang-box"><?= _lang_switcher_html() ?></div>
        <?php endif; ?>
    </div>
</div>
<?php html_foot(); ?>
