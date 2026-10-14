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
$stmt = $pdo->prepare('DELETE FROM user_login_sessions WHERE user_id = ?');
$stmt->execute([$user_id]);
header('Content-Type: application/json');
echo json_encode(['deleted' => $stmt->rowCount()]);
