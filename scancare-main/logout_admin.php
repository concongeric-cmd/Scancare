<?php







session_start();


require_once __DIR__ . '/db_config.php';



if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_username'])) {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo !== false) {
            logActivity($pdo, $_SESSION['admin_id'], 'admin', 'logout', 'Administrator logged out: ' . $_SESSION['admin_username']);
        }
    } catch (Throwable $logoutError) {
        error_log('ADMIN LOGOUT ACTIVITY ERROR: ' . $logoutError->getMessage());
    }
    error_log("ADMIN LOGOUT: Admin '{$_SESSION['admin_username']}' (ID: {$_SESSION['admin_id']}) logged out from IP {$_SERVER['REMOTE_ADDR']}");
}





$_SESSION = [];





session_destroy();






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





setcookie('admin_auth', '', time() - 42000, '/');
setcookie('admin_session', '', time() - 42000, '/');





ob_clean();





header('Location: login_admin.php?logged_out=1', true, 302);
exit;

?>

