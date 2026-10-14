<?php


session_start();
require_once 'db_config.php';


if (!isset($_SESSION['worker_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$user_type = $_SESSION['user_type'] ?? 'health_worker';
$user_id = $_SESSION['worker_id'] ?? $_SESSION['admin_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScanCare Dashboard</title>
    <script src="https:
    <link rel="stylesheet" href="https:
    <style>
        @import url('https:
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50">
    
    <!
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <h1 class="text-2xl font-heading font-bold text-slate-900">ScanCare</h1>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-600"><?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold">
                    Logout
                </a>
            </div>
        </div>
    </nav>
    
    <!
    <div class="max-w-7xl mx-auto px-6 py-8">
        
        <!
        <div class="mb-8">
            <h2 class="text-3xl font-heading font-bold text-slate-900">Health Worker Dashboard</h2>
            <p class="text-slate-600 mt-2">Manage appointments, medicines, and consultations</p>
        </div>
        
        <!
        <div class="mb-6 flex gap-4 border-b border-slate-200">
            <button onclick="showTab('appointments')" class="tab-btn active px-4 py-3 text-slate-900 font-semibold border-b-2 border-emerald-600">
                <i class="fas fa-calendar-check mr-2"></i>Appointments
            </button>
            <button onclick="showTab('inventory')" class="tab-btn px-4 py-3 text-slate-600 font-semibold border-b-2 border-transparent hover:text-slate-900">
                <i class="fas fa-pills mr-2"></i>Medicine Inventory
            </button>
            <button onclick="showTab('consultation')" class="tab-btn px-4 py-3 text-slate-600 font-semibold border-b-2 border-transparent hover:text-slate-900">
                <i class="fas fa-stethoscope mr-2"></i>Record Consultation
            </button>
        </div>
        
        <!
        <div class="space-y-6">
            
            <!
            <div id="appointments-tab" class="tab-content">
                <?php include 'FEATURE_3_appointment_schedule_manager.php'; ?>
            </div>
            
            <!
            <div id="inventory-tab" class="tab-content hidden">
                <?php include 'FEATURE_2_medicine_inventory_component.php'; ?>
            </div>
            
            <!
            <div id="consultation-tab" class="tab-content hidden">
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-heading font-bold text-slate-900 mb-6">
                        <i class="fas fa-plus-circle mr-2 text-emerald-600"></i>
                        Record New Consultation
                    </h3>
                    
                    <p class="text-slate-600 mb-4">
                        To record a consultation for a resident, click the button below to access the full consultation interface.
                    </p>
                    
                    <div class="space-y-2">
                        <p class="text-sm text-slate-500">Note: You can record a consultation for a resident by:</p>
                        <ol class="list-decimal list-inside text-sm text-slate-600 space-y-1">
                            <li>Selecting a resident from the appointment list</li>
                            <li>Clicking "Record Consultation" button</li>
                            <li>Filling in diagnosis, treatment, and dispensing medicines</li>
                        </ol>
                    </div>
                    
                    <button onclick="alert('First, select a resident from the Appointments tab')" class="mt-6 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold transition">
                        <i class="fas fa-arrow-right mr-2"></i>Go to Appointments Tab
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!
    <script>
        function showTab(tabName) {
            
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-emerald-600', 'text-slate-900');
                btn.classList.add('border-transparent', 'text-slate-600');
            });
            
            
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            event.target.closest('.tab-btn').classList.remove('border-transparent', 'text-slate-600');
            event.target.closest('.tab-btn').classList.add('border-emerald-600', 'text-slate-900');
        }
    </script>
    
</body>
</html>

