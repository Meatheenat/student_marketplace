<?php
/**
 * BNCC Market - Verify OTP Page (Unified System)
 * รองรับทั้งการสมัครสมาชิก (Register) และ การลืมรหัสผ่าน (Forgot Password)
 */
$pageTitle = "ยืนยันรหัส OTP - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// ดักจับว่ามาจาก Flow ไหน (Register หรือ Forgot Password)
$is_registration = isset($_SESSION['verify_email']);
$is_password_reset = isset($_SESSION['reset_email']);

if (!$is_registration && !$is_password_reset) {
    redirect('login.php');
}

$email = $is_registration ? $_SESSION['verify_email'] : $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp']);
    $db = getDB();

    if ($is_password_reset) {
        // ----------------------------------------------------
        // 🔑 Logic สำหรับ "ลืมรหัสผ่าน" (Forgot Password)
        // ----------------------------------------------------
        $stmt = $db->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$email, $otp_input]);
        $row = $stmt->fetch();

        if ($row) {
            $current_time = time(); 
            $expiry_time = strtotime($row['expires_at']); 

            if ($current_time <= $expiry_time) {
                $_SESSION['otp_verified'] = true;
                redirect('reset_password.php');
            } else {
                $_SESSION['flash_message'] = "รหัส OTP นี้หมดอายุแล้ว กรุณาขอรหัสใหม่";
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            $_SESSION['flash_message'] = "รหัส OTP ไม่ถูกต้อง";
            $_SESSION['flash_type'] = "danger";
        }
        
    } elseif ($is_registration) {
        // ----------------------------------------------------
        // 📝 Logic สำหรับ "สมัครสมาชิกใหม่" (Registration)
        // ----------------------------------------------------
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND otp_code = ? AND is_verified = 0");
        $stmt->execute([$email, $otp_input]);
        $user = $stmt->fetch();

        if ($user) {
            // อัปเดตสถานะเป็นยืนยันแล้ว และลบ OTP ทิ้ง
            $update = $db->prepare("UPDATE users SET is_verified = 1, otp_code = NULL WHERE id = ?");
            $update->execute([$user['id']]);

            unset($_SESSION['verify_email']);
            $_SESSION['flash_message'] = "ยืนยันบัญชีสำเร็จ! เข้าสู่ระบบได้เลย";
            $_SESSION['flash_type'] = "success";
            redirect('login.php');
        } else {
            $_SESSION['flash_message'] = "รหัส OTP ไม่ถูกต้อง ลองใหม่อีกครั้ง";
            $_SESSION['flash_type'] = "danger";
        }
    }
}
?>

<style>
    /* ============================================================
       🎨 DYNAMIC THEME VARIABLES (Synced perfectly with Login)
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
    }

    body {
        margin: 0;
        background-color: var(--login-bg) !important;
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

    /* 🔮 พื้นหลังแสงออร่าไร้รอยต่อ */
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

    /* 💎 กระจก Glass Card */
    .login-card {
        position: relative;
        width: 100%;
        max-width: 420px;
        background: var(--login-card-bg);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid var(--login-card-border);
        border-radius: 32px;
        padding: 50px 40px;
        box-shadow: var(--login-card-shadow);
        text-align: center;
        opacity: 0;
        transform: translateY(30px);
        animation: entranceCard 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes entranceCard {
        to { opacity: 1; transform: translateY(0); }
    }

    .login-header h2 {
        font-size: 2.5rem;
        font-weight: 900;
        margin-bottom: 10px;
        color: var(--text-main);
        letter-spacing: -1px;
        text-shadow: 0 0 25px var(--login-text-glow);
    }

    .login-header p {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 35px;
        line-height: 1.6;
    }

    /* ⌨️ OTP Input Styling */
    .otp-input-custom {
        width: 100%;
        padding: 20px;
        border-radius: 20px;
        background: var(--login-input-bg);
        border: 2px solid var(--login-card-border);
        color: var(--text-main);
        text-align: center;
        font-size: 2.5rem;
        letter-spacing: 12px;
        font-weight: 800;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        outline: none;
    }

    .otp-input-custom:focus {
        background: var(--login-input-focus);
        border-color: #818cf8;
        box-shadow: 0 15px 35px -10px rgba(99, 102, 241, 0.4);
        transform: translateY(-5px) scale(1.02);
    }

    /* 🔥 ปุ่มยืนยัน Radiant */
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
        margin-top: 30px;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        transition: all 0.3s ease;
    }
    .btn-login-main:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.6);
        filter: brightness(1.1);
    }

    .footer-links {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid var(--login-card-border);
    }
    
    .resend-link {
        color: #818cf8;
        font-weight: 700;
        text-decoration: none;
        transition: 0.3s;
    }
    .resend-link:hover { text-shadow: 0 0 10px rgba(129, 140, 248, 0.5); color: var(--primary-color); }

    /* Alert Style Sync */
    .alert {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #ef4444;
        padding: 15px;
        border-radius: 14px;
        margin-bottom: 25px;
        font-size: 0.9rem;
    }
    .dark-theme .alert {
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
    }
    .alert.success {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }
    .dark-theme .alert.success {
        background: rgba(16, 185, 129, 0.15);
        color: #6ee7b7;
    }
</style>

<div class="login-master-wrapper">
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>

    <div class="login-card" id="tilt-card-premium">
        <div class="login-header">
            <h2>ยืนยันตัวตน</h2>
            <p>
                รหัสความปลอดภัย 6 หลักถูกส่งไปที่ <br>
                <strong style="color: var(--primary-color);"><?php echo e($email); ?></strong>
            </p>
        </div>

        <?php echo displayFlashMessage(); ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="otp" class="otp-input-custom" 
                       placeholder="······" maxlength="6" 
                       pattern="\d{6}" inputmode="numeric"
                       required autofocus>
            </div>
            
            <button type="submit" class="btn-login-main">
                ตรวจสอบรหัส OTP <i class="fas fa-shield-check"></i>
            </button>
        </form>

        <div class="footer-links">
            <p style="font-size: 0.85rem; color: var(--text-muted);">
                ไม่ได้รับรหัสใช่หรือไม่? <br>
                <?php if ($is_password_reset): ?>
                    <a href="forgot_password.php" class="resend-link">ส่งรหัสใหม่อีกครั้ง</a>
                <?php else: ?>
                    <a href="register.php" class="resend-link">ลงทะเบียนใหม่อีกครั้ง</a>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<script>
    // 🎯 ระบบการ์ดเอียงตามเมาส์ (Sync ทุกหน้า)
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

    // กรองให้พิมพ์ได้เฉพาะตัวเลขเท่านั้นในช่อง OTP
    const otpInput = document.querySelector('.otp-input-custom');
    otpInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
</script>

<?php require_once '../includes/footer.php'; ?>