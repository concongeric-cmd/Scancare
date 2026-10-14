<?php
session_start();
require_once 'db_config.php';

$patientCode = trim($_GET['code'] ?? '');
if ($patientCode === '') {
    http_response_code(400);
    exit('Invalid resident QR code.');
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    exit('Database connection unavailable.');
}

$stmt = $pdo->prepare('SELECT resident_id FROM resident WHERE patient_code = ? LIMIT 1');
$stmt->execute([$patientCode]);
$residentId = (int) ($stmt->fetchColumn() ?: 0);
if ($residentId <= 0) {
    http_response_code(404);
    exit('Resident not found.');
}

if (($_SESSION['user_type'] ?? '') === 'resident') {
    if ((int) ($_SESSION['resident_id'] ?? 0) !== $residentId) {
        http_response_code(403);
        exit('This QR code does not belong to your account.');
    }

    header('Location: view_record.php?code=' . urlencode($patientCode) . '&qr_scan=1');
    exit;
}

if (empty($_SESSION['user_type'])) {
    header('Location: confirm_file_request.php?code=' . urlencode($patientCode));
    exit;
}

if (($_SESSION['user_type'] ?? '') === 'health_worker' && !empty($_SESSION['worker_id'])) {
    $_SESSION['qr_access_resident_id'] = $residentId;
    $_SESSION['qr_access_timestamp'] = time();
    header('Location: dashboard_bhw.php?module=consultation&resident_id=' . $residentId);
    exit;
}

header('Location: login.php');
exit;
