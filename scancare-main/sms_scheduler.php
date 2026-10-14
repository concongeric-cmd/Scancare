<?php





require_once __DIR__ . '/app_config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

date_default_timezone_set(getEnvironmentValue('APP_TIMEZONE', 'Asia/Manila'));


define('FMCSMS_API_KEY', getEnvironmentValue('FMCSMS_API_KEY'));
define('FMCSMS_API_URL', getEnvironmentValue('FMCSMS_API_URL'));
define('FMCSMS_SENDER_NAME', getEnvironmentValue('FMCSMS_SENDER_NAME', 'RHU II'));
define('FMCSMS_FROM_NUMBER', getEnvironmentValue('FMCSMS_FROM_NUMBER'));
define('NOTIFICATION_HOURS_BEFORE', 24); 
define('LOG_FILE', __DIR__ . '/logs/sms_scheduler.log');




define('DB_HOST', getEnvironmentValue('DB_HOST', 'localhost'));
define('DB_USER', getEnvironmentValue('DB_USER'));
define('DB_PASSWORD', getEnvironmentValue('DB_PASS'));
define('DB_NAME', getEnvironmentValue('DB_NAME', 'scancare_database'));
define('DB_CHARSET', getEnvironmentValue('DB_CHARSET', 'utf8mb4'));


function smsLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message<br>\n"; 
    file_put_contents(LOG_FILE, strip_tags($log_message), FILE_APPEND);
    echo $log_message; 
}


function getDBConnection() {
    try {
        if (DB_HOST === '' || DB_USER === '' || DB_NAME === '' || (isProductionEnvironment() && DB_PASSWORD === '')) {
            smsLog('Database configuration is incomplete.');
            return null;
        }

        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        smsLog("❌ DATABASE CONNECTION FAILED: " . $e->getMessage());
        return false;
    }
}


function sendSMSViaFMCSMS($phone_number, $message) {
    if (FMCSMS_API_KEY === '' || FMCSMS_API_URL === '') {
        smsLog('SMS sending skipped because FMCSMS is not configured.');
        return false;
    }

    $payload = [
        'SenderName' => FMCSMS_SENDER_NAME,
        'ToNumber' => $phone_number,
        'MessageBody' => $message,
        'FromNumber' => FMCSMS_FROM_NUMBER,
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => FMCSMS_API_URL,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . FMCSMS_API_KEY,
        ],
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    $result = [
        'success' => false,
        'http_code' => $http_code,
        'response' => $response,
        'error' => null
    ];

    if ($curl_error) {
        $result['error'] = "cURL Error: $curl_error";
        return $result;
    }

    if ($http_code === 200 || $http_code === 201) {
        $result['success'] = true;
        $result['response'] = json_decode($response, true);
    } else {
        $result['error'] = "API returned HTTP $http_code: $response";
    }

    return $result;
}


function runSMSScheduler() {
    smsLog("===== SMS SCHEDULER STARTED =====");

    $pdo = getDBConnection();
    if (!$pdo) {
        smsLog("❌ Cannot proceed without database connection");
        return;
    }

    
    $query = "
        SELECT 
            a.appointment_id,
            a.resident_id,
            a.appointment_date,
            a.appointment_time,
            a.purpose,
            r.full_name,
            r.contact_number,
            a.is_notified
        FROM appointment a
        INNER JOIN resident r ON a.resident_id = r.resident_id
        WHERE a.is_notified = 0
        AND a.status IN ('scheduled', 'pending', 'approved')
        AND DATE(a.appointment_date) = DATE(DATE_ADD(NOW(), INTERVAL " . NOTIFICATION_HOURS_BEFORE . " HOUR))
        AND a.appointment_date >= NOW()
        ORDER BY a.appointment_date ASC
    ";

    try {
        $stmt = $pdo->query($query);
        $appointments = $stmt->fetchAll();
    } catch (PDOException $e) {
        smsLog("❌ Query failed: " . $e->getMessage());
        return;
    }

    $total_appointments = count($appointments);
    smsLog("Found $total_appointments appointment(s) requiring SMS notification");

    if ($total_appointments === 0) {
        smsLog("✓ No appointments to notify at this time");
        smsLog("===== SMS SCHEDULER COMPLETED =====\n");
        return;
    }

    
    $sent_count = 0;
    $failed_count = 0;

    foreach ($appointments as $apt) {
        $apt_id = $apt['appointment_id'];
        $resident_name = $apt['full_name'];
        $phone_number = $apt['contact_number'];
        $apt_date = $apt['appointment_date'];
        $apt_time = $apt['appointment_time'];
        $purpose = $apt['purpose'];

        if (empty($phone_number)) {
            smsLog("⚠️ [APT #$apt_id] No phone number for resident '{$resident_name}' - SKIPPING");
            
            $updateStmt = $pdo->prepare("
                UPDATE appointment
                SET is_notified = 1, 
                    notification_error = ?,
                    notification_sent_at = NOW()
                WHERE appointment_id = ?
            ");
            $updateStmt->execute(['No phone number available', $apt_id]);
            $failed_count++;
            continue;
        }

        $apt_datetime = date('F j, Y \a\t g:i A', strtotime("$apt_date $apt_time"));
        $sms_message = "Hi $resident_name,\n\n"
            . "This is a reminder that you have an appointment at the Barangay Health Center.\n\n"
            . "📅 Date & Time: $apt_datetime\n"
            . "📋 Purpose: $purpose\n\n"
            . "Please arrive 10 minutes early.\n\n"
            . "RHU II - Barangay Health Center";

        smsLog("📱 [APT #$apt_id] Sending SMS to $resident_name ($phone_number)...");

        $api_response = sendSMSViaFMCSMS($phone_number, $sms_message);

        if ($api_response['success']) {
            smsLog("✅ [APT #$apt_id] SMS sent successfully to $phone_number");
            
            $updateStmt = $pdo->prepare("
                UPDATE appointment
                SET is_notified = 1, 
                    notification_sent_at = NOW(),
                    notification_error = NULL
                WHERE appointment_id = ?
            ");
            
            if ($updateStmt->execute([$apt_id])) {
                smsLog("✓ [APT #$apt_id] Database updated - marked as notified");
                $sent_count++;
            } else {
                smsLog("⚠️ [APT #$apt_id] Failed to update database");
                $failed_count++;
            }
        } else {
            smsLog("❌ [APT #$apt_id] SMS FAILED - " . ($api_response['error'] ?? print_r($api_response['response'], true)));
            
            $updateStmt = $pdo->prepare("
                UPDATE appointment
                SET notification_error = ?
                WHERE appointment_id = ?
            ");
            $updateStmt->execute([$api_response['error'] ?? 'Unknown error', $apt_id]);
            $failed_count++;
        }
    }

    smsLog("<br>📊 SUMMARY:");
    smsLog("   Total Appointments: $total_appointments");
    smsLog("   Successfully Sent: $sent_count");
    smsLog("   Failed: $failed_count");
    smsLog("===== SMS SCHEDULER COMPLETED =====\n");
}

runSMSScheduler();
?>
