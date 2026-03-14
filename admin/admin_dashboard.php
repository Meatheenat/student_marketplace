<?php
/**
 * Student Marketplace - Admin Dashboard (High Performance UI + Full Recycle Bin)
 * [SOLID HIGH-CONTRAST REDESIGN]
 */
$pageTitle = "ระบบผู้ดูแล (Admin) - BNCC Market";
require_once '../includes/header.php';

checkRole('admin');
$db = getDB();

// 🚀 🛠️ [เพิ่มใหม่] ระบบ Auto-Cleanup: ลบสินค้าและคอมเมนต์ที่อยู่ในถังขยะเกิน 30 วันทิ้งแบบถาวร (Hard Delete)
$db->query("DELETE FROM products WHERE is_deleted = 1 AND deleted_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$db->query("DELETE FROM reviews WHERE is_deleted = 1 AND deleted_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");

// 1. ดึงสถิติภาพรวม
$count_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$count_shops = $db->query("SELECT COUNT(*) FROM shops WHERE status = 'approved'")->fetchColumn();
$count_pending_shops = $db->query("SELECT COUNT(*) FROM shops WHERE status = 'pending'")->fetchColumn();
$count_products = $db->query("SELECT COUNT(*) FROM products WHERE is_deleted = 0")->fetchColumn(); 
$count_pending_products = $db->query("SELECT COUNT(*) FROM products WHERE status = 'pending' AND is_deleted = 0")->fetchColumn();

// ดึงสถิติถังขยะ
$count_trashed_products = $db->query("SELECT COUNT(*) FROM products WHERE is_deleted = 1")->fetchColumn();
$count_trashed_comments = $db->query("SELECT COUNT(*) FROM reviews WHERE is_deleted = 1")->fetchColumn(); // 🎯 นับคอมเมนต์ที่ถูกลบ

$count_pending_reports = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$count_comment_reports = $db->query("SELECT COUNT(*) FROM reports WHERE target_type = 'comment' AND status = 'pending'")->fetchColumn();
// 🎯 วางต่อท้ายจาก $count_comment_reports
$count_pending_wtb = $db->query("SELECT COUNT(*) FROM wtb_posts WHERE status = 'pending'")->fetchColumn();

// 🎯 เพิ่มดึงสถิติรายการ Barter ที่รออนุมัติ (เพิ่มแค่บรรทัดนี้ในส่วน PHP)
$count_pending_barters = $db->query("SELECT COUNT(*) FROM barter_posts WHERE status = 'pending'")->fetchColumn();

// ดึงประวัติการทำงาน
$log_stmt = $db->query("SELECT l.*, u.fullname as admin_name FROM admin_logs l JOIN users u ON l.admin_id = u.id ORDER BY l.created_at DESC LIMIT 10");
$admin_logs = $log_stmt->fetchAll();

// 2. ดึงรายการรออนุมัติ
$pending_stmt = $db->query("SELECT s.*, u.fullname FROM shops s JOIN users u ON s.user_id = u.id WHERE s.status = 'pending' ORDER BY s.created_at DESC LIMIT 5");
$pending_shops = $pending_stmt->fetchAll();

$report_stmt = $db->query("SELECT r.*, u.fullname as reporter_name FROM reports r JOIN users u ON r.reporter_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 5");
$pending_reports = $report_stmt->fetchAll();

// 3. ดึงรายการสินค้าในถังขยะ
$trash_stmt = $db->query("
    SELECT p.id, p.title, p.price, p.deleted_at, s.shop_name, u.fullname as deleter_name 
    FROM products p 
    JOIN shops s ON p.shop_id = s.id 
    LEFT JOIN users u ON p.deleted_by = u.id 
    WHERE p.is_deleted = 1 
    ORDER BY p.deleted_at DESC LIMIT 5
");
$trashed_products = $trash_stmt->fetchAll();

// 🎯 🛠️ 4. ดึงรายการคอมเมนต์ในถังขยะ
$trash_rev_stmt = $db->query("
    SELECT r.id, r.comment, r.deleted_at, p.title as product_name, u.fullname as author_name, del_u.fullname as deleter_name 
    FROM reviews r 
    JOIN products p ON r.product_id = p.id 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN users del_u ON r.deleted_by = del_u.id 
    WHERE r.is_deleted = 1 
    ORDER BY r.deleted_at DESC LIMIT 5
");
$trashed_reviews = $trash_rev_stmt->fetchAll();
?>

<style>
    /* ============================================================
       🛠️ SOLID DESIGN SYSTEM - ADMIN DASHBOARD
       ============================================================ */
    :root {
        --solid-bg: #f8fafc;
        --solid-card: #ffffff;
        --solid-text: #0f172a;
        --solid-border: #cbd5e1;
        --solid-primary: #4f46e5;
        --solid-success: #10b981;
        --solid-warning: #f59e0b;
        --solid-danger: #ef4444;
        --solid-info: #0ea5e9;
        /* 🎯 สีใหม่สำหรับ Barter Module */
        --solid-purple: #8b5cf6; 
    }

    .dark-theme {
        --solid-bg: #0f172a;
        --solid-card: #1e293b;
        --solid-text: #ffffff;
        --solid-border: #334155;
        --solid-primary: #6366f1;
        --solid-purple: #a78bfa;
    }

    body { background-color: var(--solid-bg) !important; color: var(--solid-text); }

    .admin-wrapper { max-width: 1200px; margin: 40px auto 80px; padding: 0 20px; }

    /* 🏰 Header */
    .dashboard-header { 
        margin-bottom: 40px; 
        border-left: 6px solid var(--solid-primary); 
        padding-left: 20px; 
        animation: dropIn 0.5s ease forwards;
    }
    .dashboard-header h1 { font-size: 2.2rem; font-weight: 900; margin: 0; color: var(--solid-text); letter-spacing: -1px; }

    /* 📊 Stat Cards */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .stat-card { 
        background: var(--solid-card); 
        padding: 25px; 
        border-radius: 20px; 
        border: 2px solid var(--solid-border); 
        display: flex; 
        align-items: center; 
        gap: 20px; 
        transition: all 0.3s ease; 
        box-shadow: 0 10px 20px rgba(0,0,0,0.02);
    }
    .stat-card:hover { transform: translateY(-5px); border-color: var(--solid-primary); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
    .stat-icon { width: 65px; height: 65px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0; }

    /* ⚡ Action Cards */
    .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 25px; margin-bottom: 50px; }
    .action-card { 
        background: var(--solid-card); 
        border-radius: 24px; 
        padding: 30px; 
        border: 2px solid var(--solid-border); 
        display: flex; 
        flex-direction: column; 
        justify-content: space-between; 
        transition: 0.3s;
    }
    .action-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
    
    .btn-action-solid { 
        width: 100%; 
        padding: 16px; 
        border-radius: 16px; 
        font-weight: 800; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        gap: 10px; 
        margin-top: 25px; 
        text-decoration: none; 
        transition: 0.2s; 
        border: none;
        color: white;
    }
    .btn-action-solid:hover { filter: brightness(1.15); transform: scale(1.02); }

    /* 🔴 Badges */
    .noti-badge { color: white; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 800; }
    .log-badge { background: rgba(99, 102, 241, 0.1); color: var(--solid-primary); padding: 6px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; border: 1px solid rgba(99, 102, 241, 0.2); }

    /* 📋 Solid Tables (Split Sections) */
    .split-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 30px; margin-bottom: 40px; }
    
    .content-card {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
    }
    
    .table-solid { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
    .table-solid tr { background: var(--solid-bg); transition: 0.2s; }
    .table-solid tr:hover { background: rgba(99, 102, 241, 0.05); }
    .table-solid td { padding: 15px 20px; border: 1px solid var(--solid-border); border-width: 1px 0; }
    .table-solid td:first-child { border-left-width: 1px; border-radius: 12px 0 0 12px; }
    .table-solid td:last-child { border-right-width: 1px; border-radius: 0 12px 12px 0; text-align: right; }

    /* 🪄 Animations */
    .stagger-in { opacity: 0; transform: translateY(20px); }
    .stagger-in.show { opacity: 1; transform: translateY(0); transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); }

    @keyframes dropIn { to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .split-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="admin-wrapper">
    <div class="dashboard-header">
        <h1>แผงควบคุมแอดมิน</h1>
        <p style="color: var(--text-muted); margin-top: 5px; font-weight: 600; font-size: 1.05rem;">จัดการความเรียบร้อยและอนุมัติร้านค้า/สินค้าภายใน BNCC Market</p>
    </div>

    <div class="stat-grid stagger-in">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--solid-primary); border: 2px solid var(--solid-primary);"><i class="fas fa-users"></i></div>
            <div>
                <div style="font-size: 2rem; font-weight: 900; line-height: 1;"><?= $count_users ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-top: 5px;">สมาชิก</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--solid-success); border: 2px solid var(--solid-success);"><i class="fas fa-store"></i></div>
            <div>
                <div style="font-size: 2rem; font-weight: 900; line-height: 1;"><?= $count_shops ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-top: 5px;">ร้านค้า</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--solid-warning); border: 2px solid var(--solid-warning);"><i class="fas fa-bullhorn"></i></div>
            <div>
                <div style="font-size: 2rem; font-weight: 900; line-height: 1; color: var(--solid-warning);"><?= $count_pending_wtb ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-top: 5px;">WTB รออนุมัติ</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--solid-purple); border: 2px solid var(--solid-purple);"><i class="fas fa-exchange-alt"></i></div>
            <div>
                <div style="font-size: 2rem; font-weight: 900; line-height: 1; color: var(--solid-purple);"><?= $count_pending_barters ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-top: 5px;">Barter รออนุมัติ</div>
            </div>
        </div>

        <div class="stat-card" style="border-color: var(--solid-danger);">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--solid-danger); border: 2px solid var(--solid-danger);"><i class="fas fa-trash-alt"></i></div>
            <div>
                <div style="font-size: 2rem; font-weight: 900; line-height: 1; color: var(--solid-danger);"><?= $count_trashed_products + $count_trashed_comments ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-top: 5px;">ถังขยะ</div>
            </div>
        </div>
    </div>

    <div class="action-grid stagger-in">
        <div class="action-card" style="border-left: 6px solid var(--solid-warning);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 5px; font-weight: 900;"><i class="fas fa-magnifying-glass-dollar text-warning"></i> จัดการโพสต์ WTB</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; margin: 0;">ตรวจสอบและอนุมัติประกาศตามหาของ</p>
                </div>
                <?php if($count_pending_wtb > 0): ?>
                    <span class="noti-badge" style="background: var(--solid-danger); box-shadow: 0 4px 10px rgba(239,68,68,0.4);"><?= $count_pending_wtb ?></span>
                <?php endif; ?>
            </div>
            <a href="approve_wtb.php" class="btn-action-solid" style="background: var(--solid-warning); color: #000; box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);">จัดการ WTB</a>
        </div>

        <div class="action-card" style="border-left: 6px solid var(--solid-purple);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 5px; font-weight: 900;"><i class="fas fa-sync text-purple" style="color: var(--solid-purple);"></i> จัดการระบบ Barter</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; margin: 0;">อนุมัติการลงประกาศแลกของ</p>
                </div>
                <?php if($count_pending_barters > 0): ?>
                    <span class="noti-badge" style="background: var(--solid-danger); box-shadow: 0 4px 10px rgba(239,68,68,0.4);"><?= $count_pending_barters ?></span>
                <?php endif; ?>
            </div>
            <a href="approve_barter.php" class="btn-action-solid" style="background: var(--solid-purple); color: #fff; box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);">ตรวจสอบ Barter</a>
        </div>

        <div class="action-card" style="border-left: 6px solid var(--solid-primary);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 5px; font-weight: 900;"><i class="fas fa-boxes-stacked text-primary"></i> จัดการสินค้า</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; margin: 0;">อนุมัติสินค้าใหม่เข้าระบบ</p>
                </div>
                <?php if($count_pending_products > 0): ?>
                    <span class="noti-badge" style="background: var(--solid-danger); box-shadow: 0 4px 10px rgba(239,68,68,0.4);"><?= $count_pending_products ?></span>
                <?php endif; ?>
            </div>
            <a href="approve_product.php" class="btn-action-solid" style="background: var(--solid-primary); box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);">อนุมัติสินค้า</a>
        </div>

        <div class="action-card" style="border-left: 6px solid var(--solid-success);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 5px; font-weight: 900;"><i class="fas fa-shop text-success"></i> จัดการร้านค้า</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; margin: 0;">ตรวจสอบตัวตนเจ้าของร้าน</p>
                </div>
                <?php if($count_pending_shops > 0): ?>
                    <span class="noti-badge" style="background: var(--solid-danger); box-shadow: 0 4px 10px rgba(239,68,68,0.4);"><?= $count_pending_shops ?></span>
                <?php endif; ?>
            </div>
            <a href="approve_shop.php" class="btn-action-solid" style="background: var(--solid-bg); color: var(--solid-success); border: 2px solid var(--solid-success);">อนุมัติร้านค้า</a>
        </div>

        <div class="action-card" style="border-left: 6px solid var(--solid-danger);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 5px; font-weight: 900;"><i class="fas fa-bullhorn text-danger"></i> คำร้องเรียน</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; margin: 0;">จัดการเนื้อหาที่ถูกรายงาน</p>
                </div>
                <?php if($count_pending_reports > 0): ?>
                    <span class="noti-badge" style="background: var(--solid-danger); box-shadow: 0 4px 10px rgba(239,68,68,0.4);"><?= $count_pending_reports ?></span>
                <?php endif; ?>
            </div>
            <a href="manage_reports.php" class="btn-action-solid" style="background: var(--solid-danger); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);">ดูรีพอร์ตทั่วไป</a>
        </div>

        <div class="action-card" style="border-left: 6px solid var(--solid-text);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 5px; font-weight: 900;"><i class="fas fa-user-shield"></i> จัดการสมาชิก</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; margin: 0;">สั่งแบนหรือแต่งตั้งแอดมิน</p>
                </div>
            </div>
            <a href="manage_members.php" class="btn-action-solid" style="background: var(--solid-text); color: var(--solid-bg);">
                จัดการสมาชิก <i class="fas fa-gavel"></i>
            </a>
        </div>
    </div>

    <div class="split-grid stagger-in">
        <div class="content-card">
            <h2 style="font-size: 1.4rem; font-weight: 900; margin: 0 0 25px;"><i class="fas fa-clock text-warning"></i> ร้านค้าที่รอตรวจสอบ</h2>
            <?php if(count($pending_shops) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="table-solid">
                        <tbody>
                            <?php foreach($pending_shops as $s): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: var(--solid-text);"><?= e($s['shop_name']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">โดย: <?= e($s['fullname']) ?></div>
                                </td>
                                <td>
                                    <a href="approve_shop.php?id=<?= $s['id'] ?>" class="btn-action-solid" style="margin: 0; padding: 8px 15px; font-size: 0.8rem; width: max-content; float: right; background: var(--solid-primary);">ตรวจสอบ</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-muted); padding: 30px; border: 2px dashed var(--solid-border); border-radius: 16px;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px; color: var(--solid-success); opacity: 0.5;"></i>
                    <p style="font-weight: 700; margin: 0;">ไม่มีรายการร้านค้าค้างอนุมัติ</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-card">
            <h2 style="font-size: 1.4rem; font-weight: 900; margin: 0 0 25px;"><i class="fas fa-bullhorn text-danger"></i> คำร้องเรียนล่าสุด</h2>
            <?php if(count($pending_reports) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="table-solid">
                        <tbody>
                            <?php foreach($pending_reports as $r): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: var(--solid-text);">ประเภท: <span style="color: var(--solid-danger);"><?= strtoupper($r['target_type']) ?></span></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">แจ้งโดย: <?= e($r['reporter_name']) ?></div>
                                </td>
                                <td>
                                    <a href="manage_reports.php?id=<?= $r['id'] ?>" class="btn-action-solid" style="margin: 0; padding: 8px 15px; font-size: 0.8rem; width: max-content; float: right; background: var(--solid-danger);">จัดการ</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-muted); padding: 30px; border: 2px dashed var(--solid-border); border-radius: 16px;">
                    <i class="fas fa-shield-alt" style="font-size: 2rem; margin-bottom: 10px; color: var(--solid-success); opacity: 0.5;"></i>
                    <p style="font-weight: 700; margin: 0;">ไม่มีการแจ้งร้องเรียนในขณะนี้</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="split-grid stagger-in">
        <div class="content-card" style="border-color: var(--solid-danger);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="font-size: 1.2rem; font-weight: 900; margin: 0; color: var(--solid-danger);"><i class="fas fa-box-open"></i> ถังขยะ: สินค้า</h2>
                <span style="font-size: 0.75rem; font-weight: 800; color: var(--solid-text); background: var(--solid-bg); padding: 4px 10px; border-radius: 8px;">ลบถาวรใน 30 วัน</span>
            </div>
            <?php if(count($trashed_products) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="table-solid">
                        <tbody>
                            <?php foreach($trashed_products as $t): 
                                $deleted_date = new DateTime($t['deleted_at'] ?? 'now'); 
                                $expire_date = clone $deleted_date; 
                                $expire_date->modify('+30 days'); 
                                $now = new DateTime(); 
                                $days_left = $now->diff($expire_date)->days;
                            ?>
                            <tr style="background: rgba(239, 68, 68, 0.05);">
                                <td>
                                    <div style="font-size: 0.95rem; font-weight: 800; color: var(--solid-text);"><?= e($t['title']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">ลบโดย: <span style="color: var(--solid-danger);"><?= e($t['deleter_name'] ?? 'คนขาย') ?></span></div>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; font-weight: 800; color: var(--solid-danger);"><i class="fas fa-hourglass-half"></i> อีก <?= $days_left ?> วัน</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-muted); padding: 20px; font-weight: 600;">ไม่มีสินค้าในถังขยะ</p>
            <?php endif; ?>
        </div>

        <div class="content-card" style="border-color: var(--solid-danger);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="font-size: 1.2rem; font-weight: 900; margin: 0; color: var(--solid-danger);"><i class="fas fa-comments"></i> ถังขยะ: คอมเมนต์</h2>
                <span style="font-size: 0.75rem; font-weight: 800; color: var(--solid-text); background: var(--solid-bg); padding: 4px 10px; border-radius: 8px;">ลบถาวรใน 30 วัน</span>
            </div>
            <?php if(count($trashed_reviews) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="table-solid">
                        <tbody>
                            <?php foreach($trashed_reviews as $tr): 
                                $deleted_date = new DateTime($tr['deleted_at'] ?? 'now'); 
                                $expire_date = clone $deleted_date; 
                                $expire_date->modify('+30 days'); 
                                $now = new DateTime(); 
                                $days_left = $now->diff($expire_date)->days;
                            ?>
                            <tr style="background: rgba(239, 68, 68, 0.05);">
                                <td>
                                    <div style="font-size: 0.95rem; font-weight: 800; color: var(--solid-text); font-style: italic;">"<?= e(mb_substr($tr['comment'], 0, 30)) ?>..."</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">จาก: <?= e($tr['author_name']) ?> | ลบโดย: <span style="color: var(--solid-danger);"><?= e($tr['deleter_name'] ?? 'ระบบ') ?></span></div>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; font-weight: 800; color: var(--solid-danger);"><i class="fas fa-hourglass-half"></i> อีก <?= $days_left ?> วัน</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-muted); padding: 20px; font-weight: 600;">ไม่มีคอมเมนต์ในถังขยะ</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="content-card stagger-in">
        <h2 style="font-size: 1.4rem; font-weight: 900; margin: 0 0 25px;"><i class="fas fa-history text-info"></i> ประวัติการทำงานล่าสุด (Admin Logs)</h2>
        <?php if(count($admin_logs) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-solid" style="border-spacing: 0;">
                    <thead style="background: var(--solid-bg);">
                        <tr>
                            <th style="padding: 15px 20px; font-size: 0.8rem; color: var(--text-muted); border-bottom: 2px solid var(--solid-border);">เวลา</th>
                            <th style="padding: 15px 20px; font-size: 0.8rem; color: var(--text-muted); border-bottom: 2px solid var(--solid-border);">ผู้ดูแล</th>
                            <th style="padding: 15px 20px; font-size: 0.8rem; color: var(--text-muted); border-bottom: 2px solid var(--solid-border);">ประเภท</th>
                            <th style="padding: 15px 20px; font-size: 0.8rem; color: var(--text-muted); border-bottom: 2px solid var(--solid-border);">รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($admin_logs as $log): ?>
                        <tr>
                            <td style="border: none; border-bottom: 1px solid var(--solid-border); border-radius: 0; padding: 15px 20px; font-weight: 700; font-size: 0.85rem; width: 120px;">
                                <?= date('d/m H:i', strtotime($log['created_at'])) ?>
                            </td>
                            <td style="border: none; border-bottom: 1px solid var(--solid-border); border-radius: 0; padding: 15px 20px; font-weight: 800; color: var(--solid-text); width: 180px;">
                                <?= e($log['admin_name']) ?>
                            </td>
                            <td style="border: none; border-bottom: 1px solid var(--solid-border); border-radius: 0; padding: 15px 20px; width: 200px;">
                                <span class="log-badge"><?= $log['action_type'] ?></span>
                            </td>
                            <td style="border: none; border-bottom: 1px solid var(--solid-border); border-radius: 0; padding: 15px 20px; color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">
                                <?= e($log['details']) ?> <span style="font-size: 0.8rem; opacity: 0.7;">(ID: <?= $log['target_id'] ?>)</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted); border: 2px dashed var(--solid-border); border-radius: 16px; font-weight: 700;">ยังไม่มีประวัติการบันทึก</div>
        <?php endif; ?>
    </div>
</div>

<script>
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('show');
                }, index * 100); 
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.stagger-in').forEach(el => observer.observe(el));
</script>

<?php require_once '../includes/footer.php'; ?>