<?php
$pageTitle = "แก้ไขประกาศตามหา - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../auth/login.php');

$db = getDB();
$post_id = $_GET['id'] ?? 0;

// ดึงข้อมูลเดิมและเช็คว่าเป็นเจ้าของโพสต์จริงไหม
$stmt = $db->prepare("SELECT * FROM wtb_posts WHERE id = ? AND user_id = ?");
$stmt->execute([$post_id, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) {
    $_SESSION['flash_message'] = "ไม่พบประกาศ หรือคุณไม่มีสิทธิ์แก้ไข";
    $_SESSION['flash_type'] = "danger";
    redirect('wtb_board.php');
}

$categories = $db->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category_id = $_POST['category_id'];
    $description = trim($_POST['description']);
    $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
    $expected_condition = $_POST['expected_condition'];
    
    $image_url = $post['image_url']; // ใช้รูปเดิมเป็นค่าเริ่มต้น

    // จัดการรูปใหม่ถ้ามีการอัปโหลด
    if (isset($_FILES['ref_image']) && $_FILES['ref_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = uploadImage($_FILES['ref_image']);
        if ($uploadedFile) $image_url = $uploadedFile;
    } elseif (isset($_POST['remove_old_image']) && $_POST['remove_old_image'] == '1') {
        $image_url = null; // ถ้ากดลบรูปเดิม
    }

    $update = $db->prepare("UPDATE wtb_posts SET title=?, category_id=?, description=?, image_url=?, expected_condition=?, budget=?, status='pending' WHERE id=? AND user_id=?");
    if ($update->execute([$title, $category_id, $description, $image_url, $expected_condition, $budget, $post_id, $_SESSION['user_id']])) {
        $_SESSION['flash_message'] = "แก้ไขเรียบร้อยแล้ว (รอแอดมินตรวจสอบอีกครั้ง)";
        $_SESSION['flash_type'] = "success";
        redirect('wtb_board.php');
    }
}
?>

<style>
    .wtb-upload-area { width: 100%; max-width: 200px; aspect-ratio: 1; margin: 0 auto; border: 3px dashed var(--border-color); border-radius: 24px; position: relative; cursor: pointer; overflow: hidden; }
    #img_preview { width: 100%; height: 100%; object-fit: cover; <?= $post['image_url'] ? 'display:block;' : 'display:none;' ?> }
    #remove_img_btn { position: absolute; top: 10px; right: 10px; background: red; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; display: <?= $post['image_url'] ? 'flex' : 'none' ?>; align-items: center; justify-content: center; z-index: 10; }
</style>

<div class="container mt-5">
    <div class="wtb-card p-4 shadow-sm" style="background: var(--bg-card); border-radius: 24px;">
        <h2 class="mb-4"><b><i class="fas fa-edit"></i> แก้ไขประกาศของคุณ</b></h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="remove_old_image" id="remove_old_image" value="0">
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <label class="d-block mb-2 fw-bold">รูปภาพอ้างอิง</label>
                    <div class="wtb-upload-area" id="img_container">
                        <button type="button" id="remove_img_btn" onclick="clearImage()"><i class="fas fa-times"></i></button>
                        <label for="ref_image" style="width:100%; height:100%; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                            <img id="img_preview" src="../assets/images/products/<?= $post['image_url'] ?>">
                            <div id="placeholder" style="<?= $post['image_url'] ? 'display:none;' : '' ?>"><i class="fas fa-camera fa-2x"></i></div>
                        </label>
                        <input type="file" name="ref_image" id="ref_image" style="display:none;" onchange="previewImage(this)">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="fw-bold">สิ่งที่ตามหา</label>
                        <input type="text" name="title" class="form-control" value="<?= e($post['title']) ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">หมวดหมู่</label>
                            <select name="category_id" class="form-select">
                                <?php foreach($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $post['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">สภาพ</label>
                            <select name="expected_condition" class="form-select">
                                <option value="any" <?= $post['expected_condition'] == 'any' ? 'selected' : '' ?>>ทุกสภาพ</option>
                                <option value="good" <?= $post['expected_condition'] == 'good' ? 'selected' : '' ?>>สภาพดี</option>
                                <option value="new" <?= $post['expected_condition'] == 'new' ? 'selected' : '' ?>>ของใหม่</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">งบประมาณ</label>
                        <input type="number" name="budget" class="form-control" value="<?= $post['budget'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">รายละเอียด</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($post['description']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold" style="border-radius: 15px;">บันทึกการแก้ไข</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('img_preview').src = e.target.result;
                document.getElementById('img_preview').style.display = 'block';
                document.getElementById('placeholder').style.display = 'none';
                document.getElementById('remove_img_btn').style.display = 'flex';
                document.getElementById('remove_old_image').value = '0';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    function clearImage() {
        document.getElementById('img_preview').style.display = 'none';
        document.getElementById('placeholder').style.display = 'block';
        document.getElementById('remove_img_btn').style.display = 'none';
        document.getElementById('ref_image').value = '';
        document.getElementById('remove_old_image').value = '1';
    }
</script>