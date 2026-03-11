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

// 🎯 แก้บั๊ก seller_id โดยใช้การ JOIN ตาราง shops เพื่อหาเจ้าของร้าน (owner_id)
$stmt = $db->prepare("SELECT p.*, s.user_id as owner_id, s.shop_name 
                      FROM products p 
                      JOIN shops s ON p.shop_id = s.id 
                      WHERE p.id = ? AND p.is_deleted = 0");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['flash_message'] = "ไม่พบสินค้า หรือสินค้านี้ถูกลบไปแล้ว";
    $_SESSION['flash_type'] = "danger";
    redirect('index.php');
}

// 🎯 ป้องกันการซื้อของตัวเอง (แก้จาก seller_id เป็น owner_id)
if ($product['owner_id'] == $_SESSION['user_id']) {
    $_SESSION['flash_message'] = "คุณไม่สามารถสั่งซื้อสินค้าของร้านตัวเองได้";
    $_SESSION['flash_type'] = "warning";
    redirect('product_detail.php?id=' . $product_id);
}
?>

<style>
    /* ============================================================
       🛠️ CHECKOUT UI - PREMIUM SOLID DESIGN
       ============================================================ */
    :root {
        --chk-bg: #f8fafc;
        --chk-card: #ffffff;
        --chk-text: #0f172a;
        --chk-text-muted: #64748b;
        --chk-border: #e2e8f0;
        --chk-primary: #4f46e5;
        --chk-input-bg: #f1f5f9;
    }

    .dark-theme {
        --chk-bg: #0b0e14;
        --chk-card: #161b26;
        --chk-text: #ffffff;
        --chk-text-muted: #94a3b8;
        --chk-border: #2d3748;
        --chk-primary: #6366f1;
        --chk-input-bg: #1e293b;
    }

    .checkout-wrapper {
        max-width: 800px;
        margin: 50px auto;
        padding: 0 20px;
        animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes slideUp {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    .checkout-card {
        background: var(--chk-card);
        border: 2px solid var(--chk-border);
        border-radius: 32px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
    }

    .product-summary-box {
        display: flex;
        align-items: center;
        gap: 25px;
        padding-bottom: 30px;
        border-bottom: 2px dashed var(--chk-border);
        margin-bottom: 30px;
    }

    .product-summary-img {
        width: 120px;
        height: 120px;
        border-radius: 20px;
        object-fit: cover;
        border: 1px solid var(--chk-border);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .chk-form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--chk-text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }

    .chk-input {
        width: 100%;
        background: var(--chk-input-bg);
        border: 2px solid var(--chk-border);
        color: var(--chk-text);
        padding: 16px 20px;
        border-radius: 16px;
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
        outline: none;
    }

    .chk-input:focus {
        border-color: var(--chk-primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
    }

    select.chk-input {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1em;
    }
    
    .dark-theme select.chk-input {
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    }

    .btn-confirm-order {
        width: 100%;
        padding: 20px;
        border-radius: 18px;
        background: var(--chk-primary);
        color: #ffffff;
        font-weight: 800;
        font-size: 1.15rem;
        border: none;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
        margin-top: 40px;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn-confirm-order:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.5);
    }

    @media (max-width: 768px) {
        .checkout-card { padding: 25px; }
        .product-summary-box { flex-direction: column; text-align: center; }
    }
</style>

<div class="checkout-wrapper">
    
    <div style="margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
        <div style="width: 50px; height: 50px; background: var(--chk-primary); color: white; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <h1 style="font-weight: 900; font-size: 2.2rem; color: var(--chk-text); margin: 0; letter-spacing: -1px;">ยืนยันการสั่งซื้อ</h1>
    </div>

    <div class="checkout-card">
        
        <div class="product-summary-box">
            <img src="../assets/images/products/<?= htmlspecialchars($product['image_url']) ?>" alt="Product" class="product-summary-img">
            <div>
                <div style="font-size: 0.8rem; font-weight: 800; color: var(--chk-text-muted); text-transform: uppercase; margin-bottom: 5px;">
                    <i class="fas fa-store"></i> ร้าน: <?= htmlspecialchars($product['shop_name']) ?>
                </div>
                <h3 style="font-weight: 800; color: var(--chk-text); margin: 0 0 10px 0; font-size: 1.6rem;"><?= htmlspecialchars($product['title']) ?></h3>
                <div style="font-size: 1.8rem; font-weight: 900; color: var(--chk-primary);">
                    ฿<?= number_format($product['price']) ?>
                </div>
            </div>
        </div>

        <form action="process_checkout.php" method="POST">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <input type="hidden" name="price" value="<?= $product['price'] ?>">

            <h4 style="font-weight: 800; color: var(--chk-text); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-map-marker-alt text-danger"></i> ข้อมูลการนัดรับสินค้า
            </h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label class="chk-form-label">จุดนัดรับในวิทยาลัย</label>
                    <select name="meetup_location" class="chk-input" required>
                        <option value="" disabled selected>-- เลือกสถานที่ --</option>
                        <option value="โรงอาหาร">🍔 โรงอาหาร</option>
                        <option value="หน้าตึก 1">🏢 หน้าตึก 1</option>
                        <option value="ห้องสมุด">📚 ห้องสมุด</option>
                        <option value="หน้าเสาธง">🇹🇭 หน้าเสาธง</option>
                        <option value="โดมเอนกประสงค์">🏟️ โดมเอนกประสงค์</option>
                        <option value="อื่นๆ (ตกลงในแชท)">💬 อื่นๆ (ทักแชทบอกแม่ค้า)</option>
                    </select>
                </div>
                
                <div>
                    <label class="chk-form-label">เวลานัดรับ</label>
                    <select name="meetup_time" class="chk-input" required>
                        <option value="" disabled selected>-- เลือกเวลา --</option>
                        <option value="พักเที่ยง (12:00 - 13:00)">🕛 พักเที่ยง (12:00 - 13:00)</option>
                        <option value="เลิกเรียน (16:00 เป็นต้นไป)">🕓 เลิกเรียน (16:00 เป็นต้นไป)</option>
                        <option value="คาบว่าง (ตกลงในแชท)">🕒 คาบว่าง (ตกลงในแชท)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="chk-form-label">หมายเหตุถึงผู้ขาย (ถ้ามี)</label>
                <textarea name="buyer_note" class="chk-input" rows="3" placeholder="เช่น รอตรงโต๊ะหินอ่อนนะ, ใส่เสื้อสีดำ..."></textarea>
            </div>

            <button type="submit" class="btn-confirm-order">
                ยืนยันสั่งซื้อและนัดรับสินค้า <i class="fas fa-check-circle"></i>
            </button>
        </form>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>