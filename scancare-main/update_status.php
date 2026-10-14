<?php






header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
http_response_code(200);


error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/appointment_logs.log');





session_start();


if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'authentication_required',
        'message' => 'You must be logged in to update appointments.'
    ]);
    exit;
}


$user_role = $_SESSION['user_role'] ?? $_SESSION['worker_role'] ?? null;
$valid_roles = ['bhw', 'health_worker', 'worker', 'admin'];

if (!$user_role || !in_array(strtolower($user_role), $valid_roles)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'permission_denied',
        'message' => 'Only BHW/Health Workers can update appointment statuses.'
    ]);
    exit;
}





if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_method',
        'message' => 'POST method required.'
    ]);
    exit;
}


$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : null;
$new_status = isset($_POST['status']) ? trim($_POST['status']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;


if (!$appointment_id || $appointment_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_appointment_id',
        'message' => 'Valid appointment ID is required.'
    ]);
    exit;
}

if (!$new_status) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_status',
        'message' => 'New status is required.'
    ]);
    exit;
}


$allowed_statuses = ['pending', 'approved', 'no_show', 'completed', 'cancelled', 'flagged', 'rendered', 'follow_up', 'scheduled', '0'];
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_status_value',
        'message' => 'Status must be one of: ' . implode(', ', $allowed_statuses)
    ]);
    exit;
}





require_once 'db_config.php';

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception("Database connection failed");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'database_error',
        'message' => 'Database connection failed. Please try again later.'
    ]);
    error_log("Database connection error in update_status.php: " . $e->getMessage());
    exit;
}





try {
    
    
    
    
    $select_stmt = $pdo->prepare("
        SELECT 
            appointment_id,
            resident_id,
            health_worker_id,
            status as current_status,
            appointment_date,
            appointment_time
        FROM appointment
        WHERE appointment_id = ?
    ");
    
    $select_stmt->execute([$appointment_id]);
    $appointment = $select_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'appointment_not_found',
            'message' => 'Appointment not found.',
            'appointment_id' => $appointment_id
        ]);
        error_log("Appointment not found: ID $appointment_id");
        exit;
    }
    
    
    $old_status = $appointment['current_status'];
    
    
    
    
    $bhw_id = $_SESSION['user_id'];
    $update_timestamp = date('Y-m-d H:i:s');
    
    $update_stmt = $pdo->prepare("
        UPDATE appointment
        SET 
            status = ?,
            health_worker_id = ?
        WHERE appointment_id = ?
    ");
    
    $success = $update_stmt->execute([
        $new_status,      
        $bhw_id,          
        $appointment_id   
    ]);
    
    if (!$success) {
        throw new Exception("Failed to update appointment status");
    }
    
    
    
    
    $bhw_name = "Unknown";
    $bhw_email = "";
    
    
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
            $bhw_name = $bhw_data['full_name'] ?? "Unknown";
            $bhw_email = $bhw_data['email'] ?? "";
        }
    } catch (Exception $e) {
        
        error_log("Could not fetch health worker details: " . $e->getMessage());
    }
    
    
    
    
    $activity_log = "UPDATE_APPOINTMENT_STATUS | " .
                    "Appointment: $appointment_id | " .
                    "Status: $old_status -> $new_status | " .
                    "BHW: $bhw_name (ID: $bhw_id) | " .
                    "Resident: {$appointment['resident_id']} | " .
                    "Date: $update_timestamp";
    
    error_log($activity_log);
    
    
    
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Appointment status updated successfully.',
        'appointment_id' => intval($appointment_id),
        'old_status' => $old_status,
        'new_status' => $new_status,
        'updated_by' => $bhw_name . " (ID: $bhw_id)",
        'updated_at' => $update_timestamp,
        'resident_id' => intval($appointment['resident_id'])
    ]);
    
    
    error_log("Successfully updated appointment $appointment_id status: $old_status → $new_status by BHW: $bhw_name");
    
} catch (PDOException $e) {
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'database_error',
        'message' => 'Database error occurred. Please try again or contact support.'
    ]);
    error_log("PDO Exception in update_status.php: " . $e->getMessage());
    
} catch (Exception $e) {
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'general_error',
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
    error_log("Exception in update_status.php: " . $e->getMessage());
}

?>

