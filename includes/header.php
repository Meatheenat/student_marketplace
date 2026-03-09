<?php
/**
 * BNCC Market - Master Header (Ultra Premium UX/UI Redesign)
 * Version: 2026 Edition (Refined Animations & Micro-interactions)
 * -----------------------------------------------------------------------
 * [Cite: User Summary]
 * Project: Student Marketplace for BNCC
 * Developer Collaboration: Gemini & Ploy
 */

// 🛠️ อัปเกรด: ใช้ __DIR__ เพื่อให้ Path ถูกต้องเสมอ ไม่ว่าจะเรียกจากโฟลเดอร์ไหน
require_once __DIR__ . '/functions.php';

// 1. ตรวจสอบชื่อไฟล์ปัจจุบัน
$current_page = basename($_SERVER['PHP_SELF']);

// 2. การตั้งค่าความปลอดภัยเบื้องต้น
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. รายการหน้าที่ต้องซ่อนเมนูเฉพาะจุด
$hide_home_list = ['login.php', 'register.php', 'register_google.php', 'verify_otp.php' , 'appeal_ban.php'];
$hide_auth_list = ['index.php', 'register_seller.php', 'product_detail.php'];

// 4. ฟังก์ชันจัดการรูปโปรไฟล์
$user_avatar = isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img']) 
                ? "../assets/images/profiles/" . $_SESSION['profile_img'] 
                : "../assets/images/profiles/default_profile.png";

// 5. เช็กจำนวนข้อความแชทที่ยังไม่ได้อ่าน
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'BNCC Market'; ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

    <style>
        /* ============================================================
            💎 BNCC MARKET - PREMIUM ANIMATED UI SYSTEM
           ============================================================ */
        :root {
            --nav-height: 75px;
            --glass-bg: rgba(var(--bg-card-rgb), 0.75);
            --transition-smooth: transform 0.5s cubic-bezier(0.23, 1, 0.32, 1), box-shadow 0.5s ease;
            --transition-bounce: transform 0.6s cubic-bezier(0.68, -0.6, 0.32, 1.6);
            --primary-glow: rgba(99, 102, 241, 0.4);
            --danger-glow: rgba(239, 68, 68, 0.4);
        }

        /* 🌊 ระบบ Smooth Scroll */
        html { scroll-behavior: smooth; }

        /* 🧱 Navbar Glassmorphism */
        .navbar {
            background: var(--glass-bg);
            backdrop-filter: blur(25px) saturate(190%);
            -webkit-backdrop-filter: blur(25px) saturate(190%);
            border-bottom: none;
            position: sticky;
            top: 0;
            z-index: 1000;
            height: var(--nav-height);
            display: flex;
            align-items: center;
            transition: background 0.3s ease, height 0.4s ease, box-shadow 0.4s ease;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.1);
        }

        /* 🚀 🛠️ Cyber-Line Animated Border (เส้นขอบเรืองแสงวิ่งได้) */
        .navbar::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; width: 100%; height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), #a855f7, var(--primary), transparent);
            background-size: 200% auto;
            animation: cyberLine 4s linear infinite;
            opacity: 0.7;
        }

        @keyframes cyberLine {
            0% { background-position: 0% center; }
            100% { background-position: 200% center; }
        }

        /* 🚀 Navbar Scroll Effect */
        .navbar.scrolled {
            height: 65px;
            background: rgba(var(--bg-card-rgb), 0.95);
            box-shadow: 0 15px 40px -12px rgba(0, 0, 0, 0.25);
        }

        .nav-content { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            width: 100%;
            padding: 0 1.5rem;
        }
        
        /* 🎨 Logo Branding & Hover */
        .nav-brand {
            font-size: 1.7rem;
            font-weight: 900;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition-bounce);
            position: relative;
            white-space: nowrap; 
        }
        
        /* 🎯 🛠️ แก้ปัญหาเปลี่ยนสีช้า */
        .nav-brand i { 
            font-size: 1.8rem;
            filter: drop-shadow(0 0 10px var(--primary-glow));
            transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275), color 0.05s ease !important;
        }
        .nav-brand:hover { transform: scale(1.05); }
        .nav-brand:hover i { transform: rotate(25deg) scale(1.2); color: #a855f7 !important; }
        
        .nav-brand span {
            color: var(--text-main); 
            display: inline-block;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .dark-theme .nav-brand span {
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8);
        }

        /* 🔗 Navigation Links Animation */
        .nav-links { 
            list-style: none; 
            display: flex; 
            align-items: center; 
            gap: 0.8rem; 
            margin: 0; 
            padding: 0; 
        }
        
        /* 🎯 🛠️ Staggered Entrance Animation */
        .nav-links > li {
            opacity: 0;
            transform: translateY(-15px);
            animation: navDropIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .nav-links > li:nth-child(1) { animation-delay: 0.1s; }
        .nav-links > li:nth-child(2) { animation-delay: 0.15s; }
        .nav-links > li:nth-child(3) { animation-delay: 0.2s; }
        .nav-links > li:nth-child(4) { animation-delay: 0.25s; }
        .nav-links > li:nth-child(5) { animation-delay: 0.3s; }

        @keyframes navDropIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .nav-link {
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border-radius: 18px;
            transition: background 0.3s ease, color 0.05s ease, transform 0.3s ease;
            position: relative;
            overflow: hidden;
            white-space: nowrap; 
            flex-shrink: 0; 
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0; left: 50%; width: 0; height: 3px;
            background: var(--primary);
            transition: var(--transition-smooth);
            transform: translateX(-50%);
            border-radius: 10px;
        }
        .nav-link:hover {
            background: rgba(var(--primary-rgb), 0.08);
            color: var(--primary) !important;
            transform: translateY(-3px);
        }
        .nav-link:hover::before { width: 40%; }

        /* 👑 🛠️ ADMIN EXCLUSIVE ANIMATION */
        .nav-link.text-danger {
            color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            overflow: hidden;
        }
        .nav-link.text-danger::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(239, 68, 68, 0.2), transparent);
            animation: adminShimmer 3s infinite;
        }
        @keyframes adminShimmer {
            100% { left: 200%; }
        }
        .nav-link.text-danger:hover {
            background: rgba(239, 68, 68, 0.15);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);
        }

        /* 🎯 🛠️ BADGE COUNT STYLING */
        .badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 6px;
            box-shadow: 0 0 10px var(--danger-glow);
            line-height: 1;
        }

        /* 👤 User Nav Box Design */
        .user-nav-box {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(30, 41, 59, 0.4); 
            padding: 6px 18px 6px 8px;
            border-radius: 60px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition-bounce);
            position: relative;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }
        .user-nav-box:hover { 
            background: rgba(30, 41, 59, 0.7); 
            border-color: var(--primary);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.3);
            transform: scale(1.02);
        }

        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2.5px solid var(--primary);
            transition: var(--transition-smooth);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .user-nav-box:hover .avatar-circle { 
            transform: scale(1.1) rotate(-8deg); 
            box-shadow: 0 0 20px var(--primary-glow); 
        }

        .user-info { text-align: left; line-height: 1.25; }
        .user-name { font-size: 0.95rem; font-weight: 700; color: #fff; white-space: nowrap; }
        .role-badge-nav { 
            font-size: 0.6rem; 
            font-weight: 900; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            margin-top: 3px;
            display: block;
            padding: 2px 8px;
            border-radius: 6px;
            background: rgba(255,255,255,0.1);
            white-space: nowrap;
        }
        .badge-teacher { color: #f87171; background: rgba(239, 68, 68, 0.15); }
        .badge-admin { color: #fbbf24; background: rgba(251, 191, 36, 0.15); }
        .badge-seller { color: #34d399; background: rgba(52, 211, 153, 0.15); }

        /* 🔔 Notification Bell Animation */
        @keyframes bell-shake {
            0%, 100% { transform: rotate(0); }
            15% { transform: rotate(20deg); }
            30% { transform: rotate(-18deg); }
            45% { transform: rotate(15deg); }
            60% { transform: rotate(-12deg); }
            75% { transform: rotate(8deg); }
        }
        .bell-active { 
            animation: bell-shake 2s cubic-bezier(.36,.07,.19,.97) infinite; 
            transform-origin: top center;
        }

        /* 🛠️ Toolbar Icons Premium Styles */
        .toolbar-icon {
            color: #94a3b8;
            font-size: 1.25rem;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275), background 0.3s, color 0.05s ease !important;
            position: relative;
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            flex-shrink: 0;
        }
        .toolbar-icon:hover { 
            color: white !important; 
            background: rgba(255,255,255,0.15); 
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .chat-icon-container { color: #60a5fa; }
        .chat-badge {
            position: absolute; 
            top: -4px; 
            right: -4px;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            color: white;
            font-size: 0.65rem; 
            font-weight: 900;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50px;
            border: 2px solid #1e293b;
            box-shadow: 0 4px 10px var(--danger-glow);
            animation: pulse-glow 2.5s infinite;
            pointer-events: none; 
        }

        @keyframes pulse-glow {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); transform: scale(1); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); transform: scale(1.1); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); transform: scale(1); }
        }

        /* 🎯 Dropdown Master Styles */
        .notif-dropdown {
            position: absolute; 
            top: 70px; 
            right: 0; 
            width: 360px; 
            background: var(--bg-card);
            border: 1px solid var(--border-color); 
            border-radius: 28px; 
            box-shadow: 0 30px 60px -12px rgba(0,0,0,0.5);
            display: none; 
            flex-direction: column; 
            overflow: hidden; 
            z-index: 1001;
            transform-origin: top right;
            backdrop-filter: blur(20px);
            animation: dropdownScale 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes dropdownScale {
            from { opacity: 0; transform: scale(0.95) translateY(-20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .notif-dropdown.show { display: flex; }

        .notif-header { 
            padding: 22px; 
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--border-color); 
            font-weight: 800; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            color: var(--text-main);
        }
        
        .notif-body { 
            max-height: 450px; 
            overflow-y: auto; 
            padding: 8px;
        }
        
        /* 📢 Notification Items Animation */
        .notif-item { 
            padding: 18px; 
            margin-bottom: 8px;
            border-radius: 18px;
            display: flex; 
            gap: 16px; 
            text-decoration: none; 
            color: var(--text-main); 
            transition: var(--transition-smooth);
            border: 1px solid transparent;
        }
        .notif-item:hover { 
            background: rgba(255, 255, 255, 0.05); 
            transform: translateX(5px);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .notif-unread { 
            background: rgba(99, 102, 241, 0.06); 
            border-left: 5px solid var(--primary); 
        }

        .logout-icon { 
            color: #fca5a5; 
            font-size: 1.2rem; 
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: var(--transition-bounce), color 0.05s ease !important; 
        }
        .logout-icon:hover { 
            background: rgba(239, 68, 68, 0.15); 
            color: #ef4444 !important;
            transform: rotate(15deg) scale(1.1); 
        }

        /* 🌓 Theme Toggle Glow & Anti-lag */
        #theme-toggle i {
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1), color 0.05s ease !important;
        }
        #theme-toggle:hover i { transform: rotate(180deg); }

        /* ⌨️ Custom Scrollbar */
        .notif-body::-webkit-scrollbar { width: 6px; }
        .notif-body::-webkit-scrollbar-track { background: transparent; }
        .notif-body::-webkit-scrollbar-thumb { 
            background: var(--border-color); 
            border-radius: 10px; 
        }
        .notif-body::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        /* 🌟 Staggered Animation for list items */
        .notif-item {
            opacity: 0;
            animation: fadeInRight 0.5s ease forwards;
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        /* --- ส่วนที่มึงต้องแปะเพิ่มใน <style> --- */
.search-container { position: relative; width: 100%; max-width: 500px; margin: 10px auto; }
.search-dropdown {
    position: absolute; top: 100%; left: 0; width: 100%;
    background: var(--solid-card, #1e293b); border: 2px solid var(--solid-border);
    border-radius: 12px; margin-top: 8px; display: none; z-index: 10000;
    box-shadow: 0 15px 30px rgba(0,0,0,0.5); overflow: hidden;
}
.search-item {
    display: flex; align-items: center; gap: 12px; padding: 12px 15px;
    text-decoration: none; color: white; border-bottom: 1px solid var(--solid-border);
}
.search-item:hover { background: rgba(99, 102, 241, 0.15); color: #6366f1; }
.search-item img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; }
.search-item .info h5 { margin: 0; font-size: 0.9rem; font-weight: bold; }
.search-item .info span { font-size: 0.8rem; color: #10b981; }
    </style>

    <script>
        // 🌓 🛠️ ระบบจัดการธีม Anti-Flicker (รันทันทีก่อนหน้าโหลด)
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark-theme');
            }
        })();

        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</head>
<body>

<nav class="navbar">
    <div class="container nav-content">
        <a href="../pages/index.php" class="nav-brand">
            <i class="fas fa-shopping-basket"></i>
            <span>BNCC Market</span>
        </a>

        <ul class="nav-links">
            <?php if (!in_array($current_page, $hide_home_list)): ?>
                <li>
                    <a href="../pages/index.php" class="nav-link">
                        <i class="fas fa-house"></i> <span>หน้าแรก</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher'): ?>
                    <li>
                        <a href="../admin/admin_dashboard.php" class="nav-link text-danger">
                            <i class="fas fa-shield-halved"></i> 
                            <span><?php echo $_SESSION['role'] === 'teacher' ? 'Master Admin' : 'Admin'; ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="../admin/approve_product.php" class="nav-link">
                            <i class="fas fa-clipboard-check"></i> 
                            <span>อนุมัติสินค้า</span>
                            <?php 
                                $db = getDB();
                                $count_stmt = $db->query("SELECT COUNT(*) FROM products WHERE status = 'pending'");
                                $pending_count = $count_stmt->fetchColumn();
                                if ($pending_count > 0) echo "<span class='badge-count' style='animation: pulse-red 2s infinite;'>$pending_count</span>";
                            ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'seller'): ?>
                    <li><a href="../seller/dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'buyer'): ?>
                    <li><a href="../auth/register_seller.php" class="nav-link text-primary"><i class="fas fa-store"></i> สมัครขายของ</a></li>
                <?php endif; ?>

                <li class="user-nav-box">
                    <a href="../pages/profile.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
                        <img src="<?= $user_avatar ?>" class="avatar-circle" alt="รูปโปรไฟล์">
                        <div class="user-info">
                            <div class="user-name"><?= e($_SESSION['fullname']) ?></div>
                            <small class="role-badge-nav <?= $_SESSION['role'] === 'teacher' ? 'badge-teacher' : ($_SESSION['role'] === 'admin' ? 'badge-admin' : 'badge-seller') ?>">
                                <?= strtoupper(e($_SESSION['role'])) ?>
                            </small>
                        </div>
                    </a>

                    <div style="position: relative; display: flex; align-items: center;">
                        <a href="javascript:void(0)" id="notif-bell" class="toolbar-icon magnetic-item" style="color: #f59e0b;">
                            <i class="fas fa-bell" style="pointer-events: none;"></i>
                            <span id="notif-badge" class="chat-badge" style="display:none;">0</span>
                        </a>
                        <div id="notif-dropdown" class="notif-dropdown">
                            <div class="notif-header">
                                <span><i class="fas fa-bolt text-warning"></i> แจ้งเตือนล่าสุด</span>
                                <button onclick="markNotifAsRead()" style="background:none; border:none; color:var(--primary); font-size:0.75rem; cursor:pointer; font-weight:800; text-transform:uppercase;">อ่านทั้งหมด</button>
                            </div>
                            <div class="notif-body" id="notif-list">
                                <div style="padding:40px; text-align:center; color:var(--text-muted);">
                                    <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="../pages/chat.php" title="คุยแชท" class="chat-icon-container toolbar-icon magnetic-item">
                        <i class="fas fa-comment-dots" style="pointer-events: none;"></i>
                        <?php if($unread_msg_count > 0): ?>
                            <span class="chat-badge"><?= $unread_msg_count > 99 ? '99+' : $unread_msg_count ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if ($_SESSION['role'] === 'buyer' || $_SESSION['role'] === 'seller'): ?>
                    <a href="../pages/my_orders.php" title="คำสั่งซื้อ" class="toolbar-icon magnetic-item" style="color: #10b981;">
                        <i class="fas fa-shopping-bag" style="pointer-events: none;"></i>
                    </a>
                    <?php endif; ?>

                    <a href="../pages/wishlist.php" title="สินค้าที่ชอบ" class="toolbar-icon magnetic-item" style="color: #ef4444;">
                        <i class="fas fa-heart" style="pointer-events: none;"></i>
                    </a>
                    
                    <a href="../auth/logout.php" class="logout-icon magnetic-item" title="ออกจากระบบ">
                        <i class="fas fa-power-off" style="pointer-events: none;"></i>
                    </a>
                </li>

            <?php else: ?>
                <?php if (!in_array($current_page, $hide_auth_list)): ?>
                    <li><a href="../auth/login.php" class="btn btn-outline" style="border-radius: 16px;">เข้าสู่ระบบ</a></li>
                    <li><a href="../auth/register.php" class="btn btn-primary" style="border-radius: 16px; box-shadow: 0 8px 20px -5px var(--primary-glow);">สมัครสมาชิก</a></li>
                <?php endif; ?>
            <?php endif; ?>

            <li>
                <button id="theme-toggle" class="btn-icon toolbar-icon magnetic-item" title="เปลี่ยนโหมดสี">
                    <i class="fas fa-sun" id="theme-icon"></i>
                </button>
            </li>
        </ul>
    </div>
</nav>

<script>
    /**
     * Theme Toggle Logic
     */
    const themeBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');

    function updateThemeIcon() {
        if (document.documentElement.classList.contains('dark-theme')) {
            themeIcon.classList.replace('fa-sun', 'fa-moon');
        } else {
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        }
    }

    updateThemeIcon(); // Sync ตอนโหลด

    themeBtn.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark-theme');
        const isDark = document.documentElement.classList.contains('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeIcon();
    });

    /**
     * 🎯 MAGNETIC HOVER EFFECT
     */
    document.querySelectorAll('.magnetic-item').forEach(btn => {
        btn.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            this.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px) scale(1.15)`;
            this.style.transition = "transform 0.1s ease-out";
        });
        
        btn.addEventListener('mouseleave', function(e) {
            this.style.transform = `translate(0px, 0px) scale(1)`;
            this.style.transition = "transform 0.5s cubic-bezier(0.23, 1, 0.32, 1)";
        });
    });
</script>

<?php if(isLoggedIn()): ?>
<script>
    /**
     * Notification & UI Real-time updates
     */
    const notifBell = document.getElementById('notif-bell');
    const notifBellIcon = notifBell.querySelector('i');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifBadge = document.getElementById('notif-badge');
    const notifList = document.getElementById('notif-list');

    notifBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('show');
    });

    document.addEventListener('click', function(e) {
        if (!notifDropdown.contains(e.target) && e.target !== notifBell) {
            notifDropdown.classList.remove('show');
        }
    });

    function fetchNotifications() {
        fetch('../ajax/notifications_api.php?action=fetch')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.unread_count > 0) {
                    notifBadge.style.display = 'flex';
                    notifBadge.innerText = data.unread_count > 99 ? '99+' : data.unread_count;
                    notifBellIcon.classList.add('bell-active'); 
                } else {
                    notifBadge.style.display = 'none';
                    notifBellIcon.classList.remove('bell-active');
                }

                if(data.notifications.length > 0) {
                    let html = '';
                    data.notifications.forEach((n, index) => {
                        const readClass = n.is_read == 0 ? 'notif-unread' : '';
                        const delay = index * 0.1;
                        html += `
                            <a href="${n.link}" class="notif-item ${readClass}" style="animation-delay: ${delay}s">
                                <div style="font-size: 1.5rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">${n.icon}</div>
                                <div style="flex:1">
                                    <div class="notif-text" style="font-weight:${n.is_read == 0 ? '800' : '500'}; font-size: 0.9rem;">${n.message}</div>
                                    <div class="notif-time" style="margin-top:5px; font-size:0.7rem; font-weight:700; opacity:0.5; color:var(--primary);">${n.time}</div>
                                </div>
                            </a>
                        `;
                    });
                    notifList.innerHTML = html;
                } else {
                    notifList.innerHTML = `
                        <div style="padding:50px 20px; text-align:center; color:var(--text-muted);">
                            <i class="far fa-moon" style="display:block; font-size:3rem; margin-bottom:15px; opacity:0.2;"></i>
                            <div style="font-weight:700; font-size:0.9rem;">ไม่มีอะไรใหม่ในตอนนี้</div>
                        </div>
                    `;
                }
            }
        }).catch(err => console.error("Notif Error:", err));
    }

    function markNotifAsRead() {
        fetch('../ajax/notifications_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=mark_read'
        }).then(() => {
            fetchNotifications();
        });
    }

    fetchNotifications();
    setInterval(fetchNotifications, 15000);
</script>
<?php endif; ?>

<main class="container" style="padding-top: 2.5rem; min-height: calc(100vh - var(--nav-height));">