<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Goals — ScanCare</title>
    <script src="theme-state.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="stylesheet" href="global-theme.css">
    <link rel="stylesheet" href="hero.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .grid-canvas {
            background-color: #f8fafc;
            background-image:
                linear-gradient(to right, rgba(69, 75, 27, 0.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(69, 75, 27, 0.08) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        html.dark .grid-canvas,
        body.dark-mode .grid-canvas {
            background-color: #0d1310;
            background-image: linear-gradient(to right, rgba(69, 75, 27, 0.2) 1px, transparent 1px), linear-gradient(to bottom, rgba(69, 75, 27, 0.2) 1px, transparent 1px);
        }

        html.dark .bg-white,
        html.dark [class*="bg-white/"],
        body.dark-mode .bg-white,
        body.dark-mode [class*="bg-white/"] {
            background-color: #131c17 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        html.dark .text-slate-900,
        html.dark .text-slate-800,
        body.dark-mode .text-slate-900,
        body.dark-mode .text-slate-800 {
            color: #f8fafc !important;
        }

        html.dark .text-slate-600,
        html.dark .text-slate-500,
        body.dark-mode .text-slate-600,
        body.dark-mode .text-slate-500 {
            color: #94a3b8 !important;
        }

        body .text-\[\#454B1B\] {
            color: #a3b14e !important;
        }

        html.dark,
        html.dark body,
        body.dark-mode {
            background-color: #0b0f0a !important;
            color: #f8fafc !important;
        }

        html.dark .grid-canvas,
        body.dark-mode .grid-canvas {
            background-color: #0b0f0a !important;
        }

        html.dark .bg-white,
        html.dark [class*="bg-white/"],
        body.dark-mode .bg-white,
        body.dark-mode [class*="bg-white/"] {
            background-color: #141b14 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        html.dark main [class*="bg-slate-50"],
        html.dark main [class*="bg-slate-100"],
        body.dark-mode main [class*="bg-slate-50"],
        body.dark-mode main [class*="bg-slate-100"] {
            background-color: #1b251b !important;
        }

        html.dark main .border-slate-200,
        body.dark-mode main .border-slate-200 {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        html.dark footer,
        body.dark-mode footer {
            background-color: #111827 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        html.dark main > section,
        body.dark-mode main > section {
            border-color: #2f3d2f !important;
        }

        html.dark #how-achieve-goals,
        body.dark-mode #how-achieve-goals {
            background-color: #0b0f0a !important;
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px) !important;
            color: #e5e7eb !important;
        }

        html.dark #how-achieve-goals .how-achieve-card,
        body.dark-mode #how-achieve-goals .how-achieve-card {
            background-color: #141b14 !important;
            border-color: #2f3d2f !important;
        }

        html.dark #how-achieve-goals .how-achieve-card h3,
        body.dark-mode #how-achieve-goals .how-achieve-card h3 {
            color: #f8fafc !important;
        }

        html.dark #how-achieve-goals .how-achieve-card > p,
        body.dark-mode #how-achieve-goals .how-achieve-card > p {
            color: #cbd5e1 !important;
        }

        html.dark #how-achieve-goals .how-achieve-impact,
        body.dark-mode #how-achieve-goals .how-achieve-impact {
            background-color: #1b251b !important;
            border-color: #2f3d2f !important;
            color: #d1d5db !important;
        }

        @media (max-width: 767px) {
            main [class~="bg-white"],
            main [class*="bg-white/"] {
                background-color: #f3f6e8 !important;
                border-color: #dce5c9 !important;
            }

            html.dark main [class~="bg-white"],
            html.dark main [class*="bg-white/"],
            body.dark-mode main [class~="bg-white"],
            body.dark-mode main [class*="bg-white/"] {
                background-color: #1b251b !important;
                border-color: #435332 !important;
            }
        }

        main > section:first-child h1 span,
        main > section:first-child a[class*="bg-[#454B1B]"],
        main > section:first-child [class*="bg-[#454B1B]"] {
            color: #434e1c;
        }

        main > section:first-child a[class*="bg-[#454B1B]"] {
            background-color: #434e1c;
            color: #ffffff;
        }

        main > section:first-child [class*="rounded-full"] {
            color: #434e1c;
            border-color: rgba(67, 78, 28, 0.28);
            background-color: rgba(67, 78, 28, 0.08);
        }
    </style>
</head>
<body class="grid-canvas bg-slate-50 text-slate-900 dark:bg-[#0a0f0d] dark:text-slate-100 font-body antialiased min-h-screen selection:bg-[#454B1B] selection:text-white flex flex-col justify-between">

    <?php include 'navbar.php'; ?>

    <?php if (false): ?>
    <nav class="border-b border-[#454B1B]/10 sticky top-0 w-full bg-white/80 backdrop-blur-md z-50 shadow-xs">
        <div class="sc-nav-container px-6 lg:px-12 py-3.5 h-20 flex items-center justify-between max-w-7xl">
            <a href="landing.php" class="flex items-center gap-2.5 hover:opacity-90 transition-opacity flex-shrink-0">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-10 w-10 object-contain">
                <span class="font-heading text-2xl font-extrabold tracking-tight text-slate-900 leading-none">
                    Scan<span style="color: #454B1B;">Care</span>
                </span>
            </a>

            <div class="hidden md:flex items-center gap-2">
                <a href="landing.php" class="nav-link text-sm font-medium flex items-center gap-2 <?php echo isNavActive('landing') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-house text-xs"></i>
                    <span>Home</span>
                </a>
            </div>

            <div class="hidden md:flex items-center gap-4 border-l border-slate-200/80 pl-6">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="text-[#454B1B] font-semibold text-sm max-w-[180px] truncate hover:opacity-80 transition-opacity" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>">
                        <i class="fa-regular fa-user-circle mr-1.5 text-base"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </a>
                    <button onclick="openResidentLogoutConfirmModal()" class="bg-[#454B1B] hover:bg-[#383d16] text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition-all shadow-xs hover:shadow-md">Log Out</button>
                <?php else: ?>
                    <a href="login.php" class="bg-[#454B1B] hover:bg-[#383d16] text-white text-xs font-semibold px-5 py-2.5 rounded-lg transition-all shadow-xs hover:shadow-md flex items-center gap-2">
                        <span>Login / Signup</span>
                        <i class="fa-solid fa-arrow-right-to-bracket text-xs"></i>
                    </a>
                <?php endif; ?>
            </div>

            <button id="toggleBtn" class="md:hidden p-2 rounded-lg text-slate-600 hover:text-[#454B1B] hover:bg-[#454B1B]/5 transition-colors" aria-label="Toggle mobile menu">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </div>

        <div id="mobileMenuPanel" class="hidden border-t border-slate-200/80 bg-white/95 backdrop-blur-md px-6 py-4 flex-col gap-3 md:hidden">
            <a href="landing.php" class="p-3 text-sm font-semibold rounded-lg flex items-center gap-2.5 text-slate-700 hover:bg-[#454B1B]/10 hover:text-[#454B1B] transition-colors">
                <i class="fa-solid fa-house text-sm"></i>
                <span>Home</span>
            </a>
            <div class="border-t border-slate-200/80 pt-3 mt-1">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="p-3 text-sm font-semibold text-[#454B1B] block truncate">
                            <i class="fa-regular fa-user-circle mr-2"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                        </a>
                        <button onclick="openResidentLogoutConfirmModal()" class="w-full mt-2 bg-[#454B1B] text-white py-2.5 text-xs font-semibold rounded-lg text-center">Log Out</button>
                    <?php else: ?>
                        <a href="login.php" class="w-full bg-[#454B1B] text-white py-2.5 text-xs font-semibold rounded-lg text-center block">Login / Signup</a>
                    <?php endif; ?>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggleBtn');
            const mobileMenuPanel = document.getElementById('mobileMenuPanel');
            if (toggleBtn && mobileMenuPanel) {
                toggleBtn.addEventListener('click', function () {
                    mobileMenuPanel.classList.toggle('hidden');
                    mobileMenuPanel.classList.toggle('flex');
                });
            }
        });
    </script>

    <main class="bg-dark-grid w-full">

        <!-- Hero Section (Tighter Spacing) -->
        <section class="hero-section">
            <div class="hero-animated-dots" aria-hidden="true"></div>
            <div class="hero-glow" aria-hidden="true"></div>
            
            <div class="w-full mx-auto px-6 lg:px-12 relative z-20 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                <div class="lg:col-span-6 flex flex-col justify-center">
                    <div class="hero-badge">
                        <span class="hero-badge-dot animate-pulse" aria-hidden="true"></span>
                        Strategic Vision &amp; System Framework
                    </div>

                    <h1 class="hero-title">
                        Transforming Healthcare Through Digital Excellence
                    </h1>

                    <p class="hero-subtitle">
                        ScanCare's system goals are built on a foundation of eliminating inefficiencies, ensuring data accuracy, and empowering healthcare workers with real-time insights to better serve the Barangay Gutad community.
                    </p>

                    <div class="pt-1 flex flex-wrap gap-4 items-center">
                        <a href="#core-objectives" class="hero-btn">
                            <span>Explore Objectives</span>
                            <i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-6 bg-white/80 backdrop-blur-md p-8 rounded-3xl border border-slate-200/80 shadow-xl shadow-[#454B1B]/5 space-y-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Core Framework Pillars</h3>
                    <div class="flex items-center gap-4">
                        <div style="background-color: #434e1c !important;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl flex-shrink-0">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-heading font-bold text-slate-900">Data-Driven Decisions</h3>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5 leading-snug">Real-time analytics for informed healthcare planning and resource deployment.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div style="background-color: #434e1c !important;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl flex-shrink-0">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-heading font-bold text-slate-900">Secure &amp; Compliant</h3>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5 leading-snug">Full RA 10173 Data Privacy Act compliance with end-to-end encryption.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div style="background-color: #434e1c !important;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl flex-shrink-0">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-heading font-bold text-slate-900">Community Centered</h3>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5 leading-snug">Intuitive interfaces designed specifically for local health workers and residents.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Core Objectives Section (Tighter Spacing) -->
        <section id="core-objectives" class="pt-6 pb-16 bg-transparent border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-10 space-y-2">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Five Core System Objectives
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        Every feature in ScanCare is engineered to address a specific healthcare challenge at Barangay Gutad.
                    </p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-5">

                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 hover:border-[#454B1B]/40 shadow-xs hover:shadow-md transition-all group">
                        <div style="background-color: #454B1B;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-lg mb-4 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <h3 class="text-base font-heading font-bold text-slate-900 mb-2">Reduce Wait Times</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Automated patient check-ins slash clinic queues from hours to minutes, improving resident experience and staff efficiency.</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 hover:border-[#454B1B]/40 shadow-xs hover:shadow-md transition-all group">
                        <div style="background-color: #454B1B;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-lg mb-4 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                        <h3 class="text-base font-heading font-bold text-slate-900 mb-2">Reduce Paper Dependency</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Digitize fragmented logbooks into a unified, searchable archive with instant historical access.</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 hover:border-[#454B1B]/40 shadow-xs hover:shadow-md transition-all group">
                        <div style="background-color: #454B1B;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-lg mb-4 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-vial"></i>
                        </div>
                        <h3 class="text-base font-heading font-bold text-slate-900 mb-2">Prevent Stock Outs</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Real-time inventory alerts ensure critical medicines and supplies are always available before shortages impact patient care.</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 hover:border-[#454B1B]/40 shadow-xs hover:shadow-md transition-all group">
                        <div style="background-color: #454B1B;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-lg mb-4 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <h3 class="text-base font-heading font-bold text-slate-900 mb-2">Optimize Resource Distribution</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Data-driven mapping identifies high-need zones, ensuring aid and medical missions reach the most vulnerable populations first.</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 hover:border-[#454B1B]/40 shadow-xs hover:shadow-md transition-all group">
                        <div style="background-color: #454B1B;" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-lg mb-4 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-virus"></i>
                        </div>
                        <h3 class="text-base font-heading font-bold text-slate-900 mb-2">Enable Outbreak Detection</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Epidemiological monitoring automatically flags disease clusters, enabling rapid response to emerging health threats.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How We Achieve Section -->
        <section id="how-achieve-goals" style="background-color: #ffffff; background-image: linear-gradient(to right, rgba(0, 0, 0, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(0, 0, 0, 0.05) 1px, transparent 1px); background-size: 24px 24px;" class="py-16 text-slate-900 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-12 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        How We Achieve Our Goals
                    </h2>
                </div>

                <div class="grid lg:grid-cols-2 gap-12">

                    <div class="how-achieve-card bg-white border border-slate-200 rounded-xl p-8 shadow-sm hover:border-slate-300 transition-colors">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="font-heading text-2xl font-bold text-white bg-[#434e1c] h-10 w-10 rounded-lg flex items-center justify-center">1</span>
                            <h3 class="text-xl font-heading font-bold text-slate-900">Automated Patient Registration</h3>
                        </div>
                        <p class="text-slate-700 leading-relaxed mb-4">
                            New residents are registered once via QR-linked profiles. Health workers can instantly pull patient histories, medications, and prior consultations without manual lookups. This single touchpoint eliminates redundant paperwork and human data entry errors.
                        </p>
                        <div class="how-achieve-impact bg-slate-100 border border-slate-200 rounded-lg p-4 text-sm text-slate-700">
                            <strong>Expected Impact:</strong> 70% reduction in check-in time from average 15 minutes to ~4 minutes per patient.
                        </div>
                    </div>

                    <div class="how-achieve-card bg-white border border-slate-200 rounded-xl p-8 shadow-sm hover:border-slate-300 transition-colors">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="font-heading text-2xl font-bold text-white bg-[#434e1c] h-10 w-10 rounded-lg flex items-center justify-center">2</span>
                            <h3 class="text-xl font-heading font-bold text-slate-900">Digital Record Permanence</h3>
                        </div>
                        <p class="text-slate-700 leading-relaxed mb-4">
                            Every diagnostic result, medication dispensary, and consultation note is stored in a centralized, encrypted database. Historical data is instantly retrievable, timestamped, and audit-logged for compliance and care continuity.
                        </p>
                        <div class="how-achieve-impact bg-slate-100 border border-slate-200 rounded-lg p-4 text-sm text-slate-700">
                            <strong>Expected Impact:</strong> 100% record accuracy with zero paper document loss or deterioration.
                        </div>
                    </div>

                    <div class="how-achieve-card bg-white border border-slate-200 rounded-xl p-8 shadow-sm hover:border-slate-300 transition-colors">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="font-heading text-2xl font-bold text-white bg-[#434e1c] h-10 w-10 rounded-lg flex items-center justify-center">3</span>
                            <h3 class="text-xl font-heading font-bold text-slate-900">Real-Time Pharmacy Audits</h3>
                        </div>
                        <p class="text-slate-700 leading-relaxed mb-4">
                            Every medicine dosage dispensed is logged. The system calculates consumption rates and automatically alerts BHW supervisors when critical thresholds are reached, allowing advance procurement orders to prevent sudden stockouts.
                        </p>
                        <div class="how-achieve-impact bg-slate-100 border border-slate-200 rounded-lg p-4 text-sm text-slate-700">
                            <strong>Expected Impact:</strong> Zero unplanned shortages of essential medicines; improved supply chain predictability.
                        </div>
                    </div>

                    <div class="how-achieve-card bg-white border border-slate-200 rounded-xl p-8 shadow-sm hover:border-slate-300 transition-colors">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="font-heading text-2xl font-bold text-white bg-[#434e1c] h-10 w-10 rounded-lg flex items-center justify-center">4</span>
                            <h3 class="text-xl font-heading font-bold text-slate-900">Epidemiological Intelligence</h3>
                        </div>
                        <p class="text-slate-700 leading-relaxed mb-4">
                            Diagnostic data is aggregated to identify disease clusters, seasonal trends, and at-risk demographics. Barangay officials receive visual dashboards highlighting priority zones for targeted interventions and medical missions.
                        </p>
                        <div class="how-achieve-impact bg-slate-100 border border-slate-200 rounded-lg p-4 text-sm text-slate-700">
                            <strong>Expected Impact:</strong> Faster outbreak detection with 2-week advance warning capability.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- KPI Section -->
        <section class="py-16 bg-transparent border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="text-center max-w-2xl mx-auto mb-10 space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-[#454B1B]">Target Outcomes</div>
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Key Performance Indicators (KPIs)
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base">Quantifiable metrics used to measure the success of ScanCare implementation.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white/90 backdrop-blur-sm p-6 rounded-xl border border-slate-200/80 shadow-xs">
                        <div style="color: #454B1B;" class="text-3xl font-heading font-bold mb-1">70%</div>
                        <p class="text-xs uppercase tracking-wider font-bold text-slate-400 mb-2">Target Reduction</p>
                        <p class="text-sm text-slate-600">Average patient check-in time at clinic.</p>
                    </div>
                    <div class="bg-white/90 backdrop-blur-sm p-6 rounded-xl border border-slate-200/80 shadow-xs">
                        <div style="color: #454B1B;" class="text-3xl font-heading font-bold mb-1">100%</div>
                        <p class="text-xs uppercase tracking-wider font-bold text-slate-400 mb-2">Target Digitization</p>
                        <p class="text-sm text-slate-600">Patient records transitioned to digital format.</p>
                    </div>
                    <div class="bg-white/90 backdrop-blur-sm p-6 rounded-xl border border-slate-200/80 shadow-xs">
                        <div style="color: #454B1B;" class="text-3xl font-heading font-bold mb-1">0</div>
                        <p class="text-xs uppercase tracking-wider font-bold text-slate-400 mb-2">Target Stockouts</p>
                        <p class="text-sm text-slate-600">Unplanned medicine shortages per quarter.</p>
                    </div>

                    <div class="bg-white/90 backdrop-blur-sm p-6 rounded-xl border border-slate-200/80 shadow-xs">
                        <div style="color: #454B1B;" class="text-3xl font-heading font-bold mb-1">RA 10173</div>
                        <p class="text-xs uppercase tracking-wider font-bold text-slate-400 mb-2">Full Compliance</p>
                        <p class="text-sm text-slate-600">Data Privacy Act audited and verified annually.</p>
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
                <div><h4 class="font-semibold mb-4">Contact</h4><p class="text-slate-400 text-sm">Floridablanca RHU II - GUTAD<br>Email: floridablancarhu2@gmail.com<br>Contact: 0948 056 6872</p></div>
            </div>
            <div class="border-t border-slate-800 pt-8 text-center text-slate-400 text-sm"><p>&copy; 2026 ScanCare. All rights reserved. Your data is encrypted and secure.</p></div>
        </div>
    </footer>

    <div id="residentLogoutConfirmModal" style="display: none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div style="background-color: white; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); max-width: 384px; width: 100%; border: 1px solid #e2e8f0;">

            <div style="background-color: #f8fafc; padding: 24px 24px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 18px; font-weight: 600; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-exclamation-circle" style="color: #eab308;"></i> Confirm Logout
                </h2>
                <button onclick="closeResidentLogoutConfirmModal()" style="background: none; border: none; cursor: pointer; font-size: 20px; color: #94a3b8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="padding: 24px;">
                <p style="color: #64748b; text-align: center; margin: 0 0 24px 0;">
                    Are you sure you want to log out? You will need to log in again to access your account.
                </p>
                
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

    <script>
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

</body>
</html>