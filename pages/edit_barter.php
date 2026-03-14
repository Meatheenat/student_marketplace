<?php
/**
 * ============================================================================================
 * 🔄 BNCC MARKETPLACE - EDIT BARTER POST (TITAN UI) - RE-APPROVAL VERSION
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

// 4. จัดการเมื่อมีการ Submit ฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_have = trim($_POST['item_have']);
    $description = trim($_POST['description']);
    $title = "มี " . $item_have . " หาแลกของครับ/ค่ะ"; 
    
    // 🎯 Logic หัวใจหลัก: แก้เสร็จแล้วต้องส่งกลับไปรออนุมัติ (pending) ใหม่
    $status = 'pending'; 
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
        // 🎯 อัปเดตข้อมูลและถอยสถานะกลับไปเป็น 'pending' เพื่อให้แอดมินตรวจใหม่
        $update_stmt = $db->prepare("UPDATE barter_posts SET title = ?, item_have = ?, description = ?, image_url = ?, status = ?, updated_at = NOW() WHERE id = ?");
        
        if ($update_stmt->execute([$title, $item_have, $description, $image_url, $status, $post_id])) {
            $_SESSION['flash_message'] = "✅ บันทึกการแก้ไขแล้ว! ประกาศของคุณกำลังรอแอดมินตรวจสอบความถูกต้องอีกครั้งครับ";
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
    .titan-edit-wrapper {
        max-width: 850px; 
        margin: 50px auto; 
        padding: 0 20px; 
        animation: editFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    
    @keyframes editFadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    
    .titan-form-card {
        background: var(--theme-surface); 
        border: 2px solid var(--theme-border);
        border-radius: 35px; padding: 50px; 
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1);
    }

    /* 🛡️ บล็อกแจ้งเตือนเรื่องการตรวจสอบใหม่ */
    .re-approval-notice {
        background: rgba(79, 70, 229, 0.1);
        border-left: 5px solid #4f46e5;
        padding: 15px 20px;
        margin-bottom: 30px;
        border-radius: 10px;
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .input-module { margin-bottom: 30px; }
    .input-label { display: block; font-size: 0.85rem; font-weight: 900; text-transform: uppercase; color: var(--theme-text-secondary); margin-bottom: 12px; letter-spacing: 1px; }
    
    .titan-input {
        width: 100%; padding: 20px 25px; border-radius: 20px; 
        border: 2px solid var(--theme-border); background: var(--theme-bg); 
        color: var(--theme-text-primary); font-family: inherit; font-size: 1.1rem; font-weight: 600; transition: 0.3s;
    }
    .titan-input:focus { outline: none; border-color: #4f46e5; background: var(--theme-surface); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15); }

    .edit-upload-reactor {
        border: 3px dashed var(--theme-border); border-radius: 24px; min-height: 280px;
        text-align: center; cursor: pointer; background: var(--theme-bg);
        position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;
    }
    
    #edit-preview-layer { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 5; }
    
    .btn-save-titan {
        width: 100%; padding: 22px; border-radius: 20px; border: none;
        background: linear-gradient(135deg, #4f46e5, #4338ca); color: white;
        font-weight: 900; font-size: 1.25rem; cursor: pointer; transition: 0.4s; box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4);
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .btn-save-titan:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(79, 70, 229, 0.5); }
    .btn-cancel { background: var(--theme-border); color: var(--theme-text-primary); box-shadow: none; }
    .btn-cancel:hover { background: #cbd5e1; }
</style>

<div class="titan-edit-wrapper">
    <div class="titan-form-card">
        <div style="margin-bottom: 30px;">
            <h1 style="font-size: 2.5rem; font-weight: 900;">แก้ไข <span style="color:#4f46e5;">ประกาศแลกเปลี่ยน</span></h1>
            <p style="color: var(--theme-text-secondary); font-weight: 600;">ปรับปรุงข้อมูลประกาศของคุณ</p>
        </div>

        <div class="re-approval-notice">
            <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #4f46e5;"></i>
            <span style="font-size: 0.95rem; font-weight: 700; color: var(--theme-text-primary);">
                หมายเหตุ: หลังจากกดบันทึก ประกาศจะถูกส่งไปให้ผู้ดูแลระบบตรวจสอบและอนุมัติใหม่อีกรอบครับ
            </span>
        </div>

        <form method="POST" enctype="multipart/form-data" id="editBarterForm">
            <div class="input-module">
                <label class="input-label"><i class="fas fa-box-open" style="margin-right:8px;"></i> ของที่พี่มี (I HAVE)</label>
                <input type="text" name="item_have" class="titan-input" value="<?= e($post['item_have']) ?>" required>
            </div>

            <div class="input-module">
                <label class="input-label"><i class="fas fa-align-left" style="margin-right:8px;"></i> รายละเอียดสินค้า</label>
                <textarea name="description" class="titan-input" style="min-height: 150px; resize: vertical;" required><?= e($post['description']) ?></textarea>
            </div>

            <div class="input-module">
                <label class="input-label"><i class="fas fa-camera" style="margin-right:8px;"></i> รูปภาพสินค้า (คลิกเพื่อเปลี่ยนรูปใหม่)</label>
                <div class="edit-upload-reactor" id="dropZone">
                    <?php if(!empty($post['image_url'])): ?>
                        <img id="edit-preview-layer" src="../assets/images/barter/<?= e($post['image_url']) ?>">
                    <?php else: ?>
                        <img id="edit-preview-layer" style="display:none;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #4f46e5;"></i>
                    <?php endif; ?>
                    <input type="file" name="image" id="fileInput" accept="image/*" style="display: none;">
                </div>
            </div>

            <div style="display: flex; gap: 15px;">
                <a href="barter_board.php" class="btn-save-titan btn-cancel">ยกเลิก</a>
                <button type="submit" class="btn-save-titan" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> บันทึกและส่งอนุมัติ
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const previewImg = document.getElementById('edit-preview-layer');

    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
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