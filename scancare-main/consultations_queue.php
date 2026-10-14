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

    
    
    
    $consultations_query = "
        SELECT 
            a.appointment_id,
            a.user_id,
            a.appointment_date,
            a.appointment_time,
            a.reason,
            a.status,
            u.full_name,
            u.phone_number,
            u.email,
            DATE_FORMAT(a.appointment_date, '%b %d, %Y') as formatted_date,
            DATE_FORMAT(a.appointment_time, '%g:%i %p') as formatted_time,
            TIMESTAMPDIFF(HOUR, a.created_at, NOW()) as hours_waiting
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.status IN ('pending', 'scheduled')
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 50
    ";

    $consultations_stmt = $pdo->prepare($consultations_query);
    $consultations_stmt->execute();
    $consultations = $consultations_stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $pending_count = count($consultations);
    $today_count = count(array_filter($consultations, fn($c) => $c['formatted_date'] === date('M d, Y')));

} catch (Exception $e) {
    error_log("CONSULTATIONS QUEUE ERROR: " . $e->getMessage());
    die('Error loading consultations: ' . htmlspecialchars($e->getMessage()));
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Consultations — ScanCare</title>
    <script src="https:
    <link rel="stylesheet" href="https:
    <style>
        @import url('https:
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
        body { background: #F8FAF6; }
        .priority-urgent { background-color: #fee2e2; border-left: 4px solid #dc2626; }
        .priority-normal { background-color: #dbeafe; border-left: 4px solid #2563eb; }
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
            <h1 class="text-2xl font-heading font-bold text-slate-900">Pending Consultations</h1>
            <div class="w-32"></div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 lg:px-12 py-8">

        <!
        <div class="grid md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-red-600 mb-2"><?php echo $pending_count; ?></div>
                <p class="text-sm font-semibold text-slate-600">Total Pending</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-orange-600 mb-2"><?php echo $today_count; ?></div>
                <p class="text-sm font-semibold text-slate-600">Today's Queue</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-600 mb-2">
                    <?php echo round(array_sum(array_column($consultations, 'hours_waiting')) / max(1, count($consultations))); ?>h
                </div>
                <p class="text-sm font-semibold text-slate-600">Avg Wait Time</p>
            </div>
        </div>

        <!
        <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
            <h2 class="text-2xl font-heading font-bold text-slate-900 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-hourglass-end text-[#454B1B]"></i>
                Consultation Queue
            </h2>

            <?php if (empty($consultations)): ?>
            <div class="text-center py-12 text-slate-500">
                <p><i class="fa-solid fa-check-circle text-3xl text-green-500 mb-3"></i></p>
                <p>No pending consultations. Great work!</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($consultations as $consultation): 
                    $is_urgent = $consultation['hours_waiting'] > 4;
                ?>
                <div class="<?php echo $is_urgent ? 'priority-urgent' : 'priority-normal'; ?> rounded-lg p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <a href="patient_profile.php?id=<?php echo $consultation['user_id']; ?>" class="font-semibold text-slate-900 hover:text-[#454B1B] transition-colors">
                                    <?php echo htmlspecialchars($consultation['full_name']); ?>
                                </a>
                                <?php if ($is_urgent): ?>
                                <span style="background-color: #dc2626; color: white;" class="text-xs font-bold px-2 py-1 rounded">
                                    URGENT
                                </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-slate-600">
                                <i class="fa-solid fa-stethoscope mr-1"></i>
                                <?php echo htmlspecialchars($consultation['reason']); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold text-slate-600">Waiting:</p>
                            <p class="text-lg font-bold" style="color: #454B1B;">
                                <?php echo $consultation['hours_waiting']; ?>h
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs text-slate-600 mb-3">
                        <span><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($consultation['phone_number']); ?></span>
                        <span><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($consultation['email'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="flex gap-2">
                        <a href="patient_profile.php?id=<?php echo $consultation['user_id']; ?>" class="flex-1 px-3 py-2 bg-[#454B1B] text-white text-xs font-semibold rounded hover:opacity-90 transition-opacity text-center">
                            <i class="fa-solid fa-user-circle mr-1"></i> View Profile & Consult
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

