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

    if (!preg_match('/^[a-zA-Z0-9_\-]{2,32}$/', $name)) {
        $errors[] = 'Profile name: 2–32 characters, letters/numbers/underscore/dash only.';
    }

    // Check for duplicate name for this user
    if (empty($errors)) {
        $dup = db()->prepare('SELECT id FROM profiles WHERE user_id = ? AND name = ? AND revoked = 0');
        $dup->execute([$userId, $name]);
        if ($dup->fetch()) {
            $errors[] = "You already have an active profile named '{$name}'.";
        }
    }

    if (empty($errors)) {
        $result = generate_client_cert($name, $userId);

        if (!$result) {
            $errors[] = 'Certificate generation failed. Please try again.';
        } else {
            db()->prepare(
                'INSERT INTO profiles (user_id, name, cert_serial) VALUES (?, ?, ?)'
            )->execute([$userId, $name, $result['serial']]);

            $profileId = (int) db()->lastInsertId();
            $message   = "Profile '{$name}' created. Download it below.";
            $msgType   = 'success';

            // Redirect to download
            header("Location: /user/profile.php?download={$profileId}");
            exit;
        }
    }
}

html_head('Generate VPN Profile');
html_nav($user);
?>
<main>
    <h2>Generate New VPN Profile</h2>

    <p>Each profile is a <code>.ovpn</code> file for one device. Download it and import it into your OpenVPN client.</p>
    <p>You will need to enter your password each time you connect.</p>

    <?php if ($message): flash($message, $msgType); endif; ?>
    <?php foreach ($errors as $e): flash($e, 'error'); endforeach; ?>

    <form method="post" class="form-grid">
        <label>Profile Name
            <input type="text" name="profile_name" required
                   pattern="[a-zA-Z0-9_\-]{2,32}"
                   placeholder="e.g. laptop, phone, work-pc"
                   value="<?= h($_POST['profile_name'] ?? '') ?>">
            <small>No spaces. Used as the filename.</small>
        </label>
        <button type="submit">Generate Profile</button>
    </form>

    <p><a href="/user/dashboard.php">← Back to profiles</a></p>
</main>
<?php html_foot(); ?>
