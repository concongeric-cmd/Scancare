<?php
// ============================================================================
// LOGIN REQUEST APPROVAL/REJECTION API
// Handles approval and rejection of pending login requests from other devices
// ============================================================================

require_once 'db_config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

function getCurrentUserFromSession() {
    if (isset($_SESSION['resident_id']) && $_SESSION['resident_id'] !== '' && isset($_SESSION['user_type'])) {
        return ['user_id' => $_SESSION['resident_id'], 'user_type' => $_SESSION['user_type'] ?? 'resident'];
    }
    if (isset($_SESSION['worker_id']) && $_SESSION['worker_id'] !== '' && isset($_SESSION['user_type'])) {
        return ['user_id' => $_SESSION['worker_id'], 'user_type' => $_SESSION['user_type'] ?? 'health_worker'];
    }
    if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] !== '' && isset($_SESSION['user_type'])) {
        return ['user_id' => $_SESSION['admin_id'], 'user_type' => $_SESSION['user_type'] ?? 'admin'];
    }
    if (isset($_SESSION['user_id'], $_SESSION['user_type']) && $_SESSION['user_id'] !== '' && $_SESSION['user_type'] !== '') {
        return ['user_id' => $_SESSION['user_id'], 'user_type' => $_SESSION['user_type']];
    }
    if (isset($_SESSION['user_id'], $_SESSION['user_role']) && $_SESSION['user_id'] !== '' && $_SESSION['user_role'] !== '') {
        return ['user_id' => $_SESSION['user_id'], 'user_type' => $_SESSION['user_role']];
    }
    return null;
}

function getClientIpAddress() {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
    }
    return '0.0.0.0';
}

function getDeviceIdentifier() {
    return substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);
}

function getDeviceToken() {
    $cookieName = 'sc_device_token';
    $deviceToken = $_COOKIE[$cookieName] ?? null;
    if ($deviceToken && preg_match('/^[a-f0-9]{32,64}$/', $deviceToken)) {
        return $deviceToken;
    }

    try {
        $deviceToken = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $deviceToken = md5(uniqid('', true));
    }

    setcookie($cookieName, $deviceToken, [
        'expires' => time() + (365 * 24 * 60 * 60),
        'path' => '/',
        'secure' => parse_url(appUrl(), PHP_URL_SCHEME) === 'https',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    $_COOKIE[$cookieName] = $deviceToken;
    return $deviceToken;
}

function ensureCurrentSessionIsActive(PDO $pdo, $userId, $userType) {
    $sessionId = session_id();
    $deviceToken = getDeviceToken();
    $deviceId = getDeviceIdentifier();
    $ipAddress = getClientIpAddress();

    $stmt = $pdo->prepare("SELECT id, trusted, status, device_token FROM user_login_sessions WHERE user_id = ? AND user_type = ? AND session_id = ? LIMIT 1");
    $stmt->execute([$userId, $userType, $sessionId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $needsUpdate = $existing['status'] !== 'active' || $existing['trusted'] != 1 || $existing['device_token'] !== $deviceToken;
        if ($needsUpdate) {
            $updateStmt = $pdo->prepare("UPDATE user_login_sessions SET status = 'active', trusted = 1, device_token = ?, device_id = ?, ip_address = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$deviceToken, $deviceId, $ipAddress, $existing['id']]);
        }
        return;
    }

    $insertStmt = $pdo->prepare("INSERT INTO user_login_sessions (user_id, user_type, session_id, device_id, device_token, location, ip_address, status, trusted, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NULL, ?, 'active', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    $insertStmt->execute([$userId, $userType, $sessionId, $deviceId, $deviceToken, $ipAddress]);
}

$currentUser = getCurrentUserFromSession();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = $currentUser['user_id'];
$userType = $currentUser['user_type'];

$action = $_POST['action'] ?? '';
$tempToken = trim($_POST['temp_token'] ?? '');

if (!in_array($action, ['approve', 'reject'], true) || empty($tempToken)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    error_log('APPROVE_REQUEST: User ' . $userId . ' (' . $userType . ') processing action: ' . $action . ' for token: ' . substr($tempToken, 0, 16) . '...');
    
    ensureUserLoginSessionsTable($pdo);
    
    // Find only a pending request belonging to the approving account.
    $stmt = $pdo->prepare("
        SELECT id, user_id, user_type, session_id, status
        FROM user_login_sessions 
        WHERE temp_token = ? AND user_id = ? AND user_type = ? AND status = 'pending'
          AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        LIMIT 1
    ");
    $stmt->execute([$tempToken, $userId, $userType]);
    $loginRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loginRequest) {
        error_log('APPROVE_REQUEST: Login request not found or not pending for token: ' . substr($tempToken, 0, 16) . '...');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Login request not found or already processed']);
        exit;
    }
    
    error_log('APPROVE_REQUEST: Found request ID ' . $loginRequest['id'] . ' for user ' . $loginRequest['user_id']);
    
    if ($action === 'approve') {
        // Update the request status to 'active' so the waiting device can complete login
        // IMPORTANT: Keep temp_token so Device 2 can continue polling with it!
        $updateStmt = $pdo->prepare("
            UPDATE user_login_sessions 
            SET status = 'active', trusted = 1, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND status = 'pending'
        ");
        $updateStmt->execute([$loginRequest['id']]);
        
        error_log('APPROVE_REQUEST: ✅ APPROVED - Updated record ' . $loginRequest['id'] . ' to active and trusted');
        
        echo json_encode([
            'success' => true,
            'message' => 'Login approved. The new device can now complete login.',
            'action' => 'approve'
        ]);
        exit;
        
    } elseif ($action === 'reject') {
        // Update the request status to 'rejected'
        // IMPORTANT: Keep temp_token so Device 2 knows it was rejected!
        $updateStmt = $pdo->prepare("
            UPDATE user_login_sessions 
            SET status = 'rejected', updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND status = 'pending'
        ");
        $updateStmt->execute([$loginRequest['id']]);
        
        error_log('APPROVE_REQUEST: ❌ REJECTED - Updated record ' . $loginRequest['id'] . ' to rejected');
        
        echo json_encode([
            'success' => true,
            'message' => 'Login denied. The new device will be notified.',
            'action' => 'reject'
        ]);
        exit;
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
} catch (Exception $e) {
    error_log('LOGIN_APPROVAL_ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
?>
