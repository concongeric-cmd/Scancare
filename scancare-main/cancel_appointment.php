<?php


session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'db_config.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    die('Database connection failed');
}

try {
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($appointment_id === 0) {
        throw new Exception('Invalid appointment ID');
    }
    
    
    
    
    $verify_query = "SELECT appointment_id FROM appointments WHERE appointment_id = ? AND user_id = ?";
    $verify_stmt = $pdo->prepare($verify_query);
    $verify_stmt->bindParam(1, $appointment_id, PDO::PARAM_INT);
    $verify_stmt->bindParam(2, $user_id, PDO::PARAM_INT);
    $verify_stmt->execute();
    
    if ($verify_stmt->rowCount() === 0) {
        throw new Exception('Appointment not found or unauthorized');
    }
    
    
    
    
    $cancel_query = "UPDATE appointments SET status = 'canceled', updated_at = CURRENT_TIMESTAMP WHERE appointment_id = ?";
    $cancel_stmt = $pdo->prepare($cancel_query);
    $cancel_stmt->bindParam(1, $appointment_id, PDO::PARAM_INT);
    
    if (!$cancel_stmt->execute()) {
        throw new Exception('Failed to cancel appointment');
    }
    
    
    
    
    $action_description = "Canceled appointment (ID: $appointment_id)";
    
    $log_query = "INSERT INTO activity_logs 
                  (user_id, user_role_snapshot, action_type, action_description, ip_address, user_agent) 
                  VALUES (?, ?, 'canceled_appointment', ?, ?, ?)";
    
    $log_stmt = $pdo->prepare($log_query);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $log_stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $log_stmt->bindParam(2, $_SESSION['user_role'], PDO::PARAM_STR);
    $log_stmt->bindParam(3, $action_description, PDO::PARAM_STR);
    $log_stmt->bindParam(4, $ip_address, PDO::PARAM_STR);
    $log_stmt->bindParam(5, $user_agent, PDO::PARAM_STR);
    
    $log_stmt->execute();
    
    
    
    
    $_SESSION['success_message'] = 'Appointment canceled successfully';
    header('Location: view_appointments.php');
    exit;
    
} catch (Exception $e) {
    error_log('Appointment Cancellation Error: ' . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: view_appointments.php');
    exit;
}
?>

