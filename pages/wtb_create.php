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
        $uploadedFile = uploadImage($_FILES['ref_image']); // ใช้ฟังก์ชันอัปโหลดเดิมของระบบพี่
        if ($uploadedFile) {
            $image_url = $uploadedFile;
        }
    }

    if (empty($title)) {
        $_SESSION['flash_message'] = "กรุณาระบุสิ่งที่ต้องการตามหา";
        $_SESSION['flash_type'] = "danger";
    } else {
        $stmt = $db->prepare("INSERT INTO wtb_posts (user_id, category_id, title, description, image_url, expected_condition, budget) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $category_id, $title, $description, $image_url, $expected_condition, $budget])) {
            $_SESSION['flash_message'] = "โพสต์ตามหาสินค้าสำเร็จ! รอคนทักแชทมาเสนอขายได้เลย";
            $_SESSION['flash_type'] = "success";
            redirect('wtb_board.php'); 
        } else {
            $_SESSION['flash_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            $_SESSION['flash_type'] = "danger";
        }
    }
}
?>

<div class="container mt-5 mb-5" style="max-width: 800px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="width: 70px; height: 70px; background: rgba(99, 102, 241, 0.1); color: #6366f1; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 15px;">
            <i class="fas fa-bullhorn"></i>
        </div>
        <h2 style="font-weight: 900; color: var(--text-main);">โพสต์ตามหาสินค้า (WTB)</h2>
        <p style="color: var(--text-muted);">อยากได้อะไร พิมพ์บอกไว้เลย เดี๋ยวเพื่อนๆ ที่มีของจะทักแชทมาเสนอขายเอง!</p>
    </div>

    <div class="card shadow-sm" style="border-radius: 24px; border: 2px solid var(--border-color); background: var(--bg-card);">
        <div class="card-body p-4 p-md-5">
            <?php echo displayFlashMessage(); ?>
            
            <form action="wtb_create.php" method="POST" enctype="multipart/form-data">
                
                <div class="row">
                    <div class="col-md-5 mb-4">
                        <label style="font-weight: 800; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">รูปภาพอ้างอิง (ถ้ามี)</label>
                        <label for="ref_image" style="display: block; width: 100%; aspect-ratio: 1; border: 2px dashed var(--border-color); border-radius: 20px; background: var(--bg-main); cursor: pointer; position: relative; overflow: hidden; transition: 0.3s;" id="img_container">
                            <div id="img_placeholder" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: var(--text-muted);">
                                <i class="fas fa-image" style="font-size: 3rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                <p style="font-size: 0.85rem; margin: 0;">คลิกเพื่ออัปโหลดรูป</p>
                            </div>
                            <img id="img_preview" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                        </label>
                        <input type="file" name="ref_image" id="ref_image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    </div>

                    <div class="col-md-7">
                        <div class="mb-3">
                            <label style="font-weight: 800; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">สิ่งที่ต้องการตามหา <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="เช่น หนังสือบัญชีปี 1, เสื้อช็อปไซส์ L" required style="border-radius: 14px; padding: 12px 15px; border: 2px solid var(--border-color); background: var(--bg-main);">
                        </div>

                        <div class="mb-3">
                            <label style="font-weight: 800; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">หมวดหมู่</label>
                            <select name="category_id" class="form-select" style="border-radius: 14px; padding: 12px 15px; border: 2px solid var(--border-color); background: var(--bg-main);" required>
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label style="font-weight: 800; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">สภาพที่ต้องการ</label>
                            <select name="expected_condition" class="form-select" style="border-radius: 14px; padding: 12px 15px; border: 2px solid var(--border-color); background: var(--bg-main);">
                                <option value="any">รับทุกสภาพ (ขอแค่ใช้งานได้)</option>
                                <option value="good">มือสอง สภาพดี (80%+)</option>
                                <option value="new">มือหนึ่งเท่านั้น</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label style="font-weight: 800; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">รายละเอียดเพิ่มเติม</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="เช่น ขอรุ่นพิมพ์ปี 2566, ไม่มีรอยไฮไลท์..." style="border-radius: 14px; padding: 15px; border: 2px solid var(--border-color); background: var(--bg-main);"></textarea>
                </div>

                <div class="mb-4">
                    <label style="font-weight: 800; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">งบประมาณที่มี (บาท) <span style="font-weight:normal; text-transform:none;">- ไม่บังคับ</span></label>
                    <input type="number" name="budget" class="form-control" placeholder="เช่น 250" style="border-radius: 14px; padding: 12px 15px; border: 2px solid var(--border-color); background: var(--bg-main);">
                </div>

                <button type="submit" class="btn w-100" style="background: #6366f1; color: white; border-radius: 16px; padding: 16px; font-weight: 800; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3); transition: 0.3s;">
                    <i class="fas fa-paper-plane"></i> โพสต์ตามหาของเลย
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function previewImage(input) {
        const preview = document.getElementById('img_preview');
        const placeholder = document.getElementById('img_placeholder');
        const container = document.getElementById('img_container');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                container.style.borderColor = '#6366f1';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>