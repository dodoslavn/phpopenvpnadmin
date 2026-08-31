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
    $path     = strtok($_SERVER['REQUEST_URI'], '?');
    $langHtml = _lang_switcher_html();
    $logout   = t('nav.logout');

    $link = function(string $href, string $label) use ($path): string {
        $active = ($path === $href) ? ' class="active"' : '';
        return '<li><a href="' . $href . '"' . $active . '>' . $label . '</a></li>';
    };

    echo '<nav id="mainnav">';
    echo '<div class="nav-brand"><img src="/assets/logo.svg" alt="logo" height="28"> PHP OpenVPN Admin</div>';
    echo '<button class="nav-toggle" aria-label="Menu" onclick="var n=document.getElementById(\'mainnav\');n.classList.toggle(\'open\')">&#9776;</button>';
    echo '<ul class="nav-links">';
    echo $link('/user/dashboard.php', t('nav.profiles'));
    if ($isAdmin) {
        echo $link('/admin/dashboard.php', t('nav.dashboard'));
        echo $link('/admin/server.php',    t('nav.server'));
        echo $link('/admin/users.php',     t('nav.users'));
        echo $link('/admin/settings.php',  t('nav.settings'));
    }
    echo '</ul>';
    echo '<details class="user-menu nav-links">';
    echo   '<summary>' . $username . ' <span class="badge">' . $role . '</span></summary>';
    echo   '<div class="user-menu-panel">';
    if ($langHtml) echo '<div class="user-menu-lang">' . $langHtml . '</div>';
    echo     '<a href="/logout.php" class="user-menu-logout">' . $logout . '</a>';
    echo   '</div>';
    echo '</details>';
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
