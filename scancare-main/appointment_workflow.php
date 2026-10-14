<?php


require_once 'db_config.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception('Database connection failed');
    }
    
    
    
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'complete_appointment') {
            
            $appointment_id = intval($_POST['appointment_id'] ?? 0);
            $bhw_name = trim($_POST['bhw_name'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            
            if ($appointment_id <= 0) {
                throw new Exception('Invalid appointment ID');
            }
            
            
            $pdo->beginTransaction();
            
            try {
                
                
                
                $fetch_appointment = $pdo->prepare("
                    SELECT 
                        appointment_id,
                        resident_id,
                        appointment_date,
                        appointment_time,
                        purpose,
                        status,
                        created_at
                    FROM appointment
                    WHERE appointment_id = ?
                    LIMIT 1
                    FOR UPDATE
                ");
                $fetch_appointment->execute([$appointment_id]);
                $appointment = $fetch_appointment->fetch(PDO::FETCH_ASSOC);
                
                if (!$appointment) {
                    throw new Exception('Appointment not found');
                }
                
                if ($appointment['status'] === 'completed') {
                    throw new Exception('Appointment is already completed');
                }
                
                
                
                
                
                
                
                
                
                
                $combined_notes = "Visit Time: " . date('g:i A', strtotime($appointment['appointment_time']));
                if (!empty($notes)) {
                    $combined_notes .= "\nNotes: " . $notes;
                }

                $existingHistory = $pdo->prepare("SELECT history_id FROM medical_history WHERE user_id = ? AND DATE(visit_date) = ? AND TRIM(visit_type) = TRIM(?) ORDER BY history_id DESC LIMIT 1 FOR UPDATE");
                $existingHistory->execute([$appointment['resident_id'], $appointment['appointment_date'], $appointment['purpose']]);
                $history_id = $existingHistory->fetchColumn();

                if ($history_id) {
                    $updateHistory = $pdo->prepare("UPDATE medical_history SET status = 'completed', notes = CONCAT(COALESCE(notes, ''), CASE WHEN COALESCE(notes, '') = '' THEN '' ELSE CHAR(10) END, ?), updated_at = NOW() WHERE history_id = ?");
                    $updateHistory->execute([$combined_notes, $history_id]);
                } else {
                    $insert_history = $pdo->prepare("
                        INSERT INTO medical_history (
                            user_id,
                            visit_date,
                            visit_type,
                            bhw_name,
                            notes,
                            status,
                            created_at,
                            updated_at
                        ) VALUES (?, ?, ?, ?, ?, 'completed', NOW(), NOW())
                    ");
                    $insert_history->execute([
                        $appointment['resident_id'],
                        $appointment['appointment_date'],
                        $appointment['purpose'],
                        $bhw_name,
                        $combined_notes,
                    ]);
                    $history_id = $pdo->lastInsertId();
                }
                
                
                
                
                $update_appointment = $pdo->prepare("
                    UPDATE appointment
                    SET status = 'completed', updated_at = NOW()
                    WHERE appointment_id = ?
                ");
                $update_appointment->execute([$appointment_id]);
                
                
                $pdo->commit();
                
                
                
                
                $response['success'] = true;
                $response['message'] = 'Appointment marked as completed and transferred to medical history';
                $response['data'] = [
                    'appointment_id' => $appointment_id,
                    'history_id' => $history_id,
                    'resident_id' => $appointment['resident_id'],
                    'visit_date' => $appointment['appointment_date'],
                    'visit_type' => $appointment['purpose'],
                    'bhw_name' => $bhw_name
                ];
                
                
                error_log("✅ APPOINTMENT COMPLETED: Appointment #{$appointment_id} → History #{$history_id} for Resident #{$appointment['resident_id']}");
                
            } catch (Exception $e) {
                
                $pdo->rollBack();
                throw $e;
            }
        }
        
        
        
        
        elseif ($action === 'get_pending_appointments') {
            $user_id = intval($_POST['user_id'] ?? 0);
            
            $query = "
                SELECT 
                    appointment_id,
                    resident_id,
                    appointment_date,
                    appointment_time,
                    purpose,
                    status,
                    created_at
                FROM appointment
                WHERE status IN ('scheduled', 'pending')
            ";
            
            if ($user_id > 0) {
                $query .= " AND user_id = ?";
                $stmt = $pdo->prepare($query . " ORDER BY appointment_date ASC, appointment_time ASC");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $pdo->prepare($query . " ORDER BY appointment_date ASC, appointment_time ASC");
                $stmt->execute();
            }
            
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $appointments;
            $response['message'] = count($appointments) . ' pending appointments found';
        }
        
    } else {
        throw new Exception('Invalid request method or missing action');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Unable to process the appointment workflow.';
    error_log("❌ APPOINTMENT WORKFLOW ERROR: " . $e->getMessage());
}

echo json_encode($response);
?>

