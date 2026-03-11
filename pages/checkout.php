<?php
$pageTitle = "ยืนยันการสั่งซื้อ - Student Marketplace";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// บังคับล็อกอิน
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนสั่งซื้อสินค้า";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

$product_id = $_GET['id'] ?? 0;
$db = getDB();

// ดึงข้อมูลสินค้า
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 'available'");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['flash_message'] = "ไม่พบสินค้า หรือสินค้านี้ถูกขายไปแล้ว";
    $_SESSION['flash_type'] = "danger";
    redirect('index.php');
}

// ป้องกันการซื้อของตัวเอง
if ($product['seller_id'] == $_SESSION['user_id']) {
    $_SESSION['flash_message'] = "คุณไม่สามารถสั่งซื้อสินค้าของตัวเองได้";
    $_SESSION['flash_type'] = "warning";
    redirect('product_detail.php?id=' . $product_id);
}
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4" style="font-weight: 800; color: var(--text-main);">
                <i class="fas fa-shopping-bag text-primary"></i> ยืนยันการสั่งซื้อ
            </h2>

            <div class="card shadow-sm" style="border-radius: 20px; border: 1px solid var(--border-color); background: var(--bg-card);">
                <div class="card-body p-4">
                    
                    <div class="d-flex align-items-center mb-4 pb-4" style="border-bottom: 2px dashed var(--border-color);">
                        <img src="../assets/images/products/<?= htmlspecialchars($product['image']) ?>" alt="Product" style="width: 100px; height: 100px; object-fit: cover; border-radius: 15px;">
                        <div class="ms-4">
                            <h5 style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($product['title']) ?></h5>
                            <p class="text-muted mb-1">ราคา: <span class="text-primary fw-bold">฿<?= number_format($product['price']) ?></span></p>
                        </div>
                    </div>

                    <form action="process_checkout.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="price" value="<?= $product['price'] ?>">

                        <h5 class="mb-3" style="font-weight: 700; color: var(--text-main);">📍 ข้อมูลการนัดรับสินค้า</h5>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">จุดนัดรับในวิทยาลัย (Meetup Point)</label>
                            <select name="meetup_location" class="form-select" style="border-radius: 12px; padding: 12px;" required>
                                <option value="" disabled selected>-- เลือกสถานที่นัดรับ --</option>
                                <option value="โรงอาหาร">🍔 โรงอาหาร</option>
                                <option value="หน้าตึก 1">🏢 หน้าตึก 1</option>
                                <option value="ห้องสมุด">📚 ห้องสมุด</option>
                                <option value="หน้าเสาธง">🇹🇭 หน้าเสาธง</option>
                                <option value="โดมเอนกประสงค์">🏟️ โดมเอนกประสงค์</option>
                                <option value="อื่นๆ (ตกลงในแชท)">💬 อื่นๆ (ทักแชทบอกแม่ค้าทีหลัง)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted">เวลานัดรับ (Meetup Time)</label>
                            <select name="meetup_time" class="form-select" style="border-radius: 12px; padding: 12px;" required>
                                <option value="" disabled selected>-- เลือกเวลานัดรับ --</option>
                                <option value="พักเที่ยง (12:00 - 13:00)">🕛 พักเที่ยง (12:00 - 13:00)</option>
                                <option value="เลิกเรียน (16:00 เป็นต้นไป)">🕓 เลิกเรียน (16:00 เป็นต้นไป)</option>
                                <option value="คาบว่าง (ตกลงในแชท)">🕒 คาบว่าง (ตกลงในแชท)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted">หมายเหตุถึงผู้ขาย (ถ้ามี)</label>
                            <textarea name="buyer_note" class="form-control" rows="2" style="border-radius: 12px;" placeholder="เช่น รอตรงโต๊ะหินอ่อนนะ, ใส่เสื้อสีดำ..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" style="border-radius: 15px; padding: 15px; font-weight: 800; font-size: 1.1rem;">
                            ยืนยันสั่งซื้อและนัดรับสินค้า <i class="fas fa-check-circle"></i>
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>