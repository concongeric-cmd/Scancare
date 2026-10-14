<?php


header('Content-Type: application/json');

try {
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    
    $userId = null;
    $tableName = null;
    $userType = null;

    
    if (isset($_SESSION['worker_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'health_worker') {
        $userId = intval($_SESSION['worker_id']);
        $tableName = 'health_worker';
        $userType = 'health_worker';
    }
    
    elseif (isset($_SESSION['resident_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'resident') {
        $userId = intval($_SESSION['resident_id']);
        $tableName = 'resident';
        $userType = 'resident';
    }

    if (!$userId || !$tableName) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: User not authenticated'
        ]);
        exit;
    }
    
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $currentPassword = $input['currentPassword'] ?? '';
    $newPassword = $input['newPassword'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';

    error_log("Password Change: Passwords received - Current: " . (strlen($currentPassword) > 0 ? 'yes' : 'no') . ", New: " . (strlen($newPassword) > 0 ? 'yes' : 'no'));

    
    if (empty($currentPassword)) {
        error_log("Password Change Error: Current password is empty");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Current password is required'
        ]);
        exit;
    }

    if (empty($newPassword)) {
        error_log("Password Change Error: New password is empty");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'New password is required'
        ]);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        error_log("Password Change Error: New passwords do not match");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'New passwords do not match'
        ]);
        exit;
    }

    if (strlen($newPassword) < 6) {
        error_log("Password Change Error: New password too short");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'New password must be at least 6 characters long'
        ]);
        exit;
    }

    if ($currentPassword === $newPassword) {
        error_log("Password Change Error: New password same as old password");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'New password must be different from current password'
        ]);
        exit;
    }

    
    require_once 'db_config.php';
    $pdo = getDatabaseConnection();

    if (!$pdo) {
        error_log("Password Change Error: Database connection failed");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed'
        ]);
        exit;
    }

    
    $idColumn = ($tableName === 'health_worker') ? 'worker_id' : 'resident_id';
    error_log("Password Change: Fetching user from table '$tableName' with column '$idColumn' = $userId");
    
    $stmt = $pdo->prepare("SELECT password_hash FROM $tableName WHERE $idColumn = ?");
    
    if (!$stmt) {
        error_log("Password Change Error: SQL prepare failed - " . implode(", ", $pdo->errorInfo()));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query preparation failed'
        ]);
        exit;
    }
    
    $execute = $stmt->execute([$userId]);
    
    if (!$execute) {
        error_log("Password Change Error: SQL execute failed - " . implode(", ", $stmt->errorInfo()));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query execution failed'
        ]);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log("Password Change Error: User not found in $tableName with $idColumn = $userId");
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }

    error_log("Password Change: User found, verifying current password");

    
    if (!password_verify($currentPassword, $user['password_hash'])) {
        error_log("Password Change Error: Current password verification failed");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Current password is incorrect'
        ]);
        exit;
    }

    error_log("Password Change: Current password verified, hashing new password");

    
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    
    $updateStmt = $pdo->prepare("UPDATE $tableName SET password_hash = ? WHERE $idColumn = ?");
    
    if (!$updateStmt) {
        error_log("Password Change Error: Update SQL prepare failed - " . implode(", ", $pdo->errorInfo()));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database update preparation failed'
        ]);
        exit;
    }
    
    $updateResult = $updateStmt->execute([$newPasswordHash, $userId]);

    if ($updateResult) {
        error_log("Password Change: Success! Password updated for user $userId");
        
        
        logActivity($pdo, $userId, $userType, 'password_change', 'User changed their password');
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        error_log("Password Change Error: Update execution failed - " . implode(", ", $updateStmt->errorInfo()));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update password'
        ]);
    }

} catch (Exception $e) {
    error_log("Change Password Exception: " . $e->getMessage() . " | File: " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred'
    ]);
}
?>

