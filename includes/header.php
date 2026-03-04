<?php
/**
 * BNCC Market - Master Header (Refined UX/UI)
 * สำหรับโปรเจกต์ Student Marketplace โดยเฉพาะ
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
$hide_home_list = ['login.php', 'register.php', 'register_google.php'];
$hide_auth_list = ['index.php', 'register_seller.php', 'product_detail.php'];

// 4. ฟังก์ชันจัดการรูปโปรไฟล์
// 🎯 ชี้เป้าไปที่โฟลเดอร์เดียวกันให้หมด จะได้ไม่แตก
$user_avatar = isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img']) 
                ? "../assets/images/profiles/" . $_SESSION['profile_img'] 
                : "../assets/images/profiles/default_profile.png";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'BNCC Market'; ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/student_marketplace/assets/css/style.css">

    <style>
        :root {
            --nav-height: 70px;
            --glass-bg: rgba(var(--bg-card-rgb), 0.8);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: var(--nav-height);
            display: flex;
            align-items: center;
            transition: var(--transition);
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin: 0;
            padding: 0;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-main);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 10px;
            transition: var(--transition);
        }

        .nav-link:hover {
            background: rgba(var(--primary-rgb), 0.1);
            color: var(--primary);
        }

        .user-nav-box {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #1e293b; 
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .user-info {
            text-align: left;
            line-height: 1.2;
            color: #ffffff;
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .role-badge-nav {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-top: 2px;
        }

        .badge-teacher { color: #ef4444; } /* 👑 สีของครู */
        .badge-admin { color: #f87171; }
        .badge-seller { color: #34d399; }

        .logout-icon {
            color: #ef4444;
            font-size: 1.1rem;
            margin-left: 8px;
            transition: var(--transition);
        }

        .logout-icon:hover {
            transform: scale(1.1);
            filter: brightness(1.2);
        }

        .btn-icon {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            width: 40px;
            height: 40px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .btn-icon:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .badge-count {
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 50px;
            margin-left: 5px;
        }
    </style>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark-theme');
            }
        })();
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
            <li>
                <button id="theme-toggle" class="btn-icon" title="สลับโหมด">
                    <i class="fas fa-sun" id="theme-icon"></i>
                </button>
            </li>

            <?php if (!in_array($current_page, $hide_home_list)): ?>
                <li>
                    <a href="../pages/index.php" class="nav-link">
                        <i class="fas fa-house"></i> <span>หน้าแรก</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
                
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher'): ?>
                    <li><a href="../admin/admin_dashboard.php" class="nav-link text-danger"><i class="fas fa-shield-halved"></i> <?php echo $_SESSION['role'] === 'teacher' ? 'Master Admin' : 'Admin'; ?></a></li>
                    <li>
                        <a href="../admin/approve_product.php" class="nav-link">
                            <i class="fas fa-clipboard-check"></i> อนุมัติสินค้า 
                            <?php 
                                $db = getDB();
                                $count_stmt = $db->query("SELECT COUNT(*) FROM products WHERE status = 'pending'");
                                $pending_count = $count_stmt->fetchColumn();
                                if ($pending_count > 0) echo "<span class='badge-count'>$pending_count</span>";
                            ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'seller'): ?>
                    <li><a href="../seller/dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard ผู้ขาย</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'buyer'): ?>
                    <li><a href="../auth/register_seller.php" class="nav-link text-primary"><i class="fas fa-store"></i> สมัครเป็นผู้ขาย</a></li>
                <?php endif; ?>

                <li class="user-nav-box">
                    <a href="../pages/profile.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                        <img src="<?= $user_avatar ?>" class="avatar-circle" alt="Avatar">
                        <div class="user-info">
                            <div class="user-name"><?= e($_SESSION['fullname']) ?></div>
                            <?php if (in_array($_SESSION['role'], ['admin', 'seller', 'teacher'])): ?>
                                <small class="role-badge-nav <?= $_SESSION['role'] === 'teacher' ? 'badge-teacher' : ($_SESSION['role'] === 'admin' ? 'badge-admin' : 'badge-seller') ?>">
                                    <?= strtoupper(e($_SESSION['role'])) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </a>

                    <a href="../pages/wishlist.php" title="รายการที่ชอบ" style="color: #ef4444; font-size: 1.1rem; margin-left: 5px;">
                        <i class="fas fa-heart"></i>
                    </a>
                    
                    <a href="../auth/logout.php" class="logout-icon" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>
                </li>

            <?php else: ?>
                <?php if (!in_array($current_page, $hide_auth_list)): ?>
                    <li><a href="../auth/login.php" class="btn btn-outline">เข้าสู่ระบบ</a></li>
                    <li><a href="../auth/register.php" class="btn btn-primary">สมัครสมาชิก</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<main class="container" style="padding-top: 2rem; min-height: calc(100vh - var(--nav-height));">