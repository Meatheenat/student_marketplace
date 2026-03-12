<?php
/**
 * ============================================================================================
 * 💎 BNCC MARKET - ENTERPRISE PRODUCT ARCHITECTURE (V 5.5.0)
 * ============================================================================================
 * Architecture: Model-View-Controller (Integrated Engine)
 * Design: High-Contrast Solid UX / Liquid Animations
 * Features: Multi-Layer Gallery, Sentiment Reviews, Admin Shield, Logic-Safe 404
 * --------------------------------------------------------------------------------------------
 */

require_once '../includes/functions.php';

// --------------------------------------------------------------------------------------------
// [CONTROLLER] 1. DATA ACQUISITION & INTEGRITY CHECK
// --------------------------------------------------------------------------------------------
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;
$is_product_available = false;
$product = null;

if ($product_id) {
    // 🎯 ดึงข้อมูลสินค้าพร้อม Join ข้อมูลร้านค้าและบทบาทเจ้าของ (Anti-Broken Logic)
    $stmt = $db->prepare("SELECT p.*, s.shop_name, s.contact_line, s.contact_ig, s.line_user_id, 
                          s.user_id as owner_id, u.role as owner_role 
                          FROM products p 
                          JOIN shops s ON p.shop_id = s.id 
                          JOIN users u ON s.user_id = u.id
                          WHERE p.id = ? AND p.is_deleted = 0");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
        $is_product_available = true;
    }
}

// --------------------------------------------------------------------------------------------
// [CONTROLLER] 2. SUB-RESOURCE INITIALIZATION (ONLY IF PRODUCT EXISTS)
// --------------------------------------------------------------------------------------------
if ($is_product_available) {
    // 🖼️ ดึงรูปภาพประกอบ (Maximum 5 images cluster)
    $img_stmt = $db->prepare("SELECT image_path, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC LIMIT 5");
    $img_stmt->execute([$product_id]);
    $product_images = $img_stmt->fetchAll();

    if (count($product_images) === 0) {
        $product_images[] = ['image_path' => $product['image_url'], 'is_main' => 1];
    }
    $initial_display_image = $product_images[0]['image_path'];

    // 📈 View Engine (Session-locked to prevent botting)
    if (!isset($_SESSION['viewed_stack'])) { $_SESSION['viewed_stack'] = []; }
    if (!in_array($product_id, $_SESSION['viewed_stack'])) {
        $db->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product_id]);
        $_SESSION['viewed_stack'][] = $product_id;
    }

    // ⭐ Rating & Tags Analysis
    $rating_query = $db->prepare("SELECT AVG(rating) as avg, COUNT(*) as total FROM reviews WHERE product_id = ? AND is_deleted = 0");
    $rating_query->execute([$product_id]);
    $rating_summary = $rating_query->fetch();
    $global_avg = round($rating_summary['avg'] ?? 0, 1);
    $global_total = $rating_summary['total'];

    $tag_query = $db->prepare("SELECT t.tag_name FROM tags t JOIN product_tag_map ptm ON t.id = ptm.tag_id WHERE ptm.product_id = ?");
    $tag_query->execute([$product_id]);
    $tags_stack = $tag_query->fetchAll();

    // ❤️ Wishlist Persistence Check
    $is_on_wishlist = false;
    if (isLoggedIn()) {
        $w_check = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $w_check->execute([$user_id, $product_id]);
        $is_on_wishlist = $w_check->fetch() ? true : false;
    }

    // --------------------------------------------------------------------------------------------
    // [CONTROLLER] 3. POST-ACTION ROUTING (REVIEWS, ORDERS, EDITS)
    // --------------------------------------------------------------------------------------------
    
    // 🛒 3.1 Order Dispatcher
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
        if (!isLoggedIn()) redirect('../auth/login.php');
        if ($user_id != $product['owner_id']) {
            $order_stmt = $db->prepare("INSERT INTO orders (buyer_id, shop_id, product_id, created_at) VALUES (?, ?, ?, NOW())");
            if ($order_stmt->execute([$user_id, $product['shop_id'], $product_id])) {
                $notif_payload = "🛒 นัดรับสินค้าใหม่: {$product['title']} โดยคุณ {$_SESSION['fullname']}";
                sendNotification($product['owner_id'], 'order', $notif_payload, "../seller/dashboard.php");
                if (!empty($product['line_user_id'])) {
                    sendLineMessagingAPI($product['line_user_id'], $notif_payload);
                }
                $_SESSION['flash_message'] = "ส่งคำร้องนัดรับสินค้าสำเร็จ! โปรดตรวจสอบสถานะในประวัติการซื้อ";
                $_SESSION['flash_type'] = "success";
            }
        } else {
            $_SESSION['flash_message'] = "ระบบไม่อนุญาตให้สั่งซื้อสินค้าของตนเอง";
            $_SESSION['flash_type'] = "warning";
        }
        redirect("product_detail.php?id=$product_id");
    }

    // ⭐ 3.2 Review Submission (Anti-Spam Filtered)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
        $spam_guard = canUserReview($user_id, $product_id);
        if (!$spam_guard['status']) {
            $_SESSION['flash_message'] = $spam_guard['message'];
            $_SESSION['flash_type'] = "danger";
        } else {
            $review_stmt = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($review_stmt->execute([$product_id, $user_id, $_POST['rating'], trim($_POST['comment'])])) {
                sendNotification($product['owner_id'], 'review', "⭐️ ได้รับรีวิวใหม่สำหรับ {$product['title']}", "product_detail.php?id=$product_id");
                $_SESSION['flash_message'] = "ขอบคุณสำหรับการรีวิวสินค้า";
                $_SESSION['flash_type'] = "success";
            }
        }
        redirect("product_detail.php?id=$product_id");
    }

    // ✏️ 3.3 Review Patch (Edition)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_review_submit'])) {
        $patch_stmt = $db->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ? AND user_id = ?");
        if ($patch_stmt->execute([(int)$_POST['rating'], trim($_POST['comment']), (int)$_POST['review_id'], $user_id])) {
            $_SESSION['flash_message'] = "อัปเดตรีวิวของคุณเรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "success";
        }
        redirect("product_detail.php?id=$product_id");
    }

    // 🗑️ 3.4 Review Soft Delete
    if (isset($_GET['action']) && $_GET['action'] === 'delete_my_review') {
        $db->prepare("UPDATE reviews SET is_deleted = 1 WHERE id = ? AND user_id = ?")->execute([(int)$_GET['rev_id'], $user_id]);
        $_SESSION['flash_message'] = "ลบการแสดงความคิดเห็นแล้ว";
        $_SESSION['flash_type'] = "success";
        redirect("product_detail.php?id=$product_id");
    }

    // --------------------------------------------------------------------------------------------
    // [CONTROLLER] 4. FINAL VIEW PREPARATION
    // --------------------------------------------------------------------------------------------
    $reviews_stmt = $db->prepare("SELECT r.*, u.fullname, u.profile_img, u.role as author_role, u.id as author_id 
                                  FROM reviews r 
                                  JOIN users u ON r.user_id = u.id 
                                  WHERE r.product_id = ? AND r.is_deleted = 0 
                                  ORDER BY r.created_at DESC");
    $reviews_stmt->execute([$product_id]);
    $all_reviews = $reviews_stmt->fetchAll();
}

$pageTitle = $is_product_available ? $product['title'] : "Product Not Found";
require_once '../includes/header.php';
?>

<style>
    /* 🎨 DESIGN TOKENS & SYSTEM VARIABLES */
    :root {
        --bncc-primary: #4f46e5;
        --bncc-primary-dark: #4338ca;
        --bncc-primary-glow: rgba(79, 70, 229, 0.45);
        --bncc-accent: #10b981;
        --bncc-danger: #ef4444;
        --bncc-warning: #f59e0b;
        --bncc-surface: #ffffff;
        --bncc-base-bg: #f1f5f9;
        --bncc-border: #e2e8f0;
        --bncc-text-primary: #0f172a;
        --bncc-text-secondary: #475569;
        --bncc-radius-max: 50px;
        --bncc-radius-luxe: 32px;
        --bncc-radius-standard: 18px;
        --bncc-shadow-luxe: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
        --bncc-transition-fluid: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .dark-theme {
        --bncc-surface: #111827;
        --bncc-base-bg: #030712;
        --bncc-border: #1f2937;
        --bncc-text-primary: #f8fafc;
        --bncc-text-secondary: #94a3b8;
    }

    /* 🌊 GLOBAL WRAPPER & ANIMATIONS */
    .pd-enterprise-wrapper {
        max-width: 1440px; margin: 50px auto; padding: 0 30px;
        opacity: 0; transform: translateY(30px);
        animation: pdMasterReveal 1s var(--bncc-transition-fluid) forwards;
    }

    @keyframes pdMasterReveal { to { opacity: 1; transform: translateY(0); } }

    /* 🛡️ 404 NOT FOUND - PROFESSIONAL LAYER */
    .pd-error-container {
        min-height: 75vh; display: flex; align-items: center; justify-content: center;
        background: var(--bncc-surface); border-radius: var(--bncc-radius-luxe);
        border: 2px solid var(--bncc-border); margin: 20px; box-shadow: var(--bncc-shadow-luxe);
        position: relative; overflow: hidden;
    }

    .pd-error-container::before {
        content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
        background: radial-gradient(circle, var(--bncc-primary-soft) 0%, transparent 70%);
        opacity: 0.3; pointer-events: none;
    }

    .pd-error-content { text-align: center; max-width: 600px; padding: 40px; z-index: 1; }
    .pd-error-visual { font-size: 8rem; margin-bottom: 30px; filter: drop-shadow(0 10px 20px var(--bncc-primary-glow)); }
    .pd-error-h1 { font-size: 3rem; font-weight: 900; letter-spacing: -2px; margin-bottom: 15px; }

    /* 🖼️ HIGH-FIDELITY GALLERY MODULE */
    .pd-main-architecture {
        display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 70px;
        background: var(--bncc-surface); border: 2px solid var(--bncc-border);
        border-radius: var(--bncc-radius-luxe); padding: 60px;
        box-shadow: var(--bncc-shadow-luxe); margin-bottom: 70px;
    }

    .gallery-core-module { display: flex; flex-direction: column; gap: 25px; position: sticky; top: 120px; }

    .gallery-master-frame {
        position: relative; border-radius: var(--bncc-radius-standard); overflow: hidden;
        background: #000; aspect-ratio: 1/1; border: 1px solid var(--bncc-border);
        cursor: crosshair; box-shadow: var(--bncc-shadow-luxe);
    }

    .gallery-master-frame img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform 0.8s var(--bncc-transition-fluid), opacity 0.3s ease;
    }

    .gallery-master-frame:hover img { transform: scale(1.1); }
    .gallery-transition-state { opacity: 0.2; transform: scale(0.97) rotate(-1deg) !important; }

    .thumb-cluster-track {
        display: flex; gap: 15px; overflow-x: auto; padding: 10px 5px;
        scrollbar-width: none; -ms-overflow-style: none;
    }
    .thumb-cluster-track::-webkit-scrollbar { display: none; }

    .thumb-trigger-node {
        width: 90px; height: 90px; flex-shrink: 0; border-radius: 16px;
        border: 3px solid transparent; cursor: pointer; overflow: hidden;
        transition: var(--bncc-transition-fluid); opacity: 0.4;
        background: var(--bncc-border); transform: scale(0.95);
    }

    .thumb-trigger-node img { width: 100%; height: 100%; object-fit: cover; }
    .thumb-trigger-node:hover { opacity: 0.8; transform: scale(1); }
    .thumb-trigger-node.is-active {
        border-color: var(--bncc-primary); opacity: 1;
        transform: translateY(-5px) scale(1.1);
        box-shadow: 0 10px 25px var(--bncc-primary-glow);
    }

    /* 🏷️ INFORMATION & PRICE DISPLAY */
    .pd-content-blueprint { display: flex; flex-direction: column; }
    .pd-header-group { margin-bottom: 40px; }
    .pd-main-title { 
        font-size: 3.5rem; font-weight: 900; letter-spacing: -3px; 
        line-height: 0.95; margin-bottom: 25px; color: var(--bncc-text-primary);
    }
    .pd-badge-cloud { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 30px; }
    .pd-badge-cloud .ui-badge { padding: 6px 18px; font-weight: 800; letter-spacing: 0.5px; border-radius: 12px; }

    .pd-price-giant {
        font-size: 3.2rem; font-weight: 900; color: var(--bncc-primary);
        margin: 40px 0; display: flex; align-items: center; gap: 15px;
        letter-spacing: -2px;
    }

    .pd-price-giant::before {
        content: '฿'; font-size: 1.8rem; font-weight: 800; opacity: 0.6;
    }

    /* 🔘 INTERACTION BUTTONS (LUXE) */
    .pd-btn-cluster { display: flex; gap: 15px; margin-top: 60px; }

    .btn-luxe-primary {
        flex: 1; padding: 25px; border-radius: 20px; font-weight: 900;
        font-size: 1.3rem; background: var(--bncc-primary); color: #ffffff !important;
        border: none; cursor: pointer; transition: var(--bncc-transition-fluid);
        display: flex; align-items: center; justify-content: center; gap: 15px;
        box-shadow: 0 15px 35px var(--bncc-primary-glow);
    }

    .btn-luxe-primary:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 45px var(--bncc-primary-glow);
    }

    .btn-luxe-primary:active { transform: translateY(-2px); }

    .btn-luxe-chat {
        padding: 25px 35px; border-radius: 20px; background: var(--bncc-surface);
        border: 3px solid var(--bncc-border); color: var(--bncc-text-primary);
        font-weight: 900; font-size: 1.3rem; transition: 0.4s;
        cursor: pointer; text-decoration: none; display: flex; align-items: center;
    }

    .btn-luxe-chat:hover { border-color: var(--bncc-primary); color: var(--bncc-primary); transform: scale(1.05); }

    .btn-luxe-wish {
        width: 80px; height: 80px; border-radius: 22px; background: var(--bncc-base-bg);
        border: 3px solid var(--bncc-border); color: var(--bncc-text-secondary);
        font-size: 2rem; cursor: pointer; transition: 0.4s; display: flex; align-items: center; justify-content: center;
    }

    .btn-luxe-wish:hover { transform: rotate(15deg) scale(1.1); color: var(--bncc-danger); }
    .btn-luxe-wish.is-bookmarked { color: var(--bncc-danger); border-color: var(--bncc-danger); background: #fef2f2; animation: heartBeat 0.8s ease; }

    @keyframes heartBeat {
        0% { transform: scale(1); }
        25% { transform: scale(1.2); }
        50% { transform: scale(0.9); }
        100% { transform: scale(1); }
    }

    /* 🏪 MERCHANT CONTEXT */
    .pd-shop-nexus {
        margin-top: 50px; padding: 35px; border-radius: 30px;
        background: var(--bncc-base-bg); border: 2px solid var(--bncc-border);
        display: flex; align-items: center; justify-content: space-between;
        transition: 0.3s;
    }
    .pd-shop-nexus:hover { border-color: var(--bncc-primary); transform: translateX(5px); }

    .merchant-brand-shield {
        width: 70px; height: 70px; background: linear-gradient(135deg, var(--bncc-primary), var(--bncc-primary-dark));
        border-radius: 20px; color: #fff; display: flex; align-items: center; justify-content: center;
        font-size: 2rem; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);
    }

    /* ⭐ REVIEWS ECOSYSTEM */
    .pd-review-stream { margin-top: 100px; }
    .pd-section-label {
        display: flex; justify-content: space-between; align-items: center;
        padding-bottom: 25px; border-bottom: 4px solid var(--bncc-border);
        margin-bottom: 60px;
    }

    .pd-avg-score-luxe {
        background: var(--bncc-warning); color: #000; padding: 12px 35px;
        border-radius: 60px; font-weight: 900; font-size: 1.6rem;
        box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
    }

    .review-solid-card {
        background: var(--bncc-surface); border: 2px solid var(--bncc-border);
        border-radius: 35px; padding: 45px; margin-bottom: 35px;
        transition: 0.5s var(--bncc-transition-fluid); opacity: 0; transform: translateY(40px);
    }
    .review-solid-card.revealed { opacity: 1; transform: translateY(0); }

    .rating-visual-stars { color: var(--bncc-warning); font-size: 1.1rem; display: flex; gap: 4px; }

    /* 🛡️ ADMIN MODERATOR SHIELD */
    .moderator-security-strip {
        margin-top: 40px; padding: 30px; border-radius: 25px;
        border: 2px dashed var(--bncc-danger); background: rgba(239, 68, 68, 0.05);
    }

    /* 📱 RESPONSIVE BREAKPOINTS */
    @media (max-width: 1200px) {
        .pd-luxe-card { grid-template-columns: 1fr; padding: 40px; gap: 50px; }
        .pd-main-title { font-size: 2.8rem; }
    }

    @media (max-width: 600px) {
        .pd-page-container { padding: 0 15px; }
        .pd-luxe-card { padding: 25px; border-radius: 30px; }
        .pd-btn-cluster { flex-direction: column; }
        .btn-luxe-wish { width: 100%; height: 65px; }
        .pd-price-giant { font-size: 2.2rem; }
    }
</style>

<div class="pd-enterprise-wrapper">

    <?php if (!$is_product_available): ?>
        
        <div class="pd-error-container">
            <div class="pd-error-content">
                <div class="pd-error-visual"><i class="fas fa-shopping-basket ui-text-muted" style="opacity: 0.2;"></i></div>
                <h1 class="pd-error-h1">ไม่พบสินค้าในระบบ</h1>
                <p class="ui-text-sub ui-text-lg ui-mb-10">ขออภัย สินค้าชิ้นนี้อาจถูกจำหน่ายออกไปแล้ว หรือลิงก์การเข้าถึงไม่ถูกต้อง กรุณากลับไปตรวจสอบสินค้าอื่นๆ ที่ยังพร้อมให้บริการในขณะนี้</p>
                <div class="ui-flex ui-justify-center ui-gap-4">
                    <a href="index.php" class="pd-btn-primary ui-w-auto" style="padding: 18px 40px; text-decoration: none;">
                        <i class="fas fa-store"></i> ไปที่หน้าตลาดกลาง
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>

        <div class="pd-luxe-card">
            
            <div class="pd-gallery-zone">
                <div class="gallery-core-module">
                    <div class="gallery-master-frame" id="zoomEngine">
                        <img id="pdDisplayMaster" src="../assets/images/products/<?= e($initial_display_image) ?>" alt="<?= e($product['title']) ?>">
                        
                        <div style="position: absolute; top: 25px; right: 25px; background: rgba(0,0,0,0.8); color: #fff; padding: 10px 20px; border-radius: 15px; font-weight: 900; backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1);">
                            <i class="fas fa-fire ui-mr-1" style="color: #ff4757;"></i> POPULAR NOW
                        </div>

                        <?php if ($global_total > 0): ?>
                            <div style="position: absolute; bottom: 25px; left: 25px; background: rgba(255,255,255,0.95); color: #000; padding: 12px 22px; border-radius: 18px; font-weight: 900; box-shadow: var(--bncc-shadow-lg);">
                                <i class="fas fa-star" style="color: var(--bncc-warning);"></i> <?= $global_avg ?> / 5.0
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($product_images) > 1): ?>
                    <div class="thumb-cluster-track" id="galleryPaging">
                        <?php foreach ($product_images as $index => $img_obj): ?>
                            <div class="thumb-trigger-node <?= $index === 0 ? 'is-active' : '' ?>" 
                                 onclick="invokeGalleryShift('../assets/images/products/<?= e($img_obj['image_path']) ?>', this)">
                                <img src="../assets/images/products/<?= e($img_obj['image_path']) ?>" loading="lazy">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pd-content-blueprint">
                <div class="pd-header-group">
                    <div class="ui-flex ui-items-center ui-gap-3 ui-mb-4">
                        <span class="ui-badge ui-badge-primary">ID: <?= str_pad($product['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        <span class="ui-badge ui-badge-success"><i class="fas fa-shield-check"></i> สินค้าแนะนำ</span>
                    </div>
                    <h1 class="pd-main-title"><?= e($product['title']) ?></h1>
                    <p class="ui-text-sub ui-font-bold ui-text-lg">BNCC Original Campus Merchandise</p>
                </div>

                <div class="pd-price-giant">
                    <?= number_format($product['price'], 2) ?>
                </div>

                <div class="pd-desc-block">
                    <label class="pd-desc-label">Product Overview</label>
                    <div class="pd-desc-text"><?= nl2br(e($product['description'])) ?></div>
                </div>

                <div class="pd-badge-cloud">
                    <?php foreach ($tags_stack as $t_item): ?>
                        <span class="ui-badge ui-badge-secondary">#<?= e($t_item['tag_name']) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="pd-btn-cluster">
                    <?php if ($user_id && $user_id != $product['owner_id']): ?>
                        <form method="POST" style="flex: 1; display: flex;">
                            <button type="submit" name="place_order" class="btn-luxe-primary">
                                <i class="fas fa-shopping-cart"></i> นัดรับและชำระเงิน
                            </button>
                        </form>
                        <a href="chat.php?user=<?= $product['owner_id'] ?>" class="btn-luxe-chat" title="แชทคุยกับผู้ขาย">
                            <i class="fas fa-comment-alt-dots"></i>
                        </a>
                    <?php elseif (!$user_id): ?>
                        <a href="../auth/login.php" class="btn-luxe-primary" style="text-decoration: none;">
                            <i class="fas fa-lock"></i> โปรดเข้าสู่ระบบเพื่อสั่งซื้อ
                        </a>
                    <?php else: ?>
                        <a href="../seller/edit_product.php?id=<?= $product_id ?>" class="btn-luxe-primary" style="background: var(--bncc-warning); color: black !important; box-shadow: none;">
                            <i class="fas fa-edit"></i> แก้ไขข้อมูลสินค้าของคุณ
                        </a>
                    <?php endif; ?>

                    <button id="luxeWishEngine" data-id="<?= $product['id'] ?>" class="btn-luxe-wish <?= $is_on_wishlist ? 'is-bookmarked' : '' ?>">
                        <i class="<?= $is_on_wishlist ? 'fas' : 'far' ?> fa-heart"></i>
                    </button>
                </div>

                <div class="pd-shop-nexus">
                    <div class="shop-identity">
                        <div class="merchant-brand-shield"><i class="fas fa-store"></i></div>
                        <div>
                            <div class="ui-text-xs ui-font-black ui-text-primary ui-uppercase ui-mb-1">Merchant Portal</div>
                            <a href="shop_profile.php?id=<?= $product['shop_id'] ?>" style="text-decoration: none; color: var(--bncc-text-primary); font-weight: 900; font-size: 1.4rem;">
                                <?= e($product['shop_name']) ?> <?= getUserBadge($product['owner_role']) ?>
                            </a>
                            <div class="ui-flex ui-items-center ui-gap-2 ui-mt-2">
                                <?= getShopBadge($product['shop_id']) ?>
                                <span style="font-size: 0.75rem; font-weight: 700; color: var(--bncc-accent);"><i class="fas fa-check-circle"></i> ยืนยันตัวตนแล้ว</span>
                            </div>
                        </div>
                    </div>
                    <div class="ui-flex ui-gap-3">
                        <?php if(!empty($product['contact_line'])): ?>
                            <a href="https://line.me/ti/p/~<?= e($product['contact_line']) ?>" target="_blank" style="color: #06c755; font-size: 2.2rem; transition: 0.3s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                                <i class="fab fa-line"></i>
                            </a>
                        <?php endif; ?>
                        <?php if(!empty($product['contact_ig'])): ?>
                            <a href="https://www.instagram.com/<?= e($product['contact_ig']) ?>/" target="_blank" style="color: #e1306c; font-size: 2.2rem; transition: 0.3s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                                <i class="fab fa-instagram"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                    <div class="moderator-security-strip">
                        <h4 style="color: var(--bncc-danger); font-weight: 900; font-size: 0.8rem; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">
                            <i class="fas fa-shield-alt"></i> Moderator & Admin Shield
                        </h4>
                        <div class="ui-flex ui-gap-3">
                            <button onclick="dispatchSuspendModal()" class="ui-btn ui-btn-danger ui-w-full" style="padding: 18px; border-radius: 15px; font-weight: 900;">
                                <i class="fas fa-ban"></i> SUSPEND LISTING
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                
                <button onclick="dispatchReportAction(<?= $product['shop_id'] ?>, 'shop')" style="background: none; border: none; color: var(--bncc-danger); font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 800; margin-top: 30px; opacity: 0.5; transition: 0.3s;">
                    <i class="fas fa-flag"></i> รายงานความไม่เหมาะสมของร้านค้านี้
                </button>
            </div>
        </div>

        <section class="pd-review-stream">
            <div class="pd-section-label">
                <div>
                    <h2 style="font-size: 2.8rem; font-weight: 900; letter-spacing: -2px;">Social Proof</h2>
                    <p class="ui-text-sub ui-font-bold">ความคิดเห็นจากผู้ซื้อสินค้าจริง (<?= $global_total ?> รีวิว)</p>
                </div>
                <div class="pd-avg-score-luxe">★ <?= $global_avg ?></div>
            </div>

            <?php if (isLoggedIn()): ?>
                <?php $review_eligibility = canUserReview($user_id, $product_id); ?>
                <?php if ($review_eligibility['status']): ?>
                    <div class="review-solid-card revealed" style="border-style: dashed; border-width: 3px; background: var(--pd-base-bg);">
                        <h3 class="ui-font-black ui-text-2xl ui-mb-8">แบ่งปันประสบการณ์ของคุณ</h3>
                        <form method="POST">
                            <div class="ui-mb-8">
                                <label class="pd-desc-label">คะแนนความพึงพอใจ</label>
                                <div class="pd-rating-input">
                                    <?php for($i=5; $i>=1; $i--): ?>
                                        <input type="radio" id="starLuxe<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                        <label for="starLuxe<?= $i ?>"><i class="fas fa-star"></i></label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="ui-mb-8">
                                <textarea name="comment" class="ui-input" required style="min-height: 180px; border-radius: 24px; padding: 30px; font-weight: 600; border: 2px solid var(--bncc-border);" placeholder="สินค้านี้เป็นอย่างไรบ้าง? การนัดรับสะดวกไหม? บอกเล่าให้เพื่อนๆ ฟังหน่อย..."></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn-luxe-primary" style="width: auto; padding: 20px 60px;">
                                SUBMIT FEEDBACK
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="background: var(--bncc-base-bg); border: 2px dashed var(--bncc-border); padding: 50px; border-radius: 35px; text-align: center; margin-bottom: 50px;">
                        <p style="font-weight: 800; color: var(--bncc-primary); margin: 0; font-size: 1.1rem;">
                            <i class="fas fa-info-circle ui-mr-2"></i> <?= $review_eligibility['message'] ?>
                        </p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div id="reviewMasterContainer">
                <?php if (count($all_reviews) > 0): ?>
                    <?php foreach ($all_reviews as $r_idx => $r_data): 
                        $r_avatar = !empty($r_data['profile_img']) ? "../assets/images/profiles/".$r_data['profile_img'] : "../assets/images/profiles/default_profile.png";
                    ?>
                        <div class="review-solid-card pd-scroll-observer" style="transition-delay: <?= $r_idx * 0.1 ?>s;">
                            <div class="ui-flex ui-gap-8">
                                <a href="view_profile.php?id=<?= $r_data['author_id'] ?>">
                                    <img src="<?= $r_avatar ?>" class="author-avatar" loading="lazy">
                                </a>
                                <div class="ui-flex-grow">
                                    <div class="ui-flex ui-justify-between ui-items-start">
                                        <div>
                                            <h4 class="ui-font-black ui-text-xl"><?= e($r_data['fullname']) ?> <?= getUserBadge($r_data['author_role']) ?></h4>
                                            <div class="rating-visual-stars ui-mt-2">
                                                <?php for($k=0; $k<$r_data['rating']; $k++) echo '<i class="fas fa-star"></i>'; ?>
                                                <?php for($k=0; $k<5-$r_data['rating']; $k++) echo '<i class="far fa-star" style="opacity:0.3;"></i>'; ?>
                                            </div>
                                        </div>
                                        <div class="ui-text-xs ui-font-black ui-text-muted ui-uppercase">
                                            <?= date('F j, Y', strtotime($r_data['created_at'])) ?>
                                        </div>
                                    </div>
                                    <p class="ui-mt-6 ui-text-lg" style="line-height: 1.8; opacity: 0.9; color: var(--bncc-text-primary);">
                                        <?= nl2br(e($r_data['comment'])) ?>
                                    </p>
                                    
                                    <div class="ui-flex ui-gap-6 ui-mt-8 ui-pt-6" style="border-top: 1px solid var(--bncc-border);">
                                        <?php if ($user_id == $r_data['author_id']): ?>
                                            <button onclick="dispatchReviewEditor(<?= $r_data['id'] ?>, <?= $r_data['rating'] ?>, '<?= e(addslashes(str_replace(["\r", "\n"], ' ', $r_data['comment']))) ?>')" style="background: none; border: none; color: var(--bncc-primary); font-weight: 900; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; gap: 5px;">
                                                <i class="fas fa-edit"></i> แก้ไขรีวิว
                                            </button>
                                            <a href="?id=<?= $product_id ?>&action=delete_my_review&rev_id=<?= $r_data['id'] ?>" onclick="return confirm('คุณต้องการลบรีวิวนี้ถาวรใช่หรือไม่?')" style="text-decoration: none; color: var(--bncc-danger); font-weight: 900; font-size: 0.85rem; display: flex; align-items: center; gap: 5px;">
                                                <i class="fas fa-trash"></i> ลบทิ้ง
                                            </a>
                                        <?php endif; ?>
                                        <button onclick="dispatchReportAction(<?= $r_data['id'] ?>, 'comment')" style="background: none; border: none; color: var(--bncc-text-secondary); font-weight: 800; cursor: pointer; font-size: 0.85rem; margin-left: auto;">
                                            <i class="fas fa-flag"></i> Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 120px 40px; border: 3px dashed var(--bncc-border); border-radius: 40px; background: var(--bncc-surface);">
                        <div style="font-size: 5rem; opacity: 0.1; margin-bottom: 25px;"><i class="fas fa-comments-alt"></i></div>
                        <h3 class="ui-font-black ui-text-muted" style="font-size: 1.5rem;">ยังไม่มีรีวิวสำหรับสินค้านี้</h3>
                        <p class="ui-text-sub">มาร่วมแชร์ประสบการณ์ครั้งแรกกับสินค้านี้กันเถอะ!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    <?php endif; ?>
</div>

<div id="modalReviewEditor" class="pd-modal-mask">
    <div class="pd-modal-surface">
        <div class="ui-flex ui-items-center ui-gap-4 ui-mb-6">
            <div style="width: 50px; height: 50px; background: var(--pd-primary-soft); color: var(--bncc-primary); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-pen-nib"></i>
            </div>
            <h2 style="font-weight: 900; letter-spacing: -1px; margin: 0;">แก้ไขรีวิวของคุณ</h2>
        </div>
        <form method="POST">
            <input type="hidden" name="review_id" id="patchRevId">
            <div class="ui-mb-6">
                <label class="pd-desc-label">ให้คะแนนใหม่</label>
                <select name="rating" id="patchRevRating" class="ui-input" style="padding: 15px; border-radius: 12px; border-width: 2px;">
                    <option value="5">⭐⭐⭐⭐⭐ (5/5)</option>
                    <option value="4">⭐⭐⭐⭐ (4/5)</option>
                    <option value="3">⭐⭐⭐ (3/5)</option>
                    <option value="2">⭐⭐ (2/5)</option>
                    <option value="1">⭐ (1/5)</option>
                </select>
            </div>
            <div class="ui-mb-8">
                <label class="pd-desc-label">ความเห็นของคุณ</label>
                <textarea name="comment" id="patchRevComment" class="ui-input" style="min-height: 150px; padding: 20px; font-weight: 600; border-radius: 20px; border-width: 2px;"></textarea>
            </div>
            <div class="ui-flex ui-gap-4">
                <button type="button" onclick="closeEngineModal('modalReviewEditor')" class="btn-luxe-chat" style="flex: 1; justify-content: center; border-radius: 15px;">ยกเลิก</button>
                <button type="submit" name="edit_review_submit" class="btn-luxe-primary" style="flex: 1; padding: 18px; border-radius: 15px; font-size: 1rem;">บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</div>

<div id="modalAdminShield" class="pd-modal-mask">
    <div class="pd-modal-surface" style="border-color: var(--bncc-danger);">
        <div class="ui-text-center ui-mb-8">
            <div style="width: 80px; height: 80px; background: #fef2f2; color: var(--bncc-danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 20px;">
                <i class="fas fa-shield-slash"></i>
            </div>
            <h2 style="font-weight: 900; color: var(--bncc-danger); letter-spacing: -1px;">SUSPEND LISTING</h2>
            <p class="ui-text-sub ui-font-bold">ระงับการเผยแพร่สินค้าชิ้นนี้ทันที</p>
        </div>
        <form action="../admin/admin_delete_product.php" method="POST">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <div class="ui-mb-8">
                <label class="pd-desc-label">โปรดระบุสาเหตุ (ระบบจะบันทึก Log)</label>
                <textarea name="reason" required class="ui-input" style="min-height: 120px; padding: 20px; border-radius: 18px;" placeholder="เช่น สินค้าผิดกฎระเบียบ, ข้อมูลเท็จ..."></textarea>
            </div>
            <div class="ui-flex ui-gap-4">
                <button type="button" onclick="closeEngineModal('modalAdminShield')" class="btn-luxe-chat" style="flex: 1; justify-content: center;">BACK</button>
                <button type="submit" class="btn-luxe-primary" style="flex: 1; background: var(--bncc-danger); box-shadow: none;">CONFIRM BAN</button>
            </div>
        </form>
    </div>
</div>

<div id="modalGlobalReport" class="pd-modal-mask">
    <div class="pd-modal-surface">
        <h2 class="ui-font-black ui-mb-6">รายงานปัญหา</h2>
        <form action="../auth/submit_report.php" method="POST">
            <input type="hidden" name="target_id" id="reportId">
            <input type="hidden" name="target_type" id="reportType">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <textarea name="reason" required class="ui-input ui-mb-6" style="min-height: 130px;" placeholder="กรุณาระบุรายละเอียดที่ต้องการรายงาน..."></textarea>
            <div class="ui-flex ui-gap-3">
                <button type="button" onclick="closeEngineModal('modalGlobalReport')" class="ui-btn ui-btn-secondary ui-w-full">ยกเลิก</button>
                <button type="submit" class="ui-btn ui-btn-danger ui-w-full">ส่งรายงาน</button>
            </div>
        </form>
    </div>
</div>

<script>
    /**
     * MODULE 1: FLUID GALLERY CONTROLLER
     */
    const PhotoEngine = {
        main: document.getElementById('pdDisplayMaster'),
        
        transition(newSrc, thumbElement) {
            if (this.main.src === newSrc) return;

            // Step 1: Update Thumbs UI
            document.querySelectorAll('.thumb-trigger-node').forEach(node => node.classList.remove('is-active'));
            thumbElement.classList.add('is-active');

            // Step 2: Liquid Transition
            this.main.classList.add('gallery-transition-state');
            
            setTimeout(() => {
                this.main.src = newSrc;
                this.main.classList.remove('gallery-transition-state');
            }, 180);
        }
    };

    function invokeGalleryShift(u, e) { PhotoEngine.transition(u, e); }

    /**
     * MODULE 2: SCROLL ANIMATION (INTERSECTION OBSERVER)
     */
    const scrollEngine = new IntersectionObserver((entries) => {
        entries.forEach((entry, idx) => {
            if (entry.isIntersecting) {
                // Add staggered delay for flow effect
                setTimeout(() => {
                    entry.target.classList.add('revealed');
                }, idx * 120);
                scrollEngine.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: "0px 0px -50px 0px" });

    document.querySelectorAll('.pd-scroll-observer').forEach(el => scrollEngine.observe(el));

    /**
     * MODULE 3: MODAL DYNAMIC HANDLERS
     */
    function dispatchReviewEditor(id, score, text) {
        document.getElementById('patchRevId').value = id;
        document.getElementById('patchRevRating').value = score;
        document.getElementById('patchRevComment').value = text;
        document.getElementById('modalReviewEditor').style.display = 'flex';
    }

    function dispatchSuspendModal() { document.getElementById('modalAdminShield').style.display = 'flex'; }
    function closeEngineModal(id) { document.getElementById(id).style.display = 'none'; }
    
    function dispatchReportAction(id, type) {
        document.getElementById('reportId').value = id;
        document.getElementById('reportType').value = type;
        document.getElementById('modalGlobalReport').style.display = 'flex';
    }

    window.onclick = function(e) {
        if (e.target.classList.contains('pd-modal-mask')) {
            e.target.style.display = 'none';
        }
    };

    /**
     * MODULE 4: REAL-TIME WISHLIST ENGINE (AJAX)
     */
    const wishController = document.getElementById('luxeWishEngine');
    if (wishController) {
        wishController.addEventListener('click', async function() {
            const btn = this;
            const icon = btn.querySelector('i');
            const pid = btn.dataset.id;

            // Physic Feedback
            btn.style.transform = "scale(0.7) rotate(-20deg)";
            
            try {
                const response = await fetch('../auth/toggle_wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'product_id=' + pid
                });
                
                const data = await response.json();
                
                btn.style.transform = "scale(1) rotate(0)";
                
                if (data.status === 'added') {
                    btn.classList.add('is-bookmarked');
                    icon.className = 'fas fa-heart';
                    // Optional: Pulse Animation on addition
                } else {
                    btn.classList.remove('is-bookmarked');
                    icon.className = 'far fa-heart';
                }
            } catch (err) {
                console.error("Critical Fault in Wishlist Engine:", err);
                window.location.href = '../auth/login.php';
            }
        });
    }

    /**
     * MODULE 5: ANALYTICS & LOGGING
     */
    console.log("%c BNCC ENTERPRISE CORE LOADED SUCCESSFULLY ", "background: #4f46e5; color: #fff; font-weight: 900; padding: 5px; border-radius: 5px;");
</script>

<?php require_once '../includes/footer.php'; ?>