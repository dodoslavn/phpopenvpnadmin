<?php
function html_head(string $title): void {
    $appName = APP_NAME;
    $lang    = $GLOBALS['_LANG_CODE'];
    echo <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
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
    $langHtml = _lang_switcher_html();

    echo '<nav id="mainnav">';
    echo '<div class="nav-brand"><img src="/assets/logo.svg" alt="logo" height="28"> PHP OpenVPN Admin</div>';
    echo '<button class="nav-toggle" aria-label="Menu" onclick="document.getElementById(\'mainnav\').classList.toggle(\'open\')">&#9776;</button>';
    echo '<ul class="nav-links">';
    echo '<li><a href="/user/dashboard.php">' . t('nav.profiles') . '</a></li>';
    if ($isAdmin) {
        echo '<li><a href="/admin/dashboard.php">' . t('nav.dashboard') . '</a></li>';
        echo '<li><a href="/admin/server.php">' . t('nav.server') . '</a></li>';
        echo '<li><a href="/admin/users.php">' . t('nav.users') . '</a></li>';
        echo '<li><a href="/admin/settings.php">' . t('nav.settings') . '</a></li>';
    }
    echo '</ul>';
    echo '<div class="nav-user nav-links">';
    echo $langHtml;
    echo "<span>{$username}</span> <span class=\"badge\">{$role}</span> ";
    echo '<a href="/logout.php">' . t('nav.logout') . '</a>';
    echo '</div>';
    echo '</nav>';
}

function _lang_switcher_html(): string {
    $langs   = lang_list();
    $current = $GLOBALS['_LANG_CODE'];
    if (count($langs) <= 1) return '';

    $base = strtok($_SERVER['REQUEST_URI'], '?') ?: '/';
    $out  = '<select class="lang-select" onchange="location.href=this.value" aria-label="Language">';
    foreach ($langs as $code => $name) {
        $url      = htmlspecialchars($base . '?lang=' . urlencode($code));
        $selected = $code === $current ? ' selected' : '';
        $out .= '<option value="' . $url . '"' . $selected . '>' . htmlspecialchars($name) . '</option>';
    }
    $out .= '</select>';
    return $out;
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
