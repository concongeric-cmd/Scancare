<?php


session_start();
require_once 'db_config.php';




if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'bhw') {
    header('Location: login.php');
    exit;
}

try {
    $pdo = getDatabaseConnection();
    if ($pdo === false) {
        die('Database connection error');
    }

    
    
    
    
    $sms_query = "
        SELECT 
            a.appointment_id as sms_id,
            a.user_id,
            u.full_name,
            u.phone_number,
            a.appointment_date,
            a.reason,
            'Appointment Reminder' as message_type,
            CONCAT('Reminder: You have an appointment on ', 
                   DATE_FORMAT(a.appointment_date, '%M %d, %Y'), 
                   ' at ', DATE_FORMAT(a.appointment_time, '%g:%i %p'), 
                   ' for ', a.reason) as message_preview,
            a.created_at as sent_at,
            'sent' as status,
            DATE_FORMAT(a.created_at, '%b %d, %Y at %g:%i %p') as formatted_sent_at
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY a.created_at DESC
        LIMIT 50
    ";

    $sms_stmt = $pdo->prepare($sms_query);
    $sms_stmt->execute();
    $sms_logs = $sms_stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $total_sms = count($sms_logs);
    $sent_count = count(array_filter($sms_logs, fn($s) => $s['status'] === 'sent'));
    $last_30_days = $total_sms;

} catch (Exception $e) {
    error_log("SMS LOGS ERROR: " . $e->getMessage());
    $sms_logs = [];
    $total_sms = 0;
    $sent_count = 0;
    $last_30_days = 0;
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Communications — ScanCare</title>
    <script src="https:
    <link rel="stylesheet" href="https:
    <style>
        @import url('https:
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
        body { background: #F8FAF6; }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-sent { background-color: #dcfce7; color: #166534; }
        .status-failed { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="font-body">

    <!
    <nav class="border-b border-[#454B1B]/10 bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 lg:px-12 py-4 flex items-center justify-between">
            <a href="bhw_dashboard.php" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                <i class="fa-solid fa-arrow-left text-[#454B1B]"></i>
                <span class="font-semibold text-slate-900">Back to Dashboard</span>
            </a>
            <h1 class="text-2xl font-heading font-bold text-slate-900">SMS Communications</h1>
            <div class="w-32"></div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 lg:px-12 py-8">

        <!
        <div class="grid md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-[#454B1B] mb-2"><?php echo $total_sms; ?></div>
                <p class="text-sm font-semibold text-slate-600">Total Sent</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-green-600 mb-2"><?php echo $sent_count; ?></div>
                <p class="text-sm font-semibold text-slate-600">Successfully Sent</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-600 mb-2"><?php echo $last_30_days; ?></div>
                <p class="text-sm font-semibold text-slate-600">Last 30 Days</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-purple-600 mb-2">
                    <?php echo $total_sms > 0 ? round(($sent_count / $total_sms) * 100) : 0; ?>%
                </div>
                <p class="text-sm font-semibold text-slate-600">Success Rate</p>
            </div>
        </div>

        <!
        <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
            <h2 class="text-2xl font-heading font-bold text-slate-900 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-sms text-[#454B1B]"></i>
                Communication Logs
            </h2>

            <?php if (empty($sms_logs)): ?>
            <div class="text-center py-12 text-slate-500">
                <p><i class="fa-solid fa-envelope-open text-3xl mb-3"></i></p>
                <p>No SMS communications in the last 30 days</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($sms_logs as $sms): ?>
                <div class="border border-slate-100 rounded-lg p-4 hover:bg-slate-50 transition-colors">
                    <div class="grid md:grid-cols-2 gap-4 mb-3">
                        <div>
                            <p class="font-semibold text-slate-900">
                                <i class="fa-solid fa-user-circle mr-2 text-[#454B1B]"></i>
                                <?php echo htmlspecialchars($sms['full_name']); ?>
                            </p>
                            <p class="text-sm text-slate-600">
                                <i class="fa-solid fa-phone mr-1"></i>
                                <?php echo htmlspecialchars($sms['phone_number']); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold text-slate-600">Sent:</p>
                            <p class="text-sm text-slate-900"><?php echo $sms['formatted_sent_at']; ?></p>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded p-3 mb-3 border-l-4 border-[#454B1B]">
                        <p class="text-sm text-slate-700 italic">
                            "<?php echo htmlspecialchars($sms['message_preview']); ?>"
                        </p>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="status-badge status-<?php echo $sms['status']; ?>">
                            <i class="fa-solid fa-check-circle mr-1"></i> <?php echo ucfirst($sms['status']); ?>
                        </span>
                        <a href="patient_profile.php?id=<?php echo $sms['user_id']; ?>" class="text-[#454B1B] hover:text-slate-900 font-semibold text-xs">
                            <i class="fa-solid fa-arrow-right mr-1"></i> View Patient
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </main>

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

</body>
</html>

