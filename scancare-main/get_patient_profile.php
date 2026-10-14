<?php


header('Content-Type: application/json; charset=utf-8');
session_start();






$user_id = $_SESSION['worker_id'] ?? $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}


$valid_roles = ['bhw', 'worker', 'health_worker', 'admin'];
$user_role = $_SESSION['worker_role'] ?? $_SESSION['user_role'] ?? null;
if (!$user_role || !in_array(strtolower($user_role), $valid_roles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}





if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$patient_id = intval($_POST['patient_id'] ?? 0);
if ($patient_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
    exit;
}





require_once 'db_config.php';
$pdo = getDatabaseConnection();

if ($pdo === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

try {
    
    
    
    
    $patient_query = "
        SELECT 
            resident_id,
            full_name,
            email,
            contact_number,
            date_of_birth,
            sex,
            address,
            barangay,
            city,
            province,
            postal_code
        FROM resident
        WHERE user_id = ?
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($patient_query);
    if (!$stmt) {
        throw new Exception("Patient query prep failed");
    }
    
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit;
    }
    
    
    
    
    
    $history_query = "
        SELECT 
            history_id,
            resident_id,
            diagnosis,
            treatment,
            notes,
            created_at
        FROM medical_history
        WHERE resident_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($history_query);
    $stmt->execute([$patient_id]);
    $medical_history = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    
    
    
    
    $appointment_query = "
        SELECT 
            appointment_id,
            resident_id,
            appointment_date,
            appointment_time,
            purpose,
            status,
            created_at
        FROM appointment
        WHERE resident_id = ?
        ORDER BY appointment_date DESC, appointment_time DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($appointment_query);
    $stmt->execute([$patient_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    
    
    
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'patient' => [
            'id' => $patient['resident_id'],
            'name' => htmlspecialchars($patient['full_name'] ?? ''),
            'email' => htmlspecialchars($patient['email'] ?? 'N/A'),
            'phone' => htmlspecialchars($patient['contact_number'] ?? 'N/A'),
            'dob' => $patient['date_of_birth'] ?? 'N/A',
            'sex' => htmlspecialchars($patient['sex'] ?? 'N/A'),
            'address' => htmlspecialchars($patient['address'] ?? 'N/A'),
            'barangay' => htmlspecialchars($patient['barangay'] ?? ''),
            'city' => htmlspecialchars($patient['city'] ?? ''),
            'province' => htmlspecialchars($patient['province'] ?? ''),
            'postal_code' => htmlspecialchars($patient['postal_code'] ?? '')
        ],
        'medical_history' => $medical_history,
        'appointments' => $appointments,
        'message' => 'Patient profile loaded successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_patient_profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
?>

