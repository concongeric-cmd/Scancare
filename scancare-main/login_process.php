<?php


session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    
    $passwordColumn = hasTableColumn($pdo, 'users', 'password') ? 'password' : 'password_hash';
    $roleColumn = hasTableColumn($pdo, 'users', 'user_role') ? 'user_role' : 'role';
    $nameColumn = hasTableColumn($pdo, 'users', 'full_name') ? 'full_name' : "CONCAT_WS(' ', first_name, last_name)";
    $middleInitialColumn = hasTableColumn($pdo, 'users', 'middle_initial') ? 'middle_initial' : 'NULL';
    $activeColumn = hasTableColumn($pdo, 'users', 'is_active') ? 'is_active' : '1';
    $hasLastLoginColumn = hasTableColumn($pdo, 'users', 'last_login');

    $stmt = $pdo->prepare("SELECT user_id, email, {$passwordColumn} AS password_hash, {$nameColumn} AS full_name, {$middleInitialColumn} AS middle_initial, {$roleColumn} AS user_role, {$activeColumn} AS is_active FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    
    if (!$user) {
        throw new Exception('Invalid email or password');
    }
    
    
    if (!$user['is_active']) {
        throw new Exception('Account has been deactivated');
    }
    
    
    if (!verifyPassword($password, $user['password_hash'])) {
        throw new Exception('Invalid email or password');
    }

    if (in_array(strtolower((string) $user['user_role']), ['admin', 'administrator', 'system_admin'], true)) {
        throw new Exception('Invalid email or password');
    }
    
    
    if ($hasLastLoginColumn) {
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")
            ->execute([$user['user_id']]);
    }
    
    
    $middle_initial_display = !empty($user['middle_initial']) ? ' ' . $user['middle_initial'] : '';
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_name'] = trim($user['full_name'] . $middle_initial_display);
    $_SESSION['user_role'] = $user['user_role'];
    $_SESSION['worker_role'] = $user['user_role'];
    $_SESSION['login_time'] = time();
    logActivity($pdo, $user['user_id'], $user['user_role'], 'login', 'User logged in: ' . ($user['email'] ?? 'unknown email'));

    if (in_array(strtolower((string)$user['user_role']), ['bhw', 'worker', 'health_worker'], true)) {
        $_SESSION['worker_id'] = $user['user_id'];
        $_SESSION['user_type'] = 'health_worker';
        $_SESSION['worker_role'] = $user['user_role'];

        try {
            $profileStmt = $pdo->prepare('SELECT profile_picture FROM health_worker WHERE worker_id = ? LIMIT 1');
            $profileStmt->execute([(int) $user['user_id']]);
            $profilePicture = (string) ($profileStmt->fetchColumn() ?: '');
            if ($profilePicture !== '') {
                $_SESSION['profile_picture'] = $profilePicture;
                $_SESSION['profile_pic'] = $profilePicture;
                $_SESSION['avatar'] = $profilePicture;
            }
        } catch (Throwable $profileError) {
            error_log('LOGIN_PROFILE_PICTURE_LOAD_ERROR: ' . $profileError->getMessage());
        }
    }
    
    
    $redirect = ($user['user_role'] === 'bhw') ? 'bhw_dashboard.php' :
                (($user['user_role'] === 'admin') ? 'admin_dashboard.php' : 'dashboard.php');
    
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['redirect'] = $redirect;
    
} catch (PDOException $e) {
    error_log("LOGIN_DB_ERROR: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("LOGIN_ERROR: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>

