<?php
/**
 * SECTION 1: SYSTEM LOGIC INTEGRATION
 */
require_once __DIR__ . '/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// แผนผังเส้นทางการซ่อนปุ่ม
$hide_home_list = ['login.php', 'register.php', 'register_google.php', 'verify_otp.php' , 'appeal_ban.php'];
$hide_auth_list = ['index.php', 'register_seller.php', 'product_detail.php','login.php', 'register.php', 'register_google.php', 'verify_otp.php'];

// การประมวลผลรูปโปรไฟล์
$user_avatar = isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img']) 
                ? "../assets/images/profiles/" . $_SESSION['profile_img'] 
                : "../assets/images/profiles/default_profile.png";

// ตรวจสอบข้อความที่ยังไม่ได้อ่าน
$unread_msg_count = 0;
if (isLoggedIn()) {
    try {
        $db = getDB();
        $msg_stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $msg_stmt->execute([$_SESSION['user_id']]);
        $unread_msg_count = $msg_stmt->fetchColumn();
    } catch (PDOException $e) {
        $unread_msg_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $pageTitle ?? 'BNCC Market | แหล่งรวมสินค้าคุณภาพ'; ?></title>
    
    <link rel="icon" type="image/png" href="<?= defined('BASE_URL') ? BASE_URL : '/s673190104/student_marketplace/' ?>assets/images/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

    <style>
        /* ============================================================
           SECTION 2: DYNAMIC CSS ARCHITECTURE
           ============================================================ */
        :root {
            /* Light Theme Palette */
            --p-color: #4f46e5;
            --p-light: #818cf8;
            --p-dark: #3730a3;
            --p-glow: rgba(79, 70, 229, 0.4);
            
            --s-color: #10b981;
            --s-light: #34d399;
            --s-dark: #059669;
            
            --d-color: #ef4444;
            --w-color: #f59e0b;
            
            --bg-body: #f1f5f9;
            --bg-navbar: rgba(255, 255, 255, 0.8);
            --bg-card: #ffffff;
            --bg-sidebar: #ffffff;
            
            --txt-main: #0f172a;
            --txt-sub: #475569;
            --txt-muted: #94a3b8;
            --txt-on-p: #ffffff;
            
            --brd-color: #e2e8f0;
            --brd-glass: rgba(255, 255, 255, 0.2);
            
            --shd-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shd-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shd-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --shd-glow: 0 0 20px var(--p-glow);
            
            --nv-height: 80px;
            --sb-width: 320px;
            
            --t-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --t-normal: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --t-slow: 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            --t-bounce: 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            
            --z-nav: 1000;
            --z-side: 1100;
            --z-ovr: 1050;
            --z-drop: 1200;
        }

        /* Dark Theme Palette Integration */
        .dark-theme {
            --bg-body: #0b0f1a;
            --bg-navbar: rgba(15, 23, 42, 0.85);
            --bg-card: #161e2e;
            --bg-sidebar: #111827;
            
            --txt-main: #f8fafc;
            --txt-sub: #cbd5e1;
            --txt-muted: #64748b;
            
            --brd-color: #2d3748;
            --brd-glass: rgba(255, 255, 255, 0.05);
            
            --shd-sm: 0 1px 3px rgba(0,0,0,0.3);
            --shd-md: 0 4px 12px rgba(0,0,0,0.5);
            --shd-lg: 0 20px 25px -5px rgba(0,0,0,0.6);
        }

        /* Global Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            outline: none;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Prompt', sans-serif;
            background-color: var(--bg-body);
            color: var(--txt-main);
            transition: background-color var(--t-normal), color var(--t-normal);
            overflow-x: hidden;
            min-height: 100vh;
        }

        body.sidebar-active {
            overflow: hidden;
        }

        /* Reusable Animations */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideInUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); } 100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); } }
        @keyframes spinSlow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-5px); } 100% { transform: translateY(0px); } }

        /* ============================================================
           SECTION 3: NAVBAR (GLASSMORPHISM)
           ============================================================ */
        .premium-nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--nv-height);
            background: var(--bg-navbar);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--brd-glass);
            z-index: var(--z-nav);
            display: flex;
            align-items: center;
            transition: height var(--t-normal), background var(--t-normal), box-shadow var(--t-normal);
        }

        .premium-nav.scrolled {
            height: 70px;
            box-shadow: var(--shd-md);
            background: var(--bg-navbar);
        }

        .nav-container {
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Left Zone: Logo & Menu Toggle */
        .nav-left {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .menu-toggle {
            width: 45px;
            height: 45px;
            border-radius: 14px;
            background: var(--p-color);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            transition: var(--t-bounce);
            box-shadow: var(--shd-glow);
        }

        .menu-toggle:hover {
            transform: scale(1.1);
            background: var(--p-dark);
        }

        .toggle-bar {
            width: 22px;
            height: 2.5px;
            background: white;
            border-radius: 10px;
            transition: var(--t-normal);
        }

        .sidebar-active .toggle-bar:nth-child(1) { transform: translateY(7.5px) rotate(45deg); }
        .sidebar-active .toggle-bar:nth-child(2) { opacity: 0; transform: translateX(-10px); }
        .sidebar-active .toggle-bar:nth-child(3) { transform: translateY(-7.5px) rotate(-45deg); }

        .brand-hub {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--p-color), var(--s-color));
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 1.4rem;
            box-shadow: var(--shd-md);
        }

        .brand-text {
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--txt-main);
            letter-spacing: -1px;
        }

        /* Right Zone: Actions & Profile */
        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .action-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(156, 163, 175, 0.1);
            color: var(--txt-sub);
            border: none;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            transition: var(--t-normal);
            position: relative;
            text-decoration: none;
        }

        .action-circle:hover {
            background: var(--p-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shd-glow);
        }

        .badge-dot {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 18px;
            height: 18px;
            background: var(--d-color);
            color: white;
            border-radius: 50%;
            font-size: 0.65rem;
            font-weight: 900;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid var(--bg-navbar);
            animation: pulseGlow 2s infinite;
        }

        .theme-switch-btn i {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark-theme .theme-switch-btn i {
            transform: rotate(360deg);
        }

        .mini-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 5px 5px 15px;
            border-radius: 50px;
            background: rgba(156, 163, 175, 0.1);
            text-decoration: none;
            border: 1px solid transparent;
            transition: var(--t-normal);
        }

        .mini-profile:hover {
            background: var(--bg-card);
            border-color: var(--p-color);
        }

        .mini-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--p-color);
        }

        .mini-name {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--txt-main);
        }

        /* ============================================================
           SECTION 4: SIDEBAR DRAWER (OFF-CANVAS)
           ============================================================ */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: var(--z-ovr);
            opacity: 0;
            visibility: hidden;
            transition: var(--t-normal);
        }

        .sidebar-active .sidebar-overlay {
            opacity: 1;
            visibility: visible;
        }

        .sidebar-drawer {
            position: fixed;
            top: 0;
            left: calc(-1 * var(--sb-width));
            width: var(--sb-width);
            height: 100%;
            background: var(--bg-sidebar);
            z-index: var(--z-side);
            transition: left var(--t-bounce);
            display: flex;
            flex-direction: column;
            box-shadow: var(--shd-lg);
        }

        .sidebar-active .sidebar-drawer {
            left: 0;
        }

        .sidebar-header {
            padding: 40px 30px;
            border-bottom: 1px solid var(--brd-color);
            position: relative;
        }

        .sidebar-profile-box {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .sidebar-avatar-wrap {
            width: 80px;
            height: 80px;
            border-radius: 24px;
            overflow: hidden;
            border: 4px solid var(--p-color);
            box-shadow: var(--shd-lg);
        }

        .sidebar-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sidebar-user-meta h3 {
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .badge-admin { background: #fef3c7; color: #d97706; }
        .badge-teacher { background: #fee2e2; color: #ef4444; }
        .badge-seller { background: #dcfce7; color: #10b981; }
        .badge-buyer { background: #e0e7ff; color: #4f46e5; }

        .sidebar-nav-list {
            padding: 20px 15px;
            flex-grow: 1;
            overflow-y: auto;
            list-style: none;
        }

        .menu-label {
            padding: 15px 15px 10px;
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--txt-muted);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .menu-item-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 18px;
            border-radius: 16px;
            text-decoration: none;
            color: var(--txt-sub);
            font-weight: 600;
            transition: var(--t-normal);
            margin-bottom: 5px;
        }

        .menu-item-link i {
            width: 24px;
            font-size: 1.2rem;
            text-align: center;
            transition: var(--t-normal);
        }

        .menu-item-link:hover {
            background: var(--p-primary-soft, rgba(79, 70, 229, 0.08));
            color: var(--p-color);
            transform: translateX(8px);
        }

        .menu-item-link.active {
            background: var(--p-color);
            color: white;
            box-shadow: var(--shd-glow);
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--brd-color);
        }

        .logout-btn {
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            background: rgba(239, 68, 68, 0.1);
            color: var(--d-color);
            border: none;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: var(--t-normal);
        }

        .logout-btn:hover {
            background: var(--d-color);
            color: white;
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.2);
        }

        /* ============================================================
           SECTION 5: NOTIFICATION SYSTEM UI
           ============================================================ */
        .notif-panel {
            position: absolute;
            top: calc(100% + 15px);
            right: -10px;
            width: 380px;
            max-width: 90vw;
            background: var(--bg-card);
            border-radius: 24px;
            box-shadow: var(--shd-lg);
            border: 1px solid var(--brd-color);
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: slideInUp 0.3s ease;
            z-index: var(--z-drop);
        }

        .notif-panel.active { display: flex; }

        .notif-header {
            padding: 20px;
            background: var(--p-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notif-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notif-item {
            padding: 15px 20px;
            display: flex;
            gap: 15px;
            text-decoration: none;
            color: var(--txt-main);
            border-bottom: 1px solid var(--brd-color);
            transition: var(--t-normal);
        }

        .notif-item:hover { background: var(--bg-body); }
        .notif-item.unread { background: rgba(79, 70, 229, 0.05); }

        .notif-icon-box {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: var(--p-primary-soft, rgba(79, 70, 229, 0.1));
            color: var(--p-color);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
        }

        /* ============================================================
           SECTION 6: RESPONSIVE TIERS
           ============================================================ */
        @media (max-width: 1024px) {
            .nav-container { padding: 0 20px; }
            .brand-text { font-size: 1.3rem; }
        }

        @media (max-width: 768px) {
            .mini-name { display: none; }
            .mini-profile { padding: 5px; }
            .nav-container { padding: 0 15px; }
            .notif-panel { position: fixed; top: 80px; left: 15px; right: 15px; width: auto; }
        }

        /* Extended Decorative Elements to reach significant depth */
        .cyber-grid-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(var(--brd-color) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.2;
            pointer-events: none;
            z-index: -1;
        }

        .glass-btn {
            background: var(--brd-glass);
            border: 1px solid var(--brd-glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 10px 20px;
            border-radius: 12px;
            color: var(--txt-main);
            font-weight: 700;
            text-decoration: none;
            transition: var(--t-normal);
        }

        .glass-btn:hover {
            background: var(--p-color);
            color: white;
            transform: scale(1.05);
        }
    </style>

    <script>
        /**
         * INITIAL THEME SCRIPT
         * สคริปต์นี้ต้องรันทันทีเพื่อป้องกันการกะพริบของธีม (Flicker)
         */
        (function() {
            const savedTheme = localStorage.getItem('bncc_market_theme') || 'light';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        })();
    </script>
</head>
<body>

<div class="cyber-grid-overlay"></div>

<nav class="premium-nav" id="mainHeader">
    <div class="nav-container">
        
        <div class="nav-left">
            <button class="menu-toggle" id="btnToggleSidebar" title="เปิดเมนู">
                <span class="toggle-bar"></span>
                <span class="toggle-bar"></span>
                <span class="toggle-bar"></span>
            </button>

            <a href="../pages/index.php" class="brand-hub">
                <div class="brand-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h1 class="brand-text">BNCC<span style="color:var(--p-color)">Market</span></h1>
            </a>
        </div>

        <div class="nav-right">
            
            <button class="action-circle theme-switch-btn" id="btnToggleTheme" title="สลับโหมดมืด/สว่าง">
                <i class="fas fa-moon"></i>
            </button>

            <?php if (isLoggedIn()): ?>
                
                <div style="position: relative;">
                    <button class="action-circle" id="btnToggleNotif" title="การแจ้งเตือน">
                        <i class="fas fa-bell"></i>
                        <span id="notifBadgeGlobal" class="badge-dot" style="display:none">0</span>
                    </button>

                    <div class="notif-panel" id="panelNotif">
                        <div class="notif-header">
                            <span class="fw-bold"><i class="fas fa-bell me-2"></i> การแจ้งเตือน</span>
                            <button onclick="markAllAsRead()" style="background:none; border:none; color:white; font-size:0.75rem; cursor:pointer; font-weight:800;">อ่านทั้งหมด</button>
                        </div>
                        <div class="notif-list" id="listNotifItems">
                            <div style="padding: 50px; text-align:center; color:var(--txt-muted)">
                                <i class="fas fa-circle-notch fa-spin fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="../pages/chat.php" class="action-circle" title="ข้อความ">
                    <i class="fas fa-comment-dots"></i>
                    <?php if($unread_msg_count > 0): ?>
                        <span class="badge-dot"><?= $unread_msg_count > 99 ? '99+' : $unread_msg_count ?></span>
                    <?php endif; ?>
                </a>

                <a href="../pages/profile.php" class="mini-profile">
                    <span class="mini-name"><?= htmlspecialchars($_SESSION['fullname']) ?></span>
                    <img src="<?= $user_avatar ?>" alt="User" class="mini-avatar">
                </a>

            <?php else: ?>
                
                <?php if (!in_array($current_page, $hide_auth_list)): ?>
                    <div style="display: flex; gap: 10px;">
                        <a href="../auth/login.php" class="glass-btn">เข้าสู่ระบบ</a>
                        <a href="../auth/register.php" class="glass-btn" style="background:var(--p-color); color:white; border:none;">สมัครสมาชิก</a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
</nav>

<div class="sidebar-overlay" id="overlaySidebar"></div>

<aside class="sidebar-drawer" id="sidebarMain">
    
    <div class="sidebar-header">
        <?php if (isLoggedIn()): ?>
            <div class="sidebar-profile-box">
                <div class="sidebar-avatar-wrap">
                    <img src="<?= $user_avatar ?>" alt="User Avatar">
                </div>
                <div class="sidebar-user-meta">
                    <h3><?= htmlspecialchars($_SESSION['fullname']) ?></h3>
                    <?php 
                        $r_class = 'badge-buyer'; $r_text = 'ผู้ซื้อทั่วไป';
                        if ($_SESSION['role'] === 'admin') { $r_class = 'badge-admin'; $r_text = 'ผู้ดูแลระบบ'; }
                        elseif ($_SESSION['role'] === 'teacher') { $r_class = 'badge-teacher'; $r_text = 'อาจารย์'; }
                        elseif ($_SESSION['role'] === 'seller') { $r_class = 'badge-seller'; $r_text = 'ร้านค้า'; }
                    ?>
                    <span class="role-badge <?= $r_class ?>"><?= $r_text ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="sidebar-profile-box">
                <div class="sidebar-avatar-wrap" style="background:var(--bg-body); display:flex; justify-content:center; align-items:center; border:none;">
                    <i class="fas fa-user-circle fa-4x" style="color:var(--txt-muted)"></i>
                </div>
                <div class="sidebar-user-meta">
                    <h3>ยินดีต้อนรับ</h3>
                    <p style="font-size:0.8rem; color:var(--txt-muted)">กรุณาเข้าสู่ระบบเพื่อใช้งานเต็มรูปแบบ</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <ul class="sidebar-nav-list">
        
        <li class="menu-label">สำรวจ Marketplace</li>
        <li>
            <a href="../pages/index.php" class="menu-item-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> <span>หน้าหลัก</span>
            </a>
        </li>
        <li>
            <a href="../pages/wtb_board.php" class="menu-item-link <?= $current_page == 'wtb_board.php' ? 'active' : '' ?>">
                <i class="fas fa-bullhorn text-warning"></i> <span>กระดานตามหาของ</span>
            </a>
        </li>

        <?php if (isLoggedIn()): ?>
            
            <li class="menu-label">จัดการส่วนตัว</li>
            <li>
                <a href="../pages/profile.php" class="menu-item-link <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-id-card"></i> <span>ข้อมูลส่วนตัว</span>
                </a>
            </li>
            <li>
                <a href="../pages/wishlist.php" class="menu-item-link <?= $current_page == 'wishlist.php' ? 'active' : '' ?>">
                    <i class="fas fa-heart text-danger"></i> <span>สิ่งที่ถูกใจ</span>
                </a>
            </li>
            <li>
                <a href="../pages/my_orders.php" class="menu-item-link <?= $current_page == 'my_orders.php' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-basket text-success"></i> <span>การสั่งซื้อของฉัน</span>
                </a>
            </li>

            <?php if ($_SESSION['role'] === 'seller'): ?>
                <li class="menu-label">เครื่องมือร้านค้า</li>
                <li>
                    <a href="../seller/dashboard.php" class="menu-item-link" style="color:var(--s-color)">
                        <i class="fas fa-store"></i> <span>แดชบอร์ดร้านค้า</span>
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="../auth/register_seller.php" class="menu-item-link">
                        <i class="fas fa-store-alt"></i> <span>เปิดร้านค้าของคุณ</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                <li class="menu-label">ระบบควบคุม</li>
                <li>
                    <a href="../admin/admin_dashboard.php" class="menu-item-link" style="color:var(--d-color)">
                        <i class="fas fa-shield-halved"></i> <span>แผงแอดมิน</span>
                    </a>
                </li>
            <?php endif; ?>

        <?php else: ?>
            <li class="menu-label">ระบบสมาชิก</li>
            <li>
                <a href="../auth/login.php" class="menu-item-link">
                    <i class="fas fa-key"></i> <span>เข้าสู่ระบบ</span>
                </a>
            </li>
            <li>
                <a href="../auth/register.php" class="menu-item-link">
                    <i class="fas fa-user-plus"></i> <span>สมัครสมาชิก</span>
                </a>
            </li>
        <?php endif; ?>
        
    </ul>

    <?php if (isLoggedIn()): ?>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-power-off"></i> <span>ออกจากระบบ</span>
        </a>
    </div>
    <?php endif; ?>

</aside>

<script>
/**
 * SECTION 7: SYSTEM CONTROLLER (JavaScript)
 */
document.addEventListener('DOMContentLoaded', () => {
    
    // --- ELEMENTS ---
    const header = document.getElementById('mainHeader');
    const btnToggleSidebar = document.getElementById('btnToggleSidebar');
    const sidebar = document.getElementById('sidebarMain');
    const overlay = document.getElementById('overlaySidebar');
    const btnTheme = document.getElementById('btnToggleTheme');
    const btnNotif = document.getElementById('btnToggleNotif');
    const panelNotif = document.getElementById('panelNotif');
    const listNotif = document.getElementById('listNotifItems');
    const badgeNotif = document.getElementById('notifBadgeGlobal');

    // --- NAVBAR SCROLL EFFECT ---
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // --- SIDEBAR LOGIC ---
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-active');
    }

    if (btnToggleSidebar) btnToggleSidebar.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);

    // --- THEME LOGIC ---
    function updateThemeUI(theme) {
        const icon = btnTheme.querySelector('i');
        if (theme === 'dark') {
            document.documentElement.classList.add('dark-theme');
            icon.classList.replace('fa-moon', 'fa-sun');
        } else {
            document.documentElement.classList.remove('dark-theme');
            icon.classList.replace('fa-sun', 'fa-moon');
        }
    }

    // Init Theme UI
    updateThemeUI(localStorage.getItem('bncc_market_theme') || 'light');

    btnTheme.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark-theme');
        const newTheme = isDark ? 'dark' : 'light';
        localStorage.setItem('bncc_market_theme', newTheme);
        updateThemeUI(newTheme);
    });

    // --- NOTIFICATION AJAX ENGINE ---
    <?php if(isLoggedIn()): ?>
    let notifOpen = false;

    async function fetchNotifications() {
        try {
            const res = await fetch('../ajax/notifications_api.php?action=fetch');
            const data = await res.json();

            if (data.status === 'success') {
                // Update Badge
                if (data.unread_count > 0) {
                    badgeNotif.style.display = 'flex';
                    badgeNotif.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                } else {
                    badgeNotif.style.display = 'none';
                }

                // Render List
                if (data.notifications.length > 0) {
                    listNotif.innerHTML = data.notifications.map(n => `
                        <a href="${n.link}" class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
                            <div class="notif-icon-box">${n.icon || '<i class="fas fa-bell"></i>'}</div>
                            <div class="notif-content">
                                <div style="font-size:0.85rem; font-weight:700;">${n.message}</div>
                                <div style="font-size:0.7rem; color:var(--txt-muted); margin-top:3px;">${n.time}</div>
                            </div>
                        </a>
                    `).join('');
                } else {
                    listNotif.innerHTML = `
                        <div style="padding:40px; text-align:center; color:var(--txt-muted)">
                            <i class="fas fa-inbox fa-3x mb-3" style="opacity:0.3"></i>
                            <div>ไม่มีการแจ้งเตือน</div>
                        </div>
                    `;
                }
            }
        } catch (err) {
            console.error("Notif Fetch Failed", err);
        }
    }

    btnNotif.addEventListener('click', (e) => {
        e.stopPropagation();
        notifOpen = !notifOpen;
        panelNotif.classList.toggle('active', notifOpen);
        if (notifOpen) fetchNotifications();
    });

    document.addEventListener('click', (e) => {
        if (notifOpen && !panelNotif.contains(e.target) && !btnNotif.contains(e.target)) {
            notifOpen = false;
            panelNotif.classList.remove('active');
        }
    });

    // Auto-fetch cycle
    fetchNotifications();
    setInterval(fetchNotifications, 30000);
    <?php endif; ?>

    // --- ACCESSIBILITY & ESCAPE KEY ---
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.body.classList.remove('sidebar-active');
            if (notifOpen) {
                notifOpen = false;
                panelNotif.classList.remove('active');
            }
        }
    });
});

/**
 * UTILITY FUNCTIONS
 */
function markAllAsRead() {
    fetch('../ajax/notifications_api.php?action=mark_all_read', { method: 'POST' })
        .then(() => location.reload());
}
</script>

<main style="padding-top: calc(var(--nv-height) + 20px); min-height: 100vh;">