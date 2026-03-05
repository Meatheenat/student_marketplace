<?php
/**
 * Student Marketplace - Register for Google Users (Premium UI Sync)
 * [Cite: User Summary] - พัฒนาโดย Gemini Collaboration
 * ดีไซน์เดียวกับหน้า Login และ Register หลัก
 */
$pageTitle = "ลงทะเบียนด้วย Google - Student Marketplace";
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

// 1. ตรวจสอบข้อมูลชั่วคราวจาก Google (Session)
if (!isset($_SESSION['temp_email'])) {
    redirect('login.php');
}

$temp_email = $_SESSION['temp_email'];
$db = getDB();

// 🚫 ตรวจสอบสถานะการโดนแบนก่อนเริ่มกรอกข้อมูล
$check_email_banned = $db->prepare("SELECT is_banned FROM users WHERE email = ?");
$check_email_banned->execute([$temp_email]);
$banned_status = $check_email_banned->fetchColumn();

if ($banned_status == 1) {
    $_SESSION['flash_message'] = "🚫 อีเมลนี้ถูกระงับการใช้งานในระบบ BNCC Market กรุณาติดต่อแอดมิน";
    $_SESSION['flash_type'] = "danger";
    unset($_SESSION['temp_email']);
    redirect('login.php');
}

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
    $role        = 'buyer'; 

    $full_class_room = $class_level . " " . $class_year;

    if (empty($fullname) || empty($student_id) || empty($department) || empty($password)) {
        $_SESSION['flash_message'] = "กรุณากรอกข้อมูลให้ครบถ้วนทุกช่องครับ";
        $_SESSION['flash_type'] = "danger";
    } 
    elseif ($password !== $confirm_password) {
        $_SESSION['flash_message'] = "รหัสผ่านทั้งสองช่องไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง";
        $_SESSION['flash_type'] = "danger";
    }
    else {
        $stmt = $db->prepare("SELECT id FROM users WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash_message'] = "รหัสนักเรียนนี้ถูกใช้งานในระบบแล้ว";
            $_SESSION['flash_type'] = "danger";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (student_id, fullname, class_room, department, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $db->prepare($sql);
            
            if ($insertStmt->execute([$student_id, $fullname, $full_class_room, $department, $email, $hashedPassword, $role])) {
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
    /* ============================================================
       🎨 DYNAMIC THEME VARIABLES (ก๊อปจาก Login ตัวอย่างเป๊ะๆ)
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

    .login-card {
        position: relative;
        width: 100%;
        max-width: 550px; /* ขยายความกว้างสำหรับฟอร์มกรอกข้อมูลเพิ่ม */
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

    .login-header { text-align: center; margin-bottom: 35px; }
    .login-header h2 {
        font-size: 2.5rem;
        font-weight: 900;
        margin: 0 0 5px 0;
        color: var(--text-main);
        letter-spacing: -1px;
        text-shadow: 0 0 25px var(--login-text-glow);
        animation: textGlow 4s infinite alternate;
    }
    
    @keyframes textGlow {
        0% { filter: brightness(1); }
        100% { filter: brightness(1.2); }
    }

    .login-header p {
        color: var(--text-muted);
        font-size: 0.95rem;
        font-weight: 500;
        margin: 0;
    }

    .form-group { margin-bottom: 22px; }
    .field-label {
        display: block;
        font-size: 0.7rem;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 8px;
        margin-left: 5px;
    }
    .field-label span { color: #ef4444; }

    .input-wrapper { position: relative; }
    .form-control-custom {
        width: 100%;
        padding: 16px 20px 16px 55px;
        border-radius: 16px;
        background: var(--login-input-bg);
        border: 1px solid var(--login-card-border);
        color: var(--text-main);
        font-size: 0.95rem;
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

    /* Select Styles Sync */
    select.form-control-custom {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='rgba(150,150,150,0.5)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 20px center;
        background-size: 15px;
    }
    select.form-control-custom option {
        background: var(--bg-card);
        color: var(--text-main);
    }

    .input-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--login-icon-color);
        font-size: 1.1rem;
        transition: 0.3s;
    }
    .form-control-custom:focus + .input-icon { color: #818cf8; }

    .pass-toggle-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--login-icon-color);
        padding: 5px;
        transition: 0.2s;
    }
    .pass-toggle-icon:hover { color: var(--text-main); }

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
        margin-top: 10px;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-login-main:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.6);
        filter: brightness(1.15);
    }

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
</style>

<div class="login-master-wrapper">
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>

    <div class="login-card" id="tilt-card-premium">
        <div class="login-header">
            <h2>Complete Profile</h2>
            <p>ยินดีต้อนรับสมาชิกใหม่ผ่าน Google 👋</p>
        </div>

        <?php echo displayFlashMessage(); ?>

        <form action="register_google.php" method="POST">
            <div class="form-group">
                <label class="field-label">Full Name <span>*</span></label>
                <div class="input-wrapper">
                    <input type="text" name="fullname" class="form-control-custom" placeholder="ระบุชื่อและนามสกุลจริง" required>
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="field-label">Verify Email (Google)</label>
                <div class="input-wrapper">
                    <input type="email" class="form-control-custom" value="<?php echo e($temp_email); ?>" readonly style="opacity: 0.7; cursor: not-allowed;">
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="field-label">Student ID <span>*</span></label>
                <div class="input-wrapper">
                    <input type="text" name="student_id" class="form-control-custom" placeholder="รหัสประจำตัวนักเรียน" required>
                    <i class="fas fa-id-card input-icon"></i>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="field-label">Level <span>*</span></label>
                    <div class="input-wrapper">
                        <select name="class_level" class="form-control-custom" required>
                            <option value="ปวช.">ปวช.</option>
                            <option value="ปวส.">ปวส.</option>
                        </select>
                        <i class="fas fa-graduation-cap input-icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label class="field-label">Year/Room</label>
                    <div class="input-wrapper">
                        <input type="text" name="class_year" class="form-control-custom" placeholder="เช่น 1/2" required>
                        <i class="fas fa-door-open input-icon"></i>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="field-label">Department <span>*</span></label>
                <div class="input-wrapper">
                    <select name="department" class="form-control-custom" required>
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
                    <i class="fas fa-book input-icon"></i>
                </div>
            </div>

            <div style="margin: 30px 0 15px; border-top: 1px solid var(--login-card-border); padding-top: 20px;">
                <p style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Security Setup</p>
            </div>

            <div class="form-group">
                <label class="field-label">New Password <span>*</span></label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" class="form-control-custom" placeholder="กำหนดรหัสผ่าน" required>
                    <i class="fas fa-key input-icon"></i>
                    <i class="fas fa-eye pass-toggle-icon" onclick="togglePass('password', this)"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="field-label">Confirm Password <span>*</span></label>
                <div class="input-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control-custom" placeholder="ยืนยันรหัสผ่านเดิม" required>
                    <i class="fas fa-shield-alt input-icon"></i>
                    <i class="fas fa-eye pass-toggle-icon" onclick="togglePass('confirm_password', this)"></i>
                </div>
            </div>

            <button type="submit" class="btn-login-main">
                สร้างบัญชีและเริ่มใช้งาน <i class="fas fa-rocket"></i>
            </button>
        </form>
    </div>
</div>

<script>
    // 1. ระบบดวงตาสลับรหัสผ่าน (Sync กับ Theme)
    function togglePass(inputId, iconElement) {
        const input = document.getElementById(inputId);
        const isDark = document.documentElement.classList.contains('dark-theme');
        
        if (input.type === "password") {
            input.type = "text";
            iconElement.classList.replace('fa-eye', 'fa-eye-slash');
            iconElement.style.color = isDark ? '#ffffff' : '#1e293b';
        } else {
            input.type = "password";
            iconElement.classList.replace('fa-eye-slash', 'fa-eye');
            iconElement.style.color = 'var(--login-icon-color)';
        }
        
        iconElement.animate([
            { transform: 'translateY(-50%) scale(1)' },
            { transform: 'translateY(-50%) scale(1.3)' },
            { transform: 'translateY(-50%) scale(1)' }
        ], { duration: 250 });
    }

    // 2. ระบบการ์ดเอียงตามเมาส์
    const card = document.querySelector('#tilt-card-premium');
    const wrapper = document.querySelector('.login-master-wrapper');

    wrapper.addEventListener('mousemove', (e) => {
        const x = (window.innerWidth / 2 - e.pageX) / 50;
        const y = (window.innerHeight / 2 - e.pageY) / 50;
        card.style.transform = `perspective(1000px) rotateX(${y}deg) rotateY(${-x}deg) scale3d(1.02, 1.02, 1.02)`;
        card.style.transition = "transform 0.1s ease-out";
    });

    wrapper.addEventListener('mouseleave', () => {
        card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
        card.style.transition = "transform 0.8s ease-out";
    });

    // 3. Theme Listener สำหรับไอคอนดวงตา
    document.getElementById('theme-toggle').addEventListener('click', () => {
        const passInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const icons = document.querySelectorAll('.pass-toggle-icon');
        
        setTimeout(() => {
            const isDark = document.documentElement.classList.contains('dark-theme');
            icons.forEach((icon, index) => {
                const type = index === 0 ? passInput.type : confirmInput.type;
                if(type === 'text') {
                    icon.style.color = isDark ? '#ffffff' : '#1e293b';
                } else {
                    icon.style.color = 'var(--login-icon-color)';
                }
            });
        }, 100);
    });
</script>

<?php require_once '../includes/footer.php'; ?>