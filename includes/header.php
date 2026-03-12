<?php
require_once __DIR__ . '/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$hide_home_list = ['login.php', 'register.php', 'register_google.php', 'verify_otp.php' , 'appeal_ban.php'];
$hide_auth_list = ['index.php', 'register_seller.php', 'product_detail.php','login.php', 'register.php', 'register_google.php', 'verify_otp.php'];

$user_avatar = isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img']) 
                ? "../assets/images/profiles/" . $_SESSION['profile_img'] 
                : "../assets/images/profiles/default_profile.png";

$unread_msg_count = 0;
if (isLoggedIn()) {
    $db = getDB();
    $msg_stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $msg_stmt->execute([$_SESSION['user_id']]);
    $unread_msg_count = $msg_stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $pageTitle ?? 'BNCC Market'; ?></title>
    <link rel="icon" type="image/png" href="<?= defined('BASE_URL') ? BASE_URL : '/s673190104/student_marketplace/' ?>assets/images/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

    <style>
        :root {
            --nav-height-desktop: 80px;
            --nav-height-mobile: 70px;
            --sidebar-width: 320px;
            --glass-bg-light: rgba(255, 255, 255, 0.85);
            --glass-bg-dark: rgba(15, 23, 42, 0.85);
            --glass-border-light: rgba(255, 255, 255, 0.3);
            --glass-border-dark: rgba(255, 255, 255, 0.05);
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --primary-glow: rgba(79, 70, 229, 0.5);
            --danger-color: #ef4444;
            --danger-hover: #dc2626;
            --danger-glow: rgba(239, 68, 68, 0.5);
            --warning-color: #f59e0b;
            --warning-hover: #d97706;
            --success-color: #10b981;
            --success-hover: #059669;
            --text-dark-main: #0f172a;
            --text-dark-sub: #475569;
            --text-light-main: #f8fafc;
            --text-light-sub: #cbd5e1;
            --bg-light-main: #f1f5f9;
            --bg-dark-main: #0b0f19;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-glow-primary: 0 0 15px var(--primary-glow);
            --shadow-glow-danger: 0 0 15px var(--danger-glow);
            --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-bounce: 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            --z-index-navbar: 1000;
            --z-index-overlay: 1050;
            --z-index-sidebar: 1100;
            --z-index-dropdown: 1200;
            --z-index-tooltip: 1300;
        }

        html {
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
            -moz-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        body {
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 0;
            margin-left: 0;
            padding-top: 0;
            padding-right: 0;
            padding-bottom: 0;
            padding-left: 0;
            font-family: 'Prompt', sans-serif;
            background-color: var(--bg-light-main);
            color: var(--text-dark-main);
            -webkit-transition: background-color var(--transition-normal);
            -moz-transition: background-color var(--transition-normal);
            -ms-transition: background-color var(--transition-normal);
            -o-transition: background-color var(--transition-normal);
            transition: background-color var(--transition-normal);
        }

        body.dark-theme {
            background-color: var(--bg-dark-main);
            color: var(--text-light-main);
        }

        body.sidebar-open {
            overflow: hidden;
            touch-action: none;
        }

        .master-header {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--nav-height-desktop);
            background-color: var(--glass-bg-light);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-bottom-color: var(--glass-border-light);
            z-index: var(--z-index-navbar);
            -webkit-transition: height var(--transition-normal), background-color var(--transition-normal), -webkit-box-shadow var(--transition-normal);
            -moz-transition: height var(--transition-normal), background-color var(--transition-normal), -moz-box-shadow var(--transition-normal);
            transition: height var(--transition-normal), background-color var(--transition-normal), box-shadow var(--transition-normal);
            -webkit-box-shadow: var(--shadow-sm);
            -moz-box-shadow: var(--shadow-sm);
            box-shadow: var(--shadow-sm);
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
        }

        .dark-theme .master-header {
            background-color: var(--glass-bg-dark);
            border-bottom-color: var(--glass-border-dark);
        }

        .master-header.header-scrolled {
            height: var(--nav-height-mobile);
            -webkit-box-shadow: var(--shadow-md);
            -moz-box-shadow: var(--shadow-md);
            box-shadow: var(--shadow-md);
        }

        .header-cyber-line {
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-image: -webkit-linear-gradient(left, transparent 0%, var(--primary-color) 25%, #a855f7 50%, var(--primary-color) 75%, transparent 100%);
            background-image: -moz-linear-gradient(left, transparent 0%, var(--primary-color) 25%, #a855f7 50%, var(--primary-color) 75%, transparent 100%);
            background-image: -ms-linear-gradient(left, transparent 0%, var(--primary-color) 25%, #a855f7 50%, var(--primary-color) 75%, transparent 100%);
            background-image: -o-linear-gradient(left, transparent 0%, var(--primary-color) 25%, #a855f7 50%, var(--primary-color) 75%, transparent 100%);
            background-image: linear-gradient(90deg, transparent 0%, var(--primary-color) 25%, #a855f7 50%, var(--primary-color) 75%, transparent 100%);
            background-size: 200% 100%;
            -webkit-animation: cyberLineGlow 4s linear infinite;
            -moz-animation: cyberLineGlow 4s linear infinite;
            animation: cyberLineGlow 4s linear infinite;
            opacity: 0.8;
            pointer-events: none;
        }

        @-webkit-keyframes cyberLineGlow {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        @-moz-keyframes cyberLineGlow {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        @keyframes cyberLineGlow {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        .header-container {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-pack: justify;
            -ms-flex-pack: justify;
            justify-content: space-between;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            width: 100%;
            max-width: 1400px;
            margin-top: 0;
            margin-right: auto;
            margin-bottom: 0;
            margin-left: auto;
            padding-top: 0;
            padding-right: 24px;
            padding-bottom: 0;
            padding-left: 24px;
        }

        .header-left-zone {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            gap: 16px;
        }

        .hamburger-btn {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -ms-flex-direction: column;
            flex-direction: column;
            -webkit-box-pack: center;
            -ms-flex-pack: center;
            justify-content: center;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            width: 45px;
            height: 45px;
            background-color: transparent;
            border-top-width: 0;
            border-right-width: 0;
            border-bottom-width: 0;
            border-left-width: 0;
            cursor: pointer;
            padding-top: 0;
            padding-right: 0;
            padding-bottom: 0;
            padding-left: 0;
            z-index: calc(var(--z-index-sidebar) + 10);
            -webkit-transition: -webkit-transform var(--transition-normal);
            transition: transform var(--transition-normal);
            border-radius: var(--radius-md);
        }

        .hamburger-btn:hover {
            background-color: rgba(156, 163, 175, 0.1);
        }

        .hamburger-line {
            display: block;
            width: 24px;
            height: 2.5px;
            background-color: var(--text-dark-main);
            border-radius: 4px;
            -webkit-transition: var(--transition-bounce);
            -moz-transition: var(--transition-bounce);
            -ms-transition: var(--transition-bounce);
            -o-transition: var(--transition-bounce);
            transition: var(--transition-bounce);
            -webkit-transform-origin: center right;
            -moz-transform-origin: center right;
            -ms-transform-origin: center right;
            -o-transform-origin: center right;
            transform-origin: center right;
        }

        .dark-theme .hamburger-line {
            background-color: var(--text-light-main);
        }

        .hamburger-line:nth-child(1) {
            margin-bottom: 5px;
        }

        .hamburger-line:nth-child(2) {
            margin-bottom: 5px;
        }

        body.sidebar-open .hamburger-line:nth-child(1) {
            -webkit-transform: translateY(-8px) rotate(-45deg);
            -moz-transform: translateY(-8px) rotate(-45deg);
            -ms-transform: translateY(-8px) rotate(-45deg);
            -o-transform: translateY(-8px) rotate(-45deg);
            transform: translateY(-8px) rotate(-45deg);
            width: 26px;
        }

        body.sidebar-open .hamburger-line:nth-child(2) {
            opacity: 0;
            -webkit-transform: translateX(10px);
            -moz-transform: translateX(10px);
            -ms-transform: translateX(10px);
            -o-transform: translateX(10px);
            transform: translateX(10px);
        }

        body.sidebar-open .hamburger-line:nth-child(3) {
            -webkit-transform: translateY(8px) rotate(45deg);
            -moz-transform: translateY(8px) rotate(45deg);
            -ms-transform: translateY(8px) rotate(45deg);
            -o-transform: translateY(8px) rotate(45deg);
            transform: translateY(8px) rotate(45deg);
            width: 26px;
        }

        .brand-logo {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            -webkit-transition: var(--transition-bounce);
            -moz-transition: var(--transition-bounce);
            -ms-transition: var(--transition-bounce);
            -o-transition: var(--transition-bounce);
            transition: var(--transition-bounce);
        }

        .brand-logo:hover {
            -webkit-transform: scale(1.03);
            -moz-transform: scale(1.03);
            -ms-transform: scale(1.03);
            -o-transform: scale(1.03);
            transform: scale(1.03);
        }

        .brand-icon-wrapper {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-justify: center;
            -ms-flex-justify: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-image: -webkit-linear-gradient(135deg, var(--primary-color) 0%, #a855f7 100%);
            background-image: -moz-linear-gradient(135deg, var(--primary-color) 0%, #a855f7 100%);
            background-image: -ms-linear-gradient(135deg, var(--primary-color) 0%, #a855f7 100%);
            background-image: -o-linear-gradient(135deg, var(--primary-color) 0%, #a855f7 100%);
            background-image: linear-gradient(135deg, var(--primary-color) 0%, #a855f7 100%);
            border-radius: 12px;
            color: #ffffff;
            font-size: 1.2rem;
            -webkit-box-shadow: var(--shadow-glow-primary);
            -moz-box-shadow: var(--shadow-glow-primary);
            box-shadow: var(--shadow-glow-primary);
            -webkit-transition: -webkit-transform var(--transition-bounce);
            transition: transform var(--transition-bounce);
        }

        .brand-logo:hover .brand-icon-wrapper {
            -webkit-transform: rotate(-10deg) scale(1.1);
            -moz-transform: rotate(-10deg) scale(1.1);
            -ms-transform: rotate(-10deg) scale(1.1);
            -o-transform: rotate(-10deg) scale(1.1);
            transform: rotate(-10deg) scale(1.1);
        }

        .brand-text {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-dark-main);
            letter-spacing: -0.5px;
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 0;
            margin-left: 0;
        }

        .dark-theme .brand-text {
            color: var(--text-light-main);
        }

        .header-right-zone {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            gap: 12px;
        }

        .quick-action-btn {
            position: relative;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-justify: center;
            -ms-flex-justify: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: transparent;
            color: var(--text-dark-sub);
            font-size: 1.25rem;
            border-top-width: 0;
            border-right-width: 0;
            border-bottom-width: 0;
            border-left-width: 0;
            cursor: pointer;
            -webkit-transition: var(--transition-normal);
            -moz-transition: var(--transition-normal);
            -ms-transition: var(--transition-normal);
            -o-transition: var(--transition-normal);
            transition: var(--transition-normal);
            text-decoration: none;
        }

        .dark-theme .quick-action-btn {
            color: var(--text-light-sub);
        }

        .quick-action-btn:hover {
            background-color: rgba(156, 163, 175, 0.15);
            color: var(--primary-color);
            -webkit-transform: translateY(-2px);
            -moz-transform: translateY(-2px);
            -ms-transform: translateY(-2px);
            -o-transform: translateY(-2px);
            transform: translateY(-2px);
        }

        .indicator-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background-image: -webkit-linear-gradient(135deg, var(--danger-color) 0%, #991b1b 100%);
            background-image: -moz-linear-gradient(135deg, var(--danger-color) 0%, #991b1b 100%);
            background-image: -ms-linear-gradient(135deg, var(--danger-color) 0%, #991b1b 100%);
            background-image: -o-linear-gradient(135deg, var(--danger-color) 0%, #991b1b 100%);
            background-image: linear-gradient(135deg, var(--danger-color) 0%, #991b1b 100%);
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 800;
            min-width: 18px;
            height: 18px;
            border-radius: 10px;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-justify: center;
            -ms-flex-justify: center;
            justify-content: center;
            border-top-width: 2px;
            border-right-width: 2px;
            border-bottom-width: 2px;
            border-left-width: 2px;
            border-top-style: solid;
            border-right-style: solid;
            border-bottom-style: solid;
            border-left-style: solid;
            border-top-color: var(--bg-light-main);
            border-right-color: var(--bg-light-main);
            border-bottom-color: var(--bg-light-main);
            border-left-color: var(--bg-light-main);
            -webkit-box-shadow: var(--shadow-glow-danger);
            -moz-box-shadow: var(--shadow-glow-danger);
            box-shadow: var(--shadow-glow-danger);
            pointer-events: none;
            -webkit-animation: badgePulse 2s infinite;
            -moz-animation: badgePulse 2s infinite;
            animation: badgePulse 2s infinite;
        }

        .dark-theme .indicator-badge {
            border-color: var(--bg-dark-main);
        }

        @-webkit-keyframes badgePulse {
            0% { -webkit-box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); transform: scale(1); }
            50% { -webkit-box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); transform: scale(1.1); }
            100% { -webkit-box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); transform: scale(1); }
        }

        @-moz-keyframes badgePulse {
            0% { -moz-box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); transform: scale(1); }
            50% { -moz-box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); transform: scale(1.1); }
            100% { -moz-box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); transform: scale(1); }
        }

        @keyframes badgePulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); transform: scale(1); }
            50% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); transform: scale(1.1); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); transform: scale(1); }
        }

        .user-mini-profile {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            gap: 10px;
            padding-top: 4px;
            padding-right: 12px;
            padding-bottom: 4px;
            padding-left: 4px;
            border-radius: 50px;
            background-color: rgba(156, 163, 175, 0.1);
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            border-left-width: 1px;
            border-top-style: solid;
            border-right-style: solid;
            border-bottom-style: solid;
            border-left-style: solid;
            border-top-color: transparent;
            border-right-color: transparent;
            border-bottom-color: transparent;
            border-left-color: transparent;
            text-decoration: none;
            -webkit-transition: var(--transition-normal);
            -moz-transition: var(--transition-normal);
            -ms-transition: var(--transition-normal);
            -o-transition: var(--transition-normal);
            transition: var(--transition-normal);
        }

        .user-mini-profile:hover {
            background-color: rgba(156, 163, 175, 0.2);
            border-color: var(--primary-glow);
        }

        .mini-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border-top-width: 2px;
            border-right-width: 2px;
            border-bottom-width: 2px;
            border-left-width: 2px;
            border-top-style: solid;
            border-right-style: solid;
            border-bottom-style: solid;
            border-left-style: solid;
            border-top-color: var(--primary-color);
            border-right-color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            border-left-color: var(--primary-color);
        }

        .mini-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-dark-main);
            max-width: 100px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark-theme .mini-name {
            color: var(--text-light-main);
        }

        .auth-buttons-group {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            gap: 12px;
        }

        .auth-btn-outline {
            padding-top: 10px;
            padding-right: 20px;
            padding-bottom: 10px;
            padding-left: 20px;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            color: var(--primary-color);
            background-color: transparent;
            border-top-width: 2px;
            border-right-width: 2px;
            border-bottom-width: 2px;
            border-left-width: 2px;
            border-top-style: solid;
            border-right-style: solid;
            border-bottom-style: solid;
            border-left-style: solid;
            border-top-color: var(--primary-color);
            border-right-color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            border-left-color: var(--primary-color);
            -webkit-transition: var(--transition-normal);
            -moz-transition: var(--transition-normal);
            -ms-transition: var(--transition-normal);
            -o-transition: var(--transition-normal);
            transition: var(--transition-normal);
        }

        .auth-btn-outline:hover {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        .auth-btn-solid {
            padding-top: 10px;
            padding-right: 20px;
            padding-bottom: 10px;
            padding-left: 20px;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            color: #ffffff;
            background-image: -webkit-linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            background-image: -moz-linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            background-image: -ms-linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            background-image: -o-linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            background-image: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            border-top-width: 0;
            border-right-width: 0;
            border-bottom-width: 0;
            border-left-width: 0;
            -webkit-box-shadow: var(--shadow-md);
            -moz-box-shadow: var(--shadow-md);
            box-shadow: var(--shadow-md);
            -webkit-transition: var(--transition-normal);
            -moz-transition: var(--transition-normal);
            -ms-transition: var(--transition-normal);
            -o-transition: var(--transition-normal);
            transition: var(--transition-normal);
        }

        .auth-btn-solid:hover {
            -webkit-transform: translateY(-2px);
            -moz-transform: translateY(-2px);
            -ms-transform: translateY(-2px);
            -o-transform: translateY(-2px);
            transform: translateY(-2px);
            -webkit-box-shadow: var(--shadow-glow-primary);
            -moz-box-shadow: var(--shadow-glow-primary);
            box-shadow: var(--shadow-glow-primary);
        }

        /* ============================================================
           SIDEBAR DRAWER (OFF-CANVAS MENU) - The Core Request
           ============================================================ */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.6);
            -webkit-backdrop-filter: blur(4px);
            backdrop-filter: blur(4px);
            z-index: var(--z-index-overlay);
            opacity: 0;
            visibility: hidden;
            -webkit-transition: opacity var(--transition-normal), visibility var(--transition-normal);
            -moz-transition: opacity var(--transition-normal), visibility var(--transition-normal);
            -ms-transition: opacity var(--transition-normal), visibility var(--transition-normal);
            -o-transition: opacity var(--transition-normal), visibility var(--transition-normal);
            transition: opacity var(--transition-normal), visibility var(--transition-normal);
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .sidebar-drawer {
            position: fixed;
            top: 0;
            left: -100%;
            width: var(--sidebar-width);
            max-width: 85vw;
            height: 100vh;
            background-color: var(--card-bg, #ffffff);
            z-index: var(--z-index-sidebar);
            -webkit-box-shadow: var(--shadow-xl);
            -moz-box-shadow: var(--shadow-xl);
            box-shadow: var(--shadow-xl);
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -ms-flex-direction: column;
            flex-direction: column;
            -webkit-transition: left var(--transition-bounce);
            -moz-transition: left var(--transition-bounce);
            -ms-transition: left var(--transition-bounce);
            -o-transition: left var(--transition-bounce);
            transition: left var(--transition-bounce);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .dark-theme .sidebar-drawer {
            background-color: var(--glass-bg-dark);
            border-right-width: 1px;
            border-right-style: solid;
            border-right-color: var(--glass-border-dark);
        }

        .sidebar-drawer.active {
            left: 0;
        }

        .sidebar-header {
            padding-top: 30px;
            padding-right: 24px;
            padding-bottom: 20px;
            padding-left: 24px;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -ms-flex-direction: column;
            flex-direction: column;
            gap: 20px;
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-bottom-color: var(--border-color, #e2e8f0);
            position: relative;
        }

        .dark-theme .sidebar-header {
            border-bottom-color: var(--glass-border-dark);
        }

        .sidebar-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(156, 163, 175, 0.1);
            color: var(--text-dark-sub);
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-justify: center;
            -ms-flex-justify: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            border-top-width: 0;
            border-right-width: 0;
            border-bottom-width: 0;
            border-left-width: 0;
            -webkit-transition: var(--transition-normal);
            -moz-transition: var(--transition-normal);
            -ms-transition: var(--transition-normal);
            -o-transition: var(--transition-normal);
            transition: var(--transition-normal);
        }

        .dark-theme .sidebar-close-btn {
            color: var(--text-light-sub);
        }

        .sidebar-close-btn:hover {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            -webkit-transform: rotate(90deg);
            -moz-transform: rotate(90deg);
            -ms-transform: rotate(90deg);
            -o-transform: rotate(90deg);
            transform: rotate(90deg);
        }

        .sidebar-profile-card {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            gap: 16px;
        }

        .sidebar-avatar-large {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border-top-width: 3px;
            border-right-width: 3px;
            border-bottom-width: 3px;
            border-left-width: 3px;
            border-top-style: solid;
            border-right-style: solid;
            border-bottom-style: solid;
            border-left-style: solid;
            border-top-color: var(--primary-color);
            border-right-color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            border-left-color: var(--primary-color);
            padding-top: 3px;
            padding-right: 3px;
            padding-bottom: 3px;
            padding-left: 3px;
            background-color: var(--bg-light-main);
            -webkit-box-shadow: var(--shadow-md);
            -moz-box-shadow: var(--shadow-md);
            box-shadow: var(--shadow-md);
        }

        .dark-theme .sidebar-avatar-large {
            background-color: var(--bg-dark-main);
        }

        .sidebar-profile-info {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -ms-flex-direction: column;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar-profile-name {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-dark-main);
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 0;
            margin-left: 0;
            line-height: 1.2;
        }

        .dark-theme .sidebar-profile-name {
            color: var(--text-light-main);
        }

        .sidebar-role-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-top: 3px;
            padding-right: 8px;
            padding-bottom: 3px;
            padding-left: 8px;
            border-radius: 6px;
            width: -webkit-fit-content;
            width: -moz-fit-content;
            width: fit-content;
        }

        .role-admin { background-color: rgba(245, 158, 11, 0.15); color: var(--warning-color); border-top-width: 1px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px; border-top-style: solid; border-right-style: solid; border-bottom-style: solid; border-left-style: solid; border-top-color: rgba(245, 158, 11, 0.3); border-right-color: rgba(245, 158, 11, 0.3); border-bottom-color: rgba(245, 158, 11, 0.3); border-left-color: rgba(245, 158, 11, 0.3); }
        .role-teacher { background-color: rgba(239, 68, 68, 0.15); color: var(--danger-color); border-top-width: 1px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px; border-top-style: solid; border-right-style: solid; border-bottom-style: solid; border-left-style: solid; border-top-color: rgba(239, 68, 68, 0.3); border-right-color: rgba(239, 68, 68, 0.3); border-bottom-color: rgba(239, 68, 68, 0.3); border-left-color: rgba(239, 68, 68, 0.3); }
        .role-seller { background-color: rgba(16, 185, 129, 0.15); color: var(--success-color); border-top-width: 1px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px; border-top-style: solid; border-right-style: solid; border-bottom-style: solid; border-left-style: solid; border-top-color: rgba(16, 185, 129, 0.3); border-right-color: rgba(16, 185, 129, 0.3); border-bottom-color: rgba(16, 185, 129, 0.3); border-left-color: rgba(16, 185, 129, 0.3); }
        .role-buyer { background-color: rgba(79, 70, 229, 0.15); color: var(--primary-color); border-top-width: 1px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px; border-top-style: solid; border-right-style: solid; border-bottom-style: solid; border-left-style: solid; border-top-color: rgba(79, 70, 229, 0.3); border-right-color: rgba(79, 70, 229, 0.3); border-bottom-color: rgba(79, 70, 229, 0.3); border-left-color: rgba(79, 70, 229, 0.3); }

        .sidebar-menu-list {
            padding-top: 20px;
            padding-right: 16px;
            padding-bottom: 20px;
            padding-left: 16px;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -ms-flex-direction: column;
            flex-direction: column;
            gap: 8px;
            list-style-type: none;
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 0;
            margin-left: 0;
            -webkit-box-flex: 1;
            -ms-flex-positive: 1;
            flex-grow: 1;
        }

        .sidebar-menu-label {
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-top: 15px;
            padding-right: 12px;
            padding-bottom: 5px;
            padding-left: 12px;
            margin-top: 5px;
            margin-right: 0;
            margin-bottom: 0;
            margin-left: 0;
        }

        .sidebar-link {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            gap: 16px;
            padding-top: 14px;
            padding-right: 16px;
            padding-bottom: 14px;
            padding-left: 16px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--text-dark-main);
            font-weight: 600;
            font-size: 0.95rem;
            -webkit-transition: var(--transition-normal);
            -moz-transition: var(--transition-normal);
            -ms-transition: var(--transition-normal);
            -o-transition: var(--transition-normal);
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .dark-theme .sidebar-link {
            color: var(--text-light-main);
        }

        .sidebar-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
            color: var(--text-dark-sub);
            -webkit-transition: var(--transition-normal);
            -moz-transition: var(--transition-normal);
            -ms-transition: var(--transition-normal);
            -o-transition: var(--transition-normal);
            transition: var(--transition-normal);
        }

        .dark-theme .sidebar-link i {
            color: var(--text-light-sub);
        }

        .sidebar-link:hover {
            background-color: rgba(79, 70, 229, 0.08);
            color: var(--primary-color);
            -webkit-transform: translateX(5px);
            -moz-transform: translateX(5px);
            -ms-transform: translateX(5px);
            -o-transform: translateX(5px);
            transform: translateX(5px);
        }

        .sidebar-link:hover i {
            color: var(--primary-color);
            -webkit-transform: scale(1.1);
            -moz-transform: scale(1.1);
            -ms-transform: scale(1.1);
            -o-transform: scale(1.1);
            transform: scale(1.1);
        }

        .sidebar-link.admin-link {
            background-color: rgba(239, 68, 68, 0.05);
            color: var(--danger-color);
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            border-left-width: 1px;
            border-top-style: solid;
            border-right-style: solid;
            border-bottom-style: solid;
            border-left-style: solid;
            border-top-color: rgba(239, 68, 68, 0.2);
            border-right-color: rgba(239, 68, 68, 0.2);
            border-bottom-color: rgba(239, 68, 68, 0.2);
            border-left-color: rgba(239, 68, 68, 0.2);
        }

        .sidebar-link.admin-link i {
            color: var(--danger-color);
        }

        .sidebar-link.admin-link:hover {
            background-color: rgba(239, 68, 68, 0.15);
            -webkit-box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
            -moz-box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
        }

        .sidebar-badge-inline {
            margin-left: auto;
            background-image: -webkit-linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);
            background-image: -moz-linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);
            background-image: -ms-linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);
            background-image: -o-linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);
            background-image: linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 800;
            padding-top: 2px;
            padding-right: 8px;
            padding-bottom: 2px;
            padding-left: 8px;
            border-radius: 20px;
            -webkit-box-shadow: var(--shadow-glow-danger);
            -moz-box-shadow: var(--shadow-glow-danger);
            box-shadow: var(--shadow-glow-danger);
        }

        .sidebar-footer {
            padding-top: 20px;
            padding-right: 24px;
            padding-bottom: 30px;
            padding-left: 24px;
            border-top-width: 1px;
            border-top-style: solid;
            border-top-color: var(--border-color, #e2e8f0);
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -ms-flex-direction: column;
            flex-direction: column;
            gap: 12px;
        }

        .dark-theme .sidebar-footer {
            border-top-color: var(--glass-border-dark);
        }

        .logout-btn-full {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-pack: center;
            -ms-flex-pack: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding-top: 14px;
            padding-right: 16px;
            padding-bottom: 14px;
            padding-left: 16px;
            border-radius: var(--radius-sm);
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            font-weight: 800;
            text-decoration: none;
            -webkit-transition: var(--transition-normal);
            -moz-transition: var(--transition-normal);
            -ms-transition: var(--transition-normal);
            -o-transition: var(--transition-normal);
            transition: var(--transition-normal);
        }

        .logout-btn-full:hover {
            background-color: var(--danger-color);
            color: #ffffff;
            -webkit-box-shadow: var(--shadow-glow-danger);
            -moz-box-shadow: var(--shadow-glow-danger);
            box-shadow: var(--shadow-glow-danger);
        }

        /* Notifications Dropdown (Restyled for integration) */
        .notif-dropdown-wrapper {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 380px;
            background-color: var(--card-bg, #ffffff);
            border-top-width: 1px;
            border-right-width: 1px;
            border-bottom-width: 1px;
            border-left-width: 1px;
            border-top-style: solid;
            border-right-style: solid;
            border-bottom-style: solid;
            border-left-style: solid;
            border-top-color: var(--border-color, #e2e8f0);
            border-right-color: var(--border-color, #e2e8f0);
            border-bottom-color: var(--border-color, #e2e8f0);
            border-left-color: var(--border-color, #e2e8f0);
            border-radius: var(--radius-lg);
            -webkit-box-shadow: var(--shadow-xl);
            -moz-box-shadow: var(--shadow-xl);
            box-shadow: var(--shadow-xl);
            display: none;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -ms-flex-direction: column;
            flex-direction: column;
            z-index: var(--z-index-dropdown);
            -webkit-animation: dropScaleIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            -moz-animation: dropScaleIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation: dropScaleIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            -webkit-transform-origin: top right;
            -moz-transform-origin: top right;
            -ms-transform-origin: top right;
            -o-transform-origin: top right;
            transform-origin: top right;
        }

        .dark-theme .notif-dropdown-wrapper {
            background-color: var(--glass-bg-dark);
            -webkit-backdrop-filter: blur(20px);
            backdrop-filter: blur(20px);
            border-color: var(--glass-border-dark);
        }

        .notif-dropdown-wrapper.is-open {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
        }

        @-webkit-keyframes dropScaleIn {
            0% { opacity: 0; -webkit-transform: scale(0.9) translateY(-10px); transform: scale(0.9) translateY(-10px); }
            100% { opacity: 1; -webkit-transform: scale(1) translateY(0); transform: scale(1) translateY(0); }
        }

        @-moz-keyframes dropScaleIn {
            0% { opacity: 0; -moz-transform: scale(0.9) translateY(-10px); transform: scale(0.9) translateY(-10px); }
            100% { opacity: 1; -moz-transform: scale(1) translateY(0); transform: scale(1) translateY(0); }
        }

        @keyframes dropScaleIn {
            0% { opacity: 0; -webkit-transform: scale(0.9) translateY(-10px); -moz-transform: scale(0.9) translateY(-10px); -ms-transform: scale(0.9) translateY(-10px); -o-transform: scale(0.9) translateY(-10px); transform: scale(0.9) translateY(-10px); }
            100% { opacity: 1; -webkit-transform: scale(1) translateY(0); -moz-transform: scale(1) translateY(0); -ms-transform: scale(1) translateY(0); -o-transform: scale(1) translateY(0); transform: scale(1) translateY(0); }
        }

        .notif-head {
            padding-top: 16px;
            padding-right: 20px;
            padding-bottom: 16px;
            padding-left: 20px;
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-bottom-color: var(--border-color, #e2e8f0);
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-pack: justify;
            -ms-flex-pack: justify;
            justify-content: space-between;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
        }

        .dark-theme .notif-head {
            border-bottom-color: var(--glass-border-dark);
        }

        .notif-head h5 {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-dark-main);
        }

        .dark-theme .notif-head h5 { color: var(--text-light-main); }

        .notif-read-all {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
        }

        .notif-read-all:hover { text-decoration: underline; }

        .notif-scroll-area {
            max-height: 400px;
            overflow-y: auto;
            padding-top: 8px;
            padding-right: 8px;
            padding-bottom: 8px;
            padding-left: 8px;
        }

        .notif-item-card {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            gap: 15px;
            padding-top: 16px;
            padding-right: 16px;
            padding-bottom: 16px;
            padding-left: 16px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--text-dark-main);
            -webkit-transition: var(--transition-normal);
            -moz-transition: var(--transition-normal);
            -ms-transition: var(--transition-normal);
            -o-transition: var(--transition-normal);
            transition: var(--transition-normal);
            margin-bottom: 4px;
        }

        .dark-theme .notif-item-card { color: var(--text-light-main); }

        .notif-item-card:hover {
            background-color: rgba(156, 163, 175, 0.1);
            -webkit-transform: translateX(4px);
            -moz-transform: translateX(4px);
            -ms-transform: translateX(4px);
            -o-transform: translateX(4px);
            transform: translateX(4px);
        }

        .notif-item-card.unread-status {
            background-color: rgba(79, 70, 229, 0.05);
            border-left-width: 4px;
            border-left-style: solid;
            border-left-color: var(--primary-color);
        }

        .notif-item-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            width: 40px;
            height: 40px;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-justify: center;
            -ms-flex-justify: center;
            justify-content: center;
            background-color: rgba(79, 70, 229, 0.1);
            border-radius: 50%;
            -ms-flex-negative: 0;
            flex-shrink: 0;
        }

        .notif-item-content {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -ms-flex-direction: column;
            flex-direction: column;
            gap: 4px;
        }

        .notif-item-msg {
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .unread-status .notif-item-msg { font-weight: 700; }

        .notif-item-time {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Mobile adjustments for specific topbar elements */
        @media (max-width: 768px) {
            .user-mini-profile .mini-name { display: none; }
            .user-mini-profile { padding: 0; background: transparent; border: none; }
            .notif-dropdown-wrapper {
                position: fixed;
                top: var(--nav-height-mobile);
                right: 10px;
                left: 10px;
                width: auto;
                max-width: none;
            }
        }
    </style>

    <script>
        // Theme initialization logic to prevent flickering
        (function() {
            var savedTheme = localStorage.getItem('bncc_theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark-theme');
                document.body.classList.add('dark-theme');
            }
        })();
    </script>
</head>
<body>

<nav class="master-header" id="mainNavbar">
    <div class="header-container">
        
        <div class="header-left-zone">
            <button class="hamburger-btn" id="menuToggleBtn" aria-label="Toggle Navigation Menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>

            <a href="../pages/index.php" class="brand-logo">
                <div class="brand-icon-wrapper">
                    <i class="fas fa-store"></i>
                </div>
                <h1 class="brand-text">BNCC Market</h1>
            </a>
        </div>

        <div class="header-right-zone">
            
            <button id="themeToggleBtn" class="quick-action-btn" aria-label="Toggle Dark Mode">
                <i class="fas fa-moon" id="themeIconState"></i>
            </button>

            <?php if (isLoggedIn()): ?>
                
                <div style="position: relative;">
                    <button class="quick-action-btn" id="notifToggleBtn" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <span id="globalNotifBadge" class="indicator-badge" style="display: none;">0</span>
                    </button>

                    <div class="notif-dropdown-wrapper" id="notifDropdownPanel">
                        <div class="notif-head">
                            <h5>การแจ้งเตือน</h5>
                            <button class="notif-read-all" onclick="executeMarkAllRead()">อ่านทั้งหมด</button>
                        </div>
                        <div class="notif-scroll-area" id="notifContentList">
                            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="../pages/chat.php" class="quick-action-btn" aria-label="Messages">
                    <i class="fas fa-comment-dots"></i>
                    <?php if($unread_msg_count > 0): ?>
                        <span class="indicator-badge"><?= $unread_msg_count > 99 ? '99+' : $unread_msg_count ?></span>
                    <?php endif; ?>
                </a>

                <a href="../pages/profile.php" class="user-mini-profile">
                    <img src="<?= $user_avatar ?>" alt="Profile" class="mini-avatar">
                    <span class="mini-name"><?= htmlspecialchars($_SESSION['fullname']) ?></span>
                </a>

            <?php else: ?>
                <?php if (!in_array($current_page, $hide_auth_list)): ?>
                    <div class="auth-buttons-group">
                        <a href="../auth/login.php" class="auth-btn-outline">เข้าสู่ระบบ</a>
                        <a href="../auth/register.php" class="auth-btn-solid">ลงทะเบียน</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
    <div class="header-cyber-line"></div>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar-drawer" id="sidebarMenu">
    
    <div class="sidebar-header">
        <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close Menu">
            <i class="fas fa-times"></i>
        </button>

        <?php if (isLoggedIn()): ?>
            <div class="sidebar-profile-card">
                <img src="<?= $user_avatar ?>" alt="Profile" class="sidebar-avatar-large">
                <div class="sidebar-profile-info">
                    <h3 class="sidebar-profile-name"><?= htmlspecialchars($_SESSION['fullname']) ?></h3>
                    
                    <?php 
                        $role_class = 'role-buyer';
                        $role_text = 'ผู้ซื้อทั่วไป';
                        if ($_SESSION['role'] === 'admin') { $role_class = 'role-admin'; $role_text = 'ผู้ดูแลระบบ (Admin)'; }
                        elseif ($_SESSION['role'] === 'teacher') { $role_class = 'role-teacher'; $role_text = 'อาจารย์ (Master)'; }
                        elseif ($_SESSION['role'] === 'seller') { $role_class = 'role-seller'; $role_text = 'ร้านค้า (Seller)'; }
                    ?>
                    <span class="sidebar-role-badge <?= $role_class ?>">
                        <?= $role_text ?>
                    </span>
                </div>
            </div>
        <?php else: ?>
            <div class="sidebar-profile-card">
                <div class="sidebar-avatar-large" style="display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--text-muted);">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="sidebar-profile-info">
                    <h3 class="sidebar-profile-name">ยินดีต้อนรับ, ผู้เยี่ยมชม</h3>
                    <span class="sidebar-role-badge role-buyer">กรุณาเข้าสู่ระบบ</span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <ul class="sidebar-menu-list">
        
        <li class="sidebar-menu-label">เมนูหลัก (Main Menu)</li>
        
        <li>
            <a href="../pages/index.php" class="sidebar-link">
                <i class="fas fa-home"></i>
                <span>หน้าหลัก Marketplace</span>
            </a>
        </li>
        
        <li>
            <a href="../pages/wtb_board.php" class="sidebar-link">
                <i class="fas fa-bullhorn text-warning"></i>
                <span>กระดานตามหาของ (WTB)</span>
            </a>
        </li>

        <?php if (isLoggedIn()): ?>
            
            <li class="sidebar-menu-label">ส่วนตัว (Personal)</li>
            
            <li>
                <a href="../pages/profile.php" class="sidebar-link">
                    <i class="fas fa-user-edit"></i>
                    <span>จัดการบัญชีผู้ใช้</span>
                </a>
            </li>

            <li>
                <a href="../pages/wishlist.php" class="sidebar-link">
                    <i class="fas fa-heart text-danger"></i>
                    <span>สินค้าที่ถูกใจ (Wishlist)</span>
                </a>
            </li>

            <li>
                <a href="../pages/my_orders.php" class="sidebar-link">
                    <i class="fas fa-shopping-bag text-success"></i>
                    <span>รายการคำสั่งซื้อ</span>
                </a>
            </li>

            <?php if ($_SESSION['role'] === 'seller'): ?>
                <li class="sidebar-menu-label">ร้านค้า (Seller Tools)</li>
                <li>
                    <a href="../seller/dashboard.php" class="sidebar-link" style="color: var(--success-color);">
                        <i class="fas fa-store"></i>
                        <span>แดชบอร์ดร้านค้า</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'buyer'): ?>
                <li>
                    <a href="../auth/register_seller.php" class="sidebar-link" style="color: var(--primary-color);">
                        <i class="fas fa-store-alt"></i>
                        <span>ลงทะเบียนเปิดร้านค้า</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                <li class="sidebar-menu-label">ผู้ดูแลระบบ (Administration)</li>
                <li>
                    <a href="../admin/admin_dashboard.php" class="sidebar-link admin-link">
                        <i class="fas fa-shield-alt"></i>
                        <span>แผงควบคุมระบบ (Admin Panel)</span>
                    </a>
                </li>
                <li>
                    <a href="../admin/approve_product.php" class="sidebar-link admin-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span>อนุมัติสินค้า / ร้านค้า</span>
                        <?php 
                            $count_pending_sql = "SELECT COUNT(*) FROM products WHERE status = 'pending'";
                            $pending_res = $db->query($count_pending_sql)->fetchColumn();
                            if ($pending_res > 0) {
                                echo '<span class="sidebar-badge-inline">' . $pending_res . '</span>';
                            }
                        ?>
                    </a>
                </li>
            <?php endif; ?>

        <?php else: ?>
            <li class="sidebar-menu-label">ระบบสมาชิก (Authentication)</li>
            <li>
                <a href="../auth/login.php" class="sidebar-link">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>เข้าสู่ระบบ (Login)</span>
                </a>
            </li>
            <li>
                <a href="../auth/register.php" class="sidebar-link">
                    <i class="fas fa-user-plus"></i>
                    <span>สมัครสมาชิกใหม่ (Register)</span>
                </a>
            </li>
        <?php endif; ?>
        
    </ul>

    <?php if (isLoggedIn()): ?>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn-full">
            <i class="fas fa-power-off"></i>
            <span>ออกจากระบบ (Logout)</span>
        </a>
    </div>
    <?php endif; ?>

</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // ----------------------------------------------------
        // 1. SCROLL EFFECT FOR NAVBAR
        // ----------------------------------------------------
        const masterNavbar = document.getElementById('mainNavbar');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 30) {
                masterNavbar.classList.add('header-scrolled');
            } else {
                masterNavbar.classList.remove('header-scrolled');
            }
        }, { passive: true });

        // ----------------------------------------------------
        // 2. THEME TOGGLE CONTROLLER
        // ----------------------------------------------------
        const themeBtn = document.getElementById('themeToggleBtn');
        const themeIcon = document.getElementById('themeIconState');
        const bodyElem = document.body;
        const htmlElem = document.documentElement;

        function syncThemeIcon() {
            if (bodyElem.classList.contains('dark-theme')) {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        }
        
        // Set initial icon state based on script injected in <head>
        syncThemeIcon();

        themeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            bodyElem.classList.toggle('dark-theme');
            htmlElem.classList.toggle('dark-theme'); // Sync html tag for global CSS vars
            
            const currentMode = bodyElem.classList.contains('dark-theme') ? 'dark' : 'light';
            localStorage.setItem('bncc_theme', currentMode);
            
            syncThemeIcon();
            
            // Add subtle spin animation on click
            themeIcon.style.transform = 'rotate(360deg)';
            setTimeout(() => { themeIcon.style.transform = 'none'; }, 500);
        });

        // ----------------------------------------------------
        // 3. SIDEBAR DRAWER CONTROLLER
        // ----------------------------------------------------
        const menuToggleBtn = document.getElementById('menuToggleBtn');
        const sidebarMenu = document.getElementById('sidebarMenu');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

        function openSidebar() {
            bodyElem.classList.add('sidebar-open');
            sidebarMenu.classList.add('active');
            sidebarOverlay.classList.add('active');
        }

        function closeSidebar() {
            bodyElem.classList.remove('sidebar-open');
            sidebarMenu.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        }

        if (menuToggleBtn) menuToggleBtn.addEventListener('click', openSidebar);
        if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        // Close sidebar on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebarMenu.classList.contains('active')) {
                closeSidebar();
            }
        });

        // ----------------------------------------------------
        // 4. NOTIFICATION SYSTEM LOGIC (AJAX)
        // ----------------------------------------------------
        <?php if(isLoggedIn()): ?>
        const notifToggleBtn = document.getElementById('notifToggleBtn');
        const notifDropdownPanel = document.getElementById('notifDropdownPanel');
        const globalNotifBadge = document.getElementById('globalNotifBadge');
        const notifContentList = document.getElementById('notifContentList');
        
        let isDropdownOpen = false;

        // Toggle Dropdown
        if (notifToggleBtn) {
            notifToggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                isDropdownOpen = !isDropdownOpen;
                
                if (isDropdownOpen) {
                    notifDropdownPanel.classList.add('is-open');
                } else {
                    notifDropdownPanel.classList.remove('is-open');
                }
            });
        }

        // Close Dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (isDropdownOpen && notifDropdownPanel && !notifDropdownPanel.contains(e.target) && !notifToggleBtn.contains(e.target)) {
                notifDropdownPanel.classList.remove('is-open');
                isDropdownOpen = false;
            }
        });

        // Fetch Notifications Logic
        window.executeFetchNotifications = function() {
            fetch('../ajax/notifications_api.php?action=fetch', {
                method: 'GET',
                headers: { 'Cache-Control': 'no-cache' }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Update Badge UI
                    if (data.unread_count > 0) {
                        globalNotifBadge.style.display = 'flex';
                        globalNotifBadge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        // Add shake animation to bell icon
                        const bellIcon = notifToggleBtn.querySelector('i');
                        if(bellIcon && !bellIcon.classList.contains('fa-shake')) {
                            bellIcon.classList.add('fa-shake');
                            setTimeout(() => bellIcon.classList.remove('fa-shake'), 2000);
                        }
                    } else {
                        globalNotifBadge.style.display = 'none';
                    }

                    // Update List UI
                    if (data.notifications && data.notifications.length > 0) {
                        let htmlContent = '';
                        data.notifications.forEach(notif => {
                            const isUnreadClass = notif.is_read == 0 ? 'unread-status' : '';
                            htmlContent += `
                                <a href="${notif.link || '#'}" class="notif-item-card ${isUnreadClass}">
                                    <div class="notif-item-icon">
                                        ${notif.icon || '<i class="fas fa-bell"></i>'}
                                    </div>
                                    <div class="notif-item-content">
                                        <div class="notif-item-msg">${notif.message}</div>
                                        <div class="notif-item-time">${notif.time || 'เมื่อสักครู่'}</div>
                                    </div>
                                </a>
                            `;
                        });
                        notifContentList.innerHTML = htmlContent;
                    } else {
                        notifContentList.innerHTML = `
                            <div style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
                                <div style="font-weight: 700;">ไม่มีการแจ้งเตือนใหม่</div>
                            </div>
                        `;
                    }
                }
            })
            .catch(error => {
                console.error("Notification Fetch Error:", error);
            });
        };

        // Mark All Read Logic
        window.executeMarkAllRead = function() {
            fetch('../ajax/notifications_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_read'
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    executeFetchNotifications();
                }
            })
            .catch(err => console.error("Mark Read Error:", err));
        };

        // Initialize and setup polling
        executeFetchNotifications();
        setInterval(executeFetchNotifications, 20000); // Check every 20 seconds
        <?php endif; ?>
    });
</script>

<main class="master-main-content" style="padding-top: 2rem; min-height: calc(100vh - var(--nav-height-desktop));">