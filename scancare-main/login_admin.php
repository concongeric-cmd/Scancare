<?php














if (function_exists('session_cache_limiter')) {
    session_cache_limiter('nocache');
}

require_once 'db_config.php';
session_start();




header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');







if (isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true) {
    header('Location: administrator_panel.php');
    exit;
}


$error = '';
$success = '';


if (isset($_GET['logged_out'])) {
    $success = 'You have been successfully logged out.';
}








if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        
        
        
        
        
        $username_input = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        
        
        
        if (empty($username_input) || empty($password)) {
            $error = 'Please enter both username and password.';
            error_log("ADMIN LOGIN ATTEMPT: Empty credentials from IP {$_SERVER['REMOTE_ADDR']}");
        } else {
            try {
                
                
                
                $pdo = getDatabaseConnection();
                
                if ($pdo === false) {
                    $error = 'Database connection error. Please try again later.';
                    error_log("ADMIN LOGIN ERROR: Database connection failed for username: {$username_input}");
                } else {
                    
                    
                    
                    
                    
                    $query = "
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
                        WHERE (username = ? OR email = ?) AND is_active = 1
                        LIMIT 1
                    ";
                    
                    $stmt = $pdo->prepare($query);
                    if (!$stmt) {
                        throw new Exception("Query preparation failed: " . implode(", ", $pdo->errorInfo()));
                    }
                    
                    if (!$stmt->execute([$username_input, $username_input])) {
                        throw new Exception("Query execution failed: " . implode(", ", $stmt->errorInfo()));
                    }
                    
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    
                    
                    
                    if ($admin && password_verify($password, $admin['password_hash'])) {
                        
                        
                        
                        
                        
                        
                        $_SESSION = [];
                        
                        
                        
                        
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            session_regenerate_id(true);
                        }
                        
                        
                        
                        
                        $_SESSION['admin_auth'] = true;
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['admin_name'] = $admin['full_name'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        
                        
                        
                        
                        $update_query = "UPDATE system_admins SET last_login = NOW() WHERE admin_id = ?";
                        $update_stmt = $pdo->prepare($update_query);
                        if ($update_stmt) {
                            @$update_stmt->execute([$admin['admin_id']]);
                        }
                        logActivity($pdo, $admin['admin_id'], 'admin', 'login', 'Administrator logged in: ' . $admin['username']);
                        
                        
                        
                        
                        error_log("ADMIN LOGIN SUCCESS: Admin '{$admin['username']}' (ID: {$admin['admin_id']}, Email: {$admin['email']}, Role: {$admin['role']}) logged in from IP {$_SERVER['REMOTE_ADDR']}");
                        
                        
                        
                        
                        header('Location: administrator_panel.php');
                        exit;
                    } else {
                        
                        $error = 'Invalid username or password.';
                        error_log("ADMIN LOGIN FAILED: Invalid credentials for input '{$username_input}' from IP {$_SERVER['REMOTE_ADDR']}");
                    }
                }
            } catch (Exception $e) {
                $error = 'An error occurred during login. Please try again.';
                error_log("ADMIN LOGIN EXCEPTION: " . $e->getMessage() . " for username: {$username_input}");
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
        
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="font-body">

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            
            <!-- Header with Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 mb-6">
                    <img src="images/SClogo.png" alt="ScanCare Logo" class="h-12 w-12 object-contain">
                    <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-2xl font-extrabold tracking-tight text-white">
                        Scan<span style="color: #454B1B;">Care</span>
                    </span>
                </div>
                <h1 class="text-3xl font-heading font-bold text-white mb-2">Administrator Login</h1>
                <p class="text-slate-400">System Administration Panel</p>
            </div>

            <!-- Login Form Card -->
            <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl p-8 mb-6">
                
                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-950/30 border border-red-900/50 rounded-lg">
                        <p class="text-red-400 text-sm flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Success Message -->
                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-emerald-950/30 border border-emerald-900/50 rounded-lg">
                        <p class="text-emerald-400 text-sm flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="action" value="login">
                    
                    <!-- Username Input -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">
                            Username
                        </label>
                        <input type="text" 
                               name="username" 
                               placeholder="Enter your admin username"
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition"
                               required
                               autocomplete="username">
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">
                            Password
                        </label>
                        <input type="password" 
                               name="password" 
                               placeholder="Enter your password"
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition"
                               required
                               autocomplete="current-password">
                    </div>

                    <!-- Remember Device Checkbox -->
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-600 bg-slate-700 checked:bg-[#454B1B]">
                        <span class="text-sm text-slate-400">Remember this device</span>
                    </label>

                    <!-- Submit Button -->
                    <button type="submit" 
                            style="background-color: #454B1B;" 
                            class="w-full hover:opacity-95 text-white py-3 rounded-lg font-semibold transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-sign-in-alt"></i> Sign In to Administrator Panel
                    </button>
                </form>
            </div>

            <!-- Security Info Card -->
            <div class="p-4 bg-slate-700/50 border border-slate-600 rounded-lg">
                <p class="text-xs text-slate-400 flex items-start gap-2">
                    <i class="fas fa-shield-alt mt-1 flex-shrink-0"></i>
                    <span>This is a restricted area. All login attempts are logged for security purposes. Unauthorized access attempts will be monitored and reported.</span>
                </p>
            </div>

            <!-- Back to Main Login Link -->
            <div class="text-center mt-8">
                <p class="text-slate-400 text-sm">
                    Not an administrator? 
                    <a href="login.php" class="text-[#454B1B] hover:text-[#5a6333] font-semibold transition">
                        Return to main login
                    </a>
                </p>
            </div>

        </div>
    </div>

</body>
</html>

