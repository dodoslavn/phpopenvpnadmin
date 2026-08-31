<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/template.php';

$user   = require_auth();
$userId = (int) $user['user_id'];

$message = '';
$msgType = 'info';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $new2     = $_POST['new_password2'] ?? '';

    // Verify current password
    $row = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $row->execute([$userId]);
    $row = $row->fetch();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        $errors[] = t('account.err.current');
    } elseif (strlen($new) < 8) {
        $errors[] = t('account.err.length');
    } elseif ($new !== $new2) {
        $errors[] = t('account.err.match');
    }

    if (empty($errors)) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
        $message = t('account.ok');
        $msgType = 'success';
    }
}

html_head(t('account.title'));
html_nav($user);
?>
<main>
    <h2><?= t('account.title') ?></h2>

    <?php if ($message): flash($message, $msgType); endif; ?>
    <?php foreach ($errors as $e): flash($e, 'error'); endforeach; ?>

    <div class="section">
        <h3><?= t('account.change_password') ?></h3>
        <form method="post" class="form-grid">
            <label><?= t('account.current_password') ?>
                <input type="password" name="current_password" required autocomplete="current-password">
            </label>
            <label><?= t('account.new_password') ?>
                <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
            </label>
            <label><?= t('account.new_password2') ?>
                <input type="password" name="new_password2" required minlength="8" autocomplete="new-password">
            </label>
            <button type="submit"><?= t('account.submit') ?></button>
        </form>
    </div>
</main>
<?php html_foot(); ?>
