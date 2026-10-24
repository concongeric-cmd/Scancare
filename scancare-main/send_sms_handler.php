<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

error_log("=== SMS HANDLER START ===");
error_log("Worker ID: " . ($_SESSION['worker_id'] ?? 'NOT SET'));
error_log("User Type: " . ($_SESSION['user_type'] ?? 'NOT SET'));

if (!isset($_SESSION['worker_id']) || ($_SESSION['user_type'] ?? '') !== 'health_worker') {
    error_log("Authorization failed!");
    echo json_encode(['success' => false, 'message' => 'Unauthorized - worker_id or user_type missing']);
    exit;
}

require_once __DIR__ . '/db_config.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    error_log("Database connection failed!");
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}
error_log("Database connected successfully");

// --- CONFIG ---
define('SEMAPHORE_API_KEY', getEnvironmentValue('SEMAPHORE_API_KEY'));
define('SEMAPHORE_SENDER',  getEnvironmentValue('SEMAPHORE_SENDER', 'SCANCARE'));  // max 11 chars, registered in Semaphore
define('SEMAPHORE_API_URL', 'https://api.semaphore.co/api/v4/messages');

function logSmsResult(PDO $pdo, int $workerId, string $recipient, string $message, string $status, int $responseCode, string $responseBody, string $mode): void {
    $columns = $pdo->query('SHOW COLUMNS FROM sms_log')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('recipient_number', $columns, true)) {
        $stmt = $pdo->prepare("INSERT INTO sms_log
            (recipient_number, message_body, event_type, triggered_by, status, response_code, response_body, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$recipient, $message, $mode, $workerId, $status, $responseCode, substr($responseBody, 0, 65535)]);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO sms_log
        (bhw_id, recipient, message, status, network, message_id, mode, sent_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$workerId, $recipient, $message, $status, '', null, $mode]);
}

function getSmsCaFile(): string {
    $configured = getEnvironmentValue('CURL_CA_BUNDLE', ini_get('curl.cainfo'));
    if ($configured !== '' && is_file($configured)) {
        return $configured;
    }

    foreach ([
        '/etc/ssl/certs/ca-certificates.crt',
        '/etc/pki/tls/certs/ca-bundle.crt',
        'C:/php/extras/ssl/cacert.pem'
    ] as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return '';
}

if (SEMAPHORE_API_KEY === '') {
    echo json_encode(['success' => false, 'message' => 'SMS service is not configured']);
    exit;
}

try {
    // --- INPUT ---
    $mode           = trim($_POST['mode'] ?? $_POST['recipient_type'] ?? '');   // 'all', 'appointment', 'custom'
    $message        = trim($_POST['message'] ?? '');
    $appointmentDate = trim($_POST['appointment_date'] ?? date('Y-m-d'));
    $customNumbers  = trim($_POST['custom_numbers'] ?? '');
    $bhw_id         = intval($_SESSION['worker_id']);

    error_log("Mode: $mode, Message length: " . strlen($message) . ", BHW ID: $bhw_id");

    if ($mode === '') {
        throw new Exception('Recipient type is required.');
    }

    if ($message === '') {
        throw new Exception('Message cannot be empty.');
    }

    // --- COLLECT RECIPIENTS ---
    $numbers = [];

    if ($mode === 'all') {
        error_log("Fetching all residents...");
        $stmt = $pdo->query("SELECT contact_number FROM resident WHERE contact_number IS NOT NULL AND TRIM(contact_number) != ''");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $numbers[] = $row['contact_number'];
        }
        error_log("Found " . count($numbers) . " residents with phone numbers");
    } elseif ($mode === 'appointment') {
        error_log("Fetching residents with upcoming appointments for $appointmentDate...");
        $stmt = $pdo->prepare("SELECT r.contact_number FROM appointment a JOIN resident r ON a.resident_id = r.resident_id WHERE DATE(a.appointment_date) = ? AND r.contact_number IS NOT NULL AND TRIM(r.contact_number) != ''");
        $stmt->execute([$appointmentDate]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $numbers[] = $row['contact_number'];
        }
        error_log("Found " . count($numbers) . " residents with appointments");
    } elseif ($mode === 'custom') {
        error_log("Using custom numbers...");
        $raw = explode(',', $customNumbers);
        foreach ($raw as $n) {
            $numbers[] = trim($n);
        }
        error_log("Parsed " . count($numbers) . " custom numbers");
    } else {
        throw new Exception('Unknown recipient type: ' . $mode);
    }

    function normalize_ph_number($number) {
        $n = preg_replace('/\D+/', '', $number);
        if ($n === '') {
            return null;
        }
        if (strlen($n) === 10) {
            return '63' . $n;
        }
        if (strlen($n) === 11 && substr($n, 0, 1) === '0') {
            return '63' . substr($n, 1);
        }
        if (substr($n, 0, 2) === '63') {
            return $n;
        }
        return null;
    }

    $numbers = array_filter(array_map('normalize_ph_number', $numbers));
    $numbers = array_unique(array_values($numbers));

    error_log("After normalization: " . count($numbers) . " valid numbers");

    if (empty($numbers)) {
        throw new Exception('No valid recipients found.');
    }

    // Smaller provider batches isolate a malformed recipient or temporary API limit.
    $batches   = array_chunk($numbers, 100);
    $sent      = 0;
    $failed    = 0;
    $responses = [];

    error_log("Starting SMS send in " . count($batches) . " batch(es)");

    foreach ($batches as $batchIdx => $batch) {
        error_log("Batch " . ($batchIdx + 1) . ": Sending to " . count($batch) . " recipients");
        
        $payload = http_build_query([
            'apikey'     => SEMAPHORE_API_KEY,
            'number'     => implode(',', $batch),
            'message'    => $message,
            'sendername' => SEMAPHORE_SENDER,
        ]);

        $ch = curl_init(SEMAPHORE_API_URL);
        $curlOptions = [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        $caFile = getSmsCaFile();
        if ($caFile !== '') {
            $curlOptions[CURLOPT_CAINFO] = $caFile;
        }
        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);

        error_log("Semaphore HTTP code: $httpCode");
        error_log("Semaphore raw response: " . $response); // Log full response
        
        if (!empty($curlErr)) {
            error_log("cURL Error: $curlErr");
            throw new Exception("cURL Error: $curlErr");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Semaphore rejected SMS batch with HTTP code: $httpCode");
            throw new Exception("SMS provider rejected the request (HTTP $httpCode).");
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            throw new Exception("Invalid JSON response from Semaphore API");
        }

        error_log("Parsed result type: " . gettype($result));
        if (is_array($result)) {
            error_log("Result is array with " . count($result) . " items");
        }

        // Handle different response formats from Semaphore API v4
        $messages_to_process = [];

        if (($httpCode === 200 || $httpCode === 201)) {
            // Format 1: Direct array of message objects (original format)
            if (is_array($result) && isset($result[0]) && is_array($result[0])) {
                error_log("Format 1: Direct array of messages");
                $messages_to_process = $result;
            } 
            // Format 2: Wrapper object with 'data' field containing messages
            else if (is_array($result) && isset($result['data']) && is_array($result['data'])) {
                error_log("Format 2: Wrapper object with 'data' field");
                $messages_to_process = $result['data'];
            }
            // Format 3: Wrapper object with 'messages' field
            else if (is_array($result) && isset($result['messages']) && is_array($result['messages'])) {
                error_log("Format 3: Wrapper object with 'messages' field");
                $messages_to_process = $result['messages'];
            }
            // Format 4: Single response object (treat as array with one element)
            else if (is_array($result) && (isset($result['status']) || isset($result['message_id']) || isset($result['recipient']))) {
                error_log("Format 4: Single message object");
                $messages_to_process = [$result];
            }
            // Format 5: Successful response but unexpected structure - treat as success
            else if (is_array($result)) {
                error_log("Format 5: Unknown array structure - counting as success since HTTP 200");
                $messages_to_process = [];
            }
        }

        foreach ($messages_to_process as $msg) {
            // Status can be: accepted, queued, sent, submitted, or similar
            $status    = strtolower($msg['status'] ?? $msg['result'] ?? 'accepted');
            $recipient = $msg['recipient'] ?? $msg['number'] ?? '';
            $msg_id    = $msg['message_id'] ?? $msg['id'] ?? null;
            $network   = $msg['network'] ?? '';

            error_log("Processing message - recipient: $recipient, status: $status");

            // Any response with these statuses = success
            if (in_array($status, ['queued', 'sent', 'success', 'accepted', 'submitted', 'processing', 'pending'], true)) {
                $sent++;
            } else {
                error_log("Unknown status '$status', counting as failed");
                $failed++;
            }

            // Log the SMS using the existing sms_log schema.
            try {
                logSmsResult($pdo, $bhw_id, $recipient, $message, $status, $httpCode, $response, $mode);
            } catch (PDOException $logErr) {
                error_log("Failed to log SMS: " . $logErr->getMessage());
            }
        }

        // If no messages were parsed but HTTP 200, treat as success
        if (count($messages_to_process) === 0 && ($httpCode === 200 || $httpCode === 201)) {
            error_log("No messages parsed but HTTP 200 returned - counting as success");
            $sent += count($batch);
        }
    }

    error_log("SMS send complete: Sent=$sent, Failed=$failed");

    echo json_encode([
        'success'       => $sent > 0,
        'sent'          => $sent,
        'failed'        => $failed,
        'total'         => count($numbers),
        'message'       => "SMS sent: {$sent} successful, {$failed} failed out of " . count($numbers) . " recipients.",
    ]);

} catch (Exception $e) {
    error_log("SMS Handler Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

exit;
?>
