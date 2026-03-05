<?php
/**
 * BNCC Market - Change Password Page
 * [SOLID HIGH-CONTRAST EDITION]
 * Project: BNCC Student Marketplace [Cite: User Summary]
 */

require_once '../includes/functions.php';

// 1. ตรวจสอบสิทธิ์การเข้าถึง (ต้อง Login ก่อนถึงเปลี่ยนรหัสได้)
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$pageTitle = "เปลี่ยนรหัสผ่าน - BNCC Market";
$user_id = $_SESSION['user_id'];
$db = getDB();

// 2. จัดการคำสั่งเปลี่ยนรหัสผ่าน (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // เช็กว่ากรอกครบไหม
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['flash_message'] = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
        $_SESSION['flash_type'] = "danger";
    } 
    // เช็กรหัสผ่านใหม่ตรงกันไหม
    elseif ($new_password !== $confirm_password) {
        $_SESSION['flash_message'] = "รหัสผ่านใหม่และการยืนยันไม่ตรงกัน";
        $_SESSION['flash_type'] = "danger";
    } 
    // เช็กความยาวรหัสผ่าน (ควรมีอย่างน้อย 6 ตัว)
    elseif (strlen($new_password) < 6) {
        $_SESSION['flash_message'] = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
        $_SESSION['flash_type'] = "danger";
    }
    else {
        // ดึงรหัสผ่านเดิมจากฐานข้อมูลมาเทียบ
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // ตรวจสอบว่ารหัสผ่านปัจจุบันถูกต้องไหม
        if ($user && password_verify($current_password, $user['password'])) {
            // เข้ารหัสผ่านใหม่และอัปเดตลงฐานข้อมูล
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($update->execute([$hashed_password, $user_id])) {
                $_SESSION['flash_message'] = "เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว!";
                $_SESSION['flash_type'] = "success";
                redirect('profile.php'); // เด้งกลับหน้าโปรไฟล์
            }
        } else {
            $_SESSION['flash_message'] = "รหัสผ่านปัจจุบันไม่ถูกต้อง กรุณาลองอีกครั้ง";
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// 3. โหลด Header สำหรับแสดงผล UI
require_once '../includes/header.php';
?>

<style>
    /* ============================================================
       🛠️ SOLID DESIGN SYSTEM - HIGH CONTRAST
       ============================================================ */
    :root {
        --solid-bg: #f8fafc;
        --solid-card: #ffffff;
        --solid-text: #0f172a;
        --solid-border: #cbd5e1;
        --solid-primary: #4f46e5;
        --solid-danger: #ef4444;
    }

    .dark-theme {
        --solid-bg: #0b0e14;
        --solid-card: #161b26;
        --solid-text: #ffffff;
        --solid-border: #2d3748;
        --solid-primary: #6366f1;
    }

    body {
        background-color: var(--solid-bg) !important;
        color: var(--solid-text);
        transition: background 0.3s ease;
    }

    /* 📦 Centered Auth Wrapper */
    .password-master-wrapper {
        min-height: calc(100vh - 100px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    /* 🧱 Solid Card Style */
    .solid-card {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 32px;
        padding: 50px 40px;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        opacity: 0;
        transform: translateY(-30px);
        animation: dropIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes dropIn {
        to { opacity: 1; transform: translateY(0); }
    }

    .header-title {
        text-align: center;
        margin-bottom: 40px;
    }

    .header-title h2 {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--solid-text);
        letter-spacing: -1px;
        margin-bottom: 10px;
    }

    .header-title p {
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.95rem;
    }

    /* ⌨️ Form Inputs */
    .form-group {
        margin-bottom: 25px;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 800;
        font-size: 0.8rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .input-wrapper {
        position: relative;
    }

    .input-solid {
        width: 100%;
        padding: 16px 20px 16px 55px;
        border-radius: 16px;
        border: 2px solid var(--solid-border);
        background: var(--solid-bg);
        color: var(--solid-text);
        font-size: 1.05rem;
        font-weight: 600;
        transition: all 0.3s ease;
        outline: none;
        font-family: 'Prompt', sans-serif;
    }

    .input-solid:focus {
        border-color: var(--solid-danger);
        background: var(--solid-card);
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.15);
        transform: translateY(-2px);
    }

    .input-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--solid-border);
        font-size: 1.2rem;
        transition: 0.3s;
        pointer-events: none;
    }

    .input-solid:focus + .input-icon {
        color: var(--solid-danger);
    }

    /* 👁️ Eye Toggle Icon */
    .pass-toggle-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--solid-border);
        cursor: pointer;
        padding: 5px;
        font-size: 1.1rem;
        transition: 0.2s;
    }
    
    .pass-toggle-icon:hover {
        color: var(--solid-text);
    }

    /* 🔥 Buttons */
    .btn-save-solid {
        width: 100%;
        padding: 18px;
        border-radius: 16px;
        font-weight: 800;
        background: var(--solid-danger);
        color: #fff;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 1.1rem;
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
        transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        margin-top: 15px;
    }

    .btn-save-solid:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 15px 35px rgba(239, 68, 68, 0.5); 
    }

    .back-link {
        display: block;
        text-align: center;
        margin-top: 30px;
        color: var(--text-muted);
        font-weight: 700;
        text-decoration: none;
        font-size: 0.95rem;
        transition: 0.2s;
    }
    .back-link:hover { color: var(--solid-text); transform: translateX(-5px); }

    /* 🚨 Alert Box (Solid Style) */
    .alert-solid {
        background: rgba(239, 68, 68, 0.1);
        border: 2px solid var(--solid-danger);
        color: var(--solid-danger);
        padding: 15px 20px;
        border-radius: 16px;
        margin-bottom: 30px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: shakeError 0.5s ease forwards;
    }

    @keyframes shakeError {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
</style>

<div class="password-master-wrapper">
    <div class="solid-card">
        
        <div class="header-title">
            <div style="width: 70px; height: 70px; background: rgba(239, 68, 68, 0.1); color: var(--solid-danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 2rem;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2>เปลี่ยนรหัสผ่าน</h2>
            <p>เพื่อความปลอดภัย กรุณาตั้งรหัสผ่านที่คาดเดายาก</p>
        </div>

        <?php if(isset($_SESSION['flash_message'])): ?>
            <div class="alert-solid">
                <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i>
                <?= $_SESSION['flash_message']; ?>
            </div>
            <?php 
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
            ?>
        <?php endif; ?>

        <form action="change_password.php" method="POST">
            
            <div class="form-group">
                <label>รหัสผ่านปัจจุบัน (Current Password)</label>
                <div class="input-wrapper">
                    <input type="password" name="current_password" id="current_pass" class="input-solid" placeholder="กรอกรหัสผ่านเดิม" required>
                    <i class="fas fa-unlock-alt input-icon"></i>
                    <i class="fas fa-eye pass-toggle-icon" onclick="togglePass('current_pass', this)"></i>
                </div>
            </div>

            <div style="border-top: 2px dashed var(--solid-border); margin: 30px 0;"></div>

            <div class="form-group">
                <label>รหัสผ่านใหม่ (New Password)</label>
                <div class="input-wrapper">
                    <input type="password" name="new_password" id="new_pass" class="input-solid" placeholder="ตั้งรหัสผ่านใหม่อย่างน้อย 6 ตัว" required>
                    <i class="fas fa-key input-icon"></i>
                    <i class="fas fa-eye pass-toggle-icon" onclick="togglePass('new_pass', this)"></i>
                </div>
            </div>

            <div class="form-group">
                <label>ยืนยันรหัสผ่านใหม่ (Confirm Password)</label>
                <div class="input-wrapper">
                    <input type="password" name="confirm_password" id="confirm_pass" class="input-solid" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" required>
                    <i class="fas fa-check-double input-icon"></i>
                    <i class="fas fa-eye pass-toggle-icon" onclick="togglePass('confirm_pass', this)"></i>
                </div>
            </div>

            <button type="submit" class="btn-save-solid">
                <i class="fas fa-save"></i> บันทึกรหัสผ่านใหม่
            </button>
        </form>

        <a href="profile.php" class="back-link">
            <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> กลับไปหน้าโปรไฟล์
        </a>

    </div>
</div>

<script>
    /**
     * 👁️ ฟังก์ชันเปิด/ปิดรหัสผ่าน พร้อม Animation ดุ๊กดิ๊ก
     */
    function togglePass(inputId, iconElement) {
        const input = document.getElementById(inputId);
        const isDark = document.documentElement.classList.contains('dark-theme');
        
        if (input.type === "password") {
            input.type = "text";
            iconElement.classList.replace('fa-eye', 'fa-eye-slash');
            iconElement.style.color = isDark ? '#ffffff' : '#0f172a';
        } else {
            input.type = "password";
            iconElement.classList.replace('fa-eye-slash', 'fa-eye');
            iconElement.style.color = 'var(--solid-border)';
        }
        
        // เด้งตอบสนองตอนกด
        iconElement.animate([
            { transform: 'translateY(-50%) scale(1)' },
            { transform: 'translateY(-50%) scale(1.3)' },
            { transform: 'translateY(-50%) scale(1)' }
        ], { duration: 250 });
    }
</script>

<?php require_once '../includes/footer.php'; ?>