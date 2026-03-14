<?php
/**
 * ============================================================================================
 * 🔄 BNCC MARKETPLACE - EDIT BARTER POST (TITAN HYBRID UI)
 * ============================================================================================
 */
require_once '../includes/functions.php';

// 1. ตรวจสอบการล็อกอิน
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนแก้ไขประกาศ";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. ดึงข้อมูลประกาศเดิม
$stmt = $db->prepare("SELECT * FROM barter_posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

// 3. ตรวจสอบสิทธิ์ (ต้องเป็นเจ้าของ หรือ Admin/Teacher)
if (!$post || ($post['user_id'] != $user_id && !in_array($_SESSION['role'], ['admin', 'teacher']))) {
    $_SESSION['flash_message'] = "❌ คุณไม่มีสิทธิ์แก้ไขประกาศนี้";
    $_SESSION['flash_type'] = "danger";
    redirect('barter_board.php');
}

// 🎯 Logic ตรวจสอบประเภทการแลกเปลี่ยนเดิมจากฐานข้อมูล
$is_open_offer = ($post['item_want'] === "เปิดรับทุกข้อเสนอ (Open for Offers)");

// 4. จัดการเมื่อมีการ Submit ฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_have = trim($_POST['item_have']);
    $description = trim($_POST['description']);
    $exchange_type = $_POST['exchange_type'] ?? 'any'; 
    
    // 🎯 Logic เลือกประเภทของที่อยากได้ (Hybrid Mode)
    if ($exchange_type === 'any') {
        $item_want = "เปิดรับทุกข้อเสนอ (Open for Offers)";
    } else {
        $item_want = trim($_POST['item_want_specific']);
    }

    $title = "มี " . $item_have . " อยากแลกกับ " . ($exchange_type === 'any' ? "อะไรก็ได้" : $item_want);
    $status = 'pending'; // แก้ไขแล้วต้องส่งกลับไปให้ Admin ตรวจใหม่
    
    $image_url = $post['image_url']; 

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $new_image = uploadImage($_FILES['image'], "../assets/images/barter/");
        if ($new_image) {
            $image_url = $new_image;
            if (!empty($post['image_url']) && file_exists("../assets/images/barter/" . $post['image_url'])) {
                @unlink("../assets/images/barter/" . $post['image_url']);
            }
        }
    }

    try {
        $update_stmt = $db->prepare("UPDATE barter_posts SET title = ?, item_have = ?, item_want = ?, description = ?, image_url = ?, status = ?, updated_at = NOW() WHERE id = ?");
        
        if ($update_stmt->execute([$title, $item_have, $item_want, $description, $image_url, $status, $post_id])) {
            $_SESSION['flash_message'] = "✅ บันทึกการแก้ไขแล้ว! กำลังรอแอดมินตรวจสอบใหม่อีกครั้งครับ";
            $_SESSION['flash_type'] = "success";
            redirect('barter_board.php');
        }
    } catch(PDOException $e) {
        error_log("Barter Edit Error: " . $e->getMessage());
        $_SESSION['flash_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        $_SESSION['flash_type'] = "danger";
    }
}

$pageTitle = "แก้ไขประกาศแลกเปลี่ยน | BNCC Barter";
require_once '../includes/header.php';
?>

<style>
    /* 🎨 โคลนนิ่ง Titan UI จากหน้า post_barter */
    .titan-form-wrapper {
        max-width: 900px; margin: 40px auto; padding: 0 20px; 
        animation: formRevealUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes formRevealUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    
    .titan-form-card {
        background: var(--theme-surface); border: 2px solid var(--theme-border);
        border-radius: 35px; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.08);
    }
    
    .re-approval-notice {
        background: rgba(79, 70, 229, 0.1); border-left: 5px solid #4f46e5;
        padding: 15px 20px; margin-bottom: 30px; border-radius: 10px; display: flex; gap: 15px; align-items: center;
    }

    .input-module { margin-bottom: 25px; position: relative; }
    .input-label { display: block; font-size: 0.85rem; font-weight: 900; text-transform: uppercase; color: var(--theme-text-secondary); margin-bottom: 10px; letter-spacing: 0.5px; }
    
    .titan-input {
        width: 100%; padding: 18px 22px; border-radius: 18px; 
        border: 2px solid var(--theme-border); background: var(--theme-bg); 
        color: var(--theme-text-primary); font-family: inherit; font-size: 1.05rem; font-weight: 600; transition: 0.3s;
    }
    .titan-input:focus { outline: none; border-color: #4f46e5; background: var(--theme-surface); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }

    .exchange-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
    .select-option {
        border: 2px solid var(--theme-border); padding: 15px; border-radius: 15px;
        cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: 700; transition: 0.3s;
    }
    .select-option.active { border-color: #4f46e5; background: rgba(79, 70, 229, 0.1); color: #4f46e5; }
    .select-option input[type="radio"] { width: 20px; height: 20px; accent-color: #4f46e5; }

    .upload-reactor {
        border: 3px dashed var(--theme-border); border-radius: 24px; min-height: 250px;
        text-align: center; cursor: pointer; background: var(--theme-bg);
        position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;
    }
    #edit-preview-layer { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; z-index: 5; background: var(--theme-bg); }

    .btn-submit-master {
        width: 100%; padding: 22px; border-radius: 20px; border: none;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;
        font-weight: 800; font-size: 1.2rem; cursor: pointer; transition: 0.3s; 
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3); display: flex; justify-content: center; align-items: center; gap: 12px;
    }
    .btn-submit-master:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(79, 70, 229, 0.5); }
    .btn-cancel { background: var(--theme-border); color: var(--theme-text-primary); box-shadow: none; }
</style>

<div class="titan-form-wrapper">
    <div class="titan-form-card">
        
        <div style="margin-bottom: 30px;">
            <h1 style="font-weight: 900; color: var(--theme-text-primary); font-size: 2.5rem; letter-spacing: -1px; margin: 0;">แก้ไขประกาศ</h1>
            <p style="color: var(--theme-text-secondary); font-weight: 600; margin-top: 5px;">ปรับปรุงข้อมูลสิ่งของที่พี่ต้องการแลก</p>
        </div>

        <div class="re-approval-notice">
            <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #4f46e5;"></i>
            <span style="font-size: 0.95rem; font-weight: 700; color: var(--theme-text-primary);">
                หมายเหตุ: เมื่อกดบันทึก ประกาศจะถูกส่งไปให้ Admin/ครู ตรวจสอบใหม่อีกครั้งเพื่อความปลอดภัยครับ
            </span>
        </div>

        <form method="POST" enctype="multipart/form-data" id="editBarterForm">
            
            <div class="row gx-4">
                <div class="col-md-5 mb-4">
                    <label class="input-label">รูปภาพสินค้า (คลิกเพื่อเปลี่ยน)</label>
                    <div class="upload-reactor" id="dropZone">
                        <?php if(!empty($post['image_url'])): ?>
                            <img id="edit-preview-layer" src="../assets/images/barter/<?= e($post['image_url']) ?>">
                        <?php else: ?>
                            <i class="fas fa-camera" style="font-size: 3rem; color: #4f46e5;"></i>
                        <?php endif; ?>
                        <input type="file" name="image" id="fileInput" accept="image/*" style="display: none;">
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="input-module">
                        <label class="input-label">สิ่งที่พี่มี (I HAVE)</label>
                        <input type="text" name="item_have" class="titan-input" value="<?= e($post['item_have']) ?>" required>
                    </div>

                    <div class="input-module">
                        <label class="input-label">ความต้องการแลกเปลี่ยนใหม่</label>
                        <div class="exchange-selector">
                            <label class="select-option <?= $is_open_offer ? 'active' : '' ?>" id="label_any">
                                <input type="radio" name="exchange_type" value="any" <?= $is_open_offer ? 'checked' : '' ?> onchange="toggleExchange(this)">
                                อะไรก็ได้
                            </label>
                            <label class="select-option <?= !$is_open_offer ? 'active' : '' ?>" id="label_specific">
                                <input type="radio" name="exchange_type" value="specific" <?= !$is_open_offer ? 'checked' : '' ?> onchange="toggleExchange(this)">
                                ระบุของ
                            </label>
                        </div>

                        <div id="specific_input_wrap" style="display: <?= $is_open_offer ? 'none' : 'block' ?>;">
                            <input type="text" name="item_want_specific" id="item_want_specific" class="titan-input" 
                                   placeholder="พี่อยากแลกกับอะไร..." 
                                   value="<?= !$is_open_offer ? e($post['item_want']) : '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-module">
                <label class="input-label">รายละเอียดเพิ่มเติม</label>
                <textarea name="description" class="titan-input" style="min-height: 120px; resize: vertical;" required><?= e($post['description']) ?></textarea>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <a href="barter_board.php" class="btn-submit-master btn-cancel" style="width: 30%;">ยกเลิก</a>
                <button type="submit" class="btn-submit-master" id="submitBtn" style="width: 70%;">
                    <i class="fas fa-paper-plane"></i> บันทึกและส่งอนุมัติใหม่
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleExchange(el) {
    const specificWrap = document.getElementById('specific_input_wrap');
    const specificInput = document.getElementById('item_want_specific');
    const labelAny = document.getElementById('label_any');
    const labelSpec = document.getElementById('label_specific');

    if (el.value === 'specific') {
        specificWrap.style.display = 'block';
        specificInput.required = true;
        labelSpec.classList.add('active');
        labelAny.classList.remove('active');
    } else {
        specificWrap.style.display = 'none';
        specificInput.required = false;
        labelAny.classList.add('active');
        labelSpec.classList.remove('active');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const previewImg = document.getElementById('edit-preview-layer');

    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                // ถ้าเดิมไม่มีรูป (มีแต่ไอคอน) ให้สร้าง Tag img ใหม่
                if (!previewImg) {
                    dropZone.innerHTML = `<img id="edit-preview-layer" src="${e.target.result}">`;
                } else {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                }
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    document.getElementById('editBarterForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> กำลังบันทึก...';
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>