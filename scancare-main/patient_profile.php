<?php


session_start();
require_once 'db_config.php';









if (!isset($_SESSION['user_id'])) {
    
    if (!isset($_GET['qr_scan']) || $_GET['qr_scan'] !== '1') {
        header('Location: login.php');
        exit;
    }
}


if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'bhw' && $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . ($_SESSION['user_role'] === 'resident' ? 'dashboard.php' : 'login.php'));
    exit;
}




$patient_id = intval($_GET['id'] ?? 0);

if ($patient_id <= 0) {
    die('Invalid patient ID. Please search for a patient or scan a QR code.');
}

try {
    $pdo = getDatabaseConnection();
    if ($pdo === false) {
        die('Database connection error');
    }

    
    
    
    $patient_query = "
        SELECT 
            u.user_id,
            u.full_name,
            u.phone_number,
            u.email,
            u.date_of_birth,
            u.address,
            u.is_active,
            DATE_FORMAT(u.created_at, '%M %d, %Y') as member_since
        FROM users u
        WHERE u.user_id = ? AND u.role = 'resident' AND u.is_active = 1
        LIMIT 1
    ";

    $patient_stmt = $pdo->prepare($patient_query);
    $patient_stmt->execute([$patient_id]);
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die('Patient not found or is inactive.');
    }

    
    
    
    $history_query = "
        SELECT 
            history_id,
            visit_date,
            visit_type,
            bhw_name,
            diagnosis,
            treatment,
            notes,
            DATE_FORMAT(visit_date, '%b %d, %Y') as formatted_date,
            DATE_FORMAT(visit_date, '%g:%i %p') as formatted_time
        FROM medical_history
        WHERE user_id = ?
        ORDER BY visit_date DESC
        LIMIT 10
    ";

    $history_stmt = $pdo->prepare($history_query);
    $history_stmt->execute([$patient_id]);
    $medical_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

    
    
    
    $appointments_query = "
        SELECT 
            appointment_id,
            appointment_date,
            appointment_time,
            reason,
            status,
            DATE_FORMAT(appointment_date, '%b %d, %Y') as formatted_date,
            DATE_FORMAT(appointment_time, '%g:%i %p') as formatted_time
        FROM appointments
        WHERE user_id = ?
        ORDER BY appointment_date DESC
        LIMIT 5
    ";

    $appointments_stmt = $pdo->prepare($appointments_query);
    $appointments_stmt->execute([$patient_id]);
    $appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

    
    
    
    $dob = new DateTime($patient['date_of_birth'] ?? 'now');
    $today = new DateTime();
    $age = $today->diff($dob)->y;

} catch (Exception $e) {
    error_log("PATIENT PROFILE ERROR: " . $e->getMessage());
    die('Error loading patient profile: ' . htmlspecialchars($e->getMessage()));
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile — ScanCare</title>
    <script src="https:
    <link rel="stylesheet" href="https:
    <style>
        @import url('https:
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
        body {
            background: #F8FAF6;
        }
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
            <h1 class="text-2xl font-heading font-bold text-slate-900">Patient Profile</h1>
            <div class="w-16"></div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 lg:px-12 py-8">

        <!
        <div class="bg-white border border-slate-200 rounded-2xl p-8 mb-8 shadow-sm">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <div>
                    <h2 class="text-3xl font-heading font-bold text-slate-900 mb-2">
                        <?php echo htmlspecialchars($patient['full_name']); ?>
                    </h2>
                    <div class="space-y-2 text-slate-600">
                        <p><i class="fa-solid fa-id-card text-[#454B1B] w-5"></i> ID: <?php echo $patient['user_id']; ?></p>
                        <p><i class="fa-solid fa-birthday-cake text-[#454B1B] w-5"></i> Age: <?php echo $age; ?> years old</p>
                        <p><i class="fa-solid fa-phone text-[#454B1B] w-5"></i> <?php echo htmlspecialchars($patient['phone_number'] ?? 'N/A'); ?></p>
                        <p><i class="fa-solid fa-envelope text-[#454B1B] w-5"></i> <?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?></p>
                        <p><i class="fa-solid fa-map-pin text-[#454B1B] w-5"></i> <?php echo htmlspecialchars($patient['address'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-[#454B1B]/10 to-[#454B1B]/5 rounded-xl p-8 text-center">
                    <i class="fa-solid fa-user-circle text-6xl text-[#454B1B]/40 block mb-4"></i>
                    <p class="text-sm text-slate-600">Member since</p>
                    <p class="font-semibold text-slate-900"><?php echo $patient['member_since']; ?></p>
                    <span class="status-badge status-completed mt-4">
                        <i class="fa-solid fa-check-circle mr-1"></i> Active
                    </span>
                </div>
            </div>
        </div>

        <!
        <div class="grid lg:grid-cols-3 gap-8 mb-8">

            <!
            <div class="lg:col-span-2">
                <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
                    <h3 class="text-2xl font-heading font-bold text-slate-900 mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-heart-pulse text-[#454B1B]"></i>
                        Medical History
                    </h3>

                    <?php if (empty($medical_history)): ?>
                    <div class="text-center py-12 text-slate-500">
                        <p><i class="fa-solid fa-file-medical text-3xl mb-3"></i></p>
                        <p>No medical history recorded yet</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($medical_history as $record): ?>
                        <div class="border border-slate-100 rounded-lg p-4 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($record['visit_type']); ?></p>
                                    <p class="text-sm text-slate-600">
                                        <i class="fa-solid fa-calendar mr-1"></i>
                                        <?php echo $record['formatted_date']; ?> at <?php echo $record['formatted_time']; ?>
                                    </p>
                                    <p class="text-sm text-slate-600 mt-1">
                                        <i class="fa-solid fa-user-nurse mr-1"></i>
                                        BHW: <?php echo htmlspecialchars($record['bhw_name']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="bg-slate-50 rounded p-3 text-sm text-slate-700">
                                <p><strong>Diagnosis:</strong> <?php echo htmlspecialchars($record['diagnosis']); ?></p>
                                <p class="mt-2"><strong>Treatment:</strong> <?php echo htmlspecialchars($record['treatment']); ?></p>
                                <?php if ($record['notes']): ?>
                                <p class="mt-2"><strong>Notes:</strong> <?php echo htmlspecialchars($record['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!
            <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
                <h3 class="text-xl font-heading font-bold text-slate-900 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check text-[#454B1B]"></i>
                    Appointments
                </h3>

                <?php if (empty($appointments)): ?>
                <div class="text-center py-8 text-slate-500">
                    <p><i class="fa-solid fa-calendar-xmark text-2xl mb-3"></i></p>
                    <p class="text-sm">No appointments scheduled</p>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($appointments as $apt): ?>
                    <div class="border border-slate-100 rounded-lg p-3 hover:bg-slate-50 transition-colors">
                        <p class="font-semibold text-sm text-slate-900"><?php echo htmlspecialchars($apt['reason']); ?></p>
                        <p class="text-xs text-slate-600 mt-1">
                            <i class="fa-solid fa-clock"></i>
                            <?php echo $apt['formatted_date']; ?> at <?php echo $apt['formatted_time']; ?>
                        </p>
                        <span class="status-badge status-<?php echo $apt['status']; ?> text-xs mt-2">
                            <?php echo ucfirst($apt['status']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <button onclick="alert('Schedule appointment feature coming soon.')" class="w-full mt-6 px-4 py-2 bg-[#454B1B] text-white rounded-lg font-semibold hover:opacity-90 transition-opacity">
                    <i class="fa-solid fa-plus mr-2"></i> Schedule Appointment
                </button>
            </div>

        </div>

        <!
        <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
            <h3 class="text-xl font-heading font-bold text-slate-900 mb-6">Quick Actions</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <button onclick="alert('Prescription feature coming soon.')" class="px-6 py-3 border-2 border-[#454B1B] text-[#454B1B] rounded-lg font-semibold hover:bg-[#454B1B]/5 transition-colors">
                    <i class="fa-solid fa-prescription-bottle mr-2"></i> Issue Prescription
                </button>
                <button onclick="alert('Lab test feature coming soon.')" class="px-6 py-3 border-2 border-[#454B1B] text-[#454B1B] rounded-lg font-semibold hover:bg-[#454B1B]/5 transition-colors">
                    <i class="fa-solid fa-flask mr-2"></i> Order Lab Test
                </button>
                <button onclick="alert('Referral feature coming soon.')" class="px-6 py-3 border-2 border-[#454B1B] text-[#454B1B] rounded-lg font-semibold hover:bg-[#454B1B]/5 transition-colors">
                    <i class="fa-solid fa-share mr-2"></i> Make Referral
                </button>
                <a href="bhw_dashboard.php" class="px-6 py-3 bg-[#454B1B] text-white rounded-lg font-semibold hover:opacity-90 transition-opacity text-center">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Return to Dashboard
                </a>
            </div>
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

