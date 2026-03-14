<?php
/**
 * BNCC MARKETPLACE - OFFICIAL REPORT PAGE (Autocomplete Version)
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

// 🎯 เพิ่มการดึงรายชื่อ User ทั้งหมดมาทำ Autocomplete (เอาเฉพาะที่ไม่ได้ถูกแบน)
$user_list_stmt = $db->query("SELECT id, fullname, role FROM users WHERE is_ban = 0 ORDER BY fullname ASC");
$all_users = $user_list_stmt->fetchAll();

// รับค่าเบื้องต้นกรณีส่งมาจากหน้าสินค้า/คอมเมนต์ (ถ้ามี)
$t_id = isset($_GET['id']) ? (int)$_GET['id'] : '';
$t_type = isset($_GET['type']) ? $_GET['type'] : 'user'; // user, product, comment
$p_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0; // กลับไปหน้าสินค้าเดิม

// ดึงข้อมูลชื่อ User กรณีระบุ ID มาใน URL (เช่น กดรีพอร์ตมาจากหน้าโปรไฟล์)
$default_user_name = "";
if($t_type === 'user' && $t_id > 0) {
    foreach($all_users as $u) {
        if($u['id'] == $t_id) {
            $default_user_name = $u['fullname'] . " (ID: " . $u['id'] . ")";
            break;
        }
    }
}

// --- LOGIC: จัดการการส่งฟอร์ม ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = $_POST['target_id'];
    $target_type = $_POST['target_type'];
    $reason = trim($_POST['reason']);
    $product_id = $_POST['product_id'];

    if (!empty($reason) && !empty($target_id)) {
        $stmt = $db->prepare("INSERT INTO reports (reporter_id, target_id, target_type, reason, status) VALUES (?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$_SESSION['user_id'], $target_id, $target_type, $reason])) {
            
            // 🔔 แจ้งเตือนแอดมินทุกคนทาง LINE
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
            
            if ($product_id > 0) {
                redirect("product_detail.php?id=" . $product_id);
            } else {
                redirect("../pages/index.php");
            }
        }
    } else {
        $error = "กรุณาเลือกผู้ใช้งานที่ถูกต้องและระบุรายละเอียดเหตุผล";
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

    .report-header { text-align: center; margin-bottom: 35px; }
    .report-header i { font-size: 4rem; color: #ef4444; margin-bottom: 15px; }
    .report-header h1 { font-size: 2rem; font-weight: 900; margin-bottom: 10px; letter-spacing: -1px; }

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

    /* 🎯 แก้ไข: ล็อกสีตัวอักษรดำสำหรับช่องค้นหา (Autocomplete) */
    #user_search {
        color: #000000 !important; /* ล็อกสีตัวอักษรดำ */
        background-color: #ffffff !important; /* ล็อกพื้นหลังขาวให้ตัดกัน */
    }

    /* 🎯 แก้ไข: ล็อกสีตัวอักษรในรายการตัวเลือก (Datalist) */
    datalist option {
        color: #000000 !important;
        background-color: #ffffff !important;
    }

    .report-field:focus {
        outline: none;
        border-color: #ef4444;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    }

    /* ตกแต่ง Autocomplete */
    .search-container { position: relative; }
    .search-icon { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: #64748b; }

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
            <p>ค้นหารายชื่อผู้ใช้งานที่ต้องการรายงาน</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger mb-4" style="border-radius: 15px; font-weight: 600;">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="reportForm">
            <input type="hidden" name="target_type" value="<?= $t_type ?>">
            <input type="hidden" name="product_id" value="<?= $p_id ?>">
            <input type="hidden" name="target_id" id="real_target_id" value="<?= $t_id ?>">

            <div class="report-form-group">
                <label class="report-label">เป้าหมายที่ถูกรายงาน (พิมพ์เพื่อค้นหาชื่อ) <span style="color:#ef4444">*</span></label>
                <div class="search-container">
                    <input type="text" 
                           id="user_search" 
                           class="report-field" 
                           placeholder="พิมพ์ชื่อ นามสกุล หรือ ID..." 
                           list="user_list" 
                           value="<?= $default_user_name ?>"
                           autocomplete="off" 
                           required>
                    <i class="fas fa-search search-icon"></i>
                    
                    <datalist id="user_list">
                        <?php foreach($all_users as $user): ?>
                            <option data-id="<?= $user['id'] ?>" value="<?= htmlspecialchars($user['fullname']) ?> (ID: <?= $user['id'] ?>)">
                                สิทธิ์: <?= $user['role'] ?>
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>

            <div class="report-form-group">
                <label class="report-label">เหตุผลหรือรายละเอียดปัญหา <span style="color:#ef4444">*</span></label>
                <textarea name="reason" class="report-field" rows="6" 
                    placeholder="กรุณาอธิบายปัญหาที่เกิดขึ้นโดยละเอียด..." required></textarea>
            </div>

            <button type="submit" class="btn-submit-report">
                <i class="fas fa-paper-plane"></i> ยืนยันการส่งรายงาน
            </button>

            <div class="notice-box">
                <i class="fas fa-info-circle me-2"></i> 
                <b>หมายเหตุ:</b> กรุณาเลือกรายชื่อผู้ใช้งานจากรายการที่ปรากฏขึ้นเท่านั้น เพื่อให้ระบบสามารถบันทึก ID ได้ถูกต้อง
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('user_search').addEventListener('input', function(e) {
    const input = e.target;
    const list = document.getElementById('user_list');
    const hiddenIdInput = document.getElementById('real_target_id');
    const inputValue = input.value;

    // ล้างค่า ID ก่อนถ้ายังเลือกไม่ตรง
    hiddenIdInput.value = "";

    // ตรวจสอบว่าสิ่งที่พิมพ์ตรงกับ Option ไหนใน Datalist หรือไม่
    for (let i = 0; i < list.options.length; i++) {
        if (list.options[i].value === inputValue) {
            // ดึง Data-ID จาก option ที่เลือกมาใส่ใน Hidden Input
            hiddenIdInput.value = list.options[i].getAttribute('data-id');
            input.style.borderColor = "#10b981"; // เปลี่ยนเป็นสีเขียวเมื่อเลือกถูก
            break;
        } else {
            input.style.borderColor = ""; 
        }
    }
});

// ดักจับตอน Submit ถ้าไม่ได้เลือกชื่อจาก List
document.getElementById('reportForm').addEventListener('submit', function(e) {
    const hiddenId = document.getElementById('real_target_id').value;
    if (!hiddenId) {
        e.preventDefault();
        alert('กรุณาเลือกรายชื่อผู้ใช้งานที่เด้งขึ้นมาจากรายการค้นหาเท่านั้น');
        document.getElementById('user_search').focus();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>