<?php
/**
 * ระบบตลาดออนไลน์นักเรียน (Student Marketplace)
 * หน้าเข้าสู่ระบบ (Login Page) - เพิ่มระบบกู้คืนรหัสผ่าน
 */
$pageTitle = "เข้าสู่ระบบ - Student Marketplace";
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

// 1. ตรวจสอบสถานะ: หากเข้าสู่ระบบอยู่แล้ว ให้เปลี่ยนหน้าไปยังหน้าแรกทันที
if (isLoggedIn()) {
    redirect('../pages/index.php');
}

// 2. ตั้งค่าระบบยืนยันตัวตนผ่าน Google (Google OAuth)
$client = new Google\Client();
$client->setClientId('349397957892-6m9lu6a6gd4605i8f9vruei5s07lh6hv.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/student_marketplace/auth/google_callback.php');
$client->addScope("email");
$client->addScope("profile");
$client->setPrompt('select_account'); 
$googleAuthUrl = $client->createAuthUrl();

// 3. จัดการการเข้าสู่ระบบด้วยชื่อผู้ใช้และรหัสผ่าน (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input']);
    $password    = $_POST['password'];

    if (!empty($login_input) && !empty($password)) {
        $db = getDB();
        // ตรวจสอบจากอีเมลหรือรหัสนักเรียนตามโครงสร้างฐานข้อมูล student_market_db
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR student_id = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // บันทึกข้อมูลลง Session เมื่อรหัสผ่านถูกต้อง
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['fullname']   = $user['fullname'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['student_id'] = $user['student_id'];
            
            redirect('../pages/index.php');
        } else {
            $_SESSION['flash_message'] = "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง";
            $_SESSION['flash_type'] = "danger";
        }
    }
}
?>

<div style="max-width: 400px; margin: 60px auto; background: var(--bg-card); padding: 35px; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
    
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="color: var(--primary-color);">เข้าสู่ระบบ</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">ยินดีต้อนรับสู่ระบบตลาดออนไลน์นักเรียน</p>
    </div>

    <?php echo displayFlashMessage(); ?>

    <form action="login.php" method="POST">
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px;">อีเมล หรือ รหัสนักเรียน</label>
            <input type="text" name="login_input" class="form-control" placeholder="ระบุข้อมูลที่ใช้ลงทะเบียน" required>
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 8px;">รหัสผ่าน</label>
            <div style="position: relative; display: flex; align-items: center;">
                <input type="password" name="password" id="login_pass" class="form-control" placeholder="••••••••" required style="flex: 1;">
                <i class="fas fa-eye" id="toggleLoginPass" style="position: absolute; right: 15px; cursor: pointer; color: var(--text-muted);"></i>
            </div>
            
            <div style="text-align: right; margin-top: 8px;">
                <a href="forgot_password.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">ลืมรหัสผ่าน?</a>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 600; margin-bottom: 20px;">
            เข้าสู่ระบบด้วยบัญชีปกติ
        </button>
    </form>

    <div style="text-align: center; margin-bottom: 25px; position: relative;">
        <hr style="border: 0; border-top: 1px solid var(--border-color);">
        <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--bg-card); padding: 0 10px; color: var(--text-muted); font-size: 0.8rem;">หรือเชื่อมต่อกับ</span>
    </div>

    <a href="<?php echo $googleAuthUrl; ?>" class="btn" style="width: 100%; background: #ffffff; color: #444; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; font-weight: 500; border-radius: 8px; text-decoration: none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
            <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24s8.955,20,20,20s20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
            <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
            <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
            <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
        </svg>
        บัญชีวิทยาลัย (@bncc.ac.th)
    </a>

    <div style="text-align: center; margin-top: 25px; border-top: 1px solid var(--border-color); padding-top: 20px;">
        <p style="font-size: 0.9rem; color: var(--text-muted);">
            ยังไม่มีบัญชีสมาชิก? <br>
            <a href="register.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">สมัครสมาชิกใหม่ที่นี่</a>
        </p>
    </div>
</div>

<script>
    // สลับการมองเห็นรหัสผ่าน
    const togglePass = document.querySelector('#toggleLoginPass');
    const passwordField = document.querySelector('#login_pass');

    togglePass.addEventListener('click', function () {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
    });
</script>

<?php require_once '../includes/footer.php'; ?>