<?php
// Core System Initialization
require_once __DIR__ . '/functions.php';

// Determine the current active page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Ensure session is started safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🎯 FIX 1: แก้ปัญหา 404 โดยการหา BASE_URL ที่แท้จริงแบบ Dynamic เพื่อให้ลิงก์ไม่เบิ้ล
// ถ้าระบบไม่ได้ตั้ง BASE_URL ไว้ ให้คำนวณจาก root ชั่วคราว
if (!defined('BASE_URL')) {
    // ปรับพาร์ทตรงนี้ให้ตรงกับโฟลเดอร์โปรเจกต์ของพี่
    $base_path = '/s673190104/student_marketplace/'; 
} else {
    $base_path = BASE_URL;
}

// Define visibility arrays for specific pages
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

// Process User Avatar with fallback mechanism
if (isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img'])) {
    // ตรวจสอบว่าเป็น URL ภายนอกหรือไม่ (เช่น Google Avatar)
    if (filter_var($_SESSION['profile_img'], FILTER_VALIDATE_URL)) {
        $user_avatar = $_SESSION['profile_img'];
    } else {
        $user_avatar = $base_path . "assets/images/profiles/" . $_SESSION['profile_img'];
    }
} else {
    $user_avatar = $base_path . "assets/images/profiles/default_profile.png";
}

// Initialize unread message counter
$unread_msg_count = 0;

// Fetch real-time notification data if user is authenticated
if (isLoggedIn()) {
    try {
        $db = getDB();
        $msg_query = "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0";
        $msg_stmt = $db->prepare($msg_query);
        $msg_stmt->execute([$_SESSION['user_id']]);
        $unread_msg_count = $msg_stmt->fetchColumn();
        
        // Enhanced notification data fetching
        $notif_query = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $notif_stmt = $db->prepare($notif_query);
        $notif_stmt->execute([$_SESSION['user_id']]);
        $unread_notif_count = $notif_stmt->fetchColumn();
        
        // User activity tracking
        $activity_query = "UPDATE users SET last_activity = NOW() WHERE user_id = ?";
        $activity_stmt = $db->prepare($activity_query);
        $activity_stmt->execute([$_SESSION['user_id']]);
        
    } catch (PDOException $e) {
        // Fail silently on header to prevent UI breakage, default to 0
        $unread_msg_count = 0;
        $unread_notif_count = 0;
    }
} else {
    $unread_notif_count = 0;
}

// Advanced feature: User preferences
$user_preferences = [];
if (isLoggedIn()) {
    try {
        $pref_query = "SELECT theme, language, notifications_enabled, sound_enabled FROM user_preferences WHERE user_id = ?";
        $pref_stmt = $db->prepare($pref_query);
        $pref_stmt->execute([$_SESSION['user_id']]);
        $user_preferences = $pref_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $user_preferences = [];
    }
}

// System status indicators
$system_status = [
    'maintenance_mode' => false,
    'new_features' => true,
    'urgent_notifications' => false
];

// Dynamic page title enhancement
$page_meta = [
    'description' => 'BNCC Market - Enterprise Student Marketplace',
    'keywords' => 'student, marketplace, BNCC, buy, sell, trade',
    'author' => 'BNCC Development Team'
];

if (isset($pageTitle)) {
    $page_meta['title'] = htmlspecialchars($pageTitle) . ' | BNCC Market';
} else {
    $page_meta['title'] = 'BNCC Market | แหล่งรวมสินค้าคุณภาพ';
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5" id="theme-color-meta">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="description" content="<?= $page_meta['description'] ?>">
    <meta name="keywords" content="<?= $page_meta['keywords'] ?>">
    <meta name="author" content="<?= $page_meta['author'] ?>">
    <meta property="og:title" content="<?= $page_meta['title'] ?>">
    <meta property="og:description" content="<?= $page_meta['description'] ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= $base_path ?>assets/images/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $page_meta['title'] ?>">
    <meta name="twitter:description" content="<?= $page_meta['description'] ?>">
    
    <title><?= $page_meta['title'] ?></title>
    
    <link rel="icon" type="image/png" href="<?= $base_path ?>assets/images/favicon.png">
    <link rel="apple-touch-icon" href="<?= $base_path ?>assets/images/apple-touch-icon.png">
    
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/style.css">

    <style>
        /* Global Design System & Variables */
        :root {
            /* Primary Colors */
            --bncc-primary-50: #eef2ff;
            --bncc-primary-100: #e0e7ff;
            --bncc-primary-200: #c7d2fe;
            --bncc-primary-300: #a5b4fc;
            --bncc-primary-400: #818cf8;
            --bncc-primary-500: #6366f1;
            --bncc-primary-600: #4f46e5;
            --bncc-primary-700: #4338ca;
            --bncc-primary-800: #3730a3;
            --bncc-primary-900: #312e81;
            
            /* Success Colors */
            --bncc-success-50: #ecfdf5;
            --bncc-success-100: #d1fae5;
            --bncc-success-400: #34d399;
            --bncc-success-500: #10b981;
            --bncc-success-600: #059669;
            
            /* Danger Colors */
            --bncc-danger-50: #fef2f2;
            --bncc-danger-100: #fee2e2;
            --bncc-danger-400: #f87171;
            --bncc-danger-500: #ef4444;
            --bncc-danger-600: #dc2626;
            
            /* Warning Colors */
            --bncc-warning-50: #fffbeb;
            --bncc-warning-100: #fef3c7;
            --bncc-warning-400: #fbbf24;
            --bncc-warning-500: #f59e0b;
            --bncc-warning-600: #d97706;

            /* Surface & Background Colors */
            --bncc-surface-light: #ffffff;
            --bncc-surface-dark: #0f172a;
            --bncc-background-light: #f8fafc; /* Changed to match previous UX */
            --bncc-background-dark: #020617;
            
            /* Text Colors */
            --bncc-text-primary-light: #0f172a;
            --bncc-text-secondary-light: #475569;
            --bncc-text-tertiary-light: #94a3b8;
            
            --bncc-text-primary-dark: #f8fafc;
            --bncc-text-secondary-dark: #cbd5e1;
            --bncc-text-tertiary-dark: #64748b;

            /* Border Colors */
            --bncc-border-light: #e2e8f0;
            --bncc-border-dark: #1e293b;
            
            /* Glassmorphism Tokens */
            --bncc-glass-bg-light: rgba(255, 255, 255, 0.75);
            --bncc-glass-bg-dark: rgba(15, 23, 42, 0.75);
            --bncc-glass-border-light: rgba(255, 255, 255, 0.4);
            --bncc-glass-border-dark: rgba(255, 255, 255, 0.05);
            --bncc-glass-blur: blur(24px) saturate(200%);

            /* Typography */
            --bncc-font-family: 'Prompt', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --bncc-font-xs: 0.75rem;
            --bncc-font-sm: 0.875rem;
            --bncc-font-base: 1rem;
            --bncc-font-lg: 1.125rem;
            --bncc-font-xl: 1.25rem;
            --bncc-font-2xl: 1.5rem;
            --bncc-font-3xl: 1.875rem;
            --bncc-font-4xl: 2.25rem;

            /* Shadows */
            --bncc-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --bncc-shadow-base: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --bncc-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --bncc-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --bncc-shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --bncc-shadow-glow: 0 0 20px rgba(79, 70, 229, 0.35);

            /* Z-Index Hierarchy */
            --bncc-z-under: -1;
            --bncc-z-base: 1;
            --bncc-z-dropdown: 1000;
            --bncc-z-sticky: 1020;
            --bncc-z-fixed: 1030;
            --bncc-z-modal-backdrop: 1040;
            --bncc-z-modal: 1050;
            --bncc-z-popover: 1060;
            --bncc-z-tooltip: 1070;
            --bncc-z-sidebar: 1100;
            --bncc-z-toast: 1200;

            /* Transitions & Animations */
            --bncc-ease-linear: cubic-bezier(0.0, 0.0, 1.0, 1.0);
            --bncc-ease-in: cubic-bezier(0.4, 0.0, 1.0, 1.0);
            --bncc-ease-out: cubic-bezier(0.0, 0.0, 0.2, 1.0);
            --bncc-ease-in-out: cubic-bezier(0.4, 0.0, 0.2, 1.0);
            --bncc-ease-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            --bncc-ease-elastic: cubic-bezier(0.175, 0.885, 0.32, 1.275);
            
            --bncc-duration-fast: 150ms;
            --bncc-duration-normal: 300ms;
            --bncc-duration-slow: 500ms;

            /* Layout Measurements */
            --bncc-header-height: 80px;
            --bncc-header-height-scrolled: 65px;
            --bncc-sidebar-width: 340px;
            --bncc-container-max: 1440px;
            --bncc-radius-sm: 0.375rem;
            --bncc-radius-md: 0.5rem;
            --bncc-radius-lg: 0.75rem;
            --bncc-radius-xl: 1rem;
            --bncc-radius-2xl: 1.5rem;
            --bncc-radius-full: 9999px;
        }

        /* Light Theme Assignment */
        html[data-theme="light"], :root {
            --theme-bg: var(--bncc-background-light);
            --theme-surface: var(--bncc-surface-light);
            --theme-text-primary: var(--bncc-text-primary-light);
            --theme-text-secondary: var(--bncc-text-secondary-light);
            --theme-text-tertiary: var(--bncc-text-tertiary-light);
            --theme-border: var(--bncc-border-light);
            --theme-glass-bg: var(--bncc-glass-bg-light);
            --theme-glass-border: var(--bncc-glass-border-light);
            --theme-shadow: var(--bncc-shadow-md);
            --theme-hover-bg: rgba(15, 23, 42, 0.04);
            --theme-input-bg: #f8fafc;
        }

        /* Dark Theme Assignment */
        html[data-theme="dark"], .dark-theme {
            --theme-bg: var(--bncc-background-dark);
            --theme-surface: var(--bncc-surface-dark);
            --theme-text-primary: var(--bncc-text-primary-dark);
            --theme-text-secondary: var(--bncc-text-secondary-dark);
            --theme-text-tertiary: var(--bncc-text-tertiary-dark);
            --theme-border: var(--bncc-border-dark);
            --theme-glass-bg: var(--bncc-glass-bg-dark);
            --theme-glass-border: var(--bncc-glass-border-dark);
            --theme-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5);
            --theme-hover-bg: rgba(255, 255, 255, 0.05);
            --theme-input-bg: #0f172a;
        }

        /* CSS Reset & Normalization */
        *, *::before, *::after {
            box-sizing: border-box;
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 0;
            margin-left: 0;
            padding-top: 0;
            padding-right: 0;
            padding-bottom: 0;
            padding-left: 0;
            border-width: 0;
            border-style: solid;
            border-color: transparent;
        }

        html {
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
            -moz-tab-size: 4;
            tab-size: 4;
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--bncc-font-family);
            background-color: var(--theme-bg);
            color: var(--theme-text-primary);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition-property: background-color, color;
            transition-duration: var(--bncc-duration-normal);
            transition-timing-function: var(--bncc-ease-in-out);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        body.noscroll {
            overflow: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
            background-color: transparent;
        }

        button, input, optgroup, select, textarea {
            font-family: inherit;
            font-size: 100%;
            line-height: 1.15;
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 0;
            margin-left: 0;
        }

        button, select {
            text-transform: none;
        }

        button, [type='button'], [type='reset'], [type='submit'] {
            -webkit-appearance: button;
            appearance: button;
            background-color: transparent;
            cursor: pointer;
        }

        ul, ol {
            list-style-type: none;
        }

        img, video, canvas, svg {
            display: block;
            max-width: 100%;
            height: auto;
        }

        /* Utility Classes Engine */
        .u-flex { display: flex; }
        .u-flex-col { flex-direction: column; }
        .u-items-center { align-items: center; }
        .u-justify-center { justify-content: center; }
        .u-justify-between { justify-content: space-between; }
        .u-w-full { width: 100%; }
        .u-h-full { height: 100%; }
        .u-relative { position: relative; }
        .u-absolute { position: absolute; }
        .u-hidden { display: none !important; }
        .u-block { display: block; }
        
        .u-text-center { text-align: center; }
        .u-text-left { text-align: left; }
        .u-text-right { text-align: right; }
        
        .u-font-bold { font-weight: 700; }
        .u-font-extrabold { font-weight: 800; }
        .u-font-black { font-weight: 900; }

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

        @keyframes bell-shake {
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
            filter: blur(40px);
        }

        .dark-theme .bg-particle {
            opacity: 0.1;
        }

        .bg-particle:nth-child(1) { width: 300px; height: 300px; top: -100px; left: -100px; animation-duration: 15s; }
        .bg-particle:nth-child(2) { width: 500px; height: 500px; bottom: -200px; right: -150px; animation-duration: 20s; animation-delay: -5s; background-color: var(--bncc-info-500); }
        .bg-particle:nth-child(3) { width: 200px; height: 200px; top: 40%; left: 60%; animation-duration: 12s; animation-delay: -2s; background-color: var(--bncc-success-400); }

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
            z-index: 2;
        }
        
        .notification-badge.animate-pop {
            animation: pulse-ring 2s infinite;
        }
        
        .bell-anim {
            animation: bell-shake 1s cubic-bezier(.36,.07,.19,.97) both;
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

        /* ============================================================
           SECTION 6: SIDEBAR DRAWER ARCHITECTURE (The Requested Feature)
           ============================================================ */
        
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
            padding-top: 1rem;
            padding-right: 1rem;
            padding-bottom: 1rem;
            padding-left: 1rem;
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

        /* ============================================================
           SECTION 7: ADVANCED NOTIFICATION DROPDOWN SYSTEM
           ============================================================ */
        
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

        /* ============================================================
           SECTION 8: RESPONSIVE MEDIA QUERIES
           ============================================================ */
        
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
        /**
         * SECTION 9: EARLY BLOCKING SCRIPTS
         * Theme Initialization - Executes before DOM renders to prevent flash of wrong theme
         */
        (function() {
            try {
                // Determine theme from localStorage or OS preference
                var sysTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                var savedTheme = localStorage.getItem('bncc_enterprise_theme');
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
<body>

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
                            <a href="#" class="notif-view-all-link">ดูการแจ้งเตือนทั้งหมด</a>
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
                        <a href="<?= $base_path ?>auth/register.php" class="btn-auth-register">สมัครสมาชิก</a>
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
        
        <a href="<?= $base_path ?>pages/wtb_board.php" class="nav-menu-item nav-item-warning <?= $current_page == 'wtb_board.php' ? 'is-active' : '' ?>">
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
                <a href="<?= $base_path ?>admin/admin_dashboard.php" class="nav-menu-item nav-item-admin">
                    <i class="fas fa-shield-halved nav-menu-icon"></i>
                    <span>ศูนย์ควบคุมระบบ (Admin Panel)</span>
                </a>
                <a href="<?= $base_path ?>admin/approve_product.php" class="nav-menu-item nav-item-admin">
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
     * MODULE 3: THEME MANAGER
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
            if(isDark) {
                this.html.setAttribute('data-theme', 'dark');
                localStorage.setItem('bncc_enterprise_theme', 'dark');
                if(this.metaTheme) this.metaTheme.setAttribute('content', '#111827');
            } else {
                this.html.setAttribute('data-theme', 'light');
                localStorage.setItem('bncc_enterprise_theme', 'light');
                if(this.metaTheme) this.metaTheme.setAttribute('content', '#ffffff');
            }
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
     * MODULE 5: AJAX NOTIFICATION ENGINE
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
        
        // 🎯 404 FIX: Using dynamic base path for AJAX calls
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
                    this.listView.innerHTML = `<div class="notif-empty-state"><i class="fas fa-exclamation-triangle text-danger"></i><span>ไม่สามารถโหลดการแจ้งเตือนได้</span></div>`;
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
                    
                    return `
                        <a href="${notif.link || '#'}" class="notif-entity-card ${stateClass}">
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