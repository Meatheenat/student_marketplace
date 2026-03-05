<?php
/**
 * BNCC Market - Public Profile View (Solid High-Contrast Edition)
 * Project: BNCC Student Marketplace [Cite: User Summary]
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

// 3. คำนวณคะแนนรีวิวเฉลี่ยของร้านค้า
$avg_rating = 0;
$review_count = 0;
if ($shop) {
    $rating_stmt = $db->prepare("
        SELECT AVG(r.rating) as avg_r, COUNT(r.id) as count_r 
        FROM reviews r 
        JOIN products p ON r.product_id = p.id 
        WHERE p.shop_id = ? AND p.is_deleted = 0 AND r.is_deleted = 0
    ");
    $rating_stmt->execute([$shop['id']]);
    $rating_data = $rating_stmt->fetch();
    $avg_rating = round($rating_data['avg_r'] ?? 0, 1);
    $review_count = $rating_data['count_r'];
}

// 4. ดึงสินค้าเด่นของร้าน
$products = [];
if ($shop) {
    $p_stmt = $db->prepare("SELECT * FROM products WHERE shop_id = ? AND status = 'approved' AND is_deleted = 0 ORDER BY created_at DESC LIMIT 6");
    $p_stmt->execute([$shop['id']]);
    $products = $p_stmt->fetchAll();
}

$upload_file = "../assets/images/profiles/" . $user['profile_img'];
$default_avatar = "../assets/images/profiles/default_profile.png";
$avatar = (!empty($user['profile_img']) && file_exists($upload_file)) ? $upload_file : $default_avatar;
?>

<style>
    /* ============================================================
       🛠️ SOLID UI SYSTEM - CENTERED & HIGH CONTRAST
       ============================================================ */
    :root {
        --solid-bg: #f1f5f9;
        --solid-card: #ffffff;
        --solid-border: #cbd5e1;
        --solid-text: #0f172a;
        --solid-primary: #4f46e5;
    }

    .dark-theme {
        --solid-bg: #0f172a;
        --solid-card: #1e293b;
        --solid-border: #334155;
        --solid-text: #ffffff;
        --solid-primary: #6366f1;
    }

    body { background-color: var(--solid-bg) !important; color: var(--solid-text); }

    /* 🏰 Profile Header Card */
    .profile-card-solid {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 32px;
        overflow: hidden;
        margin-bottom: 50px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        animation: dropIn 0.8s ease forwards;
    }

    .profile-banner-solid {
        height: 200px;
        background: linear-gradient(135deg, var(--solid-primary), #a855f7);
        border-bottom: 2px solid var(--solid-border);
    }

    .profile-info-solid {
        padding: 0 40px 50px;
        text-align: center;
        margin-top: -80px;
    }

    .avatar-solid {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        object-fit: cover;
        border: 6px solid var(--solid-card);
        background: var(--solid-bg);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .social-btn-solid {
        padding: 14px 30px;
        border-radius: 16px;
        font-weight: 800;
        font-size: 1rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: transform 0.2s;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .social-btn-solid:hover { transform: scale(1.05); }

    .btn-line-solid { background: #06c755; color: white !important; }
    .btn-ig-solid { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); color: white !important; }

    /* 🧱 Product Card - Solid Style (เหมือนหน้า Index) */
    .product-box {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        opacity: 0;
        transform: translateY(30px);
    }
    .product-box.show { opacity: 1; transform: translateY(0); }
    .product-box:hover {
        transform: translateY(-10px);
        border-color: var(--solid-primary);
        box-shadow: 0 20px 30px rgba(0,0,0,0.1);
    }

    .img-area { height: 220px; width: 100%; position: relative; border-bottom: 2px solid var(--solid-border); overflow: hidden; }
    .img-area img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .product-box:hover .img-area img { transform: scale(1.1); }

    /* 🎯 🛠️ BIG PRICE BADGE (ซ้ายบนเหมือน Index) */
    .price-badge {
        position: absolute;
        top: 15px; 
        left: 15px; 
        background: #0f172a; 
        color: #ffffff;
        padding: 8px 18px;
        border-radius: 12px;
        font-weight: 900;
        font-size: 1.4rem; 
        letter-spacing: -0.5px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.4); 
        border: 2px solid rgba(255,255,255,0.1); 
        animation: subtlePulse 2s infinite alternate; 
    }

    @keyframes subtlePulse {
        from { transform: scale(1); }
        to { transform: scale(1.05); }
    }

    .info-wrap { padding: 25px; }
    .info-wrap h3 { font-size: 1.2rem; font-weight: 800; margin-bottom: 10px; color: var(--solid-text); }
    
    .rating-badge-solid { 
        background: #fbbf24; 
        color: #000; 
        padding: 6px 20px; 
        border-radius: 50px; 
        font-weight: 900; 
        font-size: 1.1rem;
        display: inline-flex; 
        align-items: center; 
        gap: 8px; 
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(251, 191, 36, 0.4);
    }

    @keyframes dropIn { to { opacity: 1; transform: translateY(0); } }

    /* Modal Solid */
    .modal-overlay {
        display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; 
        background: rgba(15, 23, 42, 0.9); align-items: center; justify-content: center;
    }
    .modal-solid {
        background: var(--solid-card); padding: 40px; border-radius: 32px; width: 90%; max-width: 480px; 
        border: 2px solid var(--solid-border);
    }
</style>

<div class="view-profile-wrapper" style="max-width: 1000px; margin: 60px auto;">
    <div class="profile-card-solid">
        <div class="profile-banner-solid"></div>
        
        <div class="profile-info-solid">
            <img src="<?= $avatar ?>" onerror="this.src='<?= $default_avatar ?>'" class="avatar-solid">
            
            <div>
                <?php if ($shop && $review_count > 0): ?>
                    <div class="rating-badge-solid">
                        <i class="fas fa-star"></i> <?= $avg_rating ?> / 5.0 
                        <span style="font-size: 0.8rem; font-weight: 700; opacity: 0.8;">(<?= $review_count ?> รีวิว)</span>
                    </div>
                <?php endif; ?>

                <h1 style="font-size: 2.5rem; font-weight: 900; color: var(--solid-text); margin-bottom: 5px; letter-spacing: -1px;">
                    <?= e($user['fullname']) ?>
                    <?php if ($user['is_banned']): ?>
                        <span style="background: var(--solid-danger); color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 0.9rem; vertical-align: middle; margin-left: 10px;"><i class="fas fa-user-slash"></i> BANNED</span>
                    <?php endif; ?>
                </h1>
                
                <div style="color: var(--solid-primary); font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 25px;">
                    เข้าร่วมเมื่อ <?= date('M Y', strtotime($user['created_at'])) ?>
                </div>

                <p style="color: var(--text-muted); max-width: 650px; margin: 0 auto 35px; line-height: 1.8; font-size: 1.1rem; font-weight: 500;">
                    <?= !empty($user['bio']) ? nl2br(e($user['bio'])) : "ยังไม่ได้เขียนคำแนะนำตัว..." ?>
                </p>

                <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                    <?php if ($target_user_id != $current_user_id && isLoggedIn()): ?>
                        <button onclick="openReportModal(<?= $target_user_id ?>, 'user')" class="btn btn-outline" style="border: 2px solid var(--solid-danger); color: var(--solid-danger); padding: 12px 30px; border-radius: 16px; font-weight: 800;">
                            <i class="fas fa-flag"></i> รายงานผู้ใช้
                        </button>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $target_user_id != $current_user_id): ?>
                        <form action="../admin/manage_user_action.php" method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?= $target_user_id ?>">
                            <?php if ($user['is_banned']): ?>
                                <button type="submit" name="action" value="unban" class="btn" style="background: #10b981; color: #fff; padding: 12px 30px; border-radius: 16px; font-weight: 800;" onclick="return confirm('ปลดแบนสมาชิกคนนี้?')">ปลดแบน</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="ban" class="btn" style="background: var(--solid-danger); color: #fff; padding: 12px 30px; border-radius: 16px; font-weight: 800;" onclick="return confirm('ยืนยันการแบนสมาชิกถาวร?')">แบนถาวร</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($shop): ?>
        <div style="margin-top: 60px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; border-bottom: 3px solid var(--solid-border); padding-bottom: 15px;">
                <div>
                    <h2 style="font-size: 2rem; font-weight: 900; color: var(--solid-text);"><i class="fas fa-store" style="color: var(--solid-primary);"></i> <?= e($shop['shop_name']) ?></h2>
                    <p style="color: var(--text-muted); margin-top: 5px; font-size: 1.1rem; font-weight: 500;"><?= e($shop['description']) ?></p>
                </div>
                
                <button onclick="openReportModal(<?= $shop['id'] ?>, 'shop')" style="background: none; border: none; color: var(--solid-danger); font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: 800; padding: 10px;">
                    <i class="fas fa-exclamation-circle"></i> รายงานร้านค้า
                </button>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 50px; flex-wrap: wrap;">
                <?php if (!empty($shop['contact_line'])): ?>
                    <a href="https://line.me/ti/p/~<?= e($shop['contact_line']) ?>" target="_blank" class="social-btn-solid btn-line-solid">
                        <i class="fab fa-line" style="font-size: 1.4rem;"></i> <?= e($shop['contact_line']) ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($shop['contact_ig'])): ?>
                    <a href="https://www.instagram.com/<?= e($shop['contact_ig']) ?>/" target="_blank" class="social-btn-solid btn-ig-solid">
                        <i class="fab fa-instagram" style="font-size: 1.4rem;"></i> <?= e($shop['contact_ig']) ?>
                    </a>
                <?php endif; ?>
            </div>

            <h3 style="margin-bottom: 30px; font-weight: 900; font-size: 1.5rem;">สินค้าทั้งหมดจากร้านนี้</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 30px;">
                <?php foreach ($products as $index => $p): ?>
                    <div class="product-box" style="animation-delay: <?= $index * 0.05 ?>s;">
                        <a href="product_detail.php?id=<?= $p['id'] ?>" style="text-decoration: none; color: inherit;">
                            <div class="img-area">
                                <img src="../assets/images/products/<?= $p['image_url'] ?>" alt="<?= e($p['title']) ?>">
                                
                                <div class="price-badge">฿<?= number_format($p['price'], 0) ?></div>
                            </div>
                            
                            <div class="info-wrap">
                                <h3><?= e($p['title']) ?></h3>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($products) == 0): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 80px; background: var(--solid-card); border-radius: 32px; border: 3px dashed var(--solid-border);">
                        <i class="fas fa-box-open" style="font-size: 4rem; color: var(--solid-border); margin-bottom: 20px;"></i>
                        <h3 style="font-weight: 900;">ร้านนี้ยังไม่มีสินค้าวางจำหน่าย</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="reportModal" class="modal-overlay">
    <div class="modal-solid">
        <h3 style="margin-bottom:15px; color:var(--solid-danger); font-weight: 900;"><i class="fas fa-flag"></i> ส่งรายงานความไม่เหมาะสม</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 25px; font-weight: 600;">แอดมินจะตรวจสอบข้อมูลและดำเนินการภายใน 24 ชม.</p>
        
        <form action="../auth/submit_report.php" method="POST">
            <input type="hidden" name="target_id" id="report_target_id">
            <input type="hidden" name="target_type" id="report_target_type">
            <input type="hidden" name="redirect_url" value="view_profile.php?id=<?= $target_user_id ?>">
            
            <div class="form-group">
                <label style="display:block; margin-bottom:10px; font-weight: 800; font-size: 0.85rem;">สาเหตุที่รายงาน:</label>
                <textarea name="reason" class="form-control" required style="width:100%; min-height:120px; border-radius: 16px; padding: 20px; background: var(--solid-bg); border: 2px solid var(--solid-border); color: var(--solid-text); font-weight: 600; outline: none;"></textarea>
            </div>
            
            <div style="display:flex; gap:15px; margin-top: 30px;">
                <button type="button" onclick="closeReportModal()" class="btn btn-outline" style="flex:1; border-radius: 14px; font-weight: 800; border-width: 2px;">ยกเลิก</button>
                <button type="submit" class="btn btn-danger" style="flex:1; border-radius: 14px; font-weight: 800; background: var(--solid-danger);">ส่งรายงาน</button>
            </div>
        </form>
    </div>
</div>

<script>
    /**
     * 🚀 Intersection Observer สำหรับโหลดการ์ดสินค้า
     */
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('show');
                }, index * 50); 
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.product-box').forEach(box => observer.observe(box));

    // Modal Controls
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