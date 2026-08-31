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
        $message = t('profiles.revoked.ok', ['name' => $p['name']]);
        $msgType = 'success';
    }
}

$profiles = db()->prepare(
    'SELECT * FROM profiles WHERE user_id = ? ORDER BY created_at DESC'
);
$profiles->execute([$userId]);
$profiles = $profiles->fetchAll();

html_head(t('profiles.title'));
html_nav($user);
?>
<main>
    <h2><?= t('profiles.title') ?></h2>

    <?php if ($message): flash($message, $msgType); endif; ?>

    <div class="section">
        <a href="/user/profile.php" class="btn"><?= t('profiles.generate') ?></a>
    </div>

    <div class="section">
        <?php if (empty($profiles)): ?>
            <p class="muted"><?= t('profiles.empty') ?></p>
        <?php else: ?>
            <div class="table-wrap"><table>
                <thead>
                    <tr>
                        <th><?= t('profiles.col.name') ?></th>
                        <th><?= t('profiles.col.created') ?></th>
                        <th><?= t('profiles.col.status') ?></th>
                        <th><?= t('profiles.col.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profiles as $p): ?>
                    <tr class="<?= $p['revoked'] ? 'row-disabled' : '' ?>">
                        <td><?= h($p['name']) ?></td>
                        <td><?= h($p['created_at']) ?></td>
                        <td>
                            <?= $p['revoked']
                                ? '<span class="badge badge-off">' . t('profiles.status.revoked') . '</span>'
                                : '<span class="badge badge-ok">' . t('profiles.status.active') . '</span>' ?>
                        </td>
                        <td>
                            <?php if (!$p['revoked']): ?>
                            <a href="/user/profile.php?download=<?= $p['id'] ?>" class="btn btn-sm"><?= t('profiles.download') ?></a>
                            <form method="post" class="inline-form"
                                  onsubmit="return confirm('<?= h(t('profiles.revoke.confirm')) ?>')">
                                <input type="hidden" name="action" value="revoke">
                                <input type="hidden" name="profile_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-sm btn-danger"><?= t('profiles.revoke') ?></button>
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
