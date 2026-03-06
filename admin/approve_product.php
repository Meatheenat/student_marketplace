<?php
/**
 * BNCC Market - Admin Product Approval
 */

// 🚀 1. โหลด Functions มาก่อนเสมอ! (ห้ามโหลด header.php ตรงนี้เด็ดขาด)
require_once '../includes/functions.php';

// 🎯 🛠️ 2. เช็คสิทธิ์: อนุญาตให้ทั้ง admin และ teacher เข้าได้
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    redirect('../pages/index.php');
}

$db = getDB();

// --- 3. ตรรกะการอนุมัติ/ปฏิเสธ (ทำก่อนโหลด Header) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = ($_GET['action'] === 'approve') ? 'approved' : 'rejected';
    
    // 🎯 🛠️ ดึงข้อมูลเจ้าของร้านและชื่อสินค้าเพื่อแจ้งเตือน
    $info_stmt = $db->prepare("SELECT s.user_id, p.title FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.id = ?");
    $info_stmt->execute([$id]);
    $info = $info_stmt->fetch();

    $update = $db->prepare("UPDATE products SET status = ? WHERE id = ?");
    if ($update->execute([$new_status, $id])) {
        
        // 🎯 🛠️ ส่งการแจ้งเตือนเข้ากระดิ่งบนเว็บ
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

<style>
    /* 🎨 CSS เพิ่ม Animation ให้ตารางดูพรีเมียม */
    .table-row-hover {
        transition: background-color 0.2s ease, transform 0.2s ease;
    }
    .table-row-hover:hover {
        background-color: rgba(95, 66, 228, 0.05);
        transform: scale(1.005); /* เด้งขึ้นมานิดนึงตอนเอาเมาส์ชี้ */
    }
    .product-link {
        color: var(--text-color);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: color 0.2s ease;
    }
    .product-link:hover {
        color: #5f42e4; /* สีม่วง BNCC Market */
    }
    .product-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .product-link:hover .product-img {
        transform: scale(1.1); /* รูปขยายเมื่อชี้ที่ลิงก์ */
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    
    /* Animation เฟดเข้าของตารางตอนโหลดหน้า */
    .table-container {
        animation: fadeUp 0.5s ease forwards;
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="container" style="margin-top: 30px;">
    <h2 style="margin-bottom: 25px;"><i class="fas fa-clipboard-check text-primary"></i> รายการสินค้ารออนุมัติ</h2>
    
    <?php echo displayFlashMessage(); ?>

    <div class="card table-container" style="padding: 0; overflow: hidden; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: var(--bg-body); border-bottom: 2px solid var(--border-color);">
                <tr>
                    <th style="padding: 15px;">สินค้า (คลิกเพื่อดูรายละเอียด)</th>
                    <th style="padding: 15px;">ร้านค้า</th>
                    <th style="padding: 15px;">ราคา</th>
                    <th style="padding: 15px; text-align: center;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($pending_products) > 0): foreach($pending_products as $p): ?>
                <tr class="table-row-hover" style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 15px;">
                        <a href="../pages/product_detail.php?id=<?= $p['id'] ?>" target="_blank" class="product-link" title="คลิกเพื่อดูพรีวิวสินค้า">
                            <img src="../assets/images/products/<?= e($p['image_url']) ?>" class="product-img">
                            <div>
                                <strong style="display: block; font-size: 1.05rem;"><?= e($p['title']) ?></strong>
                                <small style="color: var(--text-muted);"><i class="fas fa-external-link-alt"></i> พรีวิวหน้าสินค้า</small>
                            </div>
                        </a>
                    </td>
                    <td style="padding: 15px;"><i class="fas fa-store text-muted"></i> <?= e($p['shop_name']) ?></td>
                    <td style="padding: 15px; font-weight: bold; color: #06C755;">฿<?= number_format($p['price'], 2) ?></td>
                    <td style="padding: 15px; text-align: center;">
                        <a href="?action=approve&id=<?= $p['id'] ?>" class="btn btn-primary btn-sm" onclick="return confirm('ยืนยันการอนุมัติ?')"><i class="fas fa-check"></i> อนุมัติ</a>
                        <a href="?action=reject&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" style="margin-left: 5px;" onclick="return confirm('ปฏิเสธรายการนี้?')"><i class="fas fa-times"></i> ปฏิเสธ</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="4" style="padding: 60px; text-align: center; color: var(--text-muted);">
                        <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i><br>
                        ไม่มีสินค้าที่รอการตรวจสอบในขณะนี้
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>