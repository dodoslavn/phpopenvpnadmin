<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function session_start_secure(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function current_user(): ?array {
    session_start_secure();

    $token = $_COOKIE[SESSION_COOKIE] ?? null;
    if (!$token) return null;

    $stmt = db()->prepare(
        'SELECT s.user_id, s.expires_at, u.username, u.role, u.enabled
         FROM sessions s JOIN users u ON u.id = s.user_id
         WHERE s.token = ?'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) return null;
    if (!$row['enabled']) return null;
    if (strtotime($row['expires_at'] . ' UTC') < time()) {
        logout();
        return null;
    }

    return $row;
}

function require_auth(): array {
    $user = current_user();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

function require_admin(): array {
    $user = require_auth();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        die('Access denied.');
    }
    return $user;
}

function login(string $username, string $password): bool {
    $stmt = db()->prepare('SELECT id, password_hash, role, enabled FROM users WHERE username = ?');
    $stmt->execute([strtolower(trim($username))]);
    $user = $stmt->fetch();

    if (!$user || !$user['enabled']) return false;
    if (!password_verify($password, $user['password_hash'])) return false;

    $token = bin2hex(random_bytes(32));
    $expires = gmdate('Y-m-d H:i:s', time() + SESSION_LIFETIME);

    db()->prepare('INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)')
       ->execute([$user['id'], $token, $expires]);

    setcookie(SESSION_COOKIE, $token, [
        'expires'  => time() + SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    return true;
}

function logout(): void {
    $token = $_COOKIE[SESSION_COOKIE] ?? null;
    if ($token) {
        db()->prepare('DELETE FROM sessions WHERE token = ?')->execute([$token]);
        setcookie(SESSION_COOKIE, '', time() - 3600, '/');
    }
}

function create_user(string $username, string $password, string $role = 'user'): int {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = db()->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
    $stmt->execute([strtolower(trim($username)), $hash, $role]);
    return (int) db()->lastInsertId();
}

function purge_expired_sessions(): void {
    db()->exec("DELETE FROM sessions WHERE expires_at < datetime('now')");
}
