<?php
/**
 * ============================================================================================
 * BNCC MARKETPLACE - ENTERPRISE MASTER HEADER SYSTEM
 * ============================================================================================
 * Architecture: Model-View-Controller (Frontend Bound)
 * Engine: PHP 8.x + Native Vanilla JS + CSS3 Advanced Variables
 * Components: Auth Guard, Notification Engine, Dynamic Theming, Routing Controller
 * ============================================================================================
 */

// --------------------------------------------------------------------------------------------
// 1. CORE DEPENDENCIES & INITIALIZATION
// --------------------------------------------------------------------------------------------
require_once __DIR__ . '/functions.php';

// Determine the current active page for navigation highlighting and logic mapping
$current_page = basename($_SERVER['PHP_SELF']);

// Ensure session is started safely to prevent "Headers Already Sent" anomalies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------------------------------------------------------------------------
// 2. ROUTING VISIBILITY CONTROLLERS (Access Control Lists)
// --------------------------------------------------------------------------------------------
$hide_home_list = [
    'login.php', 
    'register.php', 
    'register_google.php', 
    'verify_otp.php', 
    'appeal_ban.php'
];

$hide_auth_list = [
    'index.php', 
    'register_seller.php', 
    'product_detail.php',
    'login.php', 
    'register.php', 
    'register_google.php', 
    'verify_otp.php'
];

// --------------------------------------------------------------------------------------------
// 3. USER DATA NORMALIZATION
// --------------------------------------------------------------------------------------------
// Process User Avatar with absolute pathing to prevent broken images in subdirectories
$base_path_assets = defined('BASE_URL') ? BASE_URL : '/s673190104/student_marketplace/';

if (isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img'])) {
    // Check if the image is a full URL (like Google Profile)
    if (filter_var($_SESSION['profile_img'], FILTER_VALIDATE_URL)) {
        $user_avatar = $_SESSION['profile_img'];
    } else {
        $user_avatar = $base_path_assets . "assets/images/profiles/" . $_SESSION['profile_img'];
    }
} else {
    $user_avatar = $base_path_assets . "assets/images/profiles/default_profile.png";
}

// --------------------------------------------------------------------------------------------
// 4. REAL-TIME DATA FETCHING (Message Counter)
// --------------------------------------------------------------------------------------------
$unread_msg_count = 0;

if (isLoggedIn()) {
    try {
        $db = getDB();
        // Optimize query by only checking receiver ID and unread status flag
        $msg_query = "SELECT COUNT(id) FROM messages WHERE receiver_id = ? AND is_read = 0";
        $msg_stmt = $db->prepare($msg_query);
        $msg_stmt->execute([$_SESSION['user_id']]);
        $unread_msg_count = (int)$msg_stmt->fetchColumn();
    } catch (PDOException $e) {
        // Fail silently on header to prevent UI breakage, log internally if needed
        error_log("Header Message Fetch Error: " . $e->getMessage());
        $unread_msg_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    
    <meta name="theme-color" content="#4f46e5" id="meta-theme-color">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="BNCC Market">
    <meta name="application-name" content="BNCC Market">
    
    <meta name="description" content="BNCC Market - Enterprise Student Marketplace for Buying, Selling, and Requesting Items within the Campus.">
    <meta name="keywords" content="BNCC, Marketplace, Student, E-commerce, Buy, Sell">
    <meta name="author" content="BNCC Developer Team">
    
    <title>
        <?php 
        if (isset($pageTitle)) {
            echo htmlspecialchars($pageTitle) . ' | BNCC Market';
        } else {
            echo 'BNCC Market | ระบบตลาดกลางวิทยาลัย';
        }
        ?>
    </title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_path_assets ?>assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $base_path_assets ?>assets/images/favicon-16x16.png">
    <link rel="shortcut icon" href="<?= $base_path_assets ?>assets/images/favicon.ico">
    
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= $base_path_assets ?>assets/css/style.css">

    <style>
        /* -------------------------------------------------------------------------------------
           PART 1: CSS CUSTOM PROPERTIES (VARIABLES) DECLARATION
           ------------------------------------------------------------------------------------- */
        :root {
            /* BNCC Core Brand Identity Colors */
            --bncc-brand-50: #eef2ff;
            --bncc-brand-100: #e0e7ff;
            --bncc-brand-200: #c7d2fe;
            --bncc-brand-300: #a5b4fc;
            --bncc-brand-400: #818cf8;
            --bncc-brand-500: #6366f1;
            --bncc-brand-600: #4f46e5;
            --bncc-brand-700: #4338ca;
            --bncc-brand-800: #3730a3;
            --bncc-brand-900: #312e81;
            --bncc-brand-950: #1e1b4b;
            
            /* BNCC Functional Colors: Success (Green) */
            --bncc-success-50: #ecfdf5;
            --bncc-success-100: #d1fae5;
            --bncc-success-200: #a7f3d0;
            --bncc-success-300: #6ee7b7;
            --bncc-success-400: #34d399;
            --bncc-success-500: #10b981;
            --bncc-success-600: #059669;
            --bncc-success-700: #047857;
            --bncc-success-800: #065f46;
            --bncc-success-900: #064e3b;
            
            /* BNCC Functional Colors: Danger (Red) */
            --bncc-danger-50: #fef2f2;
            --bncc-danger-100: #fee2e2;
            --bncc-danger-200: #fecaca;
            --bncc-danger-300: #fca5a5;
            --bncc-danger-400: #f87171;
            --bncc-danger-500: #ef4444;
            --bncc-danger-600: #dc2626;
            --bncc-danger-700: #b91c1c;
            --bncc-danger-800: #991b1b;
            --bncc-danger-900: #7f1d1d;
            
            /* BNCC Functional Colors: Warning (Yellow/Orange) */
            --bncc-warning-50: #fffbeb;
            --bncc-warning-100: #fef3c7;
            --bncc-warning-200: #fde68a;
            --bncc-warning-300: #fcd34d;
            --bncc-warning-400: #fbbf24;
            --bncc-warning-500: #f59e0b;
            --bncc-warning-600: #d97706;
            --bncc-warning-700: #b45309;
            --bncc-warning-800: #92400e;
            --bncc-warning-900: #78350f;

            /* BNCC Functional Colors: Info (Blue) */
            --bncc-info-50: #f0f9ff;
            --bncc-info-100: #e0f2fe;
            --bncc-info-200: #bae6fd;
            --bncc-info-300: #7dd3fc;
            --bncc-info-400: #38bdf8;
            --bncc-info-500: #0ea5e9;
            --bncc-info-600: #0284c7;
            --bncc-info-700: #0369a1;
            --bncc-info-800: #075985;
            --bncc-info-900: #0c4a6e;

            /* Light Theme Contextual Mapping */
            --bncc-surface-light: #ffffff;
            --bncc-surface-light-alt: #f8fafc;
            --bncc-background-light: #f1f5f9;
            
            --bncc-text-primary-light: #0f172a;
            --bncc-text-secondary-light: #475569;
            --bncc-text-tertiary-light: #94a3b8;
            --bncc-text-inverse-light: #ffffff;
            
            --bncc-border-light: #e2e8f0;
            --bncc-border-focus-light: #cbd5e1;

            /* Dark Theme Contextual Mapping */
            --bncc-surface-dark: #111827;
            --bncc-surface-dark-alt: #1f2937;
            --bncc-background-dark: #030712;
            
            --bncc-text-primary-dark: #f8fafc;
            --bncc-text-secondary-dark: #cbd5e1;
            --bncc-text-tertiary-dark: #64748b;
            --bncc-text-inverse-dark: #0f172a;
            
            --bncc-border-dark: #334155;
            --bncc-border-focus-dark: #475569;

            /* Glassmorphism Effect Engine */
            --bncc-glass-bg-light: rgba(255, 255, 255, 0.85);
            --bncc-glass-bg-dark: rgba(11, 15, 25, 0.85);
            --bncc-glass-border-light: rgba(255, 255, 255, 0.4);
            --bncc-glass-border-dark: rgba(255, 255, 255, 0.05);
            --bncc-glass-blur-sm: blur(12px) saturate(150%);
            --bncc-glass-blur-md: blur(24px) saturate(200%);
            --bncc-glass-blur-lg: blur(40px) saturate(250%);

            /* Typography Scale System */
            --bncc-font-family: 'Prompt', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            --bncc-font-xs: 0.75rem;     /* 12px */
            --bncc-font-sm: 0.875rem;    /* 14px */
            --bncc-font-base: 1rem;      /* 16px */
            --bncc-font-lg: 1.125rem;    /* 18px */
            --bncc-font-xl: 1.25rem;     /* 20px */
            --bncc-font-2xl: 1.5rem;     /* 24px */
            --bncc-font-3xl: 1.875rem;   /* 30px */
            --bncc-font-4xl: 2.25rem;    /* 36px */
            --bncc-font-5xl: 3rem;       /* 48px */
            
            /* Line Heights */
            --bncc-leading-none: 1;
            --bncc-leading-tight: 1.25;
            --bncc-leading-snug: 1.375;
            --bncc-leading-normal: 1.5;
            --bncc-leading-relaxed: 1.625;
            --bncc-leading-loose: 2;

            /* Elevation & Box Shadows System */
            --bncc-shadow-xs: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --bncc-shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --bncc-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --bncc-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --bncc-shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --bncc-shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            --bncc-shadow-inner: inset 0 2px 4px 0 rgb(0 0 0 / 0.05);
            --bncc-shadow-none: 0 0 #0000;
            
            /* Custom Glow Effects */
            --bncc-glow-primary-sm: 0 0 10px rgba(99, 102, 241, 0.3);
            --bncc-glow-primary-md: 0 0 20px rgba(99, 102, 241, 0.4);
            --bncc-glow-primary-lg: 0 0 30px rgba(99, 102, 241, 0.5);
            --bncc-glow-danger-md: 0 0 20px rgba(239, 68, 68, 0.4);
            --bncc-glow-success-md: 0 0 20px rgba(16, 185, 129, 0.4);

            /* Z-Index Hierarchy Layering */
            --bncc-z-hide: -10;
            --bncc-z-base: 0;
            --bncc-z-docked: 10;
            --bncc-z-dropdown: 1000;
            --bncc-z-sticky: 1020;
            --bncc-z-fixed: 1030;
            --bncc-z-modal-backdrop: 1040;
            --bncc-z-modal: 1050;
            --bncc-z-popover: 1060;
            --bncc-z-tooltip: 1070;
            --bncc-z-sidebar-overlay: 1090;
            --bncc-z-sidebar: 1100;
            --bncc-z-toast: 1200;
            --bncc-z-preloader: 9999;

            /* Transition Curves & Physics */
            --bncc-ease-linear: cubic-bezier(0.0, 0.0, 1.0, 1.0);
            --bncc-ease-in: cubic-bezier(0.4, 0.0, 1.0, 1.0);
            --bncc-ease-out: cubic-bezier(0.0, 0.0, 0.2, 1.0);
            --bncc-ease-in-out: cubic-bezier(0.4, 0.0, 0.2, 1.0);
            --bncc-ease-bounce-sm: cubic-bezier(0.34, 1.56, 0.64, 1);
            --bncc-ease-bounce-md: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            --bncc-ease-bounce-lg: cubic-bezier(0.175, 0.885, 0.32, 1.275);
            
            --bncc-duration-instant: 75ms;
            --bncc-duration-fast: 150ms;
            --bncc-duration-normal: 300ms;
            --bncc-duration-slow: 500ms;
            --bncc-duration-slug: 1000ms;

            /* Structural Layout Measurements */
            --bncc-header-height: 84px;
            --bncc-header-height-scrolled: 70px;
            --bncc-header-height-mobile: 64px;
            --bncc-sidebar-width: 340px;
            --bncc-sidebar-width-collapsed: 80px;
            --bncc-container-max: 1440px;
            --bncc-container-padding: 2rem;
            --bncc-container-padding-mobile: 1rem;
            
            /* Border Radii */
            --bncc-radius-none: 0px;
            --bncc-radius-xs: 0.125rem;
            --bncc-radius-sm: 0.25rem;
            --bncc-radius-md: 0.375rem;
            --bncc-radius-lg: 0.5rem;
            --bncc-radius-xl: 0.75rem;
            --bncc-radius-2xl: 1rem;
            --bncc-radius-3xl: 1.5rem;
            --bncc-radius-full: 9999px;
        }

        /* -------------------------------------------------------------------------------------
           PART 2: THEME SWITCHING ENGINE (VARIABLE MAPPING)
           ------------------------------------------------------------------------------------- */
           
        /* Map Light Theme variables to operational variables by default */
        html, html[data-theme="light"], :root {
            --theme-bg: var(--bncc-background-light);
            --theme-surface: var(--bncc-surface-light);
            --theme-surface-alt: var(--bncc-surface-light-alt);
            
            --theme-text-primary: var(--bncc-text-primary-light);
            --theme-text-secondary: var(--bncc-text-secondary-light);
            --theme-text-tertiary: var(--bncc-text-tertiary-light);
            --theme-text-inverse: var(--bncc-text-inverse-light);
            
            --theme-border: var(--bncc-border-light);
            --theme-border-focus: var(--bncc-border-focus-light);
            
            --theme-glass-bg: var(--bncc-glass-bg-light);
            --theme-glass-border: var(--bncc-glass-border-light);
            
            --theme-shadow-base: var(--bncc-shadow-md);
            --theme-shadow-hover: var(--bncc-shadow-lg);
            
            --theme-hover-bg: rgba(15, 23, 42, 0.05);
            --theme-active-bg: rgba(15, 23, 42, 0.1);
            --theme-input-bg: var(--bncc-surface-light);
            
            /* Status Specific Overrides for Light */
            --theme-chart-grid: rgba(0,0,0,0.05);
        }

        /* Map Dark Theme variables to operational variables when dark mode is active */
        html[data-theme="dark"], .dark-theme {
            --theme-bg: var(--bncc-background-dark);
            --theme-surface: var(--bncc-surface-dark);
            --theme-surface-alt: var(--bncc-surface-dark-alt);
            
            --theme-text-primary: var(--bncc-text-primary-dark);
            --theme-text-secondary: var(--bncc-text-secondary-dark);
            --theme-text-tertiary: var(--bncc-text-tertiary-dark);
            --theme-text-inverse: var(--bncc-text-inverse-dark);
            
            --theme-border: var(--bncc-border-dark);
            --theme-border-focus: var(--bncc-border-focus-dark);
            
            --theme-glass-bg: var(--bncc-glass-bg-dark);
            --theme-glass-border: var(--bncc-glass-border-dark);
            
            /* Shadows in dark mode are typically darker/harsher to show depth without relying on borders */
            --theme-shadow-base: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 0 2px 4px -2px rgba(0, 0, 0, 0.5);
            --theme-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.6), 0 4px 6px -4px rgba(0, 0, 0, 0.6);
            
            --theme-hover-bg: rgba(255, 255, 255, 0.05);
            --theme-active-bg: rgba(255, 255, 255, 0.1);
            --theme-input-bg: rgba(15, 23, 42, 0.5);
            
            /* Status Specific Overrides for Dark */
            --theme-chart-grid: rgba(255,255,255,0.05);
        }

        /* -------------------------------------------------------------------------------------
           PART 3: CSS RESET & HARD NORMALIZATION
           ------------------------------------------------------------------------------------- */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            border-width: 0;
            border-style: solid;
            border-color: var(--theme-border);
            -webkit-tap-highlight-color: transparent;
        }

        html {
            line-height: var(--bncc-leading-normal);
            -webkit-text-size-adjust: 100%;
            -moz-tab-size: 4;
            tab-size: 4;
            font-family: var(--bncc-font-family);
            font-feature-settings: normal;
            font-variation-settings: normal;
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            line-height: inherit;
            background-color: var(--theme-bg);
            color: var(--theme-text-primary);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            
            /* Crucial transition for smooth theme switching */
            transition: background-color var(--bncc-duration-normal) var(--bncc-ease-linear), 
                        color var(--bncc-duration-normal) var(--bncc-ease-linear);
            
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            text-rendering: optimizeLegibility;
        }

        /* Body State Classes */
        body.noscroll {
            overflow: hidden;
            touch-action: none; /* Prevent rubber-banding on mobile */
        }
        
        body.is-loading * {
            transition: none !important;
            animation: none !important;
        }

        hr {
            height: 0;
            color: inherit;
            border-top-width: 1px;
            margin: 1.5rem 0;
        }

        abbr:where([title]) {
            text-decoration: underline dotted;
        }

        h1, h2, h3, h4, h5, h6 {
            font-size: inherit;
            font-weight: inherit;
            line-height: var(--bncc-leading-tight);
        }

        a {
            color: inherit;
            text-decoration: inherit;
            background-color: transparent;
            transition: color var(--bncc-duration-fast) var(--bncc-ease-out);
        }

        b, strong { font-weight: bolder; }

        code, kbd, samp, pre {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 1em;
        }

        small { font-size: 80%; }
        sub, sup { font-size: 75%; line-height: 0; position: relative; vertical-align: baseline; }
        sub { bottom: -0.25em; }
        sup { top: -0.5em; }

        table { text-indent: 0; border-color: inherit; border-collapse: collapse; }

        button, input, optgroup, select, textarea {
            font-family: inherit;
            font-feature-settings: inherit;
            font-variation-settings: inherit;
            font-size: 100%;
            font-weight: inherit;
            line-height: inherit;
            color: inherit;
            margin: 0;
            padding: 0;
        }

        button, select { text-transform: none; }

        button, [type='button'], [type='reset'], [type='submit'] {
            -webkit-appearance: button;
            appearance: button;
            background-color: transparent;
            background-image: none;
            cursor: pointer;
        }

        button:focus, input:focus, select:focus, textarea:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
        }

        :-moz-focusring { outline: auto; }
        :-moz-ui-invalid { box-shadow: none; }

        progress { vertical-align: baseline; }

        ::-webkit-inner-spin-button, ::-webkit-outer-spin-button { height: auto; }
        [type='search'] { -webkit-appearance: textfield; appearance: textfield; outline-offset: -2px; }
        ::-webkit-search-decoration { -webkit-appearance: none; }
        ::-webkit-file-upload-button { -webkit-appearance: button; font: inherit; }

        summary { display: list-item; }
        blockquote, dl, dd, h1, h2, h3, h4, h5, h6, hr, figure, p, pre { margin: 0; }
        fieldset { margin: 0; padding: 0; }
        legend { padding: 0; }
        ol, ul, menu { list-style: none; margin: 0; padding: 0; }
        dialog { padding: 0; }
        textarea { resize: vertical; }
        input::-moz-placeholder, textarea::-moz-placeholder { opacity: 1; color: var(--theme-text-tertiary); }
        input::placeholder, textarea::placeholder { opacity: 1; color: var(--theme-text-tertiary); }

        button, [role="button"] { cursor: pointer; }
        :disabled { cursor: default; }

        img, svg, video, canvas, audio, iframe, embed, object {
            display: block;
            vertical-align: middle;
            max-width: 100%;
            height: auto;
        }

        [hidden] { display: none !important; }

        /* -------------------------------------------------------------------------------------
           PART 4: CUSTOM SCROLLBAR ARCHITECTURE
           ------------------------------------------------------------------------------------- */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        ::-webkit-scrollbar-track {
            background-color: var(--theme-bg);
            border-radius: 0;
        }

        ::-webkit-scrollbar-thumb {
            background-color: var(--theme-border-focus);
            border-radius: var(--bncc-radius-full);
            border: 3px solid var(--theme-bg);
            background-clip: content-box;
            transition: background-color var(--bncc-duration-normal);
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: var(--bncc-brand-500);
        }
        
        /* Firefox Scrollbar Mapping */
        * {
            scrollbar-width: thin;
            scrollbar-color: var(--theme-border-focus) var(--theme-bg);
        }

        /* -------------------------------------------------------------------------------------
           PART 5: KEYFRAME ANIMATION ENGINE
           ------------------------------------------------------------------------------------- */
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        @keyframes fadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; }
        }

        @keyframes slideDown {
            0% { transform: translateY(-100%); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideUp {
            0% { transform: translateY(20px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideInRight {
            0% { transform: translateX(-100%); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideInLeft {
            0% { transform: translateX(100%); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        @keyframes scaleIn {
            0% { transform: scale(0.95); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes scaleOut {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0; }
        }

        @keyframes pulseRing {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        
        @keyframes pulseRingSuccess {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        
        @keyframes pulseRingPrimary {
            0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); }
            100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }

        @keyframes spinSlow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes floatY {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        @keyframes skeletonLoading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        @keyframes shimmerEffect {
            0% { transform: translateX(-100%) skewX(-15deg); }
            100% { transform: translateX(200%) skewX(-15deg); }
        }
        
        @keyframes bellShake {
            0% { transform: rotate(0); }
            10% { transform: rotate(15deg); }
            20% { transform: rotate(-10deg); }
            30% { transform: rotate(10deg); }
            40% { transform: rotate(-10deg); }
            50% { transform: rotate(5deg); }
            60% { transform: rotate(-5deg); }
            70% { transform: rotate(0); }
            100% { transform: rotate(0); }
        }

        /* -------------------------------------------------------------------------------------
           PART 6: GLOBAL PRELOADER COMPONENT
           ------------------------------------------------------------------------------------- */
        .bncc-sys-preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: var(--theme-bg);
            z-index: var(--bncc-z-preloader);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity var(--bncc-duration-slow) var(--bncc-ease-in-out), 
                        visibility var(--bncc-duration-slow) var(--bncc-ease-in-out),
                        transform var(--bncc-duration-slow) var(--bncc-ease-in-out);
        }

        .bncc-sys-preloader.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: scale(1.05);
        }

        .sys-preloader-spinner {
            position: relative;
            width: 80px;
            height: 80px;
            margin-bottom: 24px;
        }

        .sys-preloader-circle {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 4px solid var(--theme-border);
            border-radius: 50%;
        }

        .sys-preloader-spin {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-top-color: var(--bncc-brand-500);
            border-right-color: var(--bncc-brand-400);
            border-radius: 50%;
            animation: spinSlow 1s var(--bncc-ease-bounce-md) infinite;
        }

        .sys-preloader-brand {
            font-size: var(--bncc-font-xl);
            font-weight: 900;
            color: var(--theme-text-primary);
            letter-spacing: 4px;
            text-transform: uppercase;
            animation: fadeIn 1s infinite alternate;
            position: relative;
        }
        
        .sys-preloader-brand span {
            color: var(--bncc-brand-500);
        }

        /* -------------------------------------------------------------------------------------
           PART 7: BACKGROUND PARTICLE EFFECT SYSTEM
           ------------------------------------------------------------------------------------- */
        .bg-aesthetic-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: var(--bncc-z-under);
            overflow: hidden;
            pointer-events: none;
        }

        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: floatY 15s infinite ease-in-out;
            will-change: transform;
        }

        .dark-theme .bg-orb {
            opacity: 0.15;
            filter: blur(100px);
        }

        .bg-orb-1 { 
            width: 40vw; 
            height: 40vw; 
            top: -10vw; 
            left: -10vw; 
            background: radial-gradient(circle, var(--bncc-brand-300), transparent 70%);
            animation-duration: 20s; 
        }
        
        .bg-orb-2 { 
            width: 35vw; 
            height: 35vw; 
            bottom: -5vw; 
            right: -10vw; 
            background: radial-gradient(circle, var(--bncc-info-300), transparent 70%);
            animation-duration: 25s; 
            animation-delay: -5s; 
        }
        
        .bg-orb-3 { 
            width: 25vw; 
            height: 25vw; 
            top: 40%; 
            left: 60%; 
            background: radial-gradient(circle, var(--bncc-success-200), transparent 70%);
            animation-duration: 18s; 
            animation-delay: -2s; 
        }
        
        /* Add CSS Grid pattern overlay */
        .bg-grid-pattern {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: 
                linear-gradient(to right, var(--theme-border) 1px, transparent 1px),
                linear-gradient(to bottom, var(--theme-border) 1px, transparent 1px);
            background-size: 60px 60px;
            opacity: 0.3;
            mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
            -webkit-mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
        }

        /* -------------------------------------------------------------------------------------
           PART 8: MASTER HEADER (NAVIGATION BAR) ARCHITECTURE
           ------------------------------------------------------------------------------------- */
        .header-master-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--bncc-header-height);
            background-color: var(--theme-glass-bg);
            -webkit-backdrop-filter: var(--bncc-glass-blur-md);
            backdrop-filter: var(--bncc-glass-blur-md);
            border-bottom: 1px solid var(--theme-glass-border);
            z-index: var(--bncc-z-fixed);
            transition: height var(--bncc-duration-normal) var(--bncc-ease-bounce-md),
                        background-color var(--bncc-duration-normal) var(--bncc-ease-linear),
                        box-shadow var(--bncc-duration-normal) var(--bncc-ease-linear),
                        border-color var(--bncc-duration-normal) var(--bncc-ease-linear);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .header-master-wrapper.is-scrolled {
            height: var(--bncc-header-height-scrolled);
            box-shadow: var(--theme-shadow-base);
            background-color: var(--theme-surface);
            -webkit-backdrop-filter: none;
            backdrop-filter: none;
            border-bottom-color: var(--theme-border);
        }

        /* Reading Progress Indicator */
        .nav-progress-indicator {
            position: absolute;
            bottom: -1px;
            left: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--bncc-info-400), var(--bncc-brand-500), var(--bncc-brand-400));
            background-size: 200% 100%;
            width: 0%;
            z-index: calc(var(--bncc-z-fixed) + 1);
            transition: width 0.1s var(--bncc-ease-linear);
            animation: skeletonLoading 3s linear infinite;
        }

        .header-layout-container {
            width: 100%;
            max-width: var(--bncc-container-max);
            margin: 0 auto;
            padding: 0 var(--bncc-container-padding);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
        }

        /* -------------------------------------------------------------------------------------
           PART 8.1: HEADER LEFT ZONE (BRANDING & TOGGLE)
           ------------------------------------------------------------------------------------- */
        .nav-zone-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        /* Animated Hamburger Menu Button */
        .btn-sidebar-toggle {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 48px;
            height: 48px;
            border-radius: var(--bncc-radius-lg);
            background-color: var(--theme-surface);
            border: 1px solid var(--theme-border);
            box-shadow: var(--bncc-shadow-xs);
            cursor: pointer;
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce-lg);
            position: relative;
            z-index: calc(var(--bncc-z-sidebar) + 10);
            overflow: hidden;
        }

        .btn-sidebar-toggle::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: var(--bncc-brand-500);
            opacity: 0;
            transition: opacity var(--bncc-duration-normal);
            z-index: 0;
        }

        .btn-sidebar-toggle:hover, .btn-sidebar-toggle:focus-visible {
            transform: scale(1.05);
            border-color: var(--bncc-brand-400);
            box-shadow: var(--bncc-shadow-md);
        }
        
        .btn-sidebar-toggle:hover::before {
            opacity: 0.1;
        }
        
        .dark-theme .btn-sidebar-toggle:hover::before {
            opacity: 0.2;
        }

        .btn-sidebar-toggle:active {
            transform: scale(0.95);
        }

        .burger-line {
            display: block;
            width: 20px;
            height: 2px;
            background-color: var(--theme-text-primary);
            border-radius: var(--bncc-radius-full);
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce-md);
            transform-origin: center;
            position: relative;
            z-index: 1;
        }

        .burger-line:nth-child(1) { transform: translateY(-5px); }
        .burger-line:nth-child(3) { transform: translateY(5px); }

        /* Transform lines into X when sidebar is open */
        body.sidebar-is-active .burger-line:nth-child(1) {
            transform: translateY(0) rotate(45deg);
            background-color: var(--bncc-danger-500);
            width: 24px;
        }

        body.sidebar-is-active .burger-line:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }

        body.sidebar-is-active .burger-line:nth-child(3) {
            transform: translateY(0) rotate(-45deg);
            background-color: var(--bncc-danger-500);
            width: 24px;
        }
        
        body.sidebar-is-active .btn-sidebar-toggle {
            border-color: var(--bncc-danger-300);
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.2);
        }

        /* Brand Logo Container */
        .brand-identifier {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            transition: transform var(--bncc-duration-normal) var(--bncc-ease-bounce-sm);
            outline: none;
        }

        .brand-identifier:hover, .brand-identifier:focus-visible {
            transform: translateY(-2px);
        }

        .brand-icon-box {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--bncc-brand-500), var(--bncc-info-500));
            border-radius: var(--bncc-radius-xl);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #ffffff;
            font-size: 1.3rem;
            box-shadow: var(--bncc-shadow-md), var(--bncc-glow-primary-sm);
            position: relative;
            overflow: hidden;
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce-md);
        }

        .brand-identifier:hover .brand-icon-box {
            transform: rotate(-10deg) scale(1.05);
            box-shadow: var(--bncc-shadow-lg), var(--bncc-glow-primary-md);
            border-radius: var(--bncc-radius-lg);
        }

        /* Glint effect on hover */
        .brand-icon-box::after {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 50%;
            height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0), rgba(255,255,255,0.6), rgba(255,255,255,0));
            transform: skewX(-25deg);
            transition: all 0.7s ease;
        }

        .brand-identifier:hover .brand-icon-box::after {
            animation: shimmerEffect 1.5s infinite;
        }

        .brand-name-text {
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--theme-text-primary);
            letter-spacing: -0.03em;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .brand-name-accent {
            color: var(--bncc-brand-500);
            font-weight: 800;
            position: relative;
        }
        
        .brand-name-accent::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--bncc-brand-500);
            opacity: 0.2;
            border-radius: 2px;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform var(--bncc-duration-normal) var(--bncc-ease-out);
        }
        
        .brand-identifier:hover .brand-name-accent::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        /* -------------------------------------------------------------------------------------
           PART 8.2: HEADER RIGHT ZONE (ACTIONS & PROFILE)
           ------------------------------------------------------------------------------------- */
        .nav-zone-right {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        /* Standard Icon Action Button */
        .nav-action-btn {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 44px;
            height: 44px;
            border-radius: var(--bncc-radius-full);
            background-color: var(--theme-surface);
            border: 1px solid var(--theme-border);
            color: var(--theme-text-secondary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce-md);
            text-decoration: none;
            box-shadow: var(--bncc-shadow-xs);
        }

        .nav-action-btn:hover, .nav-action-btn:focus-visible {
            background-color: var(--theme-hover-bg);
            color: var(--bncc-brand-500);
            border-color: var(--bncc-brand-300);
            transform: translateY(-3px);
            box-shadow: var(--bncc-shadow-md);
        }

        .dark-theme .nav-action-btn:hover {
            border-color: var(--bncc-brand-700);
        }

        .nav-action-btn:active {
            transform: translateY(0) scale(0.95);
        }
        
        .nav-action-btn.is-active-state {
            background-color: var(--bncc-brand-50);
            color: var(--bncc-brand-600);
            border-color: var(--bncc-brand-300);
        }
        
        .dark-theme .nav-action-btn.is-active-state {
            background-color: rgba(99, 102, 241, 0.2);
            border-color: var(--bncc-brand-600);
        }

        /* Dynamic Theme Switcher Specifics */
        .theme-switcher-ui {
            overflow: hidden;
        }

        .icon-sun, .icon-moon {
            position: absolute;
            transition: all var(--bncc-duration-slow) var(--bncc-ease-bounce-lg);
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
        }

        /* Light Mode Default State */
        .icon-sun {
            opacity: 0;
            transform: translateY(30px) rotate(90deg) scale(0.5);
            color: var(--bncc-warning-500);
        }

        .icon-moon {
            opacity: 1;
            transform: translateY(0) rotate(0deg) scale(1);
        }

        /* Dark Mode Active State */
        .dark-theme .icon-sun {
            opacity: 1;
            transform: translateY(0) rotate(0deg) scale(1);
        }

        .dark-theme .icon-moon {
            opacity: 0;
            transform: translateY(-30px) rotate(-90deg) scale(0.5);
        }
        
        /* Action Button Badges (Unread counts) */
        .status-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: linear-gradient(135deg, var(--bncc-danger-400), var(--bncc-danger-600));
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 800;
            min-width: 22px;
            height: 22px;
            border-radius: var(--bncc-radius-full);
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid var(--theme-surface);
            padding: 0 4px;
            box-shadow: var(--bncc-shadow-sm);
            z-index: 2;
            transition: transform var(--bncc-duration-fast);
        }
        
        /* Animation class to be added via JS when new notif arrives */
        .bell-shake-anim {
            animation: bellShake 1s cubic-bezier(.36,.07,.19,.97) both;
        }
        .badge-pop-anim {
            animation: scaleIn 0.5s var(--bncc-ease-bounce-lg) forwards, pulseRing 2s infinite;
        }

        /* Authenticated User Micro-Profile (Pill) */
        .nav-profile-pill {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.35rem 0.5rem 0.35rem 1rem;
            border-radius: var(--bncc-radius-full);
            background-color: var(--theme-surface);
            border: 1px solid var(--theme-border);
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce-sm);
            cursor: pointer;
            text-decoration: none;
            box-shadow: var(--bncc-shadow-xs);
        }

        .nav-profile-pill:hover, .nav-profile-pill:focus-visible {
            background-color: var(--theme-hover-bg);
            border-color: var(--bncc-brand-400);
            box-shadow: var(--bncc-shadow-md);
            transform: translateY(-2px);
        }

        .dark-theme .nav-profile-pill:hover {
            border-color: var(--bncc-brand-600);
        }
        
        .nav-profile-pill:active {
            transform: translateY(0);
        }

        .nav-profile-meta {
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .nav-profile-name {
            font-size: var(--bncc-font-sm);
            font-weight: 700;
            color: var(--theme-text-primary);
            line-height: 1.2;
            max-width: 130px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-profile-role {
            font-size: 0.65rem;
            font-weight: 800;
            color: var(--bncc-brand-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--bncc-radius-full);
            object-fit: cover;
            border: 2px solid var(--bncc-brand-500);
            background-color: var(--theme-surface);
            padding: 2px;
            transition: border-color var(--bncc-duration-normal);
        }
        
        .nav-profile-pill:hover .nav-profile-avatar {
            border-color: var(--bncc-info-500);
        }

        /* Guest Authentication Button Group */
        .guest-auth-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-ghost-primary {
            padding: 0.6rem 1.25rem;
            border-radius: var(--bncc-radius-lg);
            font-weight: 700;
            font-size: var(--bncc-font-sm);
            color: var(--theme-text-primary);
            background-color: transparent;
            border: 1px solid var(--theme-border);
            transition: all var(--bncc-duration-fast) var(--bncc-ease-out);
            text-decoration: none;
            box-shadow: var(--bncc-shadow-sm);
        }

        .btn-ghost-primary:hover {
            background-color: var(--theme-hover-bg);
            border-color: var(--bncc-brand-500);
            color: var(--bncc-brand-600);
            transform: translateY(-2px);
            box-shadow: var(--bncc-shadow-md);
        }
        
        .dark-theme .btn-ghost-primary:hover {
            color: var(--bncc-brand-400);
        }

        .btn-solid-primary {
            padding: 0.6rem 1.5rem;
            border-radius: var(--bncc-radius-lg);
            font-weight: 700;
            font-size: var(--bncc-font-sm);
            color: #ffffff;
            background: linear-gradient(135deg, var(--bncc-brand-500), var(--bncc-brand-700));
            box-shadow: var(--bncc-shadow-md);
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce-sm);
            text-decoration: none;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-solid-primary::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-solid-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--bncc-shadow-lg), var(--bncc-glow-primary-md);
            background: linear-gradient(135deg, var(--bncc-brand-400), var(--bncc-brand-600));
        }
        
        .btn-solid-primary:hover::before {
            left: 100%;
        }

        /* -------------------------------------------------------------------------------------
           PART 9: ADVANCED NOTIFICATION SYSTEM (DROPDOWN)
           ------------------------------------------------------------------------------------- */
        .notif-wrapper-relative {
            position: relative;
        }

        .notif-dropdown-panel {
            position: absolute;
            top: calc(100% + 15px);
            right: -10px;
            width: 420px;
            max-width: calc(100vw - 20px);
            background-color: var(--theme-surface);
            border-radius: var(--bncc-radius-xl);
            box-shadow: var(--bncc-shadow-2xl), 0 0 0 1px var(--theme-border);
            display: flex;
            flex-direction: column;
            opacity: 0;
            visibility: hidden;
            transform: translateY(15px) scale(0.97);
            transform-origin: top right;
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce-md);
            z-index: var(--bncc-z-dropdown);
            overflow: hidden;
        }

        .notif-dropdown-panel.is-open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        /* Tail pointer for dropdown */
        .notif-dropdown-panel::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 25px;
            width: 12px;
            height: 12px;
            background-color: var(--bncc-brand-600);
            transform: rotate(45deg);
            z-index: 0;
        }

        .panel-header {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, var(--bncc-brand-500), var(--bncc-brand-700));
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .panel-title {
            font-size: var(--bncc-font-lg);
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .btn-mark-read {
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.4rem 0.8rem;
            border-radius: var(--bncc-radius-full);
            font-size: var(--bncc-font-xs);
            font-weight: 700;
            cursor: pointer;
            transition: all var(--bncc-duration-fast);
            backdrop-filter: blur(4px);
        }

        .btn-mark-read:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
            box-shadow: var(--bncc-shadow-sm);
        }

        .panel-body-scroll {
            max-height: 400px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            background-color: var(--theme-surface);
            position: relative;
            z-index: 1;
        }

        /* Custom Scrollbar for Notification Body */
        .panel-body-scroll::-webkit-scrollbar { width: 6px; }
        .panel-body-scroll::-webkit-scrollbar-track { background: transparent; }
        .panel-body-scroll::-webkit-scrollbar-thumb { background: var(--theme-border); border-radius: 10px; border: none; }
        .panel-body-scroll::-webkit-scrollbar-thumb:hover { background: var(--bncc-brand-400); }

        .notif-list-item {
            display: flex;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--theme-border);
            text-decoration: none;
            transition: background-color var(--bncc-duration-fast);
            position: relative;
        }

        .notif-list-item:hover {
            background-color: var(--theme-hover-bg);
        }

        /* Unread State Design */
        .notif-list-item.is-unread {
            background-color: var(--bncc-brand-50);
        }

        .dark-theme .notif-list-item.is-unread {
            background-color: rgba(99, 102, 241, 0.1);
        }

        .notif-list-item.is-unread::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            width: 4px;
            height: 0;
            background-color: var(--bncc-brand-500);
            border-radius: 0 4px 4px 0;
            transition: height 0.3s ease;
            animation: growLine 0.5s forwards 0.2s;
        }
        
        @keyframes growLine { to { height: 60%; } }

        .item-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: var(--bncc-radius-full);
            background-color: var(--theme-surface);
            border: 1px solid var(--theme-border);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.25rem;
            color: var(--bncc-brand-500);
            flex-shrink: 0;
            box-shadow: var(--bncc-shadow-sm);
            transition: all var(--bncc-duration-normal);
        }

        .is-unread .item-icon-wrapper {
            background: linear-gradient(135deg, var(--bncc-brand-400), var(--bncc-brand-600));
            color: #ffffff;
            border-color: transparent;
            box-shadow: var(--bncc-glow-primary-sm);
        }

        .item-text-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            flex-grow: 1;
        }

        .item-message {
            font-size: var(--bncc-font-sm);
            color: var(--theme-text-primary);
            line-height: 1.5;
        }

        .is-unread .item-message {
            font-weight: 700;
        }

        .item-time {
            font-size: var(--bncc-font-xs);
            color: var(--theme-text-tertiary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .is-unread .item-time {
            color: var(--bncc-brand-500);
        }

        /* Empty State inside Notification */
        .panel-empty-state {
            padding: 4rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--theme-text-tertiary);
            text-align: center;
        }

        .panel-empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.4;
            background: linear-gradient(to bottom, var(--theme-text-tertiary), transparent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .panel-empty-state span {
            font-weight: 600;
            font-size: var(--bncc-font-base);
        }

        .panel-footer {
            padding: 1rem;
            text-align: center;
            background-color: var(--theme-surface-alt);
            border-top: 1px solid var(--theme-border);
            position: relative;
            z-index: 1;
        }

        .btn-view-all {
            font-size: var(--bncc-font-sm);
            font-weight: 800;
            color: var(--bncc-brand-600);
            text-decoration: none;
            display: inline-block;
            transition: all var(--bncc-duration-fast);
        }
        
        .dark-theme .btn-view-all { color: var(--bncc-brand-400); }

        .btn-view-all:hover {
            color: var(--bncc-brand-700);
            transform: scale(1.05);
        }

        /* -------------------------------------------------------------------------------------
           PART 10: OFF-CANVAS SIDEBAR DRAWER (MAIN MENU)
           ------------------------------------------------------------------------------------- */
        
        /* Backdrop Overlay */
        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            z-index: var(--bncc-z-sidebar-overlay);
            opacity: 0;
            visibility: hidden;
            transition: opacity var(--bncc-duration-normal) var(--bncc-ease-linear), 
                        visibility var(--bncc-duration-normal) var(--bncc-ease-linear);
        }

        body.sidebar-is-active .drawer-overlay {
            opacity: 1;
            visibility: visible;
        }

        /* The Drawer Panel */
        .master-drawer-panel {
            position: fixed;
            top: 0;
            left: calc(-1 * var(--bncc-sidebar-width));
            width: var(--bncc-sidebar-width);
            max-width: 85vw; /* Responsive constraint */
            height: 100vh;
            height: 100dvh; /* Support for iOS Safari dynamic viewport */
            background-color: var(--theme-surface);
            z-index: var(--bncc-z-sidebar);
            display: flex;
            flex-direction: column;
            box-shadow: var(--bncc-shadow-2xl);
            transition: left var(--bncc-duration-slow) var(--bncc-ease-bounce-md);
            overflow: hidden;
        }

        .dark-theme .master-drawer-panel {
            border-right: 1px solid var(--theme-border);
            box-shadow: 10px 0 30px rgba(0,0,0,0.8);
        }

        body.sidebar-is-active .master-drawer-panel {
            left: 0;
        }

        /* Drawer Top Section (Profile Hero) */
        .drawer-hero-section {
            padding: 2.5rem 2rem 2rem 2rem;
            border-bottom: 1px solid var(--theme-border);
            position: relative;
            background: linear-gradient(180deg, var(--theme-hover-bg) 0%, var(--theme-surface) 100%);
            overflow: hidden;
        }
        
        /* Decorative Pattern in Hero */
        .drawer-hero-section::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 150px; height: 150px;
            background: radial-gradient(circle, var(--bncc-brand-500) 0%, transparent 70%);
            opacity: 0.1;
            transform: translate(30%, -30%);
            border-radius: 50%;
        }

        .btn-drawer-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 36px;
            height: 36px;
            border-radius: var(--bncc-radius-full);
            background-color: var(--theme-surface);
            border: 1px solid var(--theme-border);
            color: var(--theme-text-secondary);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all var(--bncc-duration-fast) var(--bncc-ease-bounce-sm);
            box-shadow: var(--bncc-shadow-sm);
            z-index: 2;
        }

        .btn-drawer-close:hover {
            background-color: var(--bncc-danger-100);
            color: var(--bncc-danger-600);
            border-color: var(--bncc-danger-200);
            transform: rotate(90deg) scale(1.1);
        }

        .dark-theme .btn-drawer-close:hover {
            background-color: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
        }

        .drawer-user-identity {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 1.25rem;
            position: relative;
            z-index: 1;
        }

        .drawer-avatar-hero {
            width: 80px;
            height: 80px;
            border-radius: var(--bncc-radius-2xl);
            object-fit: cover;
            border: 3px solid var(--bncc-brand-500);
            padding: 3px;
            background-color: var(--theme-surface);
            box-shadow: var(--bncc-shadow-md);
            transition: transform var(--bncc-duration-normal) var(--bncc-ease-bounce-sm);
        }
        
        .drawer-user-identity:hover .drawer-avatar-hero {
            transform: scale(1.05) rotate(5deg);
            border-color: var(--bncc-info-500);
        }

        .drawer-identity-text {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .drawer-name {
            font-size: var(--bncc-font-xl);
            font-weight: 900;
            color: var(--theme-text-primary);
            line-height: 1.2;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .drawer-role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.8rem;
            border-radius: var(--bncc-radius-md);
            font-size: var(--bncc-font-xs);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: fit-content;
            box-shadow: var(--bncc-shadow-xs);
        }

        /* Comprehensive Role Coloring System */
        .role-pill-admin { background: linear-gradient(135deg, var(--bncc-warning-100), #fef08a); color: var(--bncc-warning-700); border: 1px solid var(--bncc-warning-300); }
        .role-pill-teacher { background: linear-gradient(135deg, var(--bncc-danger-100), #fecaca); color: var(--bncc-danger-700); border: 1px solid var(--bncc-danger-300); }
        .role-pill-seller { background: linear-gradient(135deg, var(--bncc-success-100), #a7f3d0); color: var(--bncc-success-700); border: 1px solid var(--bncc-success-300); }
        .role-pill-buyer { background: linear-gradient(135deg, var(--bncc-brand-100), #c7d2fe); color: var(--bncc-brand-700); border: 1px solid var(--bncc-brand-300); }
        .role-pill-guest { background: var(--theme-surface-alt); color: var(--theme-text-secondary); border: 1px solid var(--theme-border-focus); }

        .dark-theme .role-pill-admin { background: rgba(245, 158, 11, 0.15); color: var(--bncc-warning-400); border-color: rgba(245, 158, 11, 0.3); }
        .dark-theme .role-pill-teacher { background: rgba(239, 68, 68, 0.15); color: var(--bncc-danger-400); border-color: rgba(239, 68, 68, 0.3); }
        .dark-theme .role-pill-seller { background: rgba(16, 185, 129, 0.15); color: var(--bncc-success-400); border-color: rgba(16, 185, 129, 0.3); }
        .dark-theme .role-pill-buyer { background: rgba(99, 102, 241, 0.15); color: var(--bncc-brand-400); border-color: rgba(99, 102, 241, 0.3); }
        .dark-theme .role-pill-guest { background: rgba(255, 255, 255, 0.05); color: var(--theme-text-tertiary); border-color: var(--theme-border); }

        /* Drawer Middle Section: Navigation Scroll Area */
        .drawer-nav-viewport {
            flex-grow: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1.5rem 1.25rem 2.5rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .drawer-nav-viewport::-webkit-scrollbar { width: 6px; }
        .drawer-nav-viewport::-webkit-scrollbar-track { background: transparent; }
        .drawer-nav-viewport::-webkit-scrollbar-thumb { background: var(--theme-border); border-radius: 10px; border: none; }

        .drawer-section-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--theme-text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            padding: 1rem 1rem 0.5rem 1rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .drawer-section-label::after {
            content: '';
            flex-grow: 1;
            height: 1px;
            background: linear-gradient(to right, var(--theme-border), transparent);
        }

        .drawer-menu-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.2rem;
            border-radius: var(--bncc-radius-lg);
            text-decoration: none;
            color: var(--theme-text-secondary);
            font-weight: 600;
            font-size: var(--bncc-font-sm);
            transition: all var(--bncc-duration-fast) var(--bncc-ease-out);
            position: relative;
            overflow: hidden;
            border: 1px solid transparent;
        }

        /* Active Indicator Line */
        .drawer-menu-link::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 4px;
            height: 0%;
            background-color: var(--bncc-brand-500);
            transform: translateY(-50%);
            transition: height var(--bncc-duration-normal) var(--bncc-ease-bounce-sm);
            border-radius: 0 4px 4px 0;
        }

        .drawer-menu-link:hover {
            background: var(--theme-hover-bg);
            color: var(--theme-text-primary);
            transform: translateX(5px);
            border-color: var(--theme-border);
        }

        /* The Active State (Current Page) */
        .drawer-menu-link.is-current-page {
            background-color: var(--bncc-brand-50);
            color: var(--bncc-brand-700);
            border-color: var(--bncc-brand-200);
            box-shadow: var(--bncc-shadow-sm);
        }

        .dark-theme .drawer-menu-link.is-current-page {
            background-color: rgba(99, 102, 241, 0.15);
            color: var(--bncc-brand-300);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .drawer-menu-link.is-current-page::before {
            height: 60%;
        }

        .drawer-link-icon {
            width: 24px;
            font-size: 1.25rem;
            text-align: center;
            transition: transform var(--bncc-duration-normal) var(--bncc-ease-bounce-sm), color var(--bncc-duration-normal);
        }

        .drawer-menu-link:hover .drawer-link-icon {
            transform: scale(1.15) rotate(5deg);
            color: var(--bncc-brand-500);
        }

        .drawer-menu-link.is-current-page .drawer-link-icon {
            color: var(--bncc-brand-500);
        }

        /* Specific Contextual Color Formatting for Links */
        .link-danger:hover .drawer-link-icon, .link-danger.is-current-page .drawer-link-icon { color: var(--bncc-danger-500); }
        .link-success:hover .drawer-link-icon, .link-success.is-current-page .drawer-link-icon { color: var(--bncc-success-500); }
        .link-warning:hover .drawer-link-icon, .link-warning.is-current-page .drawer-link-icon { color: var(--bncc-warning-500); }
        .link-info:hover .drawer-link-icon, .link-info.is-current-page .drawer-link-icon { color: var(--bncc-info-500); }

        /* Admin Special Area Styling */
        .link-admin-zone {
            background-color: rgba(239, 68, 68, 0.03);
            border: 1px dashed rgba(239, 68, 68, 0.15);
            margin-bottom: 0.25rem;
        }
        
        .link-admin-zone:hover {
            background-color: rgba(239, 68, 68, 0.08);
            border-style: solid;
            border-color: rgba(239, 68, 68, 0.3);
            color: var(--bncc-danger-600);
        }
        
        .dark-theme .link-admin-zone:hover { color: var(--bncc-danger-400); }

        .inline-status-badge {
            margin-left: auto;
            background: linear-gradient(135deg, var(--bncc-danger-500), var(--bncc-danger-700));
            color: white;
            padding: 2px 8px;
            border-radius: var(--bncc-radius-pill);
            font-size: 0.7rem;
            font-weight: 800;
            box-shadow: var(--bncc-shadow-sm);
            animation: pulseRing 2s infinite;
        }

        /* Drawer Bottom Section (Logout) */
        .drawer-footer-section {
            padding: 1.5rem;
            border-top: 1px solid var(--theme-border);
            background-color: var(--theme-surface-alt);
            position: relative;
            z-index: 1;
        }

        .btn-action-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 1.1rem;
            border-radius: var(--bncc-radius-lg);
            background-color: var(--bncc-danger-50);
            color: var(--bncc-danger-600);
            font-weight: 800;
            font-size: var(--bncc-font-base);
            text-decoration: none;
            border: 1px solid var(--bncc-danger-200);
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce-sm);
            box-shadow: var(--bncc-shadow-xs);
            overflow: hidden;
            position: relative;
        }

        .dark-theme .btn-action-logout {
            background-color: rgba(239, 68, 68, 0.08);
            border-color: rgba(239, 68, 68, 0.2);
            color: var(--bncc-danger-400);
        }

        .btn-action-logout::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, var(--bncc-danger-500), var(--bncc-danger-700));
            opacity: 0;
            transition: opacity var(--bncc-duration-normal);
            z-index: 0;
        }

        .btn-action-logout span, .btn-action-logout i {
            position: relative;
            z-index: 1;
            transition: color var(--bncc-duration-normal);
        }

        .btn-action-logout:hover {
            border-color: var(--bncc-danger-600);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(239, 68, 68, 0.4);
        }
        
        .btn-action-logout:hover::before {
            opacity: 1;
        }
        
        .btn-action-logout:hover span, .btn-action-logout:hover i {
            color: #ffffff;
        }

        /* -------------------------------------------------------------------------------------
           PART 11: RESPONSIVE MEDIA QUERIES
           ------------------------------------------------------------------------------------- */
        
        /* Tablet & Small Laptops (Max 1024px) */
        @media screen and (max-width: 1024px) {
            .header-layout-container { padding: 0 1.5rem; }
            .brand-name-text { font-size: 1.4rem; }
        }

        /* Tablets & Large Phones (Max 768px) */
        @media screen and (max-width: 768px) {
            :root {
                --bncc-header-height: 70px;
                --bncc-header-height-scrolled: 64px;
            }
            
            /* Hide user name and role to save space */
            .nav-profile-name, .nav-profile-role { display: none; }
            
            .nav-profile-pill { 
                padding: 0; 
                background: transparent; 
                border: none; 
                box-shadow: none; 
            }
            
            .nav-profile-pill:hover { 
                background: transparent; 
                box-shadow: none; 
                transform: scale(1.05); 
            }
            
            /* Responsive Notification Dropdown (Full width on mobile) */
            .notif-dropdown-panel {
                position: fixed;
                top: var(--bncc-header-height);
                right: 15px;
                left: 15px;
                width: auto;
                max-width: none;
                transform-origin: top center;
            }
            
            .notif-dropdown-panel::before { display: none; } /* Hide arrow tail on mobile */
            
            /* Adjust Auth Buttons */
            .guest-auth-group { gap: 0.5rem; }
            .btn-ghost-primary { padding: 0.5rem 0.75rem; font-size: var(--bncc-font-xs); }
            .btn-solid-primary { padding: 0.5rem 1rem; font-size: var(--bncc-font-xs); }
        }

        /* Small Phones (Max 480px) */
        @media screen and (max-width: 480px) {
            .brand-name-text { display: none; } /* Show only icon */
            .nav-zone-left { gap: 0.75rem; }
            .nav-zone-right { gap: 0.5rem; }
            .header-icon-btn { width: 40px; height: 40px; font-size: 1.1rem; }
            .master-drawer-panel { width: 88vw; }
            .drawer-hero-section { padding: 2rem 1.5rem 1.5rem 1.5rem; }
            .drawer-avatar-hero { width: 65px; height: 65px; }
            .drawer-name { font-size: var(--bncc-font-lg); }
        }
        
        /* Utilites generated dynamically */
        .visually-hidden {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }

    </style>
</head>
<body id="bnccBodyElement">

    <div id="sysPreloader" class="bncc-sys-preloader" aria-hidden="true">
        <div class="sys-preloader-spinner">
            <div class="sys-preloader-circle"></div>
            <div class="sys-preloader-spin"></div>
        </div>
        <div class="sys-preloader-brand">BNCC <span>MARKET</span></div>
    </div>

    <div class="bg-aesthetic-container" aria-hidden="true">
        <div class="bg-grid-pattern"></div>
        <div class="bg-orb bg-orb-1"></div>
        <div class="bg-orb bg-orb-2"></div>
        <div class="bg-orb bg-orb-3"></div>
    </div>

    <header id="sysNavbar" class="header-master-wrapper" role="banner">
        
        <div id="sysProgressBar" class="nav-progress-indicator" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>

        <div class="header-layout-container">
            
            <div class="nav-zone-left">
                
                <button id="sysBtnSidebar" class="btn-sidebar-toggle" aria-label="Open Navigation Menu" aria-expanded="false" aria-controls="sysSidebarDrawer">
                    <span class="burger-line"></span>
                    <span class="burger-line"></span>
                    <span class="burger-line"></span>
                </button>

                <a href="<?= $base_path_assets ?>pages/index.php" class="brand-identifier" aria-label="Go to Homepage">
                    <div class="brand-icon-box">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h1 class="brand-name-text">BNCC<span class="brand-name-accent">Market</span></h1>
                </a>
                
            </div>

            <div class="nav-zone-right">
                
                <button id="sysBtnTheme" class="nav-action-btn theme-switcher-ui" aria-label="Toggle Color Theme" title="สลับโหมดมืด/สว่าง">
                    <i class="fas fa-sun icon-sun" aria-hidden="true"></i>
                    <i class="fas fa-moon icon-moon" aria-hidden="true"></i>
                </button>

                <?php if (isLoggedIn()): ?>
                    
                    <div class="notif-wrapper-relative">
                        
                        <button id="sysBtnNotif" class="nav-action-btn" aria-label="Open Notifications" aria-haspopup="true" aria-expanded="false" title="การแจ้งเตือน">
                            <i class="fas fa-bell"></i>
                            <span id="sysBadgeNotif" class="status-badge" style="display: none;">0</span>
                        </button>

                        <div id="sysPanelNotif" class="notif-dropdown-panel" role="menu" aria-label="Notifications Menu">
                            
                            <div class="panel-header">
                                <h3 class="panel-title"><i class="fas fa-bolt"></i> อัปเดตล่าสุด</h3>
                                <button id="sysBtnMarkRead" class="btn-mark-read" aria-label="Mark all as read">อ่านทั้งหมด</button>
                            </div>
                            
                            <div id="sysListNotif" class="panel-body-scroll" role="list">
                                <div class="panel-empty-state">
                                    <i class="fas fa-circle-notch fa-spin"></i>
                                    <span>กำลังเชื่อมต่อเซิร์ฟเวอร์...</span>
                                </div>
                            </div>

                            <div class="panel-footer">
                                <a href="#" class="btn-view-all">ดูประวัติการแจ้งเตือนทั้งหมด <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                            
                        </div>
                    </div>

                    <a href="<?= $base_path_assets ?>pages/chat.php" class="nav-action-btn" aria-label="Open Messages" title="ข้อความแชท">
                        <i class="fas fa-comment-dots"></i>
                        <?php if($unread_msg_count > 0): ?>
                            <span class="status-badge"><?= $unread_msg_count > 99 ? '99+' : $unread_msg_count ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="<?= $base_path_assets ?>pages/profile.php" class="nav-profile-pill" aria-label="Go to your Profile" title="บัญชีของฉัน">
                        <div class="nav-profile-meta">
                            <span class="nav-profile-name"><?= htmlspecialchars($_SESSION['fullname']) ?></span>
                            <?php
                                $role_display = 'ผู้ใช้ทั่วไป';
                                if($_SESSION['role'] == 'admin') $role_display = 'ผู้ดูแลระบบ';
                                if($_SESSION['role'] == 'teacher') $role_display = 'อาจารย์';
                                if($_SESSION['role'] == 'seller') $role_display = 'ร้านค้า';
                            ?>
                            <span class="nav-profile-role"><?= $role_display ?></span>
                        </div>
                        <img src="<?= $user_avatar ?>" alt="Avatar" class="header-user-avatar" loading="lazy">
                    </a>

                <?php else: ?>
                    
                    <?php if (!in_array($current_page, $hide_auth_list)): ?>
                        <div class="guest-auth-group">
                            <a href="<?= $base_path_assets ?>auth/login.php" class="btn-ghost-primary">เข้าสู่ระบบ</a>
                            <a href="<?= $base_path_assets ?>auth/register.php" class="btn-solid-primary">สมัครสมาชิก</a>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </div>
    </header>

    <div id="sysSidebarOverlay" class="drawer-overlay" aria-hidden="true" tabindex="-1"></div>

    <aside id="sysSidebarDrawer" class="master-drawer-panel" aria-hidden="true" aria-label="Main Sidebar Navigation" tabindex="-1">
        
        <div class="drawer-hero-section">
            
            <button id="sysBtnCloseSidebar" class="btn-drawer-close" aria-label="Close Navigation Menu">
                <i class="fas fa-times"></i>
            </button>

            <?php if (isLoggedIn()): ?>
                <div class="drawer-user-identity">
                    <img src="<?= $user_avatar ?>" alt="Large Profile Avatar" class="drawer-avatar-hero" loading="lazy">
                    <div class="drawer-identity-text">
                        <h2 class="drawer-name"><?= htmlspecialchars($_SESSION['fullname']) ?></h2>
                        
                        <?php 
                            // Role Badge Contextual Setup
                            $drawer_role_class = 'role-pill-guest';
                            $drawer_role_text = 'ผู้ใช้ทั่วไป';
                            $drawer_role_icon = 'fa-user';
                            
                            switch($_SESSION['role']) {
                                case 'admin':
                                    $drawer_role_class = 'role-pill-admin';
                                    $drawer_role_text = 'ผู้ดูแลระบบ (Admin)';
                                    $drawer_role_icon = 'fa-crown';
                                    break;
                                case 'teacher':
                                    $drawer_role_class = 'role-pill-teacher';
                                    $drawer_role_text = 'อาจารย์ (Master)';
                                    $drawer_role_icon = 'fa-chalkboard-teacher';
                                    break;
                                case 'seller':
                                    $drawer_role_class = 'role-pill-seller';
                                    $drawer_role_text = 'ผู้ขาย (Seller)';
                                    $drawer_role_icon = 'fa-store';
                                    break;
                                case 'buyer':
                                    $drawer_role_class = 'role-pill-buyer';
                                    $drawer_role_text = 'สมาชิกผู้ซื้อ';
                                    $drawer_role_icon = 'fa-shopping-bag';
                                    break;
                            }
                        ?>
                        <span class="drawer-role-badge <?= $drawer_role_class ?>">
                            <i class="fas <?= $drawer_role_icon ?>"></i> <?= $drawer_role_text ?>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div class="drawer-user-identity">
                    <div class="drawer-avatar-hero" style="display:flex; justify-content:center; align-items:center; border-color:var(--theme-border-focus); background:var(--theme-surface-alt);">
                        <i class="fas fa-user-secret fa-3x" style="color:var(--theme-text-tertiary);"></i>
                    </div>
                    <div class="drawer-identity-text">
                        <h2 class="drawer-name">บุคคลทั่วไป</h2>
                        <span class="drawer-role-badge role-pill-guest">
                            <i class="fas fa-eye"></i> โหมดผู้เยี่ยมชม
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <nav class="drawer-nav-viewport" aria-label="Sidebar Menu Categories">
            
            <div class="drawer-section-label"><i class="fas fa-compass"></i> สำรวจตลาดกลาง</div>
            
            <a href="<?= $base_path_assets ?>pages/index.php" class="drawer-menu-link link-info <?= $current_page == 'index.php' ? 'is-current-page' : '' ?>">
                <i class="fas fa-store drawer-link-icon"></i>
                <span>หน้าหลัก Marketplace</span>
            </a>
            
            <a href="<?= $base_path_assets ?>pages/wtb_board.php" class="drawer-menu-link link-warning <?= $current_page == 'wtb_board.php' ? 'is-current-page' : '' ?>">
                <i class="fas fa-bullhorn drawer-link-icon"></i>
                <span>บอร์ดตามหาของ (WTB)</span>
            </a>

            <?php if (isLoggedIn()): ?>
                
                <div class="drawer-section-label"><i class="fas fa-user-circle"></i> พื้นที่ส่วนตัว</div>
                
                <a href="<?= $base_path_assets ?>pages/profile.php" class="drawer-menu-link <?= $current_page == 'profile.php' ? 'is-current-page' : '' ?>">
                    <i class="fas fa-id-card drawer-link-icon"></i>
                    <span>จัดการบัญชีผู้ใช้</span>
                </a>
                
                <a href="<?= $base_path_assets ?>pages/wishlist.php" class="drawer-menu-link link-danger <?= $current_page == 'wishlist.php' ? 'is-current-page' : '' ?>">
                    <i class="fas fa-heart drawer-link-icon"></i>
                    <span>รายการที่ถูกใจ (Wishlist)</span>
                </a>
                
                <a href="<?= $base_path_assets ?>pages/my_orders.php" class="drawer-menu-link link-success <?= $current_page == 'my_orders.php' ? 'is-current-page' : '' ?>">
                    <i class="fas fa-shopping-basket drawer-link-icon"></i>
                    <span>ประวัติการสั่งซื้อ</span>
                </a>

                <?php if ($_SESSION['role'] === 'seller'): ?>
                    <div class="drawer-section-label"><i class="fas fa-briefcase"></i> เครื่องมือผู้ขาย</div>
                    <a href="<?= $base_path_assets ?>seller/dashboard.php" class="drawer-menu-link link-success">
                        <i class="fas fa-chart-line drawer-link-icon"></i>
                        <span>แดชบอร์ดร้านค้า</span>
                    </a>
                <?php else: ?>
                    <div class="drawer-section-label"><i class="fas fa-rocket"></i> เริ่มต้นธุรกิจ</div>
                    <a href="<?= $base_path_assets ?>auth/register_seller.php" class="drawer-menu-link">
                        <i class="fas fa-store-alt drawer-link-icon"></i>
                        <span>ลงทะเบียนเปิดร้านค้า</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                    <div class="drawer-section-label"><i class="fas fa-shield-alt"></i> การจัดการระบบ</div>
                    <a href="<?= $base_path_assets ?>admin/admin_dashboard.php" class="drawer-menu-link link-admin-zone">
                        <i class="fas fa-server drawer-link-icon"></i>
                        <span>แผงควบคุมระบบ (Admin)</span>
                    </a>
                    <a href="<?= $base_path_assets ?>admin/approve_product.php" class="drawer-menu-link link-admin-zone">
                        <i class="fas fa-box-check drawer-link-icon"></i>
                        <span>อนุมัติสินค้าใหม่</span>
                    </a>
                    
                    <a href="<?= $base_path_assets ?>admin/approve_shop.php" class="drawer-menu-link link-admin-zone <?= $current_page == 'approve_shop.php' ? 'is-current-page' : '' ?>">
                        <i class="fas fa-store-slash drawer-link-icon"></i>
                        <span>อนุมัติคำร้องเปิดร้าน</span>
                        <?php 
                            // Query to get pending shops count
                            try {
                                $shop_query = "SELECT COUNT(id) FROM shops WHERE status = 'pending'";
                                $pending_shop_count = $db->query($shop_query)->fetchColumn();
                                if ($pending_shop_count > 0) {
                                    echo '<span class="inline-status-badge">' . $pending_shop_count . '</span>';
                                }
                            } catch (Exception $e) {}
                        ?>
                    </a>
                <?php endif; ?>

            <?php else: ?>
                
                <div class="drawer-section-label"><i class="fas fa-key"></i> เข้าถึงระบบ</div>
                
                <a href="<?= $base_path_assets ?>auth/login.php" class="drawer-menu-link">
                    <i class="fas fa-sign-in-alt drawer-link-icon"></i>
                    <span>ลงชื่อเข้าใช้งาน</span>
                </a>
                
                <a href="<?= $base_path_assets ?>auth/register.php" class="drawer-menu-link link-info">
                    <i class="fas fa-user-plus drawer-link-icon"></i>
                    <span>สมัครสมาชิกใหม่</span>
                </a>
                
            <?php endif; ?>
            
        </nav>

        <?php if (isLoggedIn()): ?>
        <div class="drawer-footer-section">
            <a href="<?= $base_path_assets ?>auth/logout.php" class="btn-action-logout">
                <i class="fas fa-power-off"></i>
                <span>ออกจากระบบอย่างปลอดภัย</span>
            </a>
        </div>
        <?php endif; ?>

    </aside>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        /**
         * GLOBAL CONFIGURATION
         */
        const CONFIG = {
            scrollThreshold: 30,
            ajaxBasePath: '<?= $base_path_assets ?>ajax/',
            pollInterval: 45000, // Reduced server load (45s)
            themeStorageKey: 'bncc_enterprise_theme'
        };

        /**
         * MODULE 1: PRELOADER MANAGER
         * Controls the initial loading screen fade out
         */
        const Preloader = (function() {
            const el = document.getElementById('sysPreloader');
            const body = document.getElementById('bnccBodyElement');
            
            function hide() {
                if(!el) return;
                // Add minor delay for aesthetic purposes
                setTimeout(() => {
                    el.classList.add('is-hidden');
                    body.classList.remove('noscroll');
                    // Remove from DOM to save memory after transition completes
                    setTimeout(() => { if(el.parentNode) el.parentNode.removeChild(el); }, 1000);
                }, 400);
            }

            return {
                init: function() {
                    if(!el) return;
                    body.classList.add('noscroll');
                    // Primary trigger
                    window.addEventListener('load', hide);
                    // Failsafe trigger (3 seconds max)
                    setTimeout(hide, 3000);
                }
            };
        })();

        /**
         * MODULE 2: SCROLL ENGINE
         * Handles Navbar Glassmorphism State and Progress Bar
         */
        const ScrollEngine = (function() {
            const nav = document.getElementById('sysNavbar');
            const prog = document.getElementById('sysProgressBar');
            let isTicking = false;

            function onScroll() {
                const y = window.scrollY || window.pageYOffset;
                
                // Navbar State
                if(nav) {
                    if (y > CONFIG.scrollThreshold) {
                        nav.classList.add('is-scrolled');
                    } else {
                        nav.classList.remove('is-scrolled');
                    }
                }

                // Progress Bar
                if(prog) {
                    const docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                    if(docHeight > 0) {
                        const scrolled = (y / docHeight) * 100;
                        prog.style.width = scrolled + '%';
                    }
                }
                
                isTicking = false;
            }

            return {
                init: function() {
                    window.addEventListener('scroll', () => {
                        if (!isTicking) {
                            window.requestAnimationFrame(onScroll);
                            isTicking = true;
                        }
                    }, { passive: true });
                    // Initial run
                    onScroll();
                }
            };
        })();

        /**
         * MODULE 3: THEME CONTROLLER
         * Manages Dark/Light mode switching and persistence
         */
        const ThemeManager = (function() {
            const btn = document.getElementById('sysBtnTheme');
            const html = document.documentElement;
            const metaTheme = document.getElementById('meta-theme-color');

            function updateMetaColor(isDark) {
                if(!metaTheme) return;
                metaTheme.setAttribute('content', isDark ? '#111827' : '#ffffff');
            }

            function toggleTheme() {
                const isDark = html.classList.toggle('dark-theme');
                const newTheme = isDark ? 'dark' : 'light';
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem(CONFIG.themeStorageKey, newTheme);
                updateMetaColor(isDark);
                
                // Visual feedback on button
                if(btn) {
                    btn.style.transform = 'scale(0.9)';
                    setTimeout(() => btn.style.transform = 'scale(1)', 150);
                }
            }

            return {
                init: function() {
                    if(!btn) return;
                    btn.addEventListener('click', toggleTheme);
                    
                    // Sync Meta Color on init
                    updateMetaColor(html.classList.contains('dark-theme'));
                }
            };
        })();

        /**
         * MODULE 4: DRAWER (SIDEBAR) CONTROLLER
         * Off-canvas menu mechanics and accessibility
         */
        const DrawerController = (function() {
            const btnToggle = document.getElementById('sysBtnSidebar');
            const btnClose = document.getElementById('sysBtnCloseSidebar');
            const drawer = document.getElementById('sysSidebarDrawer');
            const overlay = document.getElementById('sysSidebarOverlay');
            const body = document.getElementById('bnccBodyElement');
            let isOpen = false;

            function open() {
                isOpen = true;
                body.classList.add('sidebar-is-active');
                if(drawer) {
                    drawer.setAttribute('aria-hidden', 'false');
                    // Focus management for accessibility
                    setTimeout(() => drawer.focus(), 100);
                }
                if(overlay) overlay.setAttribute('aria-hidden', 'false');
                if(btnToggle) btnToggle.setAttribute('aria-expanded', 'true');
            }

            function close() {
                isOpen = false;
                body.classList.remove('sidebar-is-active');
                if(drawer) drawer.setAttribute('aria-hidden', 'true');
                if(overlay) overlay.setAttribute('aria-hidden', 'true');
                if(btnToggle) {
                    btnToggle.setAttribute('aria-expanded', 'false');
                    btnToggle.focus(); // Return focus
                }
            }

            function toggle(e) {
                if(e) e.stopPropagation();
                isOpen ? close() : open();
            }

            return {
                init: function() {
                    if (!btnToggle || !drawer || !overlay) return;

                    btnToggle.addEventListener('click', toggle);
                    if(btnClose) btnClose.addEventListener('click', close);
                    overlay.addEventListener('click', close);

                    // Global Keyboard Hook
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && isOpen) close();
                    });
                },
                getState: () => isOpen
            };
        })();

        /**
         * MODULE 5: AJAX NOTIFICATION ENGINE
         * Real-time polling and UI updating for notifications
         */
        <?php if(isLoggedIn()): ?>
        const NotificationEngine = (function() {
            const btnNotif = document.getElementById('sysBtnNotif');
            const panel = document.getElementById('sysPanelNotif');
            const list = document.getElementById('sysListNotif');
            const badge = document.getElementById('sysBadgeNotif');
            const btnMarkRead = document.getElementById('sysBtnMarkRead');
            
            let isOpen = false;
            let pollTimer = null;
            let currentUnread = 0;

            function togglePanel(e) {
                if(e) e.stopPropagation();
                isOpen = !isOpen;
                
                if (isOpen) {
                    panel.classList.add('is-open');
                    btnNotif.classList.add('is-active-state');
                    btnNotif.setAttribute('aria-expanded', 'true');
                    fetchData(); // Always refresh on open
                } else {
                    closePanel();
                }
            }

            function closePanel() {
                isOpen = false;
                panel.classList.remove('is-open');
                btnNotif.classList.remove('is-active-state');
                btnNotif.setAttribute('aria-expanded', 'false');
            }

            async function fetchData() {
                try {
                    const response = await fetch(CONFIG.ajaxBasePath + 'notifications_api.php?action=fetch', {
                        method: 'GET',
                        headers: { 
                            'Cache-Control': 'no-cache', 
                            'X-Requested-With': 'XMLHttpRequest' 
                        }
                    });
                    
                    if (!response.ok) throw new Error('HTTP Error: ' + response.status);
                    
                    const data = await response.json();
                    if(data.status === 'success') {
                        processData(data);
                    }
                } catch (error) {
                    console.error("Notification API Failed:", error);
                    renderError();
                }
            }

            function processData(data) {
                // Update Badge UI & Animate if new notifications arrived
                if (data.unread_count > 0) {
                    badge.style.display = 'flex';
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    
                    // Trigger bell shake animation if count increased
                    if (data.unread_count > currentUnread) {
                        const icon = btnNotif.querySelector('i');
                        icon.classList.remove('bell-shake-anim');
                        void icon.offsetWidth; // Trigger reflow
                        icon.classList.add('bell-shake-anim');
                        
                        badge.classList.remove('badge-pop-anim');
                        void badge.offsetWidth;
                        badge.classList.add('badge-pop-anim');
                    }
                } else {
                    badge.style.display = 'none';
                }
                
                currentUnread = data.unread_count;

                // Update List UI (Only if panel is open or empty to save DOM paint)
                if (!isOpen && list.children.length > 0 && !list.innerHTML.includes('fa-spin')) return;

                if (data.notifications && data.notifications.length > 0) {
                    renderList(data.notifications);
                } else {
                    renderEmpty();
                }
            }

            function renderList(notifs) {
                let html = '';
                notifs.forEach(n => {
                    const statusClass = n.is_read == 0 ? 'is-unread' : '';
                    // Generate smart icon based on message content if no icon provided
                    let smartIcon = n.icon || '<i class="fas fa-bell"></i>';
                    if(!n.icon) {
                        if(n.message.includes('อนุมัติ')) smartIcon = '<i class="fas fa-check-circle"></i>';
                        else if(n.message.includes('ปฏิเสธ') || n.message.includes('ลบ')) smartIcon = '<i class="fas fa-times-circle text-danger"></i>';
                        else if(n.message.includes('สั่งซื้อ')) smartIcon = '<i class="fas fa-shopping-bag"></i>';
                        else if(n.message.includes('ร้านค้า')) smartIcon = '<i class="fas fa-store"></i>';
                    }
                    
                    html += `
                        <a href="${n.link || '#'}" class="notif-list-item ${statusClass}">
                            <div class="item-icon-wrapper">${smartIcon}</div>
                            <div class="item-text-wrapper">
                                <span class="item-message">${n.message}</span>
                                <span class="item-time"><i class="far fa-clock"></i> ${n.time || 'เมื่อสักครู่'}</span>
                            </div>
                        </a>
                    `;
                });
                list.innerHTML = html;
            }

            function renderEmpty() {
                list.innerHTML = `
                    <div class="panel-empty-state">
                        <i class="fas fa-inbox box-empty"></i>
                        <span>ไม่มีการแจ้งเตือนใหม่ในขณะนี้</span>
                    </div>
                `;
            }

            function renderError() {
                if(list.innerHTML.includes('fa-spin')) {
                    list.innerHTML = `
                        <div class="panel-empty-state" style="color: var(--bncc-danger-500);">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>เกิดข้อผิดพลาดในการโหลดข้อมูล</span>
                            <button onclick="window.location.reload()" style="margin-top:10px; padding:5px 15px; border-radius:20px; border:1px solid currentColor; background:transparent; color:inherit; cursor:pointer;">รีเฟรชหน้าเว็บ</button>
                        </div>
                    `;
                }
            }

            async function markAllRead() {
                try {
                    // Optimistic UI Update (Immediate feedback)
                    badge.style.display = 'none';
                    currentUnread = 0;
                    
                    const unreadItems = list.querySelectorAll('.is-unread');
                    unreadItems.forEach(el => el.classList.remove('is-unread'));

                    // Button loading state
                    const originalText = btnMarkRead.innerHTML;
                    btnMarkRead.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    // Server Request
                    const formData = new URLSearchParams();
                    formData.append('action', 'mark_read');

                    await fetch(CONFIG.ajaxBasePath + 'notifications_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });
                    
                    // Reset button
                    btnMarkRead.innerHTML = originalText;
                    
                } catch (err) {
                    console.error("Failed to mark read:", err);
                    fetchData(); // Rollback UI if failed
                }
            }

            return {
                init: function() {
                    if (!btnNotif || !panel) return;

                    // Bind Events
                    btnNotif.addEventListener('click', togglePanel);
                    
                    if (btnMarkRead) {
                        btnMarkRead.addEventListener('click', (e) => {
                            e.stopPropagation();
                            markAllRead();
                        });
                    }

                    // Click outside listener
                    document.addEventListener('click', (e) => {
                        if (isOpen && !panel.contains(e.target) && !btnNotif.contains(e.target)) {
                            closePanel();
                        }
                    });

                    // Initial boot and polling setup
                    fetchData();
                    pollTimer = setInterval(fetchData, CONFIG.pollInterval);
                }
            };
        })();
        <?php endif; ?>

        /**
         * SYSTEM BOOTSTRAP
         * Initialize all modules safely
         */
        try {
            Preloader.init();
            ScrollEngine.init();
            ThemeManager.init();
            DrawerController.init();
            <?php if(isLoggedIn()): ?>
            NotificationEngine.init();
            <?php endif; ?>
            console.log("%c BNCC Enterprise Engine Loaded Successfully", "color: #10b981; font-weight: bold; font-size: 14px;");
        } catch (e) {
            console.error("Critical Engine Failure during Bootstrap:", e);
        }

    });
    </script>

    <main id="bnccMainWorkspace" class="master-main-workspace" style="padding-top: calc(var(--bncc-header-height) + 2rem); min-height: calc(100vh - var(--bncc-header-height)); position: relative; z-index: var(--bncc-z-base); padding-bottom: 4rem;">