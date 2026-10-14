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

    
    
    
    $appointments_query = "
        SELECT 
            a.appointment_id,
            a.user_id,
            a.appointment_date,
            a.appointment_time,
            a.reason,
            a.status,
            u.full_name,
            u.phone_number,
            DATE_FORMAT(a.appointment_date, '%b %d, %Y') as formatted_date,
            DATE_FORMAT(a.appointment_time, '%g:%i %p') as formatted_time,
            CASE 
                WHEN a.appointment_date >= CURDATE() THEN 'upcoming'
                ELSE 'past'
            END as appointment_type
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        ORDER BY a.appointment_date DESC
        LIMIT 50
    ";

    $appointments_stmt = $pdo->prepare($appointments_query);
    $appointments_stmt->execute();
    $appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $upcoming = array_filter($appointments, fn($a) => $a['appointment_type'] === 'upcoming');
    $completed = array_filter($appointments, fn($a) => $a['status'] === 'completed');
    $pending = array_filter($appointments, fn($a) => $a['status'] === 'scheduled' || $a['status'] === 'pending');

} catch (Exception $e) {
    error_log("APPOINTMENTS LIST ERROR: " . $e->getMessage());
    die('Error loading appointments: ' . htmlspecialchars($e->getMessage()));
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Schedule — ScanCare</title>
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
        .status-completed { background-color: #dcfce7; color: #166534; }
        .status-pending { background-color: #dbeafe; color: #1e40af; }
        .status-scheduled { background-color: #fef3c7; color: #92400e; }
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
            <h1 class="text-2xl font-heading font-bold text-slate-900">Appointments Schedule</h1>
            <div class="w-32"></div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 lg:px-12 py-8">

        <!
        <div class="grid md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-[#454B1B] mb-2"><?php echo count($appointments); ?></div>
                <p class="text-sm font-semibold text-slate-600">Total Appointments</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-blue-600 mb-2"><?php echo count($upcoming); ?></div>
                <p class="text-sm font-semibold text-slate-600">Upcoming</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-green-600 mb-2"><?php echo count($completed); ?></div>
                <p class="text-sm font-semibold text-slate-600">Completed</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                <div class="text-3xl font-bold text-orange-600 mb-2"><?php echo count($pending); ?></div>
                <p class="text-sm font-semibold text-slate-600">Pending</p>
            </div>
        </div>

        <!
        <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
            <h2 class="text-2xl font-heading font-bold text-slate-900 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-calendar-check text-[#454B1B]"></i>
                All Appointments
            </h2>

            <?php if (empty($appointments)): ?>
            <div class="text-center py-12 text-slate-500">
                <p><i class="fa-solid fa-calendar-xmark text-3xl mb-3"></i></p>
                <p>No appointments scheduled</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="text-left py-3 px-4 font-semibold text-slate-900">Patient Name</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-900">Date & Time</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-900">Reason</th>
                            <th class="text-center py-3 px-4 font-semibold text-slate-900">Status</th>
                            <th class="text-center py-3 px-4 font-semibold text-slate-900">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-4">
                                <a href="patient_profile.php?id=<?php echo $apt['user_id']; ?>" class="font-semibold text-[#454B1B] hover:underline">
                                    <?php echo htmlspecialchars($apt['full_name']); ?>
                                </a>
                                <p class="text-xs text-slate-500">📞 <?php echo htmlspecialchars($apt['phone_number']); ?></p>
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                <i class="fa-solid fa-calendar mr-2"></i><?php echo $apt['formatted_date']; ?>
                                <br>
                                <i class="fa-solid fa-clock mr-2"></i><?php echo $apt['formatted_time']; ?>
                            </td>
                            <td class="py-3 px-4 text-slate-700"><?php echo htmlspecialchars($apt['reason']); ?></td>
                            <td class="py-3 px-4 text-center">
                                <span class="status-badge status-<?php echo $apt['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="patient_profile.php?id=<?php echo $apt['user_id']; ?>" class="text-[#454B1B] hover:text-slate-900 font-semibold text-xs">
                                    <i class="fa-solid fa-arrow-right mr-1"></i> View Profile
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

