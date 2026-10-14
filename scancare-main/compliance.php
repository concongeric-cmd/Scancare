<?php session_start();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance — ScanCare</title>
    <script src="theme-state.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="stylesheet" href="global-theme.css">
    <link rel="stylesheet" href="hero.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        .compliance-grid-section {
            background-color: transparent;
        }

        .compliance-grid-canvas {
            background-color: #f8fafc;
            background-image:
                linear-gradient(to right, rgba(69, 75, 27, 0.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(69, 75, 27, 0.08) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        html.dark .compliance-grid-canvas,
        body.dark-mode .compliance-grid-canvas {
            background-color: #0d1310;
            background-image: linear-gradient(to right, rgba(69, 75, 27, 0.2) 1px, transparent 1px), linear-gradient(to bottom, rgba(69, 75, 27, 0.2) 1px, transparent 1px);
        }

        html.dark .compliance-grid-canvas [class*="bg-white"],
        html.dark .compliance-grid-canvas [class*="bg-slate-50"],
        body.dark-mode .compliance-grid-canvas [class*="bg-white"],
        body.dark-mode .compliance-grid-canvas [class*="bg-slate-50"] {
            background-color: #141b14 !important;
            border-color: #2f3d2f !important;
        }

        html.dark .compliance-grid-canvas .text-slate-900,
        html.dark .compliance-grid-canvas .text-slate-800,
        body.dark-mode .compliance-grid-canvas .text-slate-900,
        body.dark-mode .compliance-grid-canvas .text-slate-800 {
            color: #f8fafc !important;
        }

        html.dark .compliance-grid-canvas .text-slate-600,
        html.dark .compliance-grid-canvas .text-slate-500,
        body.dark-mode .compliance-grid-canvas .text-slate-600,
        body.dark-mode .compliance-grid-canvas .text-slate-500 {
            color: #94a3b8 !important;
        }

        html.dark .compliance-grid-canvas .text-slate-700,
        body.dark-mode .compliance-grid-canvas .text-slate-700 {
            color: #cbd5e1 !important;
        }

        html.dark .compliance-grid-canvas .text-\[\#454B1B\],
        body.dark-mode .compliance-grid-canvas .text-\[\#454B1B\] {
            color: #a3b14e !important;
        }

        .accordion-input {
            display: none;
        }
        
        .accordion-input:checked + .accordion-label {
            background-color: #454B1B;
            color: white;
        }
        
        .accordion-input:checked ~ .accordion-content {
            max-height: 500px;
        }
        
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background-color: #0d1310;
            color: #94a3b8;
        }

        html.dark .compliance-grid-canvas .accordion-content,
        html.dark .compliance-grid-canvas .accordion-content p,
        html.dark .compliance-grid-canvas .accordion-content li,
        body.dark-mode .compliance-grid-canvas .accordion-content,
        body.dark-mode .compliance-grid-canvas .accordion-content p,
        body.dark-mode .compliance-grid-canvas .accordion-content li {
            color: #e2e8f0 !important;
        }

        html.dark .compliance-accordion-item,
        body.dark-mode .compliance-accordion-item {
            border-color: #2f3d2f !important;
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

        main > section:first-child.hero-section {
            background-color: #435322 !important;
            background-image:
                radial-gradient(circle at 85% 50%, rgba(74, 124, 48, 0.45) 0%, rgba(67, 83, 34, 0) 65%),
                radial-gradient(rgba(255, 255, 255, 0.18) 1px, transparent 1px) !important;
            background-size: 100% 100%, 20px 20px !important;
            background-position: center center, 0 0 !important;
        }

        main > section:first-child.hero-section .hero-badge {
            color: #e2e8f0 !important;
            border-color: rgba(255, 255, 255, 0.18) !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
        }

        main > section:first-child.hero-section .hero-title {
            color: #ffffff !important;
        }

        main > section:first-child.hero-section .hero-subtitle {
            color: rgba(255, 255, 255, 0.82) !important;
        }

        @media (max-width: 640px) {
            .compliance-grid-section .bg-white > div:first-child {
                flex-wrap: wrap;
            }

            .compliance-grid-section .bg-white > div:first-child > span[class*="rounded-full"] {
                max-width: 100%;
                white-space: normal;
                overflow-wrap: anywhere;
                text-align: center;
            }

            .compliance-grid-section .bg-white {
                padding: 1rem;
            }

            .compliance-grid-section .bg-white h3 {
                font-size: 1rem;
                line-height: 1.25;
            }

            .compliance-grid-section .bg-white p,
            .compliance-grid-section .bg-white li,
            .compliance-grid-section .bg-white li span:last-child {
                font-size: 0.75rem;
                line-height: 1.35;
            }

            .compliance-grid-section .bg-white .w-11.h-11 {
                width: 2rem;
                height: 2rem;
            }

            .compliance-grid-section .bg-white .w-11.h-11 svg {
                width: 1rem;
                height: 1rem;
            }

            .compliance-grid-section .bg-white .w-5.h-5 {
                width: 1rem;
                height: 1rem;
            }

            .compliance-grid-section .bg-white .w-5.h-5 svg {
                width: 0.65rem;
                height: 0.65rem;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-[#0a0f0d] dark:text-slate-100 font-body antialiased min-h-screen selection:bg-[#454B1B] selection:text-white">

    <?php include 'navbar.php'; if (false): ?><nav class="border-b border-[#454B1B]/10 sticky top-0 w-full bg-white/90 backdrop-blur-md z-50 shadow-sm">
        <div class="sc-nav-container px-6 lg:px-12 py-4 h-20 flex items-center justify-between">
            <a href="landing.php" class="flex items-center gap-1 hover:opacity-80 transition-opacity flex-shrink-0">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-12 w-12 object-contain">
                <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-xl font-extrabold tracking-tight text-slate-900">
                    <span class="text-white font-bold">Scan</span><span class="text-[#828e34] font-bold">Care</span>
                </span>
            </a>

            <div class="nav-links-desktop">
                <span id="nav-indicator" class="nav-active-pill" aria-hidden="true"></span>
                <a href="appointments.php" class="nav-link text-sm font-medium <?php echo isNavActive('appointment') ? 'active' : ''; ?>">Appointment</a>
                <a href="system-goals.php" class="nav-link text-sm font-medium <?php echo isNavActive('system-goals') ? 'active' : ''; ?>">System Goals</a>
                <a href="service-areas.php" class="nav-link text-sm font-medium <?php echo isNavActive('service-areas') ? 'active' : ''; ?>">Service Areas</a>
                <a href="surveillance.php" class="nav-link text-sm font-medium <?php echo isNavActive('surveillance') ? 'active' : ''; ?>">Surveillance</a>
                <a href="compliance.php" class="nav-link text-sm font-medium <?php echo isNavActive('compliance') ? 'active' : ''; ?>">Compliance</a>
            </div>

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

            <button id="toggleBtn" class="nav-hamburger-btn" aria-label="Toggle mobile menu">
                <i class="fa-solid fa-bars" style="color: #475569;"></i>
            </button>
        </div>

        <div id="mobileMenuPanel" style="display: none;" class="border-t border-slate-200 bg-white flex-col px-6 py-4">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="appointments.php" style="padding: 12px; text-decoration: none; font-weight: 500; border-radius: 6px; transition: all 0.3s ease; <?php echo isNavActive('appointment') ? 'background-color: rgba(69, 75, 27, 0.12); color: #454B1B;' : 'color: #475569;'; ?>" class="mobile-nav-link">Appointment</a>
                <a href="system-goals.php" style="padding: 12px; text-decoration: none; font-weight: 500; border-radius: 6px; transition: all 0.3s ease; <?php echo isNavActive('system-goals') ? 'background-color: rgba(69, 75, 27, 0.12); color: #454B1B;' : 'color: #475569;'; ?>" class="mobile-nav-link">System Goals</a>
                <a href="service-areas.php" style="padding: 12px; text-decoration: none; font-weight: 500; border-radius: 6px; transition: all 0.3s ease; <?php echo isNavActive('service-areas') ? 'background-color: rgba(69, 75, 27, 0.12); color: #454B1B;' : 'color: #475569;'; ?>" class="mobile-nav-link">Service Areas</a>
                <a href="surveillance.php" style="padding: 12px; text-decoration: none; font-weight: 500; border-radius: 6px; transition: all 0.3s ease; <?php echo isNavActive('surveillance') ? 'background-color: rgba(69, 75, 27, 0.12); color: #454B1B;' : 'color: #475569;'; ?>" class="mobile-nav-link">Surveillance</a>
                <a href="compliance.php" style="padding: 12px; text-decoration: none; font-weight: 500; border-radius: 6px; transition: all 0.3s ease; <?php echo isNavActive('compliance') ? 'background-color: rgba(69, 75, 27, 0.12); color: #454B1B;' : 'color: #475569;'; ?>" class="mobile-nav-link">Compliance</a>

                <div style="border-top: 1px solid #e2e8f0; margin-top: 12px; padding-top: 12px;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" style="color: #454B1B; font-weight: 600; margin-bottom: 12px; white-space: normal; word-wrap: break-word; overflow-wrap: break-word; text-decoration: none; cursor: pointer; display: block; transition: opacity 0.2s ease;" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                        </a>
                        <button onclick="openResidentLogoutConfirmModal()" style="background-color: #454B1B; color: white; padding: 12px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center; border: none; cursor: pointer; width: 100%;">Log Out</button>
                    <?php else: ?>
                        <a href="login.php" style="background-color: #454B1B; color: white; padding: 12px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Login / Signup</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav><?php endif; ?>

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
            if (toggleBtn && mobileMenuPanel) {
                toggleBtn.addEventListener('click', function () {
                    mobileMenuPanel.style.display = mobileMenuPanel.style.display === 'none' ? 'flex' : 'none';
                });
            }

            initNavbarIndicator();
            window.addEventListener('resize', function () {
                clearTimeout(navbarPillResizeTimer);
                navbarPillResizeTimer = setTimeout(updateNavbarActivePill, 100);
            });
        });
    </script>

    <main class="bg-dark-grid compliance-grid-canvas grid-canvas w-full">

        <section class="hero-section">
            <div class="hero-animated-dots" aria-hidden="true"></div>
            <div class="hero-glow" aria-hidden="true"></div>

            <div class="hero-content">
                <div class="hero-badge">
                    <span class="hero-badge-dot animate-pulse" aria-hidden="true"></span>
                        Legal & Regulatory Alignment
                </div>
                <h1 class="hero-title">
                    Compliance &amp; Regulatory Framework
                    </h1>
                <p class="hero-subtitle">
                        ScanCare fully adheres to all Philippine healthcare laws, data privacy regulations, and international health standards to ensure legal, ethical, and secure operations.
                    </p>
            </div>
        </section>

        <section class="compliance-grid-section py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Additional Compliance Frameworks
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl p-6 md:p-8 border border-[#DDE7C9] shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-[#EDF3DD] text-[#454B1B] flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
                                        <path d="M8 7.5V6.8A4 4 0 0 1 12 3a4 4 0 0 1 4 3.8v.7"/>
                                        <path d="M7 10.5h10a2 2 0 0 1 2 2V17a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-4.5a2 2 0 0 1 2-2Z"/>
                                        <path d="M12 10v7"/>
                                        <path d="M8.5 13.5h7"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 leading-tight">Drug &amp; Medicine Regulations</h3>
                            </div>
                            <span class="bg-[#EDF3DD] text-[#4F5B1E] text-[10px] px-3 py-1 rounded-full font-semibold whitespace-nowrap">FDA / BFD</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Licensed medicine suppliers only</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Batch tracking with expiration dates</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Cold chain compliance for vaccines</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Adverse event reporting integration</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 md:p-8 border border-[#DDE7C9] shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-[#EDF3DD] text-[#454B1B] flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
                                        <path d="M9 18h6"/>
                                        <path d="M10 21h4"/>
                                        <path d="M8 4.5h8l2 3.5v9.5a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V8l2-3.5Z"/>
                                        <path d="M9 11h6"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 leading-tight">Cybersecurity &amp; IT Standards</h3>
                            </div>
                            <span class="bg-[#EDF3DD] text-[#4F5B1E] text-[10px] px-3 py-1 rounded-full font-semibold whitespace-nowrap">NIST CSF</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">SSL/TLS encryption</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">AES-256 encryption at rest</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Regular penetration testing</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Intrusion detection and monitoring</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 md:p-8 border border-[#DDE7C9] shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-[#EDF3DD] text-[#454B1B] flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
                                        <path d="M5 19V9.5A2.5 2.5 0 0 1 7.5 7H16.5A2.5 2.5 0 0 1 19 9.5V19"/>
                                        <path d="M8 7V5.5A2.5 2.5 0 0 1 10.5 3h3A2.5 2.5 0 0 1 16 5.5V7"/>
                                        <path d="M9.5 13h5"/>
                                        <path d="M12 10v6"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 leading-tight">Disease Reporting Requirements</h3>
                            </div>
                            <span class="bg-[#EDF3DD] text-[#4F5B1E] text-[10px] px-3 py-1 rounded-full font-semibold whitespace-nowrap">DOH Integration</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Real-time epidemiological reporting</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Automated case classification</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Outbreak investigation coordination</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">National surveillance database sync</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 md:p-8 border border-[#DDE7C9] shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-[#EDF3DD] text-[#454B1B] flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
                                        <path d="M5 19V7.5A2.5 2.5 0 0 1 7.5 5H16.5A2.5 2.5 0 0 1 19 7.5V19"/>
                                        <path d="M8 9.5h8"/>
                                        <path d="M8 13h8"/>
                                        <path d="M8 16.5h5"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 leading-tight">Healthcare Quality Standards</h3>
                            </div>
                            <span class="bg-[#EDF3DD] text-[#4F5B1E] text-[10px] px-3 py-1 rounded-full font-semibold whitespace-nowrap">PhilHealth</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Clinical performance monitoring</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Patient safety incident tracking</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Service quality audits</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-slate-700">Accreditation maintenance</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="compliance-grid-section py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Data Protection Policies (Accordion)
                    </h2>
                </div>

                <div class="space-y-4 max-w-4xl">
                    <div class="compliance-accordion-item border border-slate-100 rounded-xl overflow-hidden">
                        <input type="checkbox" id="accordion1" class="accordion-input">
                        <label for="accordion1" class="accordion-label block w-full p-6 cursor-pointer font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <span>Patient Consent & Opt-Out Rights</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </label>
                        <div class="accordion-content bg-slate-50 px-6 pb-6 text-slate-600 text-sm leading-relaxed">
                            <p class="mb-3">
                                Patients provide explicit, informed consent for data processing at registration. This includes consent for:
                            </p>
                            <ul class="space-y-2 mb-3 pl-6 list-disc">
                                <li>Health record digitization and storage</li>
                                <li>Data sharing with authorized healthcare providers</li>
                                <li>Aggregated statistical reporting (with anonymization)</li>
                                <li>Research purposes (approved projects only)</li>
                            </ul>
                            <p>
                                <strong style="color: #454B1B;">Opt-Out Option:</strong> Patients may withdraw consent at any time. Historical data is archived but no new entries are created.
                            </p>
                        </div>
                    </div>

                    <div class="compliance-accordion-item border border-slate-100 rounded-xl overflow-hidden">
                        <input type="checkbox" id="accordion2" class="accordion-input">
                        <label for="accordion2" class="accordion-label block w-full p-6 cursor-pointer font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <span>Data Retention & Deletion Policies</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </label>
                        <div class="accordion-content bg-slate-50 px-6 pb-6 text-slate-600 text-sm leading-relaxed">
                            <p class="mb-3">
                                ScanCare maintains a structured data lifecycle:
                            </p>
                            <ul class="space-y-2 mb-3 pl-6 list-disc">
                                <li><strong>Active Records:</strong> Kept for 5 years with regular access</li>
                                <li><strong>Archive:</strong> Moved to secure backup after 5 years</li>
                                <li><strong>Deletion:</strong> Permanent removal after 10 years (per RA 10173)</li>
                                <li><strong>Special Cases:</strong> Extended retention for ongoing treatment tracking or legal cases</li>
                            </ul>
                            <p>
                                <strong style="color: #454B1B;">Patient Request:</strong> Right to deletion (RACCD) honored within 30 days, except where data is needed for ongoing care.
                            </p>
                        </div>
                    </div>

                    <div class="compliance-accordion-item border border-slate-100 rounded-xl overflow-hidden">
                        <input type="checkbox" id="accordion3" class="accordion-input">
                        <label for="accordion3" class="accordion-label block w-full p-6 cursor-pointer font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <span>Access Control & Audit Logs</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </label>
                        <div class="accordion-content bg-slate-50 px-6 pb-6 text-slate-600 text-sm leading-relaxed">
                            <p class="mb-3">
                                Every access to patient data is logged with timestamp, user ID, and action taken:
                            </p>
                            <ul class="space-y-2 mb-3 pl-6 list-disc">
                                <li>Role-based access: users can only view records relevant to their function</li>
                                <li>Unusual access patterns are automatically flagged and investigated</li>
                                <li>Quarterly audit reports submitted to barangay officials</li>
                                <li>Unauthorized access triggers immediate security alert and user suspension</li>
                            </ul>
                            <p>
                                <strong style="color: #454B1B;">Patient Transparency:</strong> Patients can request an audit log of who accessed their records and when.
                            </p>
                        </div>
                    </div>

                    <div class="compliance-accordion-item border border-slate-100 rounded-xl overflow-hidden">
                        <input type="checkbox" id="accordion4" class="accordion-input">
                        <label for="accordion4" class="accordion-label block w-full p-6 cursor-pointer font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <span>Breach Notification & Incident Response</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </label>
                        <div class="accordion-content bg-slate-50 px-6 pb-6 text-slate-600 text-sm leading-relaxed">
                            <p class="mb-3">
                                In the event of a data breach or security incident:
                            </p>
                            <ul class="space-y-2 mb-3 pl-6 list-disc">
                                <li>Immediate containment and forensic investigation (within 24 hours)</li>
                                <li>Affected individuals notified within 72 hours (per RA 10173)</li>
                                <li>NPC (National Privacy Commission) notified if breach affects >100 individuals</li>
                                <li>Corrective actions documented and implemented</li>
                                <li>Regular breach drills and incident response tabletop exercises</li>
                            </ul>
                            <p>
                                <strong style="color: #454B1B;">Goal:</strong> Zero breaches through proactive security monitoring and rapid response protocols.
                            </p>
                        </div>
                    </div>

                    
                </div>
            </div>
        </section>

        <section class="compliance-grid-section py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Philippine Laws &amp; Regulatory Mandates
                    </h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl p-6 md:p-8 border border-[#DDE7C9] shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-3 mb-6">
                            <div class="flex items-center gap-3">
                                <span class="bg-[#EDF3DD] text-[#4F5B1E] text-[10px] px-3 py-1 rounded-full font-semibold">RA 10173</span>
                                <div class="w-11 h-11 rounded-xl bg-[#EDF3DD] text-[#454B1B] flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
                                        <path d="M12 3 19 6v5.5c0 4.5-3.1 8.7-7 10.5-3.9-1.8-7-6-7-10.5V6l7-3Z"/>
                                        <path d="m9.5 12.5 1.5 1.5 3.5-4"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <h3 class="text-xl font-heading font-bold text-slate-900 mb-2">Data Privacy Act of 2012</h3>
                        <p class="text-sm leading-relaxed text-slate-600 mb-5">
                            Governs collection, processing, and storage of personal data in the Philippines.
                        </p>
                        <ul class="space-y-3 text-sm text-slate-700">
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Explicit patient consent for data processing</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Encryption of all sensitive health records</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Right to access, correction, and deletion</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Annual privacy impact assessments</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-2xl p-6 md:p-8 border border-[#DDE7C9] shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-3 mb-6">
                            <div class="flex items-center gap-3">
                                <span class="bg-[#EDF3DD] text-[#4F5B1E] text-[10px] px-3 py-1 rounded-full font-semibold">RA 11223</span>
                                <div class="w-11 h-11 rounded-xl bg-[#EDF3DD] text-[#454B1B] flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
                                        <path d="M4 12.5c4-2.8 6.8-4.2 8-5.1 1.4 1.1 4 2.5 8 5.1v3c0 1.7-1.3 3-3 3H7c-1.7 0-3-1.3-3-3v-3Z"/>
                                        <path d="M12 6v11"/>
                                        <path d="M8.5 10.3h7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <h3 class="text-xl font-heading font-bold text-slate-900 mb-2">Universal Health Care Act</h3>
                        <p class="text-sm leading-relaxed text-slate-600 mb-5">
                            Mandates equitable health service delivery across all population groups.
                        </p>
                        <ul class="space-y-3 text-sm text-slate-700">
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Accessible primary health care to all</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Integrated referral system to hospitals</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Essential medicines availability</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Free preventive care programs</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-2xl p-6 md:p-8 border border-[#DDE7C9] shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-3 mb-6">
                            <div class="flex items-center gap-3">
                                <span class="bg-[#EDF3DD] text-[#4F5B1E] text-[10px] px-3 py-1 rounded-full font-semibold">RA 9994</span>
                                <div class="w-11 h-11 rounded-xl bg-[#EDF3DD] text-[#454B1B] flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
                                        <path d="M12 4.5a5 5 0 0 1 5 5v1.3A6.6 6.6 0 0 1 13.4 17H10.6A6.6 6.6 0 0 1 7 10.8V9.5a5 5 0 0 1 5-5Z"/>
                                        <path d="M9 19h6"/>
                                        <path d="M10 21h4"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <h3 class="text-xl font-heading font-bold text-slate-900 mb-2">Senior Citizens Welfare Act</h3>
                        <p class="text-sm leading-relaxed text-slate-600 mb-5">
                            Ensures healthcare and social services for citizens aged 60 and above.
                        </p>
                        <ul class="space-y-3 text-sm text-slate-700">
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Automatic age verification for 60&amp;plus;</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Priority scheduling at health center</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Discounted and free medicines tracking</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center shrink-0">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3">
                                        <path d="m5 10.3 2.7 2.7 7.1-7.1"/>
                                    </svg>
                                </span>
                                <span>Group support coordination</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="compliance-grid-section py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Third-Party Audits & Compliance Verification
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="bg-white p-8 rounded-xl border border-slate-100 shadow-sm">
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-4">Annual External Audits</h3>
                        <ul class="space-y-3 text-sm text-slate-600">
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold mt-0.5">1</span>
                                <span><strong>Data Privacy Impact Assessment (DPIA)</strong> conducted by independent auditors</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold mt-0.5">2</span>
                                <span><strong>Cybersecurity Penetration Testing</strong> by certified ethical hackers</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold mt-0.5">3</span>
                                <span><strong>ISO 27001 Compliance</strong> certification for information security management</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold mt-0.5">4</span>
                                <span><strong>Healthcare Accreditation Review</strong> aligned with PhilHealth standards</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white p-8 rounded-xl border border-slate-100 shadow-sm">
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-4">Regulatory Agency Oversight</h3>
                        <ul class="space-y-3 text-sm text-slate-600">
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold mt-0.5">✓</span>
                                <span><strong>NPC (National Privacy Commission):</strong> Annual compliance reporting</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold mt-0.5">✓</span>
                                <span><strong>DOH (Department of Health):</strong> Surveillance system audits</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold mt-0.5">✓</span>
                                <span><strong>LGU Health Office:</strong> Quarterly operational reviews</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold mt-0.5">✓</span>
                                <span><strong>PNP Cybercrime Unit:</strong> Security incident coordination</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="w-full bg-slate-900 text-white py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-6 lg:px-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="font-heading font-bold text-lg mb-4">ScanCare</h3>
                    <p class="text-slate-400 text-sm">Healthcare made simple for Barangay Gutad</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Services</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="appointments.php" class="hover:text-white transition">Appointments</a></li>
                        <li><a href="system-goals.php" class="hover:text-white transition">System Goals</a></li>
                        <li><a href="service-areas.php" class="hover:text-white transition">Service Areas</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="compliance.php" class="hover:text-white transition">Privacy Policy & Terms of Service</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Contact</h4>
                    <p class="text-slate-400 text-sm">Floridablanca RHU II - GUTAD<br>Email: floridablancarhu2@gmail.com<br>Contact: 0948 056 6872</p>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-8 text-center text-slate-400 text-sm">
                <p>&copy; 2026 ScanCare. All rights reserved. Your data is encrypted and secure.</p>
            </div>
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

