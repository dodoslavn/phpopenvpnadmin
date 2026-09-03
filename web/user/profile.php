<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/openvpn.php';
require_once __DIR__ . '/../includes/template.php';

$user   = require_auth();
$userId = (int) $user['user_id'];

// Download an existing profile
if (isset($_GET['download'])) {
    $profileId = (int) $_GET['download'];
    $stmt = db()->prepare('SELECT * FROM profiles WHERE id = ? AND user_id = ? AND revoked = 0');
    $stmt->execute([$profileId, $userId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        http_response_code(404);
        die('Profile not found or revoked.');
    }

    $serverIp = setting('server_ip');
    $port     = (int) setting('vpn_port', '1194');
    $ovpn     = build_ovpn($profile['name'], $userId, $serverIp, $port);

    if (!$ovpn) {
        http_response_code(500);
        die('Could not build .ovpn file. Check server logs.');
    }

    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $profile['name']) . '.ovpn';
    header('Content-Type: application/x-openvpn-profile');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $ovpn;
    exit;
}

// Generate a new profile
$errors  = [];
$message = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['profile_name'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9\-]{2,32}$/', $name)) {
        $errors[] = t('profile.err.name');
    }

    if (empty($errors)) {
        $dup = db()->prepare('SELECT id FROM profiles WHERE user_id = ? AND name = ? AND revoked = 0');
        $dup->execute([$userId, $name]);
        if ($dup->fetch()) {
            $errors[] = t('profile.err.duplicate', ['name' => $name]);
        }
    }

    if (empty($errors)) {
        $result = generate_client_cert($name, $userId, $user['username']);

        if (!$result) {
            $errors[] = t('profile.err.genfail');
        } else {
            db()->prepare(
                'INSERT INTO profiles (user_id, name, cert_serial) VALUES (?, ?, ?)'
            )->execute([$userId, $name, $result['serial']]);

            $profileId = (int) db()->lastInsertId();
            $message   = t('profile.created.ok', ['name' => $name]);
            $msgType   = 'success';

            header("Location: /profile?download={$profileId}");
            exit;
        }
    }
}

html_head(t('profile.title'));
html_nav($user);
?>
<main>
    <h2><?= t('profile.title') ?></h2>

    <p><?= t('profile.desc1') ?></p>
    <p><?= t('profile.desc2') ?></p>

    <?php if ($message): flash($message, $msgType); endif; ?>
    <?php foreach ($errors as $e): flash($e, 'error'); endforeach; ?>

    <form method="post" class="form-grid">
        <label><?= t('profile.name.label') ?>
            <input type="text" name="profile_name" required
                   pattern="[a-zA-Z0-9_\-]{2,32}"
                   placeholder="<?= h(t('profile.name.placeholder')) ?>"
                   value="<?= h($_POST['profile_name'] ?? '') ?>">
            <small><?= t('profile.name.hint') ?></small>
        </label>
        <button type="submit"><?= t('profile.submit') ?></button>
    </form>

    <p><a href="/profiles"><?= t('profile.back') ?></a></p>
</main>
<?php html_foot(); ?>
