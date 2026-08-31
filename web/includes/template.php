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
    $account = t('nav.account');
    echo '<details class="user-menu nav-links">';
    echo   '<summary>' . $username . ' <span class="badge">' . $role . '</span></summary>';
    echo   '<div class="user-menu-panel">';
    echo     '<a href="/user/account.php" class="user-menu-link">' . $account . '</a>';
    echo     '<a href="/logout.php" class="user-menu-logout">' . $logout . '</a>';
    echo   '</div>';
    echo '</details>';
    echo '</nav>';
}

function html_foot(): void {
    $langs   = lang_list();
    $current = $GLOBALS['_LANG_CODE'];
    $base    = strtok($_SERVER['REQUEST_URI'], '?') ?: '/';
    $version = APP_VERSION;
    $appName = APP_NAME;

    echo '<footer>';
    echo '<div class="footer-inner">';
    echo '<span class="footer-brand">'
       . htmlspecialchars($appName)
       . ' - <span class="footer-version">v' . htmlspecialchars($version) . '</span>'
       . ' - <a href="https://github.com/dodoslavn/phpopenvpnadmin" class="footer-repo" target="_blank" rel="noopener">GitHub: dodoslavn/phpopenvpnadmin</a>'
       . '</span>';

    if (count($langs) > 1) {
        echo '<div class="footer-langs">';
        $items = [];
        foreach ($langs as $code => $name) {
            if ($code === $current) {
                $items[] = '<span class="footer-lang-active">' . htmlspecialchars($name) . '</span>';
            } else {
                $url = htmlspecialchars($base . '?lang=' . urlencode($code));
                $items[] = '<a href="' . $url . '">' . htmlspecialchars($name) . '</a>';
            }
        }
        echo implode(' · ', $items);
        echo '</div>';
    }

    echo '</div>';
    echo '</footer>';
    echo '</body></html>';
}

function flash(string $msg, string $type = 'info'): void {
    echo '<div class="flash flash-' . htmlspecialchars($type) . '">' . htmlspecialchars($msg) . '</div>';
}

function fmt_connected(int|null $ts, string $fallback): string {
    if ($ts !== null) {
        return gmdate('d.m.Y H:i:s', $ts) . ' UTC';
    }
    // Legacy format: parse as server local time, convert to UTC
    try {
        $dt = new DateTimeImmutable($fallback, new DateTimeZone(date_default_timezone_get()));
        return $dt->setTimezone(new DateTimeZone('UTC'))->format('d.m.Y H:i:s') . ' UTC';
    } catch (Exception $e) {
        return $fallback;
    }
}

function fmt_bytes(int|string $bytes): string {
    $b = (float) $bytes;
    foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $unit) {
        if ($b < 1024 || $unit === 'TB') {
            return round($b, $unit === 'B' ? 0 : 2) . ' ' . $unit;
        }
        $b /= 1024;
    }
    return $bytes . ' B';
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
