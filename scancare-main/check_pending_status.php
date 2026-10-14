<?php
// ============================================================================
// CHECK PENDING LOGIN STATUS
// Returns the current status of a pending login request
// ============================================================================

header('Content-Type: application/json');
require_once 'db_config.php';

$tempToken = $_GET['temp_token'] ?? '';

if (empty($tempToken)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing token']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    ensureUserLoginSessionsTable($pdo);
    
    // Find the pending login request
    $stmt = $pdo->prepare("
        SELECT status FROM user_login_sessions 
        WHERE temp_token = ? LIMIT 1
    ");
    $stmt->execute([$tempToken]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        echo json_encode(['status' => 'not_found']);
        exit;
    }
    
    $status = $result['status'];
    
    // Check if request has expired (older than 10 minutes)
    $stmt = $pdo->prepare("
        SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) as age_seconds
        FROM user_login_sessions 
        WHERE temp_token = ? LIMIT 1
    ");
    $stmt->execute([$tempToken]);
    $ageResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ageResult && $ageResult['age_seconds'] > 600) {
        // Expired - mark as rejected
        $updateStmt = $pdo->prepare("
            UPDATE user_login_sessions 
            SET status = 'rejected'
            WHERE temp_token = ?
        ");
        $updateStmt->execute([$tempToken]);
        echo json_encode(['status' => 'rejected', 'reason' => 'Request expired']);
        exit;
    }
    
    echo json_encode(['status' => $status]);
    exit;
    
} catch (Exception $e) {
    error_log('CHECK_PENDING_STATUS_ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
    exit;
}
?>
