<?php


session_start();




if (!isset($_SESSION['resident_id']) || $_SESSION['user_type'] !== 'resident') {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Not authenticated as resident']));
}

require_once 'db_config.php';
require_once 'appointment_system.php';


$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection error']));
}

try {
    
    
    
    $required_fields = ['appointment_date', 'appointment_time', 'purpose'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field] ?? '')) {
            throw new Exception('Missing required field: ' . $field);
        }
    }
    
    $resident_id = $_SESSION['resident_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $purpose = trim($_POST['purpose']);
    
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
        throw new Exception('Invalid date format. Please use YYYY-MM-DD');
    }
    
    
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $appointment_time)) {
        throw new Exception('Invalid time format. Please use HH:MM');
    }
    
    
    $appointment_datetime = strtotime($appointment_date . ' ' . $appointment_time);
    if ($appointment_datetime === false || $appointment_datetime < time()) {
        throw new Exception('Appointment date and time must be in the future');
    }
    
    
    if (strlen($purpose) < 3) {
        throw new Exception('Purpose must be at least 3 characters long');
    }
    
    
    
    
    $resident_check = $pdo->prepare("SELECT resident_id, full_name FROM resident WHERE resident_id = ?");
    $resident_check->execute([$resident_id]);
    
    if ($resident_check->rowCount() === 0) {
        throw new Exception('Resident not found');
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    $bookingResult = bookAppointment($resident_id, $appointment_date, $appointment_time, $purpose, $pdo);
    if (empty($bookingResult['success'])) {
        http_response_code(!empty($bookingResult['conflict']) ? 409 : 500);
        echo json_encode(['success' => false, 'message' => $bookingResult['message'] ?? 'Failed to schedule appointment']);
        exit;
    }
    $appointment_id = $bookingResult['appointment_id'];
    
    
    
    
    $response = [
        'success' => true,
        'message' => 'Appointment scheduled successfully!',
        'appointment_id' => $appointment_id,
        'resident_id' => $resident_id,
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time,
        'purpose' => $purpose
    ];
    
    error_log("Appointment Created: Resident {$resident_id} - Appointment ID: {$appointment_id}");
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
    
} catch (Exception $e) {
    error_log('Appointment Scheduling Error: ' . $e->getMessage());
    
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode($response);
    exit;
}
?>

