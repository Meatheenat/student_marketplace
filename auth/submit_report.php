<?php
/**
 * BNCC MARKETPLACE - OFFICIAL REPORT PAGE
 * ---------------------------------------
 */
require_once '../includes/functions.php';
$pageTitle = "ศูนย์รายงานความปลอดภัย - BNCC Market";
require_once '../includes/header.php';

// บังคับ Login
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบเพื่อดำเนินการแจ้งรีพอร์ต";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

$db = getDB();

// รับค่าเบื้องต้นกรณีส่งมาจากหน้าสินค้า/คอมเมนต์ (ถ้ามี)
$t_id = isset($_GET['id']) ? (int)$_GET['id'] : '';
$t_type = isset($_GET['type']) ? $_GET['type'] : 'user'; // user, product, comment
$p_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0; // กลับไปหน้าสินค้าเดิม

// --- LOGIC: จัดการการส่งฟอร์ม ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = $_POST['target_id'];
    $target_type = $_POST['target_type'];
    $reason = trim($_POST['reason']);
    $product_id = $_POST['product_id'];

    if (!empty($reason)) {
        $stmt = $db->prepare("INSERT INTO reports (reporter_id, target_id, target_type, reason, status) VALUES (?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$_SESSION['user_id'], $target_id, $target_type, $reason])) {
            
            // 🔔 แจ้งเตือนแอดมินทุกคนทาง LINE (ใช้ฟังก์ชันเดิมของพี่)
            if (function_exists('notifyAllAdmins')) {
                $admin_msg = "🚩 [Report] มีการแจ้งรีพอร์ตใหม่!\n"
                           . "ประเภท: " . strtoupper($target_type) . "\n"
                           . "ID เป้าหมาย: " . $target_id . "\n"
                           . "เหตุผล: " . $reason . "\n"
                           . "จาก: " . $_SESSION['fullname'];
                notifyAllAdmins($admin_msg);
            }

            $_SESSION['flash_message'] = "✅ ส่งรายงานเรียบร้อยแล้ว แอดมินจะดำเนินการตรวจสอบโดยเร็วที่สุด";
            $_SESSION['flash_type'] = "success";
            
            // กลับไปหน้าเดิม
            if ($product_id > 0) {
                redirect("product_detail.php?id=" . $product_id);
            } else {
                redirect("index.php");
            }
        }
    } else {
        $error = "กรุณาระบุรายละเอียดหรือเหตุผลในการรีพอร์ต";
    }
}
?>

<style>
    .report-main-wrapper {
        max-width: 700px;
        margin: 60px auto;
        padding: 0 20px;
        animation: fadeIn 0.5s ease;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .report-solid-card {
        background: var(--bg-card, #ffffff);
        border: 2px solid var(--border-color, #e2e8f0);
        border-radius: 30px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
    }

    .report-header {
        text-align: center;
        margin-bottom: 35px;
    }
    .report-header i {
        font-size: 4rem;
        color: #ef4444;
        margin-bottom: 15px;
    }
    .report-header h1 {
        font-size: 2rem;
        font-weight: 900;
        margin-bottom: 10px;
        letter-spacing: -1px;
    }

    .report-form-group { margin-bottom: 25px; }
    .report-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-muted, #64748b);
        margin-bottom: 10px;
        letter-spacing: 1px;
    }

    .report-field {
        width: 100%;
        padding: 18px 22px;
        border-radius: 18px;
        border: 2px solid var(--border-color, #e2e8f0);
        background: var(--bg-main, #f8fafc);
        color: var(--text-main, #0f172a);
        font-family: inherit;
        font-size: 1.05rem;
        font-weight: 600;
        transition: 0.3s;
    }
    .report-field:focus {
        outline: none;
        border-color: #ef4444;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    }

    .report-type-pill {
        display: inline-block;
        padding: 5px 15px;
        background: #ef4444;
        color: #fff;
        border-radius: 10px;
        font-weight: 800;
        font-size: 0.8rem;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .btn-submit-report {
        width: 100%;
        padding: 20px;
        border-radius: 18px;
        border: none;
        background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
        color: #ffffff;
        font-weight: 900;
        font-size: 1.2rem;
        cursor: pointer;
        transition: 0.3s;
        box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }
    .btn-submit-report:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(239, 68, 68, 0.4);
    }

    .notice-box {
        background: rgba(15, 23, 42, 0.05);
        padding: 20px;
        border-radius: 15px;
        font-size: 0.9rem;
        color: var(--text-muted, #64748b);
        line-height: 1.6;
        margin-top: 30px;
    }
</style>

<div class="report-main-wrapper">
    <div class="report-solid-card">
        <div class="report-header">
            <i class="fas fa-shield-alt"></i>
            <h1>แจ้งรายงานความปลอดภัย</h1>
            <p>ความปลอดภัยของสมาชิกคือหัวใจสำคัญของเรา</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger mb-4" style="border-radius: 15px; font-weight: 600;">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="target_id" value="<?= $t_id ?>">
            <input type="hidden" name="target_type" value="<?= $t_type ?>">
            <input type="hidden" name="product_id" value="<?= $p_id ?>">

            <div class="report-form-group">
                <span class="report-label">เป้าหมายที่ถูกรายงาน</span>
                <div class="report-type-pill"><?= strtoupper($t_type) ?> ID: <?= $t_id ?></div>
            </div>

            <div class="report-form-group">
                <label class="report-label">เหตุผลหรือรายละเอียดปัญหา <span style="color:#ef4444">*</span></label>
                <textarea name="reason" class="report-field" rows="6" 
                    placeholder="กรุณาอธิบายปัญหาที่เกิดขึ้น เช่น พฤติกรรมการโกง, การใช้คำพูดไม่เหมาะสม, หรือสินค้าผิดกฎ..." required></textarea>
            </div>

            <button type="submit" class="btn-submit-report">
                <i class="fas fa-paper-plane"></i> ยืนยันการส่งรายงาน
            </button>

            <div class="notice-box">
                <i class="fas fa-info-circle me-2"></i> 
                <b>หมายเหตุ:</b> รายงานของคุณจะเป็นความลับ ทีมงานจะใช้เวลาตรวจสอบ 24-48 ชม. หากพบว่ามีการแจ้งรายงานเท็จเพื่อกลั่นแกล้งผู้อื่น บัญชีของคุณอาจถูกระงับการใช้งานได้
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>