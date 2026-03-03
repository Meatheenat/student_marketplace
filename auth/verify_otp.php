<?php
/**
 * BNCC Market - Verify OTP Page (Fixed Bug)
 */
$pageTitle = "ยืนยันรหัส OTP";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// ป้องกันการเข้าหน้านี้โดยตรงโดยไม่มี Email ใน Session
if (!isset($_SESSION['reset_email'])) {
    redirect('forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp']);
    $email = $_SESSION['reset_email'];
    $db = getDB();

    // แก้ไข Query: เปลี่ยนจาก ORDER BY created_at เป็น id และดึงมาเช็คใน PHP แทนเพื่อชัวร์เรื่องเวลา
    $stmt = $db->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $otp_input]);
    $row = $stmt->fetch();

    if ($row) {
        // เช็คเวลาหมดอายุโดยใช้ PHP Time (แม่นยำกว่าเทียบใน SQL)
        $current_time = time(); 
        $expiry_time = strtotime($row['expires_at']); 

        if ($current_time <= $expiry_time) {
            // OTP ถูกต้องและยังไม่หมดอายุ
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
}
?>

<div style="max-width: 400px; margin: 60px auto; background: var(--bg-card); padding: 35px; border-radius: 20px; border: 1px solid var(--border-color); text-align: center; box-shadow: var(--shadow); position: relative;">
    
    <div style="position: absolute; top: 15px; right: 15px;">
        <button id="theme-toggle" title="สลับโหมด" style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); cursor: pointer; padding: 8px; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-moon" id="theme-icon"></i>
        </button>
    </div>

    <h2 style="color: var(--primary-color); font-weight: 700; margin-bottom: 10px;">ยืนยัน OTP</h2>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 25px;">
        รหัส 6 หลักถูกส่งไปที่ <br>
        <strong style="color: var(--text-main);"><?php echo e($_SESSION['reset_email']); ?></strong>
    </p>
    
    <?php echo displayFlashMessage(); ?>

    <form method="POST">
        <div class="form-group" style="margin-bottom: 25px;">
            <input type="text" name="otp" class="form-control" placeholder="0 0 0 0 0 0" maxlength="6" 
                   style="text-align: center; font-size: 2rem; letter-spacing: 10px; font-weight: 800; padding: 15px; border-radius: 12px;" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-weight: 600; border-radius: 12px; font-size: 1rem;">
            ยืนยันรหัส OTP
        </button>
    </form>

    <div style="margin-top: 25px; border-top: 1px solid var(--border-color); padding-top: 20px;">
        <p style="font-size: 0.85rem; color: var(--text-muted);">
            ไม่ได้รับรหัส? <a href="forgot_password.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">ส่งรหัสอีกครั้ง</a>
        </p>
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
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        }

        themeBtn.addEventListener('click', () => {
            const isDark = html.classList.toggle('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            themeIcon.classList.replace(isDark ? 'fa-moon' : 'fa-sun', isDark ? 'fa-sun' : 'fa-moon');
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>