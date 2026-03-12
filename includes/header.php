<?php
/**
 * ============================================================================================
 * BNCC MARKETPLACE - ENTERPRISE MASTER HEADER SYSTEM (V 3.0.3 - THE ULTIMATE PATH FIX)
 * ============================================================================================
 * Architecture: Model-View-Controller (Frontend Bound)
 * Engine: PHP 8.x + Native Vanilla JS + CSS3 Advanced Variables (Mini-Tailwind Core)
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
// 2. PATH RESOLUTION ENGINE (🎯 THE 404 FIXER)
// --------------------------------------------------------------------------------------------
// ปัญหาคือใน functions.php พี่ตั้ง BASE_URL ไว้ผิด (ตกโฟลเดอร์ student_marketplace)
// ถ้าเราใช้ if(!defined) มันจะไปดึงตัวที่ผิดมาใช้ ทำให้พังหมด
// วิธีแก้: เราทำการ Hardcode (บังคับ) ค่าที่ถูกต้อง 100% ลงไปเลย ไม่ต้องสน BASE_URL เดิม
// $base_path = '/s673190104/student_marketplace/';
// 🎯 FIXED: ใช้ BASE_URL จาก functions.php เพื่อให้พาร์ทถูกต้อง 100% ทุกลิงก์
$base_path = defined('BASE_URL') ? BASE_URL : '/s673190104/student_marketplace/';

// --------------------------------------------------------------------------------------------
// 3. ROUTING VISIBILITY CONTROLLERS (Access Control Lists)
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
// 4. USER DATA NORMALIZATION
// --------------------------------------------------------------------------------------------
// Process User Avatar with absolute pathing to prevent broken images in deep subdirectories
if (isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img'])) {
    // Check if the image is a full URL (like Google Profile)
    if (filter_var($_SESSION['profile_img'], FILTER_VALIDATE_URL)) {
        $user_avatar = $_SESSION['profile_img'];
    } else {
        $user_avatar = $base_path . "assets/images/profiles/" . $_SESSION['profile_img'];
    }
} else {
    $user_avatar = $base_path . "assets/images/profiles/default_profile.png";
}

// --------------------------------------------------------------------------------------------
// 5. REAL-TIME DATA FETCHING (Message Counter)
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
        // Fail silently on header to prevent UI breakage, log internally
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
    
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_path ?>assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $base_path ?>assets/images/favicon-16x16.png">
    <link rel="shortcut icon" href="<?= $base_path ?>assets/images/favicon.ico">
    
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/style.css">

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
            --bncc-background-light: #f8fafc; /* Changed to match previous UX */
            
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
            --bncc-sidebar-width: 320px;
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

        /* ======================================================================================
        ADVANCED UI COMPONENT LIBRARY (BNCC ENTERPRISE UX)
        Provides reusable components for the entire application to ensure consistency
        ======================================================================================
        */
        
        /* Flexbox Utility Classes */
        .ui-flex { display: flex; }
        .ui-flex-col { flex-direction: column; }
        .ui-flex-row { flex-direction: row; }
        .ui-flex-wrap { flex-wrap: wrap; }
        .ui-items-start { align-items: flex-start; }
        .ui-items-center { align-items: center; }
        .ui-items-end { align-items: flex-end; }
        .ui-items-stretch { align-items: stretch; }
        .ui-justify-start { justify-content: flex-start; }
        .ui-justify-center { justify-content: center; }
        .ui-justify-end { justify-content: flex-end; }
        .ui-justify-between { justify-content: space-between; }
        .ui-justify-around { justify-content: space-around; }

        /* Sizing Utility Classes */
        .ui-w-full { width: 100%; }
        .ui-h-full { height: 100%; }
        .ui-w-screen { width: 100vw; }
        .ui-h-screen { height: 100vh; }
        .ui-w-auto { width: auto; }
        .ui-h-auto { height: auto; }
        .ui-max-w-full { max-width: 100%; }
        
        /* Positioning Utilities */
        .ui-relative { position: relative; }
        .ui-absolute { position: absolute; }
        .ui-fixed { position: fixed; }
        .ui-sticky { position: sticky; top: 0; }
        
        /* Display Utilities */
        .ui-hidden { display: none !important; }
        .ui-block { display: block; }
        .ui-inline-block { display: inline-block; }
        
        /* Text Alignment & Formatting */
        .ui-text-center { text-align: center; }
        .ui-text-left { text-align: left; }
        .ui-text-right { text-align: right; }
        .ui-text-justify { text-align: justify; }
        .ui-uppercase { text-transform: uppercase; }
        .ui-lowercase { text-transform: lowercase; }
        .ui-capitalize { text-transform: capitalize; }
        .ui-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        
        /* Typography System */
        .ui-font-light { font-weight: 300; }
        .ui-font-normal { font-weight: 400; }
        .ui-font-medium { font-weight: 500; }
        .ui-font-semibold { font-weight: 600; }
        .ui-font-bold { font-weight: 700; }
        .ui-font-extrabold { font-weight: 800; }
        .ui-font-black { font-weight: 900; }
        
        .ui-text-xs { font-size: var(--bncc-font-xs); }
        .ui-text-sm { font-size: var(--bncc-font-sm); }
        .ui-text-base { font-size: var(--bncc-font-base); }
        .ui-text-lg { font-size: var(--bncc-font-lg); }
        .ui-text-xl { font-size: var(--bncc-font-xl); }
        .ui-text-2xl { font-size: var(--bncc-font-2xl); }
        .ui-text-3xl { font-size: var(--bncc-font-3xl); }
        .ui-text-4xl { font-size: var(--bncc-font-4xl); }
        
        /* Comprehensive Spacing System (Margin & Padding) */
        .ui-m-0 { margin: 0px; }
        .ui-m-1 { margin: 0.25rem; }
        .ui-m-2 { margin: 0.5rem; }
        .ui-m-3 { margin: 0.75rem; }
        .ui-m-4 { margin: 1rem; }
        .ui-m-5 { margin: 1.25rem; }
        .ui-m-6 { margin: 1.5rem; }
        .ui-m-8 { margin: 2rem; }
        .ui-m-10 { margin: 2.5rem; }
        .ui-m-12 { margin: 3rem; }
        .ui-m-16 { margin: 4rem; }
        .ui-m-20 { margin: 5rem; }
        
        .ui-mt-0 { margin-top: 0px; }
        .ui-mt-1 { margin-top: 0.25rem; }
        .ui-mt-2 { margin-top: 0.5rem; }
        .ui-mt-3 { margin-top: 0.75rem; }
        .ui-mt-4 { margin-top: 1rem; }
        .ui-mt-5 { margin-top: 1.25rem; }
        .ui-mt-6 { margin-top: 1.5rem; }
        .ui-mt-8 { margin-top: 2rem; }
        .ui-mt-10 { margin-top: 2.5rem; }
        .ui-mt-12 { margin-top: 3rem; }
        
        .ui-mb-0 { margin-bottom: 0px; }
        .ui-mb-1 { margin-bottom: 0.25rem; }
        .ui-mb-2 { margin-bottom: 0.5rem; }
        .ui-mb-3 { margin-bottom: 0.75rem; }
        .ui-mb-4 { margin-bottom: 1rem; }
        .ui-mb-5 { margin-bottom: 1.25rem; }
        .ui-mb-6 { margin-bottom: 1.5rem; }
        .ui-mb-8 { margin-bottom: 2rem; }
        .ui-mb-10 { margin-bottom: 2.5rem; }
        .ui-mb-12 { margin-bottom: 3rem; }
        
        .ui-ml-0 { margin-left: 0px; }
        .ui-ml-1 { margin-left: 0.25rem; }
        .ui-ml-2 { margin-left: 0.5rem; }
        .ui-ml-3 { margin-left: 0.75rem; }
        .ui-ml-4 { margin-left: 1rem; }
        .ui-ml-6 { margin-left: 1.5rem; }
        .ui-ml-8 { margin-left: 2rem; }
        
        .ui-mr-0 { margin-right: 0px; }
        .ui-mr-1 { margin-right: 0.25rem; }
        .ui-mr-2 { margin-right: 0.5rem; }
        .ui-mr-3 { margin-right: 0.75rem; }
        .ui-mr-4 { margin-right: 1rem; }
        .ui-mr-6 { margin-right: 1.5rem; }
        .ui-mr-8 { margin-right: 2rem; }
        
        .ui-p-0 { padding: 0px; }
        .ui-p-1 { padding: 0.25rem; }
        .ui-p-2 { padding: 0.5rem; }
        .ui-p-3 { padding: 0.75rem; }
        .ui-p-4 { padding: 1rem; }
        .ui-p-5 { padding: 1.25rem; }
        .ui-p-6 { padding: 1.5rem; }
        .ui-p-8 { padding: 2rem; }
        .ui-p-10 { padding: 2.5rem; }
        .ui-p-12 { padding: 3rem; }
        
        .ui-pt-0 { padding-top: 0px; }
        .ui-pt-1 { padding-top: 0.25rem; }
        .ui-pt-2 { padding-top: 0.5rem; }
        .ui-pt-3 { padding-top: 0.75rem; }
        .ui-pt-4 { padding-top: 1rem; }
        .ui-pt-5 { padding-top: 1.25rem; }
        .ui-pt-6 { padding-top: 1.5rem; }
        .ui-pt-8 { padding-top: 2rem; }
        .ui-pt-10 { padding-top: 2.5rem; }
        .ui-pt-12 { padding-top: 3rem; }
        
        .ui-pb-0 { padding-bottom: 0px; }
        .ui-pb-1 { padding-bottom: 0.25rem; }
        .ui-pb-2 { padding-bottom: 0.5rem; }
        .ui-pb-3 { padding-bottom: 0.75rem; }
        .ui-pb-4 { padding-bottom: 1rem; }
        .ui-pb-5 { padding-bottom: 1.25rem; }
        .ui-pb-6 { padding-bottom: 1.5rem; }
        .ui-pb-8 { padding-bottom: 2rem; }
        .ui-pb-10 { padding-bottom: 2.5rem; }
        .ui-pb-12 { padding-bottom: 3rem; }
        
        .ui-pl-0 { padding-left: 0px; }
        .ui-pl-1 { padding-left: 0.25rem; }
        .ui-pl-2 { padding-left: 0.5rem; }
        .ui-pl-3 { padding-left: 0.75rem; }
        .ui-pl-4 { padding-left: 1rem; }
        .ui-pl-6 { padding-left: 1.5rem; }
        .ui-pl-8 { padding-left: 2rem; }
        
        .ui-pr-0 { padding-right: 0px; }
        .ui-pr-1 { padding-right: 0.25rem; }
        .ui-pr-2 { padding-right: 0.5rem; }
        .ui-pr-3 { padding-right: 0.75rem; }
        .ui-pr-4 { padding-right: 1rem; }
        .ui-pr-6 { padding-right: 1.5rem; }
        .ui-pr-8 { padding-right: 2rem; }
        
        .ui-px-0 { padding-left: 0px; padding-right: 0px; }
        .ui-px-1 { padding-left: 0.25rem; padding-right: 0.25rem; }
        .ui-px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
        .ui-px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
        .ui-px-4 { padding-left: 1rem; padding-right: 1rem; }
        .ui-px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
        .ui-px-8 { padding-left: 2rem; padding-right: 2rem; }
        
        .ui-py-0 { padding-top: 0px; padding-bottom: 0px; }
        .ui-py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
        .ui-py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .ui-py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
        .ui-py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .ui-py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
        .ui-py-8 { padding-top: 2rem; padding-bottom: 2rem; }

        /* Border Radius Utilities */
        .ui-rounded-none { border-radius: var(--bncc-radius-none); }
        .ui-rounded-sm { border-radius: var(--bncc-radius-sm); }
        .ui-rounded-md { border-radius: var(--bncc-radius-md); }
        .ui-rounded-lg { border-radius: var(--bncc-radius-lg); }
        .ui-rounded-xl { border-radius: var(--bncc-radius-xl); }
        .ui-rounded-2xl { border-radius: var(--bncc-radius-2xl); }
        .ui-rounded-full { border-radius: var(--bncc-radius-full); }
        
        /* Shadow Utilities */
        .ui-shadow-sm { box-shadow: var(--bncc-shadow-sm); }
        .ui-shadow-md { box-shadow: var(--bncc-shadow-md); }
        .ui-shadow-lg { box-shadow: var(--bncc-shadow-lg); }
        .ui-shadow-xl { box-shadow: var(--bncc-shadow-xl); }
        .ui-shadow-none { box-shadow: none; }

        /* Basic Grid System */
        .ui-grid { display: grid; }
        .ui-grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .ui-grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ui-grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .ui-grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .ui-grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .ui-grid-cols-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .ui-grid-cols-12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }
        
        .ui-gap-1 { gap: 0.25rem; }
        .ui-gap-2 { gap: 0.5rem; }
        .ui-gap-3 { gap: 0.75rem; }
        .ui-gap-4 { gap: 1rem; }
        .ui-gap-6 { gap: 1.5rem; }
        .ui-gap-8 { gap: 2rem; }

        /* UI Colors: Text */
        .ui-text-primary { color: var(--bncc-primary-500); }
        .ui-text-success { color: var(--bncc-success-500); }
        .ui-text-danger { color: var(--bncc-danger-500); }
        .ui-text-warning { color: var(--bncc-warning-500); }
        .ui-text-info { color: var(--bncc-info-500); }
        .ui-text-white { color: #ffffff; }
        .ui-text-black { color: #000000; }
        .ui-text-main { color: var(--theme-text-primary); }
        .ui-text-sub { color: var(--theme-text-secondary); }
        .ui-text-muted { color: var(--theme-text-tertiary); }
        
        /* UI Colors: Backgrounds */
        .ui-bg-primary { background-color: var(--bncc-primary-500); }
        .ui-bg-success { background-color: var(--bncc-success-500); }
        .ui-bg-danger { background-color: var(--bncc-danger-500); }
        .ui-bg-warning { background-color: var(--bncc-warning-500); }
        .ui-bg-info { background-color: var(--bncc-info-500); }
        .ui-bg-white { background-color: #ffffff; }
        .ui-bg-transparent { background-color: transparent; }
        .ui-bg-surface { background-color: var(--theme-surface); }
        .ui-bg-surface-alt { background-color: var(--theme-surface-alt); }
        .ui-bg-base { background-color: var(--theme-bg); }

        /* Enterprise Components: Buttons */
        .ui-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--bncc-radius-md);
            font-weight: 600;
            font-size: var(--bncc-font-sm);
            line-height: 1.5;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            transition: all var(--bncc-duration-fast) var(--bncc-ease-in-out);
            cursor: pointer;
        }

        .ui-btn:disabled, .ui-btn.is-disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .ui-btn-primary {
            color: #ffffff;
            background-color: var(--bncc-primary-500);
            box-shadow: var(--bncc-shadow-sm);
        }
        
        .ui-btn-primary:hover {
            background-color: var(--bncc-primary-600);
            box-shadow: var(--bncc-shadow-md);
            transform: translateY(-1px);
        }

        .ui-btn-secondary {
            color: var(--theme-text-primary);
            background-color: var(--theme-surface-alt);
            border-color: var(--theme-border);
        }

        .ui-btn-secondary:hover {
            background-color: var(--theme-hover-bg);
            border-color: var(--theme-border-focus);
        }
        
        .ui-btn-danger {
            color: #ffffff;
            background-color: var(--bncc-danger-500);
        }
        
        .ui-btn-danger:hover {
            background-color: var(--bncc-danger-600);
            box-shadow: var(--bncc-shadow-md), 0 0 10px rgba(239, 68, 68, 0.4);
        }

        .ui-btn-ghost {
            color: var(--theme-text-secondary);
            background-color: transparent;
        }
        
        .ui-btn-ghost:hover {
            color: var(--theme-text-primary);
            background-color: var(--theme-hover-bg);
        }

        /* Enterprise Components: Cards */
        .ui-card {
            background-color: var(--theme-surface);
            border: 1px solid var(--theme-border);
            border-radius: var(--bncc-radius-lg);
            box-shadow: var(--theme-shadow-base);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform var(--bncc-duration-normal), box-shadow var(--bncc-duration-normal);
        }
        
        .ui-card:hover {
            box-shadow: var(--theme-shadow-hover);
        }
        
        .ui-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--theme-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .ui-card-body {
            padding: 1.5rem;
            flex-grow: 1;
        }
        
        .ui-card-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--theme-border);
            background-color: var(--theme-surface-alt);
        }

        /* Enterprise Components: Badges */
        .ui-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.625rem;
            border-radius: var(--bncc-radius-full);
            font-size: var(--bncc-font-xs);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
        
        .ui-badge-primary { background-color: var(--bncc-primary-100); color: var(--bncc-primary-700); }
        .ui-badge-success { background-color: var(--bncc-success-100); color: var(--bncc-success-700); }
        .ui-badge-danger { background-color: var(--bncc-danger-100); color: var(--bncc-danger-700); }
        .ui-badge-warning { background-color: var(--bncc-warning-100); color: var(--bncc-warning-700); }
        
        .dark-theme .ui-badge-primary { background-color: rgba(99, 102, 241, 0.2); color: var(--bncc-primary-300); }
        .dark-theme .ui-badge-success { background-color: rgba(16, 185, 129, 0.2); color: var(--bncc-success-300); }
        .dark-theme .ui-badge-danger { background-color: rgba(239, 68, 68, 0.2); color: var(--bncc-danger-300); }
        .dark-theme .ui-badge-warning { background-color: rgba(245, 158, 11, 0.2); color: var(--bncc-warning-300); }

        /* Enterprise Components: Form Inputs */
        .ui-input {
            display: block;
            width: 100%;
            padding: 0.625rem 1rem;
            font-size: var(--bncc-font-sm);
            font-weight: 400;
            line-height: 1.5;
            color: var(--theme-text-primary);
            background-color: var(--theme-input-bg);
            background-clip: padding-box;
            border: 1px solid var(--theme-border);
            border-radius: var(--bncc-radius-md);
            transition: border-color var(--bncc-duration-fast), box-shadow var(--bncc-duration-fast);
        }
        
        .ui-input:focus {
            border-color: var(--bncc-primary-400);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            outline: 0;
        }

        /* Enterprise Components: Tooltips */
        .ui-tooltip {
            position: relative;
            display: inline-block;
        }
        
        .ui-tooltip .ui-tooltip-text {
            visibility: hidden;
            width: max-content;
            max-width: 250px;
            background-color: var(--bncc-surface-dark);
            color: #ffffff;
            text-align: center;
            border-radius: var(--bncc-radius-md);
            padding: 0.5rem 0.75rem;
            font-size: var(--bncc-font-xs);
            font-weight: 500;
            position: absolute;
            z-index: var(--bncc-z-tooltip);
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            box-shadow: var(--bncc-shadow-lg);
            pointer-events: none;
        }
        
        .dark-theme .ui-tooltip .ui-tooltip-text {
            background-color: var(--bncc-surface-light);
            color: var(--bncc-text-primary-light);
        }
        
        .ui-tooltip .ui-tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: var(--bncc-surface-dark) transparent transparent transparent;
        }
        
        .dark-theme .ui-tooltip .ui-tooltip-text::after {
            border-color: var(--bncc-surface-light) transparent transparent transparent;
        }
        
        .ui-tooltip:hover .ui-tooltip-text {
            visibility: visible;
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* Custom Scrollbar Architecture */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background-color: var(--theme-bg);
            border-radius: var(--bncc-radius-full);
        }

        ::-webkit-scrollbar-thumb {
            background-color: var(--theme-border);
            border-radius: var(--bncc-radius-full);
            border-top-width: 2px;
            border-right-width: 2px;
            border-bottom-width: 2px;
            border-left-width: 2px;
            border-style: solid;
            border-color: var(--theme-bg);
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: var(--bncc-primary-400);
        }

        /* Keyframe Animations Engine */
        @keyframes fade-in {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        @keyframes fade-out {
            0% { opacity: 1; }
            100% { opacity: 0; }
        }

        @keyframes slide-down {
            0% { transform: translateY(-100%); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        @keyframes slide-up {
            0% { transform: translateY(20px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        @keyframes slide-right {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(0); }
        }

        @keyframes scale-in {
            0% { transform: scale(0.95); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        @keyframes spin-slow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes float-y {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Global Preloader Component */
        .bncc-preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: var(--theme-bg);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity var(--bncc-duration-slow) var(--bncc-ease-in-out), visibility var(--bncc-duration-slow);
        }

        .bncc-preloader.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .preloader-spinner {
            width: 60px;
            height: 60px;
            border-top-width: 4px;
            border-right-width: 4px;
            border-bottom-width: 4px;
            border-left-width: 4px;
            border-style: solid;
            border-color: var(--theme-border);
            border-top-color: var(--bncc-primary-500);
            border-radius: 50%;
            animation: spin-slow 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
            margin-bottom: 20px;
        }

        .preloader-text {
            font-size: var(--bncc-font-lg);
            font-weight: 800;
            color: var(--theme-text-primary);
            letter-spacing: 2px;
            animation: pulse-opacity 1.5s infinite;
        }

        @keyframes pulse-opacity {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Background Particle Effect System */
        .bg-particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: var(--bncc-z-under);
            overflow: hidden;
            pointer-events: none;
        }

        .bg-particle {
            position: absolute;
            background-color: var(--bncc-primary-500);
            border-radius: 50%;
            opacity: 0.05;
            animation: float-y 10s infinite ease-in-out;
        }

        .dark-theme .bg-particle {
            opacity: 0.1;
        }

        .bg-particle:nth-child(1) { width: 300px; height: 300px; top: -100px; left: -100px; animation-duration: 15s; }
        .bg-particle:nth-child(2) { width: 500px; height: 500px; bottom: -200px; right: -150px; animation-duration: 20s; animation-delay: -5s; }
        .bg-particle:nth-child(3) { width: 200px; height: 200px; top: 40%; left: 60%; animation-duration: 12s; animation-delay: -2s; }

        /* Master Header & Navigation Architecture */
        .master-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--bncc-header-height);
            background-color: var(--theme-glass-bg);
            -webkit-backdrop-filter: var(--bncc-glass-blur);
            backdrop-filter: var(--bncc-glass-blur);
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-bottom-color: var(--theme-glass-border);
            z-index: var(--bncc-z-fixed);
            transition-property: height, background-color, box-shadow;
            transition-duration: var(--bncc-duration-normal);
            transition-timing-function: var(--bncc-ease-in-out);
            display: flex;
            align-items: center;
        }

        .master-header.header-is-scrolled {
            height: var(--bncc-header-height-scrolled);
            box-shadow: var(--theme-shadow);
            background-color: var(--theme-surface);
            -webkit-backdrop-filter: none;
            backdrop-filter: none;
        }

        .header-progress-bar {
            position: absolute;
            bottom: -1px;
            left: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--bncc-primary-400), var(--bncc-primary-600));
            width: 0%;
            z-index: calc(var(--bncc-z-fixed) + 1);
            transition: width 0.1s linear;
        }

        .header-layout-container {
            width: 100%;
            max-width: var(--bncc-container-max);
            margin-top: 0;
            margin-right: auto;
            margin-bottom: 0;
            margin-left: auto;
            padding-top: 0;
            padding-right: 2rem;
            padding-bottom: 0;
            padding-left: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
        }

        /* Header Left Section: Toggle & Branding */
        .header-left-zone {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .sidebar-trigger-btn {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 48px;
            height: 48px;
            border-radius: var(--bncc-radius-lg);
            background-color: var(--theme-hover-bg);
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            border-left-width: 1px;
            border-style: solid;
            border-color: transparent;
            cursor: pointer;
            transition: all var(--bncc-duration-normal) var(--bncc-ease-elastic);
            position: relative;
            z-index: calc(var(--bncc-z-sidebar) + 10);
        }

        .sidebar-trigger-btn:hover {
            background-color: var(--bncc-primary-100);
            border-color: var(--bncc-primary-200);
            transform: scale(1.05);
        }

        .dark-theme .sidebar-trigger-btn:hover {
            background-color: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.4);
        }

        .trigger-line {
            display: block;
            width: 22px;
            height: 2.5px;
            background-color: var(--theme-text-primary);
            border-radius: var(--bncc-radius-full);
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce);
            transform-origin: center;
        }

        .trigger-line:nth-child(1) { transform: translateY(-6px); }
        .trigger-line:nth-child(3) { transform: translateY(6px); }

        body.sidebar-open .trigger-line:nth-child(1) {
            transform: translateY(0) rotate(45deg);
            background-color: var(--bncc-danger-500);
        }

        body.sidebar-open .trigger-line:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }

        body.sidebar-open .trigger-line:nth-child(3) {
            transform: translateY(0) rotate(-45deg);
            background-color: var(--bncc-danger-500);
        }

        .brand-link-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            transition: transform var(--bncc-duration-normal) var(--bncc-ease-elastic);
        }

        .brand-link-wrapper:hover {
            transform: scale(1.02) rotate(-1deg);
        }

        .brand-logo-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--bncc-primary-500), var(--bncc-primary-700));
            border-radius: var(--bncc-radius-lg);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #ffffff;
            font-size: 1.25rem;
            box-shadow: var(--bncc-shadow-md);
            position: relative;
            overflow: hidden;
        }

        .brand-logo-icon::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.4), transparent);
            transform: skewX(-20deg);
            animation: shine-effect 4s infinite;
        }

        @keyframes shine-effect {
            0% { left: -100%; }
            20% { left: 200%; }
            100% { left: 200%; }
        }

        .brand-typography {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--theme-text-primary);
            letter-spacing: -0.05em;
            margin-top: 0;
            margin-bottom: 0;
            display: flex;
            align-items: baseline;
        }

        .brand-highlight {
            color: var(--bncc-primary-500);
            font-weight: 800;
        }

        /* Header Right Section: Controls & User Profile */
        .header-right-zone {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-icon-btn {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: transparent;
            color: var(--theme-text-secondary);
            font-size: 1.25rem;
            border-width: 0;
            cursor: pointer;
            transition: all var(--bncc-duration-fast) var(--bncc-ease-out);
            text-decoration: none;
        }

        .header-icon-btn:hover, .header-icon-btn.is-active {
            background-color: var(--theme-hover-bg);
            color: var(--bncc-primary-500);
            transform: translateY(-2px);
        }

        .header-icon-btn:active {
            transform: translateY(0) scale(0.95);
        }

        /* Dynamic Theme Toggle Specifics */
        .theme-toggle-wrapper {
            position: relative;
            overflow: hidden;
        }

        .theme-icon-sun, .theme-icon-moon {
            position: absolute;
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce);
        }

        .theme-icon-sun {
            opacity: 0;
            transform: translateY(20px) rotate(90deg);
            color: var(--bncc-warning-500);
        }

        .theme-icon-moon {
            opacity: 1;
            transform: translateY(0) rotate(0deg);
        }

        .dark-theme .theme-icon-sun {
            opacity: 1;
            transform: translateY(0) rotate(0deg);
        }

        .dark-theme .theme-icon-moon {
            opacity: 0;
            transform: translateY(-20px) rotate(-90deg);
        }

        /* Notification Badge Component */
        .notification-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background-color: var(--bncc-danger-500);
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 800;
            min-width: 20px;
            height: 20px;
            border-radius: var(--bncc-radius-full);
            display: flex;
            justify-content: center;
            align-items: center;
            border-top-width: 2px;
            border-right-width: 2px;
            border-bottom-width: 2px;
            border-left-width: 2px;
            border-style: solid;
            border-color: var(--theme-surface);
            padding-top: 0;
            padding-right: 4px;
            padding-bottom: 0;
            padding-left: 4px;
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            animation: pulse-ring 2s infinite;
            z-index: 2;
        }

        /* Authenticated User Micro-Profile */
        .header-user-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-top: 0.375rem;
            padding-right: 0.5rem;
            padding-bottom: 0.375rem;
            padding-left: 0.5rem;
            border-radius: var(--bncc-radius-full);
            background-color: var(--theme-hover-bg);
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            border-left-width: 1px;
            border-style: solid;
            border-color: transparent;
            transition: all var(--bncc-duration-normal) var(--bncc-ease-out);
            cursor: pointer;
            text-decoration: none;
            margin-left: 0.5rem;
        }

        .header-user-card:hover {
            background-color: var(--theme-surface);
            border-color: var(--bncc-primary-300);
            box-shadow: var(--bncc-shadow-sm);
            transform: translateY(-1px);
        }

        .dark-theme .header-user-card:hover {
            border-color: var(--bncc-primary-700);
        }

        .header-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--bncc-radius-full);
            object-fit: cover;
            border-top-width: 2px;
            border-right-width: 2px;
            border-bottom-width: 2px;
            border-left-width: 2px;
            border-style: solid;
            border-color: var(--bncc-primary-500);
            background-color: var(--theme-surface);
        }

        .header-user-details {
            display: flex;
            flex-direction: column;
            padding-right: 0.5rem;
        }

        .header-user-name {
            font-size: var(--bncc-font-sm);
            font-weight: 700;
            color: var(--theme-text-primary);
            line-height: 1.2;
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .header-user-role {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--bncc-primary-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Guest Authentication Buttons */
        .auth-action-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-left: 0.5rem;
        }

        .btn-auth-login {
            padding-top: 0.6rem;
            padding-right: 1.2rem;
            padding-bottom: 0.6rem;
            padding-left: 1.2rem;
            border-radius: var(--bncc-radius-lg);
            font-weight: 700;
            font-size: var(--bncc-font-sm);
            color: var(--theme-text-primary);
            background-color: transparent;
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            border-left-width: 1px;
            border-style: solid;
            border-color: var(--theme-border);
            transition: all var(--bncc-duration-fast) var(--bncc-ease-out);
            text-decoration: none;
        }

        .btn-auth-login:hover {
            background-color: var(--theme-hover-bg);
            border-color: var(--bncc-primary-400);
            color: var(--bncc-primary-500);
        }

        .btn-auth-register {
            padding-top: 0.6rem;
            padding-right: 1.5rem;
            padding-bottom: 0.6rem;
            padding-left: 1.5rem;
            border-radius: var(--bncc-radius-lg);
            font-weight: 700;
            font-size: var(--bncc-font-sm);
            color: #ffffff;
            background: linear-gradient(135deg, var(--bncc-primary-400), var(--bncc-primary-600));
            box-shadow: var(--bncc-shadow-md);
            transition: all var(--bncc-duration-fast) var(--bncc-ease-bounce);
            text-decoration: none;
            border-width: 0;
        }

        .btn-auth-register:hover {
            transform: translateY(-2px);
            box-shadow: var(--bncc-shadow-lg), var(--bncc-shadow-glow);
            background: linear-gradient(135deg, var(--bncc-primary-500), var(--bncc-primary-700));
        }

        /* SIDEBAR DRAWER ARCHITECTURE */
        .global-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: var(--bncc-z-modal-backdrop);
            opacity: 0;
            visibility: hidden;
            transition: opacity var(--bncc-duration-normal) var(--bncc-ease-out), visibility var(--bncc-duration-normal);
        }

        body.sidebar-open .global-overlay {
            opacity: 1;
            visibility: visible;
        }

        .sidebar-master-drawer {
            position: fixed;
            top: 0;
            left: calc(-1 * var(--bncc-sidebar-width));
            width: var(--bncc-sidebar-width);
            max-width: 85vw;
            height: 100vh;
            background-color: var(--theme-surface);
            z-index: var(--bncc-z-sidebar);
            display: flex;
            flex-direction: column;
            box-shadow: var(--bncc-shadow-xl);
            transition: left var(--bncc-duration-slow) var(--bncc-ease-bounce);
            overflow: hidden;
        }

        .dark-theme .sidebar-master-drawer {
            border-right-width: 1px;
            border-right-style: solid;
            border-right-color: var(--theme-border);
        }

        body.sidebar-open .sidebar-master-drawer {
            left: 0;
        }

        /* Sidebar Internal Layout */
        .sidebar-top-section {
            padding-top: 2rem;
            padding-right: 1.5rem;
            padding-bottom: 1.5rem;
            padding-left: 1.5rem;
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-bottom-color: var(--theme-border);
            position: relative;
            background: linear-gradient(to bottom, var(--theme-hover-bg), transparent);
        }

        .sidebar-close-action {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 32px;
            height: 32px;
            border-radius: var(--bncc-radius-full);
            background-color: var(--theme-bg);
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            border-left-width: 1px;
            border-style: solid;
            border-color: var(--theme-border);
            color: var(--theme-text-secondary);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all var(--bncc-duration-fast) var(--bncc-ease-in-out);
        }

        .sidebar-close-action:hover {
            background-color: var(--bncc-danger-100);
            color: var(--bncc-danger-600);
            border-color: var(--bncc-danger-200);
            transform: rotate(90deg);
        }

        .dark-theme .sidebar-close-action:hover {
            background-color: rgba(239, 68, 68, 0.2);
        }

        .sidebar-user-identity {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
            margin-top: 1rem;
        }

        .sidebar-huge-avatar {
            width: 72px;
            height: 72px;
            border-radius: var(--bncc-radius-2xl);
            object-fit: cover;
            border-top-width: 3px;
            border-right-width: 3px;
            border-bottom-width: 3px;
            border-left-width: 3px;
            border-style: solid;
            border-color: var(--bncc-primary-500);
            padding: 3px;
            background-color: var(--theme-surface);
            box-shadow: var(--bncc-shadow-md);
        }

        .sidebar-identity-text {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .sidebar-identity-name {
            font-size: var(--bncc-font-xl);
            font-weight: 800;
            color: var(--theme-text-primary);
            line-height: 1.2;
            margin: 0;
        }

        .sidebar-identity-role-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding-top: 0.25rem;
            padding-right: 0.75rem;
            padding-bottom: 0.25rem;
            padding-left: 0.75rem;
            border-radius: var(--bncc-radius-md);
            font-size: var(--bncc-font-xs);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: fit-content;
        }

        /* Role Tag Variations */
        .role-tag-admin { background-color: var(--bncc-warning-100); color: var(--bncc-warning-600); border: 1px solid var(--bncc-warning-400); }
        .role-tag-teacher { background-color: var(--bncc-danger-100); color: var(--bncc-danger-600); border: 1px solid var(--bncc-danger-400); }
        .role-tag-seller { background-color: var(--bncc-success-100); color: var(--bncc-success-600); border: 1px solid var(--bncc-success-400); }
        .role-tag-buyer { background-color: var(--bncc-primary-100); color: var(--bncc-primary-600); border: 1px solid var(--bncc-primary-400); }
        .role-tag-guest { background-color: var(--theme-border); color: var(--theme-text-secondary); border: 1px solid var(--theme-text-tertiary); }

        .dark-theme .role-tag-admin { background-color: rgba(245, 158, 11, 0.2); }
        .dark-theme .role-tag-teacher { background-color: rgba(239, 68, 68, 0.2); }
        .dark-theme .role-tag-seller { background-color: rgba(16, 185, 129, 0.2); }
        .dark-theme .role-tag-buyer { background-color: rgba(99, 102, 241, 0.2); }
        .dark-theme .role-tag-guest { background-color: rgba(255, 255, 255, 0.1); }

        /* Sidebar Navigation Menu */
        .sidebar-scroll-area {
            flex-grow: 1;
            overflow-y: auto;
            padding-top: 1rem;
            padding-right: 1rem;
            padding-bottom: 2rem;
            padding-left: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-group-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--theme-text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding-top: 1rem;
            padding-right: 1rem;
            padding-bottom: 0.5rem;
            padding-left: 1rem;
            margin-top: 0.5rem;
        }

        .nav-menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-top: 0.875rem;
            padding-right: 1rem;
            padding-bottom: 0.875rem;
            padding-left: 1rem;
            border-radius: var(--bncc-radius-lg);
            text-decoration: none;
            color: var(--theme-text-secondary);
            font-weight: 600;
            font-size: var(--bncc-font-sm);
            transition: all var(--bncc-duration-normal) var(--bncc-ease-out);
            position: relative;
            overflow: hidden;
        }

        .nav-menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--bncc-primary-500);
            transform: scaleY(0);
            transition: transform var(--bncc-duration-normal) var(--bncc-ease-out);
            transform-origin: center;
            border-radius: 0 4px 4px 0;
        }

        .nav-menu-item:hover {
            background-color: var(--theme-hover-bg);
            color: var(--theme-text-primary);
            transform: translateX(4px);
        }

        .nav-menu-item.is-active {
            background-color: var(--bncc-primary-50);
            color: var(--bncc-primary-700);
        }

        .dark-theme .nav-menu-item.is-active {
            background-color: rgba(99, 102, 241, 0.15);
            color: var(--bncc-primary-300);
        }

        .nav-menu-item.is-active::before {
            transform: scaleY(0.6);
        }

        .nav-menu-icon {
            width: 24px;
            font-size: 1.25rem;
            text-align: center;
            transition: transform var(--bncc-duration-normal) var(--bncc-ease-bounce);
        }

        .nav-menu-item:hover .nav-menu-icon {
            transform: scale(1.15);
            color: var(--bncc-primary-500);
        }

        .nav-menu-item.is-active .nav-menu-icon {
            color: var(--bncc-primary-500);
        }

        /* Specific Menu Item Coloring */
        .nav-item-danger:hover .nav-menu-icon { color: var(--bncc-danger-500); }
        .nav-item-success:hover .nav-menu-icon { color: var(--bncc-success-500); }
        .nav-item-warning:hover .nav-menu-icon { color: var(--bncc-warning-500); }

        .nav-item-admin {
            background-color: rgba(239, 68, 68, 0.05);
            border: 1px dashed rgba(239, 68, 68, 0.2);
        }
        
        .nav-item-admin:hover {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--bncc-danger-600);
        }

        /* Sidebar Footer (Logout) */
        .sidebar-bottom-section {
            padding-top: 1.5rem;
            padding-right: 1.5rem;
            padding-bottom: 2rem;
            padding-left: 1.5rem;
            border-top-width: 1px;
            border-top-style: solid;
            border-top-color: var(--theme-border);
            background-color: var(--theme-surface);
        }

        .btn-logout-massive {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 1rem;
            border-radius: var(--bncc-radius-lg);
            background-color: var(--bncc-danger-50);
            color: var(--bncc-danger-600);
            font-weight: 800;
            font-size: var(--bncc-font-base);
            text-decoration: none;
            border-width: 1px;
            border-style: solid;
            border-color: var(--bncc-danger-100);
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce);
        }

        .dark-theme .btn-logout-massive {
            background-color: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: var(--bncc-danger-400);
        }

        .btn-logout-massive:hover {
            background-color: var(--bncc-danger-500);
            color: #ffffff;
            border-color: var(--bncc-danger-600);
            box-shadow: 0 10px 20px -5px rgba(239, 68, 68, 0.4);
            transform: translateY(-2px);
        }

        /* ADVANCED NOTIFICATION DROPDOWN SYSTEM */
        
        .notification-dropdown-container {
            position: absolute;
            top: calc(100% + 15px);
            right: -10px;
            width: 400px;
            max-width: calc(100vw - 20px);
            background-color: var(--theme-surface);
            border-radius: var(--bncc-radius-xl);
            box-shadow: var(--bncc-shadow-xl);
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            border-left-width: 1px;
            border-style: solid;
            border-color: var(--theme-border);
            display: flex;
            flex-direction: column;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px) scale(0.98);
            transform-origin: top right;
            transition: all var(--bncc-duration-normal) var(--bncc-ease-bounce);
            z-index: var(--bncc-z-dropdown);
            overflow: hidden;
        }

        .notification-dropdown-container.is-active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .notif-dropdown-header {
            padding-top: 1.25rem;
            padding-right: 1.5rem;
            padding-bottom: 1.25rem;
            padding-left: 1.5rem;
            background: linear-gradient(135deg, var(--bncc-primary-500), var(--bncc-primary-700));
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notif-dropdown-title {
            font-size: var(--bncc-font-lg);
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notif-action-markread {
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border: none;
            padding-top: 0.25rem;
            padding-right: 0.75rem;
            padding-bottom: 0.25rem;
            padding-left: 0.75rem;
            border-radius: var(--bncc-radius-full);
            font-size: var(--bncc-font-xs);
            font-weight: 700;
            cursor: pointer;
            transition: background-color var(--bncc-duration-fast);
        }

        .notif-action-markread:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .notif-scroll-viewport {
            max-height: 450px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .notif-entity-card {
            display: flex;
            gap: 1rem;
            padding-top: 1.25rem;
            padding-right: 1.5rem;
            padding-bottom: 1.25rem;
            padding-left: 1.5rem;
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-bottom-color: var(--theme-border);
            text-decoration: none;
            transition: background-color var(--bncc-duration-fast);
            position: relative;
        }

        .notif-entity-card:hover {
            background-color: var(--theme-hover-bg);
        }

        .notif-entity-card.state-unread {
            background-color: var(--bncc-primary-50);
        }

        .dark-theme .notif-entity-card.state-unread {
            background-color: rgba(99, 102, 241, 0.1);
        }

        .notif-entity-card.state-unread::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background-color: var(--bncc-primary-500);
            border-radius: 0 4px 4px 0;
        }

        .notif-visual-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--bncc-radius-full);
            background-color: var(--theme-surface);
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            border-left-width: 1px;
            border-style: solid;
            border-color: var(--theme-border);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.25rem;
            color: var(--bncc-primary-500);
            flex-shrink: 0;
        }

        .state-unread .notif-visual-icon {
            background-color: var(--bncc-primary-500);
            color: #ffffff;
            border-color: var(--bncc-primary-600);
        }

        .notif-text-content {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .notif-message-string {
            font-size: var(--bncc-font-sm);
            color: var(--theme-text-primary);
            line-height: 1.4;
        }

        .state-unread .notif-message-string {
            font-weight: 700;
        }

        .notif-timestamp-string {
            font-size: var(--bncc-font-xs);
            color: var(--theme-text-tertiary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .notif-empty-state {
            padding-top: 4rem;
            padding-right: 2rem;
            padding-bottom: 4rem;
            padding-left: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--theme-text-tertiary);
            text-align: center;
        }

        .notif-empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .notif-dropdown-footer {
            padding-top: 1rem;
            padding-right: 1rem;
            padding-bottom: 1rem;
            padding-left: 1rem;
            text-align: center;
            border-top-width: 1px;
            border-top-style: solid;
            border-top-color: var(--theme-border);
            background-color: var(--theme-bg);
        }

        .notif-view-all-link {
            font-size: var(--bncc-font-sm);
            font-weight: 700;
            color: var(--bncc-primary-500);
            text-decoration: none;
        }

        .notif-view-all-link:hover {
            text-decoration: underline;
        }

        /* RESPONSIVE MEDIA QUERIES */
        @media screen and (max-width: 1024px) {
            .header-layout-container { padding-right: 1.5rem; padding-left: 1.5rem; }
            .brand-typography { font-size: 1.25rem; }
        }

        @media screen and (max-width: 768px) {
            .header-user-name { display: none; }
            .header-user-role { display: none; }
            .header-user-card { padding: 0; background: transparent; border: none; margin-left: 0; }
            .header-user-card:hover { background: transparent; box-shadow: none; transform: none; }
            
            .notification-dropdown-container {
                position: fixed;
                top: var(--bncc-header-height);
                right: 10px;
                left: 10px;
                width: auto;
                max-width: none;
                transform-origin: top center;
            }
            
            .auth-action-group { gap: 0.5rem; }
            .btn-auth-login { padding: 0.5rem 0.75rem; font-size: 0.75rem; }
            .btn-auth-register { padding: 0.5rem 1rem; font-size: 0.75rem; }
        }

        @media screen and (max-width: 480px) {
            .brand-typography { display: none; }
            .header-left-zone { gap: 0.75rem; }
            .header-icon-btn { width: 38px; height: 38px; font-size: 1.1rem; }
            .sidebar-master-drawer { width: 85vw; }
        }
    </style>

    <script>
        // Enterprise JS Utilities Namespace
        window.BNCCUtils = {
            storage: {
                set: function(key, value) {
                    // Save pure string to avoid JSON parse failures later
                    localStorage.setItem(key, value);
                },
                get: function(key) {
                    return localStorage.getItem(key);
                }
            }
        };

        /**
         * EARLY BLOCKING SCRIPTS
         * Theme Initialization - Executes before DOM renders to prevent flash of wrong theme
         */
        (function() {
            try {
                // 🎯 THEME BUG FIX: Handle raw string from localStorage safely
                var sysTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                var savedTheme = localStorage.getItem('bncc_enterprise_theme');
                
                // Fallback to light if local storage contains invalid data
                if (savedTheme !== 'dark' && savedTheme !== 'light') {
                    savedTheme = null;
                }
                
                var activeTheme = savedTheme || sysTheme;
                
                if (activeTheme === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    document.documentElement.classList.add('dark-theme');
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                    document.documentElement.classList.remove('dark-theme');
                }
            } catch (e) {
                console.error("Theme Init Error:", e);
            }
        })();
    </script>
</head>
<body id="bnccBodyElement">

<div id="globalPreloader" class="bncc-preloader">
    <div class="preloader-spinner"></div>
    <div class="preloader-text">BNCC MARKET</div>
</div>

<div class="bg-particles-container" aria-hidden="true">
    <div class="bg-particle"></div>
    <div class="bg-particle"></div>
    <div class="bg-particle"></div>
</div>

<nav id="masterNavbarElement" class="master-header">
    
    <div id="scrollProgressBar" class="header-progress-bar"></div>

    <div class="header-layout-container">
        
        <div class="header-left-zone">
            <button id="sidebarToggleMasterBtn" class="sidebar-trigger-btn" aria-label="Open Navigation Menu" aria-expanded="false" aria-controls="sidebarMasterDrawer">
                <span class="trigger-line"></span>
                <span class="trigger-line"></span>
                <span class="trigger-line"></span>
            </button>

            <a href="<?= $base_path ?>pages/index.php" class="brand-link-wrapper" aria-label="BNCC Market Home">
                <div class="brand-logo-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h1 class="brand-typography">BNCC<span class="brand-highlight">Market</span></h1>
            </a>
        </div>

        <div class="header-right-zone">
            
            <button id="themeToggleMasterBtn" class="header-icon-btn theme-toggle-wrapper" aria-label="Toggle Dark Mode">
                <i class="fas fa-sun theme-icon-sun"></i>
                <i class="fas fa-moon theme-icon-moon"></i>
            </button>

            <?php if (isLoggedIn()): ?>
                
                <div style="position: relative;">
                    <button id="notifToggleMasterBtn" class="header-icon-btn" aria-label="Open Notifications" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span id="notifBadgeDynamic" class="notification-badge" style="display: none;">0</span>
                    </button>

                    <div id="notifDropdownPanel" class="notification-dropdown-container" role="menu">
                        <div class="notif-dropdown-header">
                            <h3 class="notif-dropdown-title"><i class="fas fa-bell"></i> การแจ้งเตือน</h3>
                            <button id="markAllReadAction" class="notif-action-markread">ทำเครื่องหมายอ่านแล้ว</button>
                        </div>
                        
                        <div id="notifListViewport" class="notif-scroll-viewport">
                            <div class="notif-empty-state">
                                <i class="fas fa-circle-notch fa-spin"></i>
                                <span>กำลังโหลดข้อมูล...</span>
                            </div>
                        </div>

                        <div class="notif-dropdown-footer">
                            <a href="<?= $base_path ?>pages/notifications.php" class="notif-view-all-link">ดูการแจ้งเตือนทั้งหมด</a>
                        </div>
                    </div>
                </div>

                <a href="<?= $base_path ?>pages/chat.php" class="header-icon-btn" aria-label="Open Messages">
                    <i class="fas fa-comment-dots"></i>
                    <?php if($unread_msg_count > 0): ?>
                        <span class="notification-badge"><?= $unread_msg_count > 99 ? '99+' : $unread_msg_count ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= $base_path ?>pages/profile.php" class="header-user-card" aria-label="View Profile">
                    <div class="header-user-details">
                        <span class="header-user-name"><?= htmlspecialchars($_SESSION['fullname']) ?></span>
                        <span class="header-user-role"><?= htmlspecialchars($_SESSION['role']) ?></span>
                    </div>
                    <img src="<?= $user_avatar ?>" alt="Avatar" class="header-user-avatar">
                </a>

            <?php else: ?>
                
                <?php if (!in_array($current_page, $hide_auth_list)): ?>
                    <div class="auth-action-group">
                        <a href="<?= $base_path ?>auth/login.php" class="btn-auth-login">เข้าสู่ระบบ</a>
                    
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
</nav>

<div id="globalSidebarOverlay" class="global-overlay" aria-hidden="true"></div>

<aside id="sidebarMasterDrawer" class="sidebar-master-drawer" aria-hidden="true" tabindex="-1">
    
    <div class="sidebar-top-section">
        <button id="sidebarCloseMasterBtn" class="sidebar-close-action" aria-label="Close Navigation Menu">
            <i class="fas fa-times"></i>
        </button>

        <?php if (isLoggedIn()): ?>
            <div class="sidebar-user-identity">
                <img src="<?= $user_avatar ?>" alt="Large Avatar" class="sidebar-huge-avatar">
                <div class="sidebar-identity-text">
                    <h2 class="sidebar-identity-name"><?= htmlspecialchars($_SESSION['fullname']) ?></h2>
                    
                    <?php 
                        $role_tag_class = 'role-tag-guest';
                        $role_tag_text = 'ผู้ใช้ระบบ';
                        $role_tag_icon = 'fa-user';
                        
                        switch($_SESSION['role']) {
                            case 'admin':
                                $role_tag_class = 'role-tag-admin';
                                $role_tag_text = 'ผู้ดูแลระบบสูงสุด';
                                $role_tag_icon = 'fa-crown';
                                break;
                            case 'teacher':
                                $role_tag_class = 'role-tag-teacher';
                                $role_tag_text = 'อาจารย์ / ผู้ดูแล';
                                $role_tag_icon = 'fa-chalkboard-teacher';
                                break;
                            case 'seller':
                                $role_tag_class = 'role-tag-seller';
                                $role_tag_text = 'ร้านค้าที่ได้รับการอนุมัติ';
                                $role_tag_icon = 'fa-store';
                                break;
                            case 'buyer':
                                $role_tag_class = 'role-tag-buyer';
                                $role_tag_text = 'สมาชิกทั่วไป';
                                $role_tag_icon = 'fa-shopping-bag';
                                break;
                        }
                    ?>
                    <span class="sidebar-identity-role-tag <?= $role_tag_class ?>">
                        <i class="fas <?= $role_tag_icon ?>"></i> <?= $role_tag_text ?>
                    </span>
                </div>
            </div>
        <?php else: ?>
            <div class="sidebar-user-identity">
                <div class="sidebar-huge-avatar" style="display:flex; justify-content:center; align-items:center; border:none; background:var(--theme-border);">
                    <i class="fas fa-user-circle fa-3x" style="color:var(--theme-text-tertiary);"></i>
                </div>
                <div class="sidebar-identity-text">
                    <h2 class="sidebar-identity-name">ผู้เยี่ยมชมระบบ</h2>
                    <span class="sidebar-identity-role-tag role-tag-guest">
                        <i class="fas fa-info-circle"></i> โปรดเข้าสู่ระบบ
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <nav class="sidebar-scroll-area" aria-label="Main Navigation">
        
        <div class="nav-group-label">ระบบตลาดกลาง (Marketplace)</div>
        
        <a href="<?= $base_path ?>pages/index.php" class="nav-menu-item <?= $current_page == 'index.php' ? 'is-active' : '' ?>">
            <i class="fas fa-home nav-menu-icon"></i>
            <span>หน้าหลัก</span>
        </a>
        
        <a href="<?= $base_path ?>/pages/wtb_board.php" class="nav-menu-item nav-item-warning <?= $current_page == 'wtb_board.php' ? 'is-active' : '' ?>">
            <i class="fas fa-bullhorn nav-menu-icon"></i>
            <span>กระดานตามหาของ (WTB)</span>
        </a>

        <?php if (isLoggedIn()): ?>
            
            <div class="nav-group-label">พื้นที่ส่วนตัว (Personal Space)</div>
            
            <a href="<?= $base_path ?>pages/profile.php" class="nav-menu-item <?= $current_page == 'profile.php' ? 'is-active' : '' ?>">
                <i class="fas fa-id-badge nav-menu-icon"></i>
                <span>จัดการข้อมูลบัญชี</span>
            </a>
            
            <a href="<?= $base_path ?>pages/wishlist.php" class="nav-menu-item nav-item-danger <?= $current_page == 'wishlist.php' ? 'is-active' : '' ?>">
                <i class="fas fa-heart nav-menu-icon"></i>
                <span>รายการสินค้าที่ถูกใจ</span>
            </a>
            
            <a href="<?= $base_path ?>pages/my_orders.php" class="nav-menu-item nav-item-success <?= $current_page == 'my_orders.php' ? 'is-active' : '' ?>">
                <i class="fas fa-shopping-basket nav-menu-icon"></i>
                <span>ประวัติคำสั่งซื้อ</span>
            </a>

            <?php if ($_SESSION['role'] === 'seller'): ?>
                <div class="nav-group-label">เครื่องมือผู้ขาย (Seller Center)</div>
                <a href="<?= $base_path ?>seller/dashboard.php" class="nav-menu-item nav-item-success">
                    <i class="fas fa-store nav-menu-icon"></i>
                    <span>แผงควบคุมร้านค้า</span>
                </a>
            <?php else: ?>
                <a href="<?= $base_path ?>auth/register_seller.php" class="nav-menu-item">
                    <i class="fas fa-store-alt nav-menu-icon"></i>
                    <span>ลงทะเบียนเปิดร้านค้า</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                <div class="nav-group-label">ส่วนการจัดการ (Administration)</div>
                <a href="<?= $base_path ?>admin/admin_dashboard.php" class="nav-menu-item nav-item-admin <?= $current_page == 'admin_dashboard.php' ? 'is-active' : '' ?>">
                    <i class="fas fa-shield-halved nav-menu-icon"></i>
                    <span>ศูนย์ควบคุมระบบ (Admin Panel)</span>
                </a>
                <a href="<?= $base_path ?>admin/approve_product.php" class="nav-menu-item nav-item-admin <?= $current_page == 'approve_product.php' ? 'is-active' : '' ?>">
                    <i class="fas fa-clipboard-check nav-menu-icon"></i>
                    <span>อนุมัติรายการสินค้า</span>
                </a>
                <a href="<?= $base_path ?>admin/approve_shop.php" class="nav-menu-item nav-item-admin <?= $current_page == 'approve_shop.php' ? 'is-active' : '' ?>">
                    <i class="fas fa-store-slash nav-menu-icon"></i>
                    <span>อนุมัติคำร้องเปิดร้าน</span>
                </a>
            <?php endif; ?>

        <?php else: ?>
            
            <div class="nav-group-label">การเข้าถึงระบบ (Authentication)</div>
            
            <a href="<?= $base_path ?>auth/login.php" class="nav-menu-item">
                <i class="fas fa-sign-in-alt nav-menu-icon"></i>
                <span>เข้าสู่ระบบ (Login)</span>
            </a>
            
            <a href="<?= $base_path ?>auth/register.php" class="nav-menu-item">
                <i class="fas fa-user-plus nav-menu-icon"></i>
                <span>สมัครสมาชิกใหม่ (Register)</span>
            </a>
            
        <?php endif; ?>
        
    </nav>

    <?php if (isLoggedIn()): ?>
    <div class="sidebar-bottom-section">
        <a href="<?= $base_path ?>auth/logout.php" class="btn-logout-massive">
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
     * MODULE 1: PRELOADER MANAGEMENT
     */
    const PreloaderController = {
        element: document.getElementById('globalPreloader'),
        init() {
            if (!this.element) return;
            document.body.classList.add('noscroll');
            
            window.addEventListener('load', () => {
                setTimeout(() => {
                    this.element.classList.add('is-hidden');
                    document.body.classList.remove('noscroll');
                }, 300); 
            });
            
            setTimeout(() => {
                if(!this.element.classList.contains('is-hidden')){
                    this.element.classList.add('is-hidden');
                    document.body.classList.remove('noscroll');
                }
            }, 3000);
        }
    };
    PreloaderController.init();

    /**
     * MODULE 2: SCROLL ENGINE & NAVBAR EFFECTS
     */
    const ScrollController = {
        navbar: document.getElementById('masterNavbarElement'),
        progressBar: document.getElementById('scrollProgressBar'),
        lastScrollY: 0,
        ticking: false,

        update() {
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > 20) {
                this.navbar.classList.add('header-is-scrolled');
            } else {
                this.navbar.classList.remove('header-is-scrolled');
            }

            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            if(height > 0) {
                const scrolled = (winScroll / height) * 100;
                this.progressBar.style.width = scrolled + "%";
            }

            this.lastScrollY = currentScrollY;
            this.ticking = false;
        },

        init() {
            if(!this.navbar) return;
            window.addEventListener('scroll', () => {
                if (!this.ticking) {
                    window.requestAnimationFrame(() => this.update());
                    this.ticking = true;
                }
            }, { passive: true });
        }
    };
    ScrollController.init();

    /**
     * MODULE 3: THEME MANAGER (🎯 FIXED PERSISTENCE)
     */
    const ThemeController = {
        btn: document.getElementById('themeToggleMasterBtn'),
        html: document.documentElement,
        metaTheme: document.getElementById('theme-color-meta'),

        init() {
            if(!this.btn) return;
            this.btn.addEventListener('click', () => this.toggle());
        },

        toggle() {
            const isDark = this.html.classList.toggle('dark-theme');
            const theme = isDark ? 'dark' : 'light';
            this.html.setAttribute('data-theme', theme);
            localStorage.setItem('bncc_enterprise_theme', theme);
            if(this.metaTheme) this.metaTheme.setAttribute('content', isDark ? '#111827' : '#ffffff');
        }
    };
    ThemeController.init();

    /**
     * MODULE 4: SIDEBAR DRAWER MANAGER
     */
    const SidebarController = {
        toggleBtn: document.getElementById('sidebarToggleMasterBtn'),
        closeBtn: document.getElementById('sidebarCloseMasterBtn'),
        drawer: document.getElementById('sidebarMasterDrawer'),
        overlay: document.getElementById('globalSidebarOverlay'),
        body: document.body,
        isOpen: false,

        init() {
            if (!this.toggleBtn || !this.drawer || !this.overlay) return;

            this.toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggle();
            });

            this.closeBtn.addEventListener('click', () => this.close());
            this.overlay.addEventListener('click', () => this.close());

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) this.close();
            });
        },

        toggle() {
            this.isOpen ? this.close() : this.open();
        },

        open() {
            this.isOpen = true;
            this.body.classList.add('sidebar-open');
            this.drawer.setAttribute('aria-hidden', 'false');
            this.drawer.focus();
        },

        close() {
            this.isOpen = false;
            this.body.classList.remove('sidebar-open');
            this.drawer.setAttribute('aria-hidden', 'true');
        }
    };
    SidebarController.init();

    /**
     * MODULE 5: AJAX NOTIFICATION ENGINE (🎯 THE 404 & ABSOLUTE PATH FIX)
     */
    <?php if(isLoggedIn()): ?>
    const NotificationController = {
        toggleBtn: document.getElementById('notifToggleMasterBtn'),
        panel: document.getElementById('notifDropdownPanel'),
        listView: document.getElementById('notifListViewport'),
        badge: document.getElementById('notifBadgeDynamic'),
        markReadBtn: document.getElementById('markAllReadAction'),
        isOpen: false,
        pollInterval: 30000, 
        pollTimer: null,
        
        // 🎯 กำหนดพาร์ทหลักให้แน่นอน ป้องกัน AJAX 404
        baseProject: '<?= $base_path ?>',
        apiEndpoint: '<?= $base_path ?>ajax/notifications_api.php',

        init() {
            if(!this.toggleBtn || !this.panel) return;

            this.toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggle();
            });

            if(this.markReadBtn) {
                this.markReadBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.executeMarkAllRead();
                });
            }

            document.addEventListener('click', (e) => {
                if (this.isOpen && !this.panel.contains(e.target) && !this.toggleBtn.contains(e.target)) {
                    this.close();
                }
            });

            this.fetchData();
            this.startPolling();
        },

        // 🎯 ฟังก์ชันจัดการลิงก์ ป้องกันการเบิ้ลโฟลเดอร์ admin/admin หรือ pages/admin
        resolveSafeLink(link) {
            if (!link || link === '#' || link === '') return '#';
            
            // ถ้าเป็นลิงก์เต็ม (http/https) ให้ใช้ตัวเดิม
            if (link.startsWith('http://') || link.startsWith('https://')) return link;

            // ลบเครื่องหมาย / ที่อยู่ข้างหน้าลิงก์จาก DB (ถ้ามี)
            let cleanLink = link.replace(/^\/+/, '');
            
            // 🎯 บังคับให้เริ่มจาก Root Project เสมอ (Base + Link)
            return this.baseProject + cleanLink;
        },

        toggle() {
            this.isOpen ? this.close() : this.open();
        },

        open() {
            this.isOpen = true;
            this.panel.classList.add('is-active');
            this.toggleBtn.classList.add('is-active');
            this.toggleBtn.setAttribute('aria-expanded', 'true');
            this.fetchData(); 
        },

        close() {
            this.isOpen = false;
            this.panel.classList.remove('is-active');
            this.toggleBtn.classList.remove('is-active');
            this.toggleBtn.setAttribute('aria-expanded', 'false');
        },

        startPolling() {
            this.pollTimer = setInterval(() => this.fetchData(), this.pollInterval);
        },

        async fetchData() {
            try {
                const response = await fetch(this.apiEndpoint + '?action=fetch', {
                    method: 'GET',
                    headers: { 'Cache-Control': 'no-cache', 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (!response.ok) throw new Error('Network response failure');
                
                const data = await response.json();
                this.renderUI(data);
            } catch (error) {
                console.error("Notification Engine Error:", error);
                if(this.isOpen && this.listView.innerHTML.includes('fa-circle-notch')) {
                    this.listView.innerHTML = `<div class="notif-empty-state"><i class="fas fa-exclamation-triangle text-danger"></i><span>ไม่สามารถโหลดข้อมูลได้</span></div>`;
                }
            }
        },

        renderUI(data) {
            if (data.status !== 'success') return;

            if (data.unread_count > 0) {
                this.badge.style.display = 'flex';
                this.badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                this.badge.classList.add('animate-pop');
            } else {
                this.badge.style.display = 'none';
                this.badge.classList.remove('animate-pop');
            }

            if (!this.isOpen && this.listView.innerHTML !== '') return; 

            if (data.notifications && data.notifications.length > 0) {
                const htmlBuilder = data.notifications.map(notif => {
                    const stateClass = notif.is_read == 0 ? 'state-unread' : '';
                    const iconDef = notif.icon || '<i class="fas fa-bell"></i>';
                    
                    // 🎯 เรียกใช้ตัวจัดการลิงก์อัจฉริยะ แก้ปัญหา 404
                    const safeLink = this.resolveSafeLink(notif.link);
                    
                    return `
                        <a href="${safeLink}" class="notif-entity-card ${stateClass}">
                            <div class="notif-visual-icon">${iconDef}</div>
                            <div class="notif-text-content">
                                <span class="notif-message-string">${notif.message}</span>
                                <span class="notif-timestamp-string"><i class="far fa-clock"></i> ${notif.time || 'ล่าสุด'}</span>
                            </div>
                        </a>
                    `;
                }).join('');
                this.listView.innerHTML = htmlBuilder;
            } else {
                this.listView.innerHTML = `
                    <div class="notif-empty-state">
                        <i class="fas fa-inbox box-empty"></i>
                        <span>ยังไม่มีการแจ้งเตือนใหม่ในขณะนี้</span>
                    </div>
                `;
            }
        },

        async executeMarkAllRead() {
            try {
                this.badge.style.display = 'none';
                const items = this.listView.querySelectorAll('.state-unread');
                items.forEach(item => item.classList.remove('state-unread'));

                const formParams = new URLSearchParams();
                formParams.append('action', 'mark_read');

                await fetch(this.apiEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formParams
                });
                
                this.fetchData();
            } catch (err) {
                console.error("Mark Read Failure:", err);
            }
        }
    };
    NotificationController.init();
    <?php endif; ?>

});
</script>

<main class="bncc-master-main-wrapper" style="padding-top: calc(var(--bncc-header-height) + 1.5rem); min-height: calc(100vh - var(--bncc-header-height)); position: relative; z-index: var(--bncc-z-base);">