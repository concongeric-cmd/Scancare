<?php


header('Content-Type: application/json');
http_response_code(200);




if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_id']) && !isset($_SESSION['worker_id'])) {
    http_response_code(401);
    die(json_encode([
        'success' => false,
        'error' => 'authentication_required',
        'message' => 'User must be authenticated to perform this action.'
    ]));
}


$logged_in_user_id = intval($_SESSION['worker_id'] ?? $_SESSION['user_id']);
$user_role = strtolower($_SESSION['user_role'] ?? $_SESSION['worker_role'] ?? $_SESSION['user_type'] ?? 'unknown');


$authorized_roles = ['bhw', 'health_worker', 'worker', 'admin'];
if (!in_array($user_role, $authorized_roles)) {
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'error' => 'authorization_failed',
        'message' => 'Only BHW/Health Workers can update appointment statuses.'
    ]));
}




try {
    require_once __DIR__ . '/db_config.php';
    
    if (!function_exists('getDatabaseConnection')) {
        throw new Exception('Database connection function not found.');
    }
    
    $pdo = getDatabaseConnection();
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('[ERROR] Database Connection: ' . $e->getMessage());
    die(json_encode([
        'success' => false,
        'error' => 'database_error',
        'message' => 'Database connection failed.'
    ]));
}






if (!isset($_POST['appointment_id']) || empty($_POST['appointment_id'])) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'missing_appointment_id',
        'message' => 'appointment_id parameter is required.'
    ]));
}

$appointment_id = intval($_POST['appointment_id']);
if ($appointment_id <= 0) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'invalid_appointment_id',
        'message' => 'appointment_id must be a positive integer.'
    ]));
}


if (!isset($_POST['status']) || empty(trim($_POST['status']))) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'missing_status',
        'message' => 'status parameter is required.'
    ]));
}

$new_status = trim($_POST['status']);


$allowed_statuses = ['pending', 'approved', 'no_show', 'completed', 'cancelled', '0'];
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'invalid_status',
        'message' => 'Invalid status value. Allowed: pending, approved, no_show, completed, cancelled, 0'
    ]));
}




try {
    $verify_stmt = $pdo->prepare("
        SELECT appointment_id, status, resident_id, health_worker_id 
        FROM appointment 
        WHERE appointment_id = ? 
        LIMIT 1
    ");
    
    $verify_stmt->execute([$appointment_id]);
    $current_appointment = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_appointment) {
        http_response_code(404);
        die(json_encode([
            'success' => false,
            'error' => 'appointment_not_found',
            'message' => 'Appointment #' . $appointment_id . ' not found in database.'
        ]));
    }
    
    $old_status = $current_appointment['status'];
    $resident_id = $current_appointment['resident_id'];
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log('[ERROR] Verify Appointment: ' . $e->getMessage());
    die(json_encode([
        'success' => false,
        'error' => 'database_query_error',
        'message' => 'Could not verify appointment existence.'
    ]));
}




try {
    
    $update_stmt = $pdo->prepare("
        UPDATE appointment 
        SET status = ?, health_worker_id = ? 
        WHERE appointment_id = ? 
        LIMIT 1
    ");
    
    $success = $update_stmt->execute([
        $new_status,           
        $logged_in_user_id,    
        $appointment_id        
    ]);
    
    if (!$success) {
        throw new Exception('Update query execution failed.');
    }
    
    $rows_affected = $update_stmt->rowCount();
    if ($rows_affected === 0) {
        throw new Exception('No rows were updated.');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log('[ERROR] Update Appointment Status: ' . $e->getMessage());
    die(json_encode([
        'success' => false,
        'error' => 'database_update_error',
        'message' => 'Failed to update appointment status in database.'
    ]));
}




$bhw_name = 'Unknown BHW';
$bhw_email = '';

try {
    $bhw_stmt = $pdo->prepare("
        SELECT full_name, email 
        FROM health_worker 
        WHERE worker_id = ? 
        LIMIT 1
    ");
    
    $bhw_stmt->execute([$logged_in_user_id]);
    $bhw_record = $bhw_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bhw_record && !empty($bhw_record['full_name'])) {
        $bhw_name = $bhw_record['full_name'];
        $bhw_email = $bhw_record['email'] ?? '';
    }
    
} catch (PDOException $e) {
    error_log('[WARNING] Could not retrieve BHW name: ' . $e->getMessage());
    
}




$resident_name = 'Unknown Patient';

try {
    $resident_stmt = $pdo->prepare("
        SELECT full_name 
        FROM resident 
        WHERE resident_id = ? 
        LIMIT 1
    ");
    
    $resident_stmt->execute([$resident_id]);
    $resident_record = $resident_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resident_record && !empty($resident_record['full_name'])) {
        $resident_name = $resident_record['full_name'];
    }
    
} catch (PDOException $e) {
    error_log('[WARNING] Could not retrieve resident name: ' . $e->getMessage());
    
}




$timestamp = date('Y-m-d H:i:s');
$log_message = sprintf(
    "[%s] APPOINTMENT STATUS UPDATE: ID=%d | Status: '%s' → '%s' | Patient: %s | Updated by: %s (ID: %d) | User Role: %s\n",
    $timestamp,
    $appointment_id,
    $old_status,
    $new_status,
    $resident_name,
    $bhw_name,
    $logged_in_user_id,
    $user_role
);

$log_file = __DIR__ . '/appointment_status_updates.log';
@file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);




echo json_encode([
    'success' => true,
    'message' => 'Appointment status updated successfully.',
    'appointment_id' => intval($appointment_id),
    'resident_id' => intval($resident_id),
    'resident_name' => $resident_name,
    'old_status' => $old_status,
    'new_status' => $new_status,
    'updated_by' => $bhw_name,
    'updated_by_id' => $logged_in_user_id,
    'updated_by_role' => $user_role,
    'updated_at' => $timestamp
]);

exit;
?>

