<?php



require_once __DIR__ . '/app_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['resident_id']) || ($_SESSION['user_type'] ?? '') !== 'resident') {
    header('Location: ' . appPath('login.php'));
    exit;
}

header('Location: ' . appPath('dashboard_resident.php'));
exit;




















$patientData = [
    'patientId' => 'SC-2026-00847',
    'firstName' => 'Gabriel',
    'lastName' => 'Tala',
    'middleInitial' => 'C',
    'fullName' => 'Gabriel Tala',
    'age' => 34,
    'purok' => 'Purok 2 (East Residential)',
    'emergencyContact' => 'Juan Santos',
    'emergencyPhone' => '09123456789',
    'accountStatus' => 'Active',
    'registrationDate' => '2026-06-03'
];


function formatNameFormal($firstName, $lastName, $middleInitial = '') {
    $formal = $lastName . ', ' . $firstName;
    if (!empty($middleInitial)) {
        $formal .= ' ' . strtoupper(substr($middleInitial, 0, 1)) . '.';
    }
    return $formal;
}


$patientData['formalName'] = formatNameFormal($patientData['firstName'], $patientData['lastName'], $patientData['middleInitial']);



$medicines = [
    ['name' => 'Amoxicillin 500mg', 'status' => 'In Stock', 'quantity' => 250, 'badge' => 'emerald'],
    ['name' => 'Paracetamol 500mg', 'status' => 'In Stock', 'quantity' => 500, 'badge' => 'emerald'],
    ['name' => 'Multivitamins', 'status' => 'In Stock', 'quantity' => 180, 'badge' => 'emerald'],
    ['name' => 'Losartan 50mg', 'status' => 'In Stock', 'quantity' => 120, 'badge' => 'emerald'],
    ['name' => 'Cough Syrup', 'status' => 'Limited Supply', 'quantity' => 45, 'badge' => 'amber'],
    ['name' => 'Antihistamine Tablets', 'status' => 'In Stock', 'quantity' => 200, 'badge' => 'emerald'],
    ['name' => 'Blood Pressure Monitor Strips', 'status' => 'Limited Supply', 'quantity' => 60, 'badge' => 'amber'],
    ['name' => 'Insulin Vials', 'status' => 'In Stock', 'quantity' => 80, 'badge' => 'emerald'],
];



$services = [
    [
        'id' => 'general-checkup',
        'title' => 'Free General Check-up',
        'description' => 'Routine medical consultations, physical exams, and general health assessments by our licensed health professionals.',
        'icon' => 'fa-stethoscope',
        'color' => 'emerald'
    ],
    [
        'id' => 'dentist',
        'title' => 'Dentist Appointment',
        'description' => 'Free tooth extractions, professional cleaning, and dental health consultations with our visiting dentist.',
        'icon' => 'fa-tooth',
        'color' => 'blue'
    ],
    [
        'id' => 'specialist',
        'title' => 'Specialized Consultation',
        'description' => 'Access to targeted medical consultations and follow-ups with visiting specialists in various medical fields.',
        'icon' => 'fa-user-doctor',
        'color' => 'purple'
    ]
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Portal — ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="theme-state.js"></script>
    <link rel="stylesheet" href="global-theme.css">
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
                font-weight: 600;
                font-size: 14px;
                color: #475569;
            }
            .nav-hamburger-btn {
                display: none !important;
            }

                display: none !important;
            }
        }

        .notification-badge {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .slide-in {
            animation: slideInUp 0.3s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .service-card {
            transition: all 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(69, 75, 27, 0.1);
        }

        

        
        body.dark-mode #appointmentModal {
            background-color: rgba(0, 0, 0, 0.7) !important;
        }

        
        body.dark-mode #appointmentModal .bg-white {
            background-color: #1e293b !important;
        }

        
        body.dark-mode #appointmentModal [style*="background: linear-gradient"] {
            background: linear-gradient(135deg, #454B1B 0%, #6a7130 100%) !important;
        }

        
        body.dark-mode #appointmentModal .text-white {
            color: white !important;
        }

        body.dark-mode #appointmentModal .text-white/90 {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        
        body.dark-mode #appointmentModal .text-slate-900 {
            color: #f1f5f9 !important;
        }

        
        body.dark-mode #appointmentModal input[type="text"],
        body.dark-mode #appointmentModal input[type="date"],
        body.dark-mode #appointmentModal select,
        body.dark-mode #appointmentModal textarea {
            background-color: #0f172a !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
        }

        body.dark-mode #appointmentModal input:focus,
        body.dark-mode #appointmentModal select:focus,
        body.dark-mode #appointmentModal textarea:focus {
            ring-color: #454B1B !important;
            border-color: transparent !important;
        }

        body.dark-mode #appointmentModal .bg-slate-50 {
            background-color: #0f172a !important;
        }

        body.dark-mode #appointmentModal .text-slate-700 {
            color: #e2e8f0 !important;
        }

        
        body.dark-mode #appointmentModal .bg-blue-50 {
            background-color: rgba(59, 130, 246, 0.1) !important;
            border-color: #3b82f6 !important;
        }

        body.dark-mode #appointmentModal .text-blue-900 {
            color: #bfdbfe !important;
        }

        body.dark-mode #appointmentModal .text-blue-600 {
            color: #93c5fd !important;
        }

        
        body.dark-mode #appointmentModal button {
            background-color: #334155 !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
        }

        body.dark-mode #appointmentModal button:hover {
            background-color: #475569 !important;
            color: #f1f5f9 !important;
        }

        
        body.dark-mode #appointmentModal button[onclick="submitAppointment()"],
        body.dark-mode #appointmentModal button[style*="background-color: #454B1B"] {
            background-color: #334155 !important;
            color: #e2e8f0 !important;
            border: 1px solid #475569 !important;
        }

        body.dark-mode #appointmentModal button[onclick="submitAppointment()"]:hover,
        body.dark-mode #appointmentModal button[style*="background-color: #454B1B"]:hover {
            background-color: #475569 !important;
            border-color: #64748b !important;
        }

        
        body.dark-mode #appointmentModal button[onclick="closeAppointmentModal()"] {
            background-color: #334155 !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
        }

        body.dark-mode #appointmentModal button[onclick="closeAppointmentModal()"]:hover {
            background-color: #475569 !important;
            color: #f1f5f9 !important;
        }

        
        body.dark-mode #appointmentModal::-webkit-scrollbar-thumb {
            background-color: #475569 !important;
        }

        body.dark-mode #appointmentModal::-webkit-scrollbar-track {
            background-color: #1e293b !important;
        }
    </style>
</head>
<body class="bg-[#F8FAF6] text-slate-800 font-body antialiased min-h-screen selection:bg-[#454B1B] selection:text-white">

    <!
    <nav class="border-b border-[#454B1B]/10 sticky top-0 w-full bg-white/95 backdrop-blur-md z-50 shadow-sm">
        <div class="sc-nav-container px-6 lg:px-12 py-4 h-20 flex items-center justify-between">
            <!
            <a href="landing.php" class="flex items-center gap-1 hover:opacity-80 transition-opacity flex-shrink-0">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-12 w-12 object-contain">
                <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-xl font-extrabold tracking-tight text-slate-900">
                    Scan<span style="color: #454B1B;">Care</span>
                </span>
            </a>

            <!
            <div class="nav-links-desktop flex-1 justify-center">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'resident'): ?>
                <a href="dashboard.php#schedule-appointment" style="color: inherit;" class="text-sm font-medium hover:text-[#454B1B] transition-colors">Appointment</a>
                <?php endif; ?>
                <a href="system-goals.php" style="color: inherit;" class="text-sm font-medium hover:text-[#454B1B] transition-colors">System Goals</a>
                <a href="service-areas.php" style="color: inherit;" class="text-sm font-medium hover:text-[#454B1B] transition-colors">Service Areas</a>
                <a href="surveillance.php" style="color: inherit;" class="text-sm font-medium hover:text-[#454B1B] transition-colors">Surveillance</a>
                <a href="compliance.php" style="color: inherit;" class="text-sm font-medium hover:text-[#454B1B] transition-colors">Compliance</a>
            </div>

            <!
            <div class="nav-links-desktop border-l border-slate-200 pl-6 items-center gap-4 relative group flex-shrink-0">
                <!
                <div class="flex items-center gap-3 cursor-pointer py-2 px-3 rounded-lg hover:bg-slate-50 transition-colors group">
                    <!
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #454B1B 0%, #6b7333 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 16px; flex-shrink: 0;">
                        <?php 
                            $fullName = htmlspecialchars($patientData['fullName'] ?? 'User');
                            $initials = implode('', array_map(fn($n) => $n[0] ?? '', explode(' ', $fullName)));
                            echo strtoupper(substr($initials, 0, 2)); 
                        ?>
                    </div>
                    
                    <!
                    <div class="text-right">
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Account</p>
                        <p class="font-semibold text-slate-900 text-sm flex items-center gap-1.5">
                            <?php echo htmlspecialchars(substr($patientData['formalName'] ?? 'User', 0, 25)); ?>
                            <i class="fa-solid fa-chevron-down text-xs opacity-60 transition-transform group-hover:opacity-100"></i>
                        </p>
                    </div>
                </div>

                <!
                <div class="absolute top-full right-0 mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-lg hidden group-hover:block z-50 overflow-hidden">
                    <div class="px-4 py-4 border-b border-slate-100 bg-slate-50">
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-bold mb-1">Patient Account</p>
                        <p class="font-semibold text-slate-900 text-sm mb-2"><?php echo htmlspecialchars($patientData['formalName']); ?></p>
                        <p class="text-xs text-slate-600">ID: <?php echo htmlspecialchars($patientData['patientId']); ?></p>
                    </div>
                    <a href="#profile" class="block px-4 py-3 hover:bg-slate-50 text-sm text-slate-700 font-medium border-b border-slate-100 transition-colors">
                        <i class="fa-solid fa-user-circle mr-2" style="color: #454B1B;"></i> My Profile
                    </a>
                    <a href="#appointments" class="block px-4 py-3 hover:bg-slate-50 text-sm text-slate-700 font-medium border-b border-slate-100 transition-colors">
                        <i class="fa-solid fa-calendar-check mr-2" style="color: #454B1B;"></i> My Appointments
                    </a>
                    <a href="#settings" class="block px-4 py-3 hover:bg-slate-50 text-sm text-slate-700 font-medium border-b border-slate-100 transition-colors">
                        <i class="fa-solid fa-cog mr-2" style="color: #454B1B;"></i> Settings
                    </a>
                    <a href="logout.php" class="block px-4 py-3 hover:bg-red-50 text-sm text-red-600 font-medium transition-colors">
                        <i class="fa-solid fa-sign-out-alt mr-2"></i> Log Out
                    </a>
                </div>
            </div>

            <!
            <button id="toggleBtn" class="nav-hamburger-btn" aria-label="Toggle mobile menu">
                <i class="fa-solid fa-bars" style="color: #475569;"></i>
            </button>
        </div>

        <!
        <div id="mobileMenuPanel" style="display: none;" class="border-t border-slate-200 bg-white flex-col px-6 py-4">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'resident'): ?>
                <a href="dashboard.php#schedule-appointment" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Appointment</a>
                <?php endif; ?>
                <a href="system-goals.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">System Goals</a>
                <a href="service-areas.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Service Areas</a>
                <a href="surveillance.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Surveillance</a>
                <a href="compliance.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Compliance</a>

                <div style="border-top: 1px solid #e2e8f0; margin-top: 12px; padding-top: 12px;">
                    <!
                    <div style="background: linear-gradient(135deg, #454B1B/5 0%, transparent 100%); border: 1px solid #454B1B/10; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #454B1B 0%, #6b7333 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">
                                <?php 
                                    $fullName = htmlspecialchars($patientData['fullName'] ?? 'User');
                                    $initials = implode('', array_map(fn($n) => $n[0] ?? '', explode(' ', $fullName)));
                                    echo strtoupper(substr($initials, 0, 2)); 
                                ?>
                            </div>
                            <div>
                                <p style="font-weight: 600; color: #454B1B; font-size: 14px; margin: 0;"><?php echo htmlspecialchars($patientData['formalName']); ?></p>
                                <p style="font-size: 12px; color: #64748b; margin: 0;">Patient • <?php echo htmlspecialchars($patientData['patientId']); ?></p>
                            </div>
                        </div>
                        <a href="#profile" style="color: #454B1B; padding: 8px 0; text-decoration: none; font-weight: 500; font-size: 14px; display: block; margin-bottom: 8px;"><i class="fa-solid fa-user-circle mr-2"></i> View Profile</a>
                        <a href="#appointments" style="color: #454B1B; padding: 8px 0; text-decoration: none; font-weight: 500; font-size: 14px; display: block; margin-bottom: 8px;"><i class="fa-solid fa-calendar-check mr-2"></i> My Appointments</a>
                        <a href="#settings" style="color: #454B1B; padding: 8px 0; text-decoration: none; font-weight: 500; font-size: 14px; display: block; margin-bottom: 8px;"><i class="fa-solid fa-cog mr-2"></i> Settings</a>
                    </div>
                    <a href="logout.php" style="background-color: #454B1B; color: white; padding: 12px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Log Out</a>
                </div>
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

    <main class="bg-dark-grid w-full">

        <!
        <section class="bg-white border-b border-slate-200 py-8 lg:py-12 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <!
                <div class="mb-8">
                    <h1 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 mb-2">
                        Welcome back, <span style="color: #454B1B;"><?php echo htmlspecialchars($patientData['fullName']); ?></span>!
                    </h1>
                    <p class="text-slate-600 font-medium">Your health portal is ready. Access free services and track your wellness journey.</p>
                </div>

                <!
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!
                    <div class="bg-gradient-to-br from-[#454B1B]/5 to-transparent border border-[#454B1B]/20 rounded-xl p-6 hover:border-[#454B1B]/40 transition-colors">
                        <p class="text-xs uppercase tracking-wider font-bold text-slate-500 mb-2">Patient ID</p>
                        <p class="text-2xl font-heading font-bold text-[#454B1B] mb-1"><?php echo htmlspecialchars($patientData['patientId']); ?></p>
                        <p class="text-xs text-slate-600">Your unique health identifier</p>
                    </div>

                    <!
                    <div class="bg-gradient-to-br from-emerald-50 to-transparent border border-emerald-200 rounded-xl p-6 hover:border-emerald-300 transition-colors">
                        <p class="text-xs uppercase tracking-wider font-bold text-emerald-600 mb-2">Account Status</p>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-block w-2.5 h-2.5 bg-emerald-500 rounded-full"></span>
                            <p class="text-lg font-heading font-bold text-emerald-700"><?php echo htmlspecialchars($patientData['accountStatus']); ?></p>
                        </div>
                        <p class="text-xs text-slate-600">Full access to services</p>
                    </div>

                    <!
                    <div class="bg-gradient-to-br from-orange-50 to-transparent border border-orange-200 rounded-xl p-6 hover:border-orange-300 transition-colors">
                        <p class="text-xs uppercase tracking-wider font-bold text-orange-600 mb-2">Emergency Contact</p>
                        <p class="text-sm font-heading font-bold text-slate-900 mb-1"><?php echo htmlspecialchars($patientData['emergencyContact']); ?></p>
                        <p class="text-xs text-slate-600"><?php echo htmlspecialchars($patientData['emergencyPhone']); ?></p>
                    </div>

                    <!
                    <div class="bg-gradient-to-br from-blue-50 to-transparent border border-blue-200 rounded-xl p-6 hover:border-blue-300 transition-colors">
                        <p class="text-xs uppercase tracking-wider font-bold text-blue-600 mb-2">Service Area</p>
                        <p class="text-sm font-heading font-bold text-slate-900 mb-1">Barangay Gutad</p>
                        <p class="text-xs text-slate-600"><?php echo htmlspecialchars($patientData['purok']); ?></p>
                    </div>
                </div>

                <!
                <div class="bg-gradient-to-r from-[#454B1B]/10 to-[#6b7333]/5 border border-[#454B1B]/20 rounded-xl p-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-1">Your QR Health ID Card</h3>
                        <p class="text-sm text-slate-600">Generate and print your personalized patient ID card with QR code for quick health record access</p>
                    </div>
                    <a href="patient-id-card.php" style="background-color: #454B1B;" class="px-6 py-3 text-white rounded-lg font-semibold hover:opacity-90 transition-opacity flex items-center gap-2 flex-shrink-0 ml-4">
                        <i class="fa-solid fa-id-card"></i>
                        View ID Card
                    </a>
                </div>
            </div>
        </section>

        <!
        <section class="py-16 lg:py-20 bg-gradient-to-br from-slate-50 to-blue-50 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <!
                <div class="mb-12 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Health Services in Action
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed max-w-2xl">
                        See how our health workers are making a difference in Barangay Gutad through dedicated community health initiatives and patient care.
                    </p>
                </div>

                <!
                <div class="grid md:grid-cols-2 gap-8 max-w-5xl">
                    <!
                    <div class="group overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-slate-200 bg-white">
                        <div class="relative h-80 md:h-96 overflow-hidden bg-slate-100">
                            <img 
                                src="images/bhw-community-outreach.jpg" 
                                alt="BHW Community Outreach - Health workers with community members"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            >
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-heading font-bold text-slate-900 mb-2">Community Health Outreach</h3>
                            <p class="text-slate-600 text-sm">Our BHW team providing health education and services directly to community members, ensuring accessible healthcare for all residents.</p>
                        </div>
                    </div>

                    <!
                    <div class="group overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-slate-200 bg-white">
                        <div class="relative h-80 md:h-96 overflow-hidden bg-slate-100">
                            <img 
                                src="images/medical-consultation.jpg" 
                                alt="Health Worker Medical Consultation - Professional patient care"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            >
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-heading font-bold text-slate-900 mb-2">Professional Medical Care</h3>
                            <p class="text-slate-600 text-sm">Our qualified health professionals delivering expert consultations and personalized patient care to ensure optimal health outcomes.</p>
                        </div>
                    </div>
                </div>

                <!
                <div class="mt-12 bg-white border border-[#454B1B]/20 rounded-2xl p-8 text-center">
                    <h3 class="text-2xl font-heading font-bold text-slate-900 mb-3">Ready to Experience Our Services?</h3>
                    <p class="text-slate-600 mb-6 max-w-2xl mx-auto">Schedule your free health appointment today and join thousands of residents benefiting from quality healthcare at Barangay Gutad Health Center.</p>
                    <a href="#services" style="background-color: #454B1B;" class="inline-block px-8 py-3 text-white rounded-lg font-semibold hover:opacity-90 transition-opacity">
                        <i class="fa-solid fa-calendar-check mr-2"></i>
                        Book Your Appointment
                    </a>
                </div>
            </div>
        </section>

        <!
        <section class="py-16 lg:py-20 bg-gradient-to-b from-[#F8FAF6] to-white relative z-20" id="services">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <!
                <div class="mb-12 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Free Health Services at Your RHU
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed max-w-2xl">
                        Book your free appointment with our health professionals. All services are provided at no cost to residents of Barangay Gutad.
                    </p>
                </div>

                <!
                <div class="grid md:grid-cols-3 gap-6">
                    <?php foreach ($services as $service): ?>
                    <div class="service-card bg-white border border-slate-100 rounded-xl overflow-hidden shadow-sm">
                        <!
                        <div style="background: linear-gradient(135deg, #454B1B 0%, #6a7130 100%);" class="h-24 flex items-center justify-center text-white text-5xl">
                            <i class="fa-solid <?php echo $service['icon']; ?>"></i>
                        </div>

                        <!
                        <div class="p-6">
                            <h3 class="text-xl font-heading font-bold text-slate-900 mb-3"><?php echo htmlspecialchars($service['title']); ?></h3>
                            <p class="text-slate-600 text-sm leading-relaxed mb-6"><?php echo htmlspecialchars($service['description']); ?></p>
                            
                            <!
                            <button 
                                onclick="openAppointmentModal('<?php echo $service['id']; ?>', '<?php echo htmlspecialchars($service['title']); ?>')"
                                style="background-color: #454B1B;"
                                class="w-full py-2.5 text-white rounded-lg font-semibold hover:opacity-90 transition-opacity flex items-center justify-center gap-2"
                            >
                                <i class="fa-solid fa-calendar-check"></i>
                                Book Appointment
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!
        <section class="py-16 lg:py-20 bg-white border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <!
                <div class="mb-12 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Free Medicine Availability Tracker
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed max-w-2xl">
                        Check real-time availability of free essential medicines at Barangay Gutad Health Center pharmacy. Claim your prescribed medicines by presenting this screen with your Patient ID.
                    </p>
                </div>

                <!
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-[#454B1B]/20">
                                <th class="px-4 py-3 text-left font-heading font-bold text-slate-900">Medicine Name</th>
                                <th class="px-4 py-3 text-left font-heading font-bold text-slate-900 hidden md:table-cell">Quantity Available</th>
                                <th class="px-4 py-3 text-left font-heading font-bold text-slate-900">Availability Status</th>
                                <th class="px-4 py-3 text-left font-heading font-bold text-slate-900 hidden lg:table-cell">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($medicines as $medicine): 
                                $badgeColor = $medicine['badge'] === 'emerald' 
                                    ? 'bg-emerald-50 border-emerald-200 text-emerald-700' 
                                    : 'bg-amber-50 border-amber-200 text-amber-700';
                                $badgeIcon = $medicine['badge'] === 'emerald' ? 'fa-check-circle' : 'fa-exclamation-circle';
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 font-semibold text-slate-900"><?php echo htmlspecialchars($medicine['name']); ?></td>
                                <td class="px-4 py-4 text-slate-600 hidden md:table-cell">
                                    <span class="inline-block bg-slate-100 px-3 py-1 rounded-full text-xs font-medium text-slate-700"><?php echo $medicine['quantity']; ?> units</span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-semibold <?php echo $badgeColor; ?>">
                                        <i class="fa-solid <?php echo $badgeIcon; ?>"></i>
                                        <?php echo htmlspecialchars($medicine['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 hidden lg:table-cell">
                                    <button class="text-[#454B1B] hover:text-[#454B1B]/80 font-semibold text-sm flex items-center gap-1 transition-colors">
                                        <i class="fa-solid fa-info-circle"></i>
                                        Learn More
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!
                <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <h3 class="text-lg font-heading font-bold text-blue-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-lightbulb text-blue-600"></i>
                        How to Claim Your Free Medicines
                    </h3>
                    <ol class="space-y-3 text-sm text-blue-800">
                        <li class="flex gap-3">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-200 text-blue-900 font-bold text-xs flex-shrink-0">1</span>
                            <span>Visit Barangay Gutad Health Center pharmacy during operating hours (Mon-Fri: 8 AM - 5 PM, Sat: 8 AM - 12 PM)</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-200 text-blue-900 font-bold text-xs flex-shrink-0">2</span>
                            <span>Show this ScanCare portal screen with your unique Patient ID (<?php echo htmlspecialchars($patientData['patientId']); ?>)</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-200 text-blue-900 font-bold text-xs flex-shrink-0">3</span>
                            <span>Present your doctor's prescription or health worker recommendation</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-200 text-blue-900 font-bold text-xs flex-shrink-0">4</span>
                            <span>Pharmacy staff will dispense your prescribed medicines at no cost</span>
                        </li>
                    </ol>
                </div>
            </div>
        </section>

    </main>

    <!
    <div id="appointmentModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-[100] p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full slide-in max-h-[90vh] overflow-y-auto">
            <!
            <div style="background: linear-gradient(135deg, #454B1B 0%, #6a7130 100%);" class="px-6 py-6 text-white flex items-center justify-between sticky top-0 z-10">
                <div>
                    <h2 class="text-2xl font-heading font-bold">Book Your Appointment</h2>
                    <p class="text-white/90 text-sm mt-1">Schedule your free consultation at Barangay Gutad Health Center</p>
                </div>
                <button onclick="closeAppointmentModal()" class="p-1 hover:bg-white/20 rounded-lg transition-colors flex-shrink-0 ml-4">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <!
            <div class="p-6 space-y-6">
                <!
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-3">Service Type *</label>
                    <input 
                        type="text" 
                        id="serviceType" 
                        readonly
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-slate-50 text-slate-700 font-medium"
                    >
                </div>

                <!
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-3">Preferred Date *</label>
                    <input 
                        type="date" 
                        id="appointmentDate"
                        required
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent"
                    >
                </div>

                <!
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-3">Preferred Time Slot *</label>
                    <select id="appointmentTime" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent">
                        <option value="">Select time slot...</option>
                        <option value="08:00">08:00 AM</option>
                        <option value="08:30">08:30 AM</option>
                        <option value="09:00">09:00 AM</option>
                        <option value="09:30">09:30 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="10:30">10:30 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="13:00">01:00 PM</option>
                        <option value="13:30">01:30 PM</option>
                        <option value="14:00">02:00 PM</option>
                        <option value="14:30">02:30 PM</option>
                        <option value="15:00">03:00 PM</option>
                        <option value="15:30">03:30 PM</option>
                        <option value="16:00">04:00 PM</option>
                    </select>
                </div>

                <!
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-3">Reason for Visit / Main Symptoms *</label>
                    <textarea 
                        id="visitReason"
                        required
                        rows="4"
                        placeholder="Briefly describe your symptoms or reason for consultation (e.g., 'Regular check-up', 'Persistent headache', 'Blood pressure monitoring')"
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent resize-none"
                    ></textarea>
                </div>

                <!
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-900 flex items-start gap-3">
                        <i class="fa-solid fa-bell-ring text-blue-600 mt-0.5 flex-shrink-0"></i>
                        <span><strong>SMS Notification:</strong> By confirming, you will receive an automated text message (SMS) once your appointment status is approved and confirmed by the health workers at <?php echo htmlspecialchars($patientData['emergencyPhone']); ?>.</span>
                    </p>
                </div>

                <!
                <div class="flex gap-3 pt-4 border-t border-slate-200">
                    <button 
                        onclick="closeAppointmentModal()"
                        type="button"
                        class="flex-1 px-6 py-3 border-2 border-slate-300 text-slate-900 rounded-lg font-semibold hover:bg-slate-50 transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        onclick="submitAppointment()"
                        type="button"
                        style="background-color: #454B1B;"
                        class="flex-1 px-6 py-3 text-white rounded-lg font-semibold hover:opacity-90 transition-opacity flex items-center justify-center gap-2"
                    >
                        <i class="fa-solid fa-check"></i>
                        Confirm Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>

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

    <script>
        let selectedService = {};

        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Resident Portal loaded. Initializing modal as hidden.');
            
            
            const modal = document.getElementById('appointmentModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                console.log('✓ Modal visibility confirmed as HIDDEN on page load');
            }
            
            
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('appointmentDate');
            if (dateInput) {
                dateInput.min = today;
            }
        });

        
        function openAppointmentModal(serviceId, serviceTitle) {
            console.log('📋 Opening appointment modal for service:', serviceTitle);
            
            selectedService = { id: serviceId, title: serviceTitle };
            document.getElementById('serviceType').value = serviceTitle;
            
            const modal = document.getElementById('appointmentModal');
            
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            
            
            document.body.style.overflow = 'hidden';
            
            console.log('✓ Modal is now VISIBLE');
        }

        
        function closeAppointmentModal() {
            console.log('✕ Closing appointment modal');
            
            const modal = document.getElementById('appointmentModal');
            
            modal.classList.add('hidden');
            modal.style.display = 'none';
            
            
            document.body.style.overflow = 'auto';
            
            
            document.getElementById('appointmentDate').value = '';
            document.getElementById('appointmentTime').value = '';
            document.getElementById('visitReason').value = '';
            
            console.log('✓ Modal is now HIDDEN');
        }

        
        function submitAppointment() {
            const date = document.getElementById('appointmentDate').value;
            const time = document.getElementById('appointmentTime').value;
            const reason = document.getElementById('visitReason').value;

            
            if (!date || !time || !reason.trim()) {
                alert('Please fill in all required fields.');
                return;
            }

            console.log('✉️ Submitting appointment:', { date, time, reason });

            
            const formData = new FormData();
            formData.append('patient_id', '<?php echo htmlspecialchars($patientData['patientId']); ?>');
            formData.append('patient_name', '<?php echo htmlspecialchars($patientData['fullName']); ?>');
            formData.append('patient_phone', '<?php echo htmlspecialchars($patientData['emergencyPhone']); ?>');
            formData.append('service_id', selectedService.id);
            formData.append('service_title', selectedService.title);
            formData.append('appointment_date', date);
            formData.append('appointment_time', time);
            formData.append('visit_reason', reason);

            
            fetch('submit-appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('✓ Appointment submission response:', data);
                if (data.success) {
                    alert('Appointment booked successfully! You will receive an SMS confirmation shortly.');
                    closeAppointmentModal();
                    
                } else {
                    alert('Error booking appointment: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('✗ Error:', error);
                alert('Error submitting appointment. Please try again.');
            });
        }

        
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('appointmentModal');
            if (modal && e.target === modal) {
                closeAppointmentModal();
            }
        });
    </script>

</body>
</html>

