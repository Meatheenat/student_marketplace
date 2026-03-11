<?php
$pageTitle = "โพสต์ตามหาสินค้า (Want To Buy)";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// บังคับล็อกอิน
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนโพสต์ตามหาของ";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

$db = getDB();

// ดึงหมวดหมู่มาแสดงใน Dropdown
$cat_stmt = $db->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $cat_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description']);
    $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
    $expected_condition = $_POST['expected_condition'] ?? 'any';
    
    // จัดการอัปโหลดรูปภาพอ้างอิง (ถ้ามี)
    $image_url = null;
    if (isset($_FILES['ref_image']) && $_FILES['ref_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = uploadImage($_FILES['ref_image']); 
        if ($uploadedFile) {
            $image_url = $uploadedFile;
        }
    }

    if (empty($title)) {
        $_SESSION['flash_message'] = "กรุณาระบุสิ่งที่ต้องการตามหา";
        $_SESSION['flash_type'] = "danger";
    } else {
        // 🎯 แก้ Query ให้เป็น 'pending' เพื่อรอแอดมินอนุมัติ
        $stmt = $db->prepare("INSERT INTO wtb_posts (user_id, category_id, title, description, image_url, expected_condition, budget, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        if ($stmt->execute([$user_id, $category_id, $title, $description, $image_url, $expected_condition, $budget])) {
            
            // 🔔 แจ้งเตือนแอดมินทุกคน
            $adminStmt = $db->query("SELECT id FROM users WHERE role IN ('admin', 'teacher')");
            $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $adm_id) {
                if (function_exists('sendNotification')) {
                    sendNotification($adm_id, 'system', "รออนุมัติโพสต์หาของ: " . $title, "../admin/approve_wtb.php");
                }
            }

            $_SESSION['flash_message'] = "ส่งโพสต์สำเร็จ! กำลังรอแอดมินตรวจสอบเพื่อความปลอดภัยครับ";
            $_SESSION['flash_type'] = "success";
            redirect('wtb_board.php'); 
        } else {
            $_SESSION['flash_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            $_SESSION['flash_type'] = "danger";
        }
    }
}
?>

<style>
    /* ============================================================
       💎 PREMIUM WTB FORM UI 
       ============================================================ */
    .wtb-wrapper {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
        animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes slideUp {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    .wtb-card {
        background: var(--bg-card);
        border: 2px solid var(--border-color);
        border-radius: 32px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
    }

    .wtb-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }

    .wtb-input {
        width: 100%;
        background: var(--bg-main);
        border: 2px solid var(--border-color);
        color: var(--text-main);
        padding: 15px 20px;
        border-radius: 16px;
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
        outline: none;
    }

    .wtb-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
    }

    .wtb-select {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='gray' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1em;
    }
    .dark-theme .wtb-select {
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    }

    /* 📸 แก้ไขขนาดรูปเหลือ 200px และจัดกลาง */
    .wtb-upload-area {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        max-width: 300px; 
        aspect-ratio: 1;
        margin: 0 auto; 
        border: 3px dashed var(--border-color);
        border-radius: 24px;
        background: var(--bg-main);
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .wtb-upload-area:hover {
        border-color: #6366f1;
        background: rgba(99, 102, 241, 0.05);
    }

    .wtb-upload-placeholder {
        position: absolute;
        text-align: center;
        color: var(--text-muted);
        transition: 0.3s;
        z-index: 1;
    }

    #img_preview {
        width: 100%;
        height: 100%;
        object-fit: contain; 
        display: none;
        z-index: 2;
        border-radius: 20px;
    }

    /* ❌ ปุ่มกากบาทลบรูป */
    #remove_img_btn {
        position: absolute;
        top: -10px;
        right: -10px;
        width: 32px;
        height: 32px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        transition: 0.2s ease;
    }

    #remove_img_btn:hover {
        transform: scale(1.1) rotate(90deg);
    }

    .btn-submit-wtb {
        width: 100%;
        padding: 20px;
        border-radius: 18px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #ffffff; /* เปลี่ยนเป็นสีขาวให้อ่านง่ายขึ้นในปุ่ม Gradient */
        font-weight: 800;
        font-size: 1.15rem;
        border: none;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
        margin-top: 30px;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn-submit-wtb:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.5);
    }

    .req-star { color: #ef4444; margin-left: 4px; }
    .opt-tag { font-size: 0.7rem; color: var(--text-muted); background: var(--bg-main); padding: 3px 8px; border-radius: 6px; margin-left: 8px; font-weight: 600; text-transform: none; }

    @media (max-width: 768px) {
        .wtb-card { padding: 25px; }
        .wtb-upload-area { max-width: 180px; }
    }

    /* 🎯 แก้อาการ Dropdown ตัวหนังสือกลืนกับพื้นหลัง */
    .wtb-select option {
        background-color: #ffffff;
        color: #333;
    }

    .dark-theme .wtb-select option {
        background-color: #1e293b; 
        color: #ffffff;
    }
</style>

<div class="wtb-wrapper">
    <div style="text-align: center; margin-bottom: 35px;">
        <div style="width: 70px; height: 70px; background: rgba(99, 102, 241, 0.1); color: #6366f1; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 15px;">
            <i class="fas fa-bullhorn"></i>
        </div>
        <h1 style="font-weight: 900; color: var(--text-main); font-size: 2.2rem; letter-spacing: -1px; margin-bottom: 5px;">โพสต์ตามหาของ (WTB)</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem;">อยากได้อะไร พิมพ์บอกไว้เลยเดี๋ยวมีคนทักมาเสนอขายเอง!</p>
    </div>

    <div class="wtb-card">
        <?php echo displayFlashMessage(); ?>
        
        <form action="wtb_create.php" method="POST" enctype="multipart/form-data">
            
            <div class="row gx-5">
                <div class="col-md-5 mb-4 text-center">
                    <label class="wtb-label text-start">รูปภาพอ้างอิง <span class="opt-tag">ไม่บังคับ</span></label>
                    <div style="position: relative; max-width: 300px; margin: 0 auto;">
                        <button type="button" id="remove_img_btn" onclick="clearImage()">
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <label for="ref_image" class="wtb-upload-area" id="img_container">
                            <div id="img_placeholder" class="wtb-upload-placeholder">
                                <i class="fas fa-camera" style="font-size: 2.5rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                <p style="font-size: 0.75rem; margin: 0;">คลิกเพิ่มรูป</p>
                            </div>
                            <img id="img_preview" src="">
                        </label>
                        <input type="file" name="ref_image" id="ref_image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="mb-4">
                        <label class="wtb-label">สิ่งที่ต้องการตามหา <span class="req-star">*</span></label>
                        <input type="text" name="title" class="wtb-input" placeholder="ระบุสิ่งที่ต้องการ..." required>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 mb-4">
                            <label class="wtb-label">หมวดหมู่ <span class="req-star">*</span></label>
                            <select name="category_id" class="wtb-input wtb-select" required>
                                <option value="">-- เลือก --</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-sm-6 mb-4">
                            <label class="wtb-label">สภาพที่ต้องการ</label>
                            <select name="expected_condition" class="wtb-input wtb-select">
                                <option value="any">รับทุกสภาพ</option>
                                <option value="good">มือสองสภาพดี</option>
                                <option value="new">มือหนึ่งเท่านั้น</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="wtb-label">งบประมาณที่มี (บาท)</label>
                        <input type="number" name="budget" class="wtb-input" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="mb-2 mt-2">
                <label class="wtb-label">รายละเอียดเพิ่มเติม</label>
                <textarea name="description" class="wtb-input" rows="4" placeholder="ระบุสเปคหรือสภาพที่ต้องการเป็นพิเศษ..."></textarea>
            </div>

            <button type="submit" class="btn-submit-wtb">
                <i class="fas fa-paper-plane"></i> ส่งประกาศเพื่อรออนุมัติ
            </button>
            
        </form>
    </div>
</div>

<script>
    const inputField = document.getElementById('ref_image');
    const preview = document.getElementById('img_preview');
    const placeholder = document.getElementById('img_placeholder');
    const container = document.getElementById('img_container');
    const removeBtn = document.getElementById('remove_img_btn');

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                container.style.borderStyle = 'solid';
                container.style.borderColor = '#6366f1';
                removeBtn.style.display = 'flex'; 
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function clearImage() {
        inputField.value = ''; 
        preview.src = '';
        preview.style.display = 'none';
        placeholder.style.display = 'block'; 
        container.style.borderStyle = 'dashed';
        container.style.borderColor = 'var(--border-color)';
        removeBtn.style.display = 'none'; 
    }
</script>

<?php require_once '../includes/footer.php'; ?>