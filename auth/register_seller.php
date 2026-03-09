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

// 🛡️ 3. [NEW LOGIC] ตรวจสอบว่าผู้ใช้นี้เคยส่งคำขอเปิดร้านไปแล้วหรือยัง
$checkShop = $db->prepare("SELECT status FROM shops WHERE user_id = ?");
$checkShop->execute([$user_id]);
$shopStatus = $checkShop->fetchColumn();

// ถ้าร้านมีสถานะเป็น pending ให้สกัดไว้ไม่ให้ส่งข้อมูลซ้ำ
if ($shopStatus === 'pending') {
    $_SESSION['flash_message'] = "คุณได้ส่งคำขอเปิดร้านค้าไปแล้ว ขณะนี้กำลังรอการอนุมัติจากผู้ดูแลระบบ";
    $_SESSION['flash_type'] = "info";
    redirect('../pages/index.php'); // เด้งกลับไปหน้าแรก หรือหน้าที่คุณต้องการ
}

// ถ้าร้านมีสถานะเป็น approved แล้ว (เผื่อกรณีระบบ Role รวน)
if ($shopStatus === 'approved') {
     redirect('../seller/dashboard.php');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลหน้าร้านค้า
    $shop_name    = trim($_POST['shop_name']);
    $description  = trim($_POST['description']);
    $contact_line = trim($_POST['contact_line']);
    $contact_ig   = trim($_POST['contact_ig']);

    if (empty($shop_name)) {
        $_SESSION['flash_message'] = "กรุณาระบุชื่อร้านค้าของคุณ";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            // เริ่ม Transaction
            $db->beginTransaction();

            // ❌ [DELETED] เอาส่วนที่อัปเดต Role ผู้ใช้งานทันทีออก (รอแอดมินอัปเกรดให้)
            // $updateUser = $db->prepare("UPDATE users SET role = 'seller' WHERE id = ?");
            // $updateUser->execute([$user_id]);

            // 1. เพิ่มข้อมูลร้านค้าใหม่ (สถานะเริ่มต้นเป็น pending)
            $insertShop = $db->prepare("INSERT INTO shops (user_id, shop_name, description, contact_line, contact_ig, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $insertShop->execute([$user_id, $shop_name, $description, $contact_line, $contact_ig]);

            // ✅ ยืนยันการบันทึกฐานข้อมูล
            $db->commit();

            // 🔔 2. ส่งแจ้งเตือนหา Admin
            $admin_msg = "📢 [Admin] มีคำขอเปิดร้านค้าใหม่!\n"
                       . "👤 ผู้สมัคร: " . $_SESSION['fullname'] . "\n"
                       . "🏪 ชื่อร้าน: " . $shop_name . "\n"
                       . "🔗 ตรวจสอบ: http://localhost/student_marketplace/admin/manage_shops.php";
            
            if (function_exists('notifyAllAdmins')) {
                notifyAllAdmins($admin_msg);
            }

            // ❌ [DELETED] เอาส่วนอัปเดต Session Role ออก
            // $_SESSION['role'] = 'seller';

            $_SESSION['flash_message'] = "ส่งคำขอเปิดร้านสำเร็จ! ขณะนี้ร้านค้าของคุณกำลังรอการอนุมัติ (บทบาทของคุณจะอัปเดตเมื่อได้รับการอนุมัติ)";
            $_SESSION['flash_type'] = "success";
            redirect('../pages/index.php'); // กลับไปหน้าหลักก่อน เพราะยังเข้าไปใน seller/dashboard.php ไม่ได้

        } catch (Exception $e) {
            // 🛠️ เช็กก่อนว่ามี Transaction ค้างอยู่จริงไหมก่อนสั่ง Rollback
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
</div>

<?php require_once '../includes/footer.php'; ?>