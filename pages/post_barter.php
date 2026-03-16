<?php
/**
 * ============================================================================================
 * 🔄 BNCC MARKETPLACE - HYBRID BARTER POST ENGINE (V 7.0.0)
 * ============================================================================================
 * Logic: Choice between "Open Offer" or "Specific Request"
 * Status: Always starts as 'pending' for Admin Approval
 * UI: High-Fidelity Titan Design with Drag & Drop
 * ============================================================================================
 */
require_once '../includes/functions.php';

// บังคับล็อกอิน
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนลงประกาศแลกเปลี่ยน";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

$db = getDB();
$pageTitle = "สร้างประกาศแลกเปลี่ยน | BNCC Barter";
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_have = trim($_POST['item_have']);
    $description = trim($_POST['description']);
    $exchange_type = $_POST['exchange_type'] ?? 'any'; // รับค่าประเภทการแลก
    
    // 🎯 Logic เลือกประเภทของที่อยากได้
    if ($exchange_type === 'any') {
        $item_want = "เปิดรับทุกข้อเสนอ (Open for Offers)";
    } else {
        $item_want = trim($_POST['item_want_specific']);
    }

    $title = "มี " . $item_have . " อยากแลกกับ " . ($exchange_type === 'any' ? "อะไรก็ได้" : $item_want);
    $status = 'pending'; 
    
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = uploadImage($_FILES['image'], "../assets/images/barter/");
    }

    try {
       // 🎯 เอาบรรทัดที่ซ้ำกันออก ให้เหลือแค่อันนี้อันเดียวพอครับ (มี ? 7 ตัว)
$stmt = $db->prepare("INSERT INTO barter_posts (user_id, title, item_have, item_want, description, image_url, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

// 🎯 ส่งข้อมูลไป 7 ตัว ให้ตรงกับ ? ด้านบนเป๊ะๆ
if ($stmt->execute([
    $_SESSION['user_id'], 
    $title, 
    $item_have, 
    $item_want, 
    $description, 
    $image,  // ตรงนี้เช็กชื่อตัวแปรให้ดีนะครับว่าพี่ใช้ $image หรือ $image_url
    $status
])) {

            // 🔔 แจ้งเตือนแอดมิน/ครู
            $adminStmt = $db->query("SELECT id FROM users WHERE role IN ('admin', 'teacher')");
            $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $adm_id) {
                if (function_exists('sendNotification')) {
                    sendNotification($adm_id, 'system', "มีรายการ Barter ใหม่รออนุมัติ: " . $item_have, "../admin/approve_barter.php");
                }
            }

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
       [CSS CORE] TITAN HYBRID FORM UI
       ========================================================================== */
    .titan-form-wrapper {
        max-width: 900px; 
        margin: 40px auto; 
        padding: 0 20px; 
        animation: formRevealUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    
    @keyframes formRevealUp { 
        from { opacity: 0; transform: translateY(40px) scale(0.98); } 
        to { opacity: 1; transform: translateY(0); } 
    }
    
    .titan-form-card {
        background: var(--theme-surface); 
        border: 2px solid var(--theme-border);
        border-radius: 35px; 
        padding: 40px; 
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.08);
    }
    
    .form-header-group {
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 30px;
        text-align: left;
    }

    /* Approval Banner */
    .approval-warning-banner {
        background: rgba(245, 158, 11, 0.1); 
        border: 2px solid rgba(245, 158, 11, 0.3); 
        padding: 20px; 
        border-radius: 20px; 
        margin-bottom: 35px; 
        display: flex; 
        gap: 15px; 
        align-items: center;
    }
    .approval-warning-banner i { font-size: 1.8rem; color: #f59e0b; }

    /* Input Modules */
    .input-module { margin-bottom: 25px; position: relative; }
    .input-label { 
        display: block; font-size: 0.85rem; font-weight: 900; 
        text-transform: uppercase; color: var(--theme-text-secondary); 
        margin-bottom: 10px; letter-spacing: 0.5px; 
    }
    
    .titan-input {
        width: 100%; padding: 18px 22px; border-radius: 18px; 
        border: 2px solid var(--theme-border); background: var(--theme-bg); 
        color: var(--theme-text-primary); font-family: inherit; 
        font-size: 1.05rem; font-weight: 600; transition: 0.3s;
    }
    .titan-input:focus { 
        outline: none; border-color: #4f46e5; background: var(--theme-surface); 
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); transform: translateY(-2px); 
    }

    /* 🎯 Exchange Selector */
    .exchange-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    .select-option {
        border: 2px solid var(--theme-border);
        padding: 15px;
        border-radius: 15px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        transition: 0.3s;
    }
    .select-option:hover { border-color: #4f46e5; background: rgba(79, 70, 229, 0.05); }
    .select-option input[type="radio"] { width: 20px; height: 20px; accent-color: #4f46e5; }
    .select-option.active { border-color: #4f46e5; background: rgba(79, 70, 229, 0.1); color: #4f46e5; }

    /* 📸 Upload Reactor */
    .upload-reactor {
        border: 3px dashed var(--theme-border); border-radius: 24px; padding: 40px;
        text-align: center; cursor: pointer; transition: 0.4s; background: var(--theme-bg);
        position: relative; overflow: hidden; display: flex; flex-direction: column; 
        align-items: center; justify-content: center; min-height: 200px;
    }
    .upload-reactor:hover, .upload-reactor.is-dragover { border-color: #4f46e5; background: rgba(79, 70, 229, 0.05); }
    .reactor-icon { font-size: 3rem; color: #4f46e5; margin-bottom: 15px; }
    
    #visual-preview-layer { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; display: none; z-index: 5; background: var(--theme-bg); }
    .btn-nuke-preview {
        position: absolute; top: 15px; right: 15px; background: #ef4444; color: white;
        width: 35px; height: 35px; border-radius: 50%; display: none; align-items: center; 
        justify-content: center; border: none; cursor: pointer; z-index: 10; 
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .btn-submit-master {
        width: 100%; padding: 22px; border-radius: 20px; border: none;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;
        font-weight: 800; font-size: 1.2rem; cursor: pointer; transition: 0.3s; 
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        display: flex; justify-content: center; align-items: center; gap: 12px; margin-top: 20px;
    }
    .btn-submit-master:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(79, 70, 229, 0.5); }

    .req-star { color: #ef4444; margin-left: 3px; }
</style>

<div class="titan-form-wrapper">
    <div class="titan-form-card">
        
        <div class="form-header-group">
            <div>
                <h1 style="font-weight: 900; color: var(--theme-text-primary); font-size: 2.2rem; letter-spacing: -1px; margin: 0;">สร้างประกาศแลกเปลี่ยน</h1>
                <p style="color: var(--theme-text-secondary); font-weight: 600; margin-top: 5px;">ลงของที่มี แล้วรอรับข้อเสนอเจ๋งๆ จากเพื่อนใน BNCC</p>
            </div>
            <i class="fas fa-sync-alt fa-3x" style="color: var(--theme-border); opacity: 0.5;"></i>
        </div>

        <div class="approval-warning-banner">
            <i class="fas fa-user-shield"></i>
            <div>
                <strong style="color: #d97706; font-size: 1rem; display: block;">ระบบมีการตรวจสอบข้อมูล</strong>
                <span style="font-size: 0.9rem;">ประกาศจะถูกส่งไปให้ Admin/ครู ตรวจสอบก่อนขึ้นกระดานเพื่อความปลอดภัยครับ</span>
            </div>
        </div>

        <?php echo displayFlashMessage(); ?>

        <form method="POST" enctype="multipart/form-data" id="masterBarterForm">
            
            <div class="row gx-4">
                <div class="col-md-5 mb-4">
                    <label class="input-label">รูปภาพสินค้า <span class="req-star">*</span></label>
                    <div class="upload-reactor" id="imageDropReactor">
                        <div id="reactor-placeholder">
                            <i class="fas fa-camera reactor-icon"></i>
                            <p style="font-weight: 700; font-size: 0.9rem;">คลิก หรือ ลากรูปมาวาง</p>
                        </div>
                        <input type="file" name="image" id="hiddenFileInput" accept="image/*" style="display: none;" required>
                        <img id="visual-preview-layer" src="">
                        <button type="button" class="btn-nuke-preview" id="btnNukePreview"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="input-module">
                        <label class="input-label">สิ่งที่พี่มี (I HAVE) <span class="req-star">*</span></label>
                        <input type="text" name="item_have" class="titan-input" placeholder="ระบุของที่พี่มี..." required>
                    </div>

                    <div class="input-module">
                        <label class="input-label">ความต้องการแลกเปลี่ยน <span class="req-star">*</span></label>
                        <div class="exchange-selector">
                            <label class="select-option active" id="label_any">
                                <input type="radio" name="exchange_type" value="any" checked onchange="toggleExchange(this)">
                                อะไรก็ได้
                            </label>
                            <label class="select-option" id="label_specific">
                                <input type="radio" name="exchange_type" value="specific" onchange="toggleExchange(this)">
                                ระบุของที่อยากได้
                            </label>
                        </div>

                        <div id="specific_input_wrap" style="display: none; animation: fadeIn 0.3s ease;">
                            <input type="text" name="item_want_specific" id="item_want_specific" class="titan-input" placeholder="พี่อยากแลกกับอะไร...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-module">
                <label class="input-label">รายละเอียด & สภาพสินค้า <span class="req-star">*</span></label>
                <textarea name="description" class="titan-input" style="min-height: 120px; resize: vertical;" placeholder="อธิบายสภาพ ตำหนิ หรือสิ่งที่แอดมินควรรู้..." required></textarea>
            </div>

            <button type="submit" class="btn-submit-master" id="btnSubmitForm">
                <i class="fas fa-paper-plane"></i> ส่งประกาศเพื่อรออนุมัติ
            </button>
        </form>
    </div>
</div>

<script>
/**
 * FORM LOGIC ENGINE
 */
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
    const dropZone = document.getElementById('imageDropReactor');
    const fileInput = document.getElementById('hiddenFileInput');
    const previewImg = document.getElementById('visual-preview-layer');
    const removeBtn = document.getElementById('btnNukePreview');
    const placeholder = document.getElementById('reactor-placeholder');

    dropZone.addEventListener('click', (e) => {
        if(e.target !== removeBtn && !removeBtn.contains(e.target)) fileInput.click();
    });

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); });
    });

    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, () => dropZone.classList.add('is-dragover'));
    });

    ['dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, () => dropZone.classList.remove('is-dragover'));
    });

    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if(files.length > 0) {
            fileInput.files = files;
            processFile(files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) processFile(this.files[0]);
    });

    function processFile(file) {
        if (!file.type.startsWith('image/')) {
            alert('รูปเท่านั้นพี่!');
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
            removeBtn.style.display = 'flex';
            placeholder.style.display = 'none';
            dropZone.style.borderStyle = 'solid';
            dropZone.style.borderColor = '#4f46e5';
        }
        reader.readAsDataURL(file);
    }

    removeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileInput.value = '';
        previewImg.src = '';
        previewImg.style.display = 'none';
        removeBtn.style.display = 'none';
        placeholder.style.display = 'block';
        dropZone.style.borderStyle = 'dashed';
        dropZone.style.borderColor = 'var(--theme-border)';
    });
    
    document.getElementById('masterBarterForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmitForm');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> กำลังส่งข้อมูล...';
        btn.style.opacity = '0.8';
        btn.style.pointerEvents = 'none';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>