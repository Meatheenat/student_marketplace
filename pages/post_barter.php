<?php
/**
 * ============================================================================================
 * 🔄 BNCC MARKETPLACE - OPEN BARTER POST ENGINE (V 6.0.0 - APPROVAL MODE)
 * ============================================================================================
 * Logic: User submits -> Status='pending' -> Admin approves -> Status='open'
 * Design: High-Fidelity Form UI with Drag & Drop Image Upload
 * ============================================================================================
 */
require_once '../includes/functions.php';

// บังคับว่าต้องเป็นสมาชิกถึงจะลงประกาศได้ (ปรับ Role ตามระบบพี่ได้เลย)
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนลงประกาศแลกเปลี่ยน";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

$pageTitle = "สร้างประกาศแลกเปลี่ยน | BNCC Barter";
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $item_have = trim($_POST['item_have']);
    $description = trim($_POST['description']);
    
    // 🎯 Logic ใหม่อยู่ตรงนี้: ล็อคค่า item_want ไปเลย
    $item_want = "เปิดรับทุกข้อเสนอ (Open for Offers)"; 
    $title = "มี " . $item_have . " หาแลกของครับ/ค่ะ"; 
    
    // 🎯 กำหนดสถานะเป็น 'pending' (รออนุมัติ)
    $status = 'pending'; 
    
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = uploadImage($_FILES['image'], "../assets/images/barter/");
    }

    try {
        // อัปเดตคำสั่ง SQL ให้รับค่า status ด้วย
        $stmt = $db->prepare("INSERT INTO barter_posts (user_id, title, item_have, item_want, description, image_url, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        if ($stmt->execute([$_SESSION['user_id'], $title, $item_have, $item_want, $description, $image, $status])) {
            
            // 🔔 (Optional) ส่งแจ้งเตือนหา Admin ในระบบว่ามีประกาศใหม่รอตรวจ
            // แจ้งเตือน User ว่าส่งสำเร็จแล้วรอตรวจ
            $_SESSION['flash_message'] = "ส่งประกาศเรียบร้อย! กรุณารอผู้ดูแลระบบตรวจสอบและอนุมัติก่อนแสดงผลบนกระดาน";
            $_SESSION['flash_type'] = "success";
            redirect('barter_board.php');
        }
    } catch(PDOException $e) {
        error_log("Barter Post Error: " . $e->getMessage());
        $_SESSION['flash_message'] = "เกิดข้อผิดพลาดทางระบบ กรุณาลองใหม่อีกครั้ง";
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<style>
    /* ==========================================================================
       [CSS CORE] FORM ENTERPRISE UX
       ========================================================================== */
    .titan-form-wrapper {
        max-width: 850px; 
        margin: 50px auto; 
        padding: 0 20px; 
        animation: formRevealUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    
    @keyframes formRevealUp { 
        from { opacity: 0; transform: translateY(40px) scale(0.98); } 
        to { opacity: 1; transform: translateY(0) scale(1); } 
    }
    
    .titan-form-card {
        background: var(--theme-surface); 
        border: 2px solid var(--theme-border);
        border-radius: 35px; 
        padding: 50px; 
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.08);
    }
    
    .form-header-group {
        display: flex; 
        justify-content: space-between; 
        align-items: flex-start; 
        margin-bottom: 30px;
    }

    /* 🛡️ Warning Banner for Approval Process */
    .approval-warning-banner {
        background: rgba(245, 158, 11, 0.1); 
        border: 2px solid rgba(245, 158, 11, 0.3); 
        padding: 20px 25px; 
        border-radius: 20px; 
        margin-bottom: 40px; 
        display: flex; 
        gap: 20px; 
        align-items: flex-start;
    }
    
    .approval-warning-banner i { font-size: 2rem; color: #f59e0b; margin-top: 5px; }

    /* Input Styling */
    .input-module { margin-bottom: 30px; position: relative; }
    .input-label { 
        display: block; font-size: 0.85rem; font-weight: 900; 
        text-transform: uppercase; color: var(--theme-text-secondary); 
        margin-bottom: 12px; letter-spacing: 1px; 
    }
    
    .titan-input {
        width: 100%; padding: 20px 25px; border-radius: 20px; 
        border: 2px solid var(--theme-border); background: var(--theme-bg); 
        color: var(--theme-text-primary); font-family: inherit; 
        font-size: 1.1rem; font-weight: 600; transition: 0.3s;
    }
    .titan-input:focus { 
        outline: none; border-color: #4f46e5; background: var(--theme-surface); 
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15); transform: translateY(-2px); 
    }

    /* 📸 Drag & Drop Reactor */
    .upload-reactor {
        border: 3px dashed var(--theme-border); border-radius: 24px; padding: 50px 30px;
        text-align: center; cursor: pointer; transition: 0.4s; background: var(--theme-bg);
        position: relative; overflow: hidden; display: flex; flex-direction: column; 
        align-items: center; justify-content: center; min-height: 250px;
    }
    .upload-reactor:hover, .upload-reactor.is-dragover { 
        border-color: #4f46e5; background: rgba(79, 70, 229, 0.05); transform: scale(1.01); 
    }
    .reactor-icon { font-size: 4rem; color: #4f46e5; margin-bottom: 20px; transition: transform 0.3s; }
    .upload-reactor:hover .reactor-icon { transform: translateY(-10px); }
    
    #visual-preview-layer { 
        position: absolute; inset: 0; width: 100%; height: 100%; 
        object-fit: cover; display: none; z-index: 5; 
    }
    
    .btn-nuke-preview {
        position: absolute; top: 20px; right: 20px; background: #ef4444; color: white;
        width: 45px; height: 45px; border-radius: 50%; display: none; align-items: center; 
        justify-content: center; border: none; cursor: pointer; z-index: 10; 
        box-shadow: 0 10px 20px rgba(239, 68, 68, 0.4); font-size: 1.2rem; transition: 0.3s;
    }
    .btn-nuke-preview:hover { transform: scale(1.1) rotate(90deg); background: #dc2626; }

    /* Submit Button */
    .btn-submit-master {
        width: 100%; padding: 25px; border-radius: 20px; border: none;
        background: linear-gradient(135deg, #4f46e5, #4338ca); color: white;
        font-weight: 900; font-size: 1.3rem; cursor: pointer; transition: 0.4s; 
        box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4);
        display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 40px;
    }
    .btn-submit-master:hover { 
        transform: translateY(-5px); box-shadow: 0 25px 50px rgba(79, 70, 229, 0.5); letter-spacing: 1px; 
    }

    @media (max-width: 768px) {
        .titan-form-card { padding: 30px 20px; }
        .form-header-group h1 { font-size: 2.2rem !important; }
    }
</style>

<div class="titan-form-wrapper">
    <div class="titan-form-card">
        
        <div class="form-header-group">
            <div>
                <h1 style="font-size: 2.8rem; font-weight: 900; margin-bottom: 5px; line-height: 1.1; color: var(--theme-text-primary);">
                    ลงประกาศ <span style="color:#4f46e5;">หาของแลก</span>
                </h1>
                <p style="color: var(--theme-text-secondary); font-size: 1.1rem; font-weight: 600;">
                    เปลี่ยนของเก่าเก็บ ให้กลายเป็นของใหม่ที่ถูกใจ
                </p>
            </div>
            <div style="font-size: 3.5rem; color: var(--theme-border); opacity: 0.6;">
                <i class="fas fa-hand-holding-box"></i>
            </div>
        </div>

        <div class="approval-warning-banner">
            <i class="fas fa-user-shield"></i>
            <div>
                <strong style="color: #d97706; font-size: 1.1rem; display: block; margin-bottom: 5px;">
                    ประกาศของคุณจะต้องผ่านการตรวจสอบ
                </strong>
                <span style="font-size: 0.95rem; color: var(--theme-text-primary); line-height: 1.6;">
                    เพื่อความปลอดภัยของส่วนรวม เมื่อกดส่งประกาศแล้ว <b>ระบบจะส่งข้อมูลไปให้ผู้ดูแลระบบ (Admin) ตรวจสอบความเหมาะสมก่อน</b> หากผ่านการอนุมัติ ประกาศของคุณจะแสดงบนกระดานแลกเปลี่ยนทันทีครับ
                </span>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="masterBarterForm">
            
            <div class="input-module">
                <label class="input-label">
                    <i class="fas fa-cube" style="color:#4f46e5; margin-right:5px;"></i> สิ่งที่คุณมีมาเสนอ (I HAVE)
                </label>
                <input type="text" name="item_have" class="titan-input" placeholder="เช่น คีย์บอร์ด Mechanical สีขาวสภาพ 90%, แผ่นเกม PS5..." required>
                <div style="margin-top: 10px; font-size: 0.85rem; color: var(--theme-text-tertiary); font-weight: 600;">
                    <i class="fas fa-magic" style="color: #10b981;"></i> ระบบจะตั้งค่า "สิ่งที่อยากได้" เป็น "เปิดรับทุกข้อเสนอ" ให้อัตโนมัติ
                </div>
            </div>

            <div class="input-module">
                <label class="input-label">
                    <i class="fas fa-align-left" style="color:#4f46e5; margin-right:5px;"></i> รายละเอียด & สภาพสินค้า (Description)
                </label>
                <textarea name="description" class="titan-input" style="min-height: 180px; resize: vertical;" placeholder="อธิบายสเปกสินค้า อายุการใช้งาน ตำหนิ (ถ้ามี) หรือจะแอบบอกใบ้หน่อยว่าลึกๆ แล้วอยากได้อะไรแนวไหนมาแลก..." required></textarea>
            </div>

            <div class="input-module">
                <label class="input-label">
                    <i class="fas fa-camera-retro" style="color:#4f46e5; margin-right:5px;"></i> รูปภาพสินค้า (อัปโหลดเพื่อประกอบการตัดสินใจ)
                </label>
                
                <div class="upload-reactor" id="imageDropReactor">
                    <i class="fas fa-cloud-upload-alt reactor-icon"></i>
                    <h3 style="font-weight: 900; font-size: 1.4rem; margin-bottom: 10px; color: var(--theme-text-primary);">
                        คลิกเพื่อเลือก หรือลากรูปมาวางที่นี่
                    </h3>
                    <p style="font-size: 1rem; font-weight: 600; color: var(--theme-text-tertiary);">
                        รองรับไฟล์ JPG, PNG, WEBP (ขนาดไม่เกิน 5MB)
                    </p>
                    
                    <input type="file" name="image" id="hiddenFileInput" accept="image/jpeg, image/png, image/webp" style="display: none;" required>
                    
                    <img id="visual-preview-layer" src="" alt="Preview">
                    <button type="button" class="btn-nuke-preview" id="btnNukePreview" title="ยกเลิกลบรูปภาพนี้">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit-master" id="btnSubmitForm">
                <i class="fas fa-paper-plane"></i> ส่งประกาศให้ผู้ดูแลระบบตรวจสอบ
            </button>
        </form>
    </div>
</div>

<script>
/**
 * ============================================================================================
 * [JAVASCRIPT] FORM INTERACTION ENGINE
 * ============================================================================================
 */
document.addEventListener('DOMContentLoaded', () => {
    
    // ---------------------------------------------------------
    // MODULE: DRAG & DROP UPLOAD SYSTEM
    // ---------------------------------------------------------
    const dropZone = document.getElementById('imageDropReactor');
    const fileInput = document.getElementById('hiddenFileInput');
    const previewImg = document.getElementById('visual-preview-layer');
    const removeBtn = document.getElementById('btnNukePreview');

    // Trigger file input on click (ignore if clicking remove button)
    dropZone.addEventListener('click', (e) => {
        if(e.target !== removeBtn && !removeBtn.contains(e.target)) {
            fileInput.click();
        }
    });

    // Prevent default browser behaviors for drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) { 
        e.preventDefault(); 
        e.stopPropagation(); 
    }

    // Highlight drop zone when dragging over
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('is-dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('is-dragover'), false);
    });

    // Handle dropped files
    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if(files.length > 0) {
            fileInput.files = files; // Sync UI files to hidden input
            processFile(files[0]);
        }
    });

    // Handle traditional file selection
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            processFile(this.files[0]);
        }
    });

    // File processing and preview generation
    function processFile(file) {
        if (!file.type.startsWith('image/')) {
            alert('กรุณาอัปโหลดเฉพาะไฟล์รูปภาพ (JPG, PNG) เท่านั้นครับ');
            fileInput.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
            removeBtn.style.display = 'flex';
            
            // Add subtle entrance animation to image
            previewImg.style.animation = 'formRevealUp 0.4s ease-out';
        }
        reader.readAsDataURL(file);
    }

    // Handle image removal
    removeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        fileInput.value = ''; // Clear input
        previewImg.src = '';
        previewImg.style.display = 'none';
        removeBtn.style.display = 'none';
    });
    
    // ---------------------------------------------------------
    // MODULE: FORM SUBMISSION FEEDBACK
    // ---------------------------------------------------------
    document.getElementById('masterBarterForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmitForm');
        
        // Change button state to loading
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> กำลังส่งข้อมูลไปยังระบบ...';
        btn.style.opacity = '0.8';
        btn.style.pointerEvents = 'none'; // Prevent double clicking
        btn.style.transform = 'scale(0.98)';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>