<?php
session_start();
require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');

if (($_SESSION['user_type'] ?? '') !== 'resident' || empty($_SESSION['resident_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(503);
    echo json_encode(['success' => false]);
    exit;
}

$residentId = (int) $_SESSION['resident_id'];
$versionParts = [];

try {
    if (hasTableColumn($pdo, 'patient_record', 'record_id')
        && hasTableColumn($pdo, 'patient_record', 'resident_id')) {
        $updatedColumn = hasTableColumn($pdo, 'patient_record', 'updated_at')
            ? 'updated_at'
            : (hasTableColumn($pdo, 'patient_record', 'created_at') ? 'created_at' : null);
        $updatedExpression = $updatedColumn ? "COALESCE(MAX($updatedColumn), '')" : "''";
        $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(MAX(record_id), 0), $updatedExpression FROM patient_record WHERE resident_id = ?");
        $stmt->execute([$residentId]);
        $versionParts['patient_record'] = $stmt->fetch(PDO::FETCH_NUM);
    }

    if (hasTableColumn($pdo, 'medical_history', 'history_id')
        && hasTableColumn($pdo, 'medical_history', 'user_id')) {
        $updatedColumn = hasTableColumn($pdo, 'medical_history', 'updated_at')
            ? 'updated_at'
            : (hasTableColumn($pdo, 'medical_history', 'created_at') ? 'created_at' : null);
        $updatedExpression = $updatedColumn ? "COALESCE(MAX($updatedColumn), '')" : "''";
        $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(MAX(history_id), 0), $updatedExpression FROM medical_history WHERE user_id = ?");
        $stmt->execute([$residentId]);
        $versionParts['medical_history'] = $stmt->fetch(PDO::FETCH_NUM);
    }

    if (hasTableColumn($pdo, 'appointment', 'appointment_id')
        && hasTableColumn($pdo, 'appointment', 'resident_id')
        && hasTableColumn($pdo, 'appointment', 'status')) {
        $updatedExpression = hasTableColumn($pdo, 'appointment', 'updated_at')
            ? "COALESCE(MAX(updated_at), '')"
            : "''";
        $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(MAX(appointment_id), 0), $updatedExpression FROM appointment WHERE resident_id = ? AND status IN ('completed', 'no_show')");
        $stmt->execute([$residentId]);
        $versionParts['appointment'] = $stmt->fetch(PDO::FETCH_NUM);
    }

    echo json_encode([
        'success' => true,
        'version' => hash('sha256', json_encode($versionParts))
    ]);
} catch (Throwable $e) {
    error_log('Resident history version check failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['success' => false]);
}