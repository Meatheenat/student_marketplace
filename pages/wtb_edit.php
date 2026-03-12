<?php
/**
 * BNCC MARKET - WTB EDIT SYSTEM (MATCHING CREATE UI)
 */
$pageTitle = "แก้ไขประกาศตามหา - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// 1. บังคับล็อกอิน
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนแก้ไขประกาศ";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

$db = getDB();
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. ดึงข้อมูลเดิมมาโชว์ (เช็คว่าเป็นเจ้าของโพสต์จริงไหม)
$stmt = $db->prepare("SELECT * FROM wtb_posts WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$post_id, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) {
    $_SESSION['flash_message'] = "ไม่พบประกาศที่ต้องการแก้ไข หรือคุณไม่มีสิทธิ์เข้าถึง";
    $_SESSION['flash_type'] = "danger";
    redirect('wtb_board.php');
}

// ดึงหมวดหมู่มาแสดงใน Dropdown
$cat_stmt = $db->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $cat_stmt->fetchAll();

// 3. จัดการการส่งฟอร์มแก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description']);
    $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
    $expected_condition = $_POST['expected_condition'] ?? 'any';
    
    // จัดการรูปภาพ (ถ้ามีอัปโหลดใหม่)
    $image_url = $post['image_url']; // เริ่มต้นใช้รูปเดิม
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
        // 🎯 แก้ไขข้อมูลและส่งกลับไป 'pending' เพื่อให้แอดมินอนุมัติใหม่ (กันเด็กแอบแก้คำหยาบทีหลัง)
        $update_stmt = $db->prepare("UPDATE wtb_posts SET 
            category_id = ?, 
            title = ?, 
            description = ?, 
            image_url = ?, 
            expected_condition = ?, 
            budget = ?, 
            status = 'pending' 
            WHERE id = ? AND user_id = ?");
        
        if ($update_stmt->execute([$category_id, $title, $description, $image_url, $expected_condition, $budget, $post_id, $_SESSION['user_id']])) {
            
            $_SESSION['flash_message'] = "แก้ไขข้อมูลสำเร็จ! ประกาศจะแสดงผลอีกครั้งหลังแอดมินอนุมัติครับ";
            $_SESSION['flash_type'] = "success";
            redirect('wtb_board.php'); 
        } else {
            $_SESSION['flash_message'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
            $_SESSION['flash_type'] = "danger";
        }
    }
}
?>

<style>
    /* ============================================================
       💎 PREMIUM WTB FORM UI (SYNC WITH CREATE PAGE)
       ============================================================ */
    .wtb-wrapper { max-width: 900px; margin: 40px auto; padding: 0 20px; animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    @keyframes slideUp { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }

    .wtb-card { background: var(--bg-card); border: 2px solid var(--border-color); border-radius: 32px; padding: 40px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05); }
    .wtb-label { display: block; font-size: 0.85rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
    
    .wtb-input { width: 100%; background: var(--bg-main); border: 2px solid var(--border-color); color: var(--text-main); padding: 15px 20px; border-radius: 16px; font-size: 1rem; font-weight: 600; transition: all 0.3s ease; outline: none; }
    .wtb-input:focus { border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15); }

    .wtb-select { appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='gray' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1em; }
    .dark-theme .wtb-select { background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); }

    /* 📸 Upload Area - Fixed 200px as requested */
    .wtb-upload-area { display: flex; align-items: center; justify-content: center; width: 100%; max-width: 200px; aspect-ratio: 1; margin: 0 auto; border: 3px dashed var(--border-color); border-radius: 24px; background: var(--bg-main); cursor: pointer; position: relative; overflow: hidden; transition: all 0.3s ease; }
    .wtb-upload-area:hover { border-color: #6366f1; background: rgba(99, 102, 241, 0.05); }

    #img_preview { width: 100%; height: 100%; object-fit: contain; z-index: 2; border-radius: 20px; }

    /* ❌ Remove Button */
    #remove_img_btn { position: absolute; top: -10px; right: -10px; width: 32px; height: 32px; background: #ef4444; color: white; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10; box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4); transition: 0.2s ease; }
    #remove_img_btn:hover { transform: scale(1.1) rotate(90deg); }

    .btn-submit-wtb { width: 100%; padding: 20px; border-radius: 18px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #ffffff; font-weight: 800; font-size: 1.15rem; border: none; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 12px; margin-top: 30px; box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3); transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    .btn-submit-wtb:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(99, 102, 241, 0.5); }

    .wtb-select option { background-color: #ffffff; color: #333; }
    .dark-theme .wtb-select option { background-color: #1e293b; color: #ffffff; }

    .req-star { color: #ef4444; margin-left: 4px; }
    .opt-tag { font-size: 0.7rem; color: var(--text-muted); background: var(--bg-main); padding: 3px 8px; border-radius: 6px; margin-left: 8px; font-weight: 600; text-transform: none; }
</style>

<div class="wtb-wrapper">
    <div style="text-align: center; margin-bottom: 35px;">
        <div style="width: 70px; height: 70px; background: rgba(99, 102, 241, 0.1); color: #6366f1; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 15px;">
            <i class="fas fa-edit"></i>
        </div>
        <h1 style="font-weight: 900; color: var(--text-main); font-size: 2.2rem; letter-spacing: -1px; margin-bottom: 5px;">แก้ไขประกาศตามหา</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem;">คุณสามารถปรับเปลี่ยนข้อมูลได้ตลอดเวลา (ประกาศจะรออนุมัติใหม่อีกครั้ง)</p>
    </div>

    <div class="wtb-card">
        <?php echo displayFlashMessage(); ?>
        
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row gx-5">
                <div class="col-md-5 mb-4 text-center">
                    <label class="wtb-label text-start">รูปภาพอ้างอิง</label>
                    <div style="position: relative; max-width: 200px; margin: 0 auto;">
                        <button type="button" id="remove_img_btn" onclick="clearImage()" style="<?= $post['image_url'] ? 'display:flex;' : 'display:none;' ?>">
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <label for="ref_image" class="wtb-upload-area" id="img_container">
                            <div id="img_placeholder" class="wtb-upload-placeholder" style="<?= $post['image_url'] ? 'display:none;' : '' ?>">
                                <i class="fas fa-camera" style="font-size: 2.5rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                <p style="font-size: 0.75rem; margin: 0;">คลิกเพื่อเปลี่ยนรูป</p>
                            </div>
                            <img id="img_preview" src="<?= $post['image_url'] ? '../assets/images/products/'.$post['image_url'] : '' ?>" style="<?= $post['image_url'] ? 'display:block;' : 'display:none;' ?>">
                        </label>
                        <input type="file" name="ref_image" id="ref_image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="mb-4">
                        <label class="wtb-label">สิ่งที่ต้องการตามหา <span class="req-star">*</span></label>
                        <input type="text" name="title" class="wtb-input" value="<?= htmlspecialchars($post['title']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 mb-4">
                            <label class="wtb-label">หมวดหมู่ <span class="req-star">*</span></label>
                            <select name="category_id" class="wtb-input wtb-select" required>
                                <option value="">-- เลือก --</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-sm-6 mb-4">
                            <label class="wtb-label">สภาพที่ต้องการ</label>
                            <select name="expected_condition" class="wtb-input wtb-select">
                                <option value="any" <?= $post['expected_condition'] == 'any' ? 'selected' : '' ?>>ทุกสภาพ</option>
                                <option value="good" <?= $post['expected_condition'] == 'good' ? 'selected' : '' ?>>สภาพดี</option>
                                <option value="new" <?= $post['expected_condition'] == 'new' ? 'selected' : '' ?>>ของใหม่</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="wtb-label">งบประมาณที่มี (บาท)</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-muted);">฿</span>
                            <input type="number" name="budget" class="wtb-input" value="<?= (float)$post['budget'] ?>" style="padding-left: 45px;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-2 mt-2">
                <label class="wtb-label">รายละเอียดเพิ่มเติม</label>
                <textarea name="description" class="wtb-input" rows="4"><?= htmlspecialchars($post['description']) ?></textarea>
            </div>

            <button type="submit" class="btn-submit-wtb">
                <i class="fas fa-save"></i> บันทึกการแก้ไขข้อมูล
            </button>
            <div class="text-center mt-3">
                <a href="wtb_board.php" class="text-muted fw-bold" style="text-decoration:none;">ยกเลิกและกลับไปที่กระดาน</a>
            </div>
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