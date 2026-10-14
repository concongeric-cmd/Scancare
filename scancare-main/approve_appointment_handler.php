<?php


require_once 'db_config.php';


header('Content-Type: application/json');


$response = [
    'success' => false,
    'message' => 'Unknown error',
    'data' => null,
    'error_code' => null
];

try {
    
    
    
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    
    if ($appointment_id <= 0) {
        throw new Exception('Invalid appointment ID', 400);
    }
    
    
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment_plan = trim($_POST['treatment_plan'] ?? '');
    
    
    
    
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception('Database connection failed', 500);
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
                status
            FROM appointment
            WHERE appointment_id = ?
            LIMIT 1
            FOR UPDATE
        ");
        
        $fetch_appointment->execute([$appointment_id]);
        $appointment = $fetch_appointment->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            throw new Exception('Appointment not found', 404);
        }
        
        
        
        
        $eligible_statuses = ['scheduled', 'pending'];
        if (!in_array($appointment['status'], $eligible_statuses)) {
            throw new Exception('Appointment cannot be approved - current status: ' . $appointment['status'], 400);
        }
        
        
        
        
        
        function sendApprovalSMS($phone_number, $resident_name, $apt_date, $apt_time) {
    $fmcsms_api_key = getEnvironmentValue('FMCSMS_API_KEY');
    $fmcsms_url = getEnvironmentValue('FMCSMS_API_URL');
    $sender_name = getEnvironmentValue('FMCSMS_SENDER_NAME', 'RHU II');
    $from_number = getEnvironmentValue('FMCSMS_FROM_NUMBER');

    if ($fmcsms_api_key === '' || $fmcsms_url === '') {
        error_log('SMS approval skipped because FMCSMS is not configured.');
        return false;
    }
    
    
    $apt_datetime = date('F j, Y \a\t g:i A', strtotime("$apt_date $apt_time"));
    
    
    $sms_message = "Great news $resident_name! Your appointment at the Barangay Health Center has been approved.\n\n"
        . "📅 Appointment: $apt_datetime\n"
        . "📍 Location: Barangay Health Center\n\n"
        . "Please arrive 10 minutes early. Thank you!\n\n"
        . "RHU II - Barangay Health Center";
    
    
    $payload = [
        'SenderName' => $sender_name,
        'ToNumber' => $phone_number,
        'MessageBody' => $sms_message,
        'FromNumber' => $from_number,
    ];
    
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $fmcsms_url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . $fmcsms_api_key,
        ],
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);
    
    
    if (($http_code === 200 || $http_code === 201) && !$curl_error) {
        error_log("✅ SMS APPROVAL SENT: $resident_name ($phone_number)");
        return true;
    } else {
        error_log("❌ SMS APPROVAL FAILED: $resident_name ($phone_number) - Error: " . ($curl_error ?: $response));
        return false;
    }
}












$update_appointment = $pdo->prepare("
    UPDATE appointment
    SET status = 'approved'
    WHERE appointment_id = ?
");

if (!$update_appointment->execute([$appointment_id])) {
    throw new Exception('Failed to update appointment status', 500);
}



$get_resident_phone = $pdo->prepare("
    SELECT r.contact_number, r.full_name 
    FROM resident r 
    WHERE r.resident_id = ? 
    LIMIT 1
");
$get_resident_phone->execute([$appointment['resident_id']]);
$resident_data = $get_resident_phone->fetch(PDO::FETCH_ASSOC);


if ($resident_data && !empty($resident_data['contact_number'])) {
    sendApprovalSMS(
        $resident_data['contact_number'],
        $resident_data['full_name'],
        $appointment['appointment_date'],
        $appointment['appointment_time']
    );
}


        error_log("APPOINTMENT APPROVED: ID $appointment_id, Resident {$appointment['resident_id']}");
        
        
        
        
        
        
        
        $pdo->commit();
        
        
        
        
        $response = [
            'success' => true,
            'message' => 'Appointment approved successfully.',
            'data' => [
                'appointment_id' => $appointment_id,
                'resident_id' => $appointment['resident_id'],
                'visit_date' => $appointment['appointment_date'],
                'visit_type' => $appointment['purpose'],
                'previous_status' => $appointment['status'],
                'new_status' => 'approved'
            ]
        ];
        
    } catch (Exception $transaction_error) {
        
        $pdo->rollBack();
        throw $transaction_error;
    }
    
} catch (Exception $e) {
    
    
    
    $error_code = $e->getCode() ?? 500;
    
    $response = [
        'success' => false,
        'message' => 'Unable to approve the appointment.',
        'error_code' => $error_code,
        'data' => null
    ];
    
    error_log("APPOINTMENT APPROVAL ERROR: " . $e->getMessage() . " (Code: $error_code)");
}




echo json_encode($response);
exit;
?>

