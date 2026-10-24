<?php














require_once 'db_config.php';
session_start();








if (isset($_SESSION['resident_id']) && $_SESSION['user_type'] === 'resident') {
    header('Location: dashboard.php');
    exit;
} elseif (isset($_SESSION['worker_id']) && $_SESSION['user_type'] === 'health_worker') {
    header('Location: dashboard_bhw.php');
    exit;
}





if (isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin') {
    if (file_exists(__DIR__ . '/administrator_panel.php')) {
        header('Location: administrator_panel.php');
        exit;
    }
}

$error = '';
$success = '';
$admin_redirect_source = isset($_GET['redirect']) ? $_GET['redirect'] : null;


if ($admin_redirect_source === 'public') {
    $error = 'Admin accounts are not accessible through the public login form. Use this secure portal instead.';
}

if (isset($_GET['logged_out'])) {
    $success = 'You have been successfully logged out.';
}

if (isset($_GET['session_expired'])) {
    $error = 'Your session has expired. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'admin_login') {
        
        
        
        
        
        
        
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            try {
                $pdo = getDatabaseConnection();
                
                if ($pdo === false) {
                    $error = 'Database connection error. Please try again later.';
                } else {
                    $username_lower = strtolower($username);
                    
                    
                    
                    
                    
                    
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            admin_id,
                            username,
                            email,
                            password_hash,
                            full_name,
                            role,
                            is_active
                        FROM SYSTEM_ADMINS 
                        WHERE (LOWER(username) = ? OR LOWER(email) = ?) AND is_active = 1
                        LIMIT 1
                    ");
                    $stmt->execute([$username_lower, $username_lower]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($admin && password_verify($password, $admin['password_hash'])) {
                        
                        
                        
                        
                        
                        
                        
                        
                        unset($_SESSION['resident_id']);
                        unset($_SESSION['worker_id']);
                        unset($_SESSION['user_role']);
                        
                        
                        $_SESSION['admin_auth'] = true;
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['admin_name'] = $admin['full_name'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['user_type'] = 'admin';
                        $_SESSION['user_id'] = $admin['admin_id'];
                        $_SESSION['user_name'] = $admin['full_name'];
                        $_SESSION['email'] = $admin['email'];
                        $_SESSION['login_time'] = time();
                        
                        error_log("SECURE ADMIN LOGIN: Admin ID {$admin['admin_id']} (username: {$admin['username']}) logged in successfully from {$_SERVER['REMOTE_ADDR']}");
                        
                        
                        header('Location: administrator_panel.php');
                        exit;
                    } else {
                        
                        
                        
                        
                        
                        
                        
                        error_log("ADMIN LOGIN FAILED: Username {$username_lower} from {$_SERVER['REMOTE_ADDR']} - Invalid credentials or not an admin account");
                        
                        $error = 'Invalid username or password. Only system administrator accounts are allowed.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Authentication error. Please try again.';
                error_log("ADMIN LOGIN ERROR: " . $e->getMessage() . " from {$_SERVER['REMOTE_ADDR']}");
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        .sc-nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 80rem;
            margin: 0 auto;
            width: 100%;
        }

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

                display: none !important;
            }
        }

        .nav-link {
            position: relative;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            color: #475569;
            text-decoration: none;
        }

        .nav-link:hover {
            color: #A3E635;
            background-color: rgba(163, 230, 53, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-b from-slate-900 via-blue-900 to-slate-900 text-slate-200 font-body antialiased min-h-screen selection:bg-blue-600 selection:text-white">

    <nav class="border-b border-slate-700 sticky top-0 w-full bg-slate-900/95 backdrop-blur-md z-50 shadow-md">
        <div class="sc-nav-container px-6 lg:px-12 py-4 h-20 flex items-center justify-between">
            <div class="flex items-center gap-1 flex-shrink-0">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-12 w-12 object-contain">
                <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-xl font-extrabold tracking-tight text-white">
                    Scan<span style="color: #A3E635;">Care</span>
                </span>
            </div>

            <div class="nav-links-desktop">
                <span class="text-blue-400 font-semibold text-sm">System Administration Panel</span>
            </div>

            <button id="toggleBtn" class="nav-hamburger-btn" aria-label="Toggle mobile menu">
                <i class="fa-solid fa-bars" style="color: #cbd5e1;"></i>
            </button>
        </div>

        <div id="mobileMenuPanel" style="display: none;" class="border-t border-slate-700 bg-slate-900 flex-col px-6 py-4">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <p style="color: #60A5FA; font-weight: 700; font-size: 13px;">System Administration Panel</p>
            </div>
        </div>
    </nav>

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
    </script>

    <main class="w-full">
        <section class="min-h-[calc(100vh-5rem)] flex items-center py-12 relative overflow-hidden">
            <div class="absolute inset-0 z-0">
                <div class="absolute top-10 right-10 w-40 h-40 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10"></div>
                <div class="absolute bottom-10 left-10 w-40 h-40 bg-slate-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10"></div>
            </div>
            
            <div class="max-w-md w-full mx-auto px-4 relative z-10">
                
                <div class="text-center mb-8">
                    <div class="mb-6 flex justify-center">
                        <img src="images/SClogo.png" alt="ScanCare Logo" class="h-20 w-20 object-contain drop-shadow-lg">
                    </div>
                    <h1 class="text-4xl font-heading font-extrabold text-white mb-2">
                        Administrator Login
                    </h1>
                    <p class="text-sm text-blue-300 mb-8">
                        System Administration Panel
                    </p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="mb-6 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 rounded-lg text-sm">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-exclamation-circle flex-shrink-0 mt-0.5"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                <div class="mb-6 bg-emerald-900/30 border border-emerald-700 text-emerald-300 px-4 py-3 rounded-lg text-sm">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-check-circle flex-shrink-0 mt-0.5"></i>
                        <div><?php echo $success; ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-slate-800/50 border border-slate-700 rounded-lg shadow-lg p-8 backdrop-blur-sm">
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="admin_login">
                        
                        <div>
                            <label class="block text-sm font-semibold text-slate-200 mb-2.5">
                                Username
                            </label>
                            <input type="text" name="username" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Enter your admin username">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-200 mb-2.5">
                                Password
                            </label>
                            <input type="password" name="password" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Enter your password">
                        </div>

                        <button type="submit" style="background-color: #7C6E1F;" class="w-full text-white font-semibold py-3 rounded-lg hover:opacity-90 transition-all shadow-md">
                            <i class="fa-solid fa-sign-in-alt mr-2"></i>Sign In to Administrator Panel
                        </button>
                    </form>
                </div>

                <div class="mt-8 bg-blue-900/20 border border-blue-700 rounded-lg p-6 text-center">
                    <p class="text-sm text-blue-300">
                        <i class="fa-solid fa-info-circle mr-2"></i>
                        <strong>This is a restricted area.</strong> All login attempts are logged for security purposes. Unauthorized access attempts will be monitored and reported.
                    </p>
                </div>

                <div class="mt-6 text-center text-sm text-slate-400">
                    <p>Not an administrator? <a href="login.php" style="color: #A3E635;" class="font-semibold hover:text-yellow-300 transition">return to main login</a></p>
                </div>

            </div>
        </section>
    </main>

</body>
</html>

