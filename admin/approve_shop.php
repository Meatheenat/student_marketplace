<?php
/**
 * Student Marketplace - Shop Approval Page
 */
$pageTitle = "จัดการคำร้องเปิดร้านค้า";
require_once '../includes/header.php';

checkRole('admin');

$db = getDB();

// 1. จัดการการอนุมัติหรือไม่อนุมัติ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_id = (int)$_POST['shop_id'];
    $action  = $_POST['action']; // 'approve' หรือ 'reject'

    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE shops SET status = 'approved' WHERE id = ?");
        $stmt->execute([$shop_id]);
        $_SESSION['flash_message'] = "อนุมัติร้านค้าเรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "success";
    } elseif ($action === 'reject') {
        // กรณีไม่อนุมัติ เราอาจจะลบร้านนี้ออกไปเลยเพื่อความสะอาดของข้อมูล
        $stmt = $db->prepare("DELETE FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $_SESSION['flash_message'] = "ลบคำร้องขอเปิดร้านค้าที่ไม่เหมาะสมเรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "warning";
    }
}

// 2. ดึงรายการร้านค้าที่รออนุมัติทั้งหมด (ดึง user_id มาด้วยเพื่อลิงก์ไปหน้าโปรไฟล์/แชท)
$stmt = $db->query("SELECT s.*, u.id as user_id, u.fullname, u.class_room, u.email 
                    FROM shops s 
                    JOIN users u ON s.user_id = u.id 
                    WHERE s.status = 'pending' 
                    ORDER BY s.created_at ASC");
$pending_shops = $stmt->fetchAll();
?>

<div style="margin-bottom: 30px;">
    <a href="admin_dashboard.php" style="color: var(--text-muted); font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> กลับไป Dashboard</a>
    <h1 style="margin-top: 10px;">รายการรออนุมัติร้านค้า</h1>
</div>

<?php if(count($pending_shops) > 0): ?>
    <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
        <?php foreach($pending_shops as $s): ?>
            <div style="background: var(--bg-card); padding: 25px; border-radius: 16px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; box-shadow: var(--shadow);">
                <div style="flex: 1; min-width: 300px;">
                    <h3 style="margin-bottom: 5px; color: var(--primary-color);">
                        <?php echo e($s['shop_name']); ?>
                    </h3>
                    <p style="font-size: 0.95rem; margin-bottom: 10px;"><?php echo e($s['description']); ?></p>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">
                        <span><i class="fas fa-user"></i> <strong>เจ้าของ:</strong> <?php echo e($s['fullname']); ?> (<?php echo e($s['class_room']); ?>)</span>
                        <span><i class="fas fa-envelope"></i> <?php echo e($s['email']); ?></span>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <a href="../pages/profile.php?id=<?php echo $s['user_id']; ?>" class="btn" style="background: var(--bg-body); color: var(--text-main); border: 1px solid var(--border-color); padding: 5px 12px; font-size: 0.85rem; border-radius: 8px;">
                            <i class="fas fa-id-badge" style="color: var(--primary-color);"></i> ดูโปรไฟล์
                        </a>
                        
                        <a href="../pages/chat.php?receiver_id=<?php echo $s['user_id']; ?>" class="btn" style="background: var(--bg-body); color: var(--text-main); border: 1px solid var(--border-color); padding: 5px 12px; font-size: 0.85rem; border-radius: 8px;">
                            <i class="fas fa-comment-dots" style="color: #0ea5e9;"></i> แชทติดต่อ
                        </a>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <form action="approve_shop.php" method="POST" style="display: inline;">
                        <input type="hidden" name="shop_id" value="<?php echo $s['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn" style="background: var(--color-success); color: white; border-radius: 12px; padding: 10px 20px;">
                            <i class="fas fa-check"></i> อนุมัติ
                        </button>
                    </form>
                    
                    <form action="approve_shop.php" method="POST" style="display: inline;">
                        <input type="hidden" name="shop_id" value="<?php echo $s['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn" style="background: var(--color-danger); color: white; border-radius: 12px; padding: 10px 20px;" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปฏิเสธคำร้องนี้?')">
                            <i class="fas fa-times"></i> ไม่อนุมัติ
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div style="text-align: center; padding: 100px 0; background: var(--bg-card); border-radius: 16px; border: 1px dashed var(--border-color);">
        <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--color-success); opacity: 0.3; margin-bottom: 20px;"></i>
        <h3>เย้! ตรวจสอบครบหมดแล้ว</h3>
        <p style="color: var(--text-muted);">ไม่มีร้านค้าที่รอการอนุมัติในขณะนี้</p>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>