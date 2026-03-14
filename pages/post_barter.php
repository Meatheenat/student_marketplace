<?php
require_once '../includes/functions.php';
checkRole('buyer'); // ต้อง Login ก่อน
$pageTitle = "ลงประกาศแลกเปลี่ยน";
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $title = trim($_POST['title']);
    $have = trim($_POST['item_have']);
    $want = trim($_POST['item_want']);
    $desc = trim($_POST['description']);
    
    // อัปโหลดรูป (ถ้ามี)
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = uploadImage($_FILES['image'], "../assets/images/barter/");
    }

    $stmt = $db->prepare("INSERT INTO barter_posts (user_id, title, item_have, item_want, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $title, $have, $want, $desc, $image])) {
        $_SESSION['flash_message'] = "ลงประกาศสำเร็จ!";
        $_SESSION['flash_type'] = "success";
        redirect('barter_board.php');
    }
}
?>

<div class="ui-container ui-py-10" style="max-width:800px;">
    <div class="ui-card ui-p-10">
        <h2 class="ui-font-black ui-text-3xl ui-mb-8">สร้างรายการ <span class="ui-text-primary">แลกเปลี่ยน</span></h2>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="ui-mb-6">
                <label class="ui-font-black ui-text-xs ui-text-muted ui-uppercase">หัวข้อประกาศ</label>
                <input type="text" name="title" class="ui-input" placeholder="เช่น แลกหนังสือการ์ตูนกับนิยาย" required>
            </div>

            <div class="ui-grid ui-grid-cols-2 ui-gap-6 ui-mb-6">
                <div>
                    <label class="ui-font-black ui-text-xs ui-text-success ui-uppercase">สิ่งที่คุณมี (I HAVE)</label>
                    <input type="text" name="item_have" class="ui-input" placeholder="สิ่งที่พี่มีจะเอามาแลก" required>
                </div>
                <div>
                    <label class="ui-font-black ui-text-xs ui-text-primary ui-uppercase">สิ่งที่ต้องการ (I WANT)</label>
                    <input type="text" name="item_want" class="ui-input" placeholder="สิ่งที่พี่อยากได้คืนมา" required>
                </div>
            </div>

            <div class="ui-mb-6">
                <label class="ui-font-black ui-text-xs ui-text-muted ui-uppercase">รายละเอียดเพิ่มเติม</label>
                <textarea name="description" class="ui-input" style="min-height:120px;" placeholder="บอกรายละเอียดสภาพของ สเปก หรือเงื่อนไขการแลกเปลี่ยน..."></textarea>
            </div>

            <div class="ui-mb-8">
                <label class="ui-font-black ui-text-xs ui-text-muted ui-uppercase">รูปภาพสินค้า</label>
                <input type="file" name="image" class="ui-input">
            </div>

            <button type="submit" class="ui-btn ui-btn-primary ui-w-full" style="padding:20px;">
                <i class="fas fa-paper-plane"></i> ยืนยันการลงประกาศ
            </button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>