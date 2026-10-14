<?php



function getSessionClientIp() {
    $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($forwardedFor !== '') {
        foreach (explode(',', $forwardedFor) as $forwardedIp) {
            $forwardedIp = trim($forwardedIp);
            if (filter_var($forwardedIp, FILTER_VALIDATE_IP)) {
                return $forwardedIp;
            }
        }
    }

    $cloudflareIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
    if (filter_var($cloudflareIp, FILTER_VALIDATE_IP)) {
        return $cloudflareIp;
    }

    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function configureSessionCookie() {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $isHttps = strtolower((string) ($_SERVER['HTTPS'] ?? '')) === 'on'
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host = preg_replace('/:\d+$/', '', $host);
    $domain = (filter_var($host, FILTER_VALIDATE_IP) || preg_match('/^[A-Za-z0-9.-]+$/', $host))
        && strpos($host, '.') !== false
        ? $host
        : '';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $domain,
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

configureSessionCookie();

function validateSessionClientIp() {
    if (!isLoggedIn()) {
        return true;
    }

    $currentIp = getSessionClientIp();
    if ($currentIp === '') {
        return true;
    }

    if (empty($_SESSION['client_ip'])) {
        $_SESSION['client_ip'] = $currentIp;
        return true;
    }

    $_SESSION['client_ip'] = $currentIp;
    return true;
}


function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}


function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    return $_SESSION['user_role'] === $required_role;
}


function requireLogin() {
    if (!isLoggedIn() || !validateSessionClientIp()) {
        header('Location: login.php');
        exit;
    }
}


function requireRole($required_role, $fallback_page = 'login.php') {
    requireLogin();
    
    if (!hasRole($required_role)) {
        
        if ($_SESSION['user_role'] === 'resident') {
            header('Location: dashboard.php');
        } elseif ($_SESSION['user_role'] === 'bhw') {
            header('Location: bhw_dashboard.php');
        } elseif ($_SESSION['user_role'] === 'admin') {
            header('Location: admin_dashboard.php');
        } else {
            header('Location: ' . $fallback_page);
        }
        exit;
    }
}


function getUserId() {
    return $_SESSION['user_id'] ?? null;
}


function getUserName() {
    return $_SESSION['user_name'] ?? null;
}


function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}


function getUserEmail() {
    return $_SESSION['email'] ?? null;
}


function getUserPhone() {
    return $_SESSION['phone'] ?? null;
}


function logout($redirect_page = 'login.php') {
    
    $_SESSION = array();
    
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    
    session_destroy();
    
    
    header('Location: ' . $redirect_page);
    exit;
}


function getRoleDisplayName($role) {
    $role_names = [
        'resident' => 'Resident (Patient)',
        'bhw' => 'Barangay Health Worker',
        'admin' => 'System Administrator'
    ];
    
    return $role_names[$role] ?? 'Unknown Role';
}


function checkSessionTimeout($timeout = 3600) {
    if (!isLoggedIn()) {
        return;
    }
    
    $current_time = time();
    $session_start_time = $_SESSION['login_time'] ?? $current_time;
    
    if (($current_time - $session_start_time) > $timeout) {
        logout('login.php?session_expired=1');
    }
    
    
    $_SESSION['login_time'] = $current_time;
}


function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}


function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>

