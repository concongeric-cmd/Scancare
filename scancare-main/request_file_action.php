<?php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$residentId = (int) ($_SESSION['resident_id'] ?? 0);
$pdo = null;
if ($residentId <= 0 || ($_SESSION['user_type'] ?? '') !== 'resident') {
    $residentId = (int) ($_POST['resident_id'] ?? 0);
    $patientCode = trim($_POST['patient_code'] ?? '');
    $pdo = getDatabaseConnection();
    if (!$pdo || $residentId <= 0 || $patientCode === '') {
        header('Location: login.php');
        exit;
    }
    $verify = $pdo->prepare('SELECT resident_id FROM resident WHERE resident_id = ? AND patient_code = ? LIMIT 1');
    $verify->execute([$residentId, $patientCode]);
    if (!$verify->fetchColumn()) {
        http_response_code(403);
        exit('Invalid resident QR request.');
    }
}

$requestedFile = trim($_POST['requested_file'] ?? 'Medical Record');
$allowedFiles = ['Medical Record'];
if (!in_array($requestedFile, $allowedFiles, true)) {
    $requestedFile = 'Medical Record';
}

$pdo = $pdo ?? getDatabaseConnection();
if (!$pdo) {
    header('Location: file_request_success.php?status=error');
    exit;
}
ensureFileRequestsTable($pdo);

$check = $pdo->prepare("SELECT request_id FROM file_requests WHERE resident_id = ? AND status = 'pending' LIMIT 1");
$check->execute([$residentId]);
if (!$check->fetchColumn()) {
    $stmt = $pdo->prepare("INSERT INTO file_requests (resident_id, requested_file, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$residentId, $requestedFile]);
}

header('Location: file_request_success.php?status=submitted');
exit;
