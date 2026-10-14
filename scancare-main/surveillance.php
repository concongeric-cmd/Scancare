<?php session_start();
require_once 'db_config.php';

$userRole = strtolower($_SESSION['worker_role'] ?? $_SESSION['user_role'] ?? $_SESSION['user_type'] ?? 'guest');
$isBhw = in_array($userRole, ['bhw', 'worker', 'health_worker'], true);
$worker_id = isset($_SESSION['worker_id']) ? intval($_SESSION['worker_id']) : (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_surveillance_metrics') {
    header('Content-Type: application/json');
    try {
        if (!$worker_id) {
            throw new Exception('Worker not authenticated');
        }

        $pdo = getDatabaseConnection();
        if ($pdo === false) {
            throw new Exception('Database connection failed');
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS surveillance_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            worker_id INT NOT NULL,
            reporting_period VARCHAR(255) DEFAULT NULL,
            dengue_count INT DEFAULT 0,
            dengue_trend VARCHAR(255) DEFAULT NULL,
            dengue_meta VARCHAR(255) DEFAULT NULL,
            dengue_progress INT DEFAULT 0,
            rabies_count INT DEFAULT 0,
            rabies_trend VARCHAR(255) DEFAULT NULL,
            rabies_meta VARCHAR(255) DEFAULT NULL,
            rabies_progress INT DEFAULT 0,
            respiratory_count INT DEFAULT 0,
            respiratory_trend VARCHAR(255) DEFAULT NULL,
            respiratory_meta VARCHAR(255) DEFAULT NULL,
            respiratory_progress INT DEFAULT 0,
            nutrition_count INT DEFAULT 0,
            nutrition_trend VARCHAR(255) DEFAULT NULL,
            nutrition_meta VARCHAR(255) DEFAULT NULL,
            nutrition_progress INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_worker_id (worker_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $pdo->prepare("INSERT INTO surveillance_metrics (
            worker_id, reporting_period, dengue_count, dengue_trend, dengue_meta, dengue_progress,
            rabies_count, rabies_trend, rabies_meta, rabies_progress,
            respiratory_count, respiratory_trend, respiratory_meta, respiratory_progress,
            nutrition_count, nutrition_trend, nutrition_meta, nutrition_progress
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        ) ON DUPLICATE KEY UPDATE
            reporting_period = VALUES(reporting_period),
            dengue_count = VALUES(dengue_count),
            dengue_trend = VALUES(dengue_trend),
            dengue_meta = VALUES(dengue_meta),
            dengue_progress = VALUES(dengue_progress),
            rabies_count = VALUES(rabies_count),
            rabies_trend = VALUES(rabies_trend),
            rabies_meta = VALUES(rabies_meta),
            rabies_progress = VALUES(rabies_progress),
            respiratory_count = VALUES(respiratory_count),
            respiratory_trend = VALUES(respiratory_trend),
            respiratory_meta = VALUES(respiratory_meta),
            respiratory_progress = VALUES(respiratory_progress),
            nutrition_count = VALUES(nutrition_count),
            nutrition_trend = VALUES(nutrition_trend),
            nutrition_meta = VALUES(nutrition_meta),
            nutrition_progress = VALUES(nutrition_progress)");

        $stmt->execute([
            $worker_id,
            $_POST['reporting_period'] ?? null,
            intval($_POST['dengue_count'] ?? 0),
            normalizeSurveillanceTrend($_POST['dengue_trend'] ?? '', intval($_POST['dengue_count'] ?? 0)),
            $_POST['dengue_meta'] ?? null,
            intval($_POST['dengue_progress'] ?? 0),
            intval($_POST['rabies_count'] ?? 0),
            normalizeSurveillanceTrend($_POST['rabies_trend'] ?? '', intval($_POST['rabies_count'] ?? 0)),
            $_POST['rabies_meta'] ?? null,
            intval($_POST['rabies_progress'] ?? 0),
            intval($_POST['respiratory_count'] ?? 0),
            normalizeSurveillanceTrend($_POST['respiratory_trend'] ?? '', intval($_POST['respiratory_count'] ?? 0)),
            $_POST['respiratory_meta'] ?? null,
            intval($_POST['respiratory_progress'] ?? 0),
            intval($_POST['nutrition_count'] ?? 0),
            normalizeSurveillanceTrend($_POST['nutrition_trend'] ?? '', intval($_POST['nutrition_count'] ?? 0)),
            $_POST['nutrition_meta'] ?? null,
            intval($_POST['nutrition_progress'] ?? 0)
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


$surveillance_metrics = [];
try {
    $pdo = getDatabaseConnection();
    if ($pdo !== false) {
        $surveillance_metrics = getResidentSurveillanceMetrics($pdo);
    }
} catch (Exception $e) {
    error_log('SURVEILLANCE METRICS LOAD ERROR: ' . $e->getMessage());
    $surveillance_metrics = [];
}

function getSurveillanceText($metrics, $key, $fallback) {
    $value = trim((string)($metrics[$key] ?? ''));
    return $value === '' ? $fallback : $value;
}

function getSurveillanceCount($metrics, $key) {
    return intval($metrics[$key] ?? 0);
}

function getSurveillanceProgress($metrics, $progressKey, $countKey, $metricType) {
    $count = getSurveillanceCount($metrics, $countKey);

    if ($metricType === 'dengue' && $count > 0) {
        return min(100, max(0, ($count / 50) * 100));
    }

    $progress = intval($metrics[$progressKey] ?? 0);
    if ($progress > 0) {
        return min(100, max(0, $progress));
    }
    if ($count <= 0) {
        return 0;
    }
    switch ($metricType) {
        case 'rabies':
            return min(100, max(10, $count));
        case 'dengue':
        case 'respiratory':
        case 'nutrition':
            return min(100, max(0, ($count / 50) * 100));
        default:
            return min(100, max(10, $count * 10));
    }
}

function getCountBasedTrend($count) {
    $value = max(0, intval($count));

    if ($value <= 10) {
        return ['label' => 'Baseline', 'color' => 'text-emerald-600'];
    }
    if ($value <= 20) {
        return ['label' => 'Watch', 'color' => 'text-amber-600'];
    }
    if ($value <= 30) {
        return ['label' => 'Alert', 'color' => 'text-orange-600'];
    }
    return ['label' => 'Emergency', 'color' => 'text-red-600'];
}

function normalizeSurveillanceTrend($trend, $count) {
    return getCountBasedTrend($count)['label'];
}

function getSurveillanceTrend($metrics, $key, $countKey = null) {
    $count = $countKey !== null ? getSurveillanceCount($metrics, $countKey) : 0;
    return normalizeSurveillanceTrend($metrics[$key] ?? '', $count);
}

$dengueCount = getSurveillanceCount($surveillance_metrics, 'dengue_count');
$dengueTrend = getSurveillanceTrend($surveillance_metrics, 'dengue_trend', 'dengue_count');
$dengueMeta = getSurveillanceText($surveillance_metrics, 'dengue_meta', 'No active cases reported.');
$dengueProgress = getSurveillanceProgress($surveillance_metrics, 'dengue_progress', 'dengue_count', 'dengue');

$rabiesCount = getSurveillanceCount($surveillance_metrics, 'rabies_count');
$rabiesTrend = getSurveillanceTrend($surveillance_metrics, 'rabies_trend', 'rabies_count');
$rabiesMeta = getSurveillanceText($surveillance_metrics, 'rabies_meta', '0% coverage | No residents vaccinated.');
$rabiesProgress = getSurveillanceProgress($surveillance_metrics, 'rabies_progress', 'rabies_count', 'rabies');

$respiratoryCount = getSurveillanceCount($surveillance_metrics, 'respiratory_count');
$respiratoryTrend = getSurveillanceTrend($surveillance_metrics, 'respiratory_trend', 'respiratory_count');
$respiratoryMeta = getSurveillanceText($surveillance_metrics, 'respiratory_meta', 'No active cases reported.');
$respiratoryProgress = getSurveillanceProgress($surveillance_metrics, 'respiratory_progress', 'respiratory_count', 'respiratory');

$nutritionCount = getSurveillanceCount($surveillance_metrics, 'nutrition_count');
$nutritionTrend = getSurveillanceTrend($surveillance_metrics, 'nutrition_trend', 'nutrition_count');
$nutritionMeta = getSurveillanceText($surveillance_metrics, 'nutrition_meta', 'No active cases reported.');
$nutritionProgress = getSurveillanceProgress($surveillance_metrics, 'nutrition_progress', 'nutrition_count', 'nutrition');


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
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surveillance — ScanCare</title>
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

        .surveillance-grid-section {
            background-color: transparent;
        }

        .surveillance-grid-canvas {
            background-color: #FAFBF8;
            background-image:
                linear-gradient(to right, rgba(69, 75, 27, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(69, 75, 27, 0.05) 1px, transparent 1px);
            background-size: 32px 32px;
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

        @keyframes slideIn {
            from {
                width: 0;
                opacity: 0;
            }
            to {
                width: 100%;
                opacity: 1;
            }
        }
        
        .animate-bar {
            animation: slideIn 1.5s ease-out;
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

        .current-status-row {
            position: relative;
            min-width: 0;
            align-items: flex-start;
            flex-direction: column;
            gap: 0.75rem;
        }

        #alert-status-badge {
            position: relative;
            align-self: flex-end;
            display: block;
            width: min(20rem, 100%);
            min-width: 0;
            max-width: 100%;
        }

        #alert-status-badge #escalation-popover {
            position: absolute;
            top: 100%;
            right: 0;
            left: auto;
            width: 18rem;
            max-width: calc(100vw - 2rem);
            max-height: min(10rem, calc(100vh - 10rem));
            margin: 0.5rem 0 0 auto;
            overflow-y: auto;
            box-sizing: border-box;
            background: #ffffff;
            z-index: 100 !important;
        }

        /* The badge popover must rise above the following page sections. */
        .alert-escalation-section {
            z-index: 30 !important;
        }

        @media (max-width: 640px) {
            #alert-status-badge,
            #alert-status-badge [data-testid="alert-badge-trigger"] {
                width: 100%;
                max-width: 100%;
            }

            #alert-status-badge {
                align-self: stretch;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            #alert-status-badge [data-testid="alert-badge-trigger"] {
                align-self: center;
                gap: 0.5rem;
                width: fit-content;
                max-width: min(17rem, 100%);
                padding: 0.55rem 0.75rem;
            }

            #alert-status-badge [data-testid="alert-badge-trigger"] .text-xs {
                font-size: 0.625rem;
            }

            #alert-status-badge [data-testid="alert-badge-trigger"] .text-sm {
                font-size: 0.75rem;
                line-height: 1.15;
            }

            #alert-status-badge #escalation-popover {
                left: 0;
                right: 0;
                width: min(17rem, calc(100vw - 2rem));
                max-width: calc(100vw - 2rem);
                max-height: min(9rem, calc(100vh - 10rem));
                margin-left: auto;
                margin-right: auto;
            }

            #resident-surveillance-widget > div:first-child {
                padding: 0.9rem 1rem;
            }

            #resident-surveillance-widget > div:first-child h3 {
                font-size: 1.35rem;
            }

            #resident-surveillance-widget > div:first-child p {
                margin-top: 0.5rem;
                font-size: 0.75rem;
            }

            #resident-surveillance-widget #reportingPeriodDisplay {
                width: 6rem;
                font-size: 0.55rem;
                letter-spacing: 0.08em;
            }

            #alert-status-badge [data-testid="alert-badge-trigger"] {
                justify-content: flex-start;
            }
        }

        /* Keep every surveillance surface readable when the shared theme is dark. */
        html.dark body,
        body.dark-mode {
            background-color: #0b0f0a !important;
            color: #e5e7eb;
        }

        html.dark .surveillance-grid-canvas,
        body.dark-mode .surveillance-grid-canvas {
            background-color: #0b0f0a !important;
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px) !important;
        }

        html.dark .surveillance-grid-section,
        body.dark-mode .surveillance-grid-section {
            border-color: #273126 !important;
        }

        html.dark .surveillance-grid-canvas .bg-white,
        html.dark .surveillance-grid-canvas [class*="bg-slate-50"],
        html.dark .surveillance-grid-canvas [class*="bg-slate-100"],
        html.dark .surveillance-grid-canvas [class*="to-white"],
        body.dark-mode .surveillance-grid-canvas .bg-white,
        body.dark-mode .surveillance-grid-canvas [class*="bg-slate-50"],
        body.dark-mode .surveillance-grid-canvas [class*="bg-slate-100"],
        body.dark-mode .surveillance-grid-canvas [class*="to-white"] {
            background-color: #141b14 !important;
            background-image: none !important;
        }

        html.dark .surveillance-grid-canvas .bg-green-50,
        body.dark-mode .surveillance-grid-canvas .bg-green-50 {
            background-color: #163021 !important;
        }

        html.dark .surveillance-grid-canvas .bg-yellow-50,
        body.dark-mode .surveillance-grid-canvas .bg-yellow-50 {
            background-color: #302b14 !important;
        }

        html.dark .surveillance-grid-canvas .bg-orange-50,
        body.dark-mode .surveillance-grid-canvas .bg-orange-50 {
            background-color: #342214 !important;
        }

        html.dark .surveillance-grid-canvas .bg-red-50,
        body.dark-mode .surveillance-grid-canvas .bg-red-50 {
            background-color: #351919 !important;
        }

        html.dark .surveillance-grid-canvas [class*="border-slate"],
        html.dark .surveillance-grid-canvas [class*="border-dashed"],
        body.dark-mode .surveillance-grid-canvas [class*="border-slate"],
        body.dark-mode .surveillance-grid-canvas [class*="border-dashed"] {
            border-color: #344334 !important;
        }

        html.dark .surveillance-grid-canvas [class*="text-slate-900"],
        html.dark .surveillance-grid-canvas [class*="text-slate-800"],
        html.dark .surveillance-grid-canvas [class*="text-slate-700"],
        body.dark-mode .surveillance-grid-canvas [class*="text-slate-900"],
        body.dark-mode .surveillance-grid-canvas [class*="text-slate-800"],
        body.dark-mode .surveillance-grid-canvas [class*="text-slate-700"] {
            color: #f8fafc !important;
        }

        html.dark .surveillance-grid-canvas [class*="text-slate-600"],
        html.dark .surveillance-grid-canvas [class*="text-slate-500"],
        html.dark .surveillance-grid-canvas [class*="text-slate-400"],
        body.dark-mode .surveillance-grid-canvas [class*="text-slate-600"],
        body.dark-mode .surveillance-grid-canvas [class*="text-slate-500"],
        body.dark-mode .surveillance-grid-canvas [class*="text-slate-400"] {
            color: #b8c8b8 !important;
        }

        html.dark .surveillance-grid-canvas input,
        body.dark-mode .surveillance-grid-canvas input {
            background-color: #101610 !important;
            color: #f8fafc !important;
            border-color: #425542 !important;
        }

        html.dark #alert-status-badge [data-testid="alert-badge-trigger"],
        html.dark #alert-status-badge #escalation-popover,
        body.dark-mode #alert-status-badge [data-testid="alert-badge-trigger"],
        body.dark-mode #alert-status-badge #escalation-popover {
            background-color: #141b14 !important;
            border-color: #344334 !important;
        }

        html.dark #residentLogoutConfirmModal > div,
        body.dark-mode #residentLogoutConfirmModal > div {
            background-color: #141b14 !important;
            border-color: #344334 !important;
        }

        html.dark #residentLogoutConfirmModal > div > div:first-child,
        body.dark-mode #residentLogoutConfirmModal > div > div:first-child {
            background-color: #1b251b !important;
            border-color: #344334 !important;
        }

        html.dark #residentLogoutConfirmModal h2,
        body.dark-mode #residentLogoutConfirmModal h2 {
            color: #f8fafc !important;
        }

        html.dark #residentLogoutConfirmModal p,
        body.dark-mode #residentLogoutConfirmModal p {
            color: #b8c8b8 !important;
        }

        html.dark #residentLogoutConfirmModal > div > div:last-child > div > button[onclick="closeResidentLogoutConfirmModal()"],
        body.dark-mode #residentLogoutConfirmModal > div > div:last-child > div > button[onclick="closeResidentLogoutConfirmModal()"] {
            background-color: #344334 !important;
            color: #f8fafc !important;
        }
    </style>
</head>
<body class="bg-[#F8FAF6] text-slate-800 font-body antialiased min-h-screen selection:bg-[#454B1B] selection:text-white">

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

    <main class="bg-dark-grid surveillance-grid-canvas w-full">

        <section class="hero-section">
            <div class="hero-animated-dots" aria-hidden="true"></div>
            <div class="hero-glow" aria-hidden="true"></div>

            <div class="hero-content">
                <div class="hero-badge">
                    <span class="hero-badge-dot animate-pulse" aria-hidden="true"></span>
                        Real-Time Monitoring
                </div>
                <h1 class="hero-title">
                    Disease Surveillance &amp; Community Health Monitoring
                    </h1>
                <p class="hero-subtitle">
                        ScanCare's surveillance system provides real-time epidemiological insights, enabling rapid detection of disease outbreaks and targeted health interventions before community impact escalates.
                    </p>
            </div>
        </section>

        <section class="surveillance-grid-section py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        How Surveillance Works
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        Every patient consultation, diagnosis, and symptom report feeds into our aggregated epidemiological database, creating a comprehensive view of community health patterns.
                    </p>
                </div>

                <div class="grid xl:grid-cols-3 gap-8 items-start">
                    <div class="xl:col-span-2 space-y-6">
                        <div class="bg-gradient-to-br from-[#454B1B]/10 to-transparent p-8 rounded-2xl border border-slate-100">
                            <div style="background-color: #454B1B;" class="w-14 h-14 rounded-lg flex items-center justify-center text-white text-2xl mb-4">
                                <i class="fa-solid fa-database"></i>
                            </div>
                            <h3 class="text-xl font-heading font-bold text-slate-900 mb-3">Data Collection</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                Every diagnosis, symptom, and vital sign feeds into the surveillance state. This dashboard only shows verified BHW-managed data once it has been published.
                            </p>
                        </div>

                       

                        <div class="bg-gradient-to-br from-[#454B1B]/10 to-transparent p-8 rounded-2xl border border-slate-100">
                            <div style="background-color: #454B1B;" class="w-14 h-14 rounded-lg flex items-center justify-center text-white text-2xl mb-4">
                                <i class="fa-solid fa-bell"></i>
                            </div>
                            <h3 class="text-xl font-heading font-bold text-slate-900 mb-3">Alert Broadcasting</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                When a BHW publishes case updates, residents instantly see the most recent threat levels and community health metrics in the surveillance panel.
                            </p>
                        </div>

                        <div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-sm">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-xl font-heading font-bold text-slate-900">BHW Data Analytics Manager</h3>
                                    <p class="text-slate-500 text-sm">Use this portal to publish verified disease metrics and keep resident analytics accurate.</p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">BHW View</span>
                            </div>

                            <?php if ($isBhw): ?>
                            <form id="bhwMetricsForm" class="space-y-6" autocomplete="off">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="block text-sm font-medium text-slate-700">
                                        Reporting Period
                                        <input id="reportingPeriod" name="reportingPeriod" type="text" placeholder="e.g. Apr 1 - Apr 30" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-200" />
                                    </label>
                                    <label class="block text-sm font-medium text-slate-700">
                                        Submitted By
                                        <input type="text" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Barangay Health Worker'); ?>" disabled class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-500" />
                                    </label>
                                </div>

                                <div class="grid gap-4">
                                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <div>
                                                <h4 class="text-lg font-heading font-bold text-slate-900">Dengue Cases</h4>
                                                <p class="text-slate-500 text-sm">Active or confirmed dengue cases reported in the last 4 weeks.</p>
                                            </div>
                                        </div>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <label class="block text-sm font-medium text-slate-700">
                                                Case Count
                                                <input id="dengueCount" name="dengueCount" type="number" min="0" step="1" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                            <label class="block text-sm font-medium text-slate-700">
                                                Detail Text
                                                <input id="dengueMeta" name="dengueMeta" type="text" placeholder="e.g. +60% increase YoY" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                            <label class="block text-sm font-medium text-slate-700">
                                                Progress %
                                                <input id="dengueProgress" name="dengueProgress" type="number" min="0" max="100" step="1" placeholder="0 - 100" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <div>
                                                <h4 class="text-lg font-heading font-bold text-slate-900">Rabies Vaccination Coverage</h4>
                                                <p class="text-slate-500 text-sm">Coverage percentage and vaccination progress for bite exposures and outreach.</p>
                                            </div>
                                        </div>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <label class="block text-sm font-medium text-slate-700">
                                                Coverage Value
                                                <input id="rabiesCount" name="rabiesCount" type="number" min="0" max="100" step="1" placeholder="Percent" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                            <label class="block text-sm font-medium text-slate-700">
                                                Detail Text
                                                <input id="rabiesMeta" name="rabiesMeta" type="text" placeholder="e.g. 4,627 residents vaccinated" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                            <label class="block text-sm font-medium text-slate-700">
                                                Progress %
                                                <input id="rabiesProgress" name="rabiesProgress" type="number" min="0" max="100" step="1" placeholder="0 - 100" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <div>
                                                <h4 class="text-lg font-heading font-bold text-slate-900">Respiratory Infections</h4>
                                                <p class="text-slate-500 text-sm">Seasonal respiratory cases and outbreak trends among vulnerable families.</p>
                                            </div>
                                        </div>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <label class="block text-sm font-medium text-slate-700">
                                                Case Count
                                                <input id="respiratoryCount" name="respiratoryCount" type="number" min="0" step="1" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                            <label class="block text-sm font-medium text-slate-700">
                                                Detail Text
                                                <input id="respiratoryMeta" name="respiratoryMeta" type="text" placeholder="e.g. Normal seasonal pattern" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                            <label class="block text-sm font-medium text-slate-700">
                                                Progress %
                                                <input id="respiratoryProgress" name="respiratoryProgress" type="number" min="0" max="100" step="1" placeholder="0 - 100" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <div>
                                                <h4 class="text-lg font-heading font-bold text-slate-900">Nutrition Deficiency (U5)</h4>
                                                <p class="text-slate-500 text-sm">Undernutrition monitoring for children under five years old.</p>
                                            </div>
                                        </div>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <label class="block text-sm font-medium text-slate-700">
                                                Case Count
                                                <input id="nutritionCount" name="nutritionCount" type="number" min="0" step="1" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                            <label class="block text-sm font-medium text-slate-700">
                                                Detail Text
                                                <input id="nutritionMeta" name="nutritionMeta" type="text" placeholder="e.g. -15% from last quarter" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                            <label class="block text-sm font-medium text-slate-700">
                                                Progress %
                                                <input id="nutritionProgress" name="nutritionProgress" type="number" min="0" max="100" step="1" placeholder="0 - 100" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900" />
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <button id="bhwSubmitButton" type="submit" class="inline-flex items-center justify-center rounded-2xl bg-[#454B1B] px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                                        Publish / Update Statistics
                                    </button>
                                    <p id="bhwSaveMessage" class="text-sm text-emerald-700 hidden">Metrics have been saved and resident analytics updated.</p>
                                </div>
                            </form>

                            <div class="mt-10 rounded-3xl border border-slate-200 bg-slate-50 p-6">
                                <div class="flex items-center justify-between mb-5">
                                    <div>
                                        <h3 class="text-lg font-heading font-bold text-slate-900">Resident Surveillance Preview</h3>
                                        <p class="text-slate-500 text-sm">Preview the resident-facing surveillance cards from the BHW console.</p>
                                    </div>
                                    <span id="bhwReportingPeriodDisplay" class="text-xs uppercase tracking-[0.24em] text-slate-400">Reporting Period: Current 4 Weeks</span>
                                </div>

                                <div class="space-y-5" id="bhwPreviewContainer">
                                    <div class="rounded-3xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-semibold text-slate-900">Dengue Trend</span>
                                            <span id="bhwDengueTrendLabel" class="text-xs font-bold text-red-600"><?php echo htmlspecialchars($dengueTrend); ?></span>
                                        </div>
                                        <p id="bhwDengueDetail" class="text-xs text-slate-500 mb-3"><?php echo $dengueCount > 0 ? htmlspecialchars("{$dengueCount} confirmed cases | {$dengueMeta}") : htmlspecialchars($dengueMeta); ?></p>
                                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                                            <div id="bhwDengueBarFill" class="h-full rounded-full" style="background: linear-gradient(to right, #454B1B, #ef4444); width: <?php echo $dengueProgress; ?>%;"></div>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-semibold text-slate-900">Rabies Coverage</span>
                                            <span id="bhwRabiesTrendLabel" class="text-xs font-bold text-emerald-600"><?php echo htmlspecialchars($rabiesTrend); ?></span>
                                        </div>
                                        <p id="bhwRabiesDetail" class="text-xs text-slate-500 mb-3"><?php echo $rabiesCount > 0 ? htmlspecialchars("{$rabiesCount}% coverage | {$rabiesMeta}") : htmlspecialchars($rabiesMeta); ?></p>
                                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                                            <div id="bhwRabiesBarFill" class="h-full rounded-full" style="background: linear-gradient(to right, #454B1B, #10b981); width: <?php echo $rabiesProgress; ?>%;"></div>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-semibold text-slate-900">Respiratory Trend</span>
                                            <span id="bhwRespiratoryTrendLabel" class="text-xs font-bold text-blue-600"><?php echo htmlspecialchars($respiratoryTrend); ?></span>
                                        </div>
                                        <p id="bhwRespiratoryDetail" class="text-xs text-slate-500 mb-3"><?php echo $respiratoryCount > 0 ? htmlspecialchars("{$respiratoryCount} active cases | {$respiratoryMeta}") : htmlspecialchars($respiratoryMeta); ?></p>
                                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                                            <div id="bhwRespiratoryBarFill" class="h-full rounded-full" style="background: linear-gradient(to right, #454B1B, #3b82f6); width: <?php echo $respiratoryProgress; ?>%;"></div>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-semibold text-slate-900">Nutrition Trend</span>
                                            <span id="bhwNutritionTrendLabel" class="text-xs font-bold text-orange-600"><?php echo htmlspecialchars($nutritionTrend); ?></span>
                                        </div>
                                        <p id="bhwNutritionDetail" class="text-xs text-slate-500 mb-3"><?php echo $nutritionCount > 0 ? htmlspecialchars("{$nutritionCount} cases | {$nutritionMeta}") : htmlspecialchars($nutritionMeta); ?></p>
                                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                                            <div id="bhwNutritionBarFill" class="h-full rounded-full" style="background: linear-gradient(to right, #454B1B, #f97316); width: <?php echo $nutritionProgress; ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                                <p class="text-slate-600 text-sm">
                                    This portal is reserved for Barangay Health Workers. When a BHW publishes metric updates, the resident surveillance widget will display verified, real-world community analytics instead of placeholder data.
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="resident-surveillance-widget" class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-r from-[#454B1B]/5 to-white border-b border-slate-200 px-4 py-4 sm:px-6 sm:py-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-heading font-bold text-slate-900">Resident Surveillance Widget</h3>
                                    <p class="max-w-sm text-xs text-slate-500 mt-1 leading-relaxed">Live tallies group matching items checked in residents' latest medical-history records.</p>
                                </div>
                                <span id="reportingPeriodDisplay" class="text-[10px] tracking-wider uppercase font-semibold text-slate-400 text-right leading-tight w-28">REPORTING PERIOD: <?php echo htmlspecialchars(strtoupper(getSurveillanceText($surveillance_metrics, 'reporting_period', 'Current 4 Weeks')), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>

                        <div class="resident-metric-scroll max-h-[18rem] overflow-y-auto overscroll-contain p-4 pr-5 sm:max-h-[24rem] sm:p-6 sm:pr-8 lg:max-h-[28rem]" id="residentMetricsContainer">
                            <?php if (!empty($surveillance_metrics['disease_records'])): ?>
                                <?php foreach ($surveillance_metrics['disease_records'] as $record): ?>
                                    <?php
                                    $recordCount = max(0, (int) ($record['case_count'] ?? 0));
                                    $recordTrend = getCountBasedTrend($recordCount);
                                    $recordProgress = min(100, max(0, ($recordCount / 50) * 100));
                                    $recordName = trim((string) ($record['disease_name'] ?? 'Community health metric'));
                                    $recordMeta = trim((string) ($record['meta_notes'] ?? '')) ?: 'No notes provided.';
                                    ?>
                                    <div class="space-y-2 pb-5" data-resident-metric-card>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($recordName, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="text-[11px] font-bold <?php echo htmlspecialchars($recordTrend['color'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($recordTrend['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                            <p class="text-xs text-slate-500"><?php echo $recordCount; ?> resident<?php echo $recordCount === 1 ? ' is' : 's are'; ?> marked with <?php echo htmlspecialchars($recordName, ENT_QUOTES, 'UTF-8'); ?></p>
                                        <div class="resident-progress-track h-3 overflow-hidden rounded-full bg-slate-100">
                                            <div class="resident-progress-fill h-full rounded-full" style="background: linear-gradient(to right, #454B1B, #ef4444); width: <?php echo $recordProgress; ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">No checked medical-history items have been recorded yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Alert Escalation Levels Section -->
        <section class="alert-escalation-section surveillance-grid-section py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Alert Escalation Levels
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        ScanCare uses a 4-level alert escalation framework to manage public health threats, from routine baseline monitoring to emergency response coordination.
                    </p>
                </div>

                <!-- Alert Timeline Widget -->
                <div class="bg-white border border-slate-200 rounded-xl p-8 shadow-sm overflow-x-auto">
                    <div id="alert-escalation-timeline" class="min-w-full"></div>
                </div>

                <!-- Current Alert Status Badge -->
                <div class="mt-12 space-y-4">
                    <div class="current-status-row flex">
                        <h3 class="text-lg font-heading font-bold text-slate-900">Current System Status</h3>
                        <p class="text-sm text-slate-600 max-w-2xl">
                            The current alert level determines the scope of response protocols. Click the status badge above to view detailed level information and transition guidelines.
                        </p>
                        <div id="alert-status-badge" class="inline-block"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="surveillance-grid-section py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        SMS & Alert Broadcasting System
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        When a potential outbreak is detected, ScanCare automatically broadcasts alerts to residents and officials without requiring mobile internet, ensuring universal reach.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="bg-gradient-to-br from-[#454B1B]/10 to-white p-8 rounded-2xl border border-slate-100">
                        <h3 class="text-xl font-heading font-bold text-slate-900 mb-4 flex items-center gap-3">
                            <i style="color: #454B1B;" class="fa-solid fa-comment-sms text-2xl"></i>
                            Sample Alert Messages
                        </h3>
                        <div class="space-y-4 text-sm">
                            <div class="bg-white p-4 rounded-lg border border-slate-100">
                                <p class="text-slate-600">
                                    <strong style="color: #454B1B;">Subject:</strong> Health Alert - Dengue Cases in Your Area
                                </p>
                                <p class="text-slate-600 text-xs mt-2 leading-relaxed">
                                    "Increased dengue cases reported in Purok 3. Watch for fever, headache, rash. Go to health center if symptomatic. Remove stagnant water from homes. -ScanCare"
                                </p>
                            </div>
                            <div class="bg-white p-4 rounded-lg border border-slate-100">
                                <p class="text-slate-600">
                                    <strong style="color: #454B1B;">Subject:</strong> Vaccination Reminder
                                </p>
                                <p class="text-slate-600 text-xs mt-2 leading-relaxed">
                                    "Your child's DPT booster is due. Schedule at health center Mon-Fri 9AM-12PM. Vaccinations protect your family. -ScanCare"
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm">
                        <h3 class="text-xl font-heading font-bold text-slate-900 mb-4 flex items-center gap-3">
                            <i style="color: #454B1B;" class="fa-solid fa-triangle-exclamation text-2xl"></i>
                            Alert Escalation Levels
                        </h3>
                        <div class="space-y-4">
                            <div class="border border-green-200 bg-green-50 p-4 rounded-lg">
                                <div style="color: #454B1B;" class="font-bold text-sm mb-1">🟢 LEVEL 1: Baseline</div>
                                <p class="text-xs text-slate-600">Normal disease occurrence. Data logged for trend analysis.</p>
                            </div>
                            <div class="border border-yellow-200 bg-yellow-50 p-4 rounded-lg">
                                <div style="color: #454B1B;" class="font-bold text-sm mb-1">🟡 LEVEL 2: Watch</div>
                                <p class="text-xs text-slate-600">Unusual cluster detected. Officials notified for verification and light interventions.</p>
                            </div>
                            <div class="border border-orange-200 bg-orange-50 p-4 rounded-lg">
                                <div style="color: #454B1B;" class="font-bold text-sm mb-1">🟠 LEVEL 3: Alert</div>
                                <p class="text-xs text-slate-600">Confirmed outbreak. SMS broadcast to affected puroks + staff mobilization.</p>
                            </div>
                            <div class="border border-red-200 bg-red-50 p-4 rounded-lg">
                                <div style="color: #454B1B;" class="font-bold text-sm mb-1">🔴 LEVEL 4: Emergency</div>
                                <p class="text-xs text-slate-600">Major public health threat. Provincial/municipal officials contacted. Mass SMS alerts issued.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="surveillance-grid-section py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Data Protection in Surveillance
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="bg-white p-8 rounded-xl border border-slate-100 shadow-sm">
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <i style="color: #454B1B;" class="fa-solid fa-lock"></i>
                            Data Anonymization
                        </h3>
                        <p class="text-slate-600 text-sm mb-4 leading-relaxed">
                            Aggregated reports shown to officials contain no individual names or identifiers—only age ranges, puroks, and symptom clusters to protect privacy while enabling swift action.
                        </p>
                        <ul class="space-y-2 text-sm text-slate-600">
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold">✓</span>
                                <span>No patient names in alerts or reports</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold">✓</span>
                                <span>Case data linked by ID codes only</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold">✓</span>
                                <span>Purok-level reporting, not household-level</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white p-8 rounded-xl border border-slate-100 shadow-sm">
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <i style="color: #454B1B;" class="fa-solid fa-handshake"></i>
                            Ethical Standards
                        </h3>
                        <p class="text-slate-600 text-sm mb-4 leading-relaxed">
                            All surveillance activities align with WHO epidemiological standards and Philippine DOH guidelines, ensuring surveillance serves public health without stigmatizing individuals.
                        </p>
                        <ul class="space-y-2 text-sm text-slate-600">
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold">✓</span>
                                <span>Community-centered public health approach</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold">✓</span>
                                <span>Transparent communication with residents</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span style="color: #454B1B;" class="font-bold">✓</span>
                                <span>Regular consent refresh for data sharing</span>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const reportingPeriodDisplay = document.getElementById('reportingPeriodDisplay');
            const bhwSaveMessage = document.getElementById('bhwSaveMessage');
            const metricsKey = 'scancareSurveillanceMetrics';
            const savedServerMetrics = normalizeSavedMetrics(<?php echo json_encode($surveillance_metrics); ?>);
            const serverHasMetrics = <?php echo !empty($surveillance_metrics) ? 'true' : 'false'; ?>;

            function normalizeSavedMetrics(rawMetrics) {
                const metrics = rawMetrics || {};
                return {
                    reportingPeriod: safeText(metrics.reportingPeriod ?? metrics.reporting_period, 'Current 4 Weeks'),
                    dengueCount: Number(metrics.dengueCount ?? metrics.dengue_count ?? 0) || 0,
                    dengueTrend: normalizeTrendLabel(metrics.dengueTrend ?? metrics.dengue_trend, Number(metrics.dengueCount ?? metrics.dengue_count ?? 0) || 0),
                    dengueMeta: safeText(metrics.dengueMeta ?? metrics.dengue_meta, 'No active cases reported.'),
                    dengueProgress: Number(metrics.dengueProgress ?? metrics.dengue_progress ?? 0) || 0,
                    rabiesCount: Number(metrics.rabiesCount ?? metrics.rabies_count ?? 0) || 0,
                    rabiesTrend: normalizeTrendLabel(metrics.rabiesTrend ?? metrics.rabies_trend, Number(metrics.rabiesCount ?? metrics.rabies_count ?? 0) || 0),
                    rabiesMeta: safeText(metrics.rabiesMeta ?? metrics.rabies_meta, '0% coverage | No residents vaccinated.'),
                    rabiesProgress: Number(metrics.rabiesProgress ?? metrics.rabies_progress ?? 0) || 0,
                    respiratoryCount: Number(metrics.respiratoryCount ?? metrics.respiratory_count ?? 0) || 0,
                    respiratoryTrend: normalizeTrendLabel(metrics.respiratoryTrend ?? metrics.respiratory_trend, Number(metrics.respiratoryCount ?? metrics.respiratory_count ?? 0) || 0),
                    respiratoryMeta: safeText(metrics.respiratoryMeta ?? metrics.respiratory_meta, 'No active cases reported.'),
                    respiratoryProgress: Number(metrics.respiratoryProgress ?? metrics.respiratory_progress ?? 0) || 0,
                    nutritionCount: Number(metrics.nutritionCount ?? metrics.nutrition_count ?? 0) || 0,
                    nutritionTrend: normalizeTrendLabel(metrics.nutritionTrend ?? metrics.nutrition_trend, Number(metrics.nutritionCount ?? metrics.nutrition_count ?? 0) || 0),
                    nutritionMeta: safeText(metrics.nutritionMeta ?? metrics.nutrition_meta, 'No active cases reported.'),
                    nutritionProgress: Number(metrics.nutritionProgress ?? metrics.nutrition_progress ?? 0) || 0,
                    diseaseRecords: Array.isArray(metrics.disease_records)
                        ? metrics.disease_records
                        : (Array.isArray(metrics.diseaseRecords) ? metrics.diseaseRecords : null)
                };
            }

            function getSavedMetrics() {
                try {
                    const raw = JSON.parse(localStorage.getItem(metricsKey) || '{}');
                    return normalizeSavedMetrics(raw);
                } catch (error) {
                    console.warn('Unable to parse surveillance metrics from localStorage:', error);
                    return normalizeSavedMetrics({});
                }
            }

            function getCountBasedTrendLabel(count) {
                const value = Number(count || 0);
                if (value <= 10) return { label: 'Baseline', color: 'text-emerald-600' };
                if (value <= 20) return { label: 'Watch', color: 'text-amber-600' };
                if (value <= 30) return { label: 'Alert', color: 'text-orange-600' };
                return { label: 'Emergency', color: 'text-red-600' };
            }

            function normalizeTrendLabel(value, count) {
                const fallback = getCountBasedTrendLabel(count);
                if (!value || typeof value !== 'string') return fallback.label;
                const normalized = String(value).trim();
                if (['Baseline', 'Watch', 'Alert', 'Emergency', 'Low', 'Moderate', 'High'].includes(normalized)) {
                    return getCountBasedTrendLabel(count).label;
                }
                return normalized;
            }

            function updateProgressBar(element, percent) {
                if (!element) return;
                const value = Number(percent);
                element.style.width = `${Math.max(0, Math.min(100, Number.isFinite(value) ? value : 0))}%`;
            }

            function safeText(value, fallback) {
                if (value === null || value === undefined || String(value).trim() === '') {
                    return fallback;
                }
                return String(value).trim();
            }

            function deriveFallbackProgress(count, metricType) {
                const numericCount = Number(count || 0);
                if (numericCount <= 0) return 0;

                switch (metricType) {
                    case 'rabies':
                        return Math.min(100, Math.max(10, numericCount));
                    case 'dengue':
                        return Math.min(100, Math.max(0, (numericCount / 50) * 100));
                    case 'respiratory':
                    case 'nutrition':
                        return Math.min(100, Math.max(10, numericCount * 8));
                    default:
                        return Math.min(100, Math.max(10, numericCount * 10));
                }
            }

            function renderMetricSection(metric, config) {
                const count = Number(metric.count ?? 0);
                const trend = normalizeTrendLabel(metric.trend, count);
                const meta = safeText(metric.meta, config.fallbackText);
                let progress = Number(metric.progress ?? 0);

                if (!Number.isFinite(progress) || progress < 0) {
                    progress = 0;
                }
                if (progress <= 0 && count > 0) {
                    progress = deriveFallbackProgress(count, config.metricType);
                }

                const label = document.getElementById(config.labelId);
                const detail = document.getElementById(config.detailId);
                const fill = document.getElementById(config.fillId);

                if (label) label.textContent = trend;
                if (detail) {
                    detail.textContent = count > 0 ? `${count} ${config.countSuffix} | ${meta}` : config.fallbackText;
                }
                updateProgressBar(fill, progress);
            }

            function renderPreviewMetrics(prefix, metrics) {
                renderMetricSection({
                    count: metrics.dengueCount,
                    trend: metrics.dengueTrend,
                    meta: metrics.dengueMeta,
                    progress: metrics.dengueProgress
                }, {
                    labelId: `${prefix}DengueTrendLabel`,
                    detailId: `${prefix}DengueDetail`,
                    fillId: `${prefix}DengueBarFill`,
                    metricType: 'dengue',
                    countSuffix: 'confirmed cases',
                    fallbackText: 'No active cases reported.'
                });

                renderMetricSection({
                    count: metrics.rabiesCount,
                    trend: metrics.rabiesTrend,
                    meta: metrics.rabiesMeta,
                    progress: metrics.rabiesProgress
                }, {
                    labelId: `${prefix}RabiesTrendLabel`,
                    detailId: `${prefix}RabiesDetail`,
                    fillId: `${prefix}RabiesBarFill`,
                    metricType: 'rabies',
                    countSuffix: '% coverage',
                    fallbackText: '0% coverage | No residents vaccinated.'
                });

                renderMetricSection({
                    count: metrics.respiratoryCount,
                    trend: metrics.respiratoryTrend,
                    meta: metrics.respiratoryMeta,
                    progress: metrics.respiratoryProgress
                }, {
                    labelId: `${prefix}RespiratoryTrendLabel`,
                    detailId: `${prefix}RespiratoryDetail`,
                    fillId: `${prefix}RespiratoryBarFill`,
                    metricType: 'respiratory',
                    countSuffix: 'active cases',
                    fallbackText: 'No active cases reported.'
                });

                renderMetricSection({
                    count: metrics.nutritionCount,
                    trend: metrics.nutritionTrend,
                    meta: metrics.nutritionMeta,
                    progress: metrics.nutritionProgress
                }, {
                    labelId: `${prefix}NutritionTrendLabel`,
                    detailId: `${prefix}NutritionDetail`,
                    fillId: `${prefix}NutritionBarFill`,
                    metricType: 'nutrition',
                    countSuffix: 'cases',
                    fallbackText: 'No active cases reported.'
                });
            }

            function renderResidentMetrics(metrics) {
                const period = safeText(metrics.reportingPeriod, 'Current 4 Weeks');
                if (reportingPeriodDisplay) {
                    reportingPeriodDisplay.textContent = `Reporting Period: ${period}`;
                }

                const container = document.getElementById('residentMetricsContainer');
                if (!container) return;
                if (!Array.isArray(metrics.diseaseRecords)) return;
                const records = metrics.diseaseRecords;
                container.replaceChildren();

                if (!records.length) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500';
                    emptyState.textContent = 'No checked medical-history items have been recorded yet.';
                    container.appendChild(emptyState);
                    return;
                }

                records.forEach(record => {
                    const count = Math.max(0, Number(record.case_count ?? record.caseCount ?? 0) || 0);
                    const trend = getCountBasedTrendLabel(count);
                    const card = document.createElement('div');
                    card.className = 'space-y-2 pb-5';
                    card.dataset.residentMetricCard = '';

                    const heading = document.createElement('div');
                    heading.className = 'flex items-center justify-between gap-2';
                    const name = document.createElement('span');
                    name.className = 'text-sm font-bold text-slate-900';
                    name.textContent = safeText(record.disease_name ?? record.diseaseName, 'Community health metric');
                    const trendLabel = document.createElement('span');
                    trendLabel.className = `text-[11px] font-bold ${trend.color}`;
                    trendLabel.textContent = trend.label;
                    heading.append(name, trendLabel);

                    const detail = document.createElement('p');
                    detail.className = 'text-xs text-slate-500';
                    detail.textContent = `${count} resident${count === 1 ? ' is' : 's are'} marked with ${safeText(record.disease_name ?? record.diseaseName, 'Community health metric')}`;

                    const track = document.createElement('div');
                    track.className = 'resident-progress-track h-3 overflow-hidden rounded-full bg-slate-100';
                    const fill = document.createElement('div');
                    fill.className = 'resident-progress-fill h-full rounded-full';
                    fill.style.background = 'linear-gradient(to right, #454B1B, #ef4444)';
                    fill.style.width = `${Math.min(100, Math.max(0, (count / 50) * 100))}%`;
                    track.appendChild(fill);

                    card.append(heading, detail, track);
                    container.appendChild(card);
                });
            }

            function renderAllPreviews(metrics) {
                renderResidentMetrics(metrics);
                if (document.getElementById('bhwReportingPeriodDisplay')) {
                    const bhwPeriod = safeText(metrics.reportingPeriod, 'Current 4 Weeks');
                    document.getElementById('bhwReportingPeriodDisplay').textContent = `Reporting Period: ${bhwPeriod}`;
                    renderPreviewMetrics('bhw', metrics);
                }
            }

            function hydrateBhwForm(metrics) {
                const mapping = {
                    reportingPeriod: 'reportingPeriod',
                    dengueCount: 'dengueCount',
                    dengueTrend: 'dengueTrend',
                    dengueMeta: 'dengueMeta',
                    dengueProgress: 'dengueProgress',
                    rabiesCount: 'rabiesCount',
                    rabiesTrend: 'rabiesTrend',
                    rabiesMeta: 'rabiesMeta',
                    rabiesProgress: 'rabiesProgress',
                    respiratoryCount: 'respiratoryCount',
                    respiratoryTrend: 'respiratoryTrend',
                    respiratoryMeta: 'respiratoryMeta',
                    respiratoryProgress: 'respiratoryProgress',
                    nutritionCount: 'nutritionCount',
                    nutritionTrend: 'nutritionTrend',
                    nutritionMeta: 'nutritionMeta',
                    nutritionProgress: 'nutritionProgress'
                };

                Object.entries(mapping).forEach(([key, id]) => {
                    const input = document.getElementById(id);
                    if (!input) return;
                    input.value = safeText(metrics[key], '');
                });
            }

            const storedMetrics = getSavedMetrics();
            const metricsToRender = serverHasMetrics ? savedServerMetrics : storedMetrics;
            renderAllPreviews(metricsToRender);
            hydrateBhwForm(metricsToRender);

            function readCurrentFormMetrics() {
                return {
                    reportingPeriod: safeText(document.getElementById('reportingPeriod').value, 'Current 4 Weeks'),
                    dengueCount: Number(document.getElementById('dengueCount').value) || 0,
                    dengueTrend: getCountBasedTrendLabel(Number(document.getElementById('dengueCount').value) || 0).label,
                    dengueMeta: safeText(document.getElementById('dengueMeta').value, 'No active cases reported.'),
                    dengueProgress: Number(document.getElementById('dengueProgress').value) || 0,
                    rabiesCount: Number(document.getElementById('rabiesCount').value) || 0,
                    rabiesTrend: getCountBasedTrendLabel(Number(document.getElementById('rabiesCount').value) || 0).label,
                    rabiesMeta: safeText(document.getElementById('rabiesMeta').value, '0% coverage | No residents vaccinated.'),
                    rabiesProgress: Number(document.getElementById('rabiesProgress').value) || 0,
                    respiratoryCount: Number(document.getElementById('respiratoryCount').value) || 0,
                    respiratoryTrend: getCountBasedTrendLabel(Number(document.getElementById('respiratoryCount').value) || 0).label,
                    respiratoryMeta: safeText(document.getElementById('respiratoryMeta').value, 'No active cases reported.'),
                    respiratoryProgress: Number(document.getElementById('respiratoryProgress').value) || 0,
                    nutritionCount: Number(document.getElementById('nutritionCount').value) || 0,
                    nutritionTrend: getCountBasedTrendLabel(Number(document.getElementById('nutritionCount').value) || 0).label,
                    nutritionMeta: safeText(document.getElementById('nutritionMeta').value, 'No active cases reported.'),
                    nutritionProgress: Number(document.getElementById('nutritionProgress').value) || 0
                };
            }

            function syncLiveMetricsPreview() {
                const metrics = readCurrentFormMetrics();
                renderAllPreviews(metrics);
                localStorage.setItem(metricsKey, JSON.stringify(metrics));

                try {
                    if (typeof BroadcastChannel !== 'undefined') {
                        const bc = new BroadcastChannel('scancare-surveillance');
                        bc.postMessage({ type: 'update', metrics });
                        bc.close();
                    }
                } catch (error) {
                    console.warn('BroadcastChannel update failed:', error);
                }

                try {
                    window.dispatchEvent(new CustomEvent('scancare-surveillance-update', { detail: { metrics } }));
                } catch (error) {
                    console.warn('Custom surveillance update event failed:', error);
                }
            }

            const livePreviewFields = [
                'reportingPeriod',
                'dengueCount', 'dengueTrend', 'dengueMeta', 'dengueProgress',
                'rabiesCount', 'rabiesTrend', 'rabiesMeta', 'rabiesProgress',
                'respiratoryCount', 'respiratoryTrend', 'respiratoryMeta', 'respiratoryProgress',
                'nutritionCount', 'nutritionTrend', 'nutritionMeta', 'nutritionProgress'
            ];

            livePreviewFields.forEach(function (fieldId) {
                const field = document.getElementById(fieldId);
                if (!field) return;
                field.addEventListener('input', syncLiveMetricsPreview);
                field.addEventListener('change', syncLiveMetricsPreview);
            });

            const bhwMetricsForm = document.getElementById('bhwMetricsForm');
            if (bhwMetricsForm) {
                bhwMetricsForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const metrics = readCurrentFormMetrics();

                    const requestBody = new URLSearchParams({
                        action: 'save_surveillance_metrics',
                        reporting_period: metrics.reportingPeriod,
                        dengue_count: metrics.dengueCount,
                        dengue_trend: metrics.dengueTrend,
                        dengue_meta: metrics.dengueMeta,
                        dengue_progress: metrics.dengueProgress,
                        rabies_count: metrics.rabiesCount,
                        rabies_trend: metrics.rabiesTrend,
                        rabies_meta: metrics.rabiesMeta,
                        rabies_progress: metrics.rabiesProgress,
                        respiratory_count: metrics.respiratoryCount,
                        respiratory_trend: metrics.respiratoryTrend,
                        respiratory_meta: metrics.respiratoryMeta,
                        respiratory_progress: metrics.respiratoryProgress,
                        nutrition_count: metrics.nutritionCount,
                        nutrition_trend: metrics.nutritionTrend,
                        nutrition_meta: metrics.nutritionMeta,
                        nutrition_progress: metrics.nutritionProgress
                    });

                    fetch(window.location.pathname, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: requestBody.toString()
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const savedMetrics = data.metrics && Object.keys(data.metrics).length > 0 ? data.metrics : metrics;
                                localStorage.setItem(metricsKey, JSON.stringify(savedMetrics));
                                renderAllPreviews(savedMetrics);
                                hydrateBhwForm(savedMetrics);

                                try {
                                    if (typeof BroadcastChannel !== 'undefined') {
                                        const bc = new BroadcastChannel('scancare-surveillance');
                                        bc.postMessage({ type: 'update', metrics: savedMetrics });
                                        bc.close();
                                    }
                                } catch (error) {
                                    console.warn('Broadcast update after save failed:', error);
                                }

                                try {
                                    window.dispatchEvent(new CustomEvent('scancare-surveillance-update', { detail: { metrics: savedMetrics } }));
                                } catch (error) {
                                    console.warn('Custom update event after save failed:', error);
                                }

                                if (bhwSaveMessage) {
                                    bhwSaveMessage.textContent = 'Metrics have been saved and resident analytics updated.';
                                    bhwSaveMessage.classList.remove('hidden');
                                    setTimeout(() => bhwSaveMessage.classList.add('hidden'), 3500);
                                }
                            } else {
                                alert('Unable to save surveillance metrics: ' + (data.message || 'Unknown error.'));
                            }
                        })
                        .catch(error => {
                            console.error('Error saving surveillance metrics:', error);
                            alert('Error saving surveillance metrics. Please try again.');
                        });
                });
            }
        });
    </script>

    <!-- Alert Escalation Widget Script -->
    <script src="components/alert-escalation-widget.js"></script>
    <script>
        // Initialize Alert Timeline Widget
        const timelineWidget = new AlertEscalationWidget({
            containerId: 'alert-escalation-timeline',
            mode: 'timeline',
            orientation: 'horizontal',
            activeLevel: 1, // Default to baseline
            isInteractive: false, // Read-only for this view
            onLevelChange: (level) => {
                console.log('Alert level changed to:', level);
            }
        });
        timelineWidget.render();

        // Initialize Alert Status Badge
        const statusBadge = new AlertEscalationWidget({
            containerId: 'alert-status-badge',
            mode: 'badge',
            activeLevel: 1,
            isInteractive: true,
            onLevelChange: (level) => {
                console.log('Status badge level changed to:', level);
                // You can add code here to update the backend or other UI elements
            }
        });
        statusBadge.render();
    </script>

</body>
</html>
