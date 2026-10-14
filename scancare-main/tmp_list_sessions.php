<?php
require_once 'db_config.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo 'NO DB';
    exit;
}
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if (!$user_id) {
    echo 'Missing user_id';
    exit;
}
$stmt = $pdo->prepare('SELECT id, user_id, user_type, status, session_id, device_token, temp_token, created_at, updated_at, ip_address, device_id FROM user_login_sessions WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($rows, JSON_PRETTY_PRINT);
