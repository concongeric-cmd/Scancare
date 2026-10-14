<?php







session_start();
require_once 'db_config.php';








if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    header('Location: login_admin.php');
    exit;
}


$admin_id = $_SESSION['admin_id'] ?? null;
$admin_username = $_SESSION['admin_username'] ?? 'Administrator';
$admin_role = $_SESSION['admin_role'] ?? 'admin';
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';







$active_section = $_GET['section'] ?? 'dashboard';









$total_residents = 0;  
$total_staff = 0;      
$total_users = 0;      
$total_appointments = 0;
$system_uptime = '99.8%';
$db_size = '245 MB';
$last_backup = date('Y-m-d H:i:s', strtotime('-2 days'));

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception('Database connection failed');
    }
    
    
    
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM RESIDENT");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_residents = intval($result['count'] ?? 0);
        error_log("Dashboard Metrics: Residents count = {$total_residents}");
    } catch (Exception $e) {
        error_log("Trying lowercase resident table... Error: " . $e->getMessage());
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM resident");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_residents = intval($result['count'] ?? 0);
            error_log("Dashboard Metrics: Residents count (lowercase) = {$total_residents}");
        } catch (Exception $e2) {
            error_log("Failed to count residents: " . $e2->getMessage());
            $total_residents = 0;
        }
    }
    
    
    
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM HEALTH_WORKER");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_staff = intval($result['count'] ?? 0);
        error_log("Dashboard Metrics: Health Workers count = {$total_staff}");
    } catch (Exception $e) {
        error_log("Trying lowercase health_worker table... Error: " . $e->getMessage());
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM health_worker");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_staff = intval($result['count'] ?? 0);
            error_log("Dashboard Metrics: Health Workers count (lowercase) = {$total_staff}");
        } catch (Exception $e2) {
            error_log("Failed to count health workers: " . $e2->getMessage());
            $total_staff = 0;
        }
    }
    
    
    
    
    
    $total_users = $total_residents + $total_staff;
    error_log("Dashboard Metrics: Combined Total Users = {$total_users} (Residents: {$total_residents} + Staff: {$total_staff})");
    
    
    
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM APPOINTMENT WHERE status = 'scheduled'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_appointments = intval($result['count'] ?? 0);
        error_log("Dashboard Metrics: Scheduled appointments = {$total_appointments}");
    } catch (Exception $e) {
        error_log("Failed to count appointments: " . $e->getMessage());
        $total_appointments = 0;
    }
    
    error_log("Admin Dashboard Metrics Complete: Total Users={$total_users}, Staff={$total_staff}, Residents={$total_residents}, Appointments={$total_appointments}");
    
} catch (Exception $e) {
    error_log("Admin Dashboard Database Error: " . $e->getMessage());
    
}


function isActiveSection($section) {
    global $active_section;
    return $active_section === $section;
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Panel — ScanCare</title>
    <script src="https:
    <link rel="stylesheet" href="https:
    <style>
        @import url('https:
        
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        body { background: #0f172a; }

        .sidebar-link {
            position: relative;
            padding: 12px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-link:hover {
            color: #454B1B;
            background-color: rgba(69, 75, 27, 0.15);
        }

        .sidebar-link.active {
            color: #454B1B;
            background-color: rgba(69, 75, 27, 0.25);
            font-weight: 600;
        }

        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background-color: #454B1B;
            border-radius: 0 4px 4px 0;
        }

        .section-content {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: #454B1B;
            box-shadow: 0 0 20px rgba(69, 75, 27, 0.1);
        }

        .action-button {
            background-color: #454B1B;
            transition: all 0.3s ease;
        }

        .action-button:hover {
            opacity: 0.95;
            box-shadow: 0 0 15px rgba(69, 75, 27, 0.3);
        }
    </style>
</head>
<body class="font-body text-slate-200">

    <!
         LAYOUT WRAPPER
         ==================================================================== 
    <div class="flex min-h-screen">

        <!
             SIDEBAR NAVIGATION
             ================================================================ 
        <aside class="w-64 bg-slate-900 border-r border-slate-800 sticky top-0 h-screen overflow-y-auto">
            
            <!
            <div class="p-6 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <img src="images/SClogo.png" alt="ScanCare Logo" class="h-10 w-10 object-contain">
                    <div>
                        <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-lg font-extrabold tracking-tight text-white block">
                            Scan<span style="color: #454B1B;">Care</span>
                        </span>
                        <p class="text-xs text-slate-500">Admin Panel</p>
                    </div>
                </div>
            </div>

            <!
            <div class="flex items-center space-x-3 p-3 mx-4 mt-4 bg-slate-800/50 rounded-xl border border-slate-700/30 mb-6">
                <div class="p-2 bg-slate-700/60 rounded-lg text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-white tracking-wide"><?php echo htmlspecialchars($admin_name); ?></h4>
                    <p class="text-xs font-medium text-slate-400 mt-0.5">Admin</p>
                </div>
            </div>

            <!
            <nav class="px-4 space-y-2">
                
                <!
                <a href="?section=dashboard" class="sidebar-link <?php echo isActiveSection('dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-gauge-high"></i> Dashboard
                </a>

                <!
                <a href="?section=user-management" class="sidebar-link <?php echo isActiveSection('user-management') ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i> User Management
                </a>

                <!
                <a href="?section=system-config" class="sidebar-link <?php echo isActiveSection('system-config') ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i> System Configuration
                </a>

                <!
                <a href="?section=database-hub" class="sidebar-link <?php echo isActiveSection('database-hub') ? 'active' : ''; ?>">
                    <i class="fas fa-database"></i> Database Integrity
                </a>

                <!
                <a href="?section=notifications" class="sidebar-link <?php echo isActiveSection('notifications') ? 'active' : ''; ?>">
                    <i class="fas fa-bell-slash"></i> Notifications
                </a>

                <!
                <a href="?section=cloud-storage" class="sidebar-link <?php echo isActiveSection('cloud-storage') ? 'active' : ''; ?>">
                    <i class="fas fa-cloud"></i> Cloud & Storage
                </a>

                <!
                <a href="?section=monitoring" class="sidebar-link <?php echo isActiveSection('monitoring') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Monitoring
                </a>

                <!
                <a href="?section=deployment" class="sidebar-link <?php echo isActiveSection('deployment') ? 'active' : ''; ?>">
                    <i class="fas fa-rocket"></i> Deployment
                </a>
            </nav>

            <!
            <div class="p-4 border-t border-slate-800 mt-8 absolute bottom-0 w-full">
                <div class="text-xs text-slate-500 space-y-1">
                    <p><strong>Session ID:</strong> <?php echo substr(session_id(), 0, 8); ?>...</p>
                    <p><strong>Last Activity:</strong> Just now</p>
                </div>
            </div>
        </aside>

        <!
             MAIN CONTENT AREA
             ================================================================ 
        <main class="flex-1 overflow-auto">
            
            <!
            <nav class="border-b border-slate-800 bg-slate-900/50 sticky top-0 z-40 backdrop-blur">
                <div class="px-8 py-4 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-heading font-bold text-white">
                            <?php 
                                $section_titles = [
                                    'dashboard' => 'Dashboard',
                                    'user-management' => 'User Account Management',
                                    'system-config' => 'System Configuration',
                                    'database-hub' => 'Database Integrity Hub',
                                    'notifications' => 'Notification Settings',
                                    'cloud-storage' => 'Cloud & Storage Manager',
                                    'monitoring' => 'Monitoring Center',
                                    'deployment' => 'Deployment Zone'
                                ];
                                echo $section_titles[$active_section] ?? 'Administrator Panel';
                            ?>
                        </h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-slate-400">
                            <i class="fas fa-server text-green-500"></i> System Online
                        </div>
                        <a href="logout_admin.php" class="px-4 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition text-sm font-semibold">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!
            <div class="p-8 section-content">

                <!
                     SECTION 1: DASHBOARD OVERVIEW
                     ============================================================ 
                <?php if (isActiveSection('dashboard')): ?>
                <div class="space-y-8">
                    
                    <!
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        
                        <div class="stat-card rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-400">
                                    <i class="fas fa-users"></i>
                                </div>
                                <span class="text-xs font-bold text-blue-400 bg-blue-500/20 px-2 py-1 rounded">+5%</span>
                            </div>
                            <p class="text-3xl font-heading font-bold text-white"><?php echo $total_users; ?></p>
                            <p class="text-sm text-slate-400 mt-1">Total Users</p>
                        </div>

                        <div class="stat-card rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <span class="text-xs font-bold text-emerald-400 bg-emerald-500/20 px-2 py-1 rounded">Active</span>
                            </div>
                            <p class="text-3xl font-heading font-bold text-white"><?php echo $total_staff; ?></p>
                            <p class="text-sm text-slate-400 mt-1">Staff Accounts</p>
                        </div>

                        <div class="stat-card rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center text-purple-400">
                                    <i class="fas fa-server"></i>
                                </div>
                                <span class="text-xs font-bold text-purple-400 bg-purple-500/20 px-2 py-1 rounded">Excellent</span>
                            </div>
                            <p class="text-3xl font-heading font-bold text-white"><?php echo $system_uptime; ?></p>
                            <p class="text-sm text-slate-400 mt-1">System Uptime</p>
                        </div>

                        <div class="stat-card rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center text-orange-400">
                                    <i class="fas fa-database"></i>
                                </div>
                                <span class="text-xs font-bold text-orange-400 bg-orange-500/20 px-2 py-1 rounded">Healthy</span>
                            </div>
                            <p class="text-3xl font-heading font-bold text-white"><?php echo $db_size; ?></p>
                            <p class="text-sm text-slate-400 mt-1">Database Size</p>
                        </div>
                    </div>

                    <!
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4">Recent Admin Activity</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-3 border-b border-slate-700">
                                    <span class="text-sm text-slate-400">User account created: maria.santos@test.com</span>
                                    <span class="text-xs text-slate-500">2 hours ago</span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-slate-700">
                                    <span class="text-sm text-slate-400">System configuration updated</span>
                                    <span class="text-xs text-slate-500">5 hours ago</span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-slate-700">
                                    <span class="text-sm text-slate-400">Database backup completed successfully</span>
                                    <span class="text-xs text-slate-500">1 day ago</span>
                                </div>
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-sm text-slate-400">Security audit performed</span>
                                    <span class="text-xs text-slate-500">3 days ago</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <a href="?section=user-management" class="action-button w-full text-white px-4 py-3 rounded-lg font-semibold transition flex items-center gap-2">
                                    <i class="fas fa-user-plus"></i> Create New User Account
                                </a>
                                <a href="?section=database-hub" class="action-button w-full text-white px-4 py-3 rounded-lg font-semibold transition flex items-center gap-2">
                                    <i class="fas fa-backup"></i> Backup Database
                                </a>
                                <a href="?section=deployment" class="action-button w-full text-white px-4 py-3 rounded-lg font-semibold transition flex items-center gap-2">
                                    <i class="fas fa-rocket"></i> Deploy System Updates
                                </a>
                                <a href="?section=monitoring" class="action-button w-full text-white px-4 py-3 rounded-lg font-semibold transition flex items-center gap-2">
                                    <i class="fas fa-chart-line"></i> View System Monitoring
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!
                     SECTION 2: USER ACCOUNT MANAGEMENT
                     ============================================================ 
                <?php if (isActiveSection('user-management')): ?>
                <div class="space-y-6">
                    
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-slate-400">Manage, create, edit, and deactivate staff and official accounts</p>
                        <button class="action-button text-white px-6 py-2 rounded-lg font-semibold flex items-center gap-2">
                            <i class="fas fa-user-plus"></i> Create New Account
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        
                        <!
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-people-group text-blue-400"></i> Staff Accounts
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Active Staff</span>
                                    <span class="text-2xl font-bold text-blue-400">18</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Pending Activation</span>
                                    <span class="text-2xl font-bold text-amber-400">3</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Deactivated</span>
                                    <span class="text-2xl font-bold text-slate-500">2</span>
                                </div>
                            </div>
                            <button class="w-full mt-4 action-button text-white px-4 py-2 rounded-lg font-semibold text-sm">
                                <i class="fas fa-users"></i> Manage Staff Accounts
                            </button>
                        </div>

                        <!
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-shield-alt text-emerald-400"></i> Official Accounts
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Super Administrators</span>
                                    <span class="text-2xl font-bold text-emerald-400">2</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Administrators</span>
                                    <span class="text-2xl font-bold text-emerald-400">5</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Moderators</span>
                                    <span class="text-2xl font-bold text-blue-400">7</span>
                                </div>
                            </div>
                            <button class="w-full mt-4 action-button text-white px-4 py-2 rounded-lg font-semibold text-sm">
                                <i class="fas fa-shield-alt"></i> Manage Officials
                            </button>
                        </div>
                    </div>

                    <!
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                        <h3 class="text-lg font-heading font-bold text-white mb-4">Accounts Awaiting Management Action</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-700">
                                        <th class="text-left py-3 px-4 text-slate-400">Account Name</th>
                                        <th class="text-left py-3 px-4 text-slate-400">Role</th>
                                        <th class="text-left py-3 px-4 text-slate-400">Status</th>
                                        <th class="text-center py-3 px-4 text-slate-400">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-slate-700/50 hover:bg-slate-700/30 transition">
                                        <td class="py-3 px-4">Dr. Anna Reyes</td>
                                        <td class="py-3 px-4"><span class="text-blue-400">BHW</span></td>
                                        <td class="py-3 px-4"><span class="text-amber-400 text-xs font-bold px-2 py-1 bg-amber-500/20 rounded">Pending</span></td>
                                        <td class="py-3 px-4 text-center space-x-2">
                                            <button class="text-emerald-400 hover:text-emerald-300 text-xs font-bold">Approve</button>
                                            <button class="text-red-400 hover:text-red-300 text-xs font-bold">Reject</button>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-slate-700/50 hover:bg-slate-700/30 transition">
                                        <td class="py-3 px-4">Maria del Carmen</td>
                                        <td class="py-3 px-4"><span class="text-blue-400">Official</span></td>
                                        <td class="py-3 px-4"><span class="text-red-400 text-xs font-bold px-2 py-1 bg-red-500/20 rounded">Deactivation Request</span></td>
                                        <td class="py-3 px-4 text-center space-x-2">
                                            <button class="text-emerald-400 hover:text-emerald-300 text-xs font-bold">Confirm</button>
                                            <button class="text-slate-400 hover:text-slate-300 text-xs font-bold">Cancel</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!
                     SECTION 3: SYSTEM CONFIGURATION
                     ============================================================ 
                <?php if (isActiveSection('system-config')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Configure general site-wide system settings and preferences</p>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4">General Settings</h3>
                            <form class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-300 mb-2">Application Name</label>
                                    <input type="text" value="ScanCare" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-300 mb-2">Primary Email</label>
                                    <input type="email" value="admin@scancare.local" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-300 mb-2">Organization</label>
                                    <input type="text" value="Barangay Gutad Health Center" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                                </div>
                                <button type="button" class="action-button w-full text-white px-4 py-2 rounded-lg font-semibold text-sm mt-4">
                                    <i class="fas fa-save mr-2"></i> Save Settings
                                </button>
                            </form>
                        </div>

                        <!
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4">Security Settings</h3>
                            <form class="space-y-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Require strong passwords (8+ chars)</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Enable two-factor authentication</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Log all admin actions</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Enable maintenance mode</span>
                                </label>
                                <button type="button" class="action-button w-full text-white px-4 py-2 rounded-lg font-semibold text-sm mt-4">
                                    <i class="fas fa-shield-alt mr-2"></i> Update Security
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!
                     SECTION 4: DATABASE INTEGRITY HUB
                     ============================================================ 
                <?php if (isActiveSection('database-hub')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Manage database backups, integrity checks, and data maintenance</p>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        
                        <!
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-backup text-blue-400"></i> Database Backups
                            </h3>
                            <div class="space-y-4">
                                <div class="p-3 bg-slate-700/50 rounded-lg border border-slate-600">
                                    <p class="text-sm text-slate-300">Last Backup: <span class="text-emerald-400 font-semibold"><?php echo $last_backup; ?></span></p>
                                </div>
                                <button class="action-button w-full text-white px-4 py-3 rounded-lg font-semibold flex items-center justify-center gap-2">
                                    <i class="fas fa-play"></i> Create Backup Now
                                </button>
                                <button class="w-full px-4 py-3 rounded-lg font-semibold border border-slate-600 text-slate-300 hover:bg-slate-700 transition flex items-center justify-center gap-2">
                                    <i class="fas fa-history"></i> View Backup History
                                </button>
                            </div>
                        </div>

                        <!
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-stethoscope text-emerald-400"></i> Integrity Check
                            </h3>
                            <div class="space-y-4">
                                <div class="p-3 bg-slate-700/50 rounded-lg border border-emerald-600/30">
                                    <p class="text-sm text-slate-300">Status: <span class="text-emerald-400 font-semibold">✓ All tables healthy</span></p>
                                </div>
                                <button class="action-button w-full text-white px-4 py-3 rounded-lg font-semibold flex items-center justify-center gap-2">
                                    <i class="fas fa-check-circle"></i> Run Integrity Check
                                </button>
                                <button class="w-full px-4 py-3 rounded-lg font-semibold border border-slate-600 text-slate-300 hover:bg-slate-700 transition flex items-center justify-center gap-2">
                                    <i class="fas fa-tools"></i> Optimize Tables
                                </button>
                            </div>
                        </div>
                    </div>

                    <!
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                        <h3 class="text-lg font-heading font-bold text-white mb-4">Database Statistics</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 bg-slate-700/50 rounded-lg">
                                <p class="text-sm text-slate-400">Total Size</p>
                                <p class="text-2xl font-bold text-blue-400"><?php echo $db_size; ?></p>
                            </div>
                            <div class="p-4 bg-slate-700/50 rounded-lg">
                                <p class="text-sm text-slate-400">Tables</p>
                                <p class="text-2xl font-bold text-emerald-400">8</p>
                            </div>
                            <div class="p-4 bg-slate-700/50 rounded-lg">
                                <p class="text-sm text-slate-400">Total Records</p>
                                <p class="text-2xl font-bold text-purple-400">4,250</p>
                            </div>
                            <div class="p-4 bg-slate-700/50 rounded-lg">
                                <p class="text-sm text-slate-400">Last Optimized</p>
                                <p class="text-sm font-bold text-orange-400">1 week ago</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!
                     SECTION 5: NOTIFICATION SETTINGS
                     ============================================================ 
                <?php if (isActiveSection('notifications')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Configure SMS notification triggers and alerting rules</p>

                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 mb-6">
                        <h3 class="text-lg font-heading font-bold text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-bell"></i> SMS Notification Rules
                        </h3>
                        
                        <div class="space-y-4">
                            
                            <!
                            <div class="p-4 bg-slate-700/50 rounded-lg border border-slate-600 flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-white">Appointment Reminders</p>
                                    <p class="text-sm text-slate-400">Send SMS 24 hours before appointment</p>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-600 border-slate-500 checked:bg-emerald-500">
                                    <span class="text-sm text-slate-300">Enabled</span>
                                </label>
                            </div>

                            <!
                            <div class="p-4 bg-slate-700/50 rounded-lg border border-slate-600 flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-white">System Alerts</p>
                                    <p class="text-sm text-slate-400">Critical system events and errors</p>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-600 border-slate-500 checked:bg-emerald-500">
                                    <span class="text-sm text-slate-300">Enabled</span>
                                </label>
                            </div>

                            <!
                            <div class="p-4 bg-slate-700/50 rounded-lg border border-slate-600 flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-white">Health Announcements</p>
                                    <p class="text-sm text-slate-400">Clinic announcements and health tips</p>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="w-4 h-4 rounded bg-slate-600 border-slate-500 checked:bg-emerald-500">
                                    <span class="text-sm text-slate-300">Enabled</span>
                                </label>
                            </div>

                            <!
                            <div class="p-4 bg-slate-700/50 rounded-lg border border-slate-600 flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-white">Admin Actions</p>
                                    <p class="text-sm text-slate-400">Log admin actions and configuration changes</p>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-600 border-slate-500 checked:bg-emerald-500">
                                    <span class="text-sm text-slate-300">Enabled</span>
                                </label>
                            </div>
                        </div>

                        <button class="action-button w-full text-white px-6 py-3 rounded-lg font-semibold mt-6 flex items-center justify-center gap-2">
                            <i class="fas fa-save mr-2"></i> Save Notification Settings
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!
                     SECTION 6: CLOUD & STORAGE MANAGER
                     ============================================================ 
                <?php if (isActiveSection('cloud-storage')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Manage cloud synchronization and local storage settings</p>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-cloud text-blue-400"></i> Cloud Synchronization
                            </h3>
                            <form class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-300 mb-2">Cloud Provider</label>
                                    <select class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                                        <option>AWS S3</option>
                                        <option>Google Cloud Storage</option>
                                        <option>Azure Blob Storage</option>
                                        <option>Local Server Only</option>
                                    </select>
                                </div>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Enable automatic sync</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Encrypt cloud data</span>
                                </label>
                                <button type="button" class="action-button w-full text-white px-4 py-2 rounded-lg font-semibold text-sm">
                                    <i class="fas fa-sync mr-2"></i> Test Connection
                                </button>
                            </form>
                        </div>

                        <!
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-hdd text-orange-400"></i> Local Storage
                            </h3>
                            <form class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-300 mb-2">Storage Path</label>
                                    <input type="text" value="/var/www/scancare/backups" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]" readonly>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm text-slate-400">
                                        <span>Disk Usage</span>
                                        <span>185 GB / 500 GB</span>
                                    </div>
                                    <div class="w-full bg-slate-700 rounded-full h-2">
                                        <div class="bg-emerald-500 h-2 rounded-full" style="width: 37%"></div>
                                    </div>
                                </div>
                                <button type="button" class="action-button w-full text-white px-4 py-2 rounded-lg font-semibold text-sm">
                                    <i class="fas fa-broom mr-2"></i> Clean Old Backups
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!
                     SECTION 7: MONITORING CENTER
                     ============================================================ 
                <?php if (isActiveSection('monitoring')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Real-time system logs and performance monitoring</p>

                    <!
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h4 class="text-sm font-bold text-slate-400 mb-3">CPU Usage</h4>
                            <p class="text-3xl font-bold text-blue-400">34%</p>
                            <div class="w-full bg-slate-700 rounded-full h-2 mt-3">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: 34%"></div>
                            </div>
                        </div>

                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h4 class="text-sm font-bold text-slate-400 mb-3">Memory Usage</h4>
                            <p class="text-3xl font-bold text-emerald-400">52%</p>
                            <div class="w-full bg-slate-700 rounded-full h-2 mt-3">
                                <div class="bg-emerald-500 h-2 rounded-full" style="width: 52%"></div>
                            </div>
                        </div>

                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h4 class="text-sm font-bold text-slate-400 mb-3">Response Time</h4>
                            <p class="text-3xl font-bold text-purple-400">142ms</p>
                            <p class="text-xs text-slate-500 mt-2">Average page load</p>
                        </div>
                    </div>

                    <!
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                        <h3 class="text-lg font-heading font-bold text-white mb-4">System Logs</h3>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            <div class="text-sm text-slate-400 font-mono p-2 bg-slate-900 rounded border border-slate-700">
                                <span class="text-emerald-400">[2026-06-09 15:23:45]</span> INFO: Database backup completed (245 MB)
                            </div>
                            <div class="text-sm text-slate-400 font-mono p-2 bg-slate-900 rounded border border-slate-700">
                                <span class="text-blue-400">[2026-06-09 14:55:12]</span> INFO: Admin user login: admin
                            </div>
                            <div class="text-sm text-slate-400 font-mono p-2 bg-slate-900 rounded border border-slate-700">
                                <span class="text-amber-400">[2026-06-09 14:22:33]</span> WARNING: High memory usage detected (76%)
                            </div>
                            <div class="text-sm text-slate-400 font-mono p-2 bg-slate-900 rounded border border-slate-700">
                                <span class="text-emerald-400">[2026-06-09 13:00:00]</span> INFO: Automated system check completed
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!
                     SECTION 8: DEPLOYMENT ZONE
                     ============================================================ 
                <?php if (isActiveSection('deployment')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Deploy system updates and manage application versions</p>

                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-8">
                        <h3 class="text-2xl font-heading font-bold text-white mb-6 flex items-center gap-3">
                            <i class="fas fa-rocket text-emerald-400 text-3xl"></i> System Deployment
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                            
                            <!
                            <div class="bg-slate-700/50 rounded-lg p-6 border border-slate-600">
                                <h4 class="font-semibold text-white mb-4">Current Version</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Version:</span>
                                        <span class="font-semibold text-white">v2.1.0</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Release Date:</span>
                                        <span class="font-semibold text-white">2026-05-15</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Status:</span>
                                        <span class="text-emerald-400 font-semibold">✓ Stable</span>
                                    </div>
                                </div>
                            </div>

                            <!
                            <div class="bg-slate-700/50 rounded-lg p-6 border border-slate-600">
                                <h4 class="font-semibold text-white mb-4">Available Updates</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Latest Version:</span>
                                        <span class="font-semibold text-amber-400">v2.2.0 (Beta)</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Release Date:</span>
                                        <span class="font-semibold text-white">2026-06-01</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Changes:</span>
                                        <span class="font-semibold text-white">12 fixes, 5 features</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!
                        <div class="bg-gradient-to-r from-emerald-900/30 to-blue-900/30 border border-emerald-600/30 rounded-lg p-8 mb-8">
                            <h4 class="text-lg font-heading font-bold text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle text-amber-400"></i> Important Notice
                            </h4>
                            <p class="text-slate-300 mb-4">
                                System updates will restart your application. Scheduled deployments should occur during maintenance windows to minimize user impact.
                            </p>
                            <p class="text-sm text-slate-400">
                                Current users: <span class="text-emerald-400 font-semibold">12 active</span> | 
                                Recommended downtime: <span class="text-amber-400 font-semibold">5-10 minutes</span>
                            </p>
                        </div>

                        <!
                        <button class="action-button w-full text-white px-8 py-4 rounded-lg font-bold text-lg flex items-center justify-center gap-3">
                            <i class="fas fa-rocket"></i> Deploy System Update (v2.2.0)
                        </button>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

</body>
</html>

