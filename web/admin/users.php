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
            $errors[] = 'Username must be 3–32 characters (letters, numbers, underscore).';
        if (strlen($password) < 8)
            $errors[] = 'Password must be at least 8 characters.';

        if (empty($errors)) {
            $exists = db()->prepare('SELECT id FROM users WHERE username = ?');
            $exists->execute([$username]);
            if ($exists->fetch()) {
                $errors[] = 'Username already exists.';
            } else {
                create_user($username, $password, $role);
                $message = "User '{$username}' created.";
                $msgType = 'success';
            }
        }

    } elseif ($action === 'toggle') {
        $uid     = (int) ($_POST['user_id'] ?? 0);
        $enabled = (int) ($_POST['enabled'] ?? 0);

        // Cannot disable own account
        if ($uid === (int) $admin['user_id']) {
            $errors[] = 'You cannot disable your own account.';
        } else {
            db()->prepare('UPDATE users SET enabled = ? WHERE id = ?')
               ->execute([$enabled ? 0 : 1, $uid]);
            $message = 'User updated.';
            $msgType = 'success';
        }

    } elseif ($action === 'delete') {
        $uid = (int) ($_POST['user_id'] ?? 0);
        if ($uid === (int) $admin['user_id']) {
            $errors[] = 'You cannot delete your own account.';
        } else {
            db()->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$uid]);
            db()->prepare('DELETE FROM profiles WHERE user_id = ?')->execute([$uid]);
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            $message = 'User deleted.';
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

html_head('Users');
html_nav($admin);
?>
<main>
    <h2>Users</h2>

    <?php if ($message): flash($message, $msgType); endif; ?>
    <?php foreach ($errors as $e): flash($e, 'error'); endforeach; ?>

    <div class="section">
        <h3>Create User</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="create">
            <label>Username
                <input type="text" name="username" pattern="[a-z0-9_]{3,32}" required
                       value="<?= h($_POST['username'] ?? '') ?>">
            </label>
            <label>Password
                <input type="password" name="password" minlength="8" required>
            </label>
            <label>Role
                <select name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <button type="submit">Create User</button>
        </form>
    </div>

    <div class="section">
        <h3>All Users</h3>
        <div class="table-wrap"><table>
            <thead>
                <tr><th>Username</th><th>Role</th><th>Profiles</th><th>Status</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="<?= $u['enabled'] ? '' : 'row-disabled' ?>">
                    <td><?= h($u['username']) ?></td>
                    <td><span class="badge"><?= h($u['role']) ?></span></td>
                    <td><?= h((string)$u['profile_count']) ?></td>
                    <td><?= $u['enabled'] ? '<span class="badge badge-ok">Active</span>' : '<span class="badge badge-off">Disabled</span>' ?></td>
                    <td><?= h($u['created_at']) ?></td>
                    <td>
                        <?php if ($u['id'] !== $admin['user_id']): ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="enabled" value="<?= $u['enabled'] ?>">
                            <button type="submit" class="btn-sm">
                                <?= $u['enabled'] ? 'Disable' : 'Enable' ?>
                            </button>
                        </form>
                        <form method="post" class="inline-form"
                              onsubmit="return confirm('Delete <?= h($u['username']) ?>? This cannot be undone.')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn-sm btn-danger">Delete</button>
                        </form>
                        <?php else: ?>
                        <span class="muted">(you)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
</main>
<?php html_foot(); ?>
