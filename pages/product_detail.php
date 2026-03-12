<?php
/**
 * ============================================================================================
 * 💎 BNCC MARKETPLACE - ENTERPRISE PRODUCT ARCHITECTURE (V 5.0.0)
 * ============================================================================================
 * Features: 
 * - Professional 404 Guard (Soft-fail handling)
 * - Multi-Image Liquid Gallery (5-Image Cluster)
 * - Enterprise Solid UX/UI Design System
 * - Modular JS Engine for Micro-interactions
 * - Anti-Spam & Sentiment-Aware Review System
 * - Role-Based Shielding (Admin/Teacher Controls)
 * ============================================================================================
 */

require_once '../includes/functions.php';

// --------------------------------------------------------------------------------------------
// 1. DATA CONTROLLER & INITIALIZATION
// --------------------------------------------------------------------------------------------
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;
$product_valid = false;

if ($product_id) {
    // ดึงข้อมูลสินค้าพร้อมข้อมูลร้านค้าและบทบาทเจ้าของเพื่อใช้ในการแสดงผล Badge
    $stmt = $db->prepare("SELECT p.*, s.shop_name, s.contact_line, s.contact_ig, s.line_user_id, 
                          s.user_id as owner_id, u.role as owner_role 
                          FROM products p 
                          JOIN shops s ON p.shop_id = s.id 
                          JOIN users u ON s.user_id = u.id
                          WHERE p.id = ? AND p.is_deleted = 0");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
        $product_valid = true;
    }
}

// --------------------------------------------------------------------------------------------
// 2. ERROR HANDLER (PROFESSIONAL 404 INTERFACE)
// --------------------------------------------------------------------------------------------
if (!$product_valid) {
    $pageTitle = "ไม่พบสินค้า | BNCC Market";
    require_once '../includes/header.php';
    ?>
    <style>
        .error-engine-surface {
            min-height: 80vh; display: flex; align-items: center; justify-content: center;
            background: radial-gradient(circle at center, var(--bncc-brand-50) 0%, transparent 70%);
            padding: 2rem;
        }
        .error-card-luxe {
            background: var(--theme-surface); border: 2px solid var(--theme-border);
            padding: 4rem; border-radius: 40px; text-align: center; max-width: 600px;
            box-shadow: var(--bncc-shadow-2xl); animation: pdSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .error-visual-shield {
            font-size: 8rem; margin-bottom: 2rem;
            background: linear-gradient(135deg, var(--bncc-brand-500), var(--bncc-info-500));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
    </style>
    <div class="error-engine-surface">
        <div class="error-card-luxe">
            <div class="error-visual-shield"><i class="fas fa-ghost"></i></div>
            <h1 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 1rem;">Opps! สินค้าหายไปแล้ว</h1>
            <p style="color: var(--theme-text-secondary); font-size: 1.1rem; line-height: 1.6; margin-bottom: 2.5rem;">
                สินค้าที่คุณกำลังเรียกดูอาจถูกลบ ย้ายหมวดหมู่ หรือถูกระงับการขายโดยผู้ดูแลระบบ 
                กรุณาลองค้นหาสินค้าอื่นที่น่าสนใจในตลาดกลางของเรา
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <a href="index.php" class="pd-btn-primary" style="padding: 1rem 2rem; border-radius: 15px; text-decoration: none; font-weight: 800;">
                    <i class="fas fa-home"></i> กลับสู่หน้าหลัก
                </a>
                <button onclick="history.back()" class="pd-btn-ghost" style="padding: 1rem 2rem; border-radius: 15px; border: 2px solid var(--theme-border); font-weight: 800; cursor: pointer;">
                    <i class="fas fa-arrow-left"></i> ย้อนกลับ
                </button>
            </div>
        </div>
    </div>
    <?php
    require_once '../includes/footer.php';
    exit();
}

// --------------------------------------------------------------------------------------------
// 3. LOGIC MODULE: GALLERY & ANALYTICS
// --------------------------------------------------------------------------------------------
// ดึงรูปภาพประกอบ (รองรับสูงสุด 5 รูป)
$img_stmt = $db->prepare("SELECT image_path, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC LIMIT 5");
$img_stmt->execute([$product_id]);
$product_images = $img_stmt->fetchAll();

if (count($product_images) === 0) {
    $product_images[] = ['image_path' => $product['image_url'], 'is_main' => 1];
}
$main_image_src = $product_images[0]['image_path'];

// ระบบ View Counter (กันปั๊มยอดวิว)
if (!isset($_SESSION['viewed_products'])) { $_SESSION['viewed_products'] = []; }
if (!in_array($product_id, $_SESSION['viewed_products'])) {
    $db->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product_id]);
    $_SESSION['viewed_products'][] = $product_id;
}

// ข้อมูล Rating & Tags
$rating_data = $db->prepare("SELECT AVG(rating) as avg, COUNT(*) as total FROM reviews WHERE product_id = ? AND is_deleted = 0");
$rating_data->execute([$product_id]);
$rating_sum = $rating_data->fetch();
$avg_rating = round($rating_sum['avg'] ?? 0, 1);
$total_reviews = $rating_sum['total'];

$tag_stmt = $db->prepare("SELECT t.tag_name FROM tags t JOIN product_tag_map ptm ON t.id = ptm.tag_id WHERE ptm.product_id = ?");
$tag_stmt->execute([$product_id]);
$tags = $tag_stmt->fetchAll();

// เช็คสถานะ Wishlist
$is_wish = false;
if (isLoggedIn()) {
    $c_wish = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $c_wish->execute([$user_id, $product_id]);
    $is_wish = $c_wish->fetch() ? true : false;
}

// --------------------------------------------------------------------------------------------
// 4. JAVASCRIPT & CSS INTERACTION REPOSITORIES
// --------------------------------------------------------------------------------------------
require_once '../includes/header.php';
?>

<style>
    /* 🎨 SECTION 1: CSS CUSTOM PROPERTIES (DESIGN TOKENS) */
    :root {
        --pd-primary: #4f46e5;
        --pd-primary-hover: #4338ca;
        --pd-primary-glow: rgba(79, 70, 229, 0.4);
        --pd-success: #10b981;
        --pd-danger: #ef4444;
        --pd-warning: #f59e0b;
        --pd-info: #3b82f6;
        --pd-card-bg: #ffffff;
        --pd-base-bg: #f8fafc;
        --pd-border: #e2e8f0;
        --pd-text-main: #0f172a;
        --pd-text-sub: #64748b;
        --pd-radius-massive: 48px;
        --pd-radius-card: 32px;
        --pd-radius-item: 16px;
        --pd-font-black: 900;
        --pd-font-bold: 700;
        --pd-transition-smooth: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .dark-theme {
        --pd-card-bg: #111827;
        --pd-base-bg: #030712;
        --pd-border: #1f2937;
        --pd-text-main: #f8fafc;
        --pd-text-sub: #94a3b8;
    }

    /* 🎨 SECTION 2: STRUCTURAL FOUNDATION */
    .pd-page-container {
        max-width: 1400px;
        margin: 40px auto;
        padding: 0 30px;
        opacity: 0;
        transform: translateY(20px);
        animation: pdEntry 0.8s var(--pd-transition-smooth) forwards;
    }

    @keyframes pdEntry { to { opacity: 1; transform: translateY(0); } }

    /* 🎨 SECTION 3: PRODUCT LUXE CARD */
    .pd-luxe-card {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 70px;
        background: var(--pd-card-bg);
        border: 2px solid var(--pd-border);
        border-radius: var(--pd-radius-massive);
        padding: 60px;
        box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.08);
        margin-bottom: 60px;
        position: relative;
    }

    /* 🎨 SECTION 4: FLUID GALLERY SYSTEM */
    .gallery-engine { display: flex; flex-direction: column; gap: 24px; position: sticky; top: 120px; }

    .img-master-viewport {
        position: relative;
        border-radius: var(--pd-radius-card);
        overflow: hidden;
        background: #000;
        aspect-ratio: 1/1;
        border: 1px solid var(--pd-border);
        box-shadow: var(--bncc-shadow-xl);
    }

    .img-master-viewport img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform 0.8s var(--pd-transition-smooth), opacity 0.3s ease;
    }

    .img-master-viewport:hover img { transform: scale(1.08); }
    .img-swap-active { opacity: 0; transform: scale(0.96) !important; }

    .thumb-matrix {
        display: flex; gap: 15px; overflow-x: auto; padding: 10px 5px;
        scrollbar-width: none; -ms-overflow-style: none;
    }
    .thumb-matrix::-webkit-scrollbar { display: none; }

    .thumb-trigger {
        width: 90px; height: 90px; flex-shrink: 0; border-radius: 18px;
        border: 3px solid transparent; cursor: pointer; overflow: hidden;
        transition: var(--pd-transition-smooth); opacity: 0.5;
        background: var(--pd-border);
    }

    .thumb-trigger img { width: 100%; height: 100%; object-fit: cover; }
    .thumb-trigger:hover { opacity: 0.8; transform: translateY(-3px); }
    .thumb-trigger.is-active {
        border-color: var(--pd-primary); opacity: 1;
        transform: translateY(-5px) scale(1.08);
        box-shadow: 0 10px 20px var(--pd-primary-glow);
    }

    /* 🎨 SECTION 5: PRODUCT INFORMATION STYLES */
    .pd-meta-header { margin-bottom: 30px; }
    .pd-tag-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 16px; background: var(--pd-primary-soft);
        color: var(--pd-primary); border-radius: 50px;
        font-size: 0.75rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: 1px; margin-bottom: 15px;
    }

    .pd-product-title {
        font-size: 3.8rem; font-weight: 900; letter-spacing: -2.5px;
        line-height: 0.95; margin-bottom: 25px; color: var(--pd-text-main);
    }

    .pd-price-display {
        font-size: 3.2rem; font-weight: 900; color: var(--pd-primary);
        margin: 35px 0; display: flex; align-items: center; gap: 15px;
        letter-spacing: -1.5px;
    }

    .pd-desc-block { margin: 40px 0; }
    .pd-desc-label {
        font-size: 0.7rem; font-weight: 900; color: var(--pd-text-sub);
        text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px;
        display: block;
    }
    .pd-desc-text {
        font-size: 1.15rem; line-height: 1.8; color: var(--pd-text-main);
        opacity: 0.85;
    }

    /* 🎨 SECTION 6: ENTERPRISE ACTION BUTTONS */
    .pd-actions-cluster { display: flex; gap: 15px; margin-top: 50px; }

    .pd-btn-primary {
        flex: 1; padding: 24px; border-radius: 20px; font-weight: 900;
        font-size: 1.25rem; background: var(--pd-primary); color: #fff !important;
        border: none; cursor: pointer; transition: var(--pd-transition-smooth);
        display: flex; align-items: center; justify-content: center; gap: 12px;
        box-shadow: 0 15px 30px var(--pd-primary-glow);
    }

    .pd-btn-primary:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px var(--pd-primary-glow);
    }

    .pd-btn-secondary {
        padding: 24px 35px; border-radius: 20px; background: var(--pd-card-bg);
        border: 2px solid var(--pd-border); color: var(--pd-text-main);
        font-weight: 800; font-size: 1.25rem; transition: 0.3s;
        cursor: pointer; text-decoration: none; display: flex; align-items: center;
    }

    .pd-btn-secondary:hover { border-color: var(--pd-primary); color: var(--pd-primary); }

    .pd-btn-wish {
        width: 75px; height: 75px; border-radius: 20px; background: var(--pd-base-bg);
        border: 2px solid var(--pd-border); color: var(--pd-text-sub);
        font-size: 1.8rem; cursor: pointer; transition: 0.3s;
    }

    .pd-btn-wish.is-active { color: var(--pd-danger); border-color: var(--pd-danger); background: #fef2f2; }

    /* 🎨 SECTION 7: SHOP CONTEXT COMPONENT */
    .shop-luxe-portal {
        margin-top: 50px; padding: 30px; border-radius: 28px;
        background: var(--pd-base-bg); border: 2px solid var(--pd-border);
        display: flex; align-items: center; justify-content: space-between;
    }

    .shop-identity { display: flex; align-items: center; gap: 20px; }
    .shop-visual {
        width: 65px; height: 65px; background: var(--pd-primary);
        border-radius: 18px; color: white; display: flex;
        align-items: center; justify-content: center; font-size: 1.8rem;
    }

    .badge-enterprise {
        background: var(--pd-primary); color: white; font-size: 0.65rem;
        padding: 3px 10px; border-radius: 6px; font-weight: 900;
        text-transform: uppercase; margin-left: 8px; vertical-align: middle;
    }

    /* 🎨 SECTION 8: REVIEW STREAM SYSTEM */
    .pd-review-wrapper { margin-top: 100px; }
    .pd-section-header {
        display: flex; justify-content: space-between; align-items: flex-end;
        padding-bottom: 30px; border-bottom: 3px solid var(--pd-border);
        margin-bottom: 50px;
    }

    .score-giant-badge {
        padding: 12px 30px; background: var(--pd-warning); border-radius: 50px;
        color: #000; font-weight: 900; font-size: 1.5rem;
        box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3);
    }

    .review-entity-card {
        background: var(--pd-card-bg); border: 2px solid var(--pd-border);
        border-radius: 32px; padding: 40px; margin-bottom: 30px;
        transition: var(--pd-transition-smooth); opacity: 0; transform: scale(0.96);
    }
    .review-entity-card.revealed { opacity: 1; transform: scale(1); }

    .author-avatar {
        width: 70px; height: 70px; border-radius: 20px; border: 3px solid var(--pd-border);
        object-fit: cover;
    }

    /* 🎨 SECTION 9: RESPONSIVE MEDIA ARCHITECTURE */
    @media (max-width: 1100px) {
        .pd-luxe-card { grid-template-columns: 1fr; padding: 40px; gap: 40px; }
        .pd-product-title { font-size: 2.8rem; }
    }

    @media (max-width: 768px) {
        .pd-page-container { padding: 0 15px; }
        .pd-luxe-card { padding: 25px; border-radius: 30px; }
        .pd-title-h1 { font-size: 2.2rem; }
        .pd-actions-cluster { flex-direction: column; }
        .pd-btn-wish { width: 100%; height: 60px; }
        .shop-luxe-portal { flex-direction: column; gap: 20px; text-align: center; }
    }

    /* 🎨 SECTION 10: MODAL & UTILITY */
    .pd-modal-mask {
        position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px);
        display: none; align-items: center; justify-content: center;
    }
    .pd-modal-surface {
        background: var(--pd-card-bg); padding: 50px; border-radius: 40px;
        width: 90%; max-width: 500px; border: 2px solid var(--pd-border);
        animation: pdSlideUp 0.5s var(--pd-transition-smooth);
    }
    @keyframes pdSlideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }

</style>

<div class="pd-page-container">

    <div class="pd-luxe-card">
        
        <div class="pd-gallery-zone">
            <div class="gallery-engine">
                <div class="img-master-viewport" id="galleryFrame">
                    <img id="mainHeroImage" src="../assets/images/products/<?= e($main_image_src) ?>" alt="<?= e($product['title']) ?>">
                    
                    <?php if ($total_reviews > 0): ?>
                        <div style="position: absolute; top: 30px; left: 30px; background: rgba(0,0,0,0.85); color: #fff; padding: 12px 20px; border-radius: 18px; font-weight: 900; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
                            <i class="fas fa-star" style="color: var(--pd-warning);"></i> <?= $avg_rating ?> 
                            <span style="font-weight: 600; font-size: 0.8rem; opacity: 0.7; margin-left: 5px;">(<?= $total_reviews ?>)</span>
                        </div>
                    <?php endif; ?>

                    <div style="position: absolute; bottom: 30px; right: 30px; background: rgba(255,255,255,0.9); color: #000; padding: 10px 20px; border-radius: 15px; font-weight: 900; font-size: 0.85rem; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                        <i class="fas fa-chart-line ui-mr-1"></i> <?= number_format($product['views']) ?> VIEWERS
                    </div>
                </div>

                <?php if (count($product_images) > 1): ?>
                <div class="thumb-matrix" id="thumbScroller">
                    <?php foreach ($product_images as $idx => $img): ?>
                        <div class="thumb-trigger <?= $idx === 0 ? 'is-active' : '' ?>" 
                             onclick="handleGallerySwap('../assets/images/products/<?= e($img['image_path']) ?>', this)">
                            <img src="../assets/images/products/<?= e($img['image_path']) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="pd-info-stack ui-flex ui-flex-col">
            <div class="pd-meta-header">
                <span class="pd-tag-pill"><i class="fas fa-fingerprint"></i> BNCC Verified</span>
                <h1 class="pd-product-title"><?= e($product['title']) ?></h1>
                
                <div class="ui-flex ui-gap-2 ui-mb-4">
                    <span class="ui-badge ui-badge-primary"><i class="fas fa-university ui-mr-1"></i> วิทยาลัยพณิชยการบางนา</span>
                    <?php if($product['status'] === 'out-of-stock'): ?>
                        <span class="ui-badge ui-badge-danger">OUT OF STOCK</span>
                    <?php else: ?>
                        <span class="ui-badge ui-badge-success">พร้อมจำหน่าย</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pd-price-display">
                ฿<?= number_format($product['price'], 2) ?>
            </div>

            <div class="pd-desc-block">
                <label class="pd-desc-label">Item Specifications & Description</label>
                <p class="pd-desc-text"><?= nl2br(e($product['description'])) ?></p>
            </div>

            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 40px;">
                <?php foreach ($tags as $t): ?>
                    <span style="background: var(--pd-base-bg); padding: 8px 18px; border-radius: 14px; font-weight: 800; font-size: 0.8rem; color: var(--pd-text-sub); border: 1px solid var(--pd-border);">
                        #<?= e($t['tag_name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <div class="pd-actions-cluster ui-mt-auto">
                <?php if ($user_id && $user_id != $product['owner_id']): ?>
                    <form method="POST" style="flex: 1;">
                        <button type="submit" name="place_order" class="pd-btn-primary">
                            <i class="fas fa-handshake"></i> นัดรับสินค้านี้
                        </button>
                    </form>
                    <a href="chat.php?user=<?= $product['owner_id'] ?>" class="pd-btn-secondary" title="คุยกับคนขาย">
                        <i class="fas fa-comment-dots"></i>
                    </a>
                <?php elseif (!$user_id): ?>
                    <a href="../auth/login.php" class="pd-btn-primary" style="text-decoration: none;">
                        <i class="fas fa-user-lock"></i> เข้าสู่ระบบเพื่อสั่งซื้อ
                    </a>
                <?php endif; ?>

                <button id="dynamicWishAction" data-id="<?= $product['id'] ?>" class="pd-btn-wish <?= $is_wish ? 'is-active' : '' ?>">
                    <i class="<?= $is_wish ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
            </div>

            <div class="pd-shop-portal">
                <div class="shop-identity">
                    <div class="shop-visual"><i class="fas fa-shopping-basket"></i></div>
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 900; color: var(--pd-primary); text-transform: uppercase; margin-bottom: 2px;">Shop Profile</div>
                        <a href="shop_profile.php?id=<?= $product['shop_id'] ?>" style="text-decoration: none; color: var(--pd-text-main); font-weight: 900; font-size: 1.3rem;">
                            <?= e($product['shop_name']) ?>
                        </a>
                        <span class="badge-enterprise"><?= htmlspecialchars($product['owner_role']) ?></span>
                    </div>
                </div>
                <div style="display: flex; gap: 12px;">
                    <?php if($product['contact_line']): ?>
                        <a href="https://line.me/ti/p/~<?= e($product['contact_line']) ?>" target="_blank" style="color: #06c755; font-size: 2rem;"><i class="fab fa-line"></i></a>
                    <?php endif; ?>
                    <?php if($product['contact_ig']): ?>
                        <a href="https://www.instagram.com/<?= e($product['contact_ig']) ?>/" target="_blank" style="color: #e1306c; font-size: 2rem;"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                <div class="admin-shield-panel ui-mt-8">
                    <h4 style="color: var(--pd-danger); font-weight: 900; font-size: 0.75rem; margin-bottom: 15px; text-transform: uppercase;"><i class="fas fa-shield-alt"></i> Moderator Control Panel</h4>
                    <div class="ui-flex ui-gap-3">
                        <button onclick="triggerProductSuspend()" class="ui-btn ui-btn-danger ui-w-full" style="padding: 15px; border-radius: 14px; font-weight: 800;">
                            <i class="fas fa-ban"></i> SUSPEND THIS LISTING
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <section class="pd-review-wrapper">
        <div class="pd-section-header">
            <div>
                <h2 style="font-size: 2.5rem; font-weight: 900; letter-spacing: -1.5px; margin-bottom: 5px;">Community Feedback</h2>
                <p style="color: var(--pd-text-sub); font-weight: 700;">คะแนนความพึงพอใจจากผู้ใช้ระบบคนอื่นๆ</p>
            </div>
            <div class="score-giant-badge">★ <?= $avg_rating ?></div>
        </div>

        <?php if (isLoggedIn()): ?>
            <?php if (canUserReview($user_id, $product_id)['status']): ?>
                <div class="pd-luxe-card" style="grid-template-columns: 1fr; padding: 45px; border-style: dashed; border-width: 3px; background: var(--pd-bg);">
                    <h3 style="font-weight: 900; margin-bottom: 25px;">แชร์ประสบการณ์ของคุณ</h3>
                    <form method="POST">
                        <div class="ui-mb-6">
                            <label class="pd-desc-label">ให้คะแนนความพึงพอใจ</label>
                            <div class="pd-rating-input" id="ratingInputGroup">
                                <?php for($i=5; $i>=1; $i--): ?>
                                    <input type="radio" id="luxeStar<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                    <label for="luxeStar<?= $i ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="ui-mb-6">
                            <textarea name="comment" class="ui-input" required style="min-height: 150px; border-radius: 20px; padding: 25px; font-weight: 600; border: 2px solid var(--pd-border);" placeholder="สินค้านี้เป็นอย่างไรบ้าง? การนัดรับราบรื่นไหม?"></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="pd-btn-primary" style="width: auto; padding: 18px 60px;">
                            SUBMIT MY REVIEW
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div style="background: var(--pd-bg); border: 2px dashed var(--pd-border); padding: 40px; border-radius: 30px; text-align: center; margin-bottom: 40px;">
                    <p style="font-weight: 800; color: var(--pd-primary); margin: 0;">
                        <i class="fas fa-info-circle"></i> <?= canUserReview($user_id, $product_id)['message'] ?>
                    </p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div id="reviewMasterStream">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $idx => $r): 
                    $r_img = !empty($r['profile_img']) ? "../assets/images/profiles/".$r['profile_img'] : "../assets/images/profiles/default_profile.png";
                ?>
                    <div class="pd-review-card scroll-animate" style="transition-delay: <?= $idx * 0.1 ?>s;">
                        <div style="display: flex; gap: 25px;">
                            <img src="<?= $r_img ?>" class="author-avatar">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h4 style="font-weight: 900; font-size: 1.2rem;"><?= e($r['fullname']) ?> <?= getUserBadge($r['author_role']) ?></h4>
                                        <div style="color: var(--pd-warning); font-size: 0.9rem; margin-top: 5px;">
                                            <?php for($s=0; $s<$r['rating']; $s++) echo '<i class="fas fa-star"></i>'; ?>
                                        </div>
                                    </div>
                                    <span style="font-size: 0.75rem; font-weight: 800; color: var(--pd-text-sub); opacity: 0.6;">
                                        <?= date('M d, Y', strtotime($r['created_at'])) ?>
                                    </span>
                                </div>
                                <p style="margin-top: 20px; font-size: 1.1rem; line-height: 1.8; color: var(--pd-text-main); opacity: 0.9;">
                                    <?= nl2br(e($r['comment'])) ?>
                                </p>

                                <?php if ($user_id == $r['author_id']): ?>
                                    <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--pd-border); display: flex; gap: 20px;">
                                        <button onclick="openReviewEditor(<?= $r['id'] ?>, <?= $r['rating'] ?>, '<?= e(addslashes($r['comment'])) ?>')" style="background: none; border: none; color: var(--pd-primary); font-weight: 900; cursor: pointer; font-size: 0.85rem;"><i class="fas fa-edit"></i> แก้ไขข้อมูล</button>
                                        <a href="?id=<?= $product_id ?>&action=delete_my_review&rev_id=<?= $r['id'] ?>" onclick="return confirm('ลบรีวิวถาวร?')" style="text-decoration: none; color: var(--pd-danger); font-weight: 900; font-size: 0.85rem;"><i class="fas fa-trash"></i> ลบทิ้ง</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 100px 40px; border: 3px dashed var(--pd-border); border-radius: 40px; background: var(--pd-card-bg);">
                    <div style="font-size: 5rem; opacity: 0.1; margin-bottom: 20px;"><i class="fas fa-comments"></i></div>
                    <h3 style="font-weight: 900; opacity: 0.4;">Be the first to share feedback!</h3>
                </div>
            <?php endif; ?>
        </div>
    </section>

</div>

<div id="pdReviewModal" class="pd-modal-mask">
    <div class="pd-modal-surface">
        <h2 style="font-weight: 900; margin-bottom: 25px; letter-spacing: -1px;">แก้ไขความเห็นของคุณ</h2>
        <form method="POST">
            <input type="hidden" name="review_id" id="editRevId">
            <div style="margin-bottom: 20px;">
                <label class="pd-desc-label">เปลี่ยนคะแนน</label>
                <select name="rating" id="editRevRating" class="ui-input" style="padding: 15px;">
                    <option value="5">5 ดาว - ยอดเยี่ยม</option>
                    <option value="4">4 ดาว - ดีมาก</option>
                    <option value="3">3 ดาว - ปานกลาง</option>
                    <option value="2">2 ดาว - พอใช้</option>
                    <option value="1">1 ดาว - ควรปรับปรุง</option>
                </select>
            </div>
            <div style="margin-bottom: 30px;">
                <textarea name="comment" id="editRevComment" class="ui-input" style="min-height: 140px; padding: 20px; font-weight: 600;"></textarea>
            </div>
            <div style="display: flex; gap: 15px;">
                <button type="button" onclick="closeEngineModal('pdReviewModal')" class="pd-btn-secondary" style="flex: 1; justify-content: center; padding: 18px;">ยกเลิก</button>
                <button type="submit" name="edit_review_submit" class="pd-btn-primary" style="flex: 1; padding: 18px;">อัปเดตเลย</button>
            </div>
        </form>
    </div>
</div>

<div id="pdAdminModal" class="pd-modal-mask">
    <div class="pd-modal-surface" style="border-color: var(--pd-danger);">
        <h2 style="font-weight: 900; color: var(--pd-danger); margin-bottom: 10px;">ระงับการขายสินค้านี้</h2>
        <p style="font-weight: 600; color: var(--pd-text-sub); margin-bottom: 30px;">สินค้านี้จะถูกซ่อนจากหน้าตลาดกลางทันที</p>
        <form action="../admin/admin_delete_product.php" method="POST">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <div class="ui-mb-6">
                <label class="pd-desc-label">ระบุเหตุผล (Logging)</label>
                <textarea name="reason" required class="ui-input" style="min-height: 120px; padding: 20px;" placeholder="เช่น ขายสินค้าผิดกฎหมาย, ข้อมูลไม่เป็นความจริง..."></textarea>
            </div>
            <div style="display: flex; gap: 15px;">
                <button type="button" onclick="closeEngineModal('pdAdminModal')" class="pd-btn-secondary" style="flex: 1; justify-content: center; padding: 18px;">BACK</button>
                <button type="submit" class="pd-btn-primary" style="flex: 1; background: var(--pd-danger); padding: 18px;">SUSPEND NOW</button>
            </div>
        </form>
    </div>
</div>

<script>
    /**
     * MODULE 1: LIQUID GALLERY CONTROLLER
     * Handles image swapping with high-performance animations
     */
    const GalleryController = {
        main: document.getElementById('mainHeroImage'),
        frame: document.getElementById('galleryFrame'),
        
        swap(url, element) {
            if (this.main.src === url) return;

            // Update UI State
            document.querySelectorAll('.thumb-trigger').forEach(t => t.classList.remove('is-active'));
            element.classList.add('is-active');

            // Trigger Animation
            this.main.classList.add('img-swap-active');
            
            setTimeout(() => {
                this.main.src = url;
                this.main.classList.remove('img-swap-active');
            }, 150);
        }
    };

    function handleGallerySwap(u, e) { GalleryController.swap(u, e); }

    /**
     * MODULE 2: SCROLL REVEAL (INTERSECTION OBSERVER)
     * Manages staggered entrance animations for review entities
     */
    const revealOptions = { threshold: 0.15, rootMargin: "0px 0px -50px 0px" };
    const pdObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry, idx) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('revealed');
                }, idx * 100);
                pdObserver.unobserve(entry.target);
            }
        });
    }, revealOptions);

    document.querySelectorAll('.scroll-animate').forEach(el => pdObserver.observe(el));

    /**
     * MODULE 3: MODAL DYNAMICS
     */
    function openReviewEditor(id, rating, comment) {
        document.getElementById('editRevId').value = id;
        document.getElementById('editRevRating').value = rating;
        document.getElementById('editRevComment').value = comment;
        document.getElementById('pdReviewModal').style.display = 'flex';
    }

    function triggerProductSuspend() {
        document.getElementById('pdAdminModal').style.display = 'flex';
    }

    function closeEngineModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // Modal Close Hook
    window.onclick = function(event) {
        if (event.target.classList.contains('pd-modal-mask')) {
            event.target.style.display = 'none';
        }
    };

    /**
     * MODULE 4: REAL-TIME WISHLIST ENGINE
     */
    const wishNode = document.getElementById('dynamicWishAction');
    if (wishNode) {
        wishNode.addEventListener('click', async function() {
            const btn = this;
            const icon = btn.querySelector('i');
            const pid = btn.dataset.id;

            // Physical Feedback
            btn.style.transform = "scale(0.8) rotate(-10deg)";
            
            try {
                const response = await fetch('../auth/toggle_wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'product_id=' + pid
                });
                
                const data = await response.json();
                
                btn.style.transform = "scale(1) rotate(0)";
                
                if (data.status === 'added') {
                    btn.classList.add('is-active');
                    icon.className = 'fas fa-heart';
                    // Optional: Toast message logic here
                } else if (data.status === 'removed') {
                    btn.classList.remove('is-active');
                    icon.className = 'far fa-heart';
                }
            } catch (err) {
                console.error("Wishlist Engine Fault:", err);
                window.location.href = '../auth/login.php';
            }
        });
    }

    /**
     * MODULE 5: ANALYTICS & UTILITY
     */
    console.log("%c BNCC Product Engine V5 Loaded ", "background: #4f46e5; color: #fff; font-weight: 900;");
</script>

<?php require_once '../includes/footer.php'; ?>