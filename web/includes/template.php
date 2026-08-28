<?php
function html_head(string $title): void {
    $appName = APP_NAME;
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} — {$appName}</title>
    <link rel="icon" href="/assets/logo.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
HTML;
}

function html_nav(array $user): void {
    $username = htmlspecialchars($user['username']);
    $role     = $user['role'];
    $isAdmin  = $role === 'admin';

    echo <<<HTML
<nav>
    <div class="nav-brand">
        <img src="/assets/logo.svg" alt="logo" height="28"> phpopenvpnadmin
    </div>
    <ul>
        <li><a href="/user/dashboard.php">My Profiles</a></li>
HTML;
    if ($isAdmin) {
        echo '<li><a href="/admin/dashboard.php">Server Status</a></li>';
        echo '<li><a href="/admin/users.php">Users</a></li>';
        echo '<li><a href="/admin/settings.php">Settings</a></li>';
    }
    echo <<<HTML
    </ul>
    <div class="nav-user">
        <span>{$username}</span> <span class="badge">{$role}</span>
        <a href="/logout.php">Logout</a>
    </div>
</nav>
HTML;
}

function html_foot(): void {
    echo '</body></html>';
}

function flash(string $msg, string $type = 'info'): void {
    echo '<div class="flash flash-' . htmlspecialchars($type) . '">' . htmlspecialchars($msg) . '</div>';
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
