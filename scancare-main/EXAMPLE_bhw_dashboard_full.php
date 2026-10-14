<?php



session_start();
require_once 'db_config.php';





if (!isset($_SESSION['worker_id']) || $_SESSION['user_type'] !== 'health_worker') {
    header('Location: login.php');
    exit;
}


$worker_id = $_SESSION['worker_id'];
$worker_name = $_SESSION['user_name'] ?? 'Health Worker';
$worker_role = $_SESSION['worker_role'] ?? 'bhw';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BHW Dashboard — ScanCare</title>
    <script src="https:
    <link rel="stylesheet" href="https:
</head>
<body class="bg-slate-50 dark:bg-slate-900">
    
    <!
         HEADER WITH AUTH TOGGLE
         =================================================================== 
    <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            
            <!
            <a href="index.php" class="text-2xl font-bold text-emerald-600 flex items-center gap-2">
                <i class="fas fa-hospital"></i>ScanCare
            </a>
            
            <!
            <nav class="hidden md:flex gap-8">
                <a href="dashboard_bhw.php" class="text-slate-700 dark:text-slate-300 hover:text-emerald-600 font-medium">
                    Dashboard
                </a>
                <a href="residents.php" class="text-slate-700 dark:text-slate-300 hover:text-emerald-600 font-medium">
                    Residents
                </a>
                <a href="medicines.php" class="text-slate-700 dark:text-slate-300 hover:text-emerald-600 font-medium">
                    Medicines
                </a>
            </nav>
            
            <!
            <div>
                <?php include 'components/header_auth_toggle.php'; ?>
            </div>
        </div>
    </header>

    <!
         MAIN CONTENT
         =================================================================== 
    <main class="max-w-7xl mx-auto px-4 py-12">
        
        <!
        <div class="mb-12">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-2">
                        Welcome, <?php echo htmlspecialchars($worker_name); ?>! 👋
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">
                        Health Worker Dashboard • <?php echo ucfirst($worker_role); ?> Operations
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Last Login</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white">
                        <?php echo date('M d, Y h:i A', $_SESSION['login_time'] ?? time()); ?>
                    </p>
                </div>
            </div>
        </div>

        <!
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
            
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
                <p class="text-slate-600 dark:text-slate-400 text-sm font-semibold mb-2">Worker ID</p>
                <p class="text-2xl font-bold text-emerald-600"><?php echo htmlspecialchars($worker_id); ?></p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
                <p class="text-slate-600 dark:text-slate-400 text-sm font-semibold mb-2">Role</p>
                <p class="text-lg font-bold text-slate-900 dark:text-white capitalize"><?php echo htmlspecialchars($worker_role); ?></p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
                <p class="text-slate-600 dark:text-slate-400 text-sm font-semibold mb-2">System Status</p>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-green-600 dark:text-green-400 text-sm font-medium">Online</span>
                </div>
            </div>

            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-6">
                <p class="text-emerald-700 dark:text-emerald-400 text-sm font-semibold mb-2">Time</p>
                <p class="text-2xl font-bold text-emerald-600" id="current-time">
                    <?php echo date('H:i'); ?>
                </p>
            </div>

        </div>

        <!
             FEATURE 4: APPOINTMENT METRICS & MANAGEMENT
             =================================================================== 
        
        <?php 
            
            
            
            
            
            require_once 'appointment_system.php';
            include 'components/bhw_appointment_metrics.php'; 
        ?>

        <!
             FEATURE 5: MEDICINE INVENTORY MANAGEMENT
             =================================================================== 

        <?php 
            
            
            
            
            
            
            require_once 'medicine_inventory_system.php';
            include 'components/bhw_medicine_management.php'; 
        ?>

    </main>

    <!
         FOOTER
         =================================================================== 
    <footer class="w-full bg-slate-900 text-white py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-6 lg:px-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div><h3 class="font-heading font-bold text-lg mb-4">ScanCare</h3><p class="text-slate-400 text-sm">Healthcare made simple for Barangay Gutad</p></div>
                <div><h4 class="font-semibold mb-4">Services</h4><ul class="space-y-2 text-sm text-slate-400"><li><a href="appointments.php" class="hover:text-white transition">Appointments</a></li><li><a href="system-goals.php" class="hover:text-white transition">System Goals</a></li><li><a href="service-areas.php" class="hover:text-white transition">Service Areas</a></li></ul></div>
                <div><h4 class="font-semibold mb-4">Legal</h4><ul class="space-y-2 text-sm text-slate-400"><li><a href="compliance.php" class="hover:text-white transition">Privacy Policy & Terms of Service</a></li></ul></div>
                <div><h4 class="font-semibold mb-4">Contact</h4><p class="text-slate-400 text-sm">Floridablanca RHU II - GUTAD<br>Email: floridablancarhu2@gmail.com<br>Contact: 0948 056 6872</p></div>
            </div>
            <div class="border-t border-slate-800 pt-8 text-center text-slate-400 text-sm"><p>&copy; 2026 ScanCare. All rights reserved. Your data is encrypted and secure.</p></div>
        </div>
    </footer>

    <!
    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('current-time').textContent = hours + ':' + minutes;
        }
        setInterval(updateClock, 1000);
    </script>

</body>
</html>

