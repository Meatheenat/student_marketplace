<?php
/**
 * Student Marketplace - Upgrade to Seller (register_seller.php)
 * สำหรับผู้ใช้งานที่เป็นสมาชิกอยู่แล้ว และต้องการขอเปิดหน้าร้านค้า
 */
$pageTitle = "สมัครเป็นผู้ขายสินค้า";
require_once '../includes/header.php';

// 1. ตรวจสอบความปลอดภัย: ต้อง Login ก่อนเท่านั้น
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนสมัครเป็นผู้ขาย";
    $_SESSION['flash_type'] = "warning";
    redirect('login.php');
}

// 2. ถ้าเป็นผู้ขายอยู่แล้ว หรือเป็น Admin ให้ส่งไปหน้า Dashboard เลย
if ($_SESSION['role'] !== 'buyer') {
    redirect('../seller/dashboard.php');
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// 🛡️ 3. ตรวจสอบว่าผู้ใช้นี้เคยส่งคำขอเปิดร้านไปแล้วหรือยัง
$checkShop = $db->prepare("SELECT status FROM shops WHERE user_id = ?");
$checkShop->execute([$user_id]);
$shopStatus = $checkShop->fetchColumn();

// ถ้าร้านมีสถานะเป็น approved แล้วให้ไปหน้า Dashboard
if ($shopStatus === 'approved') {
     redirect('../seller/dashboard.php');
}

// 4. จัดการเมื่อกดปุ่มส่งข้อมูล (เฉพาะคนที่ยังไม่เคยสมัคร)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $shopStatus !== 'pending') {
    $shop_name    = trim($_POST['shop_name']);
    $description  = trim($_POST['description']);
    $contact_line = trim($_POST['contact_line']);
    $contact_ig   = trim($_POST['contact_ig']);

    if (empty($shop_name)) {
        $_SESSION['flash_message'] = "กรุณาระบุชื่อร้านค้าของคุณ";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            $db->beginTransaction();

            // เพิ่มข้อมูลร้านค้าใหม่ (สถานะเริ่มต้นเป็น pending)
            $insertShop = $db->prepare("INSERT INTO shops (user_id, shop_name, description, contact_line, contact_ig, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $insertShop->execute([$user_id, $shop_name, $description, $contact_line, $contact_ig]);

            $db->commit();

            // 🔔 ส่งแจ้งเตือนหา Admin (ผ่าน LINE)
            $admin_msg = "📢 [Admin] มีคำขอเปิดร้านค้าใหม่!\n"
                       . "👤 ผู้สมัคร: " . $_SESSION['fullname'] . "\n"
                       . "🏪 ชื่อร้าน: " . $shop_name . "\n"
                       . "🔗 ตรวจสอบ: http://localhost/student_marketplace/admin/approve_shop.php";
            
            if (function_exists('notifyAllAdmins')) {
                notifyAllAdmins($admin_msg);
            }

            // 🎯 [NEW] ส่งแจ้งเตือนในระบบ (กระดิ่งหน้าเว็บ) ให้ Admin และ Teacher ทุกคน
            $adminStmt = $db->query("SELECT id FROM users WHERE role IN ('admin', 'teacher')");
            $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $adm_id) {
                if (function_exists('sendNotification')) {
                    sendNotification($adm_id, 'system', "มีคำขอเปิดร้านค้าใหม่: " . $shop_name, "../admin/approve_shop.php");
                }
            }

            $_SESSION['flash_message'] = "ส่งคำขอเปิดร้านสำเร็จ! ขณะนี้ร้านค้าของคุณกำลังรอการอนุมัติ";
            $_SESSION['flash_type'] = "success";
            
            // เด้งกลับมาหน้านี้แหละ เพื่อโชว์ UI "รออนุมัติ"
            redirect('register_seller.php'); 

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}
?>

<div style="max-width: 600px; margin: 40px auto; background: var(--bg-card); padding: 40px; border-radius: 20px; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
    
    <?php echo displayFlashMessage(); ?>

    <?php if ($shopStatus === 'pending'): ?>
        <div style="text-align: center; padding: 20px 0;">
            <div style="width: 100px; height: 100px; background: rgba(245, 158, 11, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                <i class="fas fa-hourglass-half" style="font-size: 3rem; color: #f59e0b;"></i>
            </div>
            <h2 style="color: var(--text-main); font-size: 1.8rem; margin-bottom: 15px; font-weight: 800;">กำลังรอการตรวจสอบ</h2>
            <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; margin-bottom: 30px;">
                คุณได้ส่งคำขอเปิดร้านค้าไปเรียบร้อยแล้ว<br>
                ขณะนี้กำลังรอการอนุมัติจากผู้ดูแลระบบ/คุณครู<br>
                <span style="display: block; margin-top: 10px; font-size: 0.9rem; color: var(--primary-color);">
                    (บทบาทของคุณจะเปลี่ยนเป็นผู้ขายอัตโนมัติเมื่อผ่านการอนุมัติ)
                </span>
            </p>
            <a href="../pages/index.php" class="btn btn-primary" style="padding: 12px 30px; border-radius: 12px; font-weight: 600; text-decoration: none; display: inline-block;">
                <i class="fas fa-home"></i> กลับสู่หน้าหลัก
            </a>
        </div>

    <?php else: ?>
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); font-size: 1.8rem;">เปิดหน้าร้านค้าของคุณ</h2>
            <p style="color: var(--text-muted);">ยินดีด้วยคุณ <strong><?php echo e($_SESSION['fullname']); ?></strong> พร้อมจะเริ่มขายของหรือยัง?</p>
        </div>

        <form action="register_seller.php" method="POST" class="needs-validation">
            <div style="background: var(--bg-body); padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid var(--primary-color);">
                <h4 style="margin-bottom: 10px;"><i class="fas fa-store"></i> ข้อมูลหน้าร้าน</h4>
                <div class="form-group">
                    <label>ชื่อร้านค้าของคุณ <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" name="shop_name" class="form-control" placeholder="เช่น ร้านคุกกี้พี่ ม.6" required>
                </div>
                <div class="form-group">
                    <label>สโลแกนหรือคำบรรยายร้าน (สั้นๆ)</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div style="background: var(--bg-body); padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #00c300;">
                <h4 style="margin-bottom: 15px;"><i class="fas fa-comments"></i> ช่องทางการติดต่อ</h4>
                <div class="form-group">
                    <label><i class="fab fa-line" style="color: #00c300;"></i> LINE ID</label>
                    <input type="text" name="contact_line" class="form-control">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-instagram" style="color: #e1306c;"></i> Instagram</label>
                    <input type="text" name="contact_ig" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem; font-weight: 600;">
                ส่งคำขอเปิดหน้าร้าน
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>