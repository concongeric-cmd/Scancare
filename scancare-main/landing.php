<?php

session_start();

date_default_timezone_set('Asia/Manila');

$currentTime = new DateTime();
$currentHour = (int)$currentTime->format('H');
$currentMinute = (int)$currentTime->format('i');
$dayOfWeek = $currentTime->format('w');

$isWeekday = ($dayOfWeek >= 1 && $dayOfWeek <= 5);
$isOpeningHour = ($currentHour >= 8 && $currentHour < 17);

$rhuIsOpen = $isWeekday && $isOpeningHour;
$rhuStatusText = $rhuIsOpen 
    ? '● Rural Health Unit is Open | Closes at 5:00 PM' 
    : '○ RHU is Closed | Opens at 8:00 AM';
$rhuStatusClass = $rhuIsOpen ? 'open' : 'closed';
$rhuDotClass = $rhuIsOpen ? 'pulse' : '';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#454B1B">
    <link rel="icon" type="image/png" href="images/SClogo.png?v=2">
    <link rel="apple-touch-icon" href="images/SClogo.png?v=2">
    <link rel="manifest" href="manifest.json">
    <script src="pwa-offline.js?v=3" defer></script>
    <title>ScanCare — Barangay Gutad Archive</title>
    
    <!-- Apply the saved theme before the page paints to avoid a light-mode flash. -->
    <script src="theme-state.js"></script>

    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="stylesheet" href="global-theme.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Google Fonts: Plus Jakarta Sans + Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* ═════════════════════════════════════════════════════════════════════
           GLOBAL TYPOGRAPHY & UTILITIES
           ═════════════════════════════════════════════════════════════════════ */
        
        .font-body { 
            font-family: 'Inter', sans-serif; 
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .brand-logo span { line-height: 1; }

        .unified-transition { transition: all 0.2s ease-in-out; }

        .unified-transition:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }

        /* Custom Graph/Grid Overlay Background */
        .bg-grid-pattern {
            background-color: #f5f8f5 !important;
            background-size: 24px 24px !important;
            background-image: 
                linear-gradient(to right, rgba(0, 0, 0, 0.04) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(0, 0, 0, 0.04) 1px, transparent 1px) !important;
            background-repeat: repeat;
        }

        .sc-nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 80rem;
            margin: 0 auto;
            width: 100%;
        }

        .nav-links-desktop { display: none; position: relative; }

        .nav-hamburger-btn {
            display: block;
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
            transition: transform 0.35s ease, width 0.35s ease, opacity 0.35s ease;
            z-index: 0;
            opacity: 0;
            pointer-events: none;
        }

        @media (min-width: 768px) {
            .nav-links-desktop {
                display: flex;
                align-items: center;
                gap: 24px;
                font-weight: 500;
                font-size: 14px;
                color: #475569;
            }

            .nav-hamburger-btn { display: none; }
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

        .profile-dropdown {
            will-change: opacity, visibility;
        }

        .group {
            position: relative;
            padding-bottom: 8px; 
        }

        .group::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            height: 8px; 
            pointer-events: auto;
            z-index: 40;
        }

        .group:hover .profile-dropdown {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        /* ═════════════════════════════════════════════════════════════════════
           SPLASH SCREEN STYLES
           ═════════════════════════════════════════════════════════════════════ */
        
        #splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: #000000;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 1;
            visibility: visible;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        #splash-screen.fade-out {
            opacity: 0;
            visibility: hidden;
        }

        #splash-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background-color: #000000;
        }

        .enter-text {
            position: absolute;
            bottom: 60px;
            left: 50%;
            transform: translateX(-50%);
            color: #454B1B;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            z-index: 10000;
            animation: pulse-enter 2s ease-in-out infinite;
            pointer-events: none;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
            letter-spacing: 0.5px;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .enter-text.show {
            opacity: 1;
        }

        @keyframes pulse-enter {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        /* ═════════════════════════════════════════════════════════════════════
           HERO SECTION & SLIDESHOW STYLES
           ═════════════════════════════════════════════════════════════════════ */
        
        .slideshow-container {
            position: absolute;
            inset: 0;
            z-index: 0;
        }

        .slideshow-container img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 1;
            transition: opacity 0.6s ease-in-out;
        }

        .slideshow-container img.fade-out {
            opacity: 0;
        }

        .slideshow-container::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to right,
                rgba(69, 75, 27, 0.1) 0%,
                rgba(69, 75, 27, 0.3) 50%,
                rgba(248, 250, 246, 1) 100%
            );
            z-index: 1;
        }

        @keyframes slideUpFade {
            0% {
                opacity: 0;
                transform: translateY(24px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-up {
            opacity: 0;
            animation: slideUpFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-fill-mode: forwards;
        }

        .delay-100 { animation-delay: 100ms; }
        .delay-150 { animation-delay: 150ms; }
        .delay-250 { animation-delay: 250ms; }
        .delay-350 { animation-delay: 350ms; }
        .delay-420 { animation-delay: 420ms; }
        .delay-490 { animation-delay: 490ms; }
        .delay-560 { animation-delay: 560ms; }
        .delay-650 { animation-delay: 650ms; }

        .chevron-btn {
            width: 3rem;
            height: 3rem;
            pointer-events: auto;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 9999px;
            background: rgba(15, 23, 42, 0.32);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2);
            transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, color 0.2s ease;
        }
        .chevron-btn:hover {
            border-color: rgba(255, 255, 255, 0.9);
            background: #454B1B;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(69, 75, 27, 0.2), 0 8px 18px rgba(15, 23, 42, 0.28);
        }
        .chevron-btn:active {
            background: #363A15;
            box-shadow: 0 0 0 3px rgba(69, 75, 27, 0.25), 0 4px 10px rgba(15, 23, 42, 0.24);
        }
        .chevron-btn:focus-visible {
            outline: 2px solid #c4d46b;
            outline-offset: 3px;
        }

        .arrow-drop-shadow {
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.35));
        }

        @media (max-width: 767px) {
            .slideshow-container::after {
                background: linear-gradient(
                    to bottom,
                    rgba(248, 250, 246, 0.4) 0%,
                    rgba(248, 250, 246, 1) 85%
                );
            }
        }

        .slideshow-dots {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 20;
            flex-wrap: wrap;
            justify-content: center;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .dot.active {
            background-color: rgba(69, 75, 27, 0.9);
            border-color: white;
            transform: scale(1.2);
        }

        .dot:hover {
            background-color: rgba(255, 255, 255, 0.7);
            transform: scale(1.1);
        }

        /* ═════════════════════════════════════════════════════════════════════
           RHU STATUS BADGE STYLES
           ═════════════════════════════════════════════════════════════════════ */
        
        .rhu-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
        }

        .rhu-status-badge.open {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .rhu-status-badge.closed {
            background-color: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .rhu-status-dot {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .rhu-status-dot.pulse {
            animation: statusPulse 2s ease-in-out infinite;
        }

        @keyframes statusPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.3); }
        }

        /* ═════════════════════════════════════════════════════════════════════
           PROCESS FLOW SECTION STYLES
           ═════════════════════════════════════════════════════════════════════ */
        
        .process-flow-section {
            background: linear-gradient(135deg, rgba(69, 75, 27, 0.02) 0%, rgba(69, 75, 27, 0.01) 100%);
        }

        .process-step-card {
            text-align: center;
            padding: 32px 24px;
            border-radius: 16px;
            background: white;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
        }

        html.dark .process-step-card,
        body.dark-mode .process-step-card {
            background-color: var(--landing-bg-card) !important;
            border-color: var(--landing-border) !important;
            color: var(--landing-text-main) !important;
        }

        .process-step-card:hover {
            border-color: #454B1B;
            box-shadow: 0 12px 24px rgba(69, 75, 27, 0.1);
            transform: translateY(-4px);
        }

        .process-step-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            background: linear-gradient(135deg, #454B1B 0%, #6b7333 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }

        .process-step-number {
            position: absolute;
            top: -12px;
            right: -12px;
            width: 36px;
            height: 36px;
            background-color: #454B1B;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(69, 75, 27, 0.2);
        }

        .process-flow-connector {
            display: none;
            position: absolute;
            top: 50%;
            left: 100%;
            width: 32px;
            height: 2px;
            background: linear-gradient(90deg, #454B1B 0%, rgba(69, 75, 27, 0) 100%);
            transform: translateY(-50%);
        }

        @media (min-width: 768px) {
            .process-flow-connector {
                display: block;
            }

            .process-step-card:last-child .process-flow-connector {
                display: none;
            }
        }

        /* ═════════════════════════════════════════════════════════════════════
           FAQ SECTION STYLES
           ═════════════════════════════════════════════════════════════════════ */
        
        .faq-container {
            max-width: 700px;
            margin: 0 auto;
        }

        .faq-item {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }

        .faq-item:hover {
            border-color: #454B1B;
            box-shadow: 0 4px 12px rgba(69, 75, 27, 0.1);
        }

        .faq-question {
            width: 100%;
            padding: 16px 20px;
            border: 0;
            background: #ffffff;
            color: #1e293b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            user-select: none;
            transition: background-color 0.2s ease, color 0.2s ease;
            text-align: left;
        }

        .faq-item.active .faq-question {
            background-color: #454B1B;
            color: #ffffff;
        }

        .faq-question:hover {
            background-color: #f8fafc;
        }

        .faq-item.active .faq-question:hover {
            background-color: #3a3f14;
        }

        .faq-question-text {
            color: #1e293b;
            font-weight: 600;
            font-size: 15px;
            text-align: left;
            flex: 1;
        }

        .faq-item.active .faq-question-text {
            color: #ffffff;
        }

        .faq-toggle-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
            font-size: 12px;
            flex-shrink: 0;
            color: #64748b;
        }

        .faq-item.active .faq-toggle-icon {
            transform: rotate(180deg);
            color: #ffffff;
        }

        .faq-answer {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height 0.35s ease-in-out, opacity 0.25s ease-in-out, padding 0.3s ease;
            background-color: #ffffff;
        }

        .faq-item.active .faq-answer {
            max-height: 300px;
            opacity: 1;
            visibility: visible;
        }

        .faq-answer-text {
            display: block;
            padding: 16px 20px 20px 20px;
            color: #334155 !important;
            line-height: 1.6;
            font-size: 14px;
        }

        html.dark .faq-item,
        body.dark-mode .faq-item {
            background: var(--landing-bg-card) !important;
            border-color: var(--landing-border) !important;
            color: var(--landing-text-main) !important;
        }

        html.dark .faq-question,
        body.dark-mode .faq-question {
            background: var(--landing-bg-card) !important;
            color: var(--landing-text-main) !important;
        }

        html.dark .faq-question-text,
        body.dark-mode .faq-question-text {
            color: #f8fafc;
        }

        html.dark .faq-toggle-icon,
        body.dark-mode .faq-toggle-icon {
            color: #94a3b8;
        }

        html.dark .faq-question:hover,
        body.dark-mode .faq-question:hover {
            background: var(--bg-surface-alt) !important;
        }

        html.dark .faq-item.active .faq-question,
        body.dark-mode .faq-item.active .faq-question {
            background: var(--accent-active) !important;
            color: var(--text-main-dark) !important;
        }

        html.dark .faq-answer,
        body.dark-mode .faq-answer {
            background-color: var(--bg-surface-alt) !important;
        }

        html.dark .faq-answer-text,
        body.dark-mode .faq-answer-text {
            color: #cbd5e1 !important;
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

        :root {
            --bg-canvas-dark: #0d1310;
            --bg-card-dark: #131c17;
            --bg-surface-alt: #111813;
            --bg-nav-dark: rgba(30, 41, 59, 0.82);
            --accent-olive: #454B1B;
            --accent-active: #4D582A;
            --accent-lime: #a3b14e;
            --text-main-dark: #f8fafc;
            --text-muted-dark: #94a3b8;
            --border-dark: rgba(255, 255, 255, 0.1);
            --landing-bg-main: #f8fafc;
            --landing-bg-card: #ffffff;
            --landing-text-main: #0f172a;
            --landing-text-muted: #64748b;
            --landing-border: #e2e8f0;
            --landing-accent: #454b1b;
        }

        html,
        body {
            background-color: #f8fafc !important;
            color: #0f172a !important;
        }

        html.dark,
        body.dark-mode {
            --bg-canvas-dark: #0b0f0a !important;
            --bg-card-dark: #1e293b !important;
            --bg-surface-alt: #334155 !important;
            --text-main-dark: #f8fafc !important;
            --text-muted-dark: #94a3b8 !important;
            --border-dark: rgba(255, 255, 255, 0.1) !important;
        }

        html.dark body,
        body.dark-mode {
            --landing-bg-main: var(--bg-canvas-dark);
            --landing-bg-card: var(--bg-card-dark);
            --landing-text-main: #f8fafc;
            --landing-text-muted: var(--text-muted-dark);
            --landing-border: var(--border-dark);
            --landing-accent: var(--accent-olive);
            background-color: var(--landing-bg-main) !important;
            color: var(--landing-text-main) !important;
        }

        html.dark,
        html.dark body,
        body.dark-mode {
            background-color: #0f172a !important;
            color: #f8fafc !important;
        }

        html.dark .card,
        html.dark .bg-card,
        body.dark-mode .card,
        body.dark-mode .bg-card {
            background-color: #1e293b !important;
        }

        html.dark .modal-content,
        body.dark-mode .modal-content,
        html.dark #settingsModal > div,
        body.dark-mode #settingsModal > div {
            background-color: var(--bg-card-dark) !important;
            border-color: var(--border-dark) !important;
            color: var(--text-main-dark) !important;
        }

        html.dark .process-step-card h3,
        body.dark-mode .process-step-card h3,
        html.dark .faq-question-text,
        body.dark-mode .faq-question-text,
        html.dark #settingsModal h2,
        html.dark #settingsModal h3,
        body.dark-mode #settingsModal h2,
        body.dark-mode #settingsModal h3 {
            color: var(--text-main-dark) !important;
        }

        html.dark .process-step-card p,
        body.dark-mode .process-step-card p,
        html.dark .faq-answer-text,
        body.dark-mode .faq-answer-text {
            color: #cbd5e1 !important;
        }

        html.dark .process-step-number,
        body.dark-mode .process-step-number,
        html.dark .process-step-icon,
        body.dark-mode .process-step-icon {
            background-color: var(--accent-olive) !important;
        }

        html.dark .accent-text,
        html.dark .stat-number,
        html.dark .check-icon,
        body.dark-mode .accent-text,
        body.dark-mode .stat-number,
        body.dark-mode .check-icon {
            color: var(--accent-lime) !important;
        }

        html.dark section .text-slate-900,
        body.dark-mode section .text-slate-900 {
            color: var(--text-main-dark) !important;
        }

        html.dark section .text-slate-800,
        body.dark-mode section .text-slate-800 {
            color: var(--text-main-dark) !important;
        }

        html.dark section .text-slate-600,
        body.dark-mode section .text-slate-600,
        html.dark section [class*="text-[#334155]"],
        body.dark-mode section [class*="text-[#334155]"] {
            color: #cbd5e1 !important;
        }

        html.dark #settingsModal i,
        body.dark-mode #settingsModal i {
            color: var(--accent-lime) !important;
        }

        html.dark .bg-grid-pattern {
            background-color: var(--landing-bg-main) !important;
            background-image:
                linear-gradient(to right, rgba(69, 75, 27, 0.2) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(69, 75, 27, 0.2) 1px, transparent 1px) !important;
        }

        html.dark .sc-pill-navbar {
            background: rgba(30, 41, 59, 0.82) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 24px rgba(2, 6, 23, 0.28);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        html.dark header {
            background: rgba(13, 19, 16, 0.92) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        html.dark .sc-pill-item,
        html.dark .sc-pill-item:hover,
        html.dark .sc-pill-item.active {
            color: #e2e8f0;
        }

        html.dark .sc-pill-item:hover span,
        html.dark .sc-pill-item.active {
            color: #ffffff;
        }

        html.dark .sc-pill-item.active .sc-icon-wrapper,
        html.dark .sc-pill-item:hover .sc-icon-wrapper {
            background-color: #4d7c0f;
            color: #ffffff;
        }

        html.dark .slideshow-container::after {
            background: linear-gradient(
                to right,
                transparent 0%,
                rgba(13, 19, 16, 0.8) 70%,
                rgba(13, 19, 16, 1) 100%
            );
        }

        html.dark #heroCarouselContainer,
        html.dark .process-flow-section,
        html.dark section[class*="bg-white"],
        html.dark footer {
            background-color: var(--landing-bg-main) !important;
            border-color: var(--landing-border) !important;
        }

        html.dark #heroCarouselContainer h1,
        html.dark #heroCarouselContainer h2,
        html.dark #heroCarouselContainer h3,
        html.dark #heroCarouselContainer h4,
        html.dark .process-flow-section h2,
        html.dark .process-flow-section h3,
        html.dark section h2,
        html.dark section h3 {
            color: #ffffff !important;
        }

        html.dark #heroSubtext,
        html.dark #heroBullets,
        html.dark #heroBullets li,
        html.dark #heroBullets li span,
        html.dark .process-flow-section p,
        html.dark section p,
        html.dark footer {
            color: #84a98c !important;
        }

        html.dark #heroBullets li svg {
            color: #a3b14e !important;
        }

        html.dark #statsGrid [class*="text-\\[#334155\\]"],
        html.dark #statsGrid .text-\[10px\] {
            color: #94a3b8 !important;
        }

        html.dark #statsGrid [class*="text-\\[#4D582A\\]"],
        html.dark #statsGrid .font-bold {
            color: #a3b14e !important;
        }

        html.dark #heroBullets li::before,
        html.dark #slideCounter,
        html.dark #slideStatistic,
        html.dark #heroBadgeText,
        html.dark .text-\[#4D582A\] {
            color: #a3b14e !important;
        }

        html.dark #heroContentBlock .text-slate-400,
        html.dark #heroContentBlock .text-xs,
        html.dark [class*="uppercase"] {
            color: #94a3b8 !important;
        }

        html.dark #heroContentBlock .bg-white\/5,
        html.dark .process-flow-section a[class*="bg-white"],
        html.dark section[class*="bg-white"] > div {
            background-color: var(--landing-bg-card) !important;
            border-color: var(--landing-border) !important;
        }

        html.dark #primaryButton {
            background-color: #454B1B !important;
            color: #ffffff !important;
            border: 1px solid #586022;
        }

        html.dark #secondaryButton {
            background-color: transparent !important;
            border-color: #475569 !important;
            color: #e2e8f0 !important;
        }

        html.dark #secondaryButton:hover {
            background-color: rgba(51, 65, 85, 0.6) !important;
        }

        html.dark .sc-profile-name,
        html.dark .sc-profile-menu-name {
            color: #f8fafc !important;
        }

        html.dark .sc-profile-subtitle,
        html.dark .sc-profile-menu-role {
            color: #94a3b8 !important;
        }

        @media (max-width: 767px) {
            html.dark .slideshow-container::after {
                background: linear-gradient(
                    to bottom,
                    rgba(13, 19, 16, 0.12) 0%,
                    rgba(13, 19, 16, 0.96) 85%,
                    #0d1310 100%
                );
            }
        }

        .landing-header-link {
            position: relative;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .landing-header-link:hover,
        .landing-header-link.active {
            color: #454b1b;
        }

        .landing-header-link.active::after {
            content: '';
            position: absolute;
            right: 0;
            bottom: -0.7rem;
            left: 0;
            height: 2px;
            border-radius: 9999px;
            background: #454b1b;
        }

        .landing-header-status {
            white-space: nowrap;
            border-radius: 9999px;
            padding: 0.35rem 0.65rem;
            font-size: 0.68rem;
            font-weight: 700;
        }

        .landing-header-status.open {
            background: #dcfce7;
            color: #166534;
        }

        .landing-header-status.closed {
            background: #f1f5f9;
            color: #64748b;
        }

        #themeToggleBtn {
            display: inline-flex;
            min-height: 2.5rem;
            min-width: 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            color: #454b1b;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        #themeToggleBtn:hover { background: rgba(69, 75, 27, 0.1); color: #303512; }

        .landing-mobile-menu { display: none; }

        .hero-status-badge::before {
            content: '';
            width: 0.4rem;
            height: 0.4rem;
            flex: 0 0 auto;
            border-radius: 9999px;
            background: #7f9f35;
            box-shadow: 0 0 0 3px rgba(127, 159, 53, 0.16);
        }

        @media (max-width: 900px) {
            .landing-header-nav { display: none !important; }
            button.landing-mobile-menu { display: inline-flex !important; }
            .landing-header-status { display: none !important; }
        }

        @media (max-width: 640px) {
            .landing-header-brand-copy { display: none; }
            .landing-header { padding-right: 1rem; padding-left: 1rem; }
            .landing-hero-content { padding-top: 3.5rem; padding-bottom: 3.5rem; }
        }

        html.dark .landing-header-link,
        body.dark-mode .landing-header-link { color: #cbd5e1; }

        html.dark .landing-header-link:hover,
        html.dark .landing-header-link.active,
        body.dark-mode .landing-header-link:hover,
        body.dark-mode .landing-header-link.active { color: #a3b14e; }

        html.dark .landing-header-link.active::after,
        body.dark-mode .landing-header-link.active::after { background: #a3b14e; }

        html.dark #themeToggleBtn,
        body.dark-mode #themeToggleBtn { color: #a3b14e; }

        html.dark #themeToggleBtn:hover,
        body.dark-mode #themeToggleBtn:hover { background: rgba(163, 177, 78, 0.14); color: #d9f99d; }

        html.dark .landing-header-status.closed,
        body.dark-mode .landing-header-status.closed { background: #1e293b; color: #cbd5e1; }

        html.dark #landingHeader,
        body.dark-mode #landingHeader { background: rgba(13, 19, 16, 0.94) !important; border-color: rgba(255, 255, 255, 0.1); }

        html.dark #heroCarouselContainer,
        body.dark-mode #heroCarouselContainer { background: #0d1310 !important; }

        .landing-mobile-menu-panel {
            position: absolute;
            top: 100%;
            right: 1rem;
            left: 1rem;
            z-index: 70;
            display: none;
            flex-direction: column;
            gap: 0.25rem;
            padding: 0.75rem;
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.16);
        }

        .landing-mobile-menu-panel.is-open { display: flex; }

        .landing-mobile-menu-panel a {
            padding: 0.75rem;
            border-radius: 0.5rem;
            color: #475569;
            font-size: 0.875rem;
            font-weight: 700;
            text-decoration: none;
        }

        .landing-mobile-menu-panel a:hover { background: rgba(69, 75, 27, 0.1); color: #454b1b; }

        html.dark .landing-mobile-menu-panel,
        body.dark-mode .landing-mobile-menu-panel { border-color: rgba(255, 255, 255, 0.1); background: #131c17; }

        html.dark .landing-mobile-menu-panel a,
        body.dark-mode .landing-mobile-menu-panel a { color: #cbd5e1; }

        /* Landing-page night palette: green-charcoal surfaces, never the default slate-blue. */
        html.dark,
        html.dark body,
        body.dark-mode {
            --bg-canvas-dark: #0b0f0a !important;
            --bg-card-dark: #141b14 !important;
            --bg-surface-alt: #1b251b !important;
            --landing-bg-main: #0b0f0a !important;
            --landing-bg-card: #141b14 !important;
            --landing-border: #2f3d2f !important;
            background-color: #0b0f0a !important;
        }

        html.dark main > section:not(#heroCarouselContainer):not(#goals),
        body.dark-mode main > section:not(#heroCarouselContainer):not(#goals) {
            background-color: #0f1610 !important;
            border-color: #2f3d2f !important;
        }

        html.dark .process-flow-section a.group,
        body.dark-mode .process-flow-section a.group {
            background-color: #141b14 !important;
            border-color: #2f3d2f !important;
            color: #f8fafc !important;
        }

        html.dark .process-flow-section a.group:hover,
        html.dark .process-flow-section a.group:focus,
        body.dark-mode .process-flow-section a.group:hover,
        body.dark-mode .process-flow-section a.group:focus {
            background-color: #293a2c !important;
            color: #f8fafc !important;
        }

        html.dark .process-flow-section a.group:hover > div.pointer-events-none,
        html.dark .process-flow-section a.group:focus > div.pointer-events-none,
        body.dark-mode .process-flow-section a.group:hover > div.pointer-events-none,
        body.dark-mode .process-flow-section a.group:focus > div.pointer-events-none {
            color: rgba(255, 255, 255, 0.02) !important;
        }

        html.dark .process-flow-section > .max-w-7xl,
        body.dark-mode .process-flow-section > .max-w-7xl {
            background-color: transparent !important;
            border-color: transparent !important;
            box-shadow: none !important;
        }

        html.dark .process-flow-section a.group .bg-slate-100,
        body.dark-mode .process-flow-section a.group .bg-slate-100 {
            background-color: #202c21 !important;
            color: #b8c8b8 !important;
        }

        html.dark .process-flow-section a.group:hover > div.pointer-events-none,
        html.dark .process-flow-section a.group:focus > div.pointer-events-none,
        body.dark-mode .process-flow-section a.group:hover > div.pointer-events-none,
        body.dark-mode .process-flow-section a.group:focus > div.pointer-events-none {
            background-color: transparent !important;
            color: rgba(255, 255, 255, 0.02) !important;
        }

        html.dark .process-flow-section a.group:not(:hover) .text-slate-900,
        body.dark-mode .process-flow-section a.group:not(:hover) .text-slate-900 {
            color: #f8fafc !important;
        }

        html.dark .process-flow-section a.group:not(:hover) .text-slate-600,
        html.dark .process-flow-section a.group:not(:hover) .text-slate-500,
        body.dark-mode .process-flow-section a.group:not(:hover) .text-slate-600,
        body.dark-mode .process-flow-section a.group:not(:hover) .text-slate-500 {
            color: #b8c8b8 !important;
        }

        html.dark #goals > a,
        body.dark-mode #goals > a {
            background-color: #26341e !important;
            border-color: rgba(216, 230, 198, 0.14) !important;
        }

        html.dark #goals > a:hover,
        html.dark #goals > a:focus,
        body.dark-mode #goals > a:hover,
        body.dark-mode #goals > a:focus {
            background-color: #35482a !important;
            color: #f8fafc !important;
        }

        html.dark #goals > a:hover [class*="group-hover:text"],
        body.dark-mode #goals > a:hover [class*="group-hover:text"] {
            color: #d9ecbd !important;
        }

        html.dark .faq-question:hover,
        body.dark-mode .faq-question:hover,
        html.dark .faq-answer,
        body.dark-mode .faq-answer {
            background-color: #1b251b !important;
        }

        html.dark .sc-pill-navbar,
        body.dark-mode .sc-pill-navbar,
        html.dark .landing-header-status.closed,
        body.dark-mode .landing-header-status.closed {
            background-color: #141b14 !important;
        }

        .landing-hero-widget { min-height: 25rem; }

        @media (max-width: 767px) {
            .landing-hero-widget { min-height: 20rem; }
        }
    </style>
</head>
<body class="bg-[#F8FAF6] bg-grid-pattern text-slate-800 font-body antialiased min-h-screen selection:bg-[#454B1B] selection:text-white">

    <div id="splash-screen">
        <video id="splash-video" autoplay muted playsinline>
            <source src="images/logo-reveal.mp4" type="video/mp4">
        </video>
        <p class="enter-text">Tap/Click to Continue</p>
    </div>

    <?php include 'navbar.php'; ?>

    <main class="bg-dark-grid w-full">
        <section id="heroCarouselContainer" class="relative flex min-h-[80vh] w-full items-center overflow-hidden border-b border-[#454B1B]/10 bg-[#F8FAF6] dark:bg-[#0d1310]">
            <div class="absolute inset-0 z-0 bg-[linear-gradient(to_right,#454b1b05_1px,transparent_1px),linear-gradient(to_bottom,#454B1B05_1px,transparent_1px)] bg-[size:24px_24px]"></div>

            <!-- Desktop Image & Navigation Arrows Overlay -->
            <div class="absolute inset-y-0 left-0 z-30 hidden w-full overflow-hidden md:block md:w-1/2">
                <div class="slideshow-container relative h-full w-full">
                    <img id="heroImage" class="h-full w-full object-cover transition-opacity duration-500" src="images/bakuna_bayan.jpg" alt="RHU II Community Health Program">

                    <button id="prevSlideBtn" type="button" aria-label="Previous slide" class="chevron-btn absolute left-4 top-1/2 z-20 flex -translate-y-1/2 items-center justify-center p-2 text-white/90 focus:outline-none">
                        <svg class="h-10 w-10 arrow-drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>

                    <button id="nextSlideBtn" type="button" aria-label="Next slide" class="chevron-btn absolute right-4 top-1/2 z-20 flex -translate-y-1/2 items-center justify-center p-2 text-white/90 focus:outline-none">
                        <svg class="h-10 w-10 arrow-drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <div class="absolute bottom-6 left-1/2 z-40 flex -translate-x-1/2 gap-2" id="navigationDots"></div>
                </div>
            </div>

            <div class="relative z-20 mx-auto grid min-h-[80vh] w-full grid-cols-1 items-center px-6 lg:px-12 md:grid-cols-12">
                <!-- Mobile Background Image -->
                <div class="absolute inset-0 z-0 md:hidden">
                    <div class="slideshow-container h-full w-full">
                        <img id="heroImageMobile" class="h-full w-full object-cover transition-opacity duration-500" src="images/bakuna_bayan.jpg" alt="RHU II Community Health Program">
                    </div>
                </div>

                <div id="landingHeroText" class="relative z-40 order-2 flex w-full flex-col justify-center space-y-5 overflow-visible pointer-events-auto md:col-span-6 md:col-start-7 md:order-2 lg:pl-8">
                    <div id="staggerStep1" class="cascade-item delay-100 mb-6 flex w-fit max-w-full flex-col items-start gap-1 rounded-xl border border-[#454B1B]/20 bg-[#454B1B]/12 px-3 py-2 text-sm text-[#454B1B]">
                        <span id="heroBadgeText" class="hero-status-badge inline-flex items-center gap-2 rounded-full bg-[#454B1B] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white shadow-sm">ScanCare Community Services</span>
                        <span id="heroBadgeSubtext" class="inline-flex w-full items-center border-t border-[#454B1B]/25 pt-1 text-xs font-semibold text-[#454B1B]">System Active</span>
                    </div>
                    <div id="staggerStep2" class="cascade-item delay-150 relative mb-6"><h1 id="heroHeading" class="font-heading text-[clamp(2rem,3.2vw,2.75rem)] font-extrabold tracking-tight text-[#0f172a] leading-tight"></h1></div>
                    <div id="staggerStep3" class="cascade-item delay-250 mb-8 w-full"><p id="heroSubtext" class="text-base md:text-lg leading-relaxed text-[#334155]"></p></div>
                    <ul id="heroBullets" class="space-y-2.5 my-2"></ul>
                    <div id="staggerStep4" class="cascade-item delay-650 pt-2 flex flex-wrap items-center gap-4"><a id="primaryButton" href="appointments.php" class="inline-flex w-[150px] justify-center items-center px-4 py-2.5 rounded-lg bg-[#454B1B] hover:bg-[#586022] text-white font-semibold text-sm transition-colors shadow-lg shadow-[#454B1B]/20 active:scale-95">Book a Service</a><a id="secondaryButton" href="#footer-contact" data-scroll-target="#footer-contact" class="inline-flex w-[150px] justify-center items-center px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-200 font-semibold text-sm transition-all active:scale-95">Learn More</a></div>
                    <div id="staggerStats" class="cascade-item delay-560 grid w-full grid-cols-2 gap-4 rounded-xl bg-[#3b4216] px-5 py-4 text-white shadow-lg shadow-[#454B1B]/20 sm:w-auto"><div><div class="font-heading text-xl font-bold text-[#d5e47a]" id="slideCounter">1/6</div><div class="text-xs font-medium uppercase tracking-wider text-white/65">Current Program</div></div><div class="border-l border-white/25 pl-4"><div class="font-heading text-xl font-bold text-[#d5e47a]" id="slideStatistic">Health Access</div><div id="slideStatisticLabel" class="text-xs font-medium uppercase tracking-wider text-white/65">Real-time</div></div></div>
                </div>

                <div class="hidden" aria-hidden="true">
                    <div class="landing-hero-widget group relative flex w-full max-w-md flex-col justify-between overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-950 to-slate-900 p-5 shadow-2xl dark:from-[#131c17] dark:to-[#0d1310] dark:border-white/10">
                        <div class="absolute inset-0 bg-[#454B1B]/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 blur-xl pointer-events-none"></div>
                        <div class="flex items-center justify-between text-xs text-slate-400 font-mono">
                            <span>SCANCARE // SYS_V2</span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#4D582A] animate-pulse"></span>
                                LIVE SYNC
                            </span>
                        </div>
                        <div class="my-auto space-y-3 text-center">
                            <div id="slideNumberBadge" class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 dark:bg-[#0d1310] border border-slate-300 dark:border-white/10 text-[#454B1B] dark:text-[#a3b14e] text-3xl font-bold shadow-inner">1</div>
                            <p id="slideCountText" class="text-xs uppercase tracking-widest text-slate-400 font-medium">Slide 1 / 6</p>
                        </div>
                        <div id="statsGrid" class="grid grid-cols-2 gap-2 text-center text-xs font-mono bg-slate-950/60 p-3 rounded-lg border border-slate-800/80"></div>
                    </div>
                </div>
            </div>
        </section>        

        <section class="process-flow-section w-full py-20 bg-white bg-grid-pattern border-b border-slate-200 overflow-hidden">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="text-center mb-12">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 dark:text-[#f8fafc] mb-4">
                        Explore ScanCare Portal Services
                    </h2>
                    <p class="text-slate-600 dark:text-[#cbd5e1] max-w-2xl mx-auto text-base">
                        Hover or select any card below to discover key features and access public health services directly.
                    </p>
                </div>

                <div class="flex flex-col lg:flex-row gap-0 h-auto lg:h-[420px] w-full rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <a href="login.php?switch_account=1" class="group relative flex-1 hover:flex-[3] focus:flex-[3] transition-all duration-500 ease-in-out p-6 lg:p-8 flex flex-col justify-between overflow-hidden cursor-pointer border-b lg:border-b-0 lg:border-r border-slate-200 bg-white hover:bg-[#4D582A] text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:ring-offset-2">
                        <div class="absolute top-6 right-6 flex h-9 w-9 origin-center items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition-all duration-500 ease-in-out pointer-events-none z-0 lg:top-8 lg:right-8 group-hover:top-1/2 group-hover:right-1/2 group-hover:translate-x-1/2 group-hover:-translate-y-1/2 group-hover:scale-[7] group-hover:bg-transparent group-hover:text-white/[0.18] lg:group-hover:scale-[9]">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="flex items-center justify-between w-full relative z-10">
                            <span class="text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full bg-slate-100 group-hover:bg-white/20 text-slate-600 group-hover:text-white transition-colors duration-300">01</span>
                        </div>
                        <div class="relative z-10 mt-8 lg:mt-0">
                            <h3 class="text-xl font-heading font-bold mb-2 text-slate-900 group-hover:text-white transition-colors duration-300">Log In / Register</h3>
                            <p class="text-sm text-slate-600 group-hover:text-slate-100 leading-relaxed transition-colors duration-300 max-w-md">Create your ScanCare account or log in to access your digital patient records, consultations, and QR health identification.</p>
                        </div>
                        <div class="relative z-10 mt-6 pt-4 border-t border-slate-100 group-hover:border-white/20 flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-500 group-hover:text-amber-200 transition-colors duration-300">Access Portal</span>
                            <i class="fas fa-arrow-right transform -translate-x-2 group-hover:translate-x-0 opacity-0 group-hover:opacity-100 transition-all duration-300 text-amber-300"></i>
                        </div>
                    </a>

                    <a href="appointments.php" class="group relative flex-1 hover:flex-[3] focus:flex-[3] transition-all duration-500 ease-in-out p-6 lg:p-8 flex flex-col justify-between overflow-hidden cursor-pointer border-b lg:border-b-0 lg:border-r border-slate-200 bg-white hover:bg-[#4D582A] text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:ring-offset-2">
                        <div class="absolute top-6 right-6 flex h-9 w-9 origin-center items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition-all duration-500 ease-in-out pointer-events-none z-0 lg:top-8 lg:right-8 group-hover:top-1/2 group-hover:right-1/2 group-hover:translate-x-1/2 group-hover:-translate-y-1/2 group-hover:scale-[7] group-hover:bg-transparent group-hover:text-white/[0.18] lg:group-hover:scale-[9]">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="flex items-center justify-between w-full relative z-10">
                            <span class="text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full bg-slate-100 group-hover:bg-white/20 text-slate-600 group-hover:text-white transition-colors duration-300">02</span>
                        </div>
                        <div class="relative z-10 mt-8 lg:mt-0">
                            <h3 class="text-xl font-heading font-bold mb-2 text-slate-900 group-hover:text-white transition-colors duration-300">Schedule a Service</h3>
                            <p class="text-sm text-slate-600 group-hover:text-slate-100 leading-relaxed transition-colors duration-300 max-w-md">Sign up or log in to your account to browse available appointment slots and reserve a time that works best for your check-ups, immunizations, or dental consultations.</p>
                        </div>
                        <div class="relative z-10 mt-6 pt-4 border-t border-slate-100 group-hover:border-white/20 flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-500 group-hover:text-amber-200 transition-colors duration-300">Book Appointment</span>
                            <i class="fas fa-arrow-right transform -translate-x-2 group-hover:translate-x-0 opacity-0 group-hover:opacity-100 transition-all duration-300 text-amber-300"></i>
                        </div>
                    </a>

                    <a href="service-areas.php" class="group relative flex-1 hover:flex-[3] focus:flex-[3] transition-all duration-500 ease-in-out p-6 lg:p-8 flex flex-col justify-between overflow-hidden cursor-pointer border-b lg:border-b-0 lg:border-r border-slate-200 bg-white hover:bg-[#4D582A] text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:ring-offset-2">
                        <div class="absolute top-6 right-6 flex h-9 w-9 origin-center items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition-all duration-500 ease-in-out pointer-events-none z-0 lg:top-8 lg:right-8 group-hover:top-1/2 group-hover:right-1/2 group-hover:translate-x-1/2 group-hover:-translate-y-1/2 group-hover:scale-[7] group-hover:bg-transparent group-hover:text-white/[0.18] lg:group-hover:scale-[9]">
                            <i class="fas fa-clinic-medical"></i>
                        </div>
                        <div class="flex items-center justify-between w-full relative z-10">
                            <span class="text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full bg-slate-100 group-hover:bg-white/20 text-slate-600 group-hover:text-white transition-colors duration-300">03</span>
                        </div>
                        <div class="relative z-10 mt-8 lg:mt-0">
                            <h3 class="text-xl font-heading font-bold mb-2 text-slate-900 group-hover:text-white transition-colors duration-300">Service Areas</h3>
                            <p class="text-sm text-slate-600 group-hover:text-slate-100 leading-relaxed transition-colors duration-300 max-w-md">Explore our Rural Health Unit branches, barangay health centers, and localized medical service offerings across the municipality.</p>
                        </div>
                        <div class="relative z-10 mt-6 pt-4 border-t border-slate-100 group-hover:border-white/20 flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-500 group-hover:text-amber-200 transition-colors duration-300">View Locations</span>
                            <i class="fas fa-arrow-right transform -translate-x-2 group-hover:translate-x-0 opacity-0 group-hover:opacity-100 transition-all duration-300 text-amber-300"></i>
                        </div>
                    </a>

                    <a href="surveillance.php" class="group relative flex-1 hover:flex-[3] focus:flex-[3] transition-all duration-500 ease-in-out p-6 lg:p-8 flex flex-col justify-between overflow-hidden cursor-pointer border-b lg:border-b-0 lg:border-r border-slate-200 bg-white hover:bg-[#4D582A] text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:ring-offset-2">
                        <div class="absolute top-6 right-6 flex h-9 w-9 origin-center items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition-all duration-500 ease-in-out pointer-events-none z-0 lg:top-8 lg:right-8 group-hover:top-1/2 group-hover:right-1/2 group-hover:translate-x-1/2 group-hover:-translate-y-1/2 group-hover:scale-[7] group-hover:bg-transparent group-hover:text-white/[0.18] lg:group-hover:scale-[9]">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="flex items-center justify-between w-full relative z-10">
                            <span class="text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full bg-slate-100 group-hover:bg-white/20 text-slate-600 group-hover:text-white transition-colors duration-300">04</span>
                        </div>
                        <div class="relative z-10 mt-8 lg:mt-0">
                            <h3 class="text-xl font-heading font-bold mb-2 text-slate-900 group-hover:text-white transition-colors duration-300">Surveillance</h3>
                            <p class="text-sm text-slate-600 group-hover:text-slate-100 leading-relaxed transition-colors duration-300 max-w-md">Real-time disease monitoring and community disease trend analysis to keep health workers and residents informed.</p>
                        </div>
                        <div class="relative z-10 mt-6 pt-4 border-t border-slate-100 group-hover:border-white/20 flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-500 group-hover:text-amber-200 transition-colors duration-300">Track Analytics</span>
                            <i class="fas fa-arrow-right transform -translate-x-2 group-hover:translate-x-0 opacity-0 group-hover:opacity-100 transition-all duration-300 text-amber-300"></i>
                        </div>
                    </a>

                    <a href="compliance.php" class="group relative flex-1 hover:flex-[3] focus:flex-[3] transition-all duration-500 ease-in-out p-6 lg:p-8 flex flex-col justify-between overflow-hidden cursor-pointer bg-white hover:bg-[#4D582A] text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:ring-offset-2">
                        <div class="absolute top-6 right-6 flex h-9 w-9 origin-center items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition-all duration-500 ease-in-out pointer-events-none z-0 lg:top-8 lg:right-8 group-hover:top-1/2 group-hover:right-1/2 group-hover:translate-x-1/2 group-hover:-translate-y-1/2 group-hover:scale-[7] group-hover:bg-transparent group-hover:text-white/[0.18] lg:group-hover:scale-[9]">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="flex items-center justify-between w-full relative z-10">
                            <span class="text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full bg-slate-100 group-hover:bg-white/20 text-slate-600 group-hover:text-white transition-colors duration-300">05</span>
                        </div>
                        <div class="relative z-10 mt-8 lg:mt-0">
                            <h3 class="text-xl font-heading font-bold mb-2 text-slate-900 group-hover:text-white transition-colors duration-300">Compliance</h3>
                            <p class="text-sm text-slate-600 group-hover:text-slate-100 leading-relaxed transition-colors duration-300 max-w-md">Review public health guidelines, regulatory compliance standards, and Data Privacy Act protections for health data.</p>
                        </div>
                        <div class="relative z-10 mt-6 pt-4 border-t border-slate-100 group-hover:border-white/20 flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-500 group-hover:text-amber-200 transition-colors duration-300">Read Guidelines</span>
                            <i class="fas fa-arrow-right transform -translate-x-2 group-hover:translate-x-0 opacity-0 group-hover:opacity-100 transition-all duration-300 text-amber-300"></i>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <section id="goals" class="flex flex-col md:flex-row w-full gap-0 min-h-[500px] overflow-hidden bg-[#454B1B] relative z-20 border-b border-slate-200">
            <a href="system-goals.php" class="group flex-1 hover:flex-[1.8] flex flex-col justify-between p-8 lg:p-12 text-white bg-[#454B1B] hover:bg-white hover:text-slate-900 transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] cursor-pointer border-b md:border-b-0 md:border-r border-white/10">
                <div>
                    <span class="text-xs font-bold tracking-widest uppercase opacity-70 group-hover:text-[#454B1B]">01</span>
                    <h3 class="text-2xl font-heading font-bold mt-4 group-hover:text-slate-900 transition-colors">SYSTEM GOALS</h3>
                    <p class="text-sm mt-3 opacity-80 group-hover:text-slate-600 transition-colors leading-relaxed">
                        Replacing slow manual paper steps with an instant, secure digital network to decrease patient wait times at the clinic.
                    </p>
                </div>
                <div class="flex items-center text-xs font-bold uppercase tracking-wider mt-8 group-hover:text-[#454B1B]">
                    <span>View Goals</span>
                    <svg class="w-4 h-4 ml-2 transform group-hover:translate-x-2 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </div>
            </a>

            <a href="automated-registries.php" aria-label="Learn more about automated registries and resource management" class="group flex-1 hover:flex-[1.8] flex flex-col justify-between p-8 lg:p-12 text-white bg-[#454B1B] hover:bg-white hover:text-slate-900 transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] cursor-pointer border-b md:border-b-0 md:border-r border-white/10">
                <div>
                    <span class="text-xs font-bold tracking-widest uppercase opacity-70 group-hover:text-[#454B1B]">02</span>
                    <h3 class="text-2xl font-heading font-bold mt-4 group-hover:text-slate-900 transition-colors">AUTOMATED REGISTRIES</h3>
                    <p class="text-sm mt-3 opacity-80 group-hover:text-slate-600 transition-colors leading-relaxed">
                        Ditch logbooks. Health staff can instantly view a resident's history via smart secure profile lookups.
                    </p>
                </div>
                <div class="flex items-center text-xs font-bold uppercase tracking-wider mt-8 group-hover:text-[#454B1B]">
                    <span>Learn More</span>
                    <svg class="w-4 h-4 ml-2 transform group-hover:translate-x-2 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </div>
            </a>

            <a href="surveillance.php" class="group flex-1 hover:flex-[1.8] flex flex-col justify-between p-8 lg:p-12 text-white bg-[#454B1B] hover:bg-white hover:text-slate-900 transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] cursor-pointer">
                <div>
                    <span class="text-xs font-bold tracking-widest uppercase opacity-70 group-hover:text-[#454B1B]">03</span>
                    <h3 class="text-2xl font-heading font-bold mt-4 group-hover:text-slate-900 transition-colors">RESOURCE MANAGEMENT</h3>
                    <p class="text-sm mt-3 opacity-80 group-hover:text-slate-600 transition-colors leading-relaxed">
                        Generates maps showing which zones or puroks in the barangay require more medicine or medical missions based on real stats.
                    </p>
                </div>
                <div class="flex items-center text-xs font-bold uppercase tracking-wider mt-8 group-hover:text-[#454B1B]">
                    <span>View Analytics</span>
                    <svg class="w-4 h-4 ml-2 transform group-hover:translate-x-2 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </div>
            </a>
        </section>

        <section class="py-20 bg-gradient-to-b from-white to-[#F8FAF6] bg-grid-pattern">
            <div class="max-w-[1700px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-8 md:gap-10 lg:gap-16 items-center">
                    <div class="flex flex-col justify-center px-2 sm:px-3 md:px-0">
                        <div style="color: #454B1B;" class="text-xs uppercase tracking-widest font-bold mb-3 block">
                            Our Mission
                        </div>
                        <h2 class="text-3xl font-heading font-bold text-slate-900 mb-6 leading-snug text-center md:text-left">
                            Eliminating healthcare inefficiencies through smart digital solutions
                        </h2>
                        <p class="text-slate-600 leading-relaxed text-justify mb-4">
                            We believe that every resident in Barangay Gutad deserves access to organized, efficient, and secure healthcare services. Our mission is to replace slow manual paper processes with instant, reliable digital systems that empower health workers and patients alike.
                        </p>
                        <p class="text-slate-600 leading-relaxed text-justify">
                            By providing a unified platform, we reduce wait times, minimize errors, and ensure that critical health information is always accessible when needed most.
                        </p>
                    </div>
                    <div class="flex flex-col justify-center px-2 sm:px-3 md:px-0">
                        <div style="color: #454B1B;" class="text-xs uppercase tracking-widest font-bold mb-3 block">
                            Our Vision
                        </div>
                        <h2 class="text-3xl font-heading font-bold text-slate-900 mb-6 leading-snug text-center md:text-left">
                            A healthier Barangay through transparent, data-driven community care
                        </h2>
                        <p class="text-slate-600 leading-relaxed text-justify mb-4">
                            We envision a future where health data flows seamlessly between residents, health workers, and barangay officials. A system where predictive analytics guide resource allocation and community members feel empowered to take charge of their own health.
                        </p>
                        <p class="text-slate-600 leading-relaxed text-justify">
                            ScanCare is just the beginning—a foundation for a smarter, more connected healthcare ecosystem.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-20 bg-grid-pattern border-b border-slate-200">
            <div class="max-w-5xl mx-auto px-6 lg:px-12">
                <div class="text-center mb-12">
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 dark:text-[#f8fafc] mb-4">Frequently Asked Questions</h2>
                    <p class="text-slate-600 dark:text-[#cbd5e1] max-w-2xl mx-auto">Get answers to common questions about our services, appointments, and how to use ScanCare.</p>
                </div>

                <div class="faq-container">
                    <div class="faq-item">
                        <button type="button" class="faq-question" onclick="toggleFAQ(this)" aria-expanded="false" aria-controls="faqAnswer1">
                            <span class="faq-question-text">What should I bring to my appointment?</span>
                            <div class="faq-toggle-icon">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </button>
                        <div id="faqAnswer1" class="faq-answer" role="region">
                            <p class="faq-answer-text">
                                Please bring a valid ID and a digital or printed copy of your ScanCare Patient QR ID Card. This ensures quick check-in and gives our medical staff instant access to your health records.
                            </p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button type="button" class="faq-question" onclick="toggleFAQ(this)" aria-expanded="false" aria-controls="faqAnswer2">
                            <span class="faq-question-text">Are all medical services at the RHU completely free?</span>
                            <div class="faq-toggle-icon">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </button>
                        <div id="faqAnswer2" class="faq-answer" role="region">
                            <p class="faq-answer-text">
                                Yes, all general check-ups, regular dental cleanings, and essential medicines provided are 100% free for registered residents of Barangay Gutad. Our mission is to make healthcare accessible to everyone in our community.
                            </p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button type="button" class="faq-question" onclick="toggleFAQ(this)" aria-expanded="false" aria-controls="faqAnswer3">
                            <span class="faq-question-text">Can I book an appointment for a family member?</span>
                            <div class="faq-toggle-icon">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </button>
                        <div id="faqAnswer3" class="faq-answer" role="region">
                            <p class="faq-answer-text">
                                Yes, you can manage appointments and generate QR codes for family members through your User Portal. Simply log in to your account, select "Manage Family Members," and book appointments on their behalf.
                            </p>
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
                <div id="footer-contact"><h4 class="font-semibold mb-4">Contact</h4><p class="text-slate-400 text-sm">Floridablanca RHU II - GUTAD<br><a href="mailto:floridablancarhu2@gmail.com" class="inline-flex items-center gap-2 hover:text-white transition"><i class="fa-solid fa-envelope w-4 text-center" aria-hidden="true"></i><span>floridablancarhu2@gmail.com</span></a><br><a href="tel:09480566872" class="inline-flex items-center gap-2 hover:text-white transition"><i class="fa-solid fa-phone w-4 text-center" aria-hidden="true"></i><span>0948 056 6872</span></a><br><a href="https://www.facebook.com/share/1DRzKzHcYD/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 hover:text-white transition" aria-label="ScanCare on Facebook"><i class="fa-brands fa-facebook-f w-4 text-center" aria-hidden="true"></i><span>RHU II - Gutad</span></a></p></div>
            </div>
            <div class="border-t border-slate-800 pt-8 text-center text-slate-400 text-sm"><p>&copy; 2026 ScanCare. All rights reserved. Your data is encrypted and secure.</p></div>
        </div>
    </footer>

    <!-- Settings Modal -->
    <div id="settingsModal" style="display: none;" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="settingsModalTitle" onclick="if (event.target === this) closeSettingsModal()">
        <div class="modal-content bg-white dark:bg-[#131c17] border border-slate-200 dark:border-[rgba(255,255,255,0.1)] shadow-2xl rounded-xl max-w-md w-full" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-white/10">
                <h2 id="settingsModalTitle" class="text-lg font-bold text-slate-900 dark:text-white">
                    <i class="fas fa-cog mr-2 text-[#454B1B]"></i> Settings
                </h2>
                <button type="button" onclick="closeSettingsModal()" aria-label="Close settings" class="text-xl text-slate-400 hover:text-slate-600 dark:text-slate-400 dark:hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-5 p-5">
                <div>
                    <h3 class="mb-2 text-sm font-bold text-slate-900 dark:text-white">
                        <i class="fas fa-bell mr-2 text-[#454B1B]"></i> Notifications
                    </h3>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-100 dark:hover:bg-[#19251f]">
                        <input type="checkbox" id="emailNotif" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #454B1B;">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Enable Email Notifications</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-100 dark:hover:bg-[#19251f]">
                        <input type="checkbox" id="smsNotif" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #454B1B;">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Enable SMS Notifications</span>
                    </label>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-bold text-slate-900 dark:text-white">
                        <i class="fas fa-lock mr-2 text-[#454B1B]"></i> Privacy
                    </h3>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-100 dark:hover:bg-[#19251f]">
                        <input type="checkbox" id="profilePrivacy" style="width: 18px; height: 18px; cursor: pointer; accent-color: #454B1B;">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Make Profile Private</span>
                    </label>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-bold text-slate-900 dark:text-white">
                        <i class="fas fa-moon mr-2 text-[#454B1B]"></i> Appearance
                    </h3>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-100 dark:hover:bg-[#19251f]">
                        <input type="checkbox" id="darkModeToggle" style="width: 18px; height: 18px; cursor: pointer; accent-color: #454B1B;">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Enable Dark Mode</span>
                    </label>
                </div>

                <div class="border-t border-slate-200 pt-5 dark:border-white/10">
                    <button type="button" onclick="changePassword()" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 font-semibold text-white shadow transition hover:bg-blue-700">
                        <i class="fas fa-key mr-2"></i> Change Password
                    </button>
                </div>
            </div>
            <div class="flex gap-3 border-t border-slate-200 p-5 dark:border-white/10">
                <button type="button" onclick="closeSettingsModal()" class="flex-1 rounded-lg bg-slate-200 px-4 py-2.5 font-semibold text-slate-800 transition hover:bg-slate-300 dark:bg-slate-700/80 dark:text-slate-200 dark:hover:bg-slate-700">Close</button>
                <button type="button" onclick="saveSettings()" class="flex-1 rounded-lg bg-[#454B1B] px-4 py-2.5 font-semibold text-white shadow transition hover:bg-[#586022]">
                    <i class="fas fa-save mr-2"></i> Save
                </button>
            </div>
        </div>
    </div>

    <script>
        let settingsOriginalState = {};
        let settingsSaveTriggered = false;

        function closeProfileDropdown() {
            const dropdown = document.getElementById('profileDropdownMenu');
            const button = document.getElementById('profileDropdownBtn');
            if (dropdown) {
                dropdown.style.display = 'none';
            }
            if (button) {
                button.classList.remove('active');
            }
        }

        function openSettingsModal() {
            closeProfileDropdown();
            settingsSaveTriggered = false;
            settingsOriginalState = {
                emailNotif: document.getElementById('emailNotif')?.checked || false,
                smsNotif: document.getElementById('smsNotif')?.checked || false,
                profilePrivacy: document.getElementById('profilePrivacy')?.checked || false,
                darkMode: document.getElementById('darkModeToggle')?.checked || false
            };

            const modal = document.getElementById('settingsModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                modal.querySelector('button')?.focus();
            }
        }

        function closeSettingsModal() {
            if (!settingsSaveTriggered) {
                restoreSettingsState();
            }

            const modal = document.getElementById('settingsModal');
            if (modal) {
                modal.style.display = 'none';
            }
            document.body.style.overflow = 'auto';
        }

        function restoreSettingsState() {
            ['emailNotif', 'smsNotif', 'profilePrivacy', 'darkModeToggle'].forEach((id) => {
                const control = document.getElementById(id);
                const stateKey = id === 'darkModeToggle' ? 'darkMode' : id;
                if (control && Object.prototype.hasOwnProperty.call(settingsOriginalState, stateKey)) {
                    control.checked = settingsOriginalState[stateKey];
                }
            });
            if (Object.prototype.hasOwnProperty.call(settingsOriginalState, 'darkMode')) {
                applyDarkMode(settingsOriginalState.darkMode);
            }
        }

        function applyDarkMode(enabled) {
            document.documentElement.classList.toggle('dark', enabled);
            document.documentElement.classList.toggle('dark-mode', enabled);
            document.body.classList.toggle('dark-mode', enabled);
        }

        function saveSettings() {
            const settings = {
                darkMode: document.getElementById('darkModeToggle')?.checked || false,
                emailNotif: document.getElementById('emailNotif')?.checked || false,
                smsNotif: document.getElementById('smsNotif')?.checked || false,
                profilePrivacy: document.getElementById('profilePrivacy')?.checked || false
            };

            Object.entries(settings).forEach(([key, enabled]) => {
                localStorage.setItem(key, enabled ? 'enabled' : 'disabled');
            });
            localStorage.setItem('theme', settings.darkMode ? 'dark' : 'light');
            if (window.ScanCareTheme) {
                window.ScanCareTheme.set(settings.darkMode ? 'dark' : 'light');
            } else {
                localStorage.setItem('scancare_theme', settings.darkMode ? 'dark' : 'light');
                applyDarkMode(settings.darkMode);
            }
            settingsSaveTriggered = true;
            alert('Settings saved successfully!');
            closeSettingsModal();
        }

        function changePassword() {
            const currentPassword = prompt('Enter your current password:');
            if (!currentPassword) return;

            const newPassword = prompt('Enter your new password (min 6 characters):');
            if (!newPassword) return;
            if (newPassword.length < 6) {
                alert('Password must be at least 6 characters long');
                return;
            }

            const confirmPassword = prompt('Confirm your new password:');
            if (!confirmPassword) return;
            if (confirmPassword !== newPassword) {
                alert('New passwords do not match');
                return;
            }

            const button = document.querySelector('[onclick="changePassword()"]');
            if (button) {
                button.disabled = true;
                button.style.opacity = '0.6';
                button.style.cursor = 'not-allowed';
            }

            fetch('change_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ currentPassword, newPassword, confirmPassword })
            })
                .then(response => response.json().then(data => ({ response, data })))
                .then(({ response, data }) => {
                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Failed to change password');
                    }
                    alert(data.message || 'Password changed successfully');
                    closeSettingsModal();
                })
                .catch(error => {
                    console.error('Password change error:', error);
                    alert(error.message || 'An error occurred while changing your password');
                })
                .finally(() => {
                    if (button) {
                        button.disabled = false;
                        button.style.opacity = '1';
                        button.style.cursor = 'pointer';
                    }
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const savedDarkMode = localStorage.getItem('darkMode') === 'enabled';
            const darkModeToggle = document.getElementById('darkModeToggle');
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const landingMobileToggle = document.getElementById('landingMobileToggle');
            const landingMobilePanel = document.getElementById('landingMobilePanel');

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    const isDark = !document.documentElement.classList.contains('dark');
                    localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                    if (window.ScanCareTheme) window.ScanCareTheme.set(isDark ? 'dark' : 'light');
                    else applyDarkMode(isDark);
                    themeToggleBtn.innerHTML = `<i class="fas fa-${isDark ? 'sun' : 'moon'}" aria-hidden="true"></i>`;
                });
            }

            if (landingMobileToggle && landingMobilePanel) {
                const setMobileMenu = (isOpen) => {
                    landingMobilePanel.classList.toggle('is-open', isOpen);
                    landingMobilePanel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                    landingMobileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                };
                landingMobileToggle.addEventListener('click', () => setMobileMenu(!landingMobilePanel.classList.contains('is-open')));
                landingMobilePanel.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setMobileMenu(false)));
                document.addEventListener('click', event => {
                    if (!landingMobilePanel.contains(event.target) && !landingMobileToggle.contains(event.target)) setMobileMenu(false);
                });
            }

            if (darkModeToggle) {
                darkModeToggle.checked = savedDarkMode;
                darkModeToggle.addEventListener('change', () => applyDarkMode(darkModeToggle.checked));
            }
            applyDarkMode(savedDarkMode);
            if (themeToggleBtn) themeToggleBtn.innerHTML = `<i class="fas fa-${savedDarkMode ? 'sun' : 'moon'}" aria-hidden="true"></i>`;
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && document.getElementById('settingsModal')?.style.display !== 'none') {
                closeSettingsModal();
            }
        });

        function toggleFAQ(element) {
            const currentItem = element.closest('.faq-item');
            const isCurrentlyActive = currentItem.classList.contains('active');

            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
                const button = item.querySelector('.faq-question');
                if (button) {
                    button.setAttribute('aria-expanded', 'false');
                }
            });

            if (!isCurrentlyActive) {
                currentItem.classList.add('active');
                element.setAttribute('aria-expanded', 'true');
            }
        }

        document.querySelectorAll('.error-fallback').forEach(img => {
            img.onerror = function() {
                this.style.display = 'none';
                this.parentNode.innerHTML = `<span style="color: #454B1B;" class="text-lg font-bold">🩺</span>`;
            };
        });

        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash === '#schedule-appointment') {
                window.location.href = 'dashboard.php#schedule-appointment';
            }

            function scrollToTarget(targetSelector) {
                const target = document.querySelector(targetSelector);
                if (!target) return;

                const offset = window.innerWidth < 768 ? 90 : 110;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;

                window.scrollTo({
                    top,
                    behavior: 'smooth'
                });
            }
            
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href !== '#' && document.querySelector(href)) {
                        e.preventDefault();
                        const target = document.querySelector(href);
                        if (target) {
                            if (href === '#footer-contact') {
                                scrollToTarget(href);
                                return;
                            }
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });

            const secondaryButton = document.getElementById('secondaryButton');
            if (secondaryButton) {
                secondaryButton.addEventListener('click', function (e) {
                    const targetSelector = this.getAttribute('data-scroll-target') || '#footer-contact';
                    e.preventDefault();
                    scrollToTarget(targetSelector);
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const splashScreen = document.getElementById('splash-screen');
            const splashVideo = document.getElementById('splash-video');
            const enterText = document.querySelector('.enter-text');
            
            if (!splashScreen || !splashVideo) {
                console.error('Splash screen elements not found');
                return;
            }

            splashVideo.addEventListener('ended', function() {
                enterText.classList.add('show');
            });

            splashVideo.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                splashScreen.classList.add('fade-out');
                
                splashScreen.addEventListener('transitionend', function(event) {
                    if (event.propertyName === 'opacity') {
                        splashScreen.remove();
                    }
                }, { once: true }); 
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && splashScreen && splashScreen.parentNode) {
                    splashScreen.click(); 
                }
            });
        });

        function updateRHUStatus() {
            const now = new Date();
            const dayOfWeek = now.getDay();
            const hour = now.getHours();
            
            const isWeekday = dayOfWeek >= 1 && dayOfWeek <= 5;
            const isOpeningHour = (hour >= 8 && hour < 17);
            
            const isOpen = isWeekday && isOpeningHour;
            const badge = document.querySelector('.rhu-status-badge');
            
            if (badge) {
                if (isOpen) {
                    badge.classList.remove('closed');
                    badge.classList.add('open');
                    badge.innerHTML = '<span class="rhu-status-dot pulse">●</span><span>● Rural Health Unit is Open | Closes at 5:00 PM</span>';
                } else {
                    badge.classList.remove('open');
                    badge.classList.add('closed');
                    badge.innerHTML = '<span class="rhu-status-dot ">●</span><span>○ RHU is Closed | Opens at 8:00 AM</span>';
                }
            }
        }
        
        updateRHUStatus();
        setInterval(updateRHUStatus, 60000);
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slideshowData = [
                {
                    image: 'bakuna_bayan.jpg',
                    badge: 'SCANCARE COMMUNITY SERVICES',
                    subBadge: 'RHU II Immunization Program',
                    title: 'Effortless Community Healthcare Services & Direct Booking',
                    description: 'Bridging Barangay Gutad residents with real-time health services, digital record access, and streamlined RHU clinic appointments.',
                    bullets: [
                        'Quick digital appointment scheduling',
                        'QR-based resident record retrieval',
                        'Live RHU operational updates & surveillance'
                    ],
                    stats: [
                        { label: 'Campaign', value: 'Ongoing' },
                        { label: 'Coverage', value: 'Barangay Gutad' }
                    ],
                    primaryBtn: 'Learn More',
                    secondaryBtn: 'Contact Support'
                },
                {
                    image: 'bakuna_eskwela.jpg',
                    badge: 'Bakuna Eskwela',
                    subBadge: 'School Immunization',
                    title: 'Bakuna Eskwela',
                    description: 'In-school immunization programs dedicated to protecting our students and young children from vaccine-preventable illnesses. Providing a safer, healthier learning environment for everyone.',
                    bullets: [
                        'In-school vaccination clinics',
                        'Student health protection',
                        'Classroom wellness education'
                    ],
                    stats: [
                        { label: 'Audience', value: 'Students' },
                        { label: 'Focus', value: 'School Health' }
                    ],
                    primaryBtn: 'Learn More',
                    secondaryBtn: 'Contact Support'
                },
                {
                    image: 'oplankabataan.jpg',
                    badge: 'Oplan Kabataan',
                    subBadge: 'Youth Health Advocacy',
                    title: 'Oplan Kabataan: Agapay Laban sa Maagang Pagbubuntis at Paninigarilyo',
                    description: 'Empowering youth through active health education. A comprehensive advocacy seminar designed to guide our young residents against early pregnancy, smoking, and risky behaviors.',
                    bullets: [
                        'Youth health education',
                        'Early pregnancy prevention',
                        'Anti-smoking advocacy'
                    ],
                    stats: [
                        { label: 'Program', value: 'Youth Care' },
                        { label: 'Goal', value: 'Healthy Choices' }
                    ],
                    primaryBtn: 'Learn More',
                    secondaryBtn: 'Contact Support'
                },
                {
                    image: 'colgate.jpg',
                    badge: 'Oral Health Services',
                    subBadge: 'Child Dental Care',
                    title: 'Comprehensive Oral Health Services',
                    description: 'Providing essential dental care directly to our children: Oral Examination & Education, Topical Fluoride Application (1st Application), Supervised Toothbrushing Drill, Distribution of Essential Healthcare Packages.',
                    bullets: [
                        'Oral examination and education',
                        'Fluoride application for children',
                        'Supervised toothbrushing drills'
                    ],
                    stats: [
                        { label: 'Service', value: 'Dental Care' },
                        { label: 'Benefit', value: 'Oral Wellness' }
                    ],
                    primaryBtn: 'Learn More',
                    secondaryBtn: 'Contact Support'
                },
                {
                    image: 'teenpreg_awareness.jpg',
                    badge: 'Pregnancy Awareness',
                    subBadge: 'School Seminar',
                    title: 'Teenage Pregnancy Awareness Seminar',
                    description: 'Advancing community health education at Gutad National High School. A collaborative public health seminar hosted alongside Holy Angel University Nursing Students.',
                    bullets: [
                        'School-based awareness seminar',
                        'Collaborative nursing support',
                        'Youth health education'
                    ],
                    stats: [
                        { label: 'Seminar', value: 'Awareness' },
                        { label: 'Partner', value: 'Holy Angel University' }
                    ],
                    primaryBtn: 'Learn More',
                    secondaryBtn: 'Contact Support'
                },
                {
                    image: 'program.jpg',
                    badge: 'Program Review',
                    subBadge: 'Operational Audit',
                    title: 'Program Implementation Review',
                    description: 'Ensuring the highest standard of public health. A localized operational review of all Department of Health (DOH) medical programs to continuously improve healthcare services for Barangay Gutad.',
                    bullets: [
                        'Localized program assessment',
                        'DOH healthcare service review',
                        'Continuous quality improvement'
                    ],
                    stats: [
                        { label: 'Review', value: 'Program Audit' },
                        { label: 'Goal', value: 'Service Quality' }
                    ],
                    primaryBtn: 'Learn More',
                    secondaryBtn: 'Contact Support'
                }
            ];

            let currentSlideIndex = 0;
            let isPaused = false;
            const heroImage = document.getElementById('heroImage');
            const heroImageMobile = document.getElementById('heroImageMobile');
            const heroBadgeText = document.getElementById('heroBadgeText');
            const heroBadgeSubtext = document.getElementById('heroBadgeSubtext');
            const heroHeading = document.getElementById('heroHeading');
            const heroSubtext = document.getElementById('heroSubtext');
            const heroBullets = document.getElementById('heroBullets');
            const primaryButton = document.getElementById('primaryButton');
            const secondaryButton = document.getElementById('secondaryButton');
            const slideNumberBadge = document.getElementById('slideNumberBadge');
            const slideCountText = document.getElementById('slideCountText');
            const statsGrid = document.getElementById('statsGrid');
            const slideCounter = document.getElementById('slideCounter');
            const slideStatistic = document.getElementById('slideStatistic');
            const slideStatisticLabel = document.getElementById('slideStatisticLabel');
            const heroCarousel = document.getElementById('heroCarouselContainer');
            const prevSlideBtn = document.getElementById('prevSlideBtn');
            const nextSlideBtn = document.getElementById('nextSlideBtn');
            const navigationDotsContainer = document.getElementById('navigationDots');
            const staggerStepIds = ['staggerStep1', 'staggerStep2', 'staggerStep3', 'staggerStats', 'staggerStep4'];
            const staggerSteps = staggerStepIds.map(id => document.getElementById(id)).filter(Boolean);
            let autoplayTimer = null;

            function initializeNavigationDots() {
                if (!navigationDotsContainer) return;
                navigationDotsContainer.innerHTML = '';

                slideshowData.forEach((_, index) => {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.setAttribute('aria-label', `Go to slide ${index + 1}`);
                    dot.className = 'h-2 w-2 rounded-full bg-white/60 hover:bg-white transition-all duration-300';
                    dot.addEventListener('click', (e) => {
                        e.preventDefault();
                        updateCarousel(index);
                    });
                    navigationDotsContainer.appendChild(dot);
                });
            }

            function updateDots() {
                if (!navigationDotsContainer) return;
                Array.from(navigationDotsContainer.children).forEach((dot, dotIndex) => {
                    dot.className = dotIndex === currentSlideIndex
                        ? 'h-2 w-8 rounded-full bg-[#4D582A] transition-all duration-300'
                        : 'h-2 w-2 rounded-full bg-white/60 hover:bg-white transition-all duration-300';
                });
            }

            function resetStaggeredAnimation() {
                const animatedElements = [
                    ...staggerSteps,
                    ...(heroBullets ? Array.from(heroBullets.querySelectorAll('.cascade-item')) : [])
                ];

                animatedElements.forEach((el) => {
                    el.classList.remove('animate-slide-up');
                    void el.offsetWidth;
                    el.classList.add('animate-slide-up');
                });
            }

            function updateStatsGrid(stats) {
                if (!statsGrid) return;
                statsGrid.innerHTML = stats.map(stat => `
                    <div class="px-1">
                        <div class="text-[#334155] dark:text-[#94a3b8] text-[10px] uppercase">${stat.label}</div>
                        <div class="text-[#454B1B] dark:text-[#a3b14e] font-bold text-sm mt-0.5">${stat.value}</div>
                    </div>
                `).join('');
            }

            function updateBullets(bullets) {
                if (!heroBullets) return;
                heroBullets.innerHTML = '';
                bullets.forEach((bullet, idx) => {
                    const li = document.createElement('li');
                    li.className = `cascade-item delay-${350 + idx * 70} flex items-start gap-3 text-[#334155] dark:text-[#cbd5e1] text-sm md:text-base`;
                    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    svg.setAttribute('class', 'w-5 h-5 text-[#a3b14e] flex-shrink-0 mt-0.5');
                    svg.setAttribute('fill', 'none');
                    svg.setAttribute('viewBox', '0 0 24 24');
                    svg.setAttribute('stroke', 'currentColor');
                    svg.setAttribute('stroke-width', '2.5');

                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('stroke-linecap', 'round');
                    path.setAttribute('stroke-linejoin', 'round');
                    path.setAttribute('d', 'M5 13l4 4L19 7');
                    svg.appendChild(path);

                    const span = document.createElement('span');
                    span.textContent = bullet;

                    li.appendChild(svg);
                    li.appendChild(span);
                    heroBullets.appendChild(li);
                });
            }

            function updateCarousel(index) {
                currentSlideIndex = (index + slideshowData.length) % slideshowData.length;
                const slide = slideshowData[currentSlideIndex];
                const imagePath = slide.image && slide.image.startsWith('http') ? slide.image : `images/${slide.image}`;

                if (heroImage) heroImage.classList.add('opacity-0');
                if (heroImageMobile) heroImageMobile.classList.add('opacity-0');

                setTimeout(() => {
                    if (heroImage) heroImage.src = imagePath;
                    if (heroImageMobile) heroImageMobile.src = imagePath;
                    if (heroBadgeText) heroBadgeText.textContent = slide.badge;
                    if (heroBadgeSubtext) heroBadgeSubtext.textContent = slide.subBadge || 'System Active';
                    if (heroHeading) heroHeading.innerHTML = slide.title;
                    if (heroSubtext) heroSubtext.innerHTML = slide.description;
                    if (primaryButton) primaryButton.textContent = slide.primaryBtn;
                    if (secondaryButton) {
                        secondaryButton.textContent = slide.secondaryBtn;
                        secondaryButton.href = '#footer-contact';
                        secondaryButton.setAttribute('data-scroll-target', '#footer-contact');
                    }
                    if (slideNumberBadge) slideNumberBadge.textContent = currentSlideIndex + 1;
                    if (slideCountText) slideCountText.textContent = `Slide ${currentSlideIndex + 1} / ${slideshowData.length}`;
                    if (slideCounter) slideCounter.textContent = `${currentSlideIndex + 1}/${slideshowData.length}`;
                    if (slideStatistic) slideStatistic.textContent = slide.stats[1]?.value || slide.stats[0]?.value || 'Health Access';
                    if (slideStatisticLabel) slideStatisticLabel.textContent = slide.stats[1]?.label || slide.stats[0]?.label || 'Real-time';
                    updateStatsGrid(slide.stats);
                    updateBullets(slide.bullets);
                    updateDots();
                    resetStaggeredAnimation();
                    if (heroImage) heroImage.classList.remove('opacity-0');
                    if (heroImageMobile) heroImageMobile.classList.remove('opacity-0');
                }, 120);
            }

            function startAutoplay() {
                if (autoplayTimer) clearInterval(autoplayTimer);
                if (isPaused) return;
                autoplayTimer = setInterval(() => updateCarousel(currentSlideIndex + 1), 6500);
            }

            function stopAutoplay() {
                if (autoplayTimer) {
                    clearInterval(autoplayTimer);
                    autoplayTimer = null;
                }
            }

            if (heroCarousel) {
                heroCarousel.addEventListener('mouseenter', () => {
                    isPaused = true;
                    stopAutoplay();
                });
                heroCarousel.addEventListener('mouseleave', () => {
                    isPaused = false;
                    startAutoplay();
                });
            }

            if (prevSlideBtn) {
                prevSlideBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    updateCarousel(currentSlideIndex - 1);
                });
            }

            if (nextSlideBtn) {
                nextSlideBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    updateCarousel(currentSlideIndex + 1);
                });
            }

            window.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    updateCarousel(currentSlideIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    updateCarousel(currentSlideIndex + 1);
                }
            });

            initializeNavigationDots();
            updateCarousel(0);
            startAutoplay();
        });
    </script>

</body>
</html>
