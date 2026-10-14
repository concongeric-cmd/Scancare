<?php
session_start();
header('Content-Type: application/json');
require_once 'db_config.php';

$current = [
    'cookies' => $_SERVER['HTTP_COOKIE'] ?? null,
    'session_id' => session_id(),
    'session' => $_SESSION,
];

try {
    $pdo = getDatabaseConnection();
    $current['db_connected'] = $pdo ? true : false;
    if ($pdo) {
        ensureUserLoginSessionsTable($pdo);
        $userId = $_SESSION['resident_id'] ?? $_SESSION['worker_id'] ?? $_SESSION['admin_id'] ?? ($_SESSION['user_id'] ?? null);
        $userType = $_SESSION['user_type'] ?? ($_SESSION['admin_auth'] ? 'admin' : null) ?? ($_SESSION['user_role'] ?? null);
        $current['resolved_user'] = ['user_id' => $userId, 'user_type' => $userType];
        if ($userId && $userType) {
            $stmt = $pdo->prepare("SELECT id, temp_token, status, session_id, device_id, ip_address, created_at, updated_at FROM user_login_sessions WHERE user_id = ? AND user_type = ? AND status = 'pending' ORDER BY created_at DESC");
            $stmt->execute([$userId, $userType]);
            $current['pending_rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    $current['error'] = $e->getMessage();
}

echo json_encode($current, JSON_PRETTY_PRINT);
