<?php


header('Content-Type: application/json');

try {
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    
    $userId = null;
    $tableName = null;

    
    if (isset($_SESSION['worker_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'health_worker') {
        $userId = intval($_SESSION['worker_id']);
        $tableName = 'health_worker';
    }
    
    elseif (isset($_SESSION['resident_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'resident') {
        $userId = intval($_SESSION['resident_id']);
        $tableName = 'resident';
    }
    
    elseif (isset($_SESSION['user_id'])) {
        $userId = intval($_SESSION['user_id']);
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'bhw') {
            $tableName = 'health_worker';
        } else {
            $tableName = 'resident';
        }
    }

    if (!$userId || !$tableName) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'User not authenticated',
            'profile_picture' => null
        ]);
        exit;
    }

    
    require_once 'db_config.php';
    $pdo = getDatabaseConnection();

    
    $idColumn = ($tableName === 'health_worker') ? 'worker_id' : ($tableName === 'resident' ? 'resident_id' : 'user_id');

    
    $query = "SELECT profile_picture FROM {$tableName} WHERE {$idColumn} = ? LIMIT 1";
    $stmt = $pdo->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }

    if (!$stmt->execute([$userId])) {
        throw new Exception('Failed to execute query');
    }

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['profile_picture'])) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'profile_picture' => $result['profile_picture'] . '?t=' . time(),
            'has_picture' => true
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'profile_picture' => null,
            'has_picture' => false
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'profile_picture' => null
    ]);
}
?>

