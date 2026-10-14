<?php



if (session_status() !== PHP_SESSION_NONE) {
    session_destroy();
}

session_start();

require_once 'db_config.php';





if (isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true) {
    header('Location: administrator_panel.php');
    exit;
}


$error = '';
$success = '';





if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        
        $credential = trim($_POST['credential'] ?? '');  
        $password = trim($_POST['password'] ?? '');
        
        
        if (empty($credential) || empty($password)) {
            $error = 'Please enter your username/email and password.';
        } else {
            try {
                
                $pdo = getDatabaseConnection();
                
                if ($pdo === false) {
                    $error = 'Database connection error. Please try again later.';
                } else {
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            admin_id,
                            username,
                            email,
                            password_hash,
                            full_name,
                            role,
                            is_active,
                            last_login
                        FROM system_admins 
                        WHERE (username = ? OR email = ?) 
                        AND is_active = 1
                        LIMIT 1
                    ");
                    
                    
                    $stmt->execute([$credential, $credential]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    
                    if ($admin && password_verify($password, $admin['password_hash'])) {
                        
                        
                        
                        
                        
                        session_regenerate_id(true);
                        
                        
                        $_SESSION['admin_auth'] = true;
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['admin_name'] = $admin['full_name'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['user_type'] = 'admin';  
                        
                        
                        $update_stmt = $pdo->prepare("
                            UPDATE system_admins 
                            SET last_login = NOW() 
                            WHERE admin_id = ?
                        ");
                        $update_stmt->execute([$admin['admin_id']]);
                        
                        
                        error_log("ADMIN LOGIN SUCCESS: Admin '{$admin['username']}' (ID: {$admin['admin_id']}) at " . date('Y-m-d H:i:s'));
                        
                        
                        header('Location: administrator_panel.php');
                        exit;
                    } else {
                        
                        $error = 'Invalid credentials or inactive account.';
                        
                        
                        error_log("ADMIN LOGIN FAILED: Invalid credentials for '{$credential}' from IP {$_SERVER['REMOTE_ADDR']}");
                    }
                }
            } catch (Exception $e) {
                $error = 'An error occurred during login. Please try again.';
                error_log("ADMIN LOGIN ERROR: " . $e->getMessage());
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Login — ScanCare</title>
    <script src="https:
    <link rel="stylesheet" href="https:
    <style>
        @import url('https:
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="bg-slate-900 rounded-xl shadow-2xl border border-slate-700 p-8">
                
                <!
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-white mb-2">ScanCare</h1>
                    <p class="text-slate-400">Administrator Login</p>
                </div>

                <!
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-900/20 border border-red-500/50 rounded-lg">
                        <p class="text-red-400 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <!
                <form method="POST" action="" class="space-y-5">
                    <input type="hidden" name="action" value="login">
                    
                    <!
                    <div>
                        <label for="credential" class="block text-sm font-medium text-slate-300 mb-2">
                            Username or Email
                        </label>
                        <input 
                            type="text" 
                            id="credential" 
                            name="credential" 
                            placeholder="admin@example.com"
                            class="w-full px-4 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            required
                        >
                    </div>

                    <!
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-300 mb-2">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••"
                            class="w-full px-4 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            required
                        >
                    </div>

                    <!
                    <button 
                        type="submit" 
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 mt-6"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                    </button>
                </form>

            </div>
        </div>
    </div>
</body>
</html>

