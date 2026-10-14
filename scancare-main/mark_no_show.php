<?php


header('Content-Type: application/json');

session_start();
require_once 'db_config.php';




if (!isset($_SESSION['worker_id']) || $_SESSION['user_type'] !== 'health_worker') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$worker_id = intval($_SESSION['worker_id']);
$worker_name = $_SESSION['user_name'] ?? 'BHW';




$appointment_id = intval($_POST['appointment_id'] ?? 0);

if ($appointment_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}




try {
    $pdo = getDatabaseConnection();
    
    
    $verify_stmt = $pdo->prepare("SELECT appointment_id, status FROM appointment WHERE appointment_id = ?");
    $verify_stmt->execute([$appointment_id]);
    $appointment = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    $old_status = $appointment['status'];
    
    
    $update_stmt = $pdo->prepare("
        UPDATE appointment 
        SET status = ?, health_worker_id = ? 
        WHERE appointment_id = ?
    ");
    
    $success = $update_stmt->execute(['no_show', $worker_id, $appointment_id]);
    
    if (!$success) {
        throw new Exception('Database update failed');
    }
    
    
    $details_stmt = $pdo->prepare("
        SELECT a.appointment_id, a.resident_id, r.full_name as patient_name
        FROM appointment a
        LEFT JOIN resident r ON a.resident_id = r.resident_id
        WHERE a.appointment_id = ?
    ");
    $details_stmt->execute([$appointment_id]);
    $details = $details_stmt->fetch(PDO::FETCH_ASSOC);
    
    
    error_log("[NO_SHOW] Appointment #$appointment_id marked as no_show by $worker_name (ID: $worker_id) - Patient: " . ($details['patient_name'] ?? 'Unknown'));
    
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Appointment marked as no show',
        'appointment_id' => $appointment_id,
        'old_status' => $old_status,
        'new_status' => 'no_show',
        'updated_by' => $worker_name,
        'patient_name' => $details['patient_name'] ?? 'Unknown Patient'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("NO_SHOW ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>

