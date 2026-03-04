<?php
/**
 * BNCC Market - Product Detail, Reviews & Wishlist (Messaging API Version)
 * [Cite: User Summary]
 */

// 🚀 1. โหลด Functions มาก่อนเสมอ! (ห้ามโหลด header.php ตรงนี้เด็ดขาด)
require_once '../includes/functions.php';

$product_id = $_GET['id'] ?? null;
if (!$product_id) redirect('index.php');

$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;

// 🛠️ 1. SQL: ดึงข้อมูลสินค้า + ร้านค้า + ID เจ้าของร้าน
$stmt = $db->prepare("SELECT p.*, s.shop_name, s.contact_line, s.contact_ig, s.line_user_id, s.user_id as owner_id 
                      FROM products p 
                      JOIN shops s ON p.shop_id = s.id 
                      WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// 🛠️ 2. [เพิ่มใหม่] SQL: คำนวณเรตติ้งเฉลี่ยเฉพาะสินค้าชิ้นนี้
$rating_summary_stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ?");
$rating_summary_stmt->execute([$product_id]);
$rating_info = $rating_summary_stmt->fetch();
$avg_p_rating = round($rating_info['avg_rating'] ?? 0, 1);
$total_p_reviews = $rating_info['total_reviews'];

$tag_stmt = $db->prepare("SELECT t.tag_name FROM tags t JOIN product_tag_map ptm ON t.id = ptm.tag_id WHERE ptm.product_id = ?");
$tag_stmt->execute([$product_id]);
$product_tags = $tag_stmt->fetchAll();

// 3. ตรวจสอบสถานะการกดถูกใจ (Wishlist Status)
$is_wishlisted = false;
if (isLoggedIn()) {
    $check_wish = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check_wish->execute([$user_id, $product_id]);
    $is_wishlisted = $check_wish->fetch() ? true : false;
}

// --- 4. ประมวลผลการส่งรีวิว (POST) พร้อมแจ้งเตือนผ่าน Messaging API ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);
    
    $ins = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    if ($ins->execute([$product_id, $user_id, $rating, $comment])) {
        if (!empty($product['line_user_id'])) {
            $message = "📢 มีรีวิวใหม่ถึงสินค้าของคุณ!\n"
                     . "📦 สินค้า: " . $product['title'] . "\n"
                     . "⭐️ คะแนน: " . $rating . " ดาว\n"
                     . "💬 ความเห็น: " . $comment . "\n"
                     . "🔗 ดูรีวิว: http://localhost/student_marketplace/pages/product_detail.php?id=" . $product_id;
            
            sendLineMessagingAPI($product['line_user_id'], $message);
        }

        $_SESSION['flash_message'] = "ขอบคุณสำหรับรีวิว! ระบบได้แจ้งเตือนผู้ขายเรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "success";
        // 🛠️ เปลี่ยนมาใช้ redirect() ที่เราสร้างไว้ใน functions.php
        redirect("product_detail.php?id=$product_id");
    }
}

// 🛠️ 5. ดึงรีวิว: เพิ่มรูปโปรไฟล์และ ID ผู้ใช้เพื่อลิงก์ไปหน้า Profile
$rev_stmt = $db->prepare("
    SELECT r.*, u.fullname, u.profile_img, u.id as author_id 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
$rev_stmt->execute([$product_id]);
$all_reviews = $rev_stmt->fetchAll();

// 🟩 6. เมื่อคำนวณและเช็ก POST เสร็จหมดแล้ว ค่อยโหลด Header (UI) ขึ้นมา
require_once '../includes/header.php';
?>

<div class="product-detail-container" style="max-width: 1000px; margin: 30px auto;">
    <div class="card" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; padding: 30px; border-radius: 20px; background: var(--bg-card); border: 1px solid var(--border-color);">
        <div class="product-image-side">
            <img src="../assets/images/products/<?= $product['image_url'] ?>" style="width: 100%; border-radius: 15px; box-shadow: var(--shadow-lg);">
        </div>
        
        <div class="product-info-side">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 15px;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 5px; color: var(--text-main);"><?= e($product['title']) ?></h1>
                    
                    <?php if ($total_p_reviews > 0): ?>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <div style="color: #fbbf24; font-size: 1rem;">
                                <?php 
                                for($k=1; $k<=5; $k++) {
                                    echo ($k <= round($avg_p_rating)) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                            <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600;">
                                <?= $avg_p_rating ?> (จาก <?= $total_p_reviews ?> รีวิว)
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <button id="main-wish-btn" data-id="<?= $product['id'] ?>" 
                        style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); width: 48px; height: 48px; border-radius: 50%; cursor: pointer; transition: 0.3s; color: <?= $is_wishlisted ? '#ef4444' : '#cbd5e1' ?>;">
                    <i class="<?= $is_wishlisted ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
            </div>
            
            <div style="margin-bottom: 20px; display: flex; gap: 8px; flex-wrap: wrap;">
                <?php foreach ($product_tags as $tag): ?>
                    <span style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600;">
                        #<?= e($tag['tag_name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <div style="font-size: 2.2rem; color: var(--primary); font-weight: 800; margin-bottom: 20px;">
                ฿<?= number_format($product['price'], 2) ?>
            </div>
            
            <p style="color: var(--text-muted); line-height: 1.7; margin-bottom: 30px; font-size: 1.05rem;"><?= nl2br(e($product['description'])) ?></p>
            
            <div style="padding: 25px; background: var(--bg-body); border-radius: 20px; border: 1px solid var(--border-color); position: relative;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 50px; height: 50px; background: var(--primary); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-store" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">ร้านค้าผู้ขาย</div>
                        <a href="view_profile.php?id=<?= $product['owner_id'] ?>" style="text-decoration: none; color: var(--text-main); font-weight: 700; font-size: 1.2rem;">
                            <?= e($product['shop_name']) ?> <i class="fas fa-external-link-alt" style="font-size: 0.7rem; opacity: 0.5;"></i>
                        </a>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div style="display: flex; gap: 10px;">
                        <?php if(!empty($product['contact_line'])): ?>
                            <a href="https://line.me/ti/p/~<?= e($product['contact_line']) ?>" target="_blank" class="btn-contact line-color">
                                <i class="fab fa-line"></i> LINE
                            </a>
                        <?php endif; ?>
                        
                        <?php if(!empty($product['contact_ig'])): ?>
                            <a href="https://www.instagram.com/<?= e($product['contact_ig']) ?>/" target="_blank" class="btn-contact ig-color">
                                <i class="fab fa-instagram"></i> Instagram
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <button onclick="openReportModal(<?= $product['shop_id'] ?>, 'shop')" style="background: none; border: none; color: #f87171; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: 500;">
                        <i class="fas fa-flag"></i> รายงานร้านค้า
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top: 50px;">
        <h2 style="margin-bottom: 30px; display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-comments text-primary"></i> รีวิวจากเพื่อน ๆ 
            <span style="font-size: 1rem; background: var(--border-color); padding: 2px 12px; border-radius: 50px;"><?= count($all_reviews) ?></span>
        </h2>
        
        <?php if (isLoggedIn()): ?>
        <div class="card" style="padding: 30px; border-radius: 20px; margin-bottom: 40px; border: 1px solid var(--border-color); background: var(--bg-card);">
            <h4 style="margin-bottom: 20px; font-weight: 600;">แบ่งปันประสบการณ์ของคุณ</h4>
            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; font-size: 0.95rem; color: var(--text-muted);">ให้คะแนนสินค้านี้:</label>
                    <div class="star-rating">
                        <?php for($i=5; $i>=1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                            <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <textarea name="comment" class="form-control" placeholder="เขียนความรู้สึกของคุณที่นี่..." style="min-height: 120px; border-radius: 12px; padding: 15px;"></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary" style="margin-top: 20px; padding: 12px 40px; border-radius: 12px; font-weight: 700;">ส่งรีวิว</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="review-list">
            <?php if (count($all_reviews) > 0): ?>
                <?php foreach ($all_reviews as $rev): 
                    // 🛠️ แก้ไขพาธรูปให้ตรงกับโฟลเดอร์ปัจจุบันของมึง
                    $upload_file = "../assets/images/profiles/" . $rev['profile_img'];
                    $default_avatar = "../assets/images/profiles/default_profile.png";
                    $avatar = (!empty($rev['profile_img']) && file_exists($upload_file)) ? $upload_file : $default_avatar;
                ?>
                    <div class="card" style="padding: 25px; border-radius: 20px; margin-bottom: 20px; background: var(--bg-card); display: flex; gap: 20px; border: 1px solid var(--border-color); transition: 0.3s;">
                        <a href="view_profile.php?id=<?= $rev['author_id'] ?>">
                            <img src="<?= $avatar ?>" onerror="this.src='<?= $default_avatar ?>'" style="width: 55px; height: 55px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border-color); background-color: var(--bg-body);">
                        </a>
                        
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <a href="view_profile.php?id=<?= $rev['author_id'] ?>" style="text-decoration: none; color: var(--text-main); font-weight: 700; font-size: 1.05rem; display: block; margin-bottom: 4px;">
                                        <?= e($rev['fullname']) ?>
                                    </a>
                                    <div style="color: #fbbf24; font-size: 0.85rem;">
                                        <?php for($j=0; $j<$rev['rating']; $j++) echo '<i class="fas fa-star"></i>'; ?>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="openReportModal(<?= $rev['id'] ?>, 'comment')" title="รายงานคอมเมนต์ไม่สุภาพ" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size: 1.1rem;">
                                        <i class="fas fa-flag"></i>
                                    </button>
                                    
                                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher'): ?>
                                        <button onclick="openDeleteCommentModal(<?= $rev['id'] ?>, '<?= e($rev['fullname']) ?>')" title="ลบคอมเมนต์ (Admin)" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size: 1.1rem;">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p style="color: var(--text-muted); font-size: 1rem; margin-top: 12px; line-height: 1.6;"><?= nl2br(e($rev['comment'])) ?></p>
                            <div style="margin-top: 15px; font-size: 0.75rem; color: #64748b; display: flex; align-items: center; gap: 6px;">
                                <i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-muted); padding: 60px; background: var(--bg-card); border-radius: 20px; border: 1px dashed var(--border-color);">
                    <i class="fas fa-comment-slash" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>ยังไม่มีรีวิวสำหรับสินค้านี้ มารีวิวคนแรกกัน!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="reportModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); backdrop-filter: blur(5px); align-items:center; justify-content:center;">
    <div style="background:var(--bg-card); padding:40px; border-radius:24px; width:90%; max-width:450px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); border: 1px solid var(--border-color);">
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="width: 70px; height: 70px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 2rem;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3 style="margin: 0; color: var(--text-main); font-size: 1.5rem;">รายงานความไม่เหมาะสม</h3>
        </div>
        <form action="../auth/submit_report.php" method="POST">
            <input type="hidden" name="target_id" id="report_target_id">
            <input type="hidden" name="target_type" id="report_target_type">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <div class="form-group">
                <textarea name="reason" class="form-control" required style="width:100%; min-height:120px; border-radius: 12px; padding: 15px;" placeholder="ระบุเหตุผล..."></textarea>
            </div>
            <div style="display:flex; gap:12px; margin-top: 30px;">
                <button type="button" onclick="closeReportModal()" class="btn btn-outline" style="flex:1;">ยกเลิก</button>
                <button type="submit" class="btn btn-danger" style="flex:1;">ส่งรายงาน</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteCommentModal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter: blur(5px); align-items:center; justify-content:center;">
    <div style="background:var(--bg-card); padding:35px; border-radius:24px; width:90%; max-width:400px; border:1px solid #ef4444;">
        <h3 style="color:#ef4444; margin-bottom:10px;"><i class="fas fa-trash-alt"></i> ลบคอมเมนต์</h3>
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">เจ้าของคอมเมนต์: <strong id="target_user_name" style="color:var(--text-main);"></strong></p>
        <form action="../admin/admin_delete_comment.php" method="POST">
            <input type="hidden" name="comment_id" id="delete_comment_id">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            
            <div class="form-group">
                <label style="display:block; margin-bottom:8px; font-weight:600;">เหตุผลในการลบ (จะแจ้งเตือนใน Log):</label>
                <textarea name="reason" class="form-control" required style="width:100%; min-height:100px; border-radius:12px; padding:15px;" placeholder="เช่น ใช้คำไม่สุภาพ, โฆษณาชวนเชื่อ..."></textarea>
            </div>
            
            <div style="display:flex; gap:12px; margin-top: 25px;">
                <button type="button" onclick="closeDeleteCommentModal()" class="btn btn-outline" style="flex:1;">ยกเลิก</button>
                <button type="submit" class="btn btn-danger" style="flex:1; font-weight:700;">ยืนยันการลบ</button>
            </div>
        </form>
    </div>
</div>

<style>
    .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 8px; }
    .star-rating input { display: none !important; } 
    .star-rating label { font-size: 1.8rem; color: #cbd5e1; cursor: pointer; transition: 0.2s; }
    .star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label { color: #fbbf24; }

    .btn-contact { padding: 10px 20px; border-radius: 14px; color: white !important; text-decoration: none; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .btn-contact:hover { transform: translateY(-3px); filter: brightness(1.1); }
    .line-color { background: #06c755; }
    .ig-color { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); }
</style>

<script>
function openReportModal(id, type) {
    document.getElementById('report_target_id').value = id;
    document.getElementById('report_target_type').value = type;
    document.getElementById('reportModal').style.display = 'flex';
}
function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}

// 🛡️ JS สำหรับ Delete Modal
function openDeleteCommentModal(id, name) {
    document.getElementById('delete_comment_id').value = id;
    document.getElementById('target_user_name').innerText = name;
    document.getElementById('deleteCommentModal').style.display = 'flex';
}
function closeDeleteCommentModal() {
    document.getElementById('deleteCommentModal').style.display = 'none';
}

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
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>