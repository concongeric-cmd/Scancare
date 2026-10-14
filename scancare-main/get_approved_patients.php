<?php


header('Content-Type: application/json');

session_start();
require_once 'db_config.php';


if (!isset($_SESSION['worker_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    $query = "
        SELECT 
            a.appointment_id,
            a.resident_id,
            r.full_name as patient_name,
            r.date_of_birth,
            r.sex,
            r.contact_number,
            r.email,
            a.appointment_date,
            a.appointment_time,
            a.purpose
        FROM appointment a
        INNER JOIN resident r ON a.resident_id = r.resident_id
        WHERE a.status = 'approved'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'patients' => $patients
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

