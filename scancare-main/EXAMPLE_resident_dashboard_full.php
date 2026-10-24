<?php



session_start();
require_once 'db_config.php';





if (!isset($_SESSION['resident_id']) || $_SESSION['user_type'] !== 'resident') {
    header('Location: login.php');
    exit;
}


$resident_id = $_SESSION['resident_id'];
$resident_name = $_SESSION['user_name'] ?? 'Resident';
$resident_email = $_SESSION['email'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Dashboard — ScanCare</title>
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
                <a href="dashboard_resident.php" class="text-slate-700 dark:text-slate-300 hover:text-emerald-600 font-medium">
                    Dashboard
                </a>
                <a href="appointments.php" class="text-slate-700 dark:text-slate-300 hover:text-emerald-600 font-medium">
                    Appointments
                </a>
                <a href="medical-records.php" class="text-slate-700 dark:text-slate-300 hover:text-emerald-600 font-medium">
                    Medical Records
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
            <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-2">
                Welcome, <?php echo htmlspecialchars($resident_name); ?>! 👋
            </h1>
            <p class="text-slate-600 dark:text-slate-400">
                Your health information and appointment center
            </p>
        </div>

        <!
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            
            <!
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center gap-4">
                    <div class="bg-emerald-100 dark:bg-emerald-900/30 rounded-full p-4">
                        <i class="fas fa-user text-2xl text-emerald-600"></i>
                    </div>
                    <div>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Your Profile</p>
                        <p class="text-lg font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($resident_name); ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($resident_email); ?></p>
                    </div>
                </div>
            </div>

            <!
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
                <p class="text-slate-600 dark:text-slate-400 text-sm mb-4 font-semibold">Quick Actions</p>
                <div class="space-y-2">
                    <a href="appointments.php" class="block px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition">
                        <i class="fas fa-plus mr-2"></i>Book Appointment
                    </a>
                </div>
            </div>

            <!
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
                <p class="text-slate-600 dark:text-slate-400 text-sm mb-3">System Status</p>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-green-600 dark:text-green-400 font-medium">All Systems Operational</span>
                </div>
            </div>

        </div>

        <!
             FEATURE 2: MEDICAL HISTORY SECTION
             =================================================================== 
        
        <?php 
            
            
            
            
            
            include 'components/medical_history_section.php'; 
        ?>

        <!
             FEATURE 5: MEDICINE AVAILABILITY SECTION
             =================================================================== 

        <?php 
            
            
            
            
            
            require_once 'medicine_inventory_system.php';
            include 'components/resident_medicine_availability.php'; 
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
                <div><h4 class="font-semibold mb-4">Contact</h4><p class="text-slate-400 text-sm">Floridablanca RHU II - GUTAD<br><a href="mailto:floridablancarhu2@gmail.com" class="inline-flex items-center gap-2 hover:text-white transition"><i class="fa-solid fa-envelope w-4 text-center" aria-hidden="true"></i><span>floridablancarhu2@gmail.com</span></a><br><a href="tel:09480566872" class="inline-flex items-center gap-2 hover:text-white transition"><i class="fa-solid fa-phone w-4 text-center" aria-hidden="true"></i><span>0948 056 6872</span></a><br><a href="https://www.facebook.com/share/1DRzKzHcYD/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 hover:text-white transition" aria-label="ScanCare on Facebook"><i class="fa-brands fa-facebook-f w-4 text-center" aria-hidden="true"></i><span>RHU II - Gutad</span></a></p></div>
            </div>
            <div class="border-t border-slate-800 pt-8 text-center text-slate-400 text-sm"><p>&copy; 2026 ScanCare. All rights reserved. Your data is encrypted and secure.</p></div>
        </div>
    </footer>

</body>
</html>

