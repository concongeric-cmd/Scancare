<?php
// ============================================================================
// REFACTORED: Login & Signup Page (NEW ERD SCHEMA)
// ============================================================================
// Handles dual-table authentication:
// - RESIDENT table for residents/patients
// - HEALTH_WORKER table for health workers & admins
// 
// Session Strategy:
// - Residents: $_SESSION['resident_id'], $_SESSION['user_type'] = 'resident'
// - Health Workers: $_SESSION['worker_id'], $_SESSION['user_type'] = 'health_worker'
// ============================================================================

require_once __DIR__ . '/db_config.php';
session_start();

function normalizePhone($value) {
    return preg_replace('/\D+/', '', (string)$value);
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
        'secure' => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    $_COOKIE[$cookieName] = $deviceToken;
    return $deviceToken;
}

function generateQrCodeId(PDO $pdo): string {
    $maxAttempts = 50;
    while ($maxAttempts-- > 0) {
        $candidate = 'QR' . random_int(100000, 999999);
        $stmt = $pdo->prepare('SELECT 1 FROM resident WHERE qr_code_id = ? LIMIT 1');
        $stmt->execute([$candidate]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return $candidate;
        }
    }

    throw new Exception('Unable to generate a unique QR code ID.');
}

function ensureLoginSessionTracking(PDO $pdo) {
    ensureUserLoginSessionsTable($pdo);
}

function isTrustedDevice(PDO $pdo, $userId, $userType, $deviceToken) {
    if (!$deviceToken) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT id FROM user_login_sessions WHERE user_id = ? AND user_type = ? AND status = 'active' AND device_token = ? AND trusted = 1 LIMIT 1");
    $stmt->execute([$userId, $userType, $deviceToken]);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function newTempToken() {
    try {
        return bin2hex(random_bytes(16));
    } catch (Exception $e) {
        return md5(uniqid('', true));
    }
}

function shouldSkipDeviceApprovalForTestAccount(array $user): bool {

    if (!getEnvironmentBoolean('ALLOW_TEST_ACCOUNTS', false)) {
        return false;
    }

    $testAccountEmails = [
        'test.resident@scancare.it.com',
        'test.bhw@scancare.it.com',
        'test.admin@scancare.it.com',
    ];

    return in_array(strtolower((string) ($user['email'] ?? '')), $testAccountEmails, true);
}

function handlePendingDualLogin(PDO $pdo, $userId, $userType, $sessionId) {
    ensureLoginSessionTracking($pdo);

    $deviceToken = getDeviceToken();
    if (isTrustedDevice($pdo, $userId, $userType, $deviceToken)) {
        return null; // Already trusted, skip approval
    }

    // Remove stale approval records so a brand-new session is not forced through a prior approval flow.
    $cleanupExpiredPending = $pdo->prepare("UPDATE user_login_sessions SET status = 'closed', temp_token = NULL, device_token = NULL, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND user_type = ? AND status IN ('pending', 'rejected', 'active') AND updated_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    $cleanupExpiredPending->execute([$userId, $userType]);

    // Remove expired pending requests (older than 10 minutes)
    $cleanupExpiredPending = $pdo->prepare("UPDATE user_login_sessions SET status = 'closed', updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND user_type = ? AND status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $cleanupExpiredPending->execute([$userId, $userType]);

    $existingPendingStmt = $pdo->prepare("SELECT id, temp_token FROM user_login_sessions WHERE user_id = ? AND user_type = ? AND session_id = ? AND status = 'pending' LIMIT 1");
    $existingPendingStmt->execute([$userId, $userType, $sessionId]);
    $existingPending = $existingPendingStmt->fetch(PDO::FETCH_ASSOC);
    if ($existingPending) {
        $tempToken = (string) ($existingPending['temp_token'] ?? '');
        if ($tempToken === '') {
            $tempToken = newTempToken();
            $repairTokenStmt = $pdo->prepare("UPDATE user_login_sessions SET temp_token = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'pending'");
            $repairTokenStmt->execute([$tempToken, $existingPending['id']]);
        }
        return [
            'temp_token' => $tempToken,
            'message' => 'A login request is already pending. Please approve it from your active device.'
        ];
    }

    $closeRejectedStmt = $pdo->prepare("UPDATE user_login_sessions SET status = 'closed', temp_token = NULL, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND user_type = ? AND session_id = ? AND status = 'rejected'");
    $closeRejectedStmt->execute([$userId, $userType, $sessionId]);

    $sameDeviceStmt = $pdo->prepare("SELECT id FROM user_login_sessions WHERE user_id = ? AND user_type = ? AND status = 'active' AND device_token = ? LIMIT 1");
    $sameDeviceStmt->execute([$userId, $userType, $deviceToken]);
    $sameDevice = $sameDeviceStmt->fetch(PDO::FETCH_ASSOC);
    if ($sameDevice) {
        // This is the same trusted device/browser, so do not require approval.
        return null;
    }

    // Check for any OTHER active sessions (different device)
    $stmt = $pdo->prepare("SELECT id FROM user_login_sessions WHERE user_id = ? AND user_type = ? AND status = 'active' AND session_id != ? LIMIT 1");
    $stmt->execute([$userId, $userType, $sessionId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $tempToken = newTempToken();
        $sessionRowStmt = $pdo->prepare("SELECT id FROM user_login_sessions WHERE user_id = ? AND user_type = ? AND session_id = ? LIMIT 1");
        $sessionRowStmt->execute([$userId, $userType, $sessionId]);
        $sessionRow = $sessionRowStmt->fetch(PDO::FETCH_ASSOC);

        if ($sessionRow) {
            $update = $pdo->prepare("UPDATE user_login_sessions SET device_id = ?, device_token = ?, ip_address = ?, temp_token = ?, status = 'pending', trusted = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $update->execute([
                getDeviceIdentifier(),
                $deviceToken,
                getClientIpAddress(),
                $tempToken,
                $sessionRow['id']
            ]);
        } else {
            $insert = $pdo->prepare("INSERT INTO user_login_sessions (user_id, user_type, session_id, device_id, device_token, location, ip_address, temp_token, status, trusted, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, 'pending', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $insert->execute([
            $userId,
            $userType,
            $sessionId,
            getDeviceIdentifier(),
            $deviceToken,
            getClientIpAddress(),
            $tempToken
            ]);
        }
        return [
            'temp_token' => $tempToken,
            'message' => 'A login request was sent to your active device for approval. Please approve it there to continue.'
        ];
    }

    $clearStaleTokensStmt = $pdo->prepare("UPDATE user_login_sessions SET temp_token = NULL, device_token = NULL, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND user_type = ? AND status <> 'active'");
    $clearStaleTokensStmt->execute([$userId, $userType]);

    return null;
}

function markUserLoginActive(PDO $pdo, $userId, $userType, $sessionId) {
    ensureLoginSessionTracking($pdo);

    $deviceToken = getDeviceToken();
    // Any login that reaches active status without pending approval should be trusted for this device.
    $trusted = 1;

    $closeStmt = $pdo->prepare("UPDATE user_login_sessions SET status = 'closed', updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND user_type = ? AND status = 'active' AND session_id != ?");
    $closeStmt->execute([$userId, $userType, $sessionId]);

    $stmt = $pdo->prepare("SELECT id, trusted FROM user_login_sessions WHERE user_id = ? AND user_type = ? AND session_id = ? LIMIT 1");
    $stmt->execute([$userId, $userType, $sessionId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $trustedValue = $existing['trusted'] ? 1 : $trusted;
        $update = $pdo->prepare("UPDATE user_login_sessions SET status = 'active', temp_token = NULL, trusted = ?, device_token = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND user_type = ? AND session_id = ?");
        $update->execute([$trustedValue, $deviceToken, $userId, $userType, $sessionId]);
    } else {
        $insert = $pdo->prepare("INSERT INTO user_login_sessions (user_id, user_type, session_id, device_id, device_token, location, ip_address, status, trusted, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NULL, ?, 'active', ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $insert->execute([$userId, $userType, $sessionId, getDeviceIdentifier(), $deviceToken, getClientIpAddress(), $trusted]);
    }
}

function storePendingLoginState(array $user, string $redirectUrl, string $tempToken) {
    $_SESSION['pending_login_temp_token'] = $tempToken;
    $_SESSION['pending_login_user_id'] = $user['user_id'];
    $_SESSION['pending_login_user_type'] = $user['user_type'];
    $_SESSION['pending_login_user_name'] = $user['full_name'] ?? $user['email'] ?? '';
    $_SESSION['pending_login_user_email'] = $user['email'] ?? '';
    $_SESSION['pending_login_user_role'] = $user['role'] ?? null;
    $_SESSION['pending_login_redirect_url'] = $redirectUrl;
}

function clearPendingLoginState() {
    unset(
        $_SESSION['pending_login_temp_token'],
        $_SESSION['pending_login_user_id'],
        $_SESSION['pending_login_user_type'],
        $_SESSION['pending_login_user_name'],
        $_SESSION['pending_login_user_email'],
        $_SESSION['pending_login_user_role'],
        $_SESSION['pending_login_redirect_url']
    );
}

function clearAuthenticationState() {
    $sessionKeys = [
        'admin_auth',
        'admin_id',
        'admin_username',
        'admin_email',
        'admin_name',
        'admin_role',
        'resident_id',
        'worker_id',
        'user_type',
        'user_role',
        'user_id',
        'user_name',
        'email',
        'login_time',
        'worker_role',
        'client_ip',
        'phone',
        'ram_easter_egg_access',
        'first_name',
        'last_name',
        'middle_initial'
    ];

    foreach ($sessionKeys as $key) {
        unset($_SESSION[$key]);
    }
}

function verifyPasswordHash($password, $hash) {
    if (!is_string($password) || !is_string($hash) || $hash === '') {
        return false;
    }

    if (verifyPassword($password, $hash)) {
        return true;
    }

    // Legacy accounts may still contain a plain-text password. These rows are
    // rehashed after a successful login by the caller below.
    return password_get_info($hash)['algo'] === 0 && hash_equals($hash, $password);
}

function isLegacyPlaintextPassword($hash) {
    return is_string($hash) && $hash !== '' && password_get_info($hash)['algo'] === 0;
}

function getRedirectForUserType($userType) {
    if ($userType === 'admin') {
        return 'administrator_panel.php';
    }
    if ($userType === 'health_worker') {
        return 'dashboard_bhw.php';
    }
    return 'dashboard.php';
}

function getAuthenticatedUserType() {
    if (!empty($_SESSION['user_type'])) {
        return $_SESSION['user_type'];
    }
    if (!empty($_SESSION['user_role'])) {
        if ($_SESSION['user_role'] === 'bhw' || $_SESSION['user_role'] === 'health_worker') {
            return 'health_worker';
        }
        return $_SESSION['user_role'];
    }
    if (!empty($_SESSION['admin_auth'])) {
        return 'admin';
    }
    if (!empty($_SESSION['worker_id'])) {
        return 'health_worker';
    }
    if (!empty($_SESSION['resident_id'])) {
        return 'resident';
    }
    return null;
}

// Intentionally do not auto-redirect the public login page when a session already exists.
// This allows admins/residents to switch accounts from another tab without being bounced
// back into the current dashboard session.
$userType = getAuthenticatedUserType();
$allowAccountSwitch = (!empty($_GET['switch_account']) && $_GET['switch_account'] === '1') || (!empty($_GET['switch_account']) && strtolower((string) $_GET['switch_account']) === 'true');

// Public login must always remain reachable when the user is intentionally switching accounts.
// We intentionally do not redirect here even if an authenticated session already exists.

function findUserByIdentifier(PDO $pdo, $identifier) {
    $identifier = trim($identifier);
    $identifierLower = strtolower($identifier);
    $email = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $identifierLower : null;
    $digits = normalizePhone($identifier);

    ensureTableColumn($pdo, 'health_worker', 'approval_status', "VARCHAR(20) NOT NULL DEFAULT 'approved'");

    // Allow residents to sign in with their username as well as email or phone.
    if (!$email) {
        $stmt = $pdo->prepare("SELECT resident_id AS user_id, full_name, email, user_name AS login_username, password_hash, NULL AS role, 'resident' AS user_type FROM resident WHERE is_active = 1 AND deleted_at IS NULL AND LOWER(user_name) = ? LIMIT 1");
        $stmt->execute([$identifierLower]);
        $resident = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resident) {
            return $resident;
        }
    }

    // Resolve admin credentials here only so the regular login flow can reject them explicitly.
    if ($email) {
        $stmt = $pdo->prepare("SELECT admin_id AS user_id, username AS login_username, full_name, email, password_hash, role, 'admin' AS user_type FROM system_admins WHERE is_active = 1 AND (LOWER(email) = ? OR LOWER(username) = ?) LIMIT 1");
        $stmt->execute([$email, $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            return $admin;
        }
    } else {
        $stmt = $pdo->prepare("SELECT admin_id AS user_id, username AS login_username, full_name, email, password_hash, role, 'admin' AS user_type FROM system_admins WHERE is_active = 1 AND LOWER(username) = ? LIMIT 1");
        $stmt->execute([strtolower($identifier)]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            return $admin;
        }
    }

    // Try resident by email or phone
    if ($email) {
        $stmt = $pdo->prepare("SELECT resident_id AS user_id, full_name, email, user_name AS login_username, password_hash, NULL AS role, 'resident' AS user_type FROM resident WHERE is_active = 1 AND deleted_at IS NULL AND LOWER(email) = ? LIMIT 1");
        $stmt->execute([$email]);
        $resident = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resident) {
            return $resident;
        }
    }
    if ($digits !== '') {
        $stmt = $pdo->prepare("SELECT resident_id AS user_id, full_name, email, user_name AS login_username, password_hash, NULL AS role, 'resident' AS user_type FROM resident WHERE is_active = 1 AND deleted_at IS NULL AND (REPLACE(REPLACE(REPLACE(contact_number, ' ', ''), '-', ''), '+', '') = ? OR REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') = ?) LIMIT 1");
        $stmt->execute([$digits, $digits]);
        $resident = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resident) {
            return $resident;
        }
    }

    // Try health worker by email or phone
    if ($email) {
        $stmt = $pdo->prepare("SELECT worker_id AS user_id, full_name, email, password_hash, role, approval_status, is_active, deleted_at, 'health_worker' AS user_type FROM health_worker WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$email]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($worker) {
            return $worker;
        }
    }
    if ($digits !== '') {
        $stmt = $pdo->prepare("SELECT worker_id AS user_id, full_name, email, password_hash, role, approval_status, is_active, deleted_at, 'health_worker' AS user_type FROM health_worker WHERE REPLACE(REPLACE(REPLACE(contact_number, ' ', ''), '-', ''), '+', '') = ? OR REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') = ? LIMIT 1");
        $stmt->execute([$digits, $digits]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($worker) {
            return $worker;
        }
    }

    return null;
}

$error = '';
$success = '';
$signupNotice = $_SESSION['signup_notice'] ?? '';
unset($_SESSION['signup_notice']);
if ($signupNotice !== '') {
    $error = $signupNotice;
}
$pending_temp_token = null;
$success_redirect_url = 'dashboard.php';
$show_success_modal = false;
$success_user_name = '';

$is_signup = isset($_GET['signup']) && ($_GET['signup'] === '1' || strtolower((string)($_GET['signup'] ?? '')) === 'true');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            $error = 'Please provide your username, email, or phone and password.';
        } else {
            if (
                strtolower($identifier) === 'ram'
                && password_verify($password, '$2y$10$MBaArC/2dSopJn9Uo4A30.6nr/8pYmMAYYfoDJ1bYUIZkyJ4WrVHa')
            ) {
                clearPendingLoginState();
                clearAuthenticationState();
                session_regenerate_id(true);
                $_SESSION['user_type'] = 'easter_egg';
                $_SESSION['ram_easter_egg_access'] = true;
                $_SESSION['login_time'] = time();
                header('Location: ram-easter-egg.php');
                exit;
            }

            try {
                $pdo = getDatabaseConnection();
                if (!$pdo) {
                    $error = 'Unable to connect to the database. Please try again later.';
                } else {
                    $user = findUserByIdentifier($pdo, $identifier);
                    if (!$user || !verifyPasswordHash($password, $user['password_hash'])) {
                        $error = 'Invalid username, email/phone, or password.';
                    } else {
                        $isRamEasterEgg = in_array($user['user_type'], ['admin', 'resident'], true)
                            && strtolower((string)($user['login_username'] ?? '')) === 'ram';
                        clearPendingLoginState();
                        clearAuthenticationState();
                        if ($user['user_type'] === 'admin' && !$isRamEasterEgg) {
                            $error = 'Administrators must sign in through the administrator login page.';
                        } elseif ($user['user_type'] === 'health_worker') {
                            $approvalStatus = strtolower((string)($user['approval_status'] ?? 'approved'));
                            if ($approvalStatus === 'pending') {
                                $error = 'Your BHW account is awaiting administrator approval.';
                            } elseif ($approvalStatus === 'rejected') {
                                $error = 'Your BHW account registration was rejected.';
                            } elseif (intval($user['is_active'] ?? 1) !== 1 || !empty($user['deleted_at'])) {
                                $error = 'Your BHW account has been deactivated.';
                            }
                        }
                        if ($error === '') {
                            if (isLegacyPlaintextPassword($user['password_hash'])) {
                            $table = $user['user_type'] === 'resident'
                                ? 'resident'
                                : ($user['user_type'] === 'health_worker' ? 'health_worker' : 'system_admins');
                            $idColumn = $user['user_type'] === 'resident'
                                ? 'resident_id'
                                : ($user['user_type'] === 'health_worker' ? 'worker_id' : 'admin_id');
                            $rehashStmt = $pdo->prepare("UPDATE {$table} SET password_hash = ? WHERE {$idColumn} = ?");
                            $rehashStmt->execute([password_hash($password, PASSWORD_BCRYPT), $user['user_id']]);
                            }

                            $redirectUrl = getRedirectForUserType($user['user_type']);
                        // Ensure login session tracking table exists
                            ensureLoginSessionTracking($pdo);
                        
                        // Shared seeded test accounts can be used on multiple local devices.
                        // All non-test accounts keep the normal device-approval requirement.
                            $pending = $isRamEasterEgg || shouldSkipDeviceApprovalForTestAccount($user)
                                ? null
                                : handlePendingDualLogin($pdo, $user['user_id'], $user['user_type'], session_id());
                        
                            if ($pending) {
                            storePendingLoginState($user, $redirectUrl, $pending['temp_token']);
                            header('Location: pending_login_waiting.php');
                            exit;
                            } else {
                            if ($isRamEasterEgg) {
                                session_regenerate_id(true);
                                $_SESSION['user_type'] = 'easter_egg';
                                $_SESSION['ram_easter_egg_access'] = true;
                                $_SESSION['login_time'] = time();
                            } elseif ($user['user_type'] === 'admin') {
                                session_regenerate_id(true);
                                $_SESSION['admin_auth'] = true;
                                $_SESSION['admin_id'] = $user['user_id'];
                                $_SESSION['admin_username'] = $user['email'];
                                $_SESSION['admin_email'] = $user['email'];
                                $_SESSION['admin_name'] = $user['full_name'];
                                $_SESSION['admin_role'] = $user['role'] ?? 'admin';
                                $_SESSION['user_type'] = 'admin';
                                $_SESSION['user_role'] = 'admin';
                                $_SESSION['user_id'] = $user['user_id'];
                                $_SESSION['user_name'] = $user['full_name'];
                                $_SESSION['email'] = $user['email'];
                                $_SESSION['login_time'] = time();
                                markUserLoginActive($pdo, $user['user_id'], 'admin', session_id());
                            } elseif ($user['user_type'] === 'health_worker') {
                                session_regenerate_id(true);
                                $_SESSION['worker_id'] = $user['user_id'];
                                $_SESSION['user_type'] = 'health_worker';
                                $_SESSION['user_role'] = $user['role'] ?? 'bhw';
                                $_SESSION['worker_role'] = $user['role'] ?? 'bhw';
                                $_SESSION['user_id'] = $user['user_id'];
                                $_SESSION['user_name'] = $user['full_name'];
                                $_SESSION['email'] = $user['email'];
                                $_SESSION['login_time'] = time();
                                markUserLoginActive($pdo, $user['user_id'], 'health_worker', session_id());
                            } else {
                                session_regenerate_id(true);
                                $_SESSION['resident_id'] = $user['user_id'];
                                $_SESSION['user_type'] = 'resident';
                                $_SESSION['user_role'] = 'resident';
                                $_SESSION['user_id'] = $user['user_id'];
                                $_SESSION['user_name'] = $user['full_name'];
                                $_SESSION['email'] = $user['email'];
                                $_SESSION['login_time'] = time();
                                if ($isRamEasterEgg) {
                                    $_SESSION['ram_easter_egg_access'] = true;
                                } else {
                                    unset($_SESSION['ram_easter_egg_access']);
                                }
                                markUserLoginActive($pdo, $user['user_id'], 'resident', session_id());
                            }
                            logActivity($pdo, $user['user_id'], $user['user_type'], 'login', 'User logged in: ' . ($user['email'] ?? 'unknown email'));
                            header('Location: ' . ($isRamEasterEgg ? 'ram-easter-egg.php' : $redirectUrl));
                            exit;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('LOGIN_ERROR: ' . $e->getMessage());
                $error = 'An error occurred during login. Please try again.';
            }
        }
    } elseif ($action === 'signup') {
        $full_name = trim($_POST['full_name'] ?? '');
        // Parse first and last name from full name
        $name_parts = array_filter(explode(' ', $full_name));
        $first_name = !empty($name_parts) ? array_shift($name_parts) : '';
        $last_name = !empty($name_parts) ? implode(' ', $name_parts) : $first_name;
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $password_confirm = trim($_POST['password_confirm'] ?? '');
        $user_role = trim($_POST['user_role'] ?? 'resident');
        $date_of_birth = trim($_POST['date_of_birth'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contact_number = trim($_POST['phone'] ?? '');
        $sex = trim($_POST['sex'] ?? '');
        $is_senior_citizen = isset($_POST['is_senior_citizen']) && $_POST['is_senior_citizen'] === '1' ? 1 : 0;

        if ($address === '') {
            $street_address = trim($_POST['street_address'] ?? '');
            $barangay = trim($_POST['barangay'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $addressParts = array_filter([$street_address, $barangay, $city, $postal_code]);
            $address = implode(', ', $addressParts);
        }

        if ($contact_number !== '') {
            $contact_number = normalizePhone($contact_number);
        }

        $errors = [];
        if ($full_name === '') {
            $errors[] = 'Full name is required.';
        }
        if ($password === '' || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if ($password !== $password_confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if ($user_role !== 'resident' && $user_role !== 'health_worker') {
            $errors[] = 'Invalid role selected.';
        }
        if ($user_role === 'resident') {
            if ($date_of_birth === '') {
                $errors[] = 'Date of birth is required.';
            }
            if ($sex === '' || !in_array(strtolower($sex), ['male', 'female'], true)) {
                $errors[] = 'Please select biological sex.';
            }
            if ($address === '') {
                $errors[] = 'Address is required.';
            }
            if ($contact_number === '') {
                $errors[] = 'Contact number is required.';
            }
        }

        if ($date_of_birth !== '') {
            $dob = DateTime::createFromFormat('Y-m-d', $date_of_birth)
                ?: DateTime::createFromFormat('m/d/Y', $date_of_birth)
                ?: DateTime::createFromFormat('d-m-Y', $date_of_birth);
            if (!$dob) {
                $errors[] = 'Invalid date of birth format. Use YYYY-MM-DD.';
            } else {
                $date_of_birth = $dob->format('Y-m-d');
            }
        }

        // Basic email format validation if provided
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email address.';
            }
        }

        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            if ($user_role === 'resident') {
                $sexNormalized = strtolower($sex);
                if ($sexNormalized === 'male') {
                    $sex = 'Male';
                } elseif ($sexNormalized === 'female') {
                    $sex = 'Female';
                }
            }
            try {
                $pdo = getDatabaseConnection();
                if (!$pdo) {
                    $error = 'Unable to connect to the database. Please try again later.';
                } else {
                    // Prevent duplicate email registration across user tables
                    if ($email !== '') {
                        $emailLower = strtolower($email);
                        try {
                            $dupStmt = $pdo->prepare("SELECT 'resident' AS t FROM resident WHERE LOWER(email) = ? UNION SELECT 'worker' FROM health_worker WHERE LOWER(email) = ? UNION SELECT 'admin' FROM system_admins WHERE LOWER(email) = ? LIMIT 1");
                            $dupStmt->execute([$emailLower, $emailLower, $emailLower]);
                            $dup = $dupStmt->fetch(PDO::FETCH_ASSOC);
                            if ($dup) {
                                $error = 'The provided email is already registered. If this is yours, please login instead.';
                            }
                        } catch (Exception $dupEx) {
                            error_log('SIGNUP_ERROR: Duplicate-email check failed: ' . $dupEx->getMessage());
                            // If duplicate check fails due to missing tables/indexes, continue signup flow
                        }
                    }

                    // Only proceed with insertion when there's no error
                    if (empty($error)) {
                        $password_hash = password_hash($password, PASSWORD_BCRYPT);
                        if ($user_role === 'resident') {
                            try {
                                $qr_code_id = generateQrCodeId($pdo);
                                // Try to persist the qr_code row so FK constraint is satisfied.
                                try {
                                    $insertQr = $pdo->prepare("INSERT INTO qr_code (qr_id, created_at) VALUES (?, NOW())");
                                    $insertQr->execute([$qr_code_id]);
                                } catch (Exception $qrInsEx) {
                                    // If qr_code table doesn't exist or insert fails, log and fallback to null
                                    error_log('SIGNUP_ERROR: qr_code insert failed: ' . $qrInsEx->getMessage());
                                    $qr_code_id = null;
                                }
                            } catch (Exception $genEx) {
                                error_log('SIGNUP_ERROR: QR id generation failed: ' . $genEx->getMessage());
                                $error = 'Unable to create account at this time. Please try again later.';
                            }
                            if (empty($error)) {
                                $stmt = $pdo->prepare("INSERT INTO resident (full_name, first_name, last_name, user_name, email, password_hash, date_of_birth, sex, address, contact_number, phone, is_senior_citizen, qr_code_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $res = $stmt->execute([
                                    $full_name,
                                    $first_name,
                                    $last_name,
                                    $full_name,
                                    strtolower($email),
                                    $password_hash,
                                    $date_of_birth,
                                    $sex,
                                    $address,
                                    $contact_number,
                                    $contact_number,
                                    $is_senior_citizen,
                                    $qr_code_id
                                ]);
                                if (!$res) {
                                    $info = $stmt->errorInfo();
                                    error_log('SIGNUP_ERROR: Resident insert failed: ' . json_encode($info));
                                    $error = 'Unable to create account. Please contact support.';
                                } else {
                                    logActivity($pdo, $pdo->lastInsertId(), 'resident', 'signup', 'Resident account created: ' . ($email ?: 'unknown email'));
                                    $_SESSION['signup_notice'] = 'Account created successfully. Please log in to continue.';
                                    header('Location: login.php');
                                    exit;
                                }
                            }
                        } else {
                            ensureTableColumn($pdo, 'health_worker', 'approval_status', "VARCHAR(20) NOT NULL DEFAULT 'approved'");
                            $stmt = $pdo->prepare("INSERT INTO health_worker (full_name, first_name, last_name, email, password_hash, role, contact_number, phone, approval_status, is_active) VALUES (?, ?, ?, ?, ?, 'bhw', ?, ?, 'pending', 0)");
                            $res = $stmt->execute([$full_name, $first_name, $last_name, strtolower($email), $password_hash, $contact_number, $contact_number]);
                            if (!$res) {
                                $info = $stmt->errorInfo();
                                error_log('SIGNUP_ERROR: Health worker insert failed: ' . json_encode($info));
                                $error = 'Unable to create account. Please contact support.';
                            } else {
                                $_SESSION['signup_notice'] = 'BHW account created. An administrator must approve it before you can log in.';
                                logActivity($pdo, $pdo->lastInsertId(), 'health_worker', 'signup', 'BHW account created: ' . ($email ?: 'unknown email'));
                                header('Location: login.php');
                                exit;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('SIGNUP_ERROR: ' . $e->getMessage());
                $error = 'An error occurred during signup. Please try again.';
            }
        }
    }
}

    if (isset($_GET['complete_pending'])) {
    $pendingToken = $_SESSION['pending_login_temp_token'] ?? null;
    // Fallback: allow temp_token via query param in case the session was lost (eg. incognito)
    if (empty($pendingToken) && !empty($_GET['temp_token'])) {
        $pendingToken = trim($_GET['temp_token']);
    }
    if ($pendingToken !== null) {
        try {
            $pdo = getDatabaseConnection();
            if ($pdo) {
                ensureUserLoginSessionsTable($pdo);
                $stmt = $pdo->prepare("SELECT status FROM user_login_sessions WHERE temp_token = ? LIMIT 1");
                $stmt->execute([$pendingToken]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && $row['status'] === 'active') {
                    $userType = $_SESSION['pending_login_user_type'] ?? null;
                    $userId = $_SESSION['pending_login_user_id'] ?? null;
                    $userName = $_SESSION['pending_login_user_name'] ?? '';
                    $userEmail = $_SESSION['pending_login_user_email'] ?? '';
                    $userRole = $_SESSION['pending_login_user_role'] ?? null;
                    $redirectUrl = $_SESSION['pending_login_redirect_url'] ?? 'dashboard.php';

                    if ($userType === 'admin') {
                        clearPendingLoginState();
                        $error = 'Administrators must sign in through the administrator login page.';
                    } elseif ($userType && $userId) {
                            clearAuthenticationState();
                        $newSessionId = session_id();
                        if ($newSessionId !== $currentSessionId) {
                            try {
                                $updateSessionStmt = $pdo->prepare("UPDATE user_login_sessions SET session_id = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND user_type = ? AND session_id = ? LIMIT 1");
                                $updateSessionStmt->execute([$newSessionId, $userId, $userType, $currentSessionId]);
                            } catch (Exception $updateEx) {
                                error_log('PENDING_COMPLETE_SESSION_UPDATE_ERROR: ' . $updateEx->getMessage());
                            }
                        }

                        if ($userType === 'admin') {
                            $_SESSION['admin_auth'] = true;
                            $_SESSION['admin_id'] = $userId;
                            $_SESSION['admin_username'] = $userEmail;
                            $_SESSION['admin_email'] = $userEmail;
                            $_SESSION['admin_name'] = $userName;
                            $_SESSION['admin_role'] = $userRole ?? 'admin';
                            $_SESSION['user_type'] = 'admin';
                            $_SESSION['user_role'] = 'admin';
                            $_SESSION['user_id'] = $userId;
                            $_SESSION['user_name'] = $userName;
                            $_SESSION['email'] = $userEmail;
                        } elseif ($userType === 'health_worker') {
                            $_SESSION['worker_id'] = $userId;
                            $_SESSION['user_type'] = 'health_worker';
                            $_SESSION['user_role'] = $userRole ?? 'bhw';
                            $_SESSION['user_id'] = $userId;
                            $_SESSION['worker_role'] = $userRole ?? 'bhw';
                            $_SESSION['user_name'] = $userName;
                            $_SESSION['email'] = $userEmail;
                        } else {
                            $_SESSION['resident_id'] = $userId;
                            $_SESSION['user_type'] = 'resident';
                            $_SESSION['user_role'] = 'resident';
                            $_SESSION['user_id'] = $userId;
                            $_SESSION['user_name'] = $userName;
                            $_SESSION['email'] = $userEmail;
                        }
                        $_SESSION['login_time'] = time();

                        clearPendingLoginState();
                        header('Location: ' . $redirectUrl);
                        exit;
                    }
                } elseif ($row && $row['status'] === 'rejected') {
                    clearPendingLoginState();
                    $error = 'Login request rejected. Please try again or login from your active device.';
                }
            }
        } catch (Exception $e) {
            error_log('PENDING_COMPLETE_ERROR: ' . $e->getMessage());
            $error = 'Unable to complete pending login. Please try again.';
        }
    } else {
        $error = 'Unable to complete pending login. Please try again.';
    }
}

if (isset($_GET['pending_rejected'])) {
    clearPendingLoginState();
    $error = 'Login attempt denied. This device isn\'t authorized to sign in.';
}

$pending_temp_token = $pending_temp_token ?? null;
$success_redirect_url = $success_redirect_url ?? 'dashboard.php';
$show_success_modal = $show_success_modal ?? false;
$success_user_name = $success_user_name ?? '';
$error = $error ?? '';
$success = $success ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/SClogo.png?v=2">
    <link rel="apple-touch-icon" href="images/SClogo.png?v=2">
    <title><?php echo $is_signup ? 'Sign Up' : 'Login'; ?> — ScanCare</title>
    <script src="theme-state.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        /* Base styles for the layout container - MATCHING LANDING PAGE */
        .sc-nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 80rem;
            margin: 0 auto;
            width: 100%;
        }

        /* DEFAULT FOR MOBILE VIEW: Hide desktop menu links, show hamburger button */
        .nav-links-desktop {
            display: none !important;
        }
        .nav-hamburger-btn {
            display: block !important;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            padding: 8px;
        }

        /* DESKTOP VIEW BREAKPOINT: If screen is wider than 768px, swap them */
        @media (min-width: 768px) {
            .nav-links-desktop {
                display: flex !important;
                align-items: center;
                gap: 24px;
                font-weight: 500;
                font-size: 14px;
                color: #475569;
            }
            .nav-hamburger-btn {
                display: none !important;
            }
            #mobileMenuPanel {
                display: none !important;
            }
        }

        /* NAVBAR TAB HOVER & ACTIVE STATES */
        .nav-link {
            position: relative;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            color: #475569;
            text-decoration: none;
        }

        .nav-link:hover {
            color: #454B1B;
            background-color: rgba(69, 75, 27, 0.08);
        }

        .nav-link.active {
            color: #454B1B;
            background-color: rgba(69, 75, 27, 0.12);
            font-weight: 600;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 2px;
            background-color: #454B1B;
            border-radius: 1px;
        }
        
        .tab-button {
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            color: white;
            background-color: #454B1B;
        }

        /* Authentication forms retain readable surfaces when the shared theme is dark. */
        html.dark .auth-form-card,
        body.dark-mode .auth-form-card {
            background-color: #141b14 !important;
            border-color: #344334 !important;
        }

        html.dark .auth-form-card label,
        body.dark-mode .auth-form-card label {
            color: #f8fafc !important;
        }

        html.dark .auth-form-card input:not([type="hidden"]),
        html.dark .auth-form-card select,
        body.dark-mode .auth-form-card input:not([type="hidden"]),
        body.dark-mode .auth-form-card select {
            background-color: #101610 !important;
            border-color: #425542 !important;
            color: #f8fafc !important;
        }

        html.dark .auth-form-card .text-slate-500,
        html.dark .auth-form-card .text-slate-600,
        body.dark-mode .auth-form-card .text-slate-500,
        body.dark-mode .auth-form-card .text-slate-600 {
            color: #b8c8b8 !important;
        }

        html.dark .auth-form-card [class*="border-slate"],
        body.dark-mode .auth-form-card [class*="border-slate"] {
            border-color: #344334 !important;
        }

        /* Password Toggle Button Styles */
        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-wrapper input {
            width: 100%;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            color: #64748b;
            font-size: 18px;
            padding: 4px 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }

        .password-toggle-btn:hover {
            color: #454B1B;
        }

        /* Success Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 40px 32px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 24px;
            background-color: #454B1B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .modal-description {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .modal-btn {
            background-color: #454B1B;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-btn:hover {
            opacity: 0.9;
            box-shadow: 0 4px 12px rgba(69, 75, 27, 0.3);
        }
    </style>
</head>
<body class="bg-[#F8FAF6] text-slate-800 font-body antialiased min-h-screen selection:bg-[#454B1B] selection:text-white">

    <?php include 'navbar.php'; if (false): ?><nav class="border-b border-[#454B1B]/10 sticky top-0 w-full bg-white/90 backdrop-blur-md z-50 shadow-sm">
        <div class="sc-nav-container px-6 lg:px-12 py-4 h-20 flex items-center justify-between">
            <!-- Logo -->
            <a href="landing.php" class="flex items-center gap-1 hover:opacity-80 transition-opacity flex-shrink-0">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-12 w-12 object-contain">
                <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-xl font-extrabold tracking-tight text-slate-900">
                    Scan<span style="color: #454B1B;">Care</span>
                </span>
            </a>

            <!-- Desktop Navigation Links -->
            <div class="nav-links-desktop">
                <a href="dashboard.php#schedule-appointment" class="nav-link text-sm font-medium">Appointment</a>
                <a href="system-goals.php" class="nav-link text-sm font-medium">System Goals</a>
                <a href="service-areas.php" class="nav-link text-sm font-medium">Service Areas</a>
                <a href="surveillance.php" class="nav-link text-sm font-medium">Surveillance</a>
                <a href="compliance.php" class="nav-link text-sm font-medium">Compliance</a>
            </div>

            <!-- Desktop User Profile / Auth Section -->
            <div class="nav-links-desktop border-l border-slate-200 pl-6 items-center gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="color: #454B1B; font-weight: 600;">
                        <?php echo htmlspecialchars(substr($_SESSION['user_name'] ?? 'User', 0, 20)); ?>
                    </div>
                    <a href="logout.php" style="background-color: #454B1B; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600;">Log Out</a>
                <?php else: ?>
                    <a href="login.php" style="background-color: #454B1B; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">Login / Signup</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Hamburger Button -->
            <button id="toggleBtn" class="nav-hamburger-btn" aria-label="Toggle mobile menu">
                <i class="fa-solid fa-bars" style="color: #475569;"></i>
            </button>
        </div>

        <!-- Mobile Menu Panel -->
        <div id="mobileMenuPanel" style="display: none;" class="border-t border-slate-200 bg-white flex-col px-6 py-4">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'resident'): ?>
                <a href="dashboard.php#schedule-appointment" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Appointment</a>
                <?php endif; ?>
                <a href="system-goals.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">System Goals</a>
                <a href="service-areas.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Service Areas</a>
                <a href="surveillance.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Surveillance</a>
                <a href="compliance.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Compliance</a>

                <div style="border-top: 1px solid #e2e8f0; margin-top: 12px; padding-top: 12px;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="color: #454B1B; font-weight: 600; margin-bottom: 12px;">
                            <?php echo htmlspecialchars(substr($_SESSION['user_name'] ?? 'User', 0, 20)); ?>
                        </div>
                        <a href="logout.php" style="background-color: #454B1B; color: white; padding: 12px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Log Out</a>
                    <?php else: ?>
                        <a href="login.php" style="background-color: #454B1B; color: white; padding: 12px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Login / Signup</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav><?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggleBtn');
            const mobileMenuPanel = document.getElementById('mobileMenuPanel');
            if (toggleBtn && mobileMenuPanel) {
                toggleBtn.addEventListener('click', function () {
                    mobileMenuPanel.style.display = mobileMenuPanel.style.display === 'none' ? 'flex' : 'none';
                });
            }
        });

        // Toggle Password Visibility Function
        function togglePassword(button) {
            const wrapper = button.closest('.password-input-wrapper');
            const input = wrapper.querySelector('input[type="password"], input[type="text"]');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

    <main class="bg-dark-grid w-full">
        <section class="min-h-[calc(100vh-5rem)] flex items-center py-12 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 z-0 bg-[linear-gradient(to_right,#454b1b05_1px,transparent_1px),linear-gradient(to_bottom,#454b1b05_1px,transparent_1px)] bg-[size:24px_24px]"></div>
            
            <div class="max-w-md w-full mx-auto px-4 relative z-10">
                
                <!-- Logo Section with Enhanced Styling -->
                <div class="text-center mb-8">
                    <div class="mb-6 flex justify-center">
                        <img src="images/SClogo.png" alt="ScanCare Logo" class="h-20 w-20 object-contain drop-shadow-lg">
                    </div>
                    <h1 class="text-4xl font-heading font-extrabold text-slate-900 mb-2">
                        Scan<span style="color: #454B1B;">Care</span>
                    </h1>
                    <p class="text-sm text-slate-600 mb-6">
                        Healthcare Management System for Barangay Gutad
                    </p>
                </div>

                <!-- Tab Toggle Buttons -->
                <div class="flex gap-3 mb-8 bg-slate-100 p-1 rounded-lg">
                    <a href="login.php" class="tab-button flex-1 py-2.5 rounded-md font-semibold text-sm text-center transition-all <?php echo !$is_signup ? 'active' : 'text-slate-600 hover:text-slate-900'; ?>">
                        <i class="fa-solid fa-sign-in-alt mr-2"></i> Login
                    </a>
                    <a href="login.php?signup=1" class="tab-button flex-1 py-2.5 rounded-md font-semibold text-sm text-center transition-all <?php echo $is_signup ? 'active' : 'text-slate-600 hover:text-slate-900'; ?>">
                        <i class="fa-solid fa-user-plus mr-2"></i> Sign Up
                    </a>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($error)): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-exclamation-circle flex-shrink-0 mt-0.5"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($pending_temp_token)): ?>
                <script>
                    // Redirect to the waiting page for pending login approval
                    (function(){
                        setTimeout(function(){
                            window.location.href = 'pending_login_waiting.php';
                        }, 500);
                    })();
                </script>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-check-circle flex-shrink-0 mt-0.5"></i>
                        <div><?php echo $success; ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- LOGIN FORM -->
                <?php if (!$is_signup): ?>
                <div class="auth-form-card bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="login">
                        
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2.5">Username, Email Address, or Phone Number</label>
                            <input type="text" name="identifier" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="Username, email, or 09123456789">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2.5">Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" name="password" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="••••••••">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword(this)" title="Show/hide password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" style="background-color: #454B1B;" class="w-full text-white font-semibold py-3 rounded-lg hover:opacity-95 transition-all shadow-md shadow-[#454B1B]/20">
                            Sign In
                        </button>

                        <div class="text-center text-sm text-slate-600 border-t border-slate-100 pt-4">
                            Don't have an account? 
                            <a href="login.php?signup=1" style="color: #454B1B;" class="font-semibold hover:underline">
                                Sign up here
                            </a>
                        </div>
                    </form>


                </div>

                <!-- SIGNUP FORM -->
                <?php else: ?>
                <div class="auth-form-card bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="signup">
                        
                        <!-- Full Name Field (NEW SCHEMA) -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2.5">Full Name *</label>
                            <input type="text" name="full_name" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="Juan Dela Cruz">
                            <p class="text-xs text-slate-500 mt-1">Enter your complete name as you prefer to be called</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2.5">Date of Birth *</label>
                            <input type="date" name="date_of_birth" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" title="Select your date of birth">
                        </div>

                        <div id="sexFieldContainer" class="transition-all duration-300">
                            <label class="block text-sm font-semibold text-slate-900 mb-2.5">Biological Sex *</label>
                            <select id="sexSelect" name="sex" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all bg-white">
                                <option value="">Select sex</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2.5">Select Your Role *</label>
                            <select id="roleSelect" name="user_role" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all appearance-none bg-white" style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 20 20%22%3E%3Cpath stroke=%22%236b7280%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%221.5%22 d=%22M6 8l4 4 4-4%22/%3E%3C/svg%3E'); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                                <option value="">-- Choose Your Role --</option>
                                <option value="resident">👤 Resident (Patient)</option>
                                <option value="health_worker">👨‍⚕️ Barangay Health Worker (BHW)</option>
                            </select>
                            <p class="text-xs text-slate-500 mt-1">You cannot change this after registration. Choose carefully.</p>
                        </div>

                        <!-- CONDITIONAL ADDRESS FIELD: Hidden for BHW & Admin -->
                        <div id="addressFieldContainer" class="transition-all duration-300 hidden space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Street Address -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-900 mb-2.5">Street Address *</label>
                                    <input type="text" id="streetAddress" name="street_address" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="e.g., 67, San Antonio, Spurs">
                                </div>
                                
                                <!-- Barangay -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-900 mb-2.5">Barangay *</label>
                                    <input type="text" id="barangay" name="barangay" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="e.g., San Antonio">
                                </div>
                                
                                <!-- City -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-900 mb-2.5">City *</label>
                                    <input type="text" id="city" name="city" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="e.g., Spurs">
                                </div>
                                
                                <!-- Province / Postal Code -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-900 mb-2.5">Province / Postal Code *</label>
                                    <input type="text" id="postalCode" name="postal_code" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="e.g., 2004">
                                </div>
                            </div>
                            <!-- Hidden field for backward compatibility -->
                            <input type="hidden" id="addressField" name="address">
                        </div>

                        <input type="hidden" name="signup_mode" id="signupModeInput" value="email">

                        <div id="emailFieldGroup">
                            <label class="block text-sm font-semibold text-slate-900 mb-2.5">Email Address</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="your.email@example.com">
                            <button type="button" id="emailToggleLink" class="mt-2 text-sm font-semibold text-[#454B1B] hover:underline transition-colors">
                                Don't have an email?
                            </button>
                        </div>

                        <div id="phoneFieldGroup" class="mt-4">
                            <label class="block text-sm font-semibold text-slate-900 mb-2.5">Phone Number</label>
                            <input id="phoneInput" type="tel" name="phone" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="+63 912 345 6789">
                            <p id="phoneOnlyMessage" class="mt-2 text-sm text-slate-500 hidden">Signup with phone number instead</p>
                            <button type="button" id="phoneToEmailLink" class="mt-2 text-sm font-semibold text-[#454B1B] hover:underline hidden">Signup with email?</button>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2.5">Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" name="password" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="••••••••" minlength="6">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword(this)" title="Show/hide password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Minimum 6 characters</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2.5">Confirm Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" name="password_confirm" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" placeholder="••••••••" minlength="6">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword(this)" title="Show/hide password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <label class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-100">
                            <input type="checkbox" required class="w-4 h-4 rounded" style="accent-color: #454B1B;">
                            <span class="text-sm text-slate-700">I agree to the <a href="compliance.php" style="color: #454B1B;" class="font-semibold hover:underline">Privacy Policy & Terms of Service</a></span>
                        </label>

                        <button type="submit" style="background-color: #454B1B;" class="w-full text-white font-semibold py-3 rounded-lg hover:opacity-95 transition-all shadow-md shadow-[#454B1B]/20">
                            Create Account
                        </button>

                        <div class="text-center text-sm text-slate-600 border-t border-slate-100 pt-4">
                            Already have an account? 
                            <a href="login.php" style="color: #454B1B;" class="font-semibold hover:underline">
                                Login here
                            </a>
                        </div>
                    </form>

                    <!-- JAVASCRIPT: Conditional Address Field Logic -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const roleSelect = document.getElementById('roleSelect');
                            const addressFieldContainer = document.getElementById('addressFieldContainer');
                            const addressField = document.getElementById('addressField');
                            const streetAddress = document.getElementById('streetAddress');
                            const barangay = document.getElementById('barangay');
                            const city = document.getElementById('city');
                            const postalCode = document.getElementById('postalCode');
                            const form = document.querySelector('form');
                            const emailFieldGroup = document.getElementById('emailFieldGroup');
                            const phoneFieldGroup = document.getElementById('phoneFieldGroup');
                            const emailInput = document.querySelector('#emailFieldGroup input[name="email"]');
                            const phoneInput = document.getElementById('phoneInput');
                            const phoneOnlyMessage = document.getElementById('phoneOnlyMessage');
                            const phoneToEmailLink = document.getElementById('phoneToEmailLink');
                            const signupModeInput = document.getElementById('signupModeInput');
                            const emailToggleLink = document.getElementById('emailToggleLink');
                            let usePhoneOnly = signupModeInput.value === 'phone_only';

                            function setSignupMode(phoneOnly) {
                                usePhoneOnly = phoneOnly;
                                signupModeInput.value = phoneOnly ? 'phone_only' : 'email';
                                emailFieldGroup.classList.toggle('hidden', phoneOnly);
                                phoneFieldGroup.classList.toggle('hidden', false);
                                emailToggleLink.textContent = phoneOnly ? 'Use email address instead' : "Don't have an email?";
                                emailToggleLink.setAttribute('aria-expanded', phoneOnly ? 'true' : 'false');
                                emailInput.required = !phoneOnly;
                                phoneInput.required = phoneOnly;
                                phoneOnlyMessage.classList.toggle('hidden', !phoneOnly);
                                phoneOnlyMessage.textContent = 'Signup with phone number instead';
                                phoneToEmailLink.classList.toggle('hidden', !phoneOnly);
                                if (!phoneOnly) {
                                    phoneInput.value = '';
                                }
                            }

                            // Function to toggle address field visibility and required attribute
                            function toggleAddressField() {
                                const selectedRole = roleSelect.value;

                                if (selectedRole === 'resident') {
                                    // SHOW address field for Resident
                                    addressFieldContainer.classList.remove('hidden');
                                    streetAddress.setAttribute('required', 'required');
                                    barangay.setAttribute('required', 'required');
                                    city.setAttribute('required', 'required');
                                    postalCode.setAttribute('required', 'required');
                                } else {
                                    // HIDE address field for non-residents
                                    addressFieldContainer.classList.add('hidden');
                                    streetAddress.removeAttribute('required');
                                    barangay.removeAttribute('required');
                                    city.removeAttribute('required');
                                    postalCode.removeAttribute('required');
                                    streetAddress.value = '';
                                    barangay.value = '';
                                    city.value = '';
                                    postalCode.value = '';
                                }

                                toggleSexField(selectedRole !== 'health_worker');
                            }

                            function toggleSexField(show) {
                                const sexContainer = document.getElementById('sexFieldContainer');
                                const sexSelect = document.getElementById('sexSelect');
                                if (show) {
                                    sexContainer.classList.remove('hidden');
                                    sexSelect.setAttribute('required', 'required');
                                } else {
                                    sexContainer.classList.add('hidden');
                                    sexSelect.removeAttribute('required');
                                    sexSelect.value = '';
                                }
                            }

                            // Function to combine address fields before form submission
                            function combineAddressFields() {
                                const combined = [streetAddress.value, barangay.value, city.value, postalCode.value]
                                    .map(val => val.trim())
                                    .filter(val => val)
                                    .join(', ');
                                addressField.value = combined;
                            }

                            // Add event listener to role select
                            roleSelect.addEventListener('change', toggleAddressField);
                            emailToggleLink.addEventListener('click', function (event) {
                                event.preventDefault();
                                setSignupMode(!usePhoneOnly);
                            });
                            phoneToEmailLink.addEventListener('click', function (event) {
                                event.preventDefault();
                                setSignupMode(false);
                            });

                            // Combine address fields on form submit
                            if (form) {
                                form.addEventListener('submit', function () {
                                    combineAddressFields();
                                    if (usePhoneOnly) {
                                        emailInput.value = '';
                                    }
                                });
                            }

                            // Call function on page load in case form is pre-populated
                            toggleAddressField();
                            setSignupMode(usePhoneOnly);
                        });
                    </script>
                </div>
                <?php endif; ?>

                <!-- Footer Info -->
                <div class="mt-6 text-center text-xs text-slate-500">
                    <p class="flex items-center justify-center gap-1">
                        <i class="fa-solid fa-lock" style="color: #454B1B;"></i>
                        Your data is encrypted and secure
                    </p>
                </div>

            </div>
        </section>
    </main>

    <!-- Success Login Modal -->
    <div id="successModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="modal-title">Successfully Logged In</h2>
            <p class="modal-description">
                Welcome back, <strong><?php echo htmlspecialchars($success_user_name); ?></strong>!<br>
                Redirecting to your dashboard...
            </p>
            <button class="modal-btn" onclick="redirectToDashboard()">Continue</button>
        </div>
    </div>

    <script>
        // Show success modal if login was successful
        <?php if ($show_success_modal): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('successModal');
                modal.classList.add('show');
                
                // Auto-redirect after 2 seconds
                setTimeout(function() {
                    redirectToDashboard();
                }, 2000);
            });
        <?php endif; ?>

        function redirectToDashboard() {
            window.location.href = '<?php echo htmlspecialchars($success_redirect_url); ?>';
        }
    </script>

</body>
</html>
