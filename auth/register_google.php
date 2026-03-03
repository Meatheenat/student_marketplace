<?php
/**
 * Student Marketplace - Register for Google Users (Complete Edition)
 * ระบบลงทะเบียนเพิ่มเติม: รองรับการตั้งรหัสผ่านเพื่อใช้ Login ปกติได้ในอนาคต
 */
$pageTitle = "ลงทะเบียนสมาชิกใหม่ด้วย Google";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// 1. ตรวจสอบข้อมูลชั่วคราวจาก Google (Session)
if (!isset($_SESSION['temp_email'])) {
    redirect('login.php');
}

$temp_email = $_SESSION['temp_email'];

// 2. จัดการเมื่อมีการส่งฟอร์ม (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname    = trim($_POST['fullname']); 
    $student_id  = trim($_POST['student_id']);
    $class_level = $_POST['class_level']; 
    $class_year  = trim($_POST['class_year']); 
    $department  = $_POST['department'];
    $password    = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email       = $temp_email; 
    $role        = 'buyer'; // ค่าเริ่มต้นเป็นผู้ซื้อ

    $full_class_room = $class_level . " " . $class_year;

    // ตรวจสอบความถูกต้องของข้อมูล
    if (empty($fullname) || empty($student_id) || empty($department) || empty($password)) {
        $_SESSION['flash_message'] = "กรุณากรอกข้อมูลให้ครบถ้วนทุกช่องครับ";
        $_SESSION['flash_type'] = "danger";
    } 
    elseif ($password !== $confirm_password) {
        $_SESSION['flash_message'] = "รหัสผ่านทั้งสองช่องไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง";
        $_SESSION['flash_type'] = "danger";
    }
    else {
        $db = getDB();
        
        // ตรวจสอบรหัสนักเรียนซ้ำในระบบ ( student_id เป็น UNIQUE )
        $stmt = $db->prepare("SELECT id FROM users WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash_message'] = "รหัสนักเรียนนี้ถูกใช้งานในระบบแล้ว";
            $_SESSION['flash_type'] = "danger";
        } else {
            // เข้ารหัสผ่านที่ผู้ใช้กำหนดเอง (Bcrypt) เพื่อให้ใช้ Login ปกติได้
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (student_id, fullname, class_room, department, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $db->prepare($sql);
            
            if ($insertStmt->execute([$student_id, $fullname, $full_class_room, $department, $email, $hashedPassword, $role])) {
                // บันทึกสำเร็จ: สร้าง Session และเข้าสู่ระบบทันที
                $_SESSION['user_id']    = $db->lastInsertId();
                $_SESSION['fullname']   = $fullname;
                $_SESSION['role']       = $role;
                $_SESSION['student_id'] = $student_id;

                unset($_SESSION['temp_email']);
                redirect('../pages/index.php');
            }
        }
    }
}
?>

<style>
    .password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .password-wrapper .toggle-password {
        position: absolute;
        right: 15px;
        cursor: pointer;
        color: var(--text-muted);
        z-index: 10;
    }
    .password-wrapper .toggle-password:hover {
        color: var(--primary-color);
    }
</style>

<div style="max-width: 500px; margin: 40px auto; background: var(--bg-card); padding: 35px; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
    <h2 style="text-align: center; margin-bottom: 25px; color: var(--primary-color);">สมัครสมาชิก (Google)</h2>
    
    <?php echo displayFlashMessage(); ?>

    <form action="register_google.php" method="POST">
        <div class="form-group">
            <label>ชื่อ-นามสกุล <span style="color: var(--color-danger);">*</span></label>
            <input type="text" name="fullname" class="form-control" placeholder="ระบุชื่อและนามสกุลจริง" required>
        </div>

        <div class="form-group">
            <label>อีเมลวิทยาลัย (ยืนยันผ่าน Google)</label>
            <input type="email" class="form-control" value="<?php echo e($temp_email); ?>" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed;">
        </div>

        <div class="form-group">
            <label>รหัสนักเรียน <span style="color: var(--color-danger);">*</span></label>
            <input type="text" name="student_id" class="form-control" placeholder="รหัสประจำตัวนักเรียน" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label>ระดับชั้น <span style="color: var(--color-danger);">*</span></label>
                <select name="class_level" class="form-control" required>
                    <option value="ปวช.">ปวช.</option>
                    <option value="ปวส.">ปวส.</option>
                </select>
            </div>
            <div class="form-group">
                <label>ชั้น/ห้อง (เช่น 1/2)</label>
                <input type="text" name="class_year" class="form-control" placeholder="ระบุปี/ห้อง" required>
            </div>
        </div>

        <div class="form-group">
            <label>แผนกวิชา <span style="color: var(--color-danger);">*</span></label>
            <select name="department" class="form-control" required>
                <option value="">-- เลือกแผนกวิชา --</option>
                <option value="แผนกวิชาการบัญชี">แผนกวิชาการบัญชี</option>
                <option value="แผนกวิชาการตลาด">แผนกวิชาการตลาด</option>
                <option value="แผนกวิชาการจัดการเลขานุการ">แผนกวิชาการจัดการเลขานุการ</option>
                <option value="แผนกวิชาคอมพิวเตอร์ธุรกิจ">แผนกวิชาคอมพิวเตอร์ธุรกิจ</option>
                <option value="แผนกวิชาภาษาต่างประเทศ">แผนกวิชาภาษาต่างประเทศ</option>
                <option value="แผนกวิชาการโรงแรม">แผนกวิชาการโรงแรม</option>
                <option value="แผนกวิชาการจัดประชุมและนิทรรศการ">แผนกวิชาการจัดประชุมและนิทรรศการ</option>
                <option value="แผนกวิชาการจัดการโลจิสติกส์">แผนกวิชาการจัดการโลจิสติกส์</option>
                <option value="แผนกวิชาเทคโนโลยีสารสนเทศ">แผนกวิชาเทคโนโลยีสารสนเทศ</option>
            </select>
        </div>

        <hr style="border-top: 1px solid var(--border-color); margin: 25px 0;">
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">กำหนดรหัสผ่านเพื่อเข้าใช้งานด้วยอีเมลในครั้งถัดไป</p>

        <div class="form-group">
            <label>กำหนดรหัสผ่าน <span style="color: var(--color-danger);">*</span></label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" class="form-control" placeholder="กำหนดรหัสผ่าน" required>
                <i class="fas fa-eye toggle-password" onclick="togglePass('password', this)"></i>
            </div>
        </div>

        <div class="form-group">
            <label>ยืนยันรหัสผ่านอีกครั้ง <span style="color: var(--color-danger);">*</span></label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="ยืนยันรหัสผ่านเดิม" required>
                <i class="fas fa-eye toggle-password" onclick="togglePass('confirm_password', this)"></i>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px; padding: 12px; font-weight: 600;">สร้างบัญชีและเริ่มใช้งาน</button>
    </form>
</div>

<script>
    /**
     * ฟังก์ชันเปิด/ปิดการมองเห็นรหัสผ่าน
     */
    function togglePass(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.replace("fa-eye-slash", "fa-eye");
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>