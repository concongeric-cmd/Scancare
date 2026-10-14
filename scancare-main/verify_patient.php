<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);






session_start();
require_once 'db_config.php';




if (!isset($_GET['token']) || empty($_GET['token'])) {
    http_response_code(400);
    die('Invalid QR code. No token provided.');
}

$token = trim($_GET['token']);

try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        http_response_code(500);
        die('Database connection error.');
    }
    
    
    
    
    $query = "
        SELECT resident_id, full_name, email 
        FROM resident
        WHERE qr_code_token = ? 
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        throw new Exception("Query preparation failed");
    }
    
    $stmt->execute([$token]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resident) {
        http_response_code(404);
        die('Patient record not found. Invalid QR code.');
    }
    
    $resident_id = $resident['resident_id'];

    if (($_SESSION['user_type'] ?? '') === 'health_worker' && !empty($_SESSION['worker_id'])) {
        $_SESSION['qr_access_resident_id'] = $resident_id;
        $_SESSION['qr_access_timestamp'] = time();
        header('Location: dashboard_bhw.php?module=consultation&resident_id=' . (int) $resident_id);
        exit;
    }
    
    
    
    
    $today = date('Y-m-d');
    $appointment_query = "
        SELECT appointment_id, status, appointment_date, purpose 
        FROM appointment
        WHERE resident_id = ? 
        AND DATE(appointment_date) = DATE(?) 
        AND status IN ('scheduled', 'approved')
        ORDER BY appointment_date DESC
        LIMIT 1
    ";
    
    $appointment_stmt = $pdo->prepare($appointment_query);
    $appointment_stmt->execute([$resident_id, $today]);
    $appointment = $appointment_stmt->fetch(PDO::FETCH_ASSOC);
    
    
    
    
    
    if ($appointment) {
        
        
        
        $appointment_id = $appointment['appointment_id'];
        
        
        $update_query = "
            UPDATE appointment
            SET status = 'checked_in', check_in_time = NOW() 
            WHERE appointment_id = ?
        ";
        
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$appointment_id]);
        
        
        error_log("QR CHECK-IN: Resident {$resident_id} ({$resident['full_name']}) checked in for appointment {$appointment_id} at " . date('Y-m-d H:i:s'));
        
        $_SESSION['check_in_resident_id'] = $resident_id;
        $_SESSION['check_in_appointment_id'] = $appointment_id;
        $_SESSION['check_in_status'] = 'success';
        
        
        header('Location: patient_consultation.php?appointment_id=' . $appointment_id . '&checked_in=1');
        exit;
        
    } else {
        
        
        
        error_log("QR WALK-IN: Resident {$resident_id} ({$resident['full_name']}) walk-in scan at " . date('Y-m-d H:i:s'));
        
        $_SESSION['qr_access_resident_id'] = $resident_id;
        $_SESSION['qr_access_timestamp'] = time();
        $_SESSION['qr_full_name'] = $resident['full_name'];
        $_SESSION['qr_email'] = $resident['email'];
        
        error_log("QR SESSION SET: resident_id={$resident_id}, token set at " . date('Y-m-d H:i:s'));
        
        session_write_close();
        
        header('Location: view_record.php?id=' . $resident_id . '&qr_scan=1');
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("QR Verification Error: " . $e->getMessage());
    die('An error occurred while processing your request.');
}

?>

