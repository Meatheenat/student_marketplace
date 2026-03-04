<?php
/**
 * BNCC Market - Admin Product Approval
 */
$pageTitle = "จัดการคำขอลงสินค้า - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// เช็คสิทธิ์แอดมิน
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') redirect('../pages/index.php');

$db = getDB();

// --- ตรรกะการอนุมัติ/ปฏิเสธ ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = ($_GET['action'] === 'approve') ? 'approved' : 'rejected';
    
    $update = $db->prepare("UPDATE products SET status = ? WHERE id = ?");
    $update->execute([$new_status, $id]);
    
    $_SESSION['flash_message'] = "ดำเนินการเรียบร้อยแล้ว";
    $_SESSION['flash_type'] = "success";
    header("Location: approve_product.php");
    exit();
}

// ดึงสินค้าที่สถานะเป็น 'pending'
$stmt = $db->query("SELECT p.*, s.shop_name FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.status = 'pending' ORDER BY p.created_at ASC");
$pending_products = $stmt->fetchAll();
?>

<div class="container" style="margin-top: 30px;">
    <h2 style="margin-bottom: 25px;"><i class="fas fa-clipboard-check text-primary"></i> รายการสินค้ารออนุมัติ</h2>
    
    <?php echo displayFlashMessage(); ?>

    <div class="card" style="padding: 0; overflow: hidden; border-radius: 16px;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: var(--bg-body); border-bottom: 2px solid var(--border-color);">
                <tr>
                    <th style="padding: 15px;">รูปภาพ</th>
                    <th style="padding: 15px;">ชื่อสินค้า</th>
                    <th style="padding: 15px;">ร้านค้า</th>
                    <th style="padding: 15px;">ราคา</th>
                    <th style="padding: 15px;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($pending_products) > 0): foreach($pending_products as $p): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 15px;"><img src="../assets/images/products/<?= $p['image_url'] ?>" width="60" style="border-radius: 8px;"></td>
                    <td style="padding: 15px;"><strong><?= e($p['title']) ?></strong></td>
                    <td style="padding: 15px;"><?= e($p['shop_name']) ?></td>
                    <td style="padding: 15px;">฿<?= number_format($p['price'], 2) ?></td>
                    <td style="padding: 15px;">
                        <a href="?action=approve&id=<?= $p['id'] ?>" class="btn btn-primary btn-sm" onclick="return confirm('ยืนยันการอนุมัติ?')">อนุมัติ</a>
                        <a href="?action=reject&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ปฏิเสธรายการนี้?')">ปฏิเสธ</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="padding: 50px; text-align: center; color: var(--text-muted);">ไม่มีสินค้าที่รอการตรวจสอบในขณะนี้</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>