<?php
session_start();
require_once 'db_config.php';

$isResident = isset($_SESSION['resident_id']) && ($_SESSION['user_type'] ?? '') === 'resident';
$isWorker = isset($_SESSION['worker_id']) && ($_SESSION['user_type'] ?? '') === 'health_worker';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (!$isResident && !$isWorker)) {
    header('Location: login.php');
    exit;
}

$requestId = (int) ($_POST['request_id'] ?? 0);
if ($requestId <= 0) {
    $redirect = $isWorker ? 'dashboard_bhw.php?module=file-requests' : 'dashboard.php#file-requests';
    header('Location: ' . $redirect . (strpos($redirect, '?') !== false ? '&' : '?') . 'request_status=error');
    exit;
}

$pdo = getDatabaseConnection();
if (!$pdo || !ensureFileRequestsTable($pdo)) {
    $redirect = $isWorker ? 'dashboard_bhw.php?module=file-requests' : 'dashboard.php#file-requests';
    header('Location: ' . $redirect . (strpos($redirect, '?') !== false ? '&' : '?') . 'request_status=error');
    exit;
}

if ($isResident) {
    $stmt = $pdo->prepare('DELETE FROM file_requests WHERE request_id = ? AND resident_id = ?');
    $stmt->execute([$requestId, (int) $_SESSION['resident_id']]);
    header('Location: dashboard.php?request_status=deleted#file-requests');
} else {
    $stmt = $pdo->prepare('DELETE FROM file_requests WHERE request_id = ?');
    $stmt->execute([$requestId]);
    header('Location: dashboard_bhw.php?module=file-requests&request_status=deleted');
}
exit;
