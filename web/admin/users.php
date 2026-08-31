<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/template.php';

$admin   = require_admin();
$message = '';
$msgType = 'info';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] === 'admin' ? 'admin' : 'user';

        if (!preg_match('/^[a-z0-9_]{3,32}$/', $username))
            $errors[] = t('users.err.username');
        if (strlen($password) < 8)
            $errors[] = t('users.err.password');

        if (empty($errors)) {
            $exists = db()->prepare('SELECT id FROM users WHERE username = ?');
            $exists->execute([$username]);
            if ($exists->fetch()) {
                $errors[] = t('users.err.exists');
            } else {
                create_user($username, $password, $role);
                $message = t('users.created.ok', ['name' => $username]);
                $msgType = 'success';
            }
        }

    } elseif ($action === 'toggle') {
        $uid     = (int) ($_POST['user_id'] ?? 0);
        $enabled = (int) ($_POST['enabled'] ?? 0);

        if ($uid === (int) $admin['user_id']) {
            $errors[] = t('users.err.self_disable');
        } else {
            db()->prepare('UPDATE users SET enabled = ? WHERE id = ?')
               ->execute([$enabled ? 0 : 1, $uid]);
            $message = t('users.updated.ok');
            $msgType = 'success';
        }

    } elseif ($action === 'delete') {
        $uid = (int) ($_POST['user_id'] ?? 0);
        if ($uid === (int) $admin['user_id']) {
            $errors[] = t('users.err.self_delete');
        } else {
            db()->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$uid]);
            db()->prepare('DELETE FROM profiles WHERE user_id = ?')->execute([$uid]);
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            $message = t('users.deleted.ok');
            $msgType = 'success';
        }
    }
}

$users = db()->query(
    'SELECT u.id, u.username, u.role, u.enabled, u.created_at,
            COUNT(p.id) as profile_count
     FROM users u
     LEFT JOIN profiles p ON p.user_id = u.id AND p.revoked = 0
     GROUP BY u.id ORDER BY u.created_at DESC'
)->fetchAll();

html_head(t('users.title'));
html_nav($admin);
?>
<main>
    <h2><?= t('users.title') ?></h2>

    <?php if ($message): flash($message, $msgType); endif; ?>
    <?php foreach ($errors as $e): flash($e, 'error'); endforeach; ?>

    <div class="section">
        <h3><?= t('users.create') ?></h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="create">
            <label><?= t('users.col.username') ?>
                <input type="text" name="username" pattern="[a-z0-9_]{3,32}" required
                       value="<?= h($_POST['username'] ?? '') ?>">
            </label>
            <label><?= t('users.col.password') ?>
                <input type="password" name="password" minlength="8" required>
            </label>
            <label><?= t('users.col.role') ?>
                <select name="role">
                    <option value="user"><?= t('users.role.user') ?></option>
                    <option value="admin"><?= t('users.role.admin') ?></option>
                </select>
            </label>
            <button type="submit"><?= t('users.submit') ?></button>
        </form>
    </div>

    <div class="section">
        <h3><?= t('users.all') ?></h3>
        <div class="table-wrap"><table>
            <thead>
                <tr>
                    <th><?= t('users.col.username') ?></th>
                    <th><?= t('users.col.role') ?></th>
                    <th><?= t('users.col.profiles') ?></th>
                    <th><?= t('users.col.status') ?></th>
                    <th><?= t('users.col.created') ?></th>
                    <th><?= t('users.col.actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="<?= $u['enabled'] ? '' : 'row-disabled' ?>">
                    <td><?= h($u['username']) ?></td>
                    <td><span class="badge"><?= h($u['role']) ?></span></td>
                    <td><?= h((string)$u['profile_count']) ?></td>
                    <td><?= $u['enabled']
                        ? '<span class="badge badge-ok">' . t('users.status.active') . '</span>'
                        : '<span class="badge badge-off">' . t('users.status.disabled') . '</span>' ?></td>
                    <td><?= h($u['created_at']) ?></td>
                    <td>
                        <?php if ($u['id'] !== $admin['user_id']): ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="enabled" value="<?= $u['enabled'] ?>">
                            <button type="submit" class="btn-sm">
                                <?= $u['enabled'] ? t('users.disable') : t('users.enable') ?>
                            </button>
                        </form>
                        <form method="post" class="inline-form"
                              onsubmit="return confirm('<?= h(t('users.delete.confirm', ['name' => $u['username']])) ?>')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn-sm btn-danger"><?= t('users.delete') ?></button>
                        </form>
                        <?php else: ?>
                        <span class="muted"><?= t('users.you') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
</main>
<?php html_foot(); ?>
