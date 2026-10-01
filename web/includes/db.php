<?php
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');
        migrate_schema($pdo);
    }
    return $pdo;
}

/**
 * Adds columns introduced after schema.sql was last changed. schema.sql only
 * runs once, on first install (see install/steps/24-sqlite-init.sh), so an
 * already-installed server's database never sees later column additions
 * unless something applies them. Runs once per request (table_info is cheap)
 * and is a no-op once the column exists — including on fresh installs, where
 * schema.sql already has it.
 */
function migrate_schema(PDO $pdo): void {
    $cols = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('display_name', $cols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN display_name TEXT');
    }
}

function setting(string $key, string $default = ''): string {
    $stmt = db()->prepare('SELECT value FROM settings WHERE key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function setting_set(string $key, string $value): void {
    $stmt = db()->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    $stmt->execute([$key, $value]);
}

function is_setup(): bool {
    try {
        return setting('ca_cert') !== '';
    } catch (Exception $e) {
        return false;
    }
}
