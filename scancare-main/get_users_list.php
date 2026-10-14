<?php


require_once 'db_config.php';

header('Content-Type: application/json');

try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo json_encode(['error' => 'Database connection failed', 'users' => []]);
        exit;
    }
    
    
    $stmt = $pdo->query("
        SELECT 
            user_id,
            email,
            first_name,
            last_name,
            user_role,
            is_active,
            created_at,
            last_login
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    foreach ($users as &$user) {
        $user['created_at'] = date('M d, Y H:i', strtotime($user['created_at']));
        $user['last_login'] = $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never';
    }
    
    echo json_encode(['users' => $users]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'users' => []]);
}
?>

