<?php
require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$userType = strtolower((string) ($_SESSION['user_type'] ?? ''));
$redirect = $userType === 'admin' ? 'admin_login.php' : 'login.php';
$active = isCurrentSessionAccountActive();

if (!$active) {
    invalidateCurrentSession();
}

echo json_encode([
    'active' => $active,
    'redirect' => $redirect . '?account_removed=1',
]);
