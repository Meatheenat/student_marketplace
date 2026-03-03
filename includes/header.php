<?php
/**
 * Student Marketplace - Global Header
 * จัดการ Navigation, Theme Switcher และสิทธิ์การเข้าถึง
 */
require_once 'functions.php';

// 1. ตรวจสอบชื่อไฟล์ปัจจุบันเพื่อใช้ซ่อน/แสดงเมนู
$current_page = basename($_SERVER['PHP_SELF']);

// 2. ตั้งค่าหน้าที่ต้องซ่อน "หน้าแรก"
$hide_home_list = ['login.php', 'register.php', 'register_google.php', 'register_seller.php'];

// 3. ตั้งค่าหน้าที่ต้องซ่อนปุ่ม "เข้าสู่ระบบ/สมัครสมาชิก"
$hide_auth_list = ['index.php', 'register_seller.php', 'product_detail.php'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'BNCC Market'; ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/student_marketplace/assets/css/style.css">

    <script>
        // เช็คธีมจาก localStorage ทันทีเพื่อกันหน้าขาวแวบ (FOUC)
        (function() {
            const savedTheme = localStorage.getItem('theme');
            // ถ้าเคยบันทึกว่าเป็น dark หรือถ้าไม่เคยบันทึกแต่เครื่องผู้ใช้เป็น Dark Mode
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark-theme');
            } else {
                document.documentElement.classList.remove('dark-theme');
            }
        })();
    </script>
</head>
<body>

<nav class="navbar">
    <div class="container nav-content">
        <div class="logo">
            <a href="../pages/index.php" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-shopping-basket"></i>
                <span>BNCC Market</span>
            </a>
        </div>

        <ul class="nav-links">
            <li>
                <button id="theme-toggle" title="สลับโหมดมืด/สว่าง">
                    <i class="fas fa-moon" id="theme-icon"></i>
                </button>
            </li>

            <?php 
            // --- ส่วนที่ 1: เมนู "หน้าแรก" ---
            if (!in_array($current_page, $hide_home_list)): ?>
                <li>
                    <a href="../pages/index.php" style="display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-house"></i> <span>หน้าแรก</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php 
            // --- ส่วนที่ 2: เมื่อเข้าสู่ระบบแล้ว (แยกตาม Role) ---
            if (isLoggedIn()): ?>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="../admin/admin_dashboard.php" style="color: #ef4444; font-weight: 600;"><i class="fas fa-user-shield"></i> <span>หลังบ้านแอดมิน</span></a></li>
                    <li><a href="../admin/approve_shop.php"><i class="fas fa-clipboard-check"></i> <span>อนุมัติร้าน</span></a></li>

                <?php elseif ($_SESSION['role'] === 'seller'): ?>
                    <li><a href="../seller/dashboard.php" style="color: var(--primary-color); font-weight: 600;"><i class="fas fa-gauge-high"></i> <span>Dashboard</span></a></li>
                    <li><a href="../seller/add_product.php"><i class="fas fa-plus-circle"></i> <span>ลงขายสินค้า</span></a></li>

                <?php elseif ($_SESSION['role'] === 'buyer'): ?>
                    <li><a href="../auth/register_seller.php" class="btn-primary" style="padding: 6px 15px; border-radius: 8px; font-size: 0.9rem;"><i class="fas fa-shop"></i> สมัครเป็นผู้ขาย</a></li>
                <?php endif; ?>

                <li style="border-left: 1px solid var(--border-color); padding-left: 15px; margin-left: 10px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="text-align: right; line-height: 1.2;">
                            <div style="font-size: 0.85rem; font-weight: 600;"><?php echo e($_SESSION['fullname']); ?></div>
                            <small style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;"><?php echo e($_SESSION['role']); ?></small>
                        </div>
                        <a href="../auth/logout.php" title="ออกจากระบบ" style="color: #ef4444; font-size: 1.1rem;"><i class="fas fa-right-from-bracket"></i></a>
                    </div>
                </li>

            <?php else: ?>
                <?php if (!in_array($current_page, $hide_auth_list)): ?>
                    <li><a href="../auth/login.php" class="btn btn-outline" style="padding: 8px 15px;">เข้าสู่ระบบ</a></li>
                    <li><a href="../auth/register.php" class="btn btn-primary" style="padding: 8px 15px;">สมัครสมาชิก</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<main class="container">