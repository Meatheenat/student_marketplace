<?php
/**
 * BNCC Market - Forgot Password System
 * [SEAMLESS PREMIUM REDESIGN - SYNCED WITH LOGIN/REGISTER]
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
            $mail->Username   = 'BNCC Market.k@gmail.com'; // เมลของมึง
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

<style>
    /* ============================================================
       🎨 DYNAMIC THEME VARIABLES (Synced with Login/Register)
       ============================================================ */
    :root {
        --login-bg: #f8fafc;
        --login-orb-1: rgba(79, 70, 229, 0.1);
        --login-orb-2: rgba(168, 85, 247, 0.1);
        --login-card-bg: rgba(255, 255, 255, 0.7);
        --login-card-border: rgba(0, 0, 0, 0.05);
        --login-card-shadow: 0 30px 60px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255,255,255,0.5);
        --login-text-glow: rgba(0, 0, 0, 0.1);
        --login-input-bg: rgba(255, 255, 255, 0.5);
        --login-input-focus: rgba(255, 255, 255, 0.9);
        --login-icon-color: rgba(30, 41, 59, 0.4);
    }

    .dark-theme {
        --login-bg: #0b0e14;
        --login-orb-1: rgba(99, 102, 241, 0.15);
        --login-orb-2: rgba(168, 85, 247, 0.1);
        --login-card-bg: rgba(20, 25, 40, 0.55);
        --login-card-border: rgba(255, 255, 255, 0.05);
        --login-card-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255,255,255,0.05);
        --login-text-glow: rgba(255, 255, 255, 0.4);
        --login-input-bg: rgba(0, 0, 0, 0.3);
        --login-input-focus: rgba(0, 0, 0, 0.5);
        --login-icon-color: rgba(255, 255, 255, 0.3);
    }

    body {
        margin: 0;
        background-color: var(--login-bg) !important;
        font-family: 'Prompt', sans-serif;
        transition: background-color 0.5s ease;
    }

    .login-master-wrapper {
        position: relative;
        min-height: calc(100vh - 75px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        overflow: hidden;
        z-index: 1;
    }

    /* 🔮 พื้นหลังแสงออร่า */
    .glow-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(120px);
        z-index: -1;
        opacity: 0.8;
        animation: pulseOrb 8s infinite alternate ease-in-out;
    }
    .glow-orb-1 {
        width: 600px; height: 600px;
        background: radial-gradient(circle, var(--login-orb-1) 0%, transparent 70%);
        top: -10%; left: 0%;
    }
    .glow-orb-2 {
        width: 500px; height: 500px;
        background: radial-gradient(circle, var(--login-orb-2) 0%, transparent 70%);
        bottom: -10%; right: 0%;
        animation-delay: -4s;
    }

    @keyframes pulseOrb {
        0% { transform: scale(0.9); opacity: 0.6; }
        100% { transform: scale(1.1); opacity: 1; }
    }

    /* 💎 กระจก Glass Card (สไตล์ Seamless) */
    .login-card {
        position: relative;
        width: 100%;
        max-width: 450px;
        background: var(--login-card-bg);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid var(--login-card-border);
        border-radius: 32px;
        padding: 50px 45px;
        box-shadow: var(--login-card-shadow);
        opacity: 0;
        transform: translateY(30px);
        animation: entranceCard 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        transition: background 0.5s ease, border 0.5s ease, box-shadow 0.5s ease;
    }

    @keyframes entranceCard {
        to { opacity: 1; transform: translateY(0); }
    }

    .login-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .login-header h2 {
        font-size: 2.5rem;
        font-weight: 900;
        margin: 0 0 10px 0;
        color: var(--text-main);
        letter-spacing: -1px;
        text-shadow: 0 0 25px var(--login-text-glow);
        animation: textGlow 4s infinite alternate;
    }

    .login-header p {
        color: var(--text-muted);
        font-size: 0.95rem;
        font-weight: 500;
        margin: 0;
    }

    .form-group { margin-bottom: 25px; }
    .field-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 10px;
        margin-left: 5px;
    }

    .input-wrapper { position: relative; }
    .form-control-custom {
        width: 100%;
        padding: 18px 20px 18px 55px;
        border-radius: 18px;
        background: var(--login-input-bg);
        border: 1px solid var(--login-card-border);
        color: var(--text-main);
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        outline: none;
    }
    .form-control-custom:focus {
        background: var(--login-input-focus);
        border-color: rgba(99, 102, 241, 0.6);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        transform: translateY(-2px);
    }

    .input-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--login-icon-color);
        font-size: 1.2rem;
        transition: 0.3s;
        pointer-events: none;
    }
    .form-control-custom:focus + .input-icon { color: #818cf8; }

    /* 🔥 ปุ่มส่งรหัส OTP */
    .btn-login-main {
        width: 100%;
        padding: 18px;
        border-radius: 18px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #ffffff;
        font-weight: 800;
        font-size: 1.05rem;
        border: none;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 15px;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-login-main:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.6);
        filter: brightness(1.15);
    }

    .back-link-box {
        text-align: center;
        margin-top: 30px;
    }
    .back-link-box a {
        color: var(--text-muted);
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .back-link-box a:hover { color: var(--primary-color); }

    .alert {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #ef4444;
        padding: 15px;
        border-radius: 14px;
        margin-bottom: 25px;
        font-size: 0.9rem;
        text-align: center;
    }
    .dark-theme .alert {
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
    }
</style>

<div class="login-master-wrapper">
    
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>

    <div class="login-card" id="tilt-card-premium">
        
        <div class="login-header">
            <h2>ลืมรหัสผ่าน?</h2>
            <p>ระบุอีเมลวิทยาลัยเพื่อรับรหัส OTP 📧</p>
        </div>

        <?php echo displayFlashMessage(); ?>

        <form method="POST">
            <div class="form-group">
                <label class="field-label">COLLEGE EMAIL (@bncc.ac.th)</label>
                <div class="input-wrapper">
                    <input type="email" name="email" class="form-control-custom" placeholder="65xxxxxxxx@bncc.ac.th" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>

            <button type="submit" class="btn-login-main">
                ส่งรหัส OTP <i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i>
            </button>
        </form>

        <div class="back-link-box">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> กลับไปหน้าเข้าสู่ระบบ
            </a>
        </div>
    </div>
</div>

<script>
    // 1. ระบบการ์ดเอียงตามเมาส์ (Sync สไตล์เดียวกัน)
    const card = document.querySelector('#tilt-card-premium');
    const wrapper = document.querySelector('.login-master-wrapper');

    wrapper.addEventListener('mousemove', (e) => {
        const x = (window.innerWidth / 2 - e.pageX) / 40;
        const y = (window.innerHeight / 2 - e.pageY) / 40;
        card.style.transform = `perspective(1000px) rotateX(${y}deg) rotateY(${-x}deg) scale3d(1.02, 1.02, 1.02)`;
        card.style.transition = "transform 0.1s ease-out";
    });

    wrapper.addEventListener('mouseleave', () => {
        card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
        card.style.transition = "transform 0.8s ease-out";
    });

    // 2. โฟกัสช่องกรอกอีเมลอัตโนมัติ
    window.onload = () => {
        setTimeout(() => {
            document.querySelector('input[name="email"]').focus();
        }, 500);
    };
</script>

<?php require_once '../includes/footer.php'; ?>