<?php
/**
 * BNCC Market - Admin Product Approval
 */

// 🚀 1. โหลด Functions มาก่อนเสมอ! (ห้ามโหลด header.php ตรงนี้เด็ดขาด)
require_once '../includes/functions.php';

// 🎯 🛠️ 2. เช็คสิทธิ์: อนุญาตให้ทั้ง admin และ teacher เข้าได้ (จะได้ไม่เด้งกลับหน้าแรก)
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    redirect('../pages/index.php');
}

$db = getDB();

// --- 3. ตรรกะการอนุมัติ/ปฏิเสธ (ทำก่อนโหลด Header) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = ($_GET['action'] === 'approve') ? 'approved' : 'rejected';
    
    // 🎯 🛠️ [เพิ่มใหม่] ดึงข้อมูลเจ้าของร้านและชื่อสินค้าเพื่อแจ้งเตือน
    $info_stmt = $db->prepare("SELECT s.user_id, p.title FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.id = ?");
    $info_stmt->execute([$id]);
    $info = $info_stmt->fetch();

    $update = $db->prepare("UPDATE products SET status = ? WHERE id = ?");
    if ($update->execute([$new_status, $id])) {
        
        // 🎯 🛠️ [เพิ่มใหม่] ส่งการแจ้งเตือนเข้ากระดิ่งบนเว็บ
        if ($info) {
            $notif_msg = ($new_status === 'approved') 
                ? "✅ สินค้าของคุณ ({$info['title']}) ได้รับการอนุมัติแล้ว!" 
                : "❌ สินค้าของคุณ ({$info['title']}) ไม่ผ่านการอนุมัติ กรุณาตรวจสอบความถูกต้อง";
            $notif_link = "../pages/product_detail.php?id=" . $id;
            sendNotification($info['user_id'], 'system', $notif_msg, $notif_link);
        }

        $_SESSION['flash_message'] = "ดำเนินการเรียบร้อยแล้ว และแจ้งเตือนผู้ขายแล้ว";
        $_SESSION['flash_type'] = "success";
    }
    
    // 🛠️ เปลี่ยนมาใช้ redirect() ของเราแทน header()
    redirect("approve_product.php");
}

// 4. ดึงสินค้าที่สถานะเป็น 'pending'
$stmt = $db->query("SELECT p.*, s.shop_name FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.status = 'pending' ORDER BY p.created_at ASC");
$pending_products = $stmt->fetchAll();

// 🟩 5. เมื่อคำนวณและเช็กสิทธิ์เสร็จหมดแล้ว ค่อยโหลด Header (UI) ขึ้นมา
$pageTitle = "จัดการคำขอลงสินค้า - BNCC Market";
require_once '../includes/header.php';
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