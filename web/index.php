<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// First-run check
if (!is_setup()) {
    header('Location: /setup/wizard.php');
    exit;
}

// Auth check — redirect to login or dashboard
$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}

if ($user['role'] === 'admin') {
    header('Location: /admin/dashboard.php');
} else {
    header('Location: /user/dashboard.php');
}
exit;
