<?php
/**
 * Student Marketplace - Register Page (Integrated with Google Login)
 * Database: student_market_db
 */
$pageTitle = "สมัครสมาชิกใหม่";
require_once '../includes/header.php';
require_once '../includes/functions.php'; // ตรวจสอบว่ามี getDB() และ redirect()
require_once '../vendor/autoload.php';   // สำหรับ Google API Client

// --- 1. ตั้งค่า Google Client สำหรับปุ่มสมัครสมาชิก ---
$client = new Google\Client();
$client->setClientId('349397957892-6m9lu6a6gd4605i8f9vruei5s07lh6hv.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/student_marketplace/auth/google_callback.php');
$client->addScope("email");
$client->addScope("profile");
$client->setPrompt('select_account'); // บังคับเลือกบัญชีใหม่ทุกครั้ง
$googleAuthUrl = $client->createAuthUrl();

// --- 2. จัดการการสมัครสมาชิกแบบปกติ (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname    = trim($_POST['fullname']);
    $student_id  = trim($_POST['student_id']);
    $class_level = $_POST['class_level']; 
    $class_year  = trim($_POST['class_year']); 
    $department  = $_POST['department'];
    $email       = trim($_POST['email']);
    $password    = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role        = 'buyer';

    $full_class_room = $class_level . " " . $class_year;

    if (empty($fullname) || empty($student_id) || empty($email) || empty($password) || empty($department)) {
        $_SESSION['flash_message'] = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
        $_SESSION['flash_type'] = "danger";
    } 
    elseif ($password !== $confirm_password) {
        $_SESSION['flash_message'] = "รหัสผ่านทั้งสองช่องไม่ตรงกัน";
        $_SESSION['flash_type'] = "danger";
    }
    elseif (!str_ends_with($email, '@bncc.ac.th')) {
        $_SESSION['flash_message'] = "กรุณาใช้อีเมลวิทยาลัย (@bncc.ac.th) เท่านั้น";
        $_SESSION['flash_type'] = "danger";
    } 
    else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR student_id = ?");
        $stmt->execute([$email, $student_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash_message'] = "อีเมลหรือรหัสนักเรียนนี้ถูกใช้งานไปแล้ว";
            $_SESSION['flash_type'] = "danger";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (student_id, fullname, class_room, department, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $db->prepare($sql);
            
            if ($insertStmt->execute([$student_id, $fullname, $full_class_room, $department, $email, $hashedPassword, $role])) {
                $_SESSION['flash_message'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
                $_SESSION['flash_type'] = "success";
                redirect('login.php');
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
    /* สไตล์สำหรับปุ่ม Google */
    .btn-google {
        width: 100%;
        background: #ffffff;
        color: #444;
        border: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        margin-top: 15px;
        transition: 0.3s;
    }
    .btn-google:hover {
        background: #f8f8f8;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>

<div style="max-width: 500px; margin: 40px auto; background: var(--bg-card); padding: 35px; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
    <h2 style="text-align: center; margin-bottom: 25px; color: var(--primary-color);">สมัครสมาชิกใหม่</h2>
    
    <?php echo displayFlashMessage(); ?>
    
    <form action="register.php" method="POST">
        <div class="form-group">
            <label>ชื่อ-นามสกุล <span style="color: var(--color-danger);">*</span></label>
            <input type="text" name="fullname" class="form-control" placeholder="ระบุชื่อและนามสกุล" required>
        </div>

        <div class="form-group">
            <label>รหัสนักเรียน <span style="color: var(--color-danger);">*</span></label>
            <input type="text" name="student_id" class="form-control" placeholder="รหัสประจำตัว" required>
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

        <div class="form-group">
            <label>อีเมลวิทยาลัย <span style="color: var(--color-danger);">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="เช่น 65319010001@bncc.ac.th" required>
        </div>

        <div class="form-group">
            <label>รหัสผ่าน <span style="color: var(--color-danger);">*</span></label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" class="form-control" placeholder="กำหนดรหัสผ่าน" required>
                <i class="fas fa-eye toggle-password" onclick="togglePass('password', this)"></i>
            </div>
        </div>

        <div class="form-group">
            <label>ยืนยันรหัสผ่านอีกครั้ง <span style="color: var(--color-danger);">*</span></label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="กรอกรหัสผ่านเดิมอีกครั้ง" required>
                <i class="fas fa-eye toggle-password" onclick="togglePass('confirm_password', this)"></i>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 12px; font-weight: 600;">สร้างบัญชีผู้ใช้งาน</button>
    </form>

    <div style="text-align: center; margin: 25px 0; position: relative;">
        <hr style="border: 0; border-top: 1px solid var(--border-color);">
        <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--bg-card); padding: 0 10px; color: var(--text-muted); font-size: 0.8rem;">หรือสมัครสมาชิกด้วย</span>
    </div>

    <a href="<?php echo $googleAuthUrl; ?>" class="btn-google">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
            <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24s8.955,20,20,20s20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
            <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
            <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
            <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
        </svg>
        สมัครสมาชิกด้วย Google (@bncc.ac.th)
    </a>

    <p style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: var(--text-muted);">
        เป็นสมาชิกอยู่แล้ว? <a href="login.php" style="color: var(--primary-color); font-weight: 500; text-decoration: none;">เข้าสู่ระบบที่นี่</a>
    </p>
</div>

<script>
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