<?php
require_once 'db_config.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo 'NO DB';
    exit;
}
$userId = intval($_GET['user_id'] ?? 2);
$userType = $_GET['user_type'] ?? 'resident';
$sessionId = bin2hex(random_bytes(16));
$deviceId = 'Test Pending Device';
$deviceToken = bin2hex(random_bytes(16));
$ip = '127.0.0.1';
$tempToken = bin2hex(random_bytes(16));
$pdo->exec("INSERT INTO user_login_sessions (user_id, user_type, session_id, device_id, device_token, location, ip_address, temp_token, status, trusted, created_at, updated_at) VALUES ('" . addslashes($userId) . "', '" . addslashes($userType) . "', '" . addslashes($sessionId) . "', '" . addslashes($deviceId) . "', '" . addslashes($deviceToken) . "', NULL, '" . addslashes($ip) . "', '" . addslashes($tempToken) . "', 'pending', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

echo 'INSERTED: ' . $tempToken . ' for user ' . $userId . '\n';
