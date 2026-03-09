<?php
/**
 * Student Marketplace - Appeal Ban Page
 * สำหรับผู้ใช้งานที่ถูกระงับบัญชี เพื่อยื่นเรื่องขอตรวจสอบ
 */
$pageTitle = "ยื่นเรื่องอุทธรณ์ - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $reason     = trim($_POST['reason']);

    if (empty($student_id) || empty($reason)) {
        $_SESSION['flash_message'] = "กรุณากรอกข้อมูลให้ครบถ้วนครับ";
        $_SESSION['flash_type'] = "danger";
    } else {
        // 1. ตรวจสอบว่ามีรหัสนักศึกษานี้ในระบบและโดนแบนจริงหรือไม่
        $stmt = $db->prepare("SELECT id, fullname, email, is_banned FROM users WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_message'] = "ไม่พบรหัสนักศึกษานี้ในระบบ กรุณาตรวจสอบอีกครั้ง";
            $_SESSION['flash_type'] = "danger";
        } elseif ($user['is_banned'] == 0) {
            $_SESSION['flash_message'] = "บัญชีของคุณสถานะปกติ ไม่ได้ถูกระงับการใช้งานครับ";
            $_SESSION['flash_type'] = "info";
        } else {
            // 2. ส่งแจ้งเตือนหา Admin ทุกคน
            $admin_msg = "🚨 [คำขออุทธรณ์] ผู้ใช้ขอยืนยันตัวตนเพื่อปลดแบน\n"
                       . "👤 ชื่อ: " . $user['fullname'] . "\n"
                       . "🆔 รหัส: " . $student_id . "\n"
                       . "📝 เหตุผล: " . $reason;
            
            if (function_exists('notifyAllAdmins')) {
                notifyAllAdmins($admin_msg);
            }

            $adminStmt = $db->query("SELECT id FROM users WHERE role IN ('admin', 'teacher')");
            $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $adm_id) {
                if (function_exists('sendNotification')) {
                    sendNotification($adm_id, 'system', "มีการยื่นอุทธรณ์ปลดแบนจาก: " . $user['fullname'], "../admin/manage_members.php?search=" . urlencode($user['email']));
                }
            }

            $_SESSION['flash_message'] = "ส่งเรื่องอุทธรณ์สำเร็จ! แอดมินจะดำเนินการตรวจสอบให้ภายใน 1-3 วันครับ";
            $_SESSION['flash_type'] = "success";
            redirect('login.php');
        }
    }
}
?>

<style>
    /* ============================================================
       🎨 DYNAMIC THEME & PREMIUM STYLES (แก้ปัญหาสีขาว)
       ============================================================ */
    :root {
        --login-bg: #f8fafc;
        --login-orb-1: rgba(79, 70, 229, 0.1);
        --login-orb-2: rgba(168, 85, 247, 0.1);
        --login-card-bg: rgba(255, 255, 255, 0.7);
        --login-card-border: rgba(0, 0, 0, 0.05);
        --login-card-shadow: 0 30px 60px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255,255,255,0.5);
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
    }

    /* 🔮 พื้นหลังแสงออร่าเด้งๆ */
    .glow-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(120px);
        z-index: -1;
        opacity: 0.8;
        animation: pulseOrb 8s infinite alternate ease-in-out;
    }
    .glow-orb-1 { width: 600px; height: 600px; background: radial-gradient(circle, var(--login-orb-1) 0%, transparent 70%); top: -10%; left: -10%; }
    .glow-orb-2 { width: 500px; height: 500px; background: radial-gradient(circle, rgba(244, 63, 94, 0.15) 0%, transparent 70%); bottom: -10%; right: -10%; animation-delay: -4s; }

    @keyframes pulseOrb {
        0% { transform: scale(0.9); opacity: 0.6; }
        100% { transform: scale(1.1); opacity: 1; }
    }

    /* 💎 Premium Glass Card */
    .appeal-card {
        position: relative;
        width: 100%;
        max-width: 500px;
        background: var(--login-card-bg);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid var(--login-card-border);
        border-radius: 32px;
        padding: 50px 45px;
        box-shadow: var(--login-card-shadow);
        text-align: center;
        opacity: 0;
        transform: translateY(30px);
        animation: entranceCard 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes entranceCard { to { opacity: 1; transform: translateY(0); } }

    .form-control-custom {
        width: 100%;
        padding: 18px 25px;
        border-radius: 18px;
        background: var(--login-input-bg);
        border: 1px solid var(--login-card-border);
        color: var(--text-main);
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        outline: none;
        margin-bottom: 20px;
    }
    .form-control-custom:focus {
        background: var(--login-input-focus);
        border-color: #f43f5e;
        box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.15);
        transform: translateY(-2px);
    }

    .field-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 10px;
        margin-left: 5px;
        text-align: left;
    }

    .btn-appeal-main {
        width: 100%;
        padding: 18px;
        border-radius: 18px;
        background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
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
        box-shadow: 0 10px 25px rgba(244, 63, 94, 0.3);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-appeal-main:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(244, 63, 94, 0.5);
        filter: brightness(1.1);
    }

    .back-link {
        color: var(--text-muted);
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 30px;
        transition: 0.3s;
    }
    .back-link:hover { color: #f43f5e; }
</style>

<div class="login-master-wrapper">
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>

    <div class="appeal-card" id="tilt-card-premium">
        <div style="margin-bottom: 35px;">
            <div style="width: 80px; height: 80px; background: rgba(244, 63, 94, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-user-shield" style="font-size: 2.5rem; color: #f43f5e;"></i>
            </div>
            <h2 style="font-size: 2.2rem; font-weight: 900; color: var(--text-main); margin: 0; letter-spacing: -1px;">ยื่นเรื่องปลดแบน</h2>
            <p style="color: var(--text-muted); margin-top: 5px;">ระบุข้อมูลเพื่อขอยืนยันตัวตนกับผู้ดูแลระบบ</p>
        </div>

        <?php echo displayFlashMessage(); ?>

        <form method="POST">
            <div class="form-group">
                <label class="field-label">รหัสนักศึกษา 11 หลัก</label>
                <input type="text" name="student_id" class="form-control-custom" placeholder="ตัวเลข 11 หลักเท่านั้น" maxlength="11" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>

            <div class="form-group">
                <label class="field-label">เหตุผลที่ต้องการให้ตรวจสอบ</label>
                <textarea name="reason" class="form-control-custom" rows="4" placeholder="ระบุเหตุผลหรือข้อความที่ต้องการแจ้งแอดมิน..." required style="resize: none;"></textarea>
            </div>

            <button type="submit" class="btn-appeal-main">
                ส่งคำขออุทธรณ์ <i class="fas fa-paper-plane"></i>
            </button>
        </form>

        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> กลับไปยังหน้าเข้าสู่ระบบ
        </a>
    </div>
</div>

<script>
    // 🎯 ระบบการ์ดเอียงตามเมาส์ (Sync ทุกหน้า)
    const card = document.querySelector('#tilt-card-premium');
    const wrapper = document.querySelector('.login-master-wrapper');

    wrapper.addEventListener('mousemove', (e) => {
        const x = (window.innerWidth / 2 - e.pageX) / 45;
        const y = (window.innerHeight / 2 - e.pageY) / 45;
        card.style.transform = `perspective(1000px) rotateX(${y}deg) rotateY(${-x}deg) scale3d(1.02, 1.02, 1.02)`;
        card.style.transition = "transform 0.1s ease-out";
    });

    wrapper.addEventListener('mouseleave', () => {
        card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
        card.style.transition = "transform 0.8s ease-out";
    });
</script>

<?php require_once '../includes/footer.php'; ?>