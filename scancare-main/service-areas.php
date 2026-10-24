<?php
session_start();

$serviceNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$serviceDay = (int) $serviceNow->format('N');
$serviceMinutes = ((int) $serviceNow->format('G') * 60) + (int) $serviceNow->format('i');
$serviceOpeningMinutes = 8 * 60;
$serviceClosingMinutes = $serviceDay === 6 ? 12 * 60 : 17 * 60;
$serviceIsOpen = $serviceDay <= 6
    && $serviceMinutes >= $serviceOpeningMinutes
    && $serviceMinutes < $serviceClosingMinutes;
$serviceStatusLabel = $serviceIsOpen ? 'Open Now' : 'Closed Now';
$serviceStatusClass = $serviceIsOpen ? 'open' : 'closed';


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
    <title>Service Areas — ScanCare</title>
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

        /* Purok Accordion Styles */
        .purok-details {
            transition: max-height 0.3s ease-out;
        }

        .purok-details.hidden {
            display: none !important;
        }

        .purok-details i.fa-chevron-down {
            transition: transform 0.3s ease;
        }

        body {
            background-color: #f8fafc;
            background-image:
                linear-gradient(to right, rgba(69, 75, 27, 0.04) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(69, 75, 27, 0.04) 1px, transparent 1px),
                radial-gradient(circle at top left, rgba(69, 75, 27, 0.05), transparent 30%),
                linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            background-size: 24px 24px, 24px 24px, 100% 100%, 100% 100%;
            background-attachment: fixed;
        }

        .page-grid-canvas {
            position: relative;
            background: transparent;
        }

        .page-grid-canvas > section,
        .page-grid-canvas > footer {
            background: transparent;
        }

        .dark .page-grid-canvas section[class*="bg-"],
        .dark .page-grid-canvas section[class*="from-"],
        .dark .page-grid-canvas .surface-card,
        .dark .page-grid-canvas .surface-soft,
        .dark .page-grid-canvas .bg-white,
        .dark .page-grid-canvas .bg-[#F8FAF6],
        .dark .page-grid-canvas [class*="bg-white"],
        .dark .page-grid-canvas [class*="bg-[#F8FAF6]"] {
            background-color: #131c17 !important;
            backdrop-filter: blur(2px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
        }

        .dark .page-grid-canvas section[class*="bg-gradient-to-br"],
        .dark .page-grid-canvas section[class*="bg-white"],
        .dark .page-grid-canvas section[class*="bg-slate-50"],
        .dark .page-grid-canvas section[class*="bg-[#454B1B]/5"],
        .dark .page-grid-canvas section[class*="bg-[#F8FAF6]"] {
            background-image: none !important;
            background-color: transparent !important;
        }

        html:not(.dark) .page-grid-canvas section[style*="background-color: #454B1B;"],
        body:not(.dark-mode) .page-grid-canvas section[style*="background-color: #454B1B;"] {
            background-color: #454B1B !important;
        }

        .service-center-shell {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(69, 75, 27, 0.3);
            border-radius: 28px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .service-center-panel {
            display: grid;
            grid-template-columns: 1.08fr 0.92fr;
            min-height: 520px;
        }

        .service-center-map-wrap {
            position: relative;
            min-height: 100%;
            padding: 18px;
            border-right: 1px solid rgba(148, 163, 184, 0.22);
            background: linear-gradient(135deg, rgba(69, 75, 27, 0.04), rgba(255, 255, 255, 0.9));
        }

        .service-center-map {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 470px;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgba(69, 75, 27, 0.12);
            background: #e2e8f0;
        }

        .service-map-pin {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -100%);
            width: 26px;
            height: 26px;
            border-radius: 50% 50% 50% 0;
            background: linear-gradient(135deg, #687531, #454B1B);
            border: 3px solid rgba(255,255,255,0.9);
            box-shadow: 0 10px 22px rgba(69, 75, 27, 0.28);
            rotate: -45deg;
            z-index: 10;
        }

        .service-map-pin::after {
            content: "";
            position: absolute;
            inset: 6px;
            border-radius: 50%;
            background: rgba(255,255,255,0.85);
        }

        .service-center-info {
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.65);
        }

        .purok-details {
            background: #f8fafc;
            border-color: rgba(255, 255, 255, 0.1);
        }

        .service-detail-list,
        .service-detail-copy {
            color: #475569;
        }

        .service-detail-bullet {
            color: #454B1B;
        }

        html.dark .service-detail-list,
        html.dark .service-detail-copy,
        body.dark-mode .service-detail-list,
        body.dark-mode .service-detail-copy {
            color: #cbd5e1 !important;
        }

        html.dark .service-detail-bullet,
        body.dark-mode .service-detail-bullet {
            color: #a3b14e !important;
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

        main > section:nth-of-type(2) .bg-white {
            color: #0f172a;
        }

        main > section:nth-of-type(2) .bg-white p,
        main > section:nth-of-type(2) .bg-white li,
        main > section:nth-of-type(2) .bg-white .text-stone-200,
        main > section:nth-of-type(2) .bg-white .text-stone-300,
        main > section:nth-of-type(2) .bg-white .text-slate-500,
        main > section:nth-of-type(2) .bg-white .text-slate-600,
        main > section:nth-of-type(2) .bg-white .text-slate-700 {
            color: #334155 !important;
        }

        main > section:nth-of-type(2) .bg-white h3,
        main > section:nth-of-type(2) .bg-white .text-white {
            color: #0f172a !important;
        }

        .grid-canvas {
            background-color: #f8fafc;
            background-image:
                linear-gradient(to right, rgba(69, 75, 27, 0.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(69, 75, 27, 0.08) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        .dark .grid-canvas {
            background-color: #0d1310 !important;
            background-image:
                linear-gradient(to right, rgba(69, 75, 27, 0.2) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(69, 75, 27, 0.2) 1px, transparent 1px);
        }

        html.dark body,
        body.dark-mode {
            background-color: #0a0f0d;
            background-image: none;
        }

        html.dark .page-grid-canvas > section,
        body.dark-mode .page-grid-canvas > section {
            background-color: #0b0f0a !important;
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px) !important;
            background-size: 24px 24px !important;
        }

        html.dark .page-grid-canvas,
        body.dark-mode .page-grid-canvas {
            background-color: #0d1310 !important;
            background-image:
                linear-gradient(to right, rgba(69, 75, 27, 0.2) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(69, 75, 27, 0.2) 1px, transparent 1px);
        }

        html.dark .page-grid-canvas [class*="bg-white"],
        html.dark .page-grid-canvas [class*="bg-[#F8FAF6]"],
        body.dark-mode .page-grid-canvas [class*="bg-white"],
        body.dark-mode .page-grid-canvas [class*="bg-[#F8FAF6]"] {
            background-color: #131c17 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        /* These cards use an inline light background, so override it explicitly in night mode. */
        html.dark .page-grid-canvas .service-program-card,
        body.dark-mode .page-grid-canvas .service-program-card {
            background-color: #131c17 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        html.dark .purok-details,
        body.dark-mode .purok-details {
            background-color: #0f1612 !important;
        }

        html.dark .service-center-shell,
        html.dark .service-center-info,
        body.dark-mode .service-center-shell,
        body.dark-mode .service-center-info {
            background: #131c17 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        html.dark .page-grid-canvas [class*="bg-white"],
        html.dark .page-grid-canvas [class*="bg-[#F8FAF6]"],
        body.dark-mode .page-grid-canvas [class*="bg-white"],
        body.dark-mode .page-grid-canvas [class*="bg-[#F8FAF6]"] {
            background-color: #131c17 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        html.dark .page-grid-canvas h1,
        html.dark .page-grid-canvas h2,
        html.dark .page-grid-canvas h3,
        html.dark .page-grid-canvas h4,
        html.dark .page-grid-canvas .text-slate-900,
        body.dark-mode .page-grid-canvas h1,
        body.dark-mode .page-grid-canvas h2,
        body.dark-mode .page-grid-canvas h3,
        body.dark-mode .page-grid-canvas h4,
        body.dark-mode .page-grid-canvas .text-slate-900 {
            color: #f8fafc !important;
        }

        html.dark .page-grid-canvas p,
        html.dark .page-grid-canvas .text-slate-600,
        html.dark .page-grid-canvas .text-slate-500,
        body.dark-mode .page-grid-canvas p,
        body.dark-mode .page-grid-canvas .text-slate-600,
        body.dark-mode .page-grid-canvas .text-slate-500 {
            color: #94a3b8 !important;
        }

        @media (min-width: 768px) {
            html.dark main > section:nth-of-type(2) .bg-white,
            body.dark-mode main > section:nth-of-type(2) .bg-white {
                background-color: #141b14 !important;
                border-color: #2f3d2f !important;
            }

            html.dark main > section:nth-of-type(2) .bg-white h3,
            html.dark main > section:nth-of-type(2) .bg-white .text-white,
            body.dark-mode main > section:nth-of-type(2) .bg-white h3,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-white {
                color: #f8fafc !important;
            }

            html.dark main > section:nth-of-type(2) .bg-white .text-stone-200,
            html.dark main > section:nth-of-type(2) .bg-white .text-stone-300,
            html.dark main > section:nth-of-type(2) .bg-white .text-slate-500,
            html.dark main > section:nth-of-type(2) .bg-white .text-slate-600,
            html.dark main > section:nth-of-type(2) .bg-white .text-slate-700,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-stone-200,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-stone-300,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-slate-500,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-slate-600,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-slate-700 {
                color: #cbd5e1 !important;
            }

            html.dark main > section:nth-of-type(2) .bg-white .text-white\/60,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-white\/60 {
                color: #94a3b8 !important;
            }

            html.dark main > section:nth-of-type(2) .bg-white .service-detail-list li,
            body.dark-mode main > section:nth-of-type(2) .bg-white .service-detail-list li {
                color: #cbd5e1 !important;
            }

            html.dark main > section:nth-of-type(2) .bg-white .service-detail-copy,
            body.dark-mode main > section:nth-of-type(2) .bg-white .service-detail-copy {
                color: #cbd5e1 !important;
            }
        }

        @media (max-width: 767px) {
            html.dark main > section:nth-of-type(2) .bg-white,
            body.dark-mode main > section:nth-of-type(2) .bg-white {
                background-color: #141b14 !important;
                border-color: #2f3d2f !important;
            }

            html.dark main > section:nth-of-type(2) .bg-white h3,
            html.dark main > section:nth-of-type(2) .bg-white .text-white,
            body.dark-mode main > section:nth-of-type(2) .bg-white h3,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-white {
                color: #f8fafc !important;
            }

            html.dark main > section:nth-of-type(2) .bg-white .service-detail-list,
            html.dark main > section:nth-of-type(2) .bg-white .service-detail-list li,
            html.dark main > section:nth-of-type(2) .bg-white .service-detail-copy,
            html.dark main > section:nth-of-type(2) .bg-white .purok-details p,
            html.dark main > section:nth-of-type(2) .bg-white .purok-details li,
            html.dark main > section:nth-of-type(2) .bg-white .text-stone-200,
            html.dark main > section:nth-of-type(2) .bg-white .text-stone-300,
            html.dark main > section:nth-of-type(2) .bg-white .text-slate-500,
            html.dark main > section:nth-of-type(2) .bg-white .text-slate-600,
            html.dark main > section:nth-of-type(2) .bg-white .text-slate-700,
            body.dark-mode main > section:nth-of-type(2) .bg-white .service-detail-list,
            body.dark-mode main > section:nth-of-type(2) .bg-white .service-detail-list li,
            body.dark-mode main > section:nth-of-type(2) .bg-white .service-detail-copy,
            body.dark-mode main > section:nth-of-type(2) .bg-white .purok-details p,
            body.dark-mode main > section:nth-of-type(2) .bg-white .purok-details li,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-stone-200,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-stone-300,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-slate-500,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-slate-600,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-slate-700 {
                color: #cbd5e1 !important;
            }

            html.dark main > section:nth-of-type(2) .bg-white .service-detail-bullet,
            body.dark-mode main > section:nth-of-type(2) .bg-white .service-detail-bullet {
                color: #a3b14e !important;
            }

            html.dark main > section:nth-of-type(2) .bg-white .text-white\/60,
            body.dark-mode main > section:nth-of-type(2) .bg-white .text-white\/60 {
                color: #94a3b8 !important;
            }
        }

        html.dark main > section:nth-of-type(2) .bg-white p,
        html.dark main > section:nth-of-type(2) .bg-white li,
        body.dark-mode main > section:nth-of-type(2) .bg-white p,
        body.dark-mode main > section:nth-of-type(2) .bg-white li {
            color: #cbd5e1 !important;
        }

        .service-center-section {
            padding: 26px 28px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .service-center-section:last-child {
            border-bottom: none;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 999px;
            color: #166534;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .status-pill .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
        }

        .status-pill.closed {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .status-pill.closed .dot {
            background: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(69, 75, 27, 0.08);
            color: #454B1B;
            font-size: 16px;
            flex-shrink: 0;
        }

        .info-copy {
            flex: 1;
            min-width: 0;
        }

        .info-label {
            display: block;
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
        }

        .info-value {
            font-size: 15px;
            line-height: 1.5;
            color: #0f172a;
            font-weight: 600;
        }

        .service-cta-bar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-top: 1px solid rgba(148, 163, 184, 0.22);
            background: #fafaf9;
        }

        .service-cta-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 64px;
            padding: 16px 24px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .service-cta-btn.primary {
            background: #454B1B;
            color: #ffffff;
        }

        .service-cta-btn.secondary {
            background: #f3f4f6;
            color: #0f172a;
            border-left: 1px solid rgba(148, 163, 184, 0.2);
        }

        .service-cta-btn:hover {
            filter: brightness(0.98);
        }

        @media (max-width: 1024px) {
            .service-center-panel {
                grid-template-columns: 1fr;
            }

            .service-center-map-wrap {
                border-right: none;
                border-bottom: 1px solid rgba(148, 163, 184, 0.22);
            }
        }

        @media (max-width: 640px) {
            .service-center-shell {
                border-radius: 22px;
            }

            .service-center-map-wrap,
            .service-center-section {
                padding-left: 16px;
                padding-right: 16px;
            }

            .service-cta-bar {
                grid-template-columns: 1fr;
            }

            .service-cta-btn.secondary {
                border-left: none;
                border-top: 1px solid rgba(148, 163, 184, 0.2);
            }
        }

        .page-grid-canvas > section.hero-section,
        html.dark .page-grid-canvas > section.hero-section,
        body.dark-mode .page-grid-canvas > section.hero-section {
            background-color: #435322 !important;
            background-image:
                radial-gradient(circle at 85% 50%, rgba(74, 124, 48, 0.45) 0%, rgba(67, 83, 34, 0) 65%),
                radial-gradient(rgba(255, 255, 255, 0.18) 1px, transparent 1px) !important;
            background-size: 100% 100%, 20px 20px !important;
            background-position: center center, 0 0 !important;
        }

        .page-grid-canvas > section.hero-section .hero-badge {
            color: #e2e8f0 !important;
            border-color: rgba(255, 255, 255, 0.18) !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
        }

        .page-grid-canvas > section.hero-section .hero-title {
            color: #ffffff !important;
        }

        .page-grid-canvas > section.hero-section .hero-subtitle {
            color: rgba(255, 255, 255, 0.82) !important;
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
                <a href="appointments.php" class="nav-link text-sm font-medium <?php echo isNavActive('appointment') ? 'active' : ''; ?>">Appointment</a>
                <a href="system-goals.php" class="nav-link text-sm font-medium <?php echo isNavActive('system-goals') ? 'active' : ''; ?>">System Goals</a>
                <a href="service-areas.php" class="nav-link text-sm font-medium <?php echo isNavActive('service-areas') ? 'active' : ''; ?>">Service Areas</a>
                <a href="surveillance.php" class="nav-link text-sm font-medium <?php echo isNavActive('surveillance') ? 'active' : ''; ?>">Surveillance</a>
                <a href="compliance.php" class="nav-link text-sm font-medium <?php echo isNavActive('compliance') ? 'active' : ''; ?>">Compliance</a>
            </div>
            <div style="border-top: 1px solid #e2e8f0; margin-top: 12px; padding-top: 12px;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" style="color: #454B1B; font-weight: 600; margin-bottom: 12px; white-space: normal; word-wrap: break-word; overflow-wrap: break-word; text-decoration: none; cursor: pointer; display: block; transition: opacity 0.2s ease;" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                    </a>
                    <a href="logout.php" style="background-color: #454B1B; color: white; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Log Out</a>
                <?php else: ?>
                    <a href="login.php" style="background-color: #454B1B; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Login / Signup</a>
                <?php endif; ?>
            </div>
        </div>
    </nav><?php endif; ?>

    <main style="background-color: #ffffff; background-image: linear-gradient(to right, rgba(0, 0, 0, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(0, 0, 0, 0.05) 1px, transparent 1px); background-size: 24px 24px;" class="bg-dark-grid page-grid-canvas w-full">

        <section class="hero-section">
            <div class="hero-animated-dots" aria-hidden="true"></div>
            <div class="hero-glow" aria-hidden="true"></div>
            
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="hero-badge-dot animate-pulse" aria-hidden="true"></span>
                        Community Coverage
                </div>
                <h1 class="hero-title">
                    Service Areas &amp; Coverage Network
                    </h1>
                <p class="hero-subtitle">
                        ScanCare serves Barangay Gutad and surrounding primary care distribution networks, bringing digital health solutions to every household and health center across our jurisdiction.
                    </p>
            </div>
        </section>

        <section style="background-color: #ffffff; background-image: linear-gradient(to right, rgba(0, 0, 0, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(0, 0, 0, 0.05) 1px, transparent 1px); background-size: 24px 24px;" class="py-16 text-slate-900 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <!-- Title Section -->
                <div class="max-w-3xl mb-12 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Coverage by Purok (Neighborhood Units)
                    </h2>
                    <p class="text-slate-700 text-base leading-relaxed">
                        ScanCare segments Barangay Gutad into defined puroks, enabling targeted resource allocation, easier follow-up tracking, and localized health interventions.
                    </p>
                </div>

                <!-- Split Layout: Containers Left, Puroks Right -->
                <div class="grid lg:grid-cols-2 gap-8 items-start">
                    <!-- LEFT SIDE: Health Center Containers -->
                    <div class="space-y-6">
                        <!-- Clinic Operations -->
                        <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-xl p-6 backdrop-blur-sm hover:border-[#454B1B]/30 transition-colors">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-lg">
                                    🏥
                                </div>
                                <h3 class="text-lg font-heading font-bold">Clinic Operations</h3>
                            </div>
                            <ul class="service-detail-list space-y-2 text-sm">
                                <li class="flex items-start gap-2">
                                    <span class="service-detail-bullet">•</span>
                                    <span>Patient check-ins and registrations via QR-code tablets</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="service-detail-bullet">•</span>
                                    <span>Consultation room diagnostic entry and medical record updates</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="service-detail-bullet">•</span>
                                    <span>Vital signs logging (blood pressure, temperature, weight)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="service-detail-bullet">•</span>
                                    <span>Medical appointment scheduling and follow-up tracking</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Pharmacy Management -->
                        <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-xl p-6 backdrop-blur-sm hover:border-[#454B1B]/30 transition-colors">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-lg">
                                    💊
                                </div>
                                <h3 class="text-lg font-heading font-bold">Pharmacy Management</h3>
                            </div>
                            <ul class="service-detail-list space-y-2 text-sm">
                                <li class="flex items-start gap-2">
                                    <span class="service-detail-bullet">•</span>
                                    <span>Real-time medicine inventory tracking and batching</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="service-detail-bullet">•</span>
                                    <span>Prescription dispensary logging with expiration alerts</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="service-detail-bullet">•</span>
                                    <span>Automated low-stock notifications to supervisors</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="service-detail-bullet">•</span>
                                    <span>Consumption analytics for procurement planning</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Interactive Health Center Map -->
                        <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-xl p-6 backdrop-blur-sm hover:border-[#454B1B]/30 transition-colors">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-lg">
                                    🗺️
                                </div>
                                <h3 class="text-lg font-heading font-bold">Interactive Health Center Map</h3>
                            </div>
                            <p class="service-detail-copy text-sm">
                                Visual representation of clinic layout, departments, and operational zones would be displayed here with interactive hotspots.
                            </p>
                        </div>
                    </div>

                    <!-- RIGHT SIDE: Purok Dropdowns -->
                    <div class="space-y-4">
                        <!-- First Row: 4 Puroks (stacked vertically on right) -->
                        <!-- Purok 1 -->
                        <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-xl backdrop-blur-sm overflow-hidden transition-all duration-300 flex flex-col">
                            <button onclick="togglePurokDetails(this)" class="w-full px-5 py-4 flex items-center gap-3 bg-slate-100 dark:bg-[#131c17] hover:bg-slate-200 dark:hover:bg-[#19251f] transition-colors flex-1 text-left">
                                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-base flex-shrink-0">
                                    🏡
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-heading font-bold text-white">Purok 1</h3>
                                    <p class="text-stone-300 text-xs mt-1 line-clamp-2">Central zone • ~320 households</p>
                                </div>
                                <i class="fas fa-chevron-down text-white transition-transform duration-300 text-sm mt-1 flex-shrink-0"></i>
                            </button>
                            <div class="purok-details hidden" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div class="px-5 py-3 border-t border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0f1612] text-sm">
                                    <p class="text-stone-200 text-xs mb-2 leading-relaxed">
                                        Central residential zone near barangay hall.
                                    </p>
                                    <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-100">
                                        <div class="font-bold mb-1">Facilities:</div>
                                        <ul class="space-y-0.5 text-stone-200 text-xs">
                                            <li>• Barangay Hall Annex</li>
                                            <li>• Health Worker Outpost</li>
                                            <li>• Day Care Center</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purok 2 -->
                        <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-xl backdrop-blur-sm overflow-hidden transition-all duration-300 flex flex-col">
                            <button onclick="togglePurokDetails(this)" class="w-full px-5 py-4 flex items-center gap-3 bg-slate-100 dark:bg-[#131c17] hover:bg-slate-200 dark:hover:bg-[#19251f] transition-colors flex-1 text-left">
                                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-base flex-shrink-0">
                                    🏠
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-heading font-bold text-white">Purok 2</h3>
                                    <p class="text-stone-300 text-xs mt-1 line-clamp-2">East cluster • ~240 families</p>
                                </div>
                                <i class="fas fa-chevron-down text-white transition-transform duration-300 text-sm mt-1 flex-shrink-0"></i>
                            </button>
                            <div class="purok-details hidden" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div class="px-5 py-3 border-t border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0f1612] text-sm">
                                    <p class="text-stone-200 text-xs mb-2 leading-relaxed">
                                        Quiet residential cluster east of health center.
                                    </p>
                                    <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-100">
                                        <div class="font-bold mb-1">Facilities:</div>
                                        <ul class="space-y-0.5 text-stone-200 text-xs">
                                            <li>• Local Health Station</li>
                                            <li>• Community Chapel</li>
                                            <li>• Child Development Center</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purok 3 -->
                        <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-xl backdrop-blur-sm overflow-hidden transition-all duration-300 flex flex-col">
                            <button onclick="togglePurokDetails(this)" class="w-full px-5 py-4 flex items-center gap-3 bg-slate-100 dark:bg-[#131c17] hover:bg-slate-200 dark:hover:bg-[#19251f] transition-colors flex-1 text-left">
                                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-base flex-shrink-0">
                                    🌾
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-heading font-bold text-white">Purok 3</h3>
                                    <p class="text-stone-300 text-xs mt-1 line-clamp-2">Agricultural • ~180 households</p>
                                </div>
                                <i class="fas fa-chevron-down text-white transition-transform duration-300 text-sm mt-1 flex-shrink-0"></i>
                            </button>
                            <div class="purok-details hidden" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div class="px-5 py-3 border-t border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0f1612] text-sm">
                                    <p class="text-stone-200 text-xs mb-2 leading-relaxed">
                                        Agricultural sector west of barangay.
                                    </p>
                                    <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-100">
                                        <div class="font-bold mb-1">Facilities:</div>
                                        <ul class="space-y-0.5 text-stone-200 text-xs">
                                            <li>• Farmersʼ Support Hub</li>
                                            <li>• Rural Health Outreach</li>
                                            <li>• Evacuation Center</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purok 4 -->
                        <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-xl backdrop-blur-sm overflow-hidden transition-all duration-300 flex flex-col">
                            <button onclick="togglePurokDetails(this)" class="w-full px-5 py-4 flex items-center gap-3 bg-slate-100 dark:bg-[#131c17] hover:bg-slate-200 dark:hover:bg-[#19251f] transition-colors flex-1 text-left">
                                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-base flex-shrink-0">
                                    🌿
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-heading font-bold text-white">Purok 4</h3>
                                    <p class="text-stone-300 text-xs mt-1 line-clamp-2">Northside area • ~210 households</p>
                                </div>
                                <i class="fas fa-chevron-down text-white transition-transform duration-300 text-sm mt-1 flex-shrink-0"></i>
                            </button>
                            <div class="purok-details hidden" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div class="px-5 py-3 border-t border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0f1612] text-sm">
                                    <p class="text-stone-200 text-xs mb-2 leading-relaxed">
                                        Northside with mixed small farms.
                                    </p>
                                    <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-100">
                                        <div class="font-bold mb-1">Facilities:</div>
                                        <ul class="space-y-0.5 text-stone-200 text-xs">
                                            <li>• Purok Hall</li>
                                            <li>• Outreach Clinic</li>
                                            <li>• Community Learning Center</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purok 5 -->
                        <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-xl backdrop-blur-sm overflow-hidden transition-all duration-300 flex flex-col">
                            <button onclick="togglePurokDetails(this)" class="w-full px-5 py-4 flex items-center gap-3 bg-slate-100 dark:bg-[#131c17] hover:bg-slate-200 dark:hover:bg-[#19251f] transition-colors flex-1 text-left">
                                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-base flex-shrink-0">
                                    🩺
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-heading font-bold text-white">Purok 5</h3>
                                    <p class="text-stone-300 text-xs mt-1 line-clamp-2">Southside • ~200 households</p>
                                </div>
                                <i class="fas fa-chevron-down text-white transition-transform duration-300 text-sm mt-1 flex-shrink-0"></i>
                            </button>
                            <div class="purok-details hidden" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div class="px-5 py-3 border-t border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0f1612] text-sm">
                                    <p class="text-stone-200 text-xs mb-2 leading-relaxed">
                                        Southside anchored by barangay health station.
                                    </p>
                                    <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-100">
                                        <div class="font-bold mb-1">Facilities:</div>
                                        <ul class="space-y-0.5 text-stone-200 text-xs">
                                            <li>• Rural Health Clinic</li>
                                            <li>• Nutritional Support Center</li>
                                            <li>• Purok Assembly Hall</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purok 6 -->
                        <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-xl backdrop-blur-sm overflow-hidden transition-all duration-300 flex flex-col">
                            <button onclick="togglePurokDetails(this)" class="w-full px-5 py-4 flex items-center gap-3 bg-slate-100 dark:bg-[#131c17] hover:bg-slate-200 dark:hover:bg-[#19251f] transition-colors flex-1 text-left">
                                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-base flex-shrink-0">
                                    ⛪
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-heading font-bold text-white">Purok 6</h3>
                                    <p class="text-stone-300 text-xs mt-1 line-clamp-2">Residential cluster • ~170 households</p>
                                </div>
                                <i class="fas fa-chevron-down text-white transition-transform duration-300 text-sm mt-1 flex-shrink-0"></i>
                            </button>
                            <div class="purok-details hidden" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div class="px-5 py-3 border-t border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0f1612] text-sm">
                                    <p class="text-stone-200 text-xs mb-2 leading-relaxed">
                                        Established cluster with roadside chapels.
                                    </p>
                                    <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-100">
                                        <div class="font-bold mb-1">Facilities:</div>
                                        <ul class="space-y-0.5 text-stone-200 text-xs">
                                            <li>• Chapel Gathering Space</li>
                                            <li>• Purok Health Desk</li>
                                            <li>• Senior Citizen Lounge</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purok 7 -->
                        <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-xl backdrop-blur-sm overflow-hidden transition-all duration-300 flex flex-col">
                            <button onclick="togglePurokDetails(this)" class="w-full px-5 py-4 flex items-center gap-3 bg-slate-100 dark:bg-[#131c17] hover:bg-slate-200 dark:hover:bg-[#19251f] transition-colors flex-1 text-left">
                                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-base flex-shrink-0">
                                    🚑
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-heading font-bold text-white">Purok 7</h3>
                                    <p class="text-stone-300 text-xs mt-1 line-clamp-2">Mixed area • ~180 households</p>
                                </div>
                                <i class="fas fa-chevron-down text-white transition-transform duration-300 text-sm mt-1 flex-shrink-0"></i>
                            </button>
                            <div class="purok-details hidden" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div class="px-5 py-3 border-t border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0f1612] text-sm">
                                    <p class="text-stone-200 text-xs mb-2 leading-relaxed">
                                        Mixed residential & agricultural northeast.
                                    </p>
                                    <div class="bg-white dark:bg-[#131c17] border border-slate-200 dark:border-white/10 rounded-lg p-2 text-xs text-slate-700 dark:text-slate-100">
                                        <div class="font-bold mb-1">Facilities:</div>
                                        <ul class="space-y-0.5 text-stone-200 text-xs">
                                            <li>• Barangay Health Worker Outpost</li>
                                            <li>• Day Care Pavilion</li>
                                            <li>• Evacuation Assembly Area</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section style="background-color: #ffffff; background-image: linear-gradient(to right, rgba(0, 0, 0, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(0, 0, 0, 0.05) 1px, transparent 1px); background-size: 24px 24px;" class="py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Service Categories & Programs
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div style="background-color: #ffffff;" class="service-program-card p-6 rounded-2xl border border-slate-200 flex flex-col justify-between shadow-sm">
                        <div>
                            <div style="color: #454B1B;" class="text-xs font-bold uppercase tracking-wider mb-2">Service 01</div>
                            <h4 class="text-lg font-heading font-bold text-slate-900 mb-2">Primary Healthcare</h4>
                            <p class="text-slate-600 text-xs leading-relaxed">Preventive screenings, immunization programs, maternal and child health services, and chronic disease management across all puroks.</p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/60 pt-4 flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <i style="color: #454B1B;" class="fa-solid fa-hospital"></i> Community Coverage
                        </div>
                    </div>

                    <div style="background-color: #ffffff;" class="service-program-card p-6 rounded-2xl border border-slate-200 flex flex-col justify-between shadow-sm">
                        <div>
                            <div style="color: #454B1B;" class="text-xs font-bold uppercase tracking-wider mb-2">Service 02</div>
                            <h4 class="text-lg font-heading font-bold text-slate-900 mb-2">Pharmacy Services</h4>
                            <p class="text-slate-600 text-xs leading-relaxed">Medicine dispensary, prescription fulfillment, inventory auditing, and procurement coordination with automated stock alerts.</p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/60 pt-4 flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <i style="color: #454B1B;" class="fa-solid fa-pills"></i> Inventory Management
                        </div>
                    </div>

                    <div style="background-color: #ffffff;" class="service-program-card p-6 rounded-2xl border border-slate-200 flex flex-col justify-between shadow-sm">
                        <div>
                            <div style="color: #454B1B;" class="text-xs font-bold uppercase tracking-wider mb-2">Service 03</div>
                            <h4 class="text-lg font-heading font-bold text-slate-900 mb-2">Disease Surveillance</h4>
                            <p class="text-slate-600 text-xs leading-relaxed">Endemic disease monitoring, outbreak detection, epidemiological reporting, and SMS alert broadcasting to community networks.</p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/60 pt-4 flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <i style="color: #454B1B;" class="fa-solid fa-chart-line"></i> Real-Time Alerts
                        </div>
                    </div>

                    <div style="background-color: #ffffff;" class="service-program-card p-6 rounded-2xl border border-slate-200 flex flex-col justify-between shadow-sm">
                        <div>
                            <div style="color: #454B1B;" class="text-xs font-bold uppercase tracking-wider mb-2">Service 04</div>
                            <h4 class="text-lg font-heading font-bold text-slate-900 mb-2">Resource Management</h4>
                            <p class="text-slate-600 text-xs leading-relaxed">Health aid distribution optimization, medical mission scheduling, supply delivery coordination, and vulnerable population tracking.</p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/60 pt-4 flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <i style="color: #454B1B;" class="fa-solid fa-boxes-stacked"></i> Logistics Support
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section style="background-color: #ffffff; background-image: linear-gradient(to right, rgba(0, 0, 0, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(0, 0, 0, 0.05) 1px, transparent 1px); background-size: 24px 24px;" class="py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-16 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Regional Health Integration
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        ScanCare operates within the larger municipal and provincial health system, with seamless data interoperability and referral pathways.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-white p-8 rounded-xl border border-slate-100 shadow-sm">
                        <div style="color: #454B1B;" class="text-4xl font-heading font-bold mb-3">🏛️</div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Municipal Coordination</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Integration with municipal health office for policy alignment, quarterly reporting, and regulatory compliance oversight.</p>
                    </div>

                    <div class="bg-white p-8 rounded-xl border border-slate-100 shadow-sm">
                        <div style="color: #454B1B;" class="text-4xl font-heading font-bold mb-3">🔗</div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Hospital Referral Network</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Automated referral protocols to district hospitals for specialized care with secure patient data sharing and case tracking.</p>
                    </div>

                    <div class="bg-white p-8 rounded-xl border border-slate-100 shadow-sm">
                        <div style="color: #454B1B;" class="text-4xl font-heading font-bold mb-3">📋</div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">National Reporting</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">Compliance with Department of Health surveillance protocols and mandatory disease reporting requirements.</p>
                    </div>
                </div>
            </div>
        </section>

        <section style="background-color: #ffffff; background-image: linear-gradient(to right, rgba(0, 0, 0, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(0, 0, 0, 0.05) 1px, transparent 1px); background-size: 24px 24px;" class="py-20 border-b border-slate-200 relative z-20">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="max-w-3xl mb-12 space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 tracking-tight">
                        Service Center Location
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        Visit Barangay Gutad Health Center to access our full suite of healthcare services and ScanCare digital health services.
                    </p>
                </div>

                <div class="service-center-shell">
                    <div class="service-center-panel">
                        <div class="service-center-map-wrap">
                            <div class="service-center-map">
                                <div class="service-map-pin" aria-hidden="true"></div>
                                <iframe 
                                    src="https://maps.google.com/maps?q=14.9341389,120.4925556&output=embed"
                                    width="100%" 
                                    height="100%" 
                                    style="border:0;display:block;" 
                                    allowfullscreen="" 
                                    loading="lazy" 
                                    referrerpolicy="no-referrer-when-downgrade"
                                    class="absolute inset-0">
                                </iframe>
                            </div>
                        </div>

                        <div class="service-center-info">
                            <div class="service-center-section">
                                <div class="flex items-center justify-between gap-3 mb-5">
                                    <div class="flex items-center gap-3">
                                        <div class="info-icon" style="background: rgba(69, 75, 27, 0.12);">
                                            <i class="fa-solid fa-map-pin"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-heading font-bold text-slate-900">Barangay Gutad Health Center</h3>
                                        </div>
                                    </div>
                                    <span class="status-pill <?php echo $serviceStatusClass; ?>"><span class="dot"></span><?php echo $serviceStatusLabel; ?></span>
                                </div>

                                <div class="info-row">
                                    <div class="info-icon">
                                        <i class="fa-solid fa-phone"></i>
                                    </div>
                                    <div class="info-copy">
                                        <span class="info-label">Contact</span>
                                        <div class="info-value">(02) 1234-5678</div>
                                        <div class="text-sm text-slate-500 mt-1">Emergency: 0917-123-4567</div>
                                    </div>
                                </div>

                                <div class="info-row">
                                    <div class="info-icon">
                                        <i class="fa-solid fa-calendar-days"></i>
                                    </div>
                                    <div class="info-copy">
                                        <span class="info-label">Operating Hours</span>
                                        <div class="info-value">Mon–Fri: 8:00 AM – 5:00 PM</div>
                                        <div class="text-sm text-slate-500 mt-1">Sat: 8:00 AM – 12:00 PM</div>
                                    </div>
                                </div>

                            </div>

                            <div class="service-cta-bar">
                                <a href="https://www.google.com/maps/search/?api=1&query=14.9341389,120.4925556" target="_blank" rel="noreferrer noopener" class="service-cta-btn primary">
                                    <i class="fa-solid fa-location-dot"></i>
                                    Open in Google Maps
                                </a>
                                <a href="tel:09171234567" class="service-cta-btn secondary">
                                    <i class="fa-solid fa-phone-volume"></i>
                                    Call Emergency Line
                                </a>
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
        function togglePurokDetails(button) {
            const accordionItem = button.parentElement;
            const detailsDiv = accordionItem.querySelector('.purok-details');
            const chevron = button.querySelector('i.fa-chevron-down');
            const isOpen = !detailsDiv.classList.contains('hidden');

            document.querySelectorAll('.purok-details').forEach((panel) => {
                const panelItem = panel.parentElement;
                const panelButton = panelItem.querySelector('button');
                const panelChevron = panelButton.querySelector('i.fa-chevron-down');

                if (panel === detailsDiv) {
                    return;
                }

                if (!panel.classList.contains('hidden')) {
                    panel.style.maxHeight = panel.scrollHeight + 'px';
                    requestAnimationFrame(() => {
                        panel.style.maxHeight = '0px';
                    });
                    setTimeout(() => {
                        panel.classList.add('hidden');
                    }, 300);
                } else {
                    panel.style.maxHeight = '0px';
                }

                panelChevron.style.transform = 'rotate(0deg)';
            });

            if (isOpen) {
                detailsDiv.style.maxHeight = detailsDiv.scrollHeight + 'px';
                requestAnimationFrame(() => {
                    detailsDiv.style.maxHeight = '0px';
                });
                setTimeout(() => detailsDiv.classList.add('hidden'), 300);
                chevron.style.transform = 'rotate(0deg)';
            } else {
                detailsDiv.classList.remove('hidden');
                detailsDiv.style.maxHeight = '0px';
                requestAnimationFrame(() => {
                    const scrollHeight = detailsDiv.scrollHeight;
                    detailsDiv.style.maxHeight = scrollHeight + 'px';
                });
                chevron.style.transform = 'rotate(180deg)';
            }
        }

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


