<?php
/**
 * BNCC Market - Forgot Password System
 * ปรับปรุงตามโค้ดล่าสุด: เช็คอีเมลในระบบ, ตรวจสอบสถานะการแบน และใช้ App Password ใหม่
 */
$pageTitle = "ลืมรหัสผ่าน - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';
use PHPMailer\PHPMailer\PHPMailer;
require '../vendor/autoload.php';

if (isLoggedIn()) redirect('../pages/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $db = getDB();

    // 1. 🛠️ แก้ไข SQL: ดึง id และ is_banned มาตรวจสอบพร้อมกัน
    $stmt = $db->prepare("SELECT id, is_banned FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        
        // 🚫 🛠️ เงื่อนไขเพิ่มเติม: ตรวจสอบสถานะการโดนแบน
        if ($user['is_banned'] == 1) {
            $_SESSION['flash_message'] = "🚫 บัญชีนี้ถูกระงับการใช้งานชั่วคราว ไม่สามารถดำเนินการรีเซ็ตรหัสผ่านได้";
            $_SESSION['flash_type'] = "danger";
            redirect('forgot_password.php'); // หยุดการทำงานทันที
        }

        $otp = sprintf("%06d", mt_rand(1, 999999)); // สุ่มเลข 6 หลัก
        $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes")); // หมดอายุใน 15 นาที

        // --- เพิ่มเติม: ลบ OTP เก่าของเมลนี้ทิ้งก่อน เพื่อป้องกันข้อมูลซ้ำซ้อน ---
        $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

        // 2. บันทึกลงตาราง password_resets
        $stmt = $db->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $otp, $expires_at]);

        // 3. ส่ง Email ด้วย PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'meatheenat.k@gmail.com'; // เมลของมึง
            $mail->Password   = 'jxev urqg otnp avnt';    // App Password ตัวเดิม
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->CharSet = 'UTF-8'; // กันภาษาไทยเพี้ยน
            $mail->setFrom('no-reply@bnccmarket.com', 'BNCC Market');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password - OTP';
            $mail->Body    = "รหัส OTP สำหรับรีเซ็ตรหัสผ่านของคุณคือ: <b style='font-size: 20px; color: #4f46e5;'>$otp</b> (รหัสมีอายุ 15 นาที)";

            $mail->send();
            $_SESSION['reset_email'] = $email;
            redirect('verify_otp.php'); 
        } catch (Exception $e) {
            $_SESSION['flash_message'] = "ไม่สามารถส่งอีเมลได้: {$mail->ErrorInfo}";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "ไม่พบอีเมลนี้ในระบบสมาชิก";
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<div style="max-width: 450px; margin: 60px auto; background: var(--bg-card); padding: 40px; border-radius: 20px; border: 1px solid var(--border-color); position: relative; box-shadow: var(--shadow);">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="color: var(--primary-color); font-weight: 700;">ลืมรหัสผ่าน?</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">ระบุอีเมลวิทยาลัยที่ลงทะเบียนเพื่อรับรหัส OTP</p>
    </div>
    
    <?php echo displayFlashMessage(); ?>

    <form method="POST">
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 10px; font-weight: 500;">อีเมลวิทยาลัย (@bncc.ac.th)</label>
            <input type="email" name="email" class="form-control" placeholder="65xxxxxxxx@bncc.ac.th" required style="padding: 12px; border-radius: 10px;">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 14px; font-weight: 600; border-radius: 12px;">
            ส่งรหัส OTP
        </button>
    </form>

    <div style="text-align: center; margin-top: 25px;">
        <a href="login.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> กลับไปหน้าเข้าสู่ระบบ
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeBtn = document.querySelector('#theme-toggle');
        const themeIcon = document.querySelector('#theme-icon');
        const html = document.documentElement;

        const currentTheme = localStorage.getItem('theme') || 'light';
        if (currentTheme === 'dark') {
            html.classList.add('dark-theme');
            if(themeIcon) themeIcon.classList.replace('fa-moon', 'fa-sun');
        }

        if(themeBtn) {
            themeBtn.addEventListener('click', () => {
                const isDark = html.classList.toggle('dark-theme');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                
                if (themeIcon) {
                    if (isDark) {
                        themeIcon.classList.replace('fa-moon', 'fa-sun');
                    } else {
                        themeIcon.classList.replace('fa-sun', 'fa-moon');
                    }
                }
            });
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>