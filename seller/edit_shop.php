<?php
/**
 * Student Marketplace - Edit Shop Settings
 */
$pageTitle = "ตั้งค่าร้านค้า";
require_once '../includes/header.php';

checkRole('seller');

$db = getDB();
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลร้านเดิมมาโชว์ในฟอร์ม (ถ้ามี)
$stmt = $db->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->execute([$user_id]);
$shop = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name    = trim($_POST['shop_name']);
    $description  = trim($_POST['description']);
    $contact_line = trim($_POST['contact_line']);
    $contact_ig   = trim($_POST['contact_ig']);

    if (empty($shop_name)) {
        $_SESSION['flash_message'] = "กรุณาระบุชื่อร้านค้า";
        $_SESSION['flash_type'] = "danger";
    } else {
        if ($shop) {
            // อัปเดตข้อมูลร้านเดิม (สถานะจะยังคงเดิม)
            $sql = "UPDATE shops SET shop_name = ?, description = ?, contact_line = ?, contact_ig = ? WHERE user_id = ?";
            $params = [$shop_name, $description, $contact_line, $contact_ig, $user_id];
            $msg = "อัปเดตข้อมูลร้านค้าเรียบร้อยแล้ว";
        } else {
            // สร้างร้านค้าใหม่ (สถานะเริ่มต้นเป็น pending)
            $sql = "INSERT INTO shops (shop_name, description, contact_line, contact_ig, user_id, status) VALUES (?, ?, ?, ?, ?, 'pending')";
            $params = [$shop_name, $description, $contact_line, $contact_ig, $user_id];
            $msg = "สร้างร้านค้าสำเร็จ! กรุณารอครูอนุมัติร้านค้าของคุณ";
        }

        $stmt_save = $db->prepare($sql);
        if ($stmt_save->execute($params)) {
            $_SESSION['flash_message'] = $msg;
            $_SESSION['flash_type'] = "success";
            redirect('dashboard.php');
        }
    }
}
?>

<div style="max-width: 700px; margin: 0 auto;">
    <div style="margin-bottom: 30px;">
        <a href="dashboard.php" style="color: var(--text-muted); font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> กลับไปยัง Dashboard</a>
        <h1 style="margin-top: 10px;">ตั้งค่าหน้าร้านค้า</h1>
        <p style="color: var(--text-muted);">ข้อมูลส่วนนี้จะปรากฏบนหน้าโปรไฟล์ร้านค้าของคุณให้นักเรียนคนอื่นเห็น</p>
    </div>

    <form action="edit_shop.php" method="POST" class="needs-validation" style="background: var(--bg-card); padding: 30px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow);">
        <div class="form-group">
            <label>ชื่อร้านค้าของคุณ <span style="color: var(--color-danger);">*</span></label>
            <input type="text" name="shop_name" class="form-control" value="<?php echo e($shop['shop_name'] ?? ''); ?>" placeholder="เช่น ขนมบ้านน้องฟ้า, ร้านวาดรูปรับจ้าง" required>
        </div>

        <div class="form-group">
            <label>คำอธิบายร้านค้า</label>
            <textarea name="description" class="form-control" rows="4" placeholder="เล่าเรื่องราวของร้าน หรือรายละเอียดการรับสินค้า..."><?php echo e($shop['description'] ?? ''); ?></textarea>
        </div>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border-color);">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-id-card"></i> ช่องทางการติดต่อ</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label><i class="fab fa-line" style="color: #00c300;"></i> LINE ID</label>
                <input type="text" name="contact_line" class="form-control" value="<?php echo e($shop['contact_line'] ?? ''); ?>" placeholder="ไอดีไลน์ไม่ต้องใส่ @">
            </div>
            <div class="form-group">
                <label><i class="fab fa-instagram" style="color: #e1306c;"></i> Instagram User</label>
                <input type="text" name="contact_ig" class="form-control" value="<?php echo e($shop['contact_ig'] ?? ''); ?>" placeholder="ชื่อไอจี">
            </div>
        </div>

        <div style="background: rgba(79, 70, 229, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid var(--primary-color);">
            <p style="font-size: 0.85rem; color: var(--text-muted);">
                <strong>หมายเหตุ:</strong> ระบบจะใช้ไอดีเหล่านี้สร้างปุ่มติดต่ออัตโนมัติ เพื่อให้ผู้ซื้อทักหาคุณได้ทันที
            </p>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">บันทึกข้อมูลร้านค้า</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>