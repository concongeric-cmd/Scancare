<?php


header('Content-Type: application/json; charset=utf-8');

session_start();


if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}


$user_role = strtolower($_SESSION['user_role'] ?? $_SESSION['worker_role'] ?? '');
if (!in_array($user_role, ['bhw', 'health_worker', 'worker', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST method required']);
    exit;
}


$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : null;
$followup_date = isset($_POST['followup_date']) ? trim($_POST['followup_date']) : null;
$followup_time = isset($_POST['followup_time']) ? trim($_POST['followup_time']) : null;

if (!$appointment_id || !$followup_date || !$followup_time) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}


if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $followup_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}


if (!preg_match('/^\d{2}:\d{2}$/', $followup_time)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit;
}

require_once 'db_config.php';

try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    
    $stmt = $pdo->prepare("
        UPDATE appointment
        SET 
            appointment_date = ?,
            appointment_time = ?,
            status = 'follow_up'
        WHERE appointment_id = ?
    ");
    
    $success = $stmt->execute([
        $followup_date,
        $followup_time,
        $appointment_id
    ]);
    
    if ($success) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Follow-up schedule saved successfully',
            'appointment_id' => $appointment_id,
            'followup_date' => $followup_date,
            'followup_time' => $followup_time
        ]);
        
        error_log("Follow-up schedule updated for appointment $appointment_id: $followup_date $followup_time");
    } else {
        throw new Exception("Failed to update follow-up schedule");
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
    error_log("PDO Error in update_followup_schedule.php: " . $e->getMessage());
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred'
    ]);
    error_log("Error in update_followup_schedule.php: " . $e->getMessage());
}

?>

