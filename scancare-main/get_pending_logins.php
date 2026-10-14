<?php
// ============================================================================
// GET PENDING LOGIN REQUESTS API
// Returns pending login requests for the current user (used for real-time notifications)
// ============================================================================

require_once 'db_config.php';
session_start();

header('Content-Type: application/json');

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
    echo json_encode(['success' => true, 'pending' => [], 'count' => 0]);
    exit;
}

$userId = $currentUser['user_id'];
$userType = $currentUser['user_type'];

try {
    $pdo = getDatabaseConnection();
    ensureUserLoginSessionsTable($pdo);
    ensureCurrentSessionIsActive($pdo, $userId, $userType);
    
    // Expire stale requests before returning the active device's queue.
    $expireStmt = $pdo->prepare("UPDATE user_login_sessions SET status = 'rejected', updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND user_type = ? AND status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $expireStmt->execute([$userId, $userType]);

    // Get pending login requests for this user
    $stmt = $pdo->prepare("
        SELECT 
            id,
            temp_token,
            device_id,
            location,
            ip_address,
            created_at,
            TIMESTAMPDIFF(SECOND, created_at, NOW()) as age_seconds
        FROM user_login_sessions 
        WHERE user_id = ? AND user_type = ? AND status = 'pending'
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId, $userType]);
    $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('GET_PENDING: Found ' . count($pendingRequests) . ' pending requests for user ' . $userId . ' (' . $userType . ')');
    foreach ($pendingRequests as $req) {
        error_log('  - Token: ' . substr($req['temp_token'], 0, 16) . '..., Status: pending, Age: ' . $req['age_seconds'] . 's');
    }
    
    echo json_encode([
        'success' => true,
        'pending' => $pendingRequests,
        'count' => count($pendingRequests)
    ]);
    
} catch (Exception $e) {
    error_log('GET_PENDING_LOGINS_ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'pending' => []]);
    exit;
}
?>
