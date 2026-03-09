<?php
/**
 * Student Marketplace - Appeal Ban Page
 * สำหรับผู้ใช้งานที่ถูกระงับบัญชี เพื่อยื่นเรื่องขอตรวจสอบ
 */
$pageTitle = "ยื่นเรื่องอุทธรณ์การถูกระงับบัญชี - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $reason     = trim($_POST['reason']);

    if (empty($student_id) || empty($reason)) {
        $_SESSION['flash_message'] = "กรุณากรอกรหัสนักศึกษาและระบุเหตุผลให้ครบถ้วน";
        $_SESSION['flash_type'] = "danger";
    } else {
        // 1. ตรวจสอบว่ามีรหัสนักศึกษานี้ในระบบและโดนแบนจริงหรือไม่
        $stmt = $db->prepare("SELECT id, fullname, email, is_banned FROM users WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_message'] = "ไม่พบรหัสนักศึกษานี้ในระบบ";
            $_SESSION['flash_type'] = "danger";
        } elseif ($user['is_banned'] == 0) {
            $_SESSION['flash_message'] = "บัญชีนี้ไม่ได้ถูกระงับการใช้งาน คุณสามารถเข้าสู่ระบบได้ตามปกติ";
            $_SESSION['flash_type'] = "info";
        } else {
            // 2. ส่งแจ้งเตือนหา Admin ทุกคน (ผ่านระบบแจ้งเตือนหน้าเว็บและ LINE)
            $admin_msg = "🚨 [คำขออุทธรณ์] ผู้ใช้ขอยืนยันตัวตนเพื่อปลดแบน\n"
                       . "👤 ชื่อ: " . $user['fullname'] . "\n"
                       . "🆔 รหัส: " . $student_id . "\n"
                       . "📝 เหตุผล: " . $reason;
            
            // ส่ง LINE Notify
            if (function_exists('notifyAllAdmins')) {
                notifyAllAdmins($admin_msg);
            }

            // ส่งแจ้งเตือนเข้ากระดิ่งหน้าเว็บให้ Admin ทุกคน
            $adminStmt = $db->query("SELECT id FROM users WHERE role IN ('admin', 'teacher')");
            $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $adm_id) {
                if (function_exists('sendNotification')) {
                    sendNotification($adm_id, 'system', "มีการยื่นอุทธรณ์ปลดแบนจาก: " . $user['fullname'], "../admin/manage_members.php?search=" . urlencode($user['email']));
                }
            }

            $_SESSION['flash_message'] = "ส่งเรื่องอุทธรณ์สำเร็จ! กรุณารอการตรวจสอบจากแอดมิน (ประมาณ 1-3 วันทำการ)";
            $_SESSION['flash_type'] = "success";
            redirect('login.php');
        }
    }
}
?>

<style>
    /* ใช้ธีมเดียวกับ Login/Register */
    body { background-color: var(--login-bg) !important; font-family: 'Prompt', sans-serif; }
    .login-master-wrapper { position: relative; min-height: calc(100vh - 75px); display: flex; align-items: center; justify-content: center; padding: 40px 20px; overflow: hidden; }
    .login-card { position: relative; width: 100%; max-width: 500px; background: var(--login-card-bg); backdrop-filter: blur(25px); border: 1px solid var(--login-card-border); border-radius: 32px; padding: 50px 45px; box-shadow: var(--login-card-shadow); text-align: center; }
    .btn-appeal { width: 100%; padding: 18px; border-radius: 18px; background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%); color: white; font-weight: 800; border: none; cursor: pointer; margin-top: 15px; box-shadow: 0 10px 25px rgba(225, 29, 72, 0.3); transition: 0.3s; }
    .btn-appeal:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(225, 29, 72, 0.5); }
    .form-control-custom { width: 100%; padding: 16px 20px; border-radius: 16px; background: var(--login-input-bg); border: 1px solid var(--login-card-border); color: var(--text-main); margin-bottom: 20px; outline: none; }
</style>

<div class="login-master-wrapper">
    <div class="login-card">
        <div style="margin-bottom: 30px;">
            <i class="fas fa-user-shield" style="font-size: 4rem; color: #f43f5e; margin-bottom: 20px;"></i>
            <h2 style="font-weight: 900; color: var(--text-main);">ยื่นเรื่องปลดแบน</h2>
            <p style="color: var(--text-muted);">ระบุข้อมูลเพื่อให้แอดมินพิจารณาตรวจสอบบัญชีของคุณ</p>
        </div>

        <?php echo displayFlashMessage(); ?>

        <form method="POST">
            <div style="text-align: left; margin-bottom: 8px;">
                <label style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">รหัสนักศึกษา 11 หลัก</label>
            </div>
            <input type="text" name="student_id" class="form-control-custom" placeholder="กรอกรหัสนักศึกษาของคุณ" maxlength="11" required>

            <div style="text-align: left; margin-bottom: 8px;">
                <label style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">เหตุผลหรือข้อความถึงแอดมิน</label>
            </div>
            <textarea name="reason" class="form-control-custom" rows="4" placeholder="อธิบายเหตุผลที่ต้องการให้ตรวจสอบบัญชี..." required style="resize: none;"></textarea>

            <button type="submit" class="btn-appeal">
                ส่งคำขอตรวจสอบ <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
            </button>
        </form>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--login-card-border);">
            <a href="login.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> กลับหน้าเข้าสู่ระบบ
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>