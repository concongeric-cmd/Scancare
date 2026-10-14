<?php


header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/appointment-errors.log');

session_start();




if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['resident_id']) || $_SESSION['user_type'] !== 'resident') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated as resident']);
    exit;
}

require_once 'db_config.php';
require_once 'appointment_system.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    
    
    
    $resident_id = $_SESSION['resident_id'];
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    $purpose = trim($_POST['purpose'] ?? trim($_POST['reason'] ?? ''));
    
    
    if (empty($appointment_date) || empty($appointment_time) || empty($purpose)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }
    
    
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $appointment_time)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid time format']);
        exit;
    }
    
    
    $appointment_datetime = strtotime($appointment_date . ' ' . $appointment_time);
    if ($appointment_datetime === false || $appointment_datetime < time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Appointment date and time must be in the future']);
        exit;
    }
    
    
    $resident_check = $pdo->prepare("SELECT resident_id FROM resident WHERE resident_id = ?");
    $resident_check->execute([$resident_id]);
    
    if ($resident_check->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Resident not found']);
        exit;
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
        'purpose' => $purpose,
        'status' => 'scheduled'
    ];
    
    error_log("Appointment Created: Resident {$resident_id} - ID: {$appointment_id}");
    
    http_response_code(200);
    echo json_encode($response);
    exit;
    
} catch (Exception $e) {
    error_log('Appointment Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while scheduling your appointment'
    ]);
    exit;
}
?>














?>

