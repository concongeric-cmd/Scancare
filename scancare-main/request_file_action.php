<?php
session_start();
require_once 'db_config.php';

$wantsJson = strpos(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json') !== false;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($wantsJson) {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'POST is required.']);
        exit;
    }
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
        if ($wantsJson) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Resident authentication is required.']);
            exit;
        }
        header('Location: login.php');
        exit;
    }
    $verify = $pdo->prepare('SELECT resident_id FROM resident WHERE resident_id = ? AND patient_code = ? LIMIT 1');
    $verify->execute([$residentId, $patientCode]);
    if (!$verify->fetchColumn()) {
        http_response_code(403);
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'This request does not match the resident account.']);
        } else {
            exit('Invalid resident QR request.');
        }
        exit;
    }
}

$requestedFile = trim($_POST['requested_file'] ?? 'Medical Record');
$allowedFiles = ['Medical Record'];
if (!in_array($requestedFile, $allowedFiles, true)) {
    $requestedFile = 'Medical Record';
}

$pdo = $pdo ?? getDatabaseConnection();
if (!$pdo) {
    if ($wantsJson) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Unable to connect to file requests.']);
        exit;
    }
    header('Location: file_request_success.php?status=error');
    exit;
}
ensureFileRequestsTable($pdo);

try {
    $check = $pdo->prepare("SELECT request_id FROM file_requests WHERE resident_id = ? AND status = 'pending' LIMIT 1");
    $check->execute([$residentId]);
    $requestId = (int) ($check->fetchColumn() ?: 0);
    $alreadyPending = $requestId > 0;
    if (!$alreadyPending) {
        $stmt = $pdo->prepare("INSERT INTO file_requests (resident_id, requested_file, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$residentId, $requestedFile]);
        $requestId = (int) $pdo->lastInsertId();
    }

    if ($wantsJson) {
        $requestStmt = $pdo->prepare('SELECT request_id, requested_file, status, response_note, created_at FROM file_requests WHERE request_id = ? AND resident_id = ? LIMIT 1');
        $requestStmt->execute([$requestId, $residentId]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'already_pending' => $alreadyPending,
            'request' => $requestStmt->fetch(PDO::FETCH_ASSOC) ?: null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Throwable $e) {
    error_log('RESIDENT FILE REQUEST ERROR: ' . $e->getMessage());
    if ($wantsJson) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Unable to submit your file request.']);
        exit;
    }
    header('Location: file_request_success.php?status=error');
    exit;
}

header('Location: file_request_success.php?status=submitted');
exit;
