<?php


session_start();
require_once 'db_config.php';







function verifyEmailDomain($email) {
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Invalid email format'];
    }
    
    $email_parts = explode('@', $email);
    if (count($email_parts) !== 2) {
        return ['valid' => false, 'message' => 'Invalid email format'];
    }
    
    $domain = $email_parts[1];
    
    
    
    if (!function_exists('checkdnsrr')) {
        
        
        if (gethostbyname($domain) !== $domain) {
            return ['valid' => true, 'message' => 'Domain verified via DNS lookup'];
        } else {
            return ['valid' => false, 'message' => 'Domain does not appear to be real or reachable'];
        }
    }
    
    
    if (@checkdnsrr($domain, 'MX')) {
        return ['valid' => true, 'message' => 'Email domain verified - real mail server found'];
    }
    
    
    if (@checkdnsrr($domain, 'A')) {
        return ['valid' => true, 'message' => 'Email domain verified - mail server found'];
    }
    
    
    return ['valid' => false, 'message' => 'This email domain does not have a real mail server. Please use a valid email domain (gmail.com, yahoo.com, outlook.com, etc.).'];
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_initial = trim($_POST['middle_initial'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $user_role = $_POST['user_role'] ?? 'resident';
    
    
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        throw new Exception('Email, password, first name, and last name are required');
    }
    
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    
    $domain_check = verifyEmailDomain($email);
    if (!$domain_check['valid']) {
        throw new Exception($domain_check['message']);
    }
    
    
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }
    
    
    if ($password !== $password_confirm) {
        throw new Exception('Passwords do not match');
    }
    
    
    if (!in_array($user_role, ['resident', 'bhw', 'admin'])) {
        throw new Exception('Invalid user role');
    }
    
    
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    
    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        throw new Exception('Email already registered');
    }
    
    
    $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    
    
    if ($user_role === 'bhw') {
        ensureTableColumn($pdo, 'health_worker', 'approval_status', "VARCHAR(20) NOT NULL DEFAULT 'approved'");
        $stmt = $pdo->prepare("INSERT INTO health_worker (full_name, first_name, middle_initial, last_name, email, password_hash, role, contact_number, phone, approval_status, is_active) VALUES (?, ?, ?, ?, ?, ?, 'bhw', ?, ?, 'pending', 0)");
        $result = $stmt->execute([
            trim($first_name . ' ' . $last_name),
            $first_name,
            !empty($middle_initial) ? $middle_initial : NULL,
            $last_name,
            strtolower($email),
            $password_hash,
            $phone_number,
            $phone_number
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO users
            (email, password_hash, first_name, middle_initial, last_name, phone_number, user_role, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $result = $stmt->execute([
            $email,
            $password_hash,
            $first_name,
            !empty($middle_initial) ? $middle_initial : NULL,
            $last_name,
            $phone_number,
            $user_role
        ]);
    }
    
    if (!$result) {
        throw new Exception('Failed to create account');
    }
    
    
    if ($user_role === 'bhw') {
        $response['success'] = true;
        $response['message'] = 'BHW account created and submitted for administrator approval.';
        $response['redirect'] = 'login.php';
        echo json_encode($response);
        exit;
    }

    $new_user_id = $pdo->lastInsertId();
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['email'] = $email;
    $middle_initial_display = !empty($middle_initial) ? ' ' . $middle_initial : '';
    $_SESSION['user_name'] = trim($first_name . ' ' . $last_name . $middle_initial_display);
    $_SESSION['user_role'] = $user_role;
    $_SESSION['login_time'] = time();
    
    
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")
        ->execute([$new_user_id]);
    logActivity($pdo, $new_user_id, $user_role, 'signup', 'Account created: ' . $email);
    
    
    $redirect = ($user_role === 'bhw') ? 'bhw_dashboard.php' : 
                (($user_role === 'admin') ? 'admin_dashboard.php' : 'dashboard.php');
    
    $response['success'] = true;
    $response['message'] = 'Account created successfully';
    $response['redirect'] = $redirect;
    
} catch (PDOException $e) {
    error_log("SIGNUP_DB_ERROR: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("SIGNUP_ERROR: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>

