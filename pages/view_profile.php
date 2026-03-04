<?php
/**
 * BNCC Market - Public Profile View (Premium Social Version)
 * หน้าแสดงโปรไฟล์สาธารณะสำหรับให้สมาชิกคนอื่นดู
 */
require_once '../includes/header.php';
require_once '../includes/functions.php';

$target_user_id = $_GET['id'] ?? null;
if (!$target_user_id) redirect('index.php');

$db = getDB();
$current_user_id = $_SESSION['user_id'] ?? null;

// 1. ดึงข้อมูลผู้ใช้ที่ต้องการดู
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$target_user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='container' style='padding: 100px; text-align: center;'><h2>ไม่พบผู้ใช้งานนี้ในระบบ</h2><a href='index.php'>กลับหน้าหลัก</a></div>";
    require_once '../includes/footer.php';
    exit();
}

// 2. ดึงข้อมูลร้านค้า (ถ้ามี)
$shop_stmt = $db->prepare("SELECT * FROM shops WHERE user_id = ?");
$shop_stmt->execute([$target_user_id]);
$shop = $shop_stmt->fetch();

// 🛠️ 3. [เพิ่มใหม่] คำนวณคะแนนรีวิวเฉลี่ยของร้านค้า
$avg_rating = 0;
$review_count = 0;
if ($shop) {
    $rating_stmt = $db->prepare("
        SELECT AVG(r.rating) as avg_r, COUNT(r.id) as count_r 
        FROM reviews r 
        JOIN products p ON r.product_id = p.id 
        WHERE p.shop_id = ?
    ");
    $rating_stmt->execute([$shop['id']]);
    $rating_data = $rating_stmt->fetch();
    $avg_rating = round($rating_data['avg_r'] ?? 0, 1);
    $review_count = $rating_data['count_r'];
}

// 4. ดึงสินค้าเด่นของร้าน
$products = [];
if ($shop) {
    $p_stmt = $db->prepare("SELECT * FROM products WHERE shop_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 6");
    $p_stmt->execute([$shop['id']]);
    $products = $p_stmt->fetchAll();
}

// 🎯 🛠️ แก้ไข: เปลี่ยนโฟลเดอร์รูปภาพจาก uploads/profiles เป็น images/profiles ให้ตรงกับที่มึงแก้ล่าสุด!
$upload_file = "../assets/images/profiles/" . $user['profile_img'];
$default_avatar = "../assets/images/profiles/default_profile.png";
$avatar = (!empty($user['profile_img']) && file_exists($upload_file)) ? $upload_file : $default_avatar;
?>

<style>
    .profile-banner { height: 200px; background: linear-gradient(135deg, var(--primary), #6366f1, #a855f7); border-radius: 24px 24px 0 0; }
    .profile-container { margin-top: -80px; padding: 0 30px 40px; }
    .profile-avatar-large { width: 160px; height: 160px; border-radius: 50%; object-fit: cover; border: 6px solid var(--bg-card); box-shadow: var(--shadow-lg); background: var(--bg-card); }
    
    .social-btn { padding: 12px 25px; border-radius: 14px; font-weight: 700; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; box-shadow: var(--shadow-sm); }
    .social-btn:hover { transform: translateY(-3px); filter: brightness(1.1); }
    
    .btn-line { background: #06c755; color: white !important; }
    .btn-ig { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); color: white !important; }
    
    .product-mini-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden; transition: 0.3s; }
    .product-mini-card:hover { transform: scale(1.03); border-color: var(--primary); }
    
    .badge-status { padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .badge-banned { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    
    /* 🛠️ [เพิ่มใหม่] Style สำหรับแสดงดาวรีวิว */
    .rating-badge { background: rgba(251, 191, 36, 0.1); color: #fbbf24; padding: 5px 15px; border-radius: 50px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 15px; }
</style>

<div class="view-profile-wrapper" style="max-width: 1000px; margin: 40px auto;">
    <div class="card" style="border-radius: 24px; overflow: hidden; border: 1px solid var(--border-color); background: var(--bg-card);">
        <div class="profile-banner"></div>
        
        <div class="profile-container text-center">
            <img src="<?= $avatar ?>" onerror="this.src='<?= $default_avatar ?>'" class="profile-avatar-large">
            
            <div style="margin-top: 20px;">
                <?php if ($shop && $review_count > 0): ?>
                    <div class="rating-badge">
                        <i class="fas fa-star"></i> <?= $avg_rating ?> / 5.0 (<?= $review_count ?> รีวิว)
                    </div>
                <?php endif; ?>

                <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 5px;">
                    <?= e($user['fullname']) ?>
                    <?php if ($user['is_banned']): ?>
                        <span class="badge-status badge-banned" style="vertical-align: middle; margin-left: 10px;"><i class="fas fa-user-slash"></i> BANNED</span>
                    <?php endif; ?>
                </h1>
                
                <div style="color: var(--primary); font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">
                    สมาชิกตั้งแต่ <?= date('M Y', strtotime($user['created_at'])) ?>
                </div>

                <p style="color: var(--text-muted); max-width: 650px; margin: 0 auto 30px; line-height: 1.7; font-size: 1.05rem;">
                    <?= !empty($user['bio']) ? nl2br(e($user['bio'])) : "ยังไม่ได้เขียนคำแนะนำตัว..." ?>
                </p>

                <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                    <?php if ($target_user_id != $current_user_id && isLoggedIn()): ?>
                        <button onclick="openReportModal(<?= $target_user_id ?>, 'user')" class="btn btn-outline" style="border-color: #f87171; color: #f87171; padding: 10px 25px; border-radius: 12px;">
                            <i class="fas fa-flag"></i> รายงานผู้ใช้
                        </button>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $target_user_id != $current_user_id): ?>
                        <form action="../admin/manage_user_action.php" method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?= $target_user_id ?>">
                            <?php if ($user['is_banned']): ?>
                                <button type="submit" name="action" value="unban" class="btn btn-success" style="padding: 10px 25px; border-radius: 12px;" onclick="return confirm('ปลดแบนสมาชิกคนนี้?')">ปลดแบน</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="ban" class="btn btn-danger" style="padding: 10px 25px; border-radius: 12px;" onclick="return confirm('ยืนยันการแบนสมาชิกถาวร?')">แบนถาวร</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($shop): ?>
        <div style="margin-top: 50px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; border-bottom: 2px solid var(--border-color); padding-bottom: 15px;">
                <div>
                    <h2 style="font-size: 1.8rem; font-weight: 800; color: var(--text-main);"><i class="fas fa-store text-primary"></i> <?= e($shop['shop_name']) ?></h2>
                    <p style="color: var(--text-muted); margin-top: 5px;"><?= e($shop['description']) ?></p>
                </div>
                
                <button onclick="openReportModal(<?= $shop['id'] ?>, 'shop')" style="background: none; border: none; color: #f87171; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: 600; padding: 10px;">
                    <i class="fas fa-exclamation-circle"></i> รายงานร้านค้า
                </button>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 40px; flex-wrap: wrap;">
                <?php if (!empty($shop['contact_line'])): ?>
                    <a href="https://line.me/ti/p/~<?= e($shop['contact_line']) ?>" target="_blank" class="social-btn btn-line">
                        <i class="fab fa-line" style="font-size: 1.2rem;"></i> LINE: <?= e($shop['contact_line']) ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($shop['contact_ig'])): ?>
                    <a href="https://www.instagram.com/<?= e($shop['contact_ig']) ?>/" target="_blank" class="social-btn btn-ig">
                        <i class="fab fa-instagram" style="font-size: 1.2rem;"></i> Instagram: <?= e($shop['contact_ig']) ?>
                    </a>
                <?php endif; ?>
            </div>

            <h3 style="margin-bottom: 25px; font-weight: 700;">สินค้าทั้งหมดจากร้านนี้</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
                <?php foreach ($products as $p): ?>
                    <a href="product_detail.php?id=<?= $p['id'] ?>" class="product-mini-card" style="text-decoration: none; color: inherit;">
                        <img src="../assets/images/products/<?= $p['image_url'] ?>" style="width: 100%; height: 200px; object-fit: cover;">
                        <div style="padding: 15px;">
                            <div style="font-weight: 700; margin-bottom: 5px;"><?= e($p['title']) ?></div>
                            <div style="color: var(--primary); font-weight: 800; font-size: 1.1rem;">฿<?= number_format($p['price'], 2) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
                
                <?php if (count($products) == 0): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: var(--bg-card); border-radius: 20px; color: var(--text-muted); border: 1px dashed var(--border-color);">
                        <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <p>ยังไม่มีสินค้าที่วางจำหน่ายในขณะนี้</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="reportModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(2, 6, 23, 0.85); backdrop-filter: blur(5px); align-items:center; justify-content:center;">
    <div style="background:var(--bg-card); padding:40px; border-radius:24px; width:90%; max-width:450px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); border: 1px solid var(--border-color);">
        <h3 style="margin-bottom:15px; color:#ef4444;"><i class="fas fa-flag"></i> ส่งรายงานความไม่เหมาะสม</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 25px;">แอดมินจะตรวจสอบข้อมูลและดำเนินการภายใน 24 ชม.</p>
        
        <form action="../auth/submit_report.php" method="POST">
            <input type="hidden" name="target_id" id="report_target_id">
            <input type="hidden" name="target_type" id="report_target_type">
            <input type="hidden" name="redirect_url" value="view_profile.php?id=<?= $target_user_id ?>">
            
            <div class="form-group">
                <label style="display:block; margin-bottom:10px; font-weight: 600;">ระบุสาเหตุที่รายงาน:</label>
                <textarea name="reason" class="form-control" required style="width:100%; min-height:120px; border-radius: 12px; padding: 15px; background: var(--bg-body); color: var(--text-main);" placeholder="เช่น รูปโปรไฟล์ผิดกฎโรงเรียน, ชื่อร้านค้าไม่สุภาพ, หลอกลวง..."></textarea>
            </div>
            
            <div style="display:flex; gap:12px; margin-top: 30px;">
                <button type="button" onclick="closeReportModal()" class="btn btn-outline" style="flex:1; border-radius: 12px; font-weight: 600;">ยกเลิก</button>
                <button type="submit" class="btn btn-danger" style="flex:1; border-radius: 12px; font-weight: 700;">ส่งรายงาน</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReportModal(id, type) {
    document.getElementById('report_target_id').value = id;
    document.getElementById('report_target_type').value = type;
    document.getElementById('reportModal').style.display = 'flex';
}
function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>