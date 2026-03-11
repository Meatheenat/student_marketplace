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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;

    if (empty($title)) {
        $_SESSION['flash_message'] = "กรุณาระบุสิ่งที่ต้องการตามหา";
        $_SESSION['flash_type'] = "danger";
    } else {
        $stmt = $db->prepare("INSERT INTO wtb_posts (user_id, title, description, budget) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $title, $description, $budget])) {
            $_SESSION['flash_message'] = "โพสต์ตามหาสินค้าสำเร็จ! ขอให้เจอของที่ใช่นะครับ";
            $_SESSION['flash_type'] = "success";
            redirect('wtb_board.php'); // โพสต์เสร็จเด้งไปหน้ากระดาน
        }
    }
}
?>

<div class="container mt-5 mb-5" style="max-width: 700px;">
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
            <form action="wtb_create.php" method="POST">
                <div class="mb-4">
                    <label style="font-weight: 800; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">สิ่งที่ต้องการตามหา <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="เช่น หนังสือบัญชีเบื้องต้น ปี 1, ชุดพละไซส์ L" required style="border-radius: 14px; padding: 15px; border: 2px solid var(--border-color); background: var(--bg-main);">
                </div>

                <div class="mb-4">
                    <label style="font-weight: 800; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">รายละเอียดเพิ่มเติม</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="เช่น ขอสภาพ 80% ขึ้นไป ไม่มีรอยขีดเขียนเยอะ..." style="border-radius: 14px; padding: 15px; border: 2px solid var(--border-color); background: var(--bg-main);"></textarea>
                </div>

                <div class="mb-5">
                    <label style="font-weight: 800; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">งบประมาณที่มี (บาท) - ใส่หรือไม่ใส่ก็ได้</label>
                    <input type="number" name="budget" class="form-control" placeholder="เช่น 200" style="border-radius: 14px; padding: 15px; border: 2px solid var(--border-color); background: var(--bg-main);">
                </div>

                <button type="submit" class="btn w-100" style="background: #6366f1; color: white; border-radius: 16px; padding: 16px; font-weight: 800; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3); transition: 0.3s;">
                    <i class="fas fa-paper-plane"></i> โพสต์ตามหาเลย
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>