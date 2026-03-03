<?php
/**
 * Student Marketplace - Seller Dashboard
 */
$pageTitle = "แผงควบคุมผู้ขาย";
require_once '../includes/header.php';

// ตรวจสอบสิทธิ์ว่าเป็นผู้ขายหรือไม่
checkRole('seller');

$db = getDB();
$user_id = $_SESSION['user_id'];

// 1. ตรวจสอบว่านักเรียนคนนี้มีร้านค้าหรือยัง
$shop_stmt = $db->prepare("SELECT * FROM shops WHERE user_id = ?");
$shop_stmt->execute([$user_id]);
$shop = $shop_stmt->fetch();

// 2. ถ้ายังไม่มีร้านค้า ให้ดีดไปหน้าสร้างร้านค้า (edit_shop.php)
if (!$shop) {
    echo "<div style='text-align:center; padding:50px; background:var(--bg-card); border-radius:16px;'>
            <i class='fas fa-store-slash' style='font-size:3rem; color:var(--text-muted);'></i>
            <h2 style='margin-top:20px;'>คุณยังไม่มีหน้าร้านค้า</h2>
            <p style='color:var(--text-muted);'>เริ่มสร้างร้านค้าของคุณเพื่อลงขายสินค้าตัวแรกเลย!</p>
            <a href='edit_shop.php' class='btn btn-primary' style='margin-top:20px;'>สร้างร้านค้าของฉัน</a>
          </div>";
    require_once '../includes/footer.php';
    exit;
}

// 3. ถ้ามีร้านแล้ว ดึงสินค้าทั้งหมดของร้านนี้
$shop_id = $shop['id'];
$prod_stmt = $db->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.shop_id = ? ORDER BY p.created_at DESC");
$prod_stmt->execute([$shop_id]);
$products = $prod_stmt->fetchAll();

// 4. จัดการการลบสินค้า (Delete)
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    // ตรวจสอบความปลอดภัย: สินค้านี้ต้องเป็นของร้านเราจริงๆ
    $check_del = $db->prepare("DELETE FROM products WHERE id = ? AND shop_id = ?");
    if ($check_del->execute([$del_id, $shop_id])) {
        $_SESSION['flash_message'] = "ลบสินค้าเรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "success";
        redirect('dashboard.php');
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1>ร้าน: <?php echo e($shop['shop_name']); ?></h1>
        <p style="color: var(--text-muted);">
            สถานะร้านค้า: 
            <?php if($shop['status'] === 'approved'): ?>
                <span style="color: var(--color-success); font-weight: 600;">✅ อนุมัติแล้ว</span>
            <?php else: ?>
                <span style="color: var(--color-warning); font-weight: 600;">⏳ รอการอนุมัติจากครู</span>
            <?php endif; ?>
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="edit_shop.php" class="btn btn-outline">ตั้งค่าร้านค้า</a>
        <a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> เพิ่มสินค้าใหม่</a>
    </div>
</div>

<div style="background: var(--bg-card); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead style="background: var(--bg-body); border-bottom: 1px solid var(--border-color);">
            <tr>
                <th style="padding: 15px;">รูปภาพ</th>
                <th style="padding: 15px;">ชื่อสินค้า</th>
                <th style="padding: 15px;">ราคา</th>
                <th style="padding: 15px;">สถานะ</th>
                <th style="padding: 15px;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $p): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 15px;">
                        <img src="<?php echo !empty($p['image_url']) ? '../assets/images/products/'.$p['image_url'] : 'https://via.placeholder.com/60' ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                    </td>
                    <td style="padding: 15px;">
                        <div style="font-weight: 500;"><?php echo e($p['title']); ?></div>
                        <small style="color: var(--text-muted);"><?php echo e($p['category_name']); ?></small>
                    </td>
                    <td style="padding: 15px;"><?php echo formatPrice($p['price']); ?></td>
                    <td style="padding: 15px;"><?php echo getProductStatusBadge($p['product_status']); ?></td>
                    <td style="padding: 15px;">
                        <a href="add_product.php?id=<?php echo $p['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem; background: var(--bg-body); border: 1px solid var(--border-color);">แก้ไข</a>
                        <a href="dashboard.php?delete_id=<?php echo $p['id']; ?>" class="btn btn-delete" style="padding: 5px 10px; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: var(--color-danger);">ลบ</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">คุณยังไม่มีสินค้าที่ลงขาย</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>