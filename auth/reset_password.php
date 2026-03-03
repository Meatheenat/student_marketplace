<?php
/**
 * BNCC Market - Reset Password with Strength Indicator
 */
$pageTitle = "กำหนดรหัสผ่านใหม่ - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    redirect('forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'];

    if (strlen($new_pass) < 6) {
        $_SESSION['flash_message'] = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
        $_SESSION['flash_type'] = "danger";
    } elseif ($new_pass === $confirm_pass) {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        if ($stmt->execute([$hashed_pass, $email])) {
            $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            unset($_SESSION['reset_email'], $_SESSION['otp_verified']);
            $_SESSION['flash_message'] = "เปลี่ยนรหัสผ่านสำเร็จ! กรุณาเข้าสู่ระบบ";
            $_SESSION['flash_type'] = "success";
            redirect('login.php');
        }
    } else {
        $_SESSION['flash_message'] = "รหัสผ่านไม่ตรงกัน";
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<div style="max-width: 450px; margin: 60px auto; background: var(--bg-card); padding: 35px; border-radius: 20px; border: 1px solid var(--border-color); position: relative; box-shadow: var(--shadow);">
    
    <div style="position: absolute; top: 20px; right: 20px;">
        <button id="theme-toggle" style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); cursor: pointer; padding: 8px; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-moon" id="theme-icon"></i>
        </button>
    </div>

    <div style="text-align: center; margin-bottom: 25px;">
        <h2 style="color: var(--primary-color); font-weight: 700;">ตั้งรหัสผ่านใหม่</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem;">บัญชี: <strong><?php echo e($_SESSION['reset_email']); ?></strong></p>
    </div>

    <?php echo displayFlashMessage(); ?>

    <form method="POST" id="resetForm">
        <div class="form-group" style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500;">รหัสผ่านใหม่</label>
            <div style="position: relative;">
                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="ระบุรหัสผ่านใหม่" required style="width: 100%; padding-right: 40px;">
                <i class="fas fa-eye toggle-pass" data-target="new_password" style="position: absolute; right: 12px; top: 12px; cursor: pointer; color: var(--text-muted);"></i>
            </div>
            
            <div style="height: 4px; width: 100%; background: var(--border-color); margin-top: 10px; border-radius: 2px; overflow: hidden;">
                <div id="strength-bar" style="height: 100%; width: 0%; transition: 0.3s ease;"></div>
            </div>
            <small id="strength-text" style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 5px;">ความปลอดภัย: ระบุรหัสผ่าน...</small>
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500;">ยืนยันรหัสผ่านใหม่</label>
            <div style="position: relative;">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="กรอกรหัสผ่านอีกครั้ง" required style="width: 100%; padding-right: 40px;">
                <i class="fas fa-eye toggle-pass" data-target="confirm_password" style="position: absolute; right: 12px; top: 12px; cursor: pointer; color: var(--text-muted);"></i>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-weight: 600; border-radius: 12px;">
            ยืนยันรหัสผ่านใหม่
        </button>
    </form>
</div>



<script>
document.addEventListener('DOMContentLoaded', () => {
    const passInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');

    // --- ระบบวัดความแรงรหัส (Real-time Password Strength) ---
    passInput.addEventListener('input', () => {
        const val = passInput.value;
        let strength = 0;
        let status = "อ่อนแอ";
        let color = "#ef4444"; // Danger

        if (val.length >= 6) strength += 25;
        if (/[A-Z]/.test(val)) strength += 25;
        if (/[0-9]/.test(val)) strength += 25;
        if (/[^A-Za-z0-9]/.test(val)) strength += 25;

        if (strength === 50) { status = "ปานกลาง"; color = "#f59e0b"; } // Warning
        if (strength >= 75) { status = "ปลอดภัย"; color = "#22c55e"; } // Success

        strengthBar.style.width = strength + "%";
        strengthBar.style.background = color;
        strengthText.innerText = "ความปลอดภัย: " + status;
        strengthText.style.color = color;
    });

    // --- ระบบสลับการมองเห็นรหัสผ่าน ---
    document.querySelectorAll('.toggle-pass').forEach(btn => {
        btn.addEventListener('click', function() {
            const target = document.getElementById(this.dataset.target);
            const isPass = target.type === 'password';
            target.type = isPass ? 'text' : 'password';
            this.classList.toggle('fa-eye-slash');
        });
    });

    // --- ระบบสลับธีม ---
    const themeBtn = document.getElementById('theme-toggle');
    const html = document.documentElement;
    themeBtn.addEventListener('click', () => {
        const isDark = html.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        document.getElementById('theme-icon').classList.replace(isDark ? 'fa-moon' : 'fa-sun', isDark ? 'fa-sun' : 'fa-moon');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>