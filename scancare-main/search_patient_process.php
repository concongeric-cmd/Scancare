<?php

session_start();
require_once 'db_config.php';




header('Content-Type: application/json; charset=utf-8');
http_response_code(200); 


error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/search_errors.log');

error_log('PATIENT_SEARCH_SESSION_DUMP: ' . print_r($_SESSION, true));







$worker_id = $_SESSION['worker_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$admin_id = $_SESSION['admin_id'] ?? null;
$user_type = strtolower((string)($_SESSION['user_type'] ?? ''));
$raw_role = $_SESSION['worker_role'] ?? $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
$user_role = strtolower((string)($raw_role ?? ''));
$user_id = $worker_id ?? $admin_id ?? null;
$is_health_worker = $worker_id !== null && ($user_type === 'health_worker' || in_array($user_role, ['bhw', 'worker', 'health_worker'], true));
$is_admin = $admin_id !== null && ($user_type === 'admin' || $user_role === 'admin');

if (!$user_id && !empty($_POST['search_token'])) {
    $token_parts = explode('|', base64_decode((string)$_POST['search_token'], true) ?: '', 3);
    if (count($token_parts) === 3 && ctype_digit($token_parts[0]) && ctype_digit($token_parts[1])) {
        $token_user_id = (int)$token_parts[0];
        $token_expires = (int)$token_parts[1];
        $token_payload = $token_user_id . '|' . $token_expires;
        $token_signature = hash_hmac('sha256', $token_payload, DB_NAME . '|' . DB_USER . '|' . DB_PASS);
        if ($token_expires >= time() && hash_equals($token_signature, $token_parts[2])) {
            $worker_id = $token_user_id;
            $user_id = $worker_id;
            $user_type = 'health_worker';
            $user_role = 'bhw';
            $is_health_worker = true;
            error_log('PATIENT_SEARCH_SIGNED_SESSION_FALLBACK: authenticated worker ' . $worker_id);
        }
    }
}

error_log('PATIENT_SEARCH_SESSION_DEBUG: user_id=' . var_export($user_id, true) . ' user_type=' . var_export($user_type, true) . ' user_role=' . var_export($user_role, true) . ' session_keys=' . json_encode(array_keys($_SESSION)));

if (!$user_id) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error_type' => 'authentication',
        'message' => 'Session expired. Please login again.'
    ]);
    exit;
}


if (!$is_health_worker && !$is_admin) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error_type' => 'authorization',
        'message' => 'You do not have permission to search patients. BHW access required.'
    ]);
    exit;
}





$pdo = getDatabaseConnection();

if ($pdo === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_type' => 'database',
        'message' => 'Database connection unavailable. Please try again later.'
    ]);
    error_log("Database connection failed in search_patient_process.php");
    exit;
}





    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'], true)) {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error_type' => 'method',
            'message' => 'Invalid request method. GET or POST required.'
    ]);
    exit;
}





try {
    
    
    
    
    $search_term = trim($_POST['search_term'] ?? $_POST['search'] ?? $_GET['search_term'] ?? $_GET['search'] ?? '');
    
    if (empty($search_term)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_type' => 'validation',
            'message' => 'Please enter a search term (name, email, phone, or ID).'
        ]);
        exit;
    }
    
    
    $search_term = substr(trim($search_term), 0, 100);
    
    if (strlen($search_term) < 1) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_type' => 'validation',
            'message' => 'Search term must not be empty.'
        ]);
        exit;
    }
    
    error_log("Search initiated for term: '$search_term' by user: {$user_id}");
    
    
    
    
    
    $include_inactive = isset($_POST['include_inactive']) && in_array(strtolower(trim($_POST['include_inactive'])), ['1', 'true', 'yes'], true);
    
    
    $table_name = 'users';
    $id_column = 'user_id';
    $name_column = 'full_name';
    $email_column = 'email';
    $phone_column = 'phone_number';
    
    
    try {
        $check_table = $pdo->query("SELECT contact_number FROM resident LIMIT 1");
        
        $table_name = 'resident';
        $id_column = 'resident_id';
        $name_column = 'full_name';
        $email_column = 'email';
        $phone_column = 'contact_number';
    } catch (Exception $e) {
        
        try {
            $check_table = $pdo->query("SELECT phone_number FROM users LIMIT 1");
            
            $table_name = 'users';
            $id_column = 'user_id';
            $name_column = 'full_name';
            $email_column = 'email';
            $phone_column = 'phone_number';
        } catch (Exception $e2) {
            
            $table_name = 'resident';
            $id_column = 'resident_id';
            $name_column = 'full_name';
            $email_column = 'email';
            $phone_column = 'contact_number';
        }
    }
    
    
    
    
    
    
    $where_conditions = [];
    $search_params = [];

    $search_like = '%' . $search_term . '%';
    $search_like_lower = '%' . strtolower($search_term) . '%';

    $where_conditions[] = "LOWER({$name_column}) LIKE LOWER(?)";
    $search_params[] = $search_like_lower;

    $where_conditions[] = "LOWER({$email_column}) LIKE LOWER(?)";
    $search_params[] = $search_like_lower;

    if ($phone_column !== null) {
        $where_conditions[] = "REPLACE(REPLACE(REPLACE(LOWER({$phone_column}), ' ', ''), '-', ''), '+', '') LIKE LOWER(?)";
        $search_params[] = str_replace([' ', '-', '+'], '', strtolower($search_like_lower));
    }

    $where_conditions[] = "CAST({$id_column} AS CHAR) LIKE ?";
    $search_params[] = $search_like;

    $where_clause = implode(" OR ", $where_conditions);

    $is_active_check = "";
    if (!$include_inactive) {
        if ($table_name === 'users') {
            try {
                $pdo->query("SELECT is_active FROM users LIMIT 0");
                $is_active_check = "AND is_active = 1";
            } catch (Exception $e) {
                $is_active_check = "";
            }
        } else if ($table_name === 'resident') {
            try {
                $pdo->query("SELECT is_active FROM resident LIMIT 0");
                $is_active_check = "AND is_active = 1";
            } catch (Exception $e) {
                $is_active_check = "";
            }
        }
    }

    $role_check = "";
    if ($table_name === 'users') {
        $role_check = "AND role = 'resident'";
    }

    $query = "
        SELECT 
            {$id_column} as resident_id,
            {$name_column} as full_name,
            {$email_column} as email_address,
            date_of_birth" .
            ($phone_column ? ", {$phone_column} as phone_number" : ", 'N/A' as phone_number") . "
        FROM {$table_name}
        WHERE (
            {$where_clause}
        )
        {$role_check}
        {$is_active_check}
        LIMIT 10
    ";
    
    
    
    
    
    $stmt = $pdo->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Statement preparation failed: " . implode(", ", $pdo->errorInfo()));
    }
    
    
    
    
    
    
    $success = $stmt->execute($search_params);
    
    if (!$success) {
        throw new Exception("Query execution failed: " . implode(", ", $stmt->errorInfo()));
    }
    
    error_log("Search query executed successfully");
    
    
    
    
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'error_type' => 'not_found',
            'message' => 'No patient found matching that ID or details.',
            'search_term' => htmlspecialchars($search_term)
        ]);
        error_log("No results found for search term: '$search_term'");
        exit;
    }
    
    error_log("Found " . count($results) . " result(s) for search term: '$search_term'");
    
    
    
    
    
    $patient_list = [];
    
    foreach ($results as $patient) {
        
        $dob_display = 'N/A';
        if (isset($patient['date_of_birth']) && $patient['date_of_birth']) {
            try {
                $dob_date = new DateTime($patient['date_of_birth']);
                $dob_display = $dob_date->format('M d, Y');
            } catch (Exception $e) {
                $dob_display = htmlspecialchars($patient['date_of_birth'] ?? 'N/A');
            }
        }
        
        $patient_list[] = [
            'id' => $patient['resident_id'],
            'name' => htmlspecialchars($patient['full_name'] ?? 'Unknown'),
            'email' => htmlspecialchars($patient['email_address'] ?? 'N/A'),
            'phone' => htmlspecialchars($patient['phone_number'] ?? 'N/A'),
            'dob' => $dob_display
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'patients' => $patient_list,
        'data' => $patient_list,
        'count' => count($results),
        'message' => count($results) . ' result(s) found. Please select a patient.'
    ]);
    
    error_log("Search returned " . count($results) . " result(s) for search term: '$search_term'");
    exit;
    
} catch (PDOException $e) {
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_type' => 'database_error',
        'message' => 'Database error occurred. Please try again or contact support.'
    ]);
    error_log("PDO Exception in search_patient_process.php: " . $e->getMessage());
    exit;
    
} catch (Exception $e) {
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_type' => 'general_error',
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
    error_log("Exception in search_patient_process.php: " . $e->getMessage());
    exit;
}

?>

