<?php 
session_start();


function isNavActive($page) {
    $currentFile = basename($_SERVER['PHP_SELF']);
    if ($page === 'appointment' && ($currentFile === 'dashboard.php' || $currentFile === 'appointments.php')) return true;
    if ($page === 'system-goals' && $currentFile === 'system-goals.php') return true;
    if ($page === 'service-areas' && $currentFile === 'service-areas.php') return true;
    if ($page === 'user-portals' && $currentFile === 'user-portals.php') return true;
    if ($page === 'surveillance' && $currentFile === 'surveillance.php') return true;
    if ($page === 'compliance' && $currentFile === 'compliance.php') return true;
    if ($page === 'landing' && $currentFile === 'landing.php') return true;
    return false;
}







$resident_id = null;
$medical_history = [];
$medical_error = '';


if (isset($_SESSION['resident_id']) && $_SESSION['user_type'] === 'resident') {
    $resident_id = intval($_SESSION['resident_id']);
    
    try {
        if (function_exists('getDatabaseConnection')) {
            $pdo = getDatabaseConnection();
            
            if ($pdo) {
                
                
                $sql = "
                    SELECT 
                        pr.record_id,
                        pr.resident_id,
                        pr.health_worker_id,
                        pr.visit_date,
                        pr.diagnosis,
                        pr.treatment,
                        pr.notes,
                        hw.full_name as bhw_name,
                        hw.role
                    FROM patient_record pr
                    LEFT JOIN health_worker hw ON pr.health_worker_id = hw.worker_id
                    WHERE pr.resident_id = ?
                    ORDER BY pr.visit_date DESC
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$resident_id]);
                
                $medical_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (Exception $e) {
        $medical_error = 'Error fetching medical history: ' . $e->getMessage();
        error_log($medical_error);
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Services — ScanCare</title>
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

        .nav-link {
            position: relative;
            padding: 10px 16px;
            border-radius: 10px;
            transition: all 0.3s ease;
            color: #475569;
            text-decoration: none;
            white-space: nowrap;
        }

        .nav-link:hover {
            color: #454B1B;
            background-color: rgba(69, 75, 27, 0.08);
        }

        .nav-link.active {
            color: #454B1B;
            font-weight: 600;
            background-color: transparent;
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

        .nav-user-name {
            max-width: 220px;
            display: inline-block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-user-action {
            white-space: nowrap;
        }

        .faq-item {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.02);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .faq-item.is-open {
            border-color: rgba(69, 75, 27, 0.28);
            box-shadow: 0 14px 28px rgba(69, 75, 27, 0.08);
        }

        .faq-toggle {
            width: 100%;
            padding: 1.1rem 1.25rem;
            background: #ffffff;
            border: none;
            cursor: pointer;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            min-height: 4rem;
            color: #1e293b;
            transition: background-color 0.2s ease;
        }

        .faq-toggle:hover {
            background: #f8fafc;
        }

        .faq-toggle:focus-visible {
            outline: 3px solid rgba(69, 75, 27, 0.2);
            outline-offset: -3px;
            background: rgba(248, 250, 252, 0.96);
        }

        .faq-toggle-label {
            flex: 1;
            font-weight: 600;
            font-size: 1rem;
            line-height: 1.5;
        }

        .faq-chevron {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 9999px;
            background: rgba(69, 75, 27, 0.08);
            color: #454B1B;
            transition: transform 0.25s ease, background-color 0.2s ease;
        }

        .faq-item.is-open .faq-chevron {
            transform: rotate(180deg);
            background: rgba(69, 75, 27, 0.12);
        }

        .faq-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .faq-content-inner {
            padding: 1rem 1.25rem 1.25rem;
            color: #475569;
            line-height: 1.7;
        }

        html.dark .faq-item,
        body.dark-mode .faq-item,
        html.dark .faq-toggle,
        body.dark-mode .faq-toggle {
            background: #131c17;
            border-color: rgba(255, 255, 255, 0.1);
            color: #f1f5f9;
        }

        html.dark .faq-toggle:hover,
        body.dark-mode .faq-toggle:hover,
        html.dark .faq-content,
        body.dark-mode .faq-content {
            background: #0d1310;
        }

        html.dark .bg-white,
        body.dark-mode .bg-white {
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

        main > section:first-child h1 span,
        main > section:first-child a[class*="bg-[#454B1B]"],
        main > section:first-child [class*="bg-[#454B1B]"] {
            color: #5a7518;
        }

        main > section:first-child a[class*="bg-[#454B1B]"] {
            background-color: #5a7518;
            color: #ffffff;
        }

        main > section:first-child [class*="rounded-full"] {
            color: #5a7518;
            border-color: rgba(90, 117, 24, 0.35);
            background-color: rgba(90, 117, 24, 0.1);
        }

        @media (max-width: 640px) {
            .appointment-process-step {
                border: 1px solid #e2e8f0;
                background-color: #ffffff !important;
            }

            html.dark .appointment-process-step,
            body.dark-mode .appointment-process-step {
                border-color: #2f3d2f;
                background-color: #141b14 !important;
            }

            .faq-toggle {
                padding: 1rem 1rem;
                min-height: 4rem;
            }

            .faq-toggle-label {
                font-size: 0.96rem;
            }

            .faq-content-inner {
                padding: 0.9rem 1rem 1.1rem;
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
                    Scan<span style="color: #454B1B;">Care</span>
                </span>
            </a>

            <div class="nav-links-desktop">
                <span id="nav-indicator" class="nav-active-pill" aria-hidden="true"></span>
                <a href="dashboard.php" class="nav-link text-sm font-medium <?php echo isNavActive('appointment') ? 'active' : ''; ?>">Appointment</a>
                <a href="system-goals.php" class="nav-link text-sm font-medium <?php echo isNavActive('system-goals') ? 'active' : ''; ?>">System Goals</a>
                <a href="service-areas.php" class="nav-link text-sm font-medium <?php echo isNavActive('service-areas') ? 'active' : ''; ?>">Service Areas</a>
                <a href="surveillance.php" class="nav-link text-sm font-medium <?php echo isNavActive('surveillance') ? 'active' : ''; ?>">Surveillance</a>
                <a href="compliance.php" class="nav-link text-sm font-medium <?php echo isNavActive('compliance') ? 'active' : ''; ?>">Compliance</a>
            </div>

            <div class="nav-links-desktop flex items-center gap-4 border-l border-slate-200 pl-6 flex-shrink-0">
                <?php if (isset($_SESSION['resident_id']) && $_SESSION['user_type'] === 'resident'): ?>
                    <a href="dashboard.php" style="color: #454B1B; font-weight: 600; max-width: 200px; white-space: normal; word-wrap: break-word; overflow-wrap: break-word; text-decoration: none; cursor: pointer; transition: opacity 0.2s ease;" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </a>
                    <button onclick="openResidentLogoutConfirmModal()" style="background-color: #454B1B; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; white-space: nowrap; border: none; cursor: pointer;">Log Out</button>
                <?php else: ?>
                    <a href="login.php" style="background-color: #454B1B; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; white-space: nowrap;">Login / Signup</a>
                <?php endif; ?>
            </div>

            <button id="toggleBtn" class="nav-hamburger-btn" aria-label="Toggle mobile menu">
                <i class="fa-solid fa-bars" style="color: #475569;"></i>
            </button>
        </div>

        <div id="mobileMenuPanel" style="display: none;" class="border-t border-slate-200 bg-white flex-col px-6 py-4">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="appointments.php" style="display: block; padding: 12px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.3s ease; <?php echo isNavActive('appointment') ? 'background-color: #EFEFEA; color: #1e293b; font-weight: 600;' : 'color: #64748b;'; ?>">Appointment</a>
                <a href="system-goals.php" style="display: block; padding: 12px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.3s ease; <?php echo isNavActive('system-goals') ? 'background-color: #EFEFEA; color: #1e293b; font-weight: 600;' : 'color: #64748b;'; ?>">System Goals</a>
                <a href="service-areas.php" style="display: block; padding: 12px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.3s ease; <?php echo isNavActive('service-areas') ? 'background-color: #EFEFEA; color: #1e293b; font-weight: 600;' : 'color: #64748b;'; ?>">Service Areas</a>
                <a href="surveillance.php" style="display: block; padding: 12px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.3s ease; <?php echo isNavActive('surveillance') ? 'background-color: #EFEFEA; color: #1e293b; font-weight: 600;' : 'color: #64748b;'; ?>">Surveillance</a>
                <a href="compliance.php" style="display: block; padding: 12px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.3s ease; <?php echo isNavActive('compliance') ? 'background-color: #EFEFEA; color: #1e293b; font-weight: 600;' : 'color: #64748b;'; ?>">Compliance</a>
            </div>
            <div style="border-top: 1px solid #e2e8f0; margin-top: 12px; padding-top: 12px;">
                <?php if (isset($_SESSION['resident_id']) && $_SESSION['user_type'] === 'resident'): ?>
                    <a href="dashboard.php" style="color: #454B1B; font-weight: 600; margin-bottom: 12px; white-space: normal; word-wrap: break-word; overflow-wrap: break-word; text-decoration: none; cursor: pointer; display: block; transition: opacity 0.2s ease;" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </a>
                    <button onclick="openResidentLogoutConfirmModal()" style="background-color: #454B1B; color: white; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center; border: none; cursor: pointer; width: 100%;">Log Out</button>
                <?php else: ?>
                    <a href="login.php" style="background-color: #454B1B; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Login / Signup</a>
                <?php endif; ?>
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
                    if (mobileMenuPanel.style.display === 'none' || mobileMenuPanel.style.display === '') {
                        mobileMenuPanel.style.display = 'flex';
                    } else {
                        mobileMenuPanel.style.display = 'none';
                    }
                });
            }

            if (mobileMenuPanel) {
                mobileMenuPanel.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', function () {
                        mobileMenuPanel.style.display = 'none';
                    });
                });
            }

            initNavbarIndicator();
            window.addEventListener('resize', function () {
                clearTimeout(navbarPillResizeTimer);
                navbarPillResizeTimer = setTimeout(updateNavbarActivePill, 100);
            });
        });
    </script>

    <main class="bg-dark-grid w-full">
        <section class="hero-section">
            <div class="hero-animated-dots" aria-hidden="true"></div>
            <div class="hero-glow" aria-hidden="true"></div>
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="hero-badge-dot" aria-hidden="true"></span>
                            Schedule Your Care
                </div>
                    
                <h1 class="hero-title">
                    Easy Appointment Scheduling
                    </h1>
                    
                <p class="hero-subtitle">
                        Book your health services in minutes. Our streamlined appointment system eliminates wait times and ensures you get the care you need when you need it.
                    </p>
                    
                    <?php if (isset($_SESSION['resident_id']) && $_SESSION['user_type'] === 'resident'): ?>
                        <a href="dashboard.php#schedule-appointment" class="hero-btn">
                            Schedule Appointment
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="hero-btn">
                            Sign In to Schedule
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>



        <section class="grid-canvas w-full py-20 border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 mb-4">Fast, Convenient, Secure</h2>
                    <p class="text-slate-600 max-w-2xl mx-auto">Experience healthcare scheduling reimagined for the digital age</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="p-8 bg-white border border-slate-200 rounded-xl hover:border-[#454B1B] hover:shadow-lg transition-all">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-[#454B1B] to-[#6b7333] text-2xl text-white sm:h-14 sm:w-14">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-3">Instant Availability</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">Check real-time availability and book your appointment in seconds, 24/7 from any device.</p>
                    </div>

                    <div class="p-8 bg-white border border-slate-200 rounded-xl hover:border-[#454B1B] hover:shadow-lg transition-all">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-[#454B1B] to-[#6b7333] text-2xl text-white sm:h-14 sm:w-14">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-3">Get QR ID Card</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">Receive your unique QR ID card instantly. Present it at check-in for fast, error-free processing.</p>
                    </div>

                    <div class="p-8 bg-white border border-slate-200 rounded-xl hover:border-[#454B1B] hover:shadow-lg transition-all">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-[#454B1B] to-[#6b7333] text-2xl text-white sm:h-14 sm:w-14">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-3">Smart Reminders</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">Receive appointment reminders via SMS and email so you never miss your scheduled visit.</p>
                    </div>
                </div>
            </div>
        </section>

        <section style="background-color: transparent; background-image: linear-gradient(to right, rgba(148, 163, 184, 0.18) 1px, transparent 1px), linear-gradient(to bottom, rgba(148, 163, 184, 0.18) 1px, transparent 1px); background-size: 32px 32px;" class="w-full py-12 text-slate-900 sm:py-16 md:py-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="text-center mb-16">
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-[#434e1c]"></span>
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 mb-4">4-Step Appointment Process</h2>
                    <p class="text-[#434e1c] max-w-2xl mx-auto">From booking to check-in, our process is designed for speed and simplicity</p>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-4">
                    <div class="appointment-process-step relative rounded-xl bg-white/50 p-5 text-center backdrop-blur-sm sm:rounded-none sm:bg-transparent sm:p-0">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#434e1c] text-2xl font-bold text-white sm:h-14 sm:w-14">1</div>
                        <h3 class="font-heading font-bold text-lg mb-2 text-slate-900">Create Account</h3>
                        <p class="text-slate-600 text-sm">Sign up with your personal information and validate your contact details.</p>
                    </div>

                    <div class="appointment-process-step relative rounded-xl bg-white/50 p-5 text-center backdrop-blur-sm sm:rounded-none sm:bg-transparent sm:p-0">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#434e1c] text-2xl font-bold text-white sm:h-14 sm:w-14">2</div>
                        <h3 class="font-heading font-bold text-lg mb-2 text-slate-900">Select Service</h3>
                        <p class="text-slate-600 text-sm">Choose the type of service: checkup, vaccination, consultation, or prescription refill.</p>
                    </div>

                    <div class="appointment-process-step relative rounded-xl bg-white/50 p-5 text-center backdrop-blur-sm sm:rounded-none sm:bg-transparent sm:p-0">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#434e1c] text-2xl font-bold text-white sm:h-14 sm:w-14">3</div>
                        <h3 class="font-heading font-bold text-lg mb-2 text-slate-900">Pick Time Slot</h3>
                        <p class="text-slate-600 text-sm">Browse available dates and times, then confirm your preferred appointment slot.</p>
                    </div>

                    <div class="appointment-process-step relative rounded-xl bg-white/50 p-5 text-center backdrop-blur-sm sm:rounded-none sm:bg-transparent sm:p-0">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#434e1c] text-2xl font-bold text-white sm:h-14 sm:w-14">4</div>
                        <h3 class="font-heading font-bold text-lg mb-2 text-slate-900">Get QR Code</h3>
                        <p class="text-slate-600 text-sm">Receive your QR ID card for quick check-in at the clinic on appointment day.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid-canvas w-full py-20 border-b border-slate-200">
            <div class="max-w-4xl mx-auto px-6 lg:px-12">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 mb-4">Appointment FAQs</h2>
                </div>

                <div class="space-y-4" aria-label="Appointment frequently asked questions">
                    <div class="faq-item">
                        <button class="faq-toggle" type="button" aria-expanded="false" aria-controls="faq-panel-1">
                            <span class="faq-toggle-label">What should I bring to my appointment?</span>
                            <span class="faq-chevron" aria-hidden="true">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </button>
                        <div id="faq-panel-1" class="faq-content" hidden>
                            <div class="faq-content-inner">
                                Please present your ScanCare QR code on your mobile device upon arrival at the health center. Bring any relevant medical history or current prescriptions if available.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-toggle" type="button" aria-expanded="false" aria-controls="faq-panel-2">
                            <span class="faq-toggle-label">Can I reschedule or cancel my appointment?</span>
                            <span class="faq-chevron" aria-hidden="true">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </button>
                        <div id="faq-panel-2" class="faq-content" hidden>
                            <div class="faq-content-inner">
                                Yes, you can reschedule or cancel your appointment through your ScanCare user portal at least 2 hours before your scheduled time slot.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-toggle" type="button" aria-expanded="false" aria-controls="faq-panel-3">
                            <span class="faq-toggle-label">What if I arrive late to my appointment?</span>
                            <span class="faq-chevron" aria-hidden="true">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </button>
                        <div id="faq-panel-3" class="faq-content" hidden>
                            <div class="faq-content-inner">
                                Late arrivals will be accommodated on a next-available basis, but we recommend arriving 10 minutes before your scheduled time to ensure smooth processing.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid-canvas w-full py-20 border-b border-slate-200">
            <div class="max-w-4xl mx-auto px-6 lg:px-12 text-center">
                <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 mb-6">Ready to Book Your Appointment?</h2>
                <p class="text-slate-600 max-w-2xl mx-auto mb-8 text-lg">Join thousands of residents who trust ScanCare for their healthcare needs</p>
                <?php if (isset($_SESSION['resident_id']) && $_SESSION['user_type'] === 'resident'): ?>
                    <a href="dashboard.php#schedule-appointment" style="background-color: #454B1B;" class="hover:opacity-95 text-white px-8 py-4 rounded-xl font-semibold text-sm transition-all shadow-lg shadow-[#454B1B]/20 inline-flex items-center gap-3 group">
                        Schedule Now
                        <span class="inline-block transition-transform group-hover:translate-x-1">&rarr;</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" style="background-color: #454B1B;" class="hover:opacity-95 text-white px-8 py-4 rounded-xl font-semibold text-sm transition-all shadow-lg shadow-[#454B1B]/20 inline-flex items-center gap-3 group">
                        Sign In to Schedule
                        <span class="inline-block transition-transform group-hover:translate-x-1">&rarr;</span>
                    </a>
                <?php endif; ?>
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
                    <p class="text-slate-400 text-sm">Floridablanca RHU II - GUTAD<br><a href="mailto:floridablancarhu2@gmail.com" class="inline-flex items-center gap-2 hover:text-white transition"><i class="fa-solid fa-envelope w-4 text-center" aria-hidden="true"></i><span>floridablancarhu2@gmail.com</span></a><br><a href="tel:09480566872" class="inline-flex items-center gap-2 hover:text-white transition"><i class="fa-solid fa-phone w-4 text-center" aria-hidden="true"></i><span>0948 056 6872</span></a><br><a href="https://www.facebook.com/share/1DRzKzHcYD/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 hover:text-white transition" aria-label="ScanCare on Facebook"><i class="fa-brands fa-facebook-f w-4 text-center" aria-hidden="true"></i><span>RHU II - Gutad</span></a></p>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-8 text-center text-slate-400 text-sm">
                <p>&copy; 2026 ScanCare. All rights reserved. Your data is encrypted and secure.</p>
            </div>
        </div>
    </footer>

    <script>
    function setFaqItemState(item, isOpen) {
        const button = item.querySelector('.faq-toggle');
        const panel = item.querySelector('.faq-content');
        const chevron = item.querySelector('.faq-chevron');

        if (!button || !panel || !chevron) {
            return;
        }

        item.classList.toggle('is-open', isOpen);
        button.setAttribute('aria-expanded', String(isOpen));

        if (isOpen) {
            panel.hidden = false;
            panel.style.maxHeight = panel.scrollHeight + 'px';
            chevron.style.transform = 'rotate(180deg)';
            return;
        }

        panel.style.maxHeight = '0px';
        chevron.style.transform = 'rotate(0deg)';

        window.setTimeout(() => {
            if (button.getAttribute('aria-expanded') === 'false') {
                panel.hidden = true;
            }
        }, 250);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const faqItems = document.querySelectorAll('.faq-item');

        faqItems.forEach((item) => {
            const button = item.querySelector('.faq-toggle');
            const panel = item.querySelector('.faq-content');

            if (!button || !panel) {
                return;
            }

            panel.hidden = true;
            panel.style.maxHeight = '0px';
            button.setAttribute('aria-expanded', 'false');

            button.addEventListener('click', function () {
                const isExpanded = button.getAttribute('aria-expanded') === 'true';

                faqItems.forEach((otherItem) => {
                    if (otherItem !== item) {
                        setFaqItemState(otherItem, false);
                    }
                });

                setFaqItemState(item, !isExpanded);
            });
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

        const residentLogoutModal = document.getElementById('residentLogoutConfirmModal');
        if (residentLogoutModal) {
            residentLogoutModal.addEventListener('click', function(e) {
                if (e.target === this) closeResidentLogoutConfirmModal();
            });
        }

    
    if (residentLogoutModal) {
        residentLogoutModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeResidentLogoutConfirmModal();
            }
        });
    }

    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('residentLogoutConfirmModal').style.display !== 'none') {
            closeResidentLogoutConfirmModal();
        }
    });
    </script>

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

</body>
</html>
