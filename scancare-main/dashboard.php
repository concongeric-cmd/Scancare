<?php
// Dashboard Controller - Routes to role-specific dashboard
// Handles authentication and role-based routing

session_start();
require_once 'db_config.php';

// ============================================================================
// SESSION VALIDATION - RESIDENT PROTECTION
// ============================================================================
// Verify resident is logged in with proper session variables
// Redirect to login if session is invalid or missing
// ============================================================================

// Check if user is logged in
if (!isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

// Route to appropriate dashboard based on user type
$user_type = $_SESSION['user_type'];

if ($user_type === 'resident') {
    // Verify resident_id is set
    if (!isset($_SESSION['resident_id'])) {
        header('Location: login.php');
        exit;
    }
    // Include resident dashboard content
    // All resident-specific variables and logic are handled in dashboard_resident.php
    define('DASHBOARD_ROLE', 'resident');
} elseif ($user_type === 'health_worker') {
    // Verify worker_id is set
    if (!isset($_SESSION['worker_id'])) {
        header('Location: login.php');
        exit;
    }
    // Route health workers (BHW/admin) to their dedicated dashboard
    header('Location: dashboard_bhw.php');
    exit;
} else {
    // Unknown role - redirect to login
    header('Location: login.php');
    exit;
}

// Helper function to determine if a nav link is active
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
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes, maximum-scale=5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#454B1B">
    <link rel="icon" type="image/png" href="images/SClogo.png?v=2">
    <link rel="apple-touch-icon" href="images/SClogo.png?v=2">
    <link rel="manifest" href="manifest.json">
    <script src="pwa-offline.js?v=4" defer></script>
    <title>Resident Dashboard — ScanCare</title>
    <script src="theme-state.js?v=2"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        /* Base styles for the layout container */
        .sc-nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin: 0 auto;
        }

        /* DEFAULT FOR MOBILE VIEW: Hide desktop menu links, show hamburger button */
        .nav-links-desktop {
            display: none !important;
        }
        .mobile-nav-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 36px;
            padding: 4px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #1f2937;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            z-index: 10001;
            pointer-events: auto;
        }

        .mobile-nav-toggle:hover,
        .mobile-nav-toggle.active {
            background: #f3f4f6;
            border-color: #cbd5e1;
        }

        nav.sticky,
        nav[role='navigation'],
        nav {
            z-index: 10000;
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            pointer-events: auto;
        }

        .mobile-sidebar {
            z-index: 9998;
        }

        .mobile-nav-icon {
            display: grid;
            gap: 3px;
            width: 14px;
            height: 14px;
        }

        .mobile-nav-icon span {
            display: block;
            width: 100%;
            height: 2px;
            background: #111827;
            border-radius: 999px;
        }

        /* DESKTOP VIEW BREAKPOINT: If screen is wider than 768px, swap them */
        @media (min-width: 768px) {
            .nav-links-desktop {
                position: relative;
                display: flex !important;
                align-items: center;
                gap: 24px;
                font-weight: 500;
                font-size: 14px;
                color: #475569;
            }
            .mobile-nav-toggle {
                display: none !important;
            }
            #mobileMenuPanel {
                display: none !important;
            }
        }

        /* NAVBAR TAB HOVER & ACTIVE STATES */
        .nav-link {
            position: relative;
            z-index: 10;
            padding: 8px 12px;
            border-radius: 6px;
            color: #475569;
            text-decoration: none;
            background-color: transparent !important;
            transition: color 0.2s ease, background-color 0.2s ease, opacity 0.2s ease;
        }

        .nav-link:hover:not(.active) {
            color: #454B1B;
            background-color: rgba(69, 75, 27, 0.08);
        }

        .nav-link:focus-visible {
            outline: 2px solid rgba(69, 75, 27, 0.35);
            outline-offset: 3px;
            background-color: rgba(69, 75, 27, 0.08);
            color: #454B1B;
        }

        .nav-link.active {
            color: #454B1B;
            font-weight: 600;
            background-color: transparent !important;
        }

        .nav-link.active:hover {
            color: #111827;
        }

        .nav-active-pill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            border-radius: 999px;
            background-color: rgba(69, 75, 27, 0.12);
            width: 0;
            transform: translateX(0);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), width 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
            z-index: 0;
            opacity: 0;
            pointer-events: none;
            view-transition-name: nav-pill;
            view-transition: cross-fade(250ms ease-in-out);
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

        /* Staff-style mobile sidebar for resident dashboard */
        .mobile-sidebar {
            position: relative;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            background: #ffffff;
            z-index: 40;
            display: none;
            max-height: calc(100vh - 5rem);
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            border-bottom: 1px solid #e5e7eb;
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
        }
        .mobile-sidebar.active {
            display: block;
        }
        .mobile-sidebar .close-mobile-sidebar {
            display: flex;
            justify-content: flex-end;
            padding: 1rem 1.25rem 0;
        }
        .mobile-sidebar nav {
            padding: 1rem 1rem 1.5rem;
        }
        .mobile-sidebar .sidebar-nav {
            display: grid;
            gap: 10px;
        }
        .mobile-sidebar .sidebar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 18px;
            color: #475569;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            white-space: normal;
            justify-content: flex-start;
        }
        .mobile-sidebar .sidebar-item:hover {
            background: linear-gradient(135deg, #f6fbf6, #f0f8f2);
            color: #111827;
            border-color: #c8e6c9;
        }
        .mobile-sidebar .sidebar-item.active {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: #1b5e20;
            border-color: #81c784;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.12);
        }
        .mobile-sidebar .sidebar-item i {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #e8f5e9, #d4e9db);
            color: #2d5016;
            transition: all 0.3s ease;
        }
        .mobile-sidebar .sidebar-item:hover i {
            background: linear-gradient(135deg, #c8e6c9, #a5d6a7);
            color: #1b5e20;
        }

        .resident-mobile-section.mobile-hidden-section {
            display: none !important;
        }

        @media (min-width: 768px) {
            .resident-mobile-section.mobile-hidden-section {
                display: block !important;
            }
        }

        .mobile-sidebar .nav-link,
        .mobile-sidebar .sidebar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            color: #475569;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            background: transparent;
        }
        .mobile-sidebar .nav-link:hover,
        .mobile-sidebar .sidebar-item:hover {
            background: linear-gradient(135deg, #f6fbf6, #f0f8f2);
            color: #111827;
            border-color: #c8e6c9;
        }
        .mobile-sidebar .nav-link.active,
        .mobile-sidebar .sidebar-item.active {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: #1b5e20;
            border-color: #81c784;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.12);
        }
        .mobile-sidebar .nav-link i,
        .mobile-sidebar .sidebar-item i {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #e8f5e9, #d4e9db);
            color: #2d5016;
            transition: all 0.3s ease;
        }
        .mobile-sidebar .nav-link:hover i,
        .mobile-sidebar .sidebar-item:hover i {
            background: linear-gradient(135deg, #c8e6c9, #a5d6a7);
            color: #1b5e20;
        }
        .sidebar-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }
        .sidebar-subtitle {
            font-size: 0.82rem;
            color: #6b7280;
            margin-top: 2px;
        }
        @media (min-width: 768px) {
            .mobile-sidebar,
            .mobile-menu-backdrop {
                display: none !important;
            }
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-completed {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-scheduled {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        /* ================================================================
           PROFILE PICTURE COMPONENT STYLES
           ================================================================ */
        
        .profile-pic-navbar {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, #454B1B 0%, #2a2d0f 100%);
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .profile-pic-navbar:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .profile-pic-navbar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-pic-navbar-text {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .profile-pic-overlay-navbar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-pic-navbar:hover .profile-pic-overlay-navbar {
            opacity: 1;
        }

        .profile-pic-overlay-icon {
            color: white;
            font-size: 16px;
        }

        .profile-upload-input {
            display: none;
        }

        /* Status message for upload */
        .profile-upload-status {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            display: none;
        }

        .profile-upload-status.success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            display: block;
        }

        .profile-upload-status.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            display: block;
        }

        /* ================================================================
           PROFILE DROPDOWN MENU STYLES
           ================================================================ */
        
        .profile-dropdown-container {
            position: relative;
            display: inline-block;
        }

        .profile-dropdown-btn {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            background: linear-gradient(135deg, #454B1B 0%, #2a2d0f 100%);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .profile-dropdown-btn:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .profile-dropdown-btn.active {
            box-shadow: 0 4px 12px rgba(69, 75, 27, 0.3);
        }

        .profile-pic-navbar-text {
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .profile-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            min-width: 260px;
            z-index: 1000;
            border: 1px solid #e2e8f0;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-dropdown-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .dropdown-profile-img,
        .dropdown-profile-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            background: linear-gradient(135deg, #454B1B 0%, #2a2d0f 100%);
            flex-shrink: 0;
            font-size: 14px;
        }

        .dropdown-profile-img {
            object-fit: cover;
        }

        .dropdown-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }

        .dropdown-role {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .profile-dropdown-divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 8px 0;
        }

        .dropdown-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 14px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .dropdown-menu-item:hover {
            background-color: #f8fafc;
            color: #454B1B;
        }

        .dropdown-menu-item i {
            width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #454B1B;
        }

        .dropdown-menu-item.logout-item {
            color: #dc2626;
            border-top: 1px solid #e2e8f0;
            margin-top: 8px;
        }

        .dropdown-menu-item.logout-item:hover {
            background-color: #fee2e2;
        }

        .dropdown-menu-item.logout-item i {
            color: #dc2626;
        }

        /* ================================================================
           DARK MODE - COPY OF ADMIN PANEL COLORWAY
           ================================================================ */
        
        html,
        body {
            background-color: #f8fafc !important;
            color: #0f172a !important;
        }

        html.dark,
        html.dark body,
        body.dark-mode {
            background-color: #0d1310 !important;
            color: #f8fafc !important;
        }

        body.dark-mode h1,
        body.dark-mode h2,
        body.dark-mode h3,
        body.dark-mode h4,
        body.dark-mode h5,
        body.dark-mode h6,
        body.dark-mode p,
        body.dark-mode span,
        body.dark-mode label,
        body.dark-mode a,
        body.dark-mode button,
        body.dark-mode div {
            color: #cbd5e1 !important;
        }

        body.dark-mode nav {
            background: #0f172a !important;
            border-color: #334155 !important;
        }

        body.dark-mode .profile-dropdown-menu {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
            border: 1px solid #334155 !important;
        }

        body.dark-mode .profile-dropdown-header,
        body.dark-mode #settingsModal > div > div,
        body.dark-mode #residentLogoutConfirmModal > div {
            background: #1e293b !important;
            border-color: #334155 !important;
        }

        body.dark-mode .dropdown-menu-item:hover {
            background-color: rgba(69, 75, 27, 0.15) !important;
            color: #454B1B !important;
        }

        body.dark-mode input,
        body.dark-mode select,
        body.dark-mode textarea {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #cbd5e1 !important;
        }

        body.dark-mode input[type="checkbox"],
        body.dark-mode input[type="radio"] {
            accent-color: #454B1B !important;
        }

        body.dark-mode button[style*="background-color: #e2e8f0"],
        body.dark-mode button[style*="background-color: #f"] {
            background-color: #334155 !important;
            color: #cbd5e1 !important;
        }

        body.dark-mode [style*="border: 1px solid #e2e8f0"],
        body.dark-mode [style*="border-bottom: 1px solid #e2e8f0"],
        body.dark-mode [style*="border-top: 1px solid #e2e8f0"] {
            border-color: #334155 !important;
        }

        body.dark-mode [style*="background-color: white"] {
            background-color: #1e293b !important;
        }

        body.dark-mode [style*="background-color: #f8fafc"],
        body.dark-mode [style*="background-color: #f1f5f9"] {
            background-color: #1e293b !important;
        }

        /* Cards and Sections - DARK MODE */
        body.dark-mode [style*="background-color: white"],
        body.dark-mode [style*="background-color: #fff"],
        body.dark-mode [style*="background-color: #ffffff"] {
            background-color: #1e293b !important;
        }

        body.dark-mode [style*="border-radius"] {
            background-color: #1e293b !important;
        }

        body.dark-mode [style*="box-shadow"] {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        /* Force all light colors to dark theme */
        body.dark-mode [style*="color: #e2e8f0"],
        body.dark-mode [style*="color: #cbd5e1"],
        body.dark-mode [style*="color: #94a3b8"],
        body.dark-mode [style*="color: #475569"],
        body.dark-mode [style*="color: #64748b"],
        body.dark-mode [style*="color: #a0aec0"] {
            color: #cbd5e1 !important;
        }

        body.dark-mode h1,
        body.dark-mode h2,
        body.dark-mode h3,
        body.dark-mode h4,
        body.dark-mode h5,
        body.dark-mode h6 {
            color: #cbd5e1 !important;
        }

        body.dark-mode p,
        body.dark-mode span,
        body.dark-mode label,
        body.dark-mode small {
            color: #cbd5e1 !important;
        }

        /* Tables - DARK MODE */
        body.dark-mode table {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        body.dark-mode thead {
            background-color: #0f172a !important;
            border-color: #334155 !important;
        }

        body.dark-mode thead th {
            background-color: #0f172a !important;
            color: #cbd5e1 !important;
            border-color: #334155 !important;
        }

        body.dark-mode tbody tr {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        body.dark-mode tbody tr:hover {
            background-color: rgba(69, 75, 27, 0.1) !important;
        }

        body.dark-mode tbody td {
            background-color: #1e293b !important;
            color: #cbd5e1 !important;
            border-color: #334155 !important;
        }

        body.dark-mode th,
        body.dark-mode td {
            color: #cbd5e1 !important;
            border-color: #334155 !important;
        }

        body.dark-mode [style*="border"] table,
        body.dark-mode table[style*="border"] {
            border-color: #334155 !important;
        }

        /* Override Tailwind classes for dark mode */
        body.dark-mode.bg-\[#F8FAF6\] {
            background-color: #0d1310 !important;
        }

        body.dark-mode {
            background-color: #0d1310 !important;
        }

        body.dark-mode [class*="bg-white"],
        body.dark-mode [class*="bg-slate"],
        body.dark-mode [class*="bg-gray"] {
            background-color: #1e293b !important;
        }

        body.dark-mode [class*="text-slate"],
        body.dark-mode [class*="text-gray"],
        body.dark-mode [class*="text-black"] {
            color: #cbd5e1 !important;
        }

        body.dark-mode .bg-white {
            background-color: #1e293b !important;
        }

        body.dark-mode .text-slate-800,
        body.dark-mode .text-gray-800,
        body.dark-mode .text-black {
            color: #cbd5e1 !important;
        }

        body.dark-mode .border-\[#454B1B\]\/10 {
            border-color: #334155 !important;
        }

        body.dark-mode [style*="background-color"] {
            background-color: #1e293b !important;
        }

        /* Remove gradients in dark mode - use solid colors instead */
        body.dark-mode .bg-gradient-to-br,
        body.dark-mode .bg-gradient-to-r,
        body.dark-mode .bg-gradient-to-b,
        body.dark-mode .bg-gradient-to-l,
        body.dark-mode [class*="bg-gradient"] {
            background-image: none !important;
            background: #1e293b !important;
        }

        body.dark-mode section[class*="bg-gradient"] {
            background: #1e293b !important;
        }

        body.dark-mode div[class*="bg-gradient"] {
            background: #1e293b !important;
        }

        /* Ensure all text labels are visible in dark mode */
        body.dark-mode .text-slate-400,
        body.dark-mode .text-slate-500,
        body.dark-mode .text-gray-400,
        body.dark-mode .text-gray-500,
        body.dark-mode .text-slate-300 {
            color: #cbd5e1 !important;
        }

        body.dark-mode .font-body,
        body.dark-mode .font-heading {
            color: #cbd5e1 !important;
        }

        body.dark-mode [class*="text-"] {
            color: #cbd5e1 !important;
        }

        /* Cards and their content in dark mode */
        body.dark-mode [class*="rounded-lg"],
        body.dark-mode [class*="rounded-md"],
        body.dark-mode [class*="rounded"] {
            background-color: #1e293b !important;
            color: #cbd5e1 !important;
        }

        /* Small labels and secondary text */
        body.dark-mode small,
        body.dark-mode .text-sm,
        body.dark-mode [class*="text-sm"] {
            color: #cbd5e1 !important;
        }

        /* Ensure inline elements are readable */
        body.dark-mode i {
            color: #cbd5e1 !important;
        }

        /* ================================================================
           COMPREHENSIVE DARK MODE ICON COLORS - ALL ICONS
           ================================================================ */
        
        /* Default icon color */
        body.dark-mode i,
        body.dark-mode .fa,
        body.dark-mode [class*="fa-"] {
            color: #cbd5e1 !important;
        }

        /* Calendar & Date Icons */
        body.dark-mode .fa-calendar,
        body.dark-mode .fa-calendar-check,
        body.dark-mode .fa-calendar-plus,
        body.dark-mode .fa-calendar-alt,
        body.dark-mode .fa-calendar-days {
            color: #fbbf24 !important;
        }

        /* Time & Clock Icons */
        body.dark-mode .fa-clock,
        body.dark-mode .fa-hourglass,
        body.dark-mode .fa-hourglass-end,
        body.dark-mode .fa-watch,
        body.dark-mode .fa-timer {
            color: #60a5fa !important;
        }

        /* User & Profile Icons */
        body.dark-mode .fa-user,
        body.dark-mode .fa-user-circle,
        body.dark-mode .fa-user-alt,
        body.dark-mode .fa-user-check,
        body.dark-mode .fa-user-md,
        body.dark-mode .fa-id-card,
        body.dark-mode .fa-address-card,
        body.dark-mode .fa-people,
        body.dark-mode .fa-users,
        body.dark-mode .fa-user-group {
            color: #a78bfa !important;
        }

        /* History & Records Icons */
        body.dark-mode .fa-history,
        body.dark-mode .fa-list,
        body.dark-mode .fa-list-check,
        body.dark-mode .fa-file,
        body.dark-mode .fa-file-medical,
        body.dark-mode .fa-book,
        body.dark-mode .fa-scroll,
        body.dark-mode .fa-archive,
        body.dark-mode .fa-database {
            color: #34d399 !important;
        }

        /* Success & Check Icons */
        body.dark-mode .fa-check,
        body.dark-mode .fa-check-circle,
        body.dark-mode .fa-check-double,
        body.dark-mode .fa-check-square,
        body.dark-mode .fa-circle-check,
        body.dark-mode .fa-thumbs-up,
        body.dark-mode .fa-checkmark {
            color: #4ade80 !important;
        }

        /* Error & Delete Icons */
        body.dark-mode .fa-times,
        body.dark-mode .fa-times-circle,
        body.dark-mode .fa-xmark,
        body.dark-mode .fa-x,
        body.dark-mode .fa-circle-xmark,
        body.dark-mode .fa-ban,
        body.dark-mode .fa-trash,
        body.dark-mode .fa-trash-alt,
        body.dark-mode .fa-delete,
        body.dark-mode .fa-close {
            color: #f87171 !important;
        }

        /* Notification & Bell Icons */
        body.dark-mode .fa-bell,
        body.dark-mode .fa-bell-alt,
        body.dark-mode .fa-bell-slash {
            color: #fbbf24 !important;
        }

        /* Security & Lock Icons */
        body.dark-mode .fa-lock,
        body.dark-mode .fa-lock-alt,
        body.dark-mode .fa-padlock,
        body.dark-mode .fa-key,
        body.dark-mode .fa-password,
        body.dark-mode .fa-shield,
        body.dark-mode .fa-shield-check {
            color: #60a5fa !important;
        }

        /* Settings & Configuration Icons */
        body.dark-mode .fa-cog,
        body.dark-mode .fa-gear,
        body.dark-mode .fa-sliders,
        body.dark-mode .fa-sliders-h,
        body.dark-mode .fa-wrench,
        body.dark-mode .fa-tools,
        body.dark-mode .fa-screwdriver {
            color: #34d399 !important;
        }

        /* Theme & Appearance Icons */
        body.dark-mode .fa-moon,
        body.dark-mode .fa-sun,
        body.dark-mode .fa-star,
        body.dark-mode .fa-palette {
            color: #fbbf24 !important;
        }

        /* Logout & Exit Icons */
        body.dark-mode .fa-sign-out,
        body.dark-mode .fa-sign-out-alt,
        body.dark-mode .fa-door-open,
        body.dark-mode .fa-right-from-bracket,
        body.dark-mode .fa-arrow-right-from-bracket {
            color: #f87171 !important;
        }

        /* Warning & Alert Icons */
        body.dark-mode .fa-exclamation,
        body.dark-mode .fa-exclamation-circle,
        body.dark-mode .fa-exclamation-triangle,
        body.dark-mode .fa-triangle-exclamation,
        body.dark-mode .fa-warning,
        body.dark-mode .fa-alert {
            color: #fbbf24 !important;
        }

        /* Medical & Health Icons */
        body.dark-mode .fa-stethoscope,
        body.dark-mode .fa-heart,
        body.dark-mode .fa-heart-pulse,
        body.dark-mode .fa-pulse,
        body.dark-mode .fa-hospital,
        body.dark-mode .fa-hospital-alt,
        body.dark-mode .fa-clinic-medical,
        body.dark-mode .fa-pills,
        body.dark-mode .fa-capsules,
        body.dark-mode .fa-prescription,
        body.dark-mode .fa-medicine,
        body.dark-mode .fa-bottle,
        body.dark-mode .fa-syringe,
        body.dark-mode .fa-bandage,
        body.dark-mode .fa-microscope,
        body.dark-mode .fa-dna {
            color: #ef4444 !important;
        }

        /* Action & Navigation Icons */
        body.dark-mode .fa-arrow-right,
        body.dark-mode .fa-arrow-left,
        body.dark-mode .fa-arrow-up,
        body.dark-mode .fa-arrow-down,
        body.dark-mode .fa-arrow-right-long,
        body.dark-mode .fa-arrow-left-long,
        body.dark-mode .fa-chevron-right,
        body.dark-mode .fa-chevron-left,
        body.dark-mode .fa-chevron-down,
        body.dark-mode .fa-chevron-up,
        body.dark-mode .fa-angles-right,
        body.dark-mode .fa-angles-left,
        body.dark-mode .fa-play,
        body.dark-mode .fa-pause,
        body.dark-mode .fa-next {
            color: #60a5fa !important;
        }

        /* Plus & Add Icons */
        body.dark-mode .fa-plus,
        body.dark-mode .fa-plus-circle,
        body.dark-mode .fa-circle-plus,
        body.dark-mode .fa-add {
            color: #4ade80 !important;
        }

        /* Minus & Remove Icons */
        body.dark-mode .fa-minus,
        body.dark-mode .fa-minus-circle,
        body.dark-mode .fa-circle-minus {
            color: #f87171 !important;
        }

        /* Search & Find Icons */
        body.dark-mode .fa-search,
        body.dark-mode .fa-magnifying-glass,
        body.dark-mode .fa-zoom-in,
        body.dark-mode .fa-binoculars {
            color: #a78bfa !important;
        }

        /* Download & Upload Icons */
        body.dark-mode .fa-download,
        body.dark-mode .fa-upload,
        body.dark-mode .fa-cloud-download,
        body.dark-mode .fa-cloud-upload {
            color: #60a5fa !important;
        }

        /* Edit & Edit Icons */
        body.dark-mode .fa-edit,
        body.dark-mode .fa-pencil,
        body.dark-mode .fa-pencil-alt,
        body.dark-mode .fa-pen,
        body.dark-mode .fa-pen-to-square {
            color: #fbbf24 !important;
        }

        /* Copy & Duplicate Icons */
        body.dark-mode .fa-copy,
        body.dark-mode .fa-clone,
        body.dark-mode .fa-duplicate {
            color: #34d399 !important;
        }

        /* Filter & Sort Icons */
        body.dark-mode .fa-filter,
        body.dark-mode .fa-funnel,
        body.dark-mode .fa-sort,
        body.dark-mode .fa-sort-down,
        body.dark-mode .fa-sort-up {
            color: #a78bfa !important;
        }

        /* Print & Save Icons */
        body.dark-mode .fa-print,
        body.dark-mode .fa-save,
        body.dark-mode .fa-floppy-disk,
        body.dark-mode .fa-download {
            color: #60a5fa !important;
        }

        /* Phone & Communication Icons */
        body.dark-mode .fa-phone,
        body.dark-mode .fa-phone-alt,
        body.dark-mode .fa-mobile,
        body.dark-mode .fa-mobile-alt,
        body.dark-mode .fa-phone-square,
        body.dark-mode .fa-envelope,
        body.dark-mode .fa-envelope-open,
        body.dark-mode .fa-envelope-square,
        body.dark-mode .fa-mail,
        body.dark-mode .fa-sms,
        body.dark-mode .fa-message {
            color: #4ade80 !important;
        }

        /* Location & Map Icons */
        body.dark-mode .fa-location,
        body.dark-mode .fa-location-dot,
        body.dark-mode .fa-map,
        body.dark-mode .fa-map-location,
        body.dark-mode .fa-compass,
        body.dark-mode .fa-location-pin {
            color: #ef4444 !important;
        }

        /* Document & Clipboard Icons */
        body.dark-mode .fa-clipboard,
        body.dark-mode .fa-clipboard-check,
        body.dark-mode .fa-clipboard-list,
        body.dark-mode .fa-document,
        body.dark-mode .fa-file-text {
            color: #a78bfa !important;
        }

        /* Barcode & QR Icons */
        body.dark-mode .fa-barcode,
        body.dark-mode .fa-qrcode,
        body.dark-mode .fa-barcode-read,
        body.dark-mode .fa-scanner {
            color: #4ade80 !important;
        }

        /* Dashboard & View Icons */
        body.dark-mode .fa-chart,
        body.dark-mode .fa-chart-bar,
        body.dark-mode .fa-chart-line,
        body.dark-mode .fa-chart-pie,
        body.dark-mode .fa-dashboard,
        body.dark-mode .fa-eye,
        body.dark-mode .fa-eye-alt {
            color: #60a5fa !important;
        }

        /* ================================================================
           LOGOUT BUTTON - DARK MODE STYLING
           ================================================================ */
        
        /* Logout button with border in dark mode */
        body.dark-mode button[onclick="confirmResidentLogout()"],
        body.dark-mode button[onclick*="confirmResidentLogout"] {
            border: 2px solid #f87171 !important;
            background-color: #dc2626 !important;
            color: white !important;
        }

        body.dark-mode button[onclick="confirmResidentLogout()"]:hover,
        body.dark-mode button[onclick*="confirmResidentLogout"]:hover {
            border-color: #fca5a5 !important;
            background-color: #b91c1c !important;
        }

        /* Modal background in dark mode */
        body.dark-mode #residentLogoutConfirmModal {
            background-color: rgba(0, 0, 0, 0.7) !important;
        }

        /* Modal dialog in dark mode */
        body.dark-mode #residentLogoutConfirmModal [style*="background-color: white"] {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        /* Modal header in dark mode */
        body.dark-mode #residentLogoutConfirmModal [style*="background-color: #f8fafc"] {
            background-color: #0f172a !important;
            border-color: #334155 !important;
        }

        /* Modal content text in dark mode */
        body.dark-mode #residentLogoutConfirmModal [style*="color: #64748b"] {
            color: #cbd5e1 !important;
        }

        body.dark-mode #residentLogoutConfirmModal [style*="color: #1e293b"] {
            color: #f1f5f9 !important;
        }

        /* Cancel button in dark mode */
        body.dark-mode button[onclick="closeResidentLogoutConfirmModal()"] {
            background-color: #334155 !important;
            color: #e2e8f0 !important;
            border: 1px solid #475569 !important;
        }

        body.dark-mode button[onclick="closeResidentLogoutConfirmModal()"]:hover {
            background-color: #475569 !important;
            color: #f1f5f9 !important;
        }

        /* ================================================================
           SETTINGS MODAL BUTTONS - DARK MODE STYLING
           ================================================================ */
        
        /* Settings modal background in dark mode */
        body.dark-mode #settingsModal {
            background-color: rgba(0, 0, 0, 0.7) !important;
        }

        /* Settings modal dialog in dark mode */
        body.dark-mode #settingsModal .bg-white {
            background-color: #1e293b !important;
        }

        /* Settings modal header border in dark mode */
        body.dark-mode #settingsModal [style*="border-bottom: 1px solid #e2e8f0"] {
            border-color: #334155 !important;
            background-color: #0f172a !important;
        }

        /* Settings modal header title in dark mode */
        body.dark-mode #settingsModal [style*="color: #1e293b"] {
            color: #f1f5f9 !important;
        }

        /* Settings modal section headings in dark mode */
        body.dark-mode #settingsModal h3 {
            color: #f1f5f9 !important;
        }

        /* Settings modal labels text in dark mode */
        body.dark-mode #settingsModal [style*="color: #475569"] {
            color: #cbd5e1 !important;
        }

        /* Close button in settings modal (dark mode) */
        body.dark-mode button[onclick="closeSettingsModal()"] {
            background-color: #334155 !important;
            color: #e2e8f0 !important;
            border: 1px solid #475569 !important;
        }

        body.dark-mode button[onclick="closeSettingsModal()"]:hover {
            background-color: #475569 !important;
            color: #f1f5f9 !important;
        }

        /* Save button in settings modal (dark mode) */
        body.dark-mode button[onclick="saveSettings()"] {
            background-color: #454B1B !important;
            color: white !important;
            border: 2px solid #6b7c2f !important;
        }

        body.dark-mode button[onclick="saveSettings()"]:hover {
            background-color: #5a6324 !important;
            border-color: #8ba83f !important;
        }

        /* Change Password button in settings modal (dark mode) */
        body.dark-mode button[onclick="changePassword()"] {
            background-color: #3b82f6 !important;
            color: white !important;
            border: 1px solid #60a5fa !important;
        }

        body.dark-mode button[onclick="changePassword()"]:hover {
            background-color: #2563eb !important;
            border-color: #93c5fd !important;
        }

        /* Settings modal footer border in dark mode */
        body.dark-mode #settingsModal [style*="border-top: 1px solid"] {
            border-color: #334155 !important;
        }

        /* Light color text for all labels - admin panel style */
        body.dark-mode label,
        body.dark-mode span,
        body.dark-mode .text-sm,
        body.dark-mode [class*="text-sm"],
        body.dark-mode small,
        body.dark-mode h2,
        body.dark-mode h3,
        body.dark-mode h4 {
            color: #cbd5e1 !important;
        }

        /* Stat cards with gradient backgrounds in dark mode */
        body.dark-mode .bg-blue-50 {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%) !important;
        }

        body.dark-mode .bg-green-50 {
            background: linear-gradient(135deg, #15803d 0%, #16a34a 100%) !important;
        }

        body.dark-mode .bg-orange-50 {
            background: linear-gradient(135deg, #92400e 0%, #b45309 100%) !important;
        }

        body.dark-mode .bg-red-50 {
            background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%) !important;
        }

        body.dark-mode .bg-purple-50 {
            background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 100%) !important;
        }

        /* Ensure text is visible on gradient backgrounds */
        body.dark-mode .text-blue-700 {
            color: #bfdbfe !important;
        }

        body.dark-mode .text-green-700 {
            color: #86efac !important;
        }

        body.dark-mode .text-orange-700 {
            color: #fed7aa !important;
        }

        body.dark-mode .text-red-700 {
            color: #fca5a5 !important;
        }

        body.dark-mode .text-purple-700 {
            color: #d8b4fe !important;
        }

        /* Secondary text colors */
        body.dark-mode .text-blue-600 {
            color: #93c5fd !important;
        }

        body.dark-mode .text-green-600 {
            color: #6ee7b7 !important;
        }

        body.dark-mode .text-orange-600 {
            color: #fdba74 !important;
        }

        body.dark-mode .text-red-600 {
            color: #f87171 !important;
        }

        body.dark-mode .text-purple-600 {
            color: #c084fc !important;
        }

        /* Action Column Buttons - Dark Mode */
        /* Emerald/Green buttons */
        body.dark-mode .bg-emerald-100 {
            background-color: rgba(52, 211, 153, 0.35) !important;
            border: 1px solid rgba(16, 185, 129, 0.5) !important;
        }

        body.dark-mode .bg-emerald-100:hover {
            background-color: rgba(52, 211, 153, 0.45) !important;
        }

        body.dark-mode .text-emerald-700 {
            color: #a7f3d0 !important;
        }

        /* Red buttons */
        body.dark-mode .bg-red-100 {
            background-color: rgba(248, 113, 113, 0.35) !important;
            border: 1px solid rgba(239, 68, 68, 0.5) !important;
        }

        body.dark-mode .bg-red-100:hover {
            background-color: rgba(248, 113, 113, 0.45) !important;
        }

        body.dark-mode .text-red-700 {
            color: #fecaca !important;
        }

        /* Blue buttons */
        body.dark-mode .bg-blue-100 {
            background-color: rgba(96, 165, 250, 0.35) !important;
            border: 1px solid rgba(59, 130, 246, 0.5) !important;
        }

        body.dark-mode .bg-blue-100:hover {
            background-color: rgba(96, 165, 250, 0.45) !important;
        }

        body.dark-mode .text-blue-700 {
            color: #dbeafe !important;
        }

        /* Purple buttons */
        body.dark-mode .bg-purple-100 {
            background-color: rgba(196, 140, 255, 0.35) !important;
            border: 1px solid rgba(168, 85, 247, 0.5) !important;
        }

        body.dark-mode .bg-purple-100:hover {
            background-color: rgba(196, 140, 255, 0.45) !important;
        }

        body.dark-mode .text-purple-700 {
            color: #e9d5ff !important;
        }

        /* Additional hover states */
        body.dark-mode .bg-emerald-200 {
            background-color: rgba(52, 211, 153, 0.3) !important;
        }

        body.dark-mode .bg-emerald-200:hover {
            background-color: rgba(52, 211, 153, 0.45) !important;
        }

        body.dark-mode .bg-red-200 {
            background-color: rgba(248, 113, 113, 0.3) !important;
        }

        body.dark-mode .bg-red-200:hover {
            background-color: rgba(248, 113, 113, 0.45) !important;
        }

        body.dark-mode .bg-blue-200 {
            background-color: rgba(96, 165, 250, 0.3) !important;
        }

        body.dark-mode .bg-blue-200:hover {
            background-color: rgba(96, 165, 250, 0.45) !important;
        }

        body.dark-mode .bg-purple-200 {
            background-color: rgba(196, 140, 255, 0.3) !important;
        }

        body.dark-mode .bg-purple-200:hover {
            background-color: rgba(196, 140, 255, 0.45) !important;
        }

        /* Image Cropper Modal Styles */
        .cropper-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            animation: fadeIn 0.3s ease-out;
            padding: 20px;
            box-sizing: border-box;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .cropper-modal-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .cropper-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .cropper-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #454B1B;
        }

        .cropper-close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #64748b;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .cropper-close-btn:hover {
            background: #e2e8f0;
            color: #454B1B;
        }

        .cropper-body {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
            overflow-y: auto;
            max-height: 500px;
        }

        .cropper-preview-container {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
            overflow: hidden;
            width: 100%;
            height: 350px;
        }

        #cropperCanvas {
            max-width: 100%;
            max-height: 100%;
            display: block;
        }

        .cropper-controls {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .control-group label {
            font-weight: 600;
            color: #454B1B;
            font-size: 14px;
        }

        .control-group input.slider {
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #e2e8f0;
            outline: none;
            -webkit-appearance: none;
            appearance: none;
        }

        .control-group input.slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #454B1B;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .control-group input.slider::-webkit-slider-thumb:hover {
            background: #3a3e15;
            box-shadow: 0 0 10px rgba(69, 75, 27, 0.4);
        }

        .control-group input.slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #454B1B;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .control-group input.slider::-moz-range-thumb:hover {
            background: #3a3e15;
            box-shadow: 0 0 10px rgba(69, 75, 27, 0.4);
        }

        #zoomValue {
            display: inline-block;
            background: #454B1B;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            width: fit-content;
        }

        .control-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .control-group label {
            width: 100%;
            margin-bottom: 4px;
        }

        .control-btn {
            flex: 1;
            min-width: 100px;
            padding: 8px 12px;
            background: #f1f5f9;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #454B1B;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .control-btn:hover {
            background: #e2e8f0;
            border-color: #454B1B;
            box-shadow: 0 2px 8px rgba(69, 75, 27, 0.15);
        }

        .cropper-footer {
            display: flex;
            gap: 12px;
            padding: 16px 20px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            justify-content: flex-end;
        }

        .cropper-btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .cropper-btn-cancel {
            background: #e2e8f0;
            color: #454B1B;
        }

        .cropper-btn-cancel:hover {
            background: #cbd5e1;
        }

        .cropper-btn-confirm {
            background: #454B1B;
            color: white;
        }

        .cropper-btn-confirm:hover {
            background: #3a3e15;
            box-shadow: 0 4px 12px rgba(69, 75, 27, 0.3);
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel.mobile-sidebar,
        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .mobile-sidebar-header {
            background: #111a36 !important;
            border-color: rgba(51, 65, 85, 0.5) !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel img[alt="ScanCare"] {
            background: #1a254b !important;
            border: 1px solid rgba(51, 65, 85, 0.5);
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-title {
            color: #f8fafc !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-subtitle {
            color: #94a3b8 !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item {
            background: transparent !important;
            border-color: transparent !important;
            color: #cbd5e1 !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item:hover {
            background: rgba(148, 163, 184, 0.08) !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item.active {
            background: linear-gradient(to right, #10b981, #0d9488) !important;
            border-color: rgba(16, 185, 129, 0.65) !important;
            color: #f8fafc !important;
            box-shadow: 0 0 18px rgba(16, 185, 129, 0.18) !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item i {
            background: linear-gradient(135deg, #e8f5e9, #d4e9db) !important;
            color: #2d5016 !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item:hover i {
            background: linear-gradient(135deg, #c8e6c9, #a5d6a7) !important;
            color: #1b5e20 !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item .fa-calendar-alt,
        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item .fa-bullhorn {
            color: #fbbf24 !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item .fa-file-arrow-down,
        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item .fa-history {
            color: #34d399 !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item .fa-id-card {
            color: #a78bfa !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item .fa-shield-virus {
            color: #60a5fa !important;
        }

        :is(html.dark, html.dark-mode, body.dark-mode) #mobileMenuPanel .sidebar-item .fa-pills {
            color: #ef4444 !important;
        }
    </style>
</head>
<body class="bg-[#F8FAF6] text-slate-800 font-body antialiased min-h-screen selection:bg-[#454B1B] selection:text-white">

    <!-- RESPONSIVE NAVIGATION BAR - CLEAN HEADER MATCHING BHW DASHBOARD -->
    <nav class="border-b border-[#454B1B]/10 sticky top-0 w-full bg-white/95 backdrop-blur-md z-50 shadow-sm">
        <div class="max-w-7xl mx-auto w-full px-3 sm:px-6 lg:px-12 py-4 h-20 flex items-center justify-between gap-2 sm:gap-6">
            <div class="flex min-w-0 items-center gap-2 sm:gap-6">
                <button id="mobileMenuToggleBtn" type="button" class="mobile-nav-toggle" aria-expanded="false" aria-controls="mobileMenuPanel">
                    <span class="mobile-nav-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
                <a href="landing.php" class="flex min-w-0 items-center gap-2 sm:gap-3 flex-shrink-0 hover:opacity-80 transition-opacity">
                    <img src="images/SClogo.png" alt="ScanCare Logo" class="h-9 w-9 sm:h-12 sm:w-12 object-contain">
                    <div>
                        <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">
                            Scan<span style="color: #454B1B;">Care</span>
                        </span>
                        <p class="text-xs text-slate-500 mt-0.5">Resident dashboard</p>
                    </div>
                </a>
            </div>

            <!-- User Profile Section - Right Aligned -->
            <div class="flex shrink-0 items-center gap-2 sm:gap-6">
                <?php if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): ?>
                    <div class="absolute top-24 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-3xl px-6">
                        <div class="bg-red-50 border border-red-200 text-red-800 rounded-2xl px-4 py-3 shadow-sm">
                            <strong class="font-semibold">Notice:</strong>
                            <span><?php echo htmlspecialchars($_SESSION['error_message']); ?></span>
                        </div>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_type']) && isset($_SESSION['resident_id'])): ?>
                    <div class="hidden md:flex flex-col text-left">
                        <div id="residentProfileName" style="color: #454B1B; font-weight: 600; font-size: 14px;">
                            <?php echo htmlspecialchars(substr($_SESSION['user_name'] ?? 'Resident', 0, 25)); ?>
                        </div>
                        <p class="text-xs text-slate-500">Resident • Barangay Gutad</p>
                    </div>
                    
                    <div class="profile-dropdown-container flex items-center gap-3">
                        <button id="profileDropdownBtn" onclick="toggleProfileDropdown()" class="profile-dropdown-btn">
                            <img id="profileImageNavbar" src="" alt="Profile picture" style="display: none; width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <div id="profileInitials" class="profile-pic-navbar-text">--</div>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div id="profileDropdownMenu" class="profile-dropdown-menu" style="display: none;">
                            <div class="profile-dropdown-header">
                                <img id="dropdownProfileImage" src="" alt="Profile" style="display: none;" class="dropdown-profile-img">
                                <div id="dropdownInitials" class="dropdown-profile-initials">--</div>
                                <div>
                                    <div class="dropdown-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Resident'); ?></div>
                                    <div class="dropdown-role">Resident Account</div>
                                </div>
                            </div>
                            
                            <div class="profile-dropdown-divider"></div>
                            
                            <a href="javascript:void(0)" onclick="document.getElementById('profileUploadInput').click()" class="dropdown-menu-item">
                                <i class="fas fa-camera"></i> Change Photo
                            </a>
                            <button type="button" onclick="openSettingsModal()" class="dropdown-menu-item" role="menuitem">
                                <i class="fas fa-cog"></i> Settings
                            </button>
                            
                            <div class="profile-dropdown-divider"></div>
                            
                            <a href="javascript:void(0)" onclick="openResidentLogoutConfirmModal()" class="dropdown-menu-item logout-item">
                                <i class="fas fa-sign-out-alt"></i> Log Out
                            </a>
                        </div>
                    </div>
                    <button type="button" id="dashboardThemeToggle" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-[#454B1B] transition hover:bg-[#454B1B]/10 dark:text-[#a3b14e] dark:hover:bg-[#a3b14e]/15 sm:h-11 sm:w-11" aria-label="Toggle dark mode" title="Toggle dark mode">
                        <i class="fas fa-moon" aria-hidden="true"></i>
                    </button>
                    
                    <input type="file" id="profileUploadInput" class="profile-upload-input" accept="image/*" onchange="handleProfileUpload(event)">
                    
                    <!-- Image Cropper Modal -->
                    <div id="cropperModal" class="cropper-modal" style="display: none;">
                        <div class="cropper-modal-content">
                            <div class="cropper-header">
                                <h3>Adjust Your Profile Picture</h3>
                                <button type="button" class="cropper-close-btn" onclick="closeCropperModal()">×</button>
                            </div>
                            
                            <div class="cropper-body">
                                <div class="cropper-preview-container" id="cropperContainer">
                                    <canvas id="cropperCanvas"></canvas>
                                </div>
                                
                                <div class="cropper-controls">
                                    <div class="control-group">
                                        <label>Zoom</label>
                                        <input type="range" id="zoomSlider" min="0.5" max="3" step="0.1" value="1" oninput="updateCropperZoom(this.value)" class="slider">
                                        <span id="zoomValue">100%</span>
                                    </div>
                                    
                                    <div class="control-group">
                                        <label>Position Your Photo</label>
                                        <p style="font-size: 13px; color: #64748b; margin: 8px 0 0 0;">Drag the image to position it within the circle (for profile picture)</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="cropper-footer">
                                <button type="button" class="cropper-btn cropper-btn-cancel" onclick="closeCropperModal()">Cancel</button>
                                <button type="button" class="cropper-btn cropper-btn-confirm" onclick="confirmCropperImage()">Save & Upload</button>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <a href="login.php" style="background-color: #454B1B; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; white-space: nowrap;">Login / Signup</a>
                <?php endif; ?>
            </div>
        </div>

        <div id="mobileMenuPanel" class="mobile-sidebar">
            <div class="mobile-sidebar-header border-b border-slate-200 px-6 py-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <img src="images/SClogo.png" alt="ScanCare" class="h-10 w-10 rounded-2xl bg-[#f3f4f6] p-2 object-contain">
                        <div>
                            <div class="sidebar-title">ScanCare Menu</div>
                            <div class="sidebar-subtitle">Resident navigation</div>
                        </div>
                    </div>
                    <button type="button" onclick="toggleMobilePanel()" class="text-slate-500 hover:text-slate-900 focus:outline-none">
                        <i class="fas fa-times fa-lg"></i>
                    </button>
                </div>
            </div>
            <nav id="mobileSidebarNav" class="sidebar-nav mt-3 px-2">
                <a href="#schedule-appointment" class="sidebar-item"><i class="fas fa-calendar-alt"></i>Appointments</a>
                <a href="#community-announcements" class="sidebar-item"><i class="fas fa-bullhorn"></i>Announcements</a>
                <a href="#file-requests" class="sidebar-item"><i class="fas fa-file-arrow-down"></i>File Requests</a>
                <a href="#your-patient-id" class="sidebar-item"><i class="fas fa-id-card"></i>Your Patient ID</a>
                <a href="#community-surveillance" class="sidebar-item"><i class="fas fa-shield-virus"></i>Community Surveillance</a>
                <a href="#available-medicines" class="sidebar-item"><i class="fas fa-pills"></i>Free Medicines</a>
                <a href="#medical-history" class="sidebar-item"><i class="fas fa-history"></i>Medical Visit History</a>
            </nav>
        </div>

    </nav>

    <script>
        function updateNavbarActivePill() {
            const navWrapper = document.querySelector('.nav-links-desktop');
            const indicator = document.querySelector('.nav-active-pill');
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

        function initPill() {
            const navWrapper = document.querySelector('.nav-links-desktop');
            const indicator = document.querySelector('.nav-active-pill');
            const activeLink = navWrapper?.querySelector('.nav-link.active');
            if (!navWrapper || !indicator || !activeLink) return;

            const containerRect = navWrapper.getBoundingClientRect();
            const activeRect = activeLink.getBoundingClientRect();
            const currentLeft = activeRect.left - containerRect.left;
            const currentWidth = activeRect.width;
            const prevLeft = sessionStorage.getItem('nav_pill_left');
            const prevWidth = sessionStorage.getItem('nav_pill_width');
            const pillTransition = 'transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), width 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease';

            indicator.style.opacity = '1';
            indicator.style.height = `${activeRect.height}px`;

            if (prevLeft !== null && prevWidth !== null) {
                indicator.style.transition = 'none';
                indicator.style.transform = `translateX(${prevLeft}px)`;
                indicator.style.width = `${prevWidth}px`;
                indicator.getBoundingClientRect();
                indicator.style.transition = pillTransition;
                indicator.style.transform = `translateX(${currentLeft}px)`;
                indicator.style.width = `${currentWidth}px`;
            } else {
                indicator.style.transition = 'none';
                indicator.style.transform = `translateX(${currentLeft}px)`;
                indicator.style.width = `${currentWidth}px`;
                indicator.getBoundingClientRect();
                indicator.style.transition = pillTransition;
            }

            navWrapper.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    const clickedRect = link.getBoundingClientRect();
                    const containerRect = navWrapper.getBoundingClientRect();
                    sessionStorage.setItem('nav_pill_left', clickedRect.left - containerRect.left);
                    sessionStorage.setItem('nav_pill_width', clickedRect.width);
                });
            });
        }

        function setupNavbarViewTransitions() {
            const navWrapper = document.querySelector('.nav-links-desktop');
            if (!navWrapper || typeof document.startViewTransition !== 'function') return;

            navWrapper.querySelectorAll('a.nav-link').forEach(link => {
                link.addEventListener('click', function (event) {
                    const href = this.href;
                    if (!href || href === location.href || location.origin !== new URL(href).origin) return;
                    event.preventDefault();
                    document.startViewTransition(() => {
                        location.href = href;
                    });
                });
            });
        }

        let navbarPillResizeTimer = null;

        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('mobileMenuToggleBtn');
            const mobileMenuPanel = document.getElementById('mobileMenuPanel');

            function showResidentMobileSection(sectionId) {
                if (window.innerWidth > 767) return;
                const sections = document.querySelectorAll('.resident-mobile-section');
                sections.forEach(section => {
                    if (section.id === sectionId) {
                        section.classList.remove('mobile-hidden-section');
                    } else {
                        section.classList.add('mobile-hidden-section');
                    }
                });
            }
            window.showResidentMobileSection = showResidentMobileSection;

            function setMobilePanelState(active) {
                if (!mobileMenuPanel || !toggleBtn) return;
                mobileMenuPanel.classList.toggle('active', active);
                toggleBtn.classList.toggle('active', active);
                toggleBtn.setAttribute('aria-expanded', active ? 'true' : 'false');
            }

            function toggleMobilePanel() {
                setMobilePanelState(!mobileMenuPanel.classList.contains('active'));
            }
            window.toggleMobilePanel = toggleMobilePanel;

            if (toggleBtn && mobileMenuPanel) {
                toggleBtn.addEventListener('click', toggleMobilePanel);
            }

            showResidentMobileSection('schedule-appointment');

            if (!window.location.hash) {
                const defaultSection = document.getElementById('schedule-appointment');
                if (defaultSection) {
                    defaultSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            initPill();
            setupNavbarViewTransitions();

            const navLinks = document.querySelectorAll('#mobileMenuPanel .sidebar-item');
            navLinks.forEach(link => {
                link.addEventListener('click', function () {
                    setMobilePanelState(false);

                    const targetHash = this.getAttribute('href');
                    if (targetHash && targetHash.startsWith('#')) {
                        const target = document.querySelector(targetHash);
                        if (window.innerWidth <= 767 && target) {
                            showResidentMobileSection(target.id);
                        }
                        if (target && target.classList.contains('mobile-hidden-section')) {
                            target.classList.remove('mobile-hidden-section');
                        }
                        if (target) {
                            setTimeout(() => {
                                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }, 100);
                        }
                    }
                });
            });

            if (window.location.hash) {
                const targetOnLoad = document.querySelector(window.location.hash);
                if (window.innerWidth <= 767 && targetOnLoad) {
                    showResidentMobileSection(targetOnLoad.id);
                }
                if (targetOnLoad && targetOnLoad.classList.contains('mobile-hidden-section')) {
                    targetOnLoad.classList.remove('mobile-hidden-section');
                }
            }
        });
    </script>

    <!-- Resident Logout Confirmation Modal -->
    <div id="residentLogoutConfirmModal" style="display: none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div style="background-color: white; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); max-width: 384px; width: 100%; border: 1px solid #e2e8f0;">
            <!-- Modal Header -->
            <div style="background-color: #f8fafc; padding: 24px 24px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 18px; font-weight: 600; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-exclamation-circle" style="color: #eab308;"></i> Confirm Logout
                </h2>
                <button onclick="closeResidentLogoutConfirmModal()" style="background: none; border: none; cursor: pointer; font-size: 20px; color: #94a3b8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div style="padding: 24px;">
                <p style="color: #64748b; text-align: center; margin: 0 0 24px 0;">
                    Are you sure you want to log out? You will need to log in again to access your account.
                </p>
                
                <!-- Action Buttons -->
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

    <!-- Settings Modal -->
    <div id="settingsModal" style="display: none;" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="settingsModalTitle" onclick="if (event.target === this) closeSettingsModal()">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full" onclick="event.stopPropagation()">
            <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h2 id="settingsModalTitle" style="margin: 0; font-size: 18px; font-weight: 700; color: #1e293b;">
                    <i class="fas fa-cog" style="margin-right: 8px; color: #454B1B;"></i> Settings
                </h2>
                <button onclick="closeSettingsModal()" style="background: none; border: none; cursor: pointer; font-size: 20px; color: #94a3b8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="padding: 20px;">
                <div style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    <button onclick="changePassword()" style="width: 100%; padding: 10px; margin-bottom: 8px; background-color: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease;">
                        <i class="fas fa-key" style="margin-right: 8px;"></i> Change Password
                    </button>
                </div>
            </div>
            <div style="padding: 20px; border-top: 1px solid #e2e8f0; display: flex; gap: 12px;">
                <button onclick="closeSettingsModal()" style="flex: 1; padding: 12px 16px; border-radius: 8px; background-color: #e2e8f0; color: #1e293b; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease;">
                    Close
                </button>
                <button onclick="saveSettings()" style="flex: 1; padding: 12px 16px; border-radius: 8px; background-color: #454B1B; color: white; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease;">
                    <i class="fas fa-save" style="margin-right: 8px;"></i> Save
                </button>
            </div>
        </div>
    </div>

    <div id="changePasswordModal" style="display: none;" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="changePasswordModalTitle" onclick="if (event.target === this) closeChangePasswordModal()">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full" onclick="event.stopPropagation()">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h2 id="changePasswordModalTitle" class="text-lg font-bold text-slate-900 dark:text-white"><i class="fas fa-key mr-2 text-[#454B1B] dark:text-lime-300"></i>Change Password</h2>
                <button type="button" onclick="closeChangePasswordModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl" aria-label="Close">&times;</button>
            </div>
            <form id="changePasswordForm" onsubmit="submitPasswordChange(event)" class="p-5 space-y-4">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Current password
                    <input id="currentPasswordInput" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                </label>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200">New password
                    <input id="newPasswordInput" type="password" autocomplete="new-password" minlength="6" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                </label>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Confirm new password
                    <input id="confirmPasswordInput" type="password" autocomplete="new-password" minlength="6" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                </label>
                <p id="changePasswordMessage" class="hidden rounded-lg px-3 py-2 text-sm" role="status" aria-live="polite"></p>
                <div class="flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700 pt-4">
                    <button type="button" onclick="closeChangePasswordModal()" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-semibold hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
                    <button id="submitPasswordChangeButton" type="submit" class="px-4 py-2 rounded-lg bg-[#454B1B] text-white font-semibold hover:opacity-90">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ====================================================================
        // RESIDENT LOGOUT CONFIRMATION MODAL FUNCTIONS
        // ====================================================================
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

        // Close modal when clicking outside
        document.getElementById('residentLogoutConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeResidentLogoutConfirmModal();
            }
        });

        // ====================================================================
        // PROFILE DROPDOWN MENU FUNCTIONS
        // ====================================================================
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdownMenu');
            const btn = document.getElementById('profileDropdownBtn');
            
            if (dropdown.style.display === 'none' || dropdown.style.display === '') {
                dropdown.style.display = 'block';
                btn.classList.add('active');
            } else {
                dropdown.style.display = 'none';
                btn.classList.remove('active');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const container = document.querySelector('.profile-dropdown-container');
            if (container && !container.contains(event.target)) {
                const dropdown = document.getElementById('profileDropdownMenu');
                if (dropdown) {
                    dropdown.style.display = 'none';
                    const btn = document.getElementById('profileDropdownBtn');
                    if (btn) btn.classList.remove('active');
                }
            }
        });

        // ====================================================================
        // EDIT PROFILE MODAL FUNCTIONS
        // ====================================================================
        async function openEditProfileModal() {
            closeProfileDropdown();
            const modal = document.getElementById('editProfileModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';

                // Fetch current user profile data and populate form
                try {
                    const response = await fetch('get_profile_data.php');
                    const data = await response.json();

                    if (data.success) {
                        document.getElementById('editFirstName').value = data.firstName;
                        document.getElementById('editLastName').value = data.lastName;
                        document.getElementById('editEmail').value = data.email;
                        document.getElementById('editPhone').value = data.phone;
                    }
                } catch (error) {
                    console.error('Error fetching profile data:', error);
                }
            }
        }

        function closeEditProfileModal() {
            const modal = document.getElementById('editProfileModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // ====================================================================
        // DROPDOWN CLOSE HELPER
        // ====================================================================
        function closeProfileDropdown() {
            const dropdown = document.getElementById('profileDropdownMenu');
            const btn = document.getElementById('profileDropdownBtn');
            if (dropdown) dropdown.style.display = 'none';
            if (btn) btn.classList.remove('active');
        }

        // ====================================================================
        // SETTINGS MODAL STATE MANAGEMENT
        // ====================================================================
        let settingsSaveTriggered = false;

        function openSettingsModal() {
            closeProfileDropdown();
            settingsSaveTriggered = false;
            const modal = document.getElementById('settingsModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            modal.querySelector('button')?.focus();
        }

        function closeSettingsModal() {
            document.getElementById('settingsModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function saveSettings() {
            settingsSaveTriggered = true;
            alert('✓ Settings saved successfully!');
            closeSettingsModal();
        }

        // ====================================================================
        // CHANGE PASSWORD FUNCTION
        // ====================================================================
        function changePassword() {
            const modal = document.getElementById('changePasswordModal');
            const form = document.getElementById('changePasswordForm');
            const message = document.getElementById('changePasswordMessage');
            form.reset();
            message.textContent = '';
            message.className = 'hidden rounded-lg px-3 py-2 text-sm';
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.getElementById('currentPasswordInput').focus();
        }

        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
            document.body.style.overflow = document.getElementById('settingsModal').style.display === 'flex' ? 'hidden' : 'auto';
        }

        async function submitPasswordChange(event) {
            event.preventDefault();
            const form = event.currentTarget;
            const currentPassword = document.getElementById('currentPasswordInput').value;
            const newPassword = document.getElementById('newPasswordInput').value;
            const confirmPassword = document.getElementById('confirmPasswordInput').value;
            const message = document.getElementById('changePasswordMessage');
            const submitButton = document.getElementById('submitPasswordChangeButton');
            const showMessage = (text, success = false) => {
                message.textContent = text;
                message.className = `rounded-lg px-3 py-2 text-sm ${success ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200'}`;
            };

            if (newPassword.length < 6) {
                showMessage('New password must be at least 6 characters long.');
                return;
            }
            if (newPassword !== confirmPassword) {
                showMessage('New passwords do not match.');
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Updating...';
            try {
                const response = await fetch('change_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ currentPassword, newPassword, confirmPassword })
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    showMessage(data.error || 'Unable to change password. Please check your current password and try again.');
                    return;
                }
                showMessage(data.message || 'Password updated successfully.', true);
                form.reset();
            } catch (error) {
                showMessage('Unable to connect. Please try again.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Update Password';
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('changePasswordModal')?.style.display === 'flex') {
                closeChangePasswordModal();
                return;
            }
            if (e.key === 'Escape' && document.getElementById('residentLogoutConfirmModal').style.display !== 'none') {
                closeResidentLogoutConfirmModal();
            }
            if (e.key === 'Escape' && document.getElementById('editProfileModal')?.style.display !== 'none') {
                closeEditProfileModal();
            }
            if (e.key === 'Escape' && document.getElementById('settingsModal')?.style.display !== 'none') {
                closeSettingsModal();
            }
        });
    </script>

    <!-- Profile Picture Upload Handler -->
    <script>
        /**
         * Handle profile picture upload
         */
        // Cropper state variables
        let cropperImageData = {
            originalImage: null,
            canvas: null,
            ctx: null,
            zoom: 1,
            panX: 0,
            panY: 0,
            file: null,
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0
        };

        function handleProfileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file
            if (!validateProfileFile(file)) {
                return;
            }

            // Read the file and show cropper
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    cropperImageData.originalImage = img;
                    cropperImageData.file = file;
                    cropperImageData.zoom = 1;
                    cropperImageData.panX = 0;
                    cropperImageData.panY = 0;
                    
                    document.getElementById('zoomSlider').value = 1;
                    document.getElementById('zoomValue').textContent = '100%';
                    
                    openCropperModal();
                    drawCropperImage();
                    setupCropperDragListeners();
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        /**
         * Validate profile file
         */
        function validateProfileFile(file) {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff', 'image/svg+xml'];
            const maxFileSize = 2 * 1024 * 1024; // 2MB

            if (!allowedTypes.includes(file.type)) {
                showUploadStatus('❌ Only image files (JPG, PNG, GIF, WebP, BMP, TIFF, SVG) are allowed', 'error');
                return false;
            }

            if (file.size > maxFileSize) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                showUploadStatus(`❌ File size (${sizeMB}MB) exceeds 2MB limit`, 'error');
                return false;
            }

            return true;
        }

        // ====================================================================
        // CROPPER MODAL FUNCTIONS
        // ====================================================================
        function openCropperModal() {
            const modal = document.getElementById('cropperModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeCropperModal() {
            const modal = document.getElementById('cropperModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            // Reset file input
            document.getElementById('profileUploadInput').value = '';
        }

        function drawCropperImage() {
            const canvas = document.getElementById('cropperCanvas');
            const container = document.querySelector('.cropper-preview-container');
            
            if (!canvas || !container || !cropperImageData.originalImage) return;

            const containerWidth = container.clientWidth;
            const containerHeight = container.clientHeight;
            
            // Set canvas size
            canvas.width = containerWidth;
            canvas.height = containerHeight;

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Scale image to fit container while maintaining aspect ratio
            const imgAspect = cropperImageData.originalImage.width / cropperImageData.originalImage.height;
            const containerAspect = canvas.width / canvas.height;
            
            let imgWidth, imgHeight;
            if (imgAspect > containerAspect) {
                imgWidth = canvas.width;
                imgHeight = canvas.width / imgAspect;
            } else {
                imgHeight = canvas.height;
                imgWidth = canvas.height * imgAspect;
            }

            // Apply zoom
            imgWidth *= cropperImageData.zoom;
            imgHeight *= cropperImageData.zoom;

            // Position image with pan offset (for dragging)
            const x = (canvas.width - imgWidth) / 2 + cropperImageData.panX;
            const y = (canvas.height - imgHeight) / 2 + cropperImageData.panY;

            // Draw image
            ctx.drawImage(cropperImageData.originalImage, x, y, imgWidth, imgHeight);

            // Draw circular crop indicator (profile picture should be circular)
            const circleRadius = Math.min(canvas.width, canvas.height) / 2 - 10;
            ctx.strokeStyle = 'rgba(69, 75, 27, 0.7)';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.arc(canvas.width / 2, canvas.height / 2, circleRadius, 0, Math.PI * 2);
            ctx.stroke();
            
            // Add center circle marker
            ctx.fillStyle = 'rgba(69, 75, 27, 0.3)';
            ctx.beginPath();
            ctx.arc(canvas.width / 2, canvas.height / 2, 6, 0, Math.PI * 2);
            ctx.fill();
        }

        function updateCropperZoom(value) {
            cropperImageData.zoom = parseFloat(value);
            const percentage = Math.round(value * 100);
            document.getElementById('zoomValue').textContent = percentage + '%';
            drawCropperImage();
        }

        function setupCropperDragListeners() {
            const canvas = document.getElementById('cropperCanvas');
            if (!canvas) return;

            canvas.addEventListener('mousedown', function(e) {
                cropperImageData.isDragging = true;
                cropperImageData.dragStartX = e.clientX;
                cropperImageData.dragStartY = e.clientY;
            });

            document.addEventListener('mousemove', function(e) {
                if (!cropperImageData.isDragging) return;

                const deltaX = e.clientX - cropperImageData.dragStartX;
                const deltaY = e.clientY - cropperImageData.dragStartY;

                cropperImageData.panX += deltaX;
                cropperImageData.panY += deltaY;

                cropperImageData.dragStartX = e.clientX;
                cropperImageData.dragStartY = e.clientY;

                drawCropperImage();
            });

            document.addEventListener('mouseup', function() {
                cropperImageData.isDragging = false;
            });

            // Touch support for mobile
            canvas.addEventListener('touchstart', function(e) {
                if (e.touches.length === 1) {
                    cropperImageData.isDragging = true;
                    cropperImageData.dragStartX = e.touches[0].clientX;
                    cropperImageData.dragStartY = e.touches[0].clientY;
                }
            });

            document.addEventListener('touchmove', function(e) {
                if (!cropperImageData.isDragging || e.touches.length !== 1) return;

                const deltaX = e.touches[0].clientX - cropperImageData.dragStartX;
                const deltaY = e.touches[0].clientY - cropperImageData.dragStartY;

                cropperImageData.panX += deltaX;
                cropperImageData.panY += deltaY;

                cropperImageData.dragStartX = e.touches[0].clientX;
                cropperImageData.dragStartY = e.touches[0].clientY;

                drawCropperImage();
            });

            document.addEventListener('touchend', function() {
                cropperImageData.isDragging = false;
            });
        }

        async function confirmCropperImage() {
            const canvas = document.getElementById('cropperCanvas');
            if (!canvas || !cropperImageData.originalImage) {
                showUploadStatus('❌ Error: Canvas not ready', 'error');
                return;
            }

            try {
                // Convert canvas to blob
                canvas.toBlob(async function(blob) {
                    if (!blob) {
                        showUploadStatus('❌ Error: Failed to create image', 'error');
                        return;
                    }

                    // Create FormData with the cropped image
                    const formData = new FormData();
                    formData.append('profile_picture', blob, 'cropped_profile.png');

                    const response = await fetch('upload_profile.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    // Parse response text first to debug
                    const responseText = await response.text();
                    console.log('Upload response:', responseText);

                    // Try to parse JSON
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('Failed to parse response as JSON:', parseError);
                        throw new Error('Invalid server response: ' + responseText.substring(0, 200));
                    }

                    // Check response status from parsed data
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || data.error || 'HTTP error! status: ' + response.status);
                    }

                    if (data.success) {
                        // Update all profile pictures
                        const imagePath = data.image_url;
                        
                        // Update navbar
                        const navbarImg = document.getElementById('profileImageNavbar');
                        if (navbarImg) {
                            navbarImg.src = imagePath;
                            navbarImg.style.display = 'block';
                            document.getElementById('profileInitials').style.display = 'none';
                        }

                        // Update mobile
                        const mobileImg = document.getElementById('profileImageMobile');
                        if (mobileImg) {
                            mobileImg.src = imagePath;
                            mobileImg.style.display = 'block';
                            document.getElementById('profileInitialsMobile').style.display = 'none';
                        }
                        
                        // Update dropdown
                        const dropdownImg = document.getElementById('dropdownProfileImage');
                        if (dropdownImg) {
                            dropdownImg.src = imagePath;
                            dropdownImg.style.display = 'block';
                        }
                        const dropdownInitials = document.getElementById('dropdownInitials');
                        if (dropdownInitials) {
                            dropdownInitials.style.display = 'none';
                        }

                        showUploadStatus('✓ Profile picture updated successfully!', 'success');
                        closeCropperModal();
                    } else {
                        showUploadStatus('❌ Upload failed: ' + (data.message || data.error || 'Server reported failure'), 'error');
                    }
                }, 'image/png');
            } catch (error) {
                console.error('Upload error:', error);
                showUploadStatus('❌ ' + error.message, 'error');
            }
        }

        /**
         * Show upload status message
         */
        function showUploadStatus(message, type) {
            // Create status element if it doesn't exist
            let statusEl = document.getElementById('profileUploadStatusMsg');
            if (!statusEl) {
                statusEl = document.createElement('div');
                statusEl.id = 'profileUploadStatusMsg';
                statusEl.style.position = 'fixed';
                statusEl.style.top = '80px';
                statusEl.style.right = '20px';
                statusEl.style.zIndex = '1000';
                statusEl.style.borderRadius = '8px';
                statusEl.style.padding = '12px 20px';
                statusEl.style.maxWidth = '300px';
                document.body.appendChild(statusEl);
            }

            statusEl.textContent = message;
            statusEl.style.display = 'block';
            statusEl.className = 'profile-upload-status ' + type;

            // Auto-hide after 4 seconds
            setTimeout(() => {
                statusEl.style.display = 'none';
            }, 4000);
        }

        /**
         * Initialize profile picture on page load
         */
        document.addEventListener('DOMContentLoaded', function() {
            initializeProfilePicture();
        });

        async function initializeProfilePicture() {
            try {
                // Fetch stored profile picture from database
                const response = await fetch('get_profile_picture.php');
                const data = await response.json();
                
                if (data.success && data.has_picture && data.profile_picture) {
                    // Display the stored profile picture in navbar
                    const navbarImg = document.getElementById('profileImageNavbar');
                    if (navbarImg) {
                        navbarImg.src = data.profile_picture;
                        navbarImg.style.display = 'block';
                    }
                    
                    const mobileImg = document.getElementById('profileImageMobile');
                    if (mobileImg) {
                        mobileImg.src = data.profile_picture;
                        mobileImg.style.display = 'block';
                    }
                    
                    // Display in dropdown
                    const dropdownImg = document.getElementById('dropdownProfileImage');
                    if (dropdownImg) {
                        dropdownImg.src = data.profile_picture;
                        dropdownImg.style.display = 'block';
                    }
                    
                    // Hide initials
                    const profileInitials = document.getElementById('profileInitials');
                    if (profileInitials) profileInitials.style.display = 'none';
                    
                    const profileInitialsMobile = document.getElementById('profileInitialsMobile');
                    if (profileInitialsMobile) profileInitialsMobile.style.display = 'none';
                    
                    const dropdownInitials = document.getElementById('dropdownInitials');
                    if (dropdownInitials) dropdownInitials.style.display = 'none';
                } else {
                    // No profile picture stored, show initials
                    const userName = document.getElementById('residentProfileName');
                    
                    if (userName) {
                        const nameText = userName.textContent.trim();
                        const initials = getInitials(nameText);
                        
                        const profileInitials = document.getElementById('profileInitials');
                        const profileInitialsMobile = document.getElementById('profileInitialsMobile');
                        const dropdownInitials = document.getElementById('dropdownInitials');
                        
                        if (profileInitials) {
                            profileInitials.textContent = initials;
                            profileInitials.style.display = 'block';
                        }
                        if (profileInitialsMobile) {
                            profileInitialsMobile.textContent = initials;
                            profileInitialsMobile.style.display = 'block';
                        }
                        if (dropdownInitials) {
                            dropdownInitials.textContent = initials;
                            dropdownInitials.style.display = 'flex';
                        }
                        
                        // Hide images
                        const navbarImg = document.getElementById('profileImageNavbar');
                        if (navbarImg) navbarImg.style.display = 'none';
                        
                        const mobileImg = document.getElementById('profileImageMobile');
                        if (mobileImg) mobileImg.style.display = 'none';
                        
                        const dropdownImg = document.getElementById('dropdownProfileImage');
                        if (dropdownImg) dropdownImg.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error initializing profile picture:', error);
                
                // Fallback to initials on error
                const userName = document.getElementById('residentProfileName');
                
                if (userName) {
                    const nameText = userName.textContent.trim();
                    const initials = getInitials(nameText);
                    
                    const profileInitials = document.getElementById('profileInitials');
                    const profileInitialsMobile = document.getElementById('profileInitialsMobile');
                    const dropdownInitials = document.getElementById('dropdownInitials');
                    
                    if (profileInitials) {
                        profileInitials.textContent = initials;
                        profileInitials.style.display = 'block';
                    }
                    if (profileInitialsMobile) {
                        profileInitialsMobile.textContent = initials;
                        profileInitialsMobile.style.display = 'block';
                    }
                    if (dropdownInitials) {
                        dropdownInitials.textContent = initials;
                        dropdownInitials.style.display = 'flex';
                    }
                }
            }
        }

        function getInitials(fullName) {
            if (!fullName) return '--';
            const parts = fullName.trim().split(' ');
            if (parts.length >= 2) {
                return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
            }
            return fullName.substring(0, 2).toUpperCase();
        }
    </script>

    <?php require_once 'dashboard_resident.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const themeToggle = document.getElementById('dashboardThemeToggle');
            if (!themeToggle) return;

            const updateThemeIcon = () => {
                const isDark = document.documentElement.classList.contains('dark');
                themeToggle.innerHTML = isDark
                    ? '<i class="fas fa-sun" aria-hidden="true" style="color: #fbbf24 !important;"></i>'
                    : '<i class="fas fa-moon" aria-hidden="true"></i>';
            };

            themeToggle.addEventListener('click', () => {
                const isDark = !document.documentElement.classList.contains('dark');
                if (window.ScanCareTheme) {
                    window.ScanCareTheme.set(isDark ? 'dark' : 'light');
                } else {
                    document.documentElement.classList.toggle('dark', isDark);
                    document.documentElement.classList.toggle('dark-mode', isDark);
                    document.body.classList.toggle('dark-mode', isDark);
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                    localStorage.setItem('scancare_theme', isDark ? 'dark' : 'light');
                    localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
                }
                updateThemeIcon();
            });

            updateThemeIcon();
        });
    </script>
</html>
