<?php
/**
 * Student Marketplace - Admin Dashboard (High Performance UI)
 */
$pageTitle = "ระบบผู้ดูแล (Admin) - BNCC Market";
require_once '../includes/header.php';

// ตรวจสอบสิทธิ์แอดมิน
checkRole('admin');

$db = getDB();

// 1. ดึงสถิติภาพรวม
$count_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$count_shops = $db->query("SELECT COUNT(*) FROM shops WHERE status = 'approved'")->fetchColumn();
$count_pending_shops = $db->query("SELECT COUNT(*) FROM shops WHERE status = 'pending'")->fetchColumn();
$count_products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$count_pending_products = $db->query("SELECT COUNT(*) FROM products WHERE status = 'pending'")->fetchColumn();

// 🛠️ เพิ่ม: ดึงสถิติคำร้องเรียน (Reports)
$count_pending_reports = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

// 🛠️ เพิ่ม: ดึงรายการประวัติการทำงานของแอดมินล่าสุด (Audit Log)
$log_stmt = $db->query("
    SELECT l.*, u.fullname as admin_name 
    FROM admin_logs l 
    JOIN users u ON l.admin_id = u.id 
    ORDER BY l.created_at DESC LIMIT 10
");
$admin_logs = $log_stmt->fetchAll();

// 2. ดึงรายการร้านค้าที่รออนุมัติ (ล่าสุด 5 รายการ)
$pending_stmt = $db->query("SELECT s.*, u.fullname FROM shops s JOIN users u ON s.user_id = u.id WHERE s.status = 'pending' ORDER BY s.created_at DESC LIMIT 5");
$pending_shops = $pending_stmt->fetchAll();

// 🛠️ เพิ่ม: ดึงรายการรีพอร์ตล่าสุด (5 รายการ)
$report_stmt = $db->query("SELECT r.*, u.fullname as reporter_name FROM reports r JOIN users u ON r.reporter_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 5");
$pending_reports = $report_stmt->fetchAll();
?>

<style>
    /* 🎨 Dashboard Specific Styles */
    .dashboard-header { margin-bottom: 35px; border-left: 6px solid var(--primary); padding-left: 20px; }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
    
    .stat-card {
        background: var(--bg-card);
        padding: 30px;
        border-radius: 24px;
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: var(--shadow-sm);
    }
    .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); border-color: var(--primary); }
    .stat-icon { width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

    /* ปรับ Action Grid ให้รองรับ 4 การ์ด */
    .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px; }
    .action-card {
        background: var(--bg-card);
        border-radius: 24px;
        padding: 35px;
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .action-card::before {
        content: ''; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: var(--primary);
    }
    
    .btn-action {
        width: 100%; padding: 14px; border-radius: 14px; font-weight: 700; 
        display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px;
        text-decoration: none;
        transition: 0.2s;
    }
    .btn-action:hover { filter: brightness(1.1); transform: scale(1.02); }
    
    .pending-badge { background: #ef4444; color: white; padding: 2px 8px; border-radius: 50px; font-size: 0.75rem; }
    .report-badge { background: #f87171; color: white; padding: 2px 8px; border-radius: 50px; font-size: 0.75rem; }

    /* Style สำหรับ Audit Log */
    .log-badge { background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; }
</style>

<div class="dashboard-header">
    <h1 style="font-size: 2.2rem; font-weight: 800; margin: 0;">แผงควบคุมแอดมิน</h1>
    <p style="color: var(--text-muted); margin-top: 5px;">จัดการความเรียบร้อยและอนุมัติร้านค้า/สินค้าภายใน BNCC Market</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;"><i class="fas fa-users"></i></div>
        <div>
            <div style="font-size: 1.8rem; font-weight: 800;"><?= $count_users ?></div>
            <div style="color: var(--text-muted); font-size: 0.9rem;">สมาชิกทั้งหมด</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-store"></i></div>
        <div>
            <div style="font-size: 1.8rem; font-weight: 800;"><?= $count_shops ?></div>
            <div style="color: var(--text-muted); font-size: 0.9rem;">ร้านค้าที่เปิดอยู่</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-box"></i></div>
        <div>
            <div style="font-size: 1.8rem; font-weight: 800;"><?= $count_products ?></div>
            <div style="color: var(--text-muted); font-size: 0.9rem;">สินค้าทั้งหมด</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-flag"></i></div>
        <div>
            <div style="font-size: 1.8rem; font-weight: 800;"><?= $count_pending_reports ?></div>
            <div style="color: var(--text-muted); font-size: 0.9rem;">รีพอร์ตที่ค้างอยู่</div>
        </div>
    </div>
</div>

<div class="action-grid">
    <div class="action-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="font-size: 1.2rem; margin-bottom: 10px;"><i class="fas fa-boxes-stacked text-primary"></i> จัดการสินค้า</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0;">อนุมัติสินค้าใหม่เข้าระบบ</p>
            </div>
            <?php if($count_pending_products > 0): ?>
                <span class="pending-badge"><?= $count_pending_products ?></span>
            <?php endif; ?>
        </div>
        <a href="approve_product.php" class="btn btn-primary btn-action">อนุมัติสินค้า</a>
    </div>

    <div class="action-card" style="border-color: #10b981;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="font-size: 1.2rem; margin-bottom: 10px;"><i class="fas fa-shop text-success"></i> จัดการร้านค้า</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0;">ตรวจสอบตัวตนเจ้าของร้าน</p>
            </div>
            <?php if($count_pending_shops > 0): ?>
                <span class="pending-badge"><?= $count_pending_shops ?></span>
            <?php endif; ?>
        </div>
        <a href="approve_shop.php" class="btn btn-outline btn-action" style="border-color: #10b981; color: #10b981;">อนุมัติร้านค้า</a>
    </div>

    <div class="action-card" style="border-color: #ef4444;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="font-size: 1.2rem; margin-bottom: 10px;"><i class="fas fa-bullhorn text-danger"></i> คำร้องเรียน</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0;">จัดการเนื้อหาที่ถูกรายงาน</p>
            </div>
            <?php if($count_pending_reports > 0): ?>
                <span class="report-badge"><?= $count_pending_reports ?></span>
            <?php endif; ?>
        </div>
        <a href="manage_reports.php" class="btn btn-danger btn-action">ดูรีพอร์ต</a>
    </div>

    <div class="action-card" style="border-color: #6366f1;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="font-size: 1.2rem; margin-bottom: 10px;"><i class="fas fa-user-shield text-primary"></i> จัดการสมาชิก</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0;">สั่งแบนสมาชิก หรือระงับร้านค้า</p>
            </div>
        </div>
        <a href="manage_members.php" class="btn btn-primary btn-action" style="background: #6366f1; border-color: #6366f1;">
            จัดการสมาชิกและสิทธิ์ <i class="fas fa-gavel"></i>
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <div class="card shadow-lg" style="border-radius: 24px; padding: 30px; border: 1px solid var(--border-color); background: var(--bg-card);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="font-size: 1.2rem; font-weight: 700; margin: 0;"><i class="fas fa-clock text-warning"></i> ร้านค้าที่รอการตรวจสอบล่าสุด</h2>
        </div>

        <?php if(count($pending_shops) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                    <tbody>
                        <?php foreach($pending_shops as $s): ?>
                        <tr style="background: var(--bg-body);">
                            <td style="padding: 15px; border-radius: 12px 0 0 12px; font-size: 0.85rem;"><?= e($s['shop_name']) ?></td>
                            <td style="padding: 15px; text-align: right; border-radius: 0 12px 12px 0;">
                                <a href="approve_shop.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-primary" style="font-size: 0.75rem;">ตรวจสอบ</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); padding: 20px;">ไม่มีรายการค้าง</p>
        <?php endif; ?>
    </div>

    <div class="card shadow-lg" style="border-radius: 24px; padding: 30px; border: 1px solid var(--border-color); background: var(--bg-card);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="font-size: 1.2rem; font-weight: 700; margin: 0;"><i class="fas fa-bullhorn text-danger"></i> คำร้องเรียนล่าสุด</h2>
        </div>

        <?php if(count($pending_reports) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                    <tbody>
                        <?php foreach($pending_reports as $r): ?>
                        <tr style="background: var(--bg-body);">
                            <td style="padding: 15px; border-radius: 12px 0 0 12px;">
                                <div style="font-size: 0.85rem; font-weight: 600;">ประเภท: <?= strtoupper($r['target_type']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">โดย: <?= e($r['reporter_name']) ?></div>
                            </td>
                            <td style="padding: 15px; text-align: right; border-radius: 0 12px 12px 0;">
                                <a href="manage_reports.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-danger" style="font-size: 0.75rem;">จัดการ</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); padding: 20px;">ไม่มีการแจ้งร้องเรียน</p>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-lg" style="border-radius: 24px; padding: 35px; border: 1px solid var(--border-color); background: var(--bg-card); margin-top: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-size: 1.3rem; font-weight: 700; margin: 0;"><i class="fas fa-history text-info"></i> ประวัติการทำงานล่าสุดของแอดมิน</h2>
    </div>

    <?php if(count($admin_logs) > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">
                        <th style="padding: 10px;">เวลา</th>
                        <th style="padding: 10px;">แอดมิน</th>
                        <th style="padding: 10px;">การกระทำ</th>
                        <th style="padding: 10px;">รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($admin_logs as $log): ?>
                    <tr style="background: var(--bg-body); transition: 0.2s;">
                        <td style="padding: 15px; border-radius: 12px 0 0 12px; font-size: 0.85rem; width: 150px;">
                            <?= date('d/m H:i', strtotime($log['created_at'])) ?>
                        </td>
                        <td style="padding: 15px; font-weight: 600; width: 200px;">
                            <?= e($log['admin_name']) ?>
                        </td>
                        <td style="padding: 15px; width: 180px;">
                            <span class="log-badge"><?= $log['action_type'] ?></span>
                        </td>
                        <td style="padding: 15px; border-radius: 0 12px 12px 0; color: var(--text-muted); font-size: 0.9rem;">
                            <?= e($log['details']) ?> (เป้าหมาย ID: <?= $log['target_id'] ?>)
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--text-muted);">ยังไม่มีประวัติการบันทึก</div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>