<?php
session_start();


function isNavActive($page) {
    $currentFile = basename($_SERVER['PHP_SELF']);
    if ($page === 'appointment' && $currentFile === 'dashboard.php') return true;
    if ($page === 'system-goals' && $currentFile === 'system-goals.php') return true;
    if ($page === 'service-areas' && $currentFile === 'service-areas.php') return true;
    if ($page === 'user-portals' && $currentFile === 'user-portals.php') return true;
    if ($page === 'surveillance' && $currentFile === 'surveillance.php') return true;
    if ($page === 'compliance' && $currentFile === 'compliance.php') return true;
    if ($page === 'landing' && $currentFile === 'landing.php') return true;
    return false;
}
?>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portals — ScanCare</title>
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
            width: 100%;
            margin: 0 auto;
        }

        
        .nav-links-desktop {
            display: none !important;
            position: relative;
        }
        .nav-hamburger-btn {
            display: block !important;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            padding: 8px;
        }

        .nav-active-pill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0;
            border-radius: 999px;
            background-color: rgba(69, 75, 27, 0.12);
            transform: translateX(0);
            transition: transform 0.35s cubic-bezier(0.25, 1, 0.5, 1), width 0.35s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.35s ease;
            will-change: transform, width;
            z-index: 0;
            opacity: 0;
            pointer-events: none;
            view-transition-name: nav-pill;
            view-transition: cross-fade(250ms ease-in-out);
        }

        
        @media (min-width: 768px) {
            .nav-links-desktop {
                display: flex !important;
                align-items: center;
                gap: 24px;
                font-weight: 500;
                font-size: 14px;
                color: #475569;
            }
            .nav-hamburger-btn {
                display: none !important;
            }

                display: none !important;
            }
        }

        
        .nav-link {
            position: relative;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            color: #475569;
            text-decoration: none;
        }

        .nav-link:hover {
            color: #454B1B;
            background-color: rgba(69, 75, 27, 0.08);
        }

        .nav-link.active {
            color: #454B1B;
            background-color: rgba(69, 75, 27, 0.12);
            font-weight: 600;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 2px;
            background-color: #454B1B;
            border-radius: 1px;
        }
    </style>
</head>
<body class="bg-[#F8FAF6] text-slate-800 font-body antialiased min-h-screen selection:bg-[#454B1B] selection:text-white">

    <!
    <nav class="border-b border-[#454B1B]/10 sticky top-0 w-full bg-white/90 backdrop-blur-md z-50 shadow-sm">
        <div class="sc-nav-container px-6 lg:px-12 py-4 h-20 flex items-center justify-between">
            <!
            <a href="landing.php" class="flex items-center gap-1 hover:opacity-80 transition-opacity flex-shrink-0">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-12 w-12 object-contain">
                <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-xl font-extrabold tracking-tight text-slate-900">
                    Scan<span style="color: #454B1B;">Care</span>
                </span>
            </a>

            <!
            <div class="nav-links-desktop">
                <span id="nav-indicator" class="nav-active-pill" aria-hidden="true"></span>
                <a href="appointments.php" class="nav-link text-sm font-medium <?php echo isNavActive('appointment') ? 'active' : ''; ?>">Appointment</a>
                <a href="system-goals.php" class="nav-link text-sm font-medium <?php echo isNavActive('system-goals') ? 'active' : ''; ?>">System Goals</a>
                <a href="service-areas.php" class="nav-link text-sm font-medium <?php echo isNavActive('service-areas') ? 'active' : ''; ?>">Service Areas</a>
                <a href="surveillance.php" class="nav-link text-sm font-medium <?php echo isNavActive('surveillance') ? 'active' : ''; ?>">Surveillance</a>
                <a href="compliance.php" class="nav-link text-sm font-medium <?php echo isNavActive('compliance') ? 'active' : ''; ?>">Compliance</a>
            </div>

            <!
            <div class="nav-links-desktop border-l border-slate-200 pl-6 items-center gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" style="color: #454B1B; font-weight: 600; max-width: 200px; white-space: normal; word-wrap: break-word; overflow-wrap: break-word; text-decoration: none; cursor: pointer; transition: opacity 0.2s ease;" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </a>
                    <button onclick="openResidentLogoutConfirmModal()" style="background-color: #454B1B; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; border: none; cursor: pointer;">Log Out</button>
                <?php else: ?>
                    <a href="login.php" style="background-color: #454B1B; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">Login / Signup</a>
                <?php endif; ?>
            </div>

            <!
            <button id="toggleBtn" class="nav-hamburger-btn" aria-label="Toggle mobile menu">
                <i class="fa-solid fa-bars" style="color: #475569;"></i>
            </button>
        </div>

        <!
        <div id="mobileMenuPanel" style="display: none;" class="border-t border-slate-200 bg-white flex-col px-6 py-4">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="appointments.php" class="nav-link text-sm font-medium <?php echo isNavActive('appointment') ? 'active' : ''; ?>">Appointment</a>
                <a href="system-goals.php" class="nav-link text-sm font-medium <?php echo isNavActive('system-goals') ? 'active' : ''; ?>">System Goals</a>
                <a href="service-areas.php" class="nav-link text-sm font-medium <?php echo isNavActive('service-areas') ? 'active' : ''; ?>">Service Areas</a>
                <a href="surveillance.php" class="nav-link text-sm font-medium <?php echo isNavActive('surveillance') ? 'active' : ''; ?>">Surveillance</a>
                <a href="compliance.php" class="nav-link text-sm font-medium <?php echo isNavActive('compliance') ? 'active' : ''; ?>">Compliance</a>
            </div>
            <div style="border-top: 1px solid #e2e8f0; margin-top: 12px; padding-top: 12px;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="nav-user-name" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </a>
                    <a href="logout.php" class="nav-user-action" style="background-color: #454B1B; color: white; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Log Out</a>
                <?php else: ?>
                    <a href="login.php" style="background-color: #454B1B; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Login / Signup</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="bg-dark-grid w-full">

        <!
        <section class="relative w-full min-h-[60vh] flex items-center border-b border-[#454B1B]/10 overflow-hidden py-20">
            <div class="absolute inset-0 z-0 bg-[linear-gradient(to_right,#454b1b05_1px,transparent_1px),linear-gradient(to_bottom,#454b1b05_1px,transparent_1px)] bg-[size:24px_24px]"></div>
            
            <div class="w-full mx-auto px-6 lg:px-12 relative z-20">
                <div class="max-w-3xl mb-12 space-y-3">
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold bg-[#454B1B]/10 text-[#454B1B] border border-[#454B1B]/20 px-3 py-1 rounded-full tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#454B1B] animate-pulse"></span>
                        Role-Based Access
                    </div>
                    <h1 class="text-4xl lg:text-5xl xl:text-6xl font-heading font-extrabold text-slate-900 leading-[1.1] tracking-tight">
                        User Portals & <span style="color: #454B1B;">Role-Specific Dashboards</span>
                    </h1>
                    <p class="text-base text-slate-600 font-medium max-w-2xl leading-relaxed">
                        ScanCare provides dedicated, contextually-optimized interfaces for each user role, ensuring intuitive access to relevant data and tools without overwhelming users with unnecessary features.
                    </p>
                </div>
            </div>
        </section>

        <!
        <section class="py-20 bg-white border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Complete Portal Ecosystem
                    </h2>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-[#F8FAF6] p-6 rounded-2xl border border-slate-100 flex flex-col justify-between shadow-sm">
                        <div>
                            <div style="color: #454B1B;" class="text-xs font-bold uppercase tracking-wider mb-2">Portal 01</div>
                            <h4 class="text-lg font-heading font-bold text-slate-900 mb-2">Residents & Patients</h4>
                            <p class="text-slate-600 text-xs leading-relaxed">Access personal QR health IDs, track consultation histories, and review appointment schedules.</p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/60 pt-4 flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <i style="color: #454B1B;" class="fa-solid fa-user"></i> Self-Service Access
                        </div>
                    </div>

                    <div class="bg-[#F8FAF6] p-6 rounded-2xl border border-slate-100 flex flex-col justify-between shadow-sm">
                        <div>
                            <div style="color: #454B1B;" class="text-xs font-bold uppercase tracking-wider mb-2">Portal 02</div>
                            <h4 class="text-lg font-heading font-bold text-slate-900 mb-2">Barangay Health Workers</h4>
                            <p class="text-slate-600 text-xs leading-relaxed">Real-time patient check-ins, vital signs logging, medicine dispensary tracking, and pharmacy alerts.</p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/60 pt-4 flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <i style="color: #454B1B;" class="fa-solid fa-stethoscope"></i> Operational Tools
                        </div>
                    </div>

                    <div class="bg-[#F8FAF6] p-6 rounded-2xl border border-slate-100 flex flex-col justify-between shadow-sm">
                        <div>
                            <div style="color: #454B1B;" class="text-xs font-bold uppercase tracking-wider mb-2">Portal 03</div>
                            <h4 class="text-lg font-heading font-bold text-slate-900 mb-2">Barangay Officials</h4>
                            <p class="text-slate-600 text-xs leading-relaxed">Access visual epidemiology charts, map resource distribution trends, and evaluate community health metrics.</p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/60 pt-4 flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <i style="color: #454B1B;" class="fa-solid fa-chart-line"></i> Strategic Insights
                        </div>
                    </div>

                    <div class="bg-[#F8FAF6] p-6 rounded-2xl border border-slate-100 flex flex-col justify-between shadow-sm">
                        <div>
                            <div style="color: #454B1B;" class="text-xs font-bold uppercase tracking-wider mb-2">Portal 04</div>
                            <h4 class="text-lg font-heading font-bold text-slate-900 mb-2">System Administrators</h4>
                            <p class="text-slate-600 text-xs leading-relaxed">Manage access matrices, regulate authentication policies, audit transactions, and oversee system integrity.</p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/60 pt-4 flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <i style="color: #454B1B;" class="fa-solid fa-gears"></i> Full System Control
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!
        <section style="background-color: #454B1B;" class="py-20 text-white relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-white tracking-tight">
                        Role-Based Access Control (RBAC)
                    </h2>
                    <p class="text-stone-200 text-base leading-relaxed">
                        ScanCare enforces strict permission matrices ensuring users can only access data and features appropriate to their role, protecting patient privacy and system integrity.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="bg-black/20 border border-white/10 rounded-xl p-8 backdrop-blur-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-lg">
                                🔐
                            </div>
                            <h3 class="text-xl font-heading font-bold">Authentication Methods</h3>
                        </div>
                        <ul class="space-y-3 text-stone-100 text-sm">
                            <li class="flex items-start gap-3">
                                <span class="font-bold mt-0.5">•</span>
                                <span><strong>QR Code Scanning:</strong> Residents and health workers via unique health IDs</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="font-bold mt-0.5">•</span>
                                <span><strong>Username/Password:</strong> Standard login with optional 2FA for staff accounts</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="font-bold mt-0.5">•</span>
                                <span><strong>Multi-Factor Auth:</strong> Required for admin and barangay officials</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="font-bold mt-0.5">•</span>
                                <span><strong>Session Management:</strong> Automatic timeout after 30 mins of inactivity</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-black/20 border border-white/10 rounded-xl p-8 backdrop-blur-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-lg">
                                📋
                            </div>
                            <h3 class="text-xl font-heading font-bold">Permission Levels</h3>
                        </div>
                        <ul class="space-y-3 text-stone-100 text-sm">
                            <li class="flex items-start gap-3">
                                <span class="font-bold mt-0.5">•</span>
                                <span><strong>View:</strong> Read-only access to assigned data sections</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="font-bold mt-0.5">•</span>
                                <span><strong>Edit:</strong> Ability to create and modify records within scope</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="font-bold mt-0.5">•</span>
                                <span><strong>Delete:</strong> Archive records (permanent deletion requires approval)</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="font-bold mt-0.5">•</span>
                                <span><strong>Admin:</strong> Full system control with audit trail logging</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!
        <section class="py-20 bg-white border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Portal Integration & Data Flow
                    </h2>
                </div>

                <div class="bg-white border border-slate-100 rounded-2xl p-8 shadow-sm">
                    <div class="space-y-6">
                        <div class="flex gap-6 items-start">
                            <div style="background-color: #454B1B;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                1
                            </div>
                            <div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Patient Initiates Check-In</h3>
                                <p class="text-slate-600">Resident arrives at clinic and scans their QR health ID using the tablet at the reception desk.</p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div style="background-color: #454B1B;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                2
                            </div>
                            <div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">System Retrieves Data</h3>
                                <p class="text-slate-600">ScanCare instantly pulls patient history, prior diagnoses, medications, and appointment records into the BHW portal.</p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div style="background-color: #454B1B;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                3
                            </div>
                            <div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Health Worker Records Consultation</h3>
                                <p class="text-slate-600">BHW logs vitals, notes, diagnosis, and any medicines dispensed in real-time. Pharmacy inventory updates automatically.</p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div style="background-color: #454B1B;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                4
                            </div>
                            <div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Data Syncs Across Portals</h3>
                                <p class="text-slate-600">Barangay officials instantly see updated analytics. Residents receive appointment reminders via SMS.</p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div style="background-color: #454B1B;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                5
                            </div>
                            <div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Admins Monitor System Health</h3>
                                <p class="text-slate-600">Administrators verify all transactions logged, backups completed, and security protocols maintained.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

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
        function updateNavbarActivePill() {
            const navWrapper = document.querySelector('.nav-links-desktop');
            const indicator = document.getElementById('nav-indicator');
            const activeLink = navWrapper?.querySelector('.nav-link.active');
            if (!navWrapper || !indicator || !activeLink) return;

            const navRect = navWrapper.getBoundingClientRect();
            const linkRect = activeLink.getBoundingClientRect();
            indicator.style.width = `${linkRect.width}px`;
            indicator.style.height = `${linkRect.height}px`;
            indicator.style.transform = `translateX(${linkRect.left - navRect.left}px)`;
            indicator.style.top = `${linkRect.top - navRect.top}px`;
            indicator.style.opacity = '1';
        }

        function initNavbarIndicator() {
            const navWrapper = document.querySelector('.nav-links-desktop');
            const indicator = document.getElementById('nav-indicator');
            const activeLink = navWrapper?.querySelector('.nav-link.active');
            if (!navWrapper || !indicator || !activeLink) return;

            const containerRect = navWrapper.getBoundingClientRect();
            const activeRect = activeLink.getBoundingClientRect();
            const currentLeft = activeRect.left - containerRect.left;
            const currentWidth = activeRect.width;
            const prevLeft = sessionStorage.getItem('nav_pill_left');
            const prevWidth = sessionStorage.getItem('nav_pill_width');
            const defaultTransition = 'transform 0.35s cubic-bezier(0.25, 1, 0.5, 1), width 0.35s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.35s ease';

            indicator.style.opacity = '1';

            if (prevLeft !== null && prevWidth !== null) {
                indicator.style.transition = 'none';
                indicator.style.transform = `translateX(${prevLeft}px)`;
                indicator.style.width = `${prevWidth}px`;
                indicator.style.height = `${activeRect.height}px`;
                indicator.offsetHeight;
                indicator.style.transition = defaultTransition;
                indicator.style.transform = `translateX(${currentLeft}px)`;
                indicator.style.width = `${currentWidth}px`;
            } else {
                indicator.style.transition = 'none';
                indicator.style.transform = `translateX(${currentLeft}px)`;
                indicator.style.width = `${currentWidth}px`;
                indicator.style.height = `${activeRect.height}px`;
                indicator.offsetHeight;
                indicator.style.transition = defaultTransition;
            }

            navWrapper.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    sessionStorage.setItem('nav_pill_left', currentLeft);
                    sessionStorage.setItem('nav_pill_width', currentWidth);
                });
            });
        }

        let navbarPillResizeTimer = null;

        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggleBtn');
            const mobileMenuPanel = document.getElementById('mobileMenuPanel');

            toggleBtn.addEventListener('click', function () {
                if (mobileMenuPanel.style.display === 'none' || mobileMenuPanel.style.display === '') {
                    mobileMenuPanel.style.display = 'flex';
                } else {
                    mobileMenuPanel.style.display = 'none';
                }
            });

            const navLinks = mobileMenuPanel.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function () {
                    mobileMenuPanel.style.display = 'none';
                });
            });

            initNavbarIndicator();
            window.addEventListener('resize', function () {
                clearTimeout(navbarPillResizeTimer);
                navbarPillResizeTimer = setTimeout(updateNavbarActivePill, 100);
            });
        });

        
        
        
        function openResidentLogoutConfirmModal() {
            document.getElementById('residentLogoutConfirmModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeResidentLogoutConfirmModal() {
            document.getElementById('residentLogoutConfirmModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function confirmResidentLogout() {
            window.location.href = 'logout.php';
        }

        
        document.getElementById('residentLogoutConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeResidentLogoutConfirmModal();
            }
        });

        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('residentLogoutConfirmModal').style.display !== 'none') {
                closeResidentLogoutConfirmModal();
            }
        });
    </script>

    <!
    <div id="residentLogoutConfirmModal" style="display: none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div style="background-color: white; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); max-width: 384px; width: 100%; border: 1px solid #e2e8f0;">
            <!
            <div style="background-color: #f8fafc; padding: 24px 24px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 18px; font-weight: 600; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-exclamation-circle" style="color: #eab308;"></i> Confirm Logout
                </h2>
                <button onclick="closeResidentLogoutConfirmModal()" style="background: none; border: none; cursor: pointer; font-size: 20px; color: #94a3b8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!
            <div style="padding: 24px;">
                <p style="color: #64748b; text-align: center; margin: 0 0 24px 0;">
                    Are you sure you want to log out? You will need to log in again to access your account.
                </p>
                
                <!
                <div style="display: flex; gap: 12px;">
                    <button onclick="closeResidentLogoutConfirmModal()" style="flex: 1; padding: 12px 16px; border-radius: 8px; background-color: #e2e8f0; color: #1e293b; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-times" style="margin-right: 8px;"></i> Cancel
                    </button>
                    <button onclick="confirmResidentLogout()" style="flex: 1; padding: 12px 16px; border-radius: 8px; background-color: #dc2626; color: white; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

