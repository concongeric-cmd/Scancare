<?php
/**
 * ScanCare Logout Handler - NEW ERD SCHEMA
 * =====================================================================
 * Safely destroys all session data, cookies, and redirects to login.
 * Compatible with new ERD schema using resident_id/worker_id.
 * 
 * Session Variables Supported:
 * - $_SESSION['resident_id'] (for residents)
 * - $_SESSION['worker_id'] (for health workers)
 * - $_SESSION['user_type'] ('resident' or 'health_worker')
 * - $_SESSION['user_name']
 * - $_SESSION['email']
 * - $_SESSION['login_time']
 * - $_SESSION['worker_role'] (for health workers: 'bhw' or 'admin')
 */

// =====================================================================
// STEP 1: Start session if not already started
// =====================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_config.php';

// =====================================================================
// STEP 2: Log the logout event (optional - for audit trails)
// =====================================================================
try {
    // Get user info before destroying session (for logging)
    $loggedInUser = $_SESSION['user_name'] ?? 'Unknown User';
    $userType = $_SESSION['user_type'] ?? 'unknown';
    $userId = $_SESSION['resident_id'] ?? $_SESSION['worker_id'] ?? 'N/A';
    $logTime = date('Y-m-d H:i:s');

    if ($userId !== 'N/A') {
        $pdo = getDatabaseConnection();
        if ($pdo !== false) {
            logActivity($pdo, $userId, $userType, 'logout', 'User logged out: ' . $loggedInUser);
        }
    }
    
    // Optional: Log to file for audit purposes
    $logEntry = "{$logTime} | LOGOUT | User: {$loggedInUser} | Type: {$userType} | ID: {$userId}\n";
    
    // Create logs directory if it doesn't exist
    $logsDir = __DIR__ . '/logs';
    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }
    
    @file_put_contents($logsDir . '/logout.log', $logEntry, FILE_APPEND);
    
} catch (Exception $e) {
    // Silently fail if logging doesn't work - don't interrupt logout
    error_log("Logout logging failed: " . $e->getMessage());
}

// =====================================================================
// STEP 3: Detect user role before destroying session
// =====================================================================
// Determine which login page to redirect to based on user role
$userRole = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
$isResident = $userRole === 'resident' || isset($_SESSION['resident_id']);
$isBHW = $userRole === 'health_worker' || $userRole === 'bhw' || isset($_SESSION['worker_id']);
$isAdmin = $userRole === 'admin' || (
    isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true && ($userRole === null || $userRole === 'admin')
);

// Close any active login session for this user before destroying the session.
$logoutUserId = $_SESSION['resident_id'] ?? $_SESSION['worker_id'] ?? $_SESSION['admin_id'] ?? null;
$logoutUserType = null;
if ($isResident) {
    $logoutUserType = 'resident';
} elseif ($isBHW) {
    $logoutUserType = 'health_worker';
} elseif ($isAdmin) {
    $logoutUserType = 'admin';
}

if ($logoutUserId !== null && $logoutUserType !== null) {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo !== false) {
            $stmt = $pdo->prepare("UPDATE user_login_sessions SET status = 'closed', temp_token = NULL, device_token = NULL, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND user_type = ? AND session_id = ?");
            $stmt->execute([$logoutUserId, $logoutUserType, session_id()]);
        }
    } catch (Exception $e) {
        error_log('Logout session cleanup failed: ' . $e->getMessage());
    }
}

// If session contains conflicting role values, prefer explicit user_type
if ($userRole === 'resident') {
    $isResident = true;
    $isBHW = false;
    $isAdmin = false;
} elseif ($userRole === 'health_worker' || $userRole === 'bhw') {
    $isBHW = true;
    $isResident = false;
    $isAdmin = false;
} elseif ($userRole === 'admin') {
    $isAdmin = true;
    $isResident = false;
    $isBHW = false;
}

$adminUsername = $_SESSION['admin_username'] ?? null;

// =====================================================================
// STEP 4: Unset all session variables (NEW ERD + OLD LEGACY SUPPORT)
// =====================================================================
// This is more thorough than just calling session_destroy()
$sessionVarsToUnset = [
    // New ERD schema variables
    'resident_id',
    'worker_id',
    'user_type',
    'user_name',
    'email',
    'login_time',
    'worker_role',
    
    // Admin session variables
    'admin_auth',
    'admin_id',
    'admin_username',
    'admin_email',
    'admin_name',
    'admin_role',
    
    // Legacy/old schema variables (for backwards compatibility)
    'user_id',
    'user_role',
    'first_name',
    'last_name',
    'middle_initial'
];

foreach ($sessionVarsToUnset as $key) {
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

// Also completely clear remaining session variables
$_SESSION = [];

// =====================================================================
// STEP 5: Destroy the session completely
// =====================================================================
session_destroy();

// =====================================================================
// STEP 6: Clear all session cookies
// =====================================================================
// Get session cookie parameters
$params = session_get_cookie_params();

// Delete the PHPSESSID cookie (or whatever the session name is)
setcookie(
    session_name(),
    '',
    time() - 42000,  // Expire in the past
    $params['path'],
    $params['domain'],
    $params['secure'],
    $params['httponly']
);

// Also try to delete common session cookie names (for thorough cleanup)
$cookieNames = ['PHPSESSID', 'JSESSIONID', 'session_id', 'sessionid'];
foreach ($cookieNames as $name) {
    setcookie(
        $name,
        '',
        time() - 42000,
        '/',
        '',
        false,
        true
    );
}

// =====================================================================
// STEP 7: Clear any custom authentication cookies
// =====================================================================
// If your app uses "remember me" or auth tokens, delete them here
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 42000, '/');
}
if (isset($_COOKIE['auth_token'])) {
    setcookie('auth_token', '', time() - 42000, '/');
}
if (isset($_COOKIE['scancare_auth'])) {
    setcookie('scancare_auth', '', time() - 42000, '/');
}

// =====================================================================
// STEP 8: Prevent caching of sensitive pages
// =====================================================================
// Ensure logout page isn't cached (security best practice)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');

// =====================================================================
// STEP 9: Redirect to appropriate login page based on user role
// =====================================================================
// Route to the correct login page based on the user's role
// Admin → login_admin.php (secure admin portal)
// Resident/BHW → login.php (general login)
if ($isAdmin) {
    header('Location: login_admin.php?logged_out=1');
} else {
    header('Location: login.php?logged_out=1');
}
exit;
?>
