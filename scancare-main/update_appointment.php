<?php


header('Content-Type: application/json');




if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'authentication_required',
        'message' => 'You must be logged in to perform this action.'
    ]);
    exit;
}


$user_role = strtolower($_SESSION['user_role'] ?? $_SESSION['worker_role'] ?? '');
$allowed_roles = ['bhw', 'health_worker', 'worker', 'admin'];

if (!in_array($user_role, $allowed_roles)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'authorization_failed',
        'message' => 'You do not have permission to update appointment statuses.'
    ]);
    exit;
}


$bhw_id = intval($_SESSION['user_id']);
if ($bhw_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_session',
        'message' => 'Invalid user session.'
    ]);
    exit;
}




try {
    
    $db_config_path = __DIR__ . '/db_config.php';
    if (!file_exists($db_config_path)) {
        throw new Exception('Database configuration file not found.');
    }
    
    require_once $db_config_path;
    
    
    if (!function_exists('getDatabaseConnection')) {
        throw new Exception('getDatabaseConnection function not found in db_config.php');
    }
    
    $pdo = getDatabaseConnection();
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Database connection error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'database_connection_failed',
        'message' => 'Could not connect to database.'
    ]);
    exit;
}





if (!isset($_POST['appointment_id']) || empty($_POST['appointment_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'missing_appointment_id',
        'message' => 'Appointment ID is required.'
    ]);
    exit;
}

$appointment_id = intval($_POST['appointment_id']);
if ($appointment_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_appointment_id',
        'message' => 'Appointment ID must be a positive integer.'
    ]);
    exit;
}


if (!isset($_POST['status']) || empty(trim($_POST['status']))) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'missing_status',
        'message' => 'Status is required.'
    ]);
    exit;
}

$new_status = trim($_POST['status']);


$allowed_statuses = ['pending', 'approved', 'no_show', 'completed', 'cancelled', '0'];
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_status',
        'message' => 'Invalid status value. Allowed: pending, approved, no_show, completed, cancelled.'
    ]);
    exit;
}




try {
    $check_stmt = $pdo->prepare("
        SELECT appointment_id, status, resident_id, appointment_date, appointment_time
        FROM appointment
        WHERE appointment_id = ?
        LIMIT 1
    ");
    
    $check_stmt->execute([$appointment_id]);
    $appointment = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'appointment_not_found',
            'message' => 'The specified appointment does not exist.'
        ]);
        exit;
    }
    
    $old_status = $appointment['status'];
    $resident_id = $appointment['resident_id'];
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database query error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'database_error',
        'message' => 'Could not verify appointment.'
    ]);
    exit;
}




try {
    
    $update_stmt = $pdo->prepare("
        UPDATE appointment
        SET status = ?, health_worker_id = ?
        WHERE appointment_id = ?
    ");
    
    
    $success = $update_stmt->execute([
        $new_status,
        $bhw_id,
        $appointment_id
    ]);
    
    if (!$success) {
        throw new Exception('Update failed.');
    }
    
    $affected_rows = $update_stmt->rowCount();
    if ($affected_rows === 0) {
        throw new Exception('No rows updated.');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'update_failed',
        'message' => 'Could not update appointment status.'
    ]);
    exit;
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
    
    $bhw_stmt->execute([$bhw_id]);
    $bhw_data = $bhw_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bhw_data) {
        $bhw_name = $bhw_data['full_name'] ?? 'Unknown BHW';
        $bhw_email = $bhw_data['email'] ?? '';
    }
    
} catch (PDOException $e) {
    error_log('Could not fetch BHW info: ' . $e->getMessage());
    
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
    $resident_data = $resident_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resident_data) {
        $resident_name = $resident_data['full_name'] ?? 'Unknown Patient';
    }
    
} catch (PDOException $e) {
    error_log('Could not fetch resident info: ' . $e->getMessage());
    
}




$log_message = sprintf(
    "[%s] Appointment #%d status changed: '%s' → '%s' | Patient: %s | Updated by: %s (ID: %d)\n",
    date('Y-m-d H:i:s'),
    $appointment_id,
    $old_status,
    $new_status,
    $resident_name,
    $bhw_name,
    $bhw_id
);

$log_file = __DIR__ . '/appointment_logs.log';
if (is_writable(dirname($log_file)) || !file_exists($log_file)) {
    @file_put_contents($log_file, $log_message, FILE_APPEND);
}




$update_timestamp = date('Y-m-d H:i:s');




http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Appointment status updated successfully.',
    'appointment_id' => intval($appointment_id),
    'resident_id' => intval($resident_id),
    'resident_name' => $resident_name,
    'old_status' => $old_status,
    'new_status' => $new_status,
    'updated_by' => $bhw_name . ' (ID: ' . $bhw_id . ')',
    'updated_by_id' => $bhw_id,
    'updated_by_name' => $bhw_name,
    'updated_at' => $update_timestamp
]);

exit;
?>

