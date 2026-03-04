<?php
/**
 * BNCC Market - Product Detail, Reviews & Wishlist (Line Notification Version)
 */
require_once '../includes/header.php';
require_once '../includes/functions.php';

$product_id = $_GET['id'] ?? null;
if (!$product_id) redirect('index.php');

$db = getDB();
$user_id = $_SESSION['user_id'];

// 🛠️ 1. อัปเดต SQL: ดึง line_token มาด้วยเพื่อใช้แจ้งเตือน
$stmt = $db->prepare("SELECT p.*, s.shop_name, s.contact_line, s.contact_ig, s.line_token 
                      FROM products p 
                      JOIN shops s ON p.shop_id = s.id 
                      WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

$tag_stmt = $db->prepare("SELECT t.tag_name FROM tags t JOIN product_tag_map ptm ON t.id = ptm.tag_id WHERE ptm.product_id = ?");
$tag_stmt->execute([$product_id]);
$product_tags = $tag_stmt->fetchAll();

// 2. ตรวจสอบสถานะการกดถูกใจ (Wishlist Status)
$is_wishlisted = false;
if (isLoggedIn()) {
    $check_wish = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check_wish->execute([$user_id, $product_id]);
    $is_wishlisted = $check_wish->fetch() ? true : false;
}

// --- 3. ประมวลผลการส่งรีวิว (POST) พร้อมแจ้งเตือน Line ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);
    
    $ins = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    if ($ins->execute([$product_id, $user_id, $rating, $comment])) {
        
        // 🔔 ยิงแจ้งเตือนเข้า Line คนขาย (ถ้าเขามี Token)
        if (!empty($product['line_token'])) {
            $message = "\n📢 มีรีวิวใหม่ถึงสินค้าของคุณ!\n"
                     . "📦 สินค้า: " . $product['title'] . "\n"
                     . "⭐️ คะแนน: " . $rating . " ดาว\n"
                     . "💬 ความเห็น: " . $comment . "\n"
                     . "🔗 ดูรีวิว: http://localhost/student_marketplace/pages/product_detail.php?id=" . $product_id;
            
            // เรียกใช้ฟังก์ชันที่กูให้ไปใส่ใน functions.php ก่อนหน้านี้
            sendLineNotify($product['line_token'], $message);
        }

        $_SESSION['flash_message'] = "ขอบคุณสำหรับรีวิว! ระบบได้แจ้งเตือนผู้ขายเรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "success";
        header("Location: product_detail.php?id=$product_id");
        exit();
    }
}

// 4. ดึงรีวิวทั้งหมดของสินค้านี้
$rev_stmt = $db->prepare("SELECT r.*, u.fullname FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$rev_stmt->execute([$product_id]);
$all_reviews = $rev_stmt->fetchAll();
?>

<div class="product-detail-container" style="max-width: 1000px; margin: 30px auto;">
    <div class="card" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; padding: 30px; border-radius: 20px;">
        <div>
            <img src="../assets/images/products/<?= $product['image_url'] ?>" style="width: 100%; border-radius: 15px; box-shadow: var(--shadow);">
        </div>
        
        <div>
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 15px;">
                <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 10px; flex: 1;"><?= e($product['title']) ?></h1>
                
                <button id="main-wish-btn" 
                        data-id="<?= $product['id'] ?>" 
                        title="เพิ่มในรายการที่ชอบ"
                        style="background: var(--bg-card); border: 1px solid var(--border-color); width: 48px; height: 48px; border-radius: 50%; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: <?= $is_wishlisted ? '#ef4444' : '#cbd5e1' ?>; box-shadow: var(--shadow-sm);">
                    <i class="<?= $is_wishlisted ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
            </div>
            
            <div style="margin-bottom: 20px; display: flex; gap: 8px; flex-wrap: wrap;">
                <?php foreach ($product_tags as $tag): ?>
                    <span style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600;">
                        <i class="fas fa-tag" style="font-size: 0.7rem;"></i> <?= e($tag['tag_name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <div style="font-size: 1.8rem; color: var(--primary); font-weight: 800; margin-bottom: 20px;">
                ฿<?= number_format($product['price'], 2) ?>
            </div>
            
            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 30px;"><?= nl2br(e($product['description'])) ?></p>
            
            <div style="padding: 20px; background: var(--bg-body); border-radius: 12px; display: flex; align-items: center; gap: 15px; border: 1px solid var(--border-color);">
                <i class="fas fa-store" style="font-size: 2rem; color: var(--primary);"></i>
                <div style="flex: 1;">
                    <div style="font-size: 0.8rem; color: var(--text-muted);">ร้านค้าผู้ขาย</div>
                    <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 12px;"><?= e($product['shop_name']) ?></div>
                    
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <?php if(!empty($product['contact_line'])): ?>
                            <a href="https://line.me/ti/p/~<?= e($product['contact_line']) ?>" target="_blank" class="btn-contact line-color">
                                <i class="fab fa-line"></i> ทักไลน์เลย
                            </a>
                        <?php endif; ?>
                        
                        <?php if(!empty($product['contact_ig'])): ?>
                            <a href="https://www.instagram.com/<?= e($product['contact_ig']) ?>/" target="_blank" class="btn-contact ig-color">
                                <i class="fab fa-instagram"></i> ดู IG ร้าน
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top: 40px;">
        <h2 style="margin-bottom: 25px;"><i class="fas fa-comments text-primary"></i> รีวิวจากเพื่อน ๆ (<?= count($all_reviews) ?>)</h2>
        
        <div class="card" style="padding: 25px; border-radius: 16px; margin-bottom: 30px; border: 1px solid var(--border-color);">
            <h4 style="margin-bottom: 15px;">เขียนรีวิวของคุณ</h4>
            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px;">ให้คะแนนสินค้า:</label>
                    <div class="star-rating">
                        <?php for($i=5; $i>=1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                            <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <textarea name="comment" class="form-control" placeholder="แชร์ความรู้สึกหลังใช้งานสินค้าชิ้นนี้..." style="min-height: 100px;"></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary" style="margin-top: 15px; padding: 10px 30px;">ส่งรีวิว</button>
            </form>
        </div>

        <div class="review-list">
            <?php if (count($all_reviews) > 0): ?>
                <?php foreach ($all_reviews as $rev): ?>
                    <div class="card" style="padding: 20px; border-radius: 12px; margin-bottom: 15px; background: var(--bg-card);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong style="color: var(--text-main);"><?= e($rev['fullname']) ?></strong>
                            <div style="color: #fbbf24;">
                                <?php for($j=0; $j<$rev['rating']; $j++) echo '<i class="fas fa-star"></i>'; ?>
                            </div>
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.95rem;"><?= nl2br(e($rev['comment'])) ?></p>
                        <small style="color: #94a3b8; font-size: 0.75rem;"><?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-muted); padding: 40px;">ยังไม่มีรีวิวสำหรับสินค้านี้ เป็นคนแรกที่รีวิวสิ!</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* สไตล์ปุ่มติดต่อ */
    .btn-contact {
        padding: 10px 18px; border-radius: 12px; color: white !important;
        text-decoration: none; font-size: 0.85rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: 8px;
        transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .btn-contact:hover { transform: translateY(-3px); filter: brightness(1.1); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
    .line-color { background: #00c300; }
    .ig-color { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); }

    /* การเลือกดาว */
    .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 5px; }
    .star-rating input { display: none; }
    .star-rating label { font-size: 1.5rem; color: #cbd5e1; cursor: pointer; transition: 0.2s; }
    .star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label { color: #fbbf24; }
</style>

<script>
/**
 * AJAX Toggle Wishlist Script
 */
document.getElementById('main-wish-btn').addEventListener('click', function() {
    const btn = this;
    const icon = btn.querySelector('i');
    const productId = btn.dataset.id;

    fetch('../auth/toggle_wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'added') {
            icon.classList.replace('far', 'fas');
            btn.style.color = '#ef4444';
        } else if (data.status === 'removed') {
            icon.classList.replace('fas', 'far');
            btn.style.color = '#cbd5e1';
        } else if (data.status === 'error') {
            alert(data.message);
        }
    })
    .catch(err => console.error('Error:', err));
});
</script>

<?php require_once '../includes/footer.php'; ?>