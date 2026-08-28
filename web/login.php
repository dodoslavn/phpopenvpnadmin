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
        $error = 'Username and password are required.';
    } elseif (!login($username, $password)) {
        $error = 'Invalid username or password.';
    } else {
        header('Location: /');
        exit;
    }
}

html_head('Login');
?>
<div class="login-wrap">
    <div class="login-box">
        <img src="/assets/logo.svg" alt="logo" height="48">
        <h1>Sign In</h1>
        <?php if ($error): ?>
            <?php flash($error, 'error'); ?>
        <?php endif; ?>
        <form method="post">
            <label>Username
                <input type="text" name="username" autocomplete="username" required
                       value="<?= h($_POST['username'] ?? '') ?>">
            </label>
            <label>Password
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button type="submit">Sign In</button>
        </form>
    </div>
</div>
<?php html_foot(); ?>
