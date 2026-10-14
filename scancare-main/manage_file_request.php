<?php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['worker_id']) || ($_SESSION['user_type'] ?? '') !== 'health_worker') {
    header('Location: login.php');
    exit;
}

$requestId = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['request_action'] ?? '';
if ($requestId <= 0 || !in_array($action, ['accept', 'decline'], true)) {
    header('Location: dashboard_bhw.php?module=file-requests&request_status=error');
    exit;
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    header('Location: dashboard_bhw.php?module=file-requests&request_status=error');
    exit;
}
ensureFileRequestsTable($pdo);
$status = $action === 'accept' ? 'accepted' : 'declined';
$note = trim($_POST['response_note'] ?? '');
$stmt = $pdo->prepare("UPDATE file_requests SET status = ?, health_worker_id = ?, response_note = ?, processed_at = NOW() WHERE request_id = ? AND status = 'pending'");
$stmt->execute([$status, (int) $_SESSION['worker_id'], $note ?: null, $requestId]);

header('Location: dashboard_bhw.php?module=file-requests&request_status=' . $status);
exit;
