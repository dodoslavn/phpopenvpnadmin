<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// First-run check
if (!is_setup()) {
    header('Location: /setup');
    exit;
}

// Auth check — redirect to login or dashboard
$user = current_user();
if (!$user) {
    header('Location: /login');
    exit;
}

if ($user['role'] === 'admin') {
    header('Location: /dashboard');
} else {
    header('Location: /profiles');
}
exit;
