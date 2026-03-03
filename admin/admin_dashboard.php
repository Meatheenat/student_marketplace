<?php
/**
 * Student Marketplace - Admin Dashboard
 */
$pageTitle = "ระบบผู้ดูแล (Admin)";
require_once '../includes/header.php';

// ตรวจสอบสิทธิ์ว่าเป็น Admin หรือไม่
checkRole('admin');

$db = getDB();

// 1. ดึงสถิติต่างๆ
$count_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$count_shops = $db->query("SELECT COUNT(*) FROM shops WHERE status = 'approved'")->fetchColumn();
$count_pending = $db->query("SELECT COUNT(*) FROM shops WHERE status = 'pending'")->fetchColumn();
$count_products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

// 2. ดึงรายการร้านค้าที่รออนุมัติ (ล่าสุด 5 รายการ)
$pending_stmt = $db->query("SELECT s.*, u.fullname FROM shops s JOIN users u ON s.user_id = u.id WHERE s.status = 'pending' ORDER BY s.created_at DESC LIMIT 5");
$pending_shops = $pending_stmt->fetchAll();
?>

<div style="margin-bottom: 30px;">
    <h1>แผงควบคุมผู้ดูแลระบบ</h1>
    <p style="color: var(--text-muted);">จัดการความเรียบร้อยและอนุมัติร้านค้าภายในโรงเรียน</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
    <div style="background: var(--bg-card); padding: 25px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center; box-shadow: var(--shadow);">
        <i class="fas fa-users" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 10px;"></i>
        <div style="font-size: 1.5rem; font-weight: 600;"><?php echo $count_users; ?></div>
        <div style="color: var(--text-muted); font-size: 0.9rem;">สมาชิกทั้งหมด</div>
    </div>
    <div style="background: var(--bg-card); padding: 25px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center; box-shadow: var(--shadow);">
        <i class="fas fa-store" style="font-size: 2rem; color: var(--color-success); margin-bottom: 10px;"></i>
        <div style="font-size: 1.5rem; font-weight: 600;"><?php echo $count_shops; ?></div>
        <div style="color: var(--text-muted); font-size: 0.9rem;">ร้านค้าที่เปิดอยู่</div>
    </div>
    <div style="background: var(--bg-card); padding: 25px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center; box-shadow: var(--shadow); border-top: 4px solid var(--color-warning);">
        <i class="fas fa-clock" style="font-size: 2rem; color: var(--color-warning); margin-bottom: 10px;"></i>
        <div style="font-size: 1.5rem; font-weight: 600;"><?php echo $count_pending; ?></div>
        <div style="color: var(--text-muted); font-size: 0.9rem;">รอการอนุมัติ</div>
    </div>
    <div style="background: var(--bg-card); padding: 25px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center; box-shadow: var(--shadow);">
        <i class="fas fa-box" style="font-size: 2rem; color: var(--color-info); margin-bottom: 10px;"></i>
        <div style="font-size: 1.5rem; font-weight: 600;"><?php echo $count_products; ?></div>
        <div style="color: var(--text-muted); font-size: 0.9rem;">สินค้าทั้งหมด</div>
    </div>
</div>

<div style="background: var(--bg-card); padding: 30px; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-size: 1.3rem;"><i class="fas fa-clipboard-check"></i> ร้านค้าที่รอการตรวจสอบ</h2>
        <a href="approve_shop.php" class="btn btn-outline" style="font-size: 0.85rem;">ดูทั้งหมด</a>
    </div>

    <?php if(count($pending_shops) > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="border-bottom: 2px solid var(--border-color);">
                    <tr>
                        <th style="padding: 12px;">วันที่สมัคร</th>
                        <th style="padding: 12px;">ชื่อร้านค้า</th>
                        <th style="padding: 12px;">เจ้าของร้าน</th>
                        <th style="padding: 12px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pending_shops as $s): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px; font-size: 0.9rem;"><?php echo date('d/m/Y', strtotime($s['created_at'])); ?></td>
                        <td style="padding: 12px; font-weight: 500;"><?php echo e($s['shop_name']); ?></td>
                        <td style="padding: 12px;"><?php echo e($s['fullname']); ?></td>
                        <td style="padding: 12px;">
                            <a href="approve_shop.php" class="btn btn-primary" style="padding: 5px 15px; font-size: 0.8rem;">ตรวจสอบ</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: var(--text-muted); padding: 20px;">ไม่มีรายการรออนุมัติในขณะนี้</p>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>