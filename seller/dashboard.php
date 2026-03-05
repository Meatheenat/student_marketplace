<?php
/**
 * Student Marketplace - Seller Dashboard (Order Management System + Analytics)
 */
require_once '../includes/functions.php';

// ตรวจสอบสิทธิ์ว่าเป็นผู้ขายหรือไม่
checkRole('seller');

$db = getDB();
$user_id = $_SESSION['user_id'];

// ตรวจสอบว่านักเรียนคนนี้มีร้านค้าหรือยัง
$shop_stmt = $db->prepare("SELECT * FROM shops WHERE user_id = ?");
$shop_stmt->execute([$user_id]);
$shop = $shop_stmt->fetch();

if ($shop) {
    $shop_id = $shop['id'];

    // --- 1. จัดการอัปเดตสถานะออเดอร์ (OMS) ---
    if (isset($_POST['update_order_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        
        $db->prepare("UPDATE orders SET status = ? WHERE id = ? AND shop_id = ?")->execute([$new_status, $order_id, $shop_id]);
        $_SESSION['flash_message'] = "อัปเดตสถานะออเดอร์เรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "success";
        redirect('dashboard.php');
    }

    // --- 2. จัดการการลบสินค้า (Soft Delete) ---
    if (isset($_GET['delete_id'])) {
        $del_id = (int)$_GET['delete_id'];
        $check_del = $db->prepare("UPDATE products SET is_deleted = 1 WHERE id = ? AND shop_id = ?");
        if ($check_del->execute([$del_id, $shop_id])) {
            $_SESSION['flash_message'] = "ลบสินค้าออกจากหน้าร้านเรียบร้อยแล้ว (Soft Delete)";
            $_SESSION['flash_type'] = "success";
            redirect('dashboard.php');
        }
    }

    // --- 🎯 🛠️ 3. [เพิ่มใหม่] ดึงข้อมูลสถิติภาพรวมร้านค้า ---
    // ยอดวิวรวม
    $total_views = $db->query("SELECT SUM(views) FROM products WHERE shop_id = $shop_id AND is_deleted = 0")->fetchColumn() ?? 0;
    // จำนวนคนกดหัวใจรวม
    $total_wishlist = $db->query("SELECT COUNT(*) FROM wishlist w JOIN products p ON w.product_id = p.id WHERE p.shop_id = $shop_id AND p.is_deleted = 0")->fetchColumn();
    // ยอดขายรวม (นับเฉพาะที่สำเร็จแล้ว)
    $total_sales = $db->query("SELECT COUNT(*) FROM orders WHERE shop_id = $shop_id AND status = 'completed'")->fetchColumn();

    // --- 4. ดึงสินค้าทั้งหมดของร้าน (เพิ่มยอดวิวและนับคนกดหัวใจรายชิ้น) ---
    $prod_stmt = $db->prepare("
        SELECT p.*, c.category_name, 
        (SELECT COUNT(*) FROM wishlist WHERE product_id = p.id) as wish_count 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.shop_id = ? AND p.is_deleted = 0 
        ORDER BY p.created_at DESC
    ");
    $prod_stmt->execute([$shop_id]);
    $products = $prod_stmt->fetchAll();

    // --- 5. ดึงคำสั่งซื้อ (Orders) ของร้านนี้ ---
    $order_stmt = $db->prepare("
        SELECT o.*, p.title as product_name, p.image_url, p.price, u.fullname as buyer_name, u.id as buyer_id 
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        JOIN users u ON o.buyer_id = u.id 
        WHERE o.shop_id = ? 
        ORDER BY o.created_at DESC
    ");
    $order_stmt->execute([$shop_id]);
    $orders = $order_stmt->fetchAll();
}

$pageTitle = "แผงควบคุมผู้ขาย - BNCC Market";
require_once '../includes/header.php';

// ถ้ายังไม่มีร้านค้า
if (!$shop) {
    echo "<div style='text-align:center; padding:50px; background:var(--bg-card); border-radius:16px;'>
            <i class='fas fa-store-slash' style='font-size:3rem; color:var(--text-muted);'></i>
            <h2 style='margin-top:20px;'>คุณยังไม่มีหน้าร้านค้า</h2>
            <a href='edit_shop.php' class='btn btn-primary' style='margin-top:20px;'>สร้างร้านค้าของฉัน</a>
          </div>";
    require_once '../includes/footer.php';
    exit;
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1>ร้าน: <?php echo e($shop['shop_name']); ?></h1>
        <p style="color: var(--text-muted);">จัดการออเดอร์และดูสถิติร้านค้าของคุณ</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="edit_shop.php" class="btn btn-outline">ตั้งค่าร้านค้า</a>
        <a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> เพิ่มสินค้าใหม่</a>
    </div>
</div>

<?php echo displayFlashMessage(); ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
    <div style="background: var(--bg-card); padding: 25px; border-radius: 20px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 20px;">
        <div style="width: 50px; height: 50px; background: rgba(99, 102, 241, 0.1); color: var(--primary); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
            <i class="fas fa-eye"></i>
        </div>
        <div>
            <div style="font-size: 1.5rem; font-weight: 800;"><?= number_format($total_views) ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">ยอดเข้าชมรวม</div>
        </div>
    </div>
    <div style="background: var(--bg-card); padding: 25px; border-radius: 20px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 20px;">
        <div style="width: 50px; height: 50px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
            <i class="fas fa-heart"></i>
        </div>
        <div>
            <div style="font-size: 1.5rem; font-weight: 800;"><?= number_format($total_wishlist) ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">คนกดถูกใจรวม</div>
        </div>
    </div>
    <div style="background: var(--bg-card); padding: 25px; border-radius: 20px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 20px;">
        <div style="width: 50px; height: 50px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <div style="font-size: 1.5rem; font-weight: 800;"><?= number_format($total_sales) ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">ออเดอร์ที่สำเร็จ</div>
        </div>
    </div>
</div>

<h2 style="margin-bottom: 20px; font-size: 1.4rem; display: flex; align-items: center; gap: 10px;">
    <i class="fas fa-clipboard-list text-primary"></i> คำสั่งซื้อจากลูกค้า
</h2>
<div style="background: var(--bg-card); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow); border: 1px solid var(--border-color); margin-bottom: 40px;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead style="background: var(--bg-body); border-bottom: 1px solid var(--border-color);">
            <tr>
                <th style="padding: 15px;">ออเดอร์</th>
                <th style="padding: 15px;">สินค้า</th>
                <th style="padding: 15px;">ลูกค้า</th>
                <th style="padding: 15px;">สถานะ</th>
                <th style="padding: 15px;">อัปเดต</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($orders) > 0): foreach($orders as $o): 
                $status_colors = ['pending' => '#d97706', 'preparing' => '#2563eb', 'completed' => '#059669', 'cancelled' => '#dc2626'];
            ?>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <td style="padding: 15px;"><span style="font-size: 0.8rem; color: var(--text-muted);">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                <td style="padding: 15px; font-weight: 600;"><?= e($o['product_name']) ?></td>
                <td style="padding: 15px;"><a href="../pages/chat.php?user=<?= $o['buyer_id'] ?>" style="color: var(--primary); text-decoration: none;"><?= e($o['buyer_name']) ?> <i class="fas fa-comment"></i></a></td>
                <td style="padding: 15px;"><span style="color: <?= $status_colors[$o['status']] ?>; font-weight: 700;"><?= strtoupper($o['status']) ?></span></td>
                <td style="padding: 15px;">
                    <form method="POST" style="display: flex; gap: 5px;">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <select name="new_status" style="padding: 5px; border-radius: 8px; font-size: 0.8rem; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-main);">
                            <option value="pending" <?= $o['status']=='pending' ? 'selected':'' ?>>รอยืนยัน</option>
                            <option value="preparing" <?= $o['status']=='preparing' ? 'selected':'' ?>>เตรียมของ</option>
                            <option value="completed" <?= $o['status']=='completed' ? 'selected':'' ?>>สำเร็จ</option>
                            <option value="cancelled" <?= $o['status']=='cancelled' ? 'selected':'' ?>>ยกเลิก</option>
                        </select>
                        <button type="submit" name="update_order_status" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.75rem;">OK</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">ยังไม่มีออเดอร์</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h2 style="margin-bottom: 20px; font-size: 1.4rem; display: flex; align-items: center; gap: 10px;">
    <i class="fas fa-chart-bar text-success"></i> สินค้าและการวิเคราะห์
</h2>
<div style="background: var(--bg-card); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead style="background: var(--bg-body); border-bottom: 1px solid var(--border-color);">
            <tr>
                <th style="padding: 15px;">ชื่อสินค้า</th>
                <th style="padding: 15px; text-align: center;"><i class="fas fa-eye"></i> ยอดวิว</th>
                <th style="padding: 15px; text-align: center;"><i class="fas fa-heart"></i> หัวใจ</th>
                <th style="padding: 15px; text-align: center;"><i class="fas fa-fire"></i> ความนิยม</th>
                <th style="padding: 15px;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $p): 
                    // คำนวณความนิยม (สูตรแบบง่าย: ยอดวิว + (หัวใจ * 5))
                    $popularity = $p['views'] + ($p['wish_count'] * 5);
                ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 15px; display: flex; align-items: center; gap: 15px;">
                        <img src="<?= !empty($p['image_url']) ? '../assets/images/products/'.$p['image_url'] : 'https://via.placeholder.com/50' ?>" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                        <div>
                            <div style="font-weight: 600;"><?= e($p['title']) ?></div>
                            <small style="color: var(--text-muted);">฿<?= number_format($p['price'], 2) ?></small>
                        </div>
                    </td>
                    <td style="padding: 15px; text-align: center; font-weight: 700; color: var(--primary);"><?= number_format($p['views']) ?></td>
                    <td style="padding: 15px; text-align: center; font-weight: 700; color: #ef4444;"><?= number_format($p['wish_count']) ?></td>
                    <td style="padding: 15px; text-align: center;">
                        <div style="width: 100%; background: var(--bg-body); height: 8px; border-radius: 10px; overflow: hidden; max-width: 100px; margin: 0 auto;">
                            <div style="width: <?= min(($popularity / 100) * 100, 100) ?>%; background: linear-gradient(90deg, #6366f1, #a855f7); height: 100%;"></div>
                        </div>
                        <small style="font-size: 0.65rem; color: var(--text-muted);">Score: <?= $popularity ?></small>
                    </td>
                    <td style="padding: 15px;">
                        <a href="add_product.php?id=<?= $p['id']; ?>" class="btn btn-sm" style="background: var(--bg-body); border: 1px solid var(--border-color);">แก้ไข</a>
                        <a href="dashboard.php?delete_id=<?= $p['id']; ?>" class="btn btn-delete btn-sm" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;" onclick="return confirm('ลบสินค้านี้?');">ลบ</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">ยังไม่มีสินค้า</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>