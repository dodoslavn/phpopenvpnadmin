<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/template.php';

$user    = require_auth();
$userId  = (int) $user['user_id'];
$message = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'revoke') {
    $profileId = (int) ($_POST['profile_id'] ?? 0);
    $profile   = db()->prepare('SELECT * FROM profiles WHERE id = ? AND user_id = ?');
    $profile->execute([$profileId, $userId]);
    $p = $profile->fetch();

    if ($p && !$p['revoked']) {
        require_once __DIR__ . '/../includes/openvpn.php';
        revoke_client_cert($p['cert_serial']);
        db()->prepare('UPDATE profiles SET revoked = 1 WHERE id = ?')->execute([$profileId]);
        $message = "Profile '{$p['name']}' revoked.";
        $msgType = 'success';
    }
}

$profiles = db()->prepare(
    'SELECT * FROM profiles WHERE user_id = ? ORDER BY created_at DESC'
);
$profiles->execute([$userId]);
$profiles = $profiles->fetchAll();

html_head('My VPN Profiles');
html_nav($user);
?>
<main>
    <h2>My VPN Profiles</h2>

    <?php if ($message): flash($message, $msgType); endif; ?>

    <div class="section">
        <a href="/user/profile.php" class="btn">+ Generate New Profile</a>
    </div>

    <div class="section">
        <?php if (empty($profiles)): ?>
            <p class="muted">No profiles yet. Generate one above to connect to the VPN.</p>
        <?php else: ?>
            <div class="table-wrap"><table>
                <thead>
                    <tr><th>Name</th><th>Created</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($profiles as $p): ?>
                    <tr class="<?= $p['revoked'] ? 'row-disabled' : '' ?>">
                        <td><?= h($p['name']) ?></td>
                        <td><?= h($p['created_at']) ?></td>
                        <td>
                            <?= $p['revoked']
                                ? '<span class="badge badge-off">Revoked</span>'
                                : '<span class="badge badge-ok">Active</span>' ?>
                        </td>
                        <td>
                            <?php if (!$p['revoked']): ?>
                            <a href="/user/profile.php?download=<?= $p['id'] ?>" class="btn btn-sm">Download .ovpn</a>
                            <form method="post" class="inline-form"
                                  onsubmit="return confirm('Revoke this profile? It cannot be re-activated.')">
                                <input type="hidden" name="action" value="revoke">
                                <input type="hidden" name="profile_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-sm btn-danger">Revoke</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</main>
<?php html_foot(); ?>
