<?php
/**
 * BNCC Market - My Wishlist Page
 */
$pageTitle = "รายการที่ฉันชอบ - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../auth/login.php');

$db = getDB();
$user_id = $_SESSION['user_id'];

// ดึงสินค้าที่ถูกใจ JOIN กับข้อมูลสินค้าและร้านค้า
$stmt = $db->prepare("
    SELECT p.*, s.shop_name, c.category_name 
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    JOIN shops s ON p.shop_id = s.id
    JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();
?>

<div class="container" style="margin-top: 30px; margin-bottom: 50px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="font-size: 1.8rem; font-weight: 700;">
            <i class="fas fa-heart text-danger"></i> รายการที่ถูกใจของฉัน
        </h1>
        <span style="color: var(--text-muted);"><?= count($wishlist_items) ?> รายการ</span>
    </div>

    <?php if (count($wishlist_items) > 0): ?>
        <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
            <?php foreach ($wishlist_items as $p): ?>
                <div class="product-card" id="product-<?= $p['id'] ?>" style="background: var(--bg-card); border-radius: 20px; overflow: hidden; border: 1px solid var(--border-color); position: relative;">
                    <button class="wishlist-btn active" data-id="<?= $p['id'] ?>" style="position: absolute; top: 15px; right: 15px; z-index: 10; background: rgba(255,255,255,0.9); border: none; width: 35px; height: 35px; border-radius: 50%; color: #ef4444; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                        <i class="fas fa-heart"></i>
                    </button>

                    <a href="product_detail.php?id=<?= $p['id'] ?>" style="text-decoration: none; color: inherit;">
                        <img src="../assets/images/products/<?= $p['image_url'] ?>" style="width: 100%; height: 200px; object-fit: cover;">
                        <div style="padding: 20px;">
                            <span style="font-size: 0.75rem; color: var(--primary); font-weight: 600;"><?= e($p['category_name']) ?></span>
                            <h3 style="font-size: 1.1rem; margin: 8px 0; font-weight: 700;"><?= e($p['title']) ?></h3>
                            <div style="font-size: 1.2rem; font-weight: 800; color: var(--primary);">฿<?= number_format($p['price'], 2) ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 100px 20px; background: var(--bg-card); border-radius: 24px; border: 2px dashed var(--border-color);">
            <i class="far fa-heart" style="font-size: 4rem; color: var(--border-color); margin-bottom: 20px;"></i>
            <h3 style="color: var(--text-main);">ยังไม่มีรายการที่ถูกใจ</h3>
            <p style="color: var(--text-muted);">เริ่มสำรวจสินค้าและกดรูปหัวใจเพื่อบันทึกสิ่งที่คุณชอบ</p>
            <a href="index.php" class="btn btn-primary" style="margin-top: 20px; padding: 12px 30px; border-radius: 12px;">ไปช้อปปิ้งกันเลย</a>
        </div>
    <?php endif; ?>
</div>

<script>
/**
 * AJAX Toggle Wishlist Script
 */
document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const productId = this.dataset.id;
        const card = document.getElementById('product-' + productId);

        fetch('../auth/toggle_wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'product_id=' + productId
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'removed') {
                // ถ้าอยู่ในหน้า Wishlist ให้ลบการ์ดออกทันที
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300);
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>