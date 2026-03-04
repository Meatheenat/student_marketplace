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

// 2. ดึงรายการร้านค้าที่รออนุมัติ (ล่าสุด 5 รายการ)
$pending_stmt = $db->query("SELECT s.*, u.fullname FROM shops s JOIN users u ON s.user_id = u.id WHERE s.status = 'pending' ORDER BY s.created_at DESC LIMIT 5");
$pending_shops = $pending_stmt->fetchAll();
?>

<style>
    /* 🎨 Dashboard Specific Styles */
    .dashboard-header { margin-bottom: 35px; border-left: 6px solid var(--primary); padding-left: 20px; }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 40px; }
    
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

    .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; margin-bottom: 40px; }
    .action-card {
        background: var(--bg-card);
        border-radius: 24px;
        padding: 35px;
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
    }
    .action-card::before {
        content: ''; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: var(--primary);
    }
    
    .btn-action {
        width: 100%; padding: 14px; border-radius: 14px; font-weight: 700; 
        display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px;
    }
    
    .pending-badge { background: #ef4444; color: white; padding: 2px 8px; border-radius: 50px; font-size: 0.75rem; }
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
</div>

<div class="action-grid">
    <div class="action-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="font-size: 1.3rem; margin-bottom: 10px;"><i class="fas fa-boxes-stacked text-primary"></i> จัดการสินค้า</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0;">ตรวจสอบความถูกต้องและอนุมัติสินค้าใหม่</p>
            </div>
            <?php if($count_pending_products > 0): ?>
                <span class="pending-badge"><?= $count_pending_products ?> รอตรวจ</span>
            <?php endif; ?>
        </div>
        <a href="approve_product.php" class="btn btn-primary btn-action">
            เข้าสู่หน้าอนุมัติสินค้า <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <div class="action-card" style="border-color: var(--color-success);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="font-size: 1.3rem; margin-bottom: 10px;"><i class="fas fa-shop text-success"></i> จัดการร้านค้า</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0;">ตรวจสอบตัวตนเจ้าของร้านและอนุมัติร้านค้า</p>
            </div>
            <?php if($count_pending_shops > 0): ?>
                <span class="pending-badge"><?= $count_pending_shops ?> รอตรวจ</span>
            <?php endif; ?>
        </div>
        <a href="approve_shop.php" class="btn btn-outline btn-action" style="border-color: #10b981; color: #10b981;">
            เข้าสู่หน้าอนุมัติร้านค้า <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>

<div class="card shadow-lg" style="border-radius: 24px; padding: 40px; border: 1px solid var(--border-color); background: var(--bg-card);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="font-size: 1.4rem; font-weight: 700; margin: 0;"><i class="fas fa-clock text-warning"></i> ร้านค้าที่รอการตรวจสอบล่าสุด</h2>
        <a href="approve_shop.php" class="nav-link" style="font-size: 0.9rem; font-weight: 600;">ดูทั้งหมด <i class="fas fa-chevron-right"></i></a>
    </div>

    <?php if(count($pending_shops) > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">
                        <th style="padding: 15px;">วันที่สมัคร</th>
                        <th style="padding: 15px;">ชื่อร้านค้า</th>
                        <th style="padding: 15px;">เจ้าของร้าน</th>
                        <th style="padding: 15px; text-align: right;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pending_shops as $s): ?>
                    <tr style="background: var(--bg-body); transition: 0.2s;">
                        <td style="padding: 18px; border-radius: 12px 0 0 12px; font-size: 0.9rem;"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                        <td style="padding: 18px; font-weight: 600;"><?= e($s['shop_name']) ?></td>
                        <td style="padding: 18px; color: var(--text-muted);"><?= e($s['fullname']) ?></td>
                        <td style="padding: 18px; border-radius: 0 12px 12px 0; text-align: right;">
                            <a href="approve_shop.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-primary" style="padding: 8px 20px; border-radius: 10px;">
                                ตรวจสอบ
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 0; color: var(--text-muted);">
            <i class="fas fa-check-circle" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
            <p>ยอดเยี่ยม! ไม่มีรายการร้านค้าที่ค้างรออนุมัติ</p>
        </div>
    <?php endif; ?>
</div>


<?php require_once '../includes/footer.php'; ?>