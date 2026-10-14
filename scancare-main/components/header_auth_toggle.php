<?php



require_once __DIR__ . '/../app_config.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}





$is_logged_in = false;
$user_full_name = '';
$user_type = '';


if (isset($_SESSION['resident_id']) && $_SESSION['user_type'] === 'resident') {
    $is_logged_in = true;
    $user_full_name = $_SESSION['user_name'] ?? 'Resident';
    $user_type = 'resident';
} 
elseif (isset($_SESSION['worker_id']) && $_SESSION['user_type'] === 'health_worker') {
    $is_logged_in = true;
    $user_full_name = $_SESSION['user_name'] ?? 'Health Worker';
    $user_type = 'health_worker';
}
elseif (isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true) {
    $is_logged_in = true;
    $user_full_name = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Administrator';
    $user_type = 'admin';
}

?>

<!
     AUTHENTICATION STATE TOGGLE - RIGHT SIDE OF HEADER
     ===================================================================== 

<div class="flex items-center gap-3">
    
    <!
         CONDITION A: GUEST USER (Not logged in)
         Show "Login / Signup" button
         =================================================================== 
    <?php if (!$is_logged_in): ?>
        
        <div class="flex items-center gap-2">
            <!
            <a href="login.php?switch_account=1" 
               class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-medium transition rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </a>
            
            <!
            <a href="signup.php" 
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition flex items-center gap-2">
                <i class="fas fa-user-plus"></i>Sign Up
            </a>
        </div>

    <!
         CONDITION B: LOGGED-IN USER (Any type)
         Show "User Name + Logout" button
         =================================================================== 
    <?php else: ?>
        
        <div class="flex items-center gap-4">
            <!
            <span class="text-slate-700 dark:text-slate-300 font-medium hidden sm:inline-block">
                <i class="fas fa-user-circle mr-2 text-emerald-600"></i><?php echo htmlspecialchars($user_full_name); ?>
            </span>
            
            <!
            <a href="logout.php" 
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition flex items-center gap-2">
                <i class="fas fa-sign-out-alt"></i><span class="hidden sm:inline">Log Out</span>
            </a>

            <script src="<?php echo htmlspecialchars(appPath('login-notification-system.js'), ENT_QUOTES, 'UTF-8'); ?>?v=13"></script>

            <!
            <div class="sm:hidden">
                <details class="relative">
                    <summary class="list-none cursor-pointer px-2 py-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                        <i class="fas fa-bars text-xl text-slate-700 dark:text-slate-300"></i>
                    </summary>
                    <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 z-50">
                        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                            <p class="text-slate-900 dark:text-white font-semibold"><?php echo htmlspecialchars($user_full_name); ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 capitalize"><?php echo htmlspecialchars($user_type); ?></p>
                        </div>
                        <a href="logout.php" 
                           class="block w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 font-medium transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>Log Out
                        </a>
                    </div>
                </details>
            </div>
        </div>

    <?php endif; ?>

</div>

<!
     RESPONSIVE STYLES (Optional - for custom adjustments)
     ===================================================================== 
<style>
    
    details > summary::-webkit-details-marker {
        display: none;
    }

    
    a.transition {
        transition: all 0.2s ease-in-out;
    }

    
    @media (prefers-color-scheme: dark) {
        .dark\:text-slate-300 { color: #cbd5e1; }
        .dark\:bg-slate-700 { background-color: #334155; }
        .dark\:text-white { color: #ffffff; }
    }
</style>

<!
     USAGE IN MAIN HEADER TEMPLATE
     ===================================================================== 
<!
Example integration in your main header:

<header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <!
        <a href="index.php" class="text-2xl font-bold text-emerald-600">ScanCare</a>
        
        <!
        <nav class="hidden md:flex gap-8">
            <a href="index.php">Home</a>
            <a href="about-us.php">About</a>
            <a href="services.php">Services</a>
        </nav>
        
        <!
        <div>
            <?php include 'components/header_auth_toggle.php'; ?>
        </div>
    </div>
</header>


