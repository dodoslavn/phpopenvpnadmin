<?php
// Handle ?lang=XX switch — sets cookie and redirects back
if (isset($_GET['lang'])) {
    $requested = preg_replace('/[^a-z]/', '', strtolower($_GET['lang']));
    $file = __DIR__ . '/../lang/' . $requested . '.php';
    if (is_file($file)) {
        setcookie('lang', $requested, time() + 60 * 60 * 24 * 365, '/');
        $redirect = strtok($_SERVER['REQUEST_URI'], '?') ?: '/';
        header('Location: ' . $redirect);
        exit;
    }
}

// Detect language: cookie → Accept-Language → 'en'
function _detect_lang(): string {
    if (isset($_COOKIE['lang'])) {
        $c = preg_replace('/[^a-z]/', '', strtolower($_COOKIE['lang']));
        if (is_file(__DIR__ . '/../lang/' . $c . '.php')) return $c;
    }
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $part) {
            $code = strtolower(substr(trim(explode(';', $part)[0]), 0, 2));
            $code = preg_replace('/[^a-z]/', '', $code);
            if ($code !== '' && is_file(__DIR__ . '/../lang/' . $code . '.php')) return $code;
        }
    }
    return 'en';
}

$GLOBALS['_LANG_CODE'] = _detect_lang();

// Load chosen language, merge over English base so untranslated keys fall back
$_LANG_BASE = require __DIR__ . '/../lang/en.php';
if ($GLOBALS['_LANG_CODE'] !== 'en') {
    $override = require __DIR__ . '/../lang/' . $GLOBALS['_LANG_CODE'] . '.php';
    $GLOBALS['_LANG'] = array_merge($_LANG_BASE, $override);
} else {
    $GLOBALS['_LANG'] = $_LANG_BASE;
}

// Translate a key with optional {var} substitution
function t(string $key, array $vars = []): string {
    $str = $GLOBALS['_LANG'][$key] ?? $key;
    foreach ($vars as $k => $v) {
        $str = str_replace('{' . $k . '}', $v, $str);
    }
    return $str;
}

// Return list of available languages: ['en' => 'English', 'de' => 'Deutsch', ...]
function lang_list(): array {
    $langs = [];
    foreach (glob(__DIR__ . '/../lang/*.php') as $file) {
        $code = basename($file, '.php');
        // Each lang file may optionally define 'lang.name' for its own name
        $strings = require $file;
        $langs[$code] = $strings['lang.name'] ?? strtoupper($code);
    }
    ksort($langs);
    return $langs;
}
