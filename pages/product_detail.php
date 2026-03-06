<?php
/**
 * ==========================================================================================
 * 💎 BNCC MARKET - ULTIMATE PREMIUM PRODUCT DETAIL SYSTEM (PRO VERSION 2026)
 * ==========================================================================================
 * [TARGET LINE COUNT: 850+ LINES | FULL FEATURE INTEGRATION]
 * * 🛠️ CORE ARCHITECTURE:
 * - High-Contrast Glassmorphism UI System (Solid Edge Design)
 * - Dynamic Multi-Image Gallery (Optimized for 5-Image Support)
 * - Session-based View Counter (Anti-Inflate Analytics)
 * - Advanced Order Management System (Real-time DB Integration)
 * - Anti-Spam Review Guard (Cooldown + Duplicate Prevention)
 * - Global Badge Authority System (Admin, Teacher, Recommended Shop)
 * - Administrative Multi-Action Control Panel
 * * 📁 DEPENDENCIES:
 * - includes/functions.php (Required: canUserReview, getUserBadge, getShopBadge)
 * - includes/header.php & includes/footer.php
 * - config/database.php
 * * ------------------------------------------------------------------------------------------
 * Project: BNCC Student Marketplace
 * Developer: Gemini AI x Ploy Collaboration (IT Support Specialist Edition)
 * ==========================================================================================
 */

// ------------------------------------------------------------------------------------------
// 🚀 SECTION 1: SYSTEM INITIALIZATION & DATA FETCHING
// ------------------------------------------------------------------------------------------

require_once '../includes/functions.php';

// 🛡️ Security Check: Ensure Product ID is present and valid
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$product_id || $product_id <= 0) {
    header("Location: index.php");
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'guest';

// 🔍 Fetch Primary Product Data with User Role Integration
$sql = "SELECT p.*, s.shop_name, s.contact_line, s.contact_ig, s.line_user_id, 
               s.user_id as owner_id, u.role as owner_role, u.fullname as owner_name, u.class_room
        FROM products p 
        JOIN shops s ON p.shop_id = s.id 
        JOIN users u ON s.user_id = u.id
        WHERE p.id = ? AND p.is_deleted = 0";
$stmt = $db->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// 🚨 Handle Non-existent or Suspended Products
if (!$product) {
    $_SESSION['flash_message'] = "ขออภัย ไม่พบสินค้านี้ในระบบ หรือถูกระงับการขายชั่วคราว";
    $_SESSION['flash_type'] = "warning";
    header("Location: index.php");
    exit;
}

// 🖼️ Fetch Gallery Images (Strict 5-Image Limit)
$img_sql = "SELECT image_path, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC LIMIT 5";
$img_stmt = $db->prepare($img_sql);
$img_stmt->execute([$product_id]);
$gallery = $img_stmt->fetchAll();

// 🛠️ Legacy Support: If no separate gallery entry exists, use main image
if (count($gallery) === 0) {
    $gallery[] = ['image_path' => $product['image_url'], 'is_main' => 1];
}
$main_image_src = $gallery[0]['image_path'];

// ------------------------------------------------------------------------------------------
// 📈 SECTION 2: ANALYTICS & SOCIAL PROOF
// ------------------------------------------------------------------------------------------

// 👁️ Session-Based View Counter (Prevents artificial inflation)
if (!isset($_SESSION['view_log'])) {
    $_SESSION['view_log'] = [];
}
if (!in_array($product_id, $_SESSION['view_log'])) {
    $db->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product_id]);
    $_SESSION['view_log'][] = $product_id;
    $product['views'] += 1;
}

// ⭐ Aggregate Rating Summary
$rev_sum_sql = "SELECT AVG(rating) as avg_rating, COUNT(id) as count_reviews 
                FROM reviews 
                WHERE product_id = ? AND is_deleted = 0";
$rev_sum_stmt = $db->prepare($rev_sum_sql);
$rev_sum_stmt->execute([$product_id]);
$r_data = $rev_sum_stmt->fetch();

$avg_p_rating = round($r_data['avg_rating'] ?? 0, 1);
$total_p_reviews = $r_data['count_reviews'];

// 🏷️ Fetch Dynamic Product Tags
$tags_sql = "SELECT t.tag_name FROM tags t JOIN product_tag_map ptm ON t.id = ptm.tag_id WHERE ptm.product_id = ?";
$tags_stmt = $db->prepare($tags_sql);
$tags_stmt->execute([$product_id]);
$tags_list = $tags_stmt->fetchAll();

// ❤️ Wishlist Status Checker
$is_liked = false;
if (isLoggedIn()) {
    $check_w = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check_w->execute([$user_id, $product_id]);
    $is_liked = $check_w->fetch() ? true : false;
}

// ------------------------------------------------------------------------------------------
// 🛒 SECTION 3: TRANSACTIONAL LOGIC (ORDERS & REVIEWS)
// ------------------------------------------------------------------------------------------

// 💳 Order Submission Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isLoggedIn()) redirect('../auth/login.php');
    
    if ($user_id == $product['owner_id']) {
        $_SESSION['flash_message'] = "คุณไม่สามารถสั่งซื้อสินค้าของร้านตัวเองได้";
        $_SESSION['flash_type'] = "error";
    } else {
        $db->beginTransaction();
        try {
            $stmt_ord = $db->prepare("INSERT INTO orders (buyer_id, shop_id, product_id, status) VALUES (?, ?, ?, 'pending')");
            $stmt_ord->execute([$user_id, $product['shop_id'], $product_id]);
            
            // Notification dispatch
            $notif_text = "🛒 ออเดอร์ใหม่: {$product['title']} จากคุณ {$_SESSION['fullname']}";
            sendNotification($product['owner_id'], 'order', $notif_text, "../seller/dashboard.php");

            if (!empty($product['line_user_id'])) {
                sendLineMessagingAPI($product['line_user_id'], $notif_text);
            }
            $db->commit();
            $_SESSION['flash_message'] = "ส่งคำสั่งซื้อสำเร็จ! โปรดรอเจ้าของร้านติดต่อกลับ";
            $_SESSION['flash_type'] = "success";
        } catch (Exception $ex) {
            $db->rollBack();
            $_SESSION['flash_message'] = "ระบบขัดข้อง: " . $ex->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
    redirect("product_detail.php?id=$product_id");
}

// 🛡️ Review Submission with Anti-Spam Guard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) redirect('../auth/login.php');

    $rating_val = (int)$_POST['rating'];
    $comment_val = trim($_POST['comment']);
    
    // 🛑 Anti-Spam Verification (Calls functions.php)
    $spam_gate = canUserReview($user_id, $product_id);

    if (!$spam_gate['status']) {
        $_SESSION['flash_message'] = $spam_gate['message'];
        $_SESSION['flash_type'] = "danger";
    } else {
        $sql_rev = "INSERT INTO reviews (product_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt_rev = $db->prepare($sql_rev);
        if ($stmt_rev->execute([$product_id, $user_id, $rating_val, $comment_val])) {
            
            $notif_rev = "⭐ รีวิวใหม่ ({$rating_val} ดาว) สำหรับ {$product['title']}";
            sendNotification($product['owner_id'], 'review', $notif_rev, "product_detail.php?id=$product_id");
            
            $_SESSION['flash_message'] = "ขอบคุณสำหรับการรีวิวสินค้า!";
            $_SESSION['flash_type'] = "success";
        }
    }
    redirect("product_detail.php?id=$product_id");
}

// 💬 Fetch Detailed Review List
$rev_list_sql = "SELECT r.*, u.fullname, u.profile_img, u.role as author_role 
                 FROM reviews r 
                 JOIN users u ON r.user_id = u.id 
                 WHERE r.product_id = ? AND r.is_deleted = 0 
                 ORDER BY r.created_at DESC";
$rev_list_stmt = $db->prepare($rev_list_sql);
$rev_list_stmt->execute([$product_id]);
$all_reviews = $rev_list_stmt->fetchAll();

$pageTitle = $product['title'] . " | BNCC Market";
require_once '../includes/header.php';
?>

<style>
    :root {
        --b-bg: #0f172a;
        --b-card: #1e293b;
        --b-border: rgba(255,255,255,0.08);
        --b-primary: #6366f1;
        --b-accent: #fbbf24;
        --b-text: #f8fafc;
        --b-text-muted: #94a3b8;
        --b-danger: #ef4444;
        --b-success: #10b981;
        --b-glass: rgba(15, 23, 42, 0.7);
        --b-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
    }

    body {
        background-color: var(--b-bg) !important;
        color: var(--b-text);
        font-family: 'Prompt', sans-serif;
        font-weight: 400;
        -webkit-font-smoothing: antialiased;
    }

    .master-product-container {
        max-width: 1300px;
        margin: 60px auto;
        padding: 0 40px;
        animation: globalEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes globalEntrance {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* 🏰 Main Product Layout Grid */
    .product-core-card {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 70px;
        background: var(--b-card);
        border: 2px solid var(--b-border);
        border-radius: 50px;
        padding: 60px;
        box-shadow: var(--b-shadow);
        position: relative;
        overflow: hidden;
    }

    .product-core-card::after {
        content: '';
        position: absolute;
        bottom: -150px;
        left: -150px;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
        z-index: 0;
    }

    /* 🖼️ Gallery Engine Styles */
    .gallery-engine { position: relative; z-index: 1; }

    .gallery-view-port {
        width: 100%;
        aspect-ratio: 1/1;
        border-radius: 40px;
        overflow: hidden;
        background: #000;
        border: 2px solid var(--b-border);
        position: relative;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }

    .gallery-view-port img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.8s cubic-bezier(0.2, 1, 0.3, 1);
    }

    .gallery-view-port:hover img { transform: scale(1.15); }

    .gallery-thumb-rail {
        display: flex;
        gap: 18px;
        margin-top: 30px;
        overflow-x: auto;
        padding: 10px 5px;
        scrollbar-width: none;
    }

    .gallery-thumb-rail::-webkit-scrollbar { display: none; }

    .thumb-item {
        width: 100px;
        height: 100px;
        border-radius: 22px;
        border: 4px solid transparent;
        overflow: hidden;
        cursor: pointer;
        opacity: 0.45;
        transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        flex-shrink: 0;
    }

    .thumb-item img { width: 100%; height: 100%; object-fit: cover; }
    .thumb-item:hover { opacity: 0.9; transform: translateY(-8px); }
    .thumb-item.active {
        border-color: var(--b-primary);
        opacity: 1;
        transform: scale(1.1);
        box-shadow: 0 12px 25px rgba(99, 102, 241, 0.4);
    }

    /* 📝 Content Typography & Badges */
    .content-hub { position: relative; z-index: 1; }
    
    .product-cat-label {
        color: var(--b-primary);
        font-weight: 900;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 3px;
        margin-bottom: 15px;
        display: block;
    }

    .product-main-h1 {
        font-size: 4.2rem;
        font-weight: 950;
        letter-spacing: -3px;
        line-height: 0.9;
        margin-bottom: 20px;
        color: var(--b-text);
        text-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .product-price-xl {
        font-size: 3.5rem;
        font-weight: 950;
        color: var(--b-accent);
        margin: 40px 0;
        display: flex;
        align-items: center;
        gap: 15px;
        text-shadow: 0 8px 20px rgba(251, 191, 36, 0.2);
    }

    /* 🥇 Premium Badges */
    .badge-gold-medal {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: #000;
        padding: 8px 20px;
        border-radius: 15px;
        font-weight: 950;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 10px 30px rgba(251, 191, 36, 0.4);
        border: 1px solid rgba(0,0,0,0.1);
        text-transform: uppercase;
    }

    .badge-staff {
        background: rgba(99, 102, 241, 0.15);
        border: 1.5px solid var(--b-primary);
        color: var(--b-primary);
        padding: 5px 14px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    /* 🔘 Interactive Buttons */
    .btn-order-giant {
        flex: 1;
        background: var(--b-primary);
        color: #fff !important;
        border: none;
        padding: 26px;
        border-radius: 28px;
        font-weight: 950;
        font-size: 1.5rem;
        cursor: pointer;
        transition: 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 20px 40px rgba(99, 102, 241, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        text-decoration: none;
    }

    .btn-order-giant:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 25px 55px rgba(99, 102, 241, 0.5);
        filter: brightness(1.1);
    }

    .btn-sq-action {
        width: 85px;
        height: 85px;
        border-radius: 28px;
        border: 2px solid var(--b-border);
        background: rgba(255,255,255,0.02);
        color: #fff;
        font-size: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.4s;
    }

    .btn-sq-action:hover {
        background: var(--b-card);
        border-color: var(--b-primary);
        color: var(--b-primary);
        transform: scale(1.1) rotate(5deg);
    }

    /* 🏪 Shop Identity System */
    .shop-portal-card {
        background: var(--b-glass);
        border: 2.5px solid var(--b-border);
        border-radius: 40px;
        padding: 40px;
        margin-top: 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        backdrop-filter: blur(25px);
        border-left: 8px solid var(--b-primary);
    }

    .shop-avatar-giant {
        width: 85px;
        height: 85px;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        border-radius: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #fff;
        box-shadow: 0 15px 30px rgba(99, 102, 241, 0.4);
    }

    .shop-link-title {
        font-size: 2.2rem;
        font-weight: 950;
        color: #fff;
        text-decoration: none;
        transition: 0.3s;
    }

    .shop-link-title:hover { color: var(--b-primary); text-shadow: 0 0 20px rgba(99, 102, 241, 0.3); }

    /* ⭐ Ultimate Review Cards */
    .review-master-hub { margin-top: 120px; }
    
    .review-solid-entry {
        background: var(--b-card);
        border: 2px solid var(--b-border);
        border-radius: 40px;
        padding: 50px;
        margin-bottom: 40px;
        transition: 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
    }

    .review-solid-entry:hover {
        border-color: var(--b-primary);
        transform: scale(1.02) translateX(15px);
        box-shadow: 0 30px 60px rgba(0,0,0,0.4);
    }

    .reviewer-meta-box { display: flex; align-items: center; gap: 25px; margin-bottom: 30px; }
    .reviewer-avatar-img { width: 75px; height: 75px; border-radius: 28px; object-fit: cover; border: 4px solid var(--b-border); }

    .rating-star-group { display: flex; gap: 8px; color: var(--b-accent); font-size: 1.1rem; }

    /* 🛡️ Review Anti-Spam Styling */
    .spam-blocker-shield {
        text-align: center;
        padding: 100px 50px;
        background: rgba(99, 102, 241, 0.03);
        border-radius: 50px;
        border: 3px dashed var(--b-border);
        margin-bottom: 60px;
    }

    .spam-blocker-shield i { font-size: 5rem; color: var(--b-primary); opacity: 0.4; margin-bottom: 30px; }

    /* 🚨 Glass Modals System */
    .modal-universal-bg {
        position: fixed; inset: 0;
        background: rgba(15, 23, 42, 0.95);
        z-index: 1000000;
        display: none;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(30px);
    }

    .modal-universal-card {
        background: var(--b-card);
        border: 3px solid var(--b-border);
        border-radius: 50px;
        width: 95%;
        max-width: 600px;
        padding: 70px;
        box-shadow: 0 60px 120px rgba(0,0,0,0.9);
        animation: modalInDynamics 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalInDynamics {
        from { transform: scale(0.7) translateY(50px); opacity: 0; }
        to { transform: scale(1) translateY(0); opacity: 1; }
    }

    /* 📱 Responsive Engineering */
    @media (max-width: 1100px) {
        .product-core-card { grid-template-columns: 1fr; padding: 45px; }
        .product-main-h1 { font-size: 3.2rem; }
    }

    @media (max-width: 768px) {
        .master-product-container { padding: 0 20px; }
        .product-core-card { padding: 30px; border-radius: 35px; }
        .product-main-h1 { font-size: 2.6rem; }
        .shop-portal-card { flex-direction: column; gap: 30px; text-align: center; }
    }
</style>

<div class="master-product-container">
    
    <div class="product-core-card">
        
        <div class="gallery-engine">
            <div class="gallery-view-port">
                <img id="activeGalleryImg" src="../assets/images/products/<?= e($main_image_src) ?>" alt="<?= e($product['title']) ?>">
                
                <div style="position: absolute; top: 35px; left: 35px; background: rgba(0,0,0,0.8); backdrop-filter: blur(15px); padding: 12px 25px; border-radius: 20px; font-weight: 950; font-size: 1rem; border: 1.5px solid rgba(255,255,255,0.15);">
                    <i class="fas fa-star" style="color: var(--b-accent);"></i> <?= $avg_p_rating ?> <span style="font-weight: 500; opacity: 0.6; margin-left: 5px;">(<?= $total_p_reviews ?> รีวิว)</span>
                </div>

                <div style="position: absolute; bottom: 35px; right: 35px; background: rgba(255,255,255,0.95); color: #000; padding: 12px 25px; border-radius: 20px; font-weight: 950; font-size: 0.9rem; box-shadow: 0 15px 35px rgba(0,0,0,0.4);">
                    <i class="fas fa-chart-line" style="margin-right: 8px;"></i> <?= number_format($product['views']) ?> VIEWS
                </div>
            </div>

            <?php if (count($gallery) > 1): ?>
            <div class="gallery-thumb-rail">
                <?php foreach ($gallery as $idx => $img): ?>
                    <div class="thumb-item <?= $idx === 0 ? 'active' : '' ?>" 
                         onclick="primeGalleryImage('../assets/images/products/<?= e($img['image_path']) ?>', this)">
                        <img src="../assets/images/products/<?= e($img['image_path']) ?>" alt="Product Thumb <?= $idx+1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="content-hub">
            <div class="p-header">
                <span class="product-cat-label">BNCC MARKET EXCLUSIVE</span>
                <h1 class="product-main-h1"><?= e($product['title']) ?></h1>
                
                <div style="display: flex; gap: 15px; margin-top: 25px; flex-wrap: wrap;">
                    <?php foreach ($tags_list as $tag): ?>
                        <span style="background: rgba(99, 102, 241, 0.15); border: 2px solid rgba(99, 102, 241, 0.3); color: #c7d2fe; padding: 8px 18px; border-radius: 16px; font-size: 0.85rem; font-weight: 900;">#<?= e($tag['tag_name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="product-price-xl">
                ฿<?= number_format($product['price'], 2) ?>
                <span>สุทธิ (NET)</span>
            </div>

            <div style="margin-bottom: 55px; padding-bottom: 45px; border-bottom: 2px dashed var(--b-border);">
                <h6 style="text-transform: uppercase; font-weight: 950; font-size: 0.85rem; opacity: 0.4; letter-spacing: 3px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-align-left" style="color: var(--b-primary);"></i> Product Description
                </h6>
                <p style="font-size: 1.3rem; line-height: 2.1; opacity: 0.95; font-weight: 500; text-align: justify; color: #cbd5e1;">
                    <?= nl2br(e($product['description'])) ?>
                </p>
            </div>

            <form method="POST" style="display: flex; gap: 25px; align-items: center;">
                <?php if ($user_id && $user_id != $product['owner_id']): ?>
                    <button type="submit" name="place_order" class="btn-order-giant" onclick="return confirm('คุณต้องการส่งคำสั่งซื้อสินค้านี้ใช่หรือไม่?')">
                        <i class="fas fa-bolt"></i> PROCEED ORDER
                    </button>
                    <a href="chat.php?user=<?= $product['owner_id'] ?>" class="btn-sq-action" title="ทักแชทสอบถาม"><i class="fas fa-comment-alt"></i></a>
                <?php elseif (!$user_id): ?>
                    <a href="../auth/login.php" class="btn-order-giant">LOGIN TO UNLOCK PURCHASE</a>
                <?php endif; ?>

                <button type="button" id="wishlistTrigger" data-id="<?= $product['id'] ?>" class="btn-sq-action" style="color: <?= $is_liked ? 'var(--b-danger)' : 'var(--b-text)' ?>;">
                    <i class="<?= $is_liked ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
            </form>

            <div class="shop-portal-card">
                <div style="display: flex; align-items: center; gap: 28px;">
                    <div class="shop-avatar-giant"><i class="fas fa-store"></i></div>
                    <div>
                        <span style="font-size: 0.75rem; font-weight: 900; color: var(--b-primary); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px; display: block;">
                            Verified Merchant <?= getUserBadge($product['owner_role']) ?>
                        </span>
                        <div style="display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
                            <a href="shop_profile.php?id=<?= $product['shop_id'] ?>" class="shop-link-title"><?= e($product['shop_name']) ?></a>
                            <?= getShopBadge($product['shop_id']) ?> </div>
                        <div style="font-size: 0.85rem; font-weight: 800; opacity: 0.5; margin-top: 8px;">
                            <i class="fas fa-user-circle"></i> <?= e($product['owner_name']) ?> | ห้อง: <?= e($product['class_room']) ?>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <?php if(!empty($product['contact_line'])): ?>
                        <a href="https://line.me/ti/p/~<?= e($product['contact_line']) ?>" target="_blank" style="color: var(--b-success); font-size: 2.2rem; transition: 0.3s;" onmouseover="this.style.transform='rotate(15deg) scale(1.2)'"><i class="fab fa-line"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($product['contact_ig'])): ?>
                        <a href="https://www.instagram.com/<?= e($product['contact_ig']) ?>/" target="_blank" style="color: #ec4899; font-size: 2.2rem; transition: 0.3s;" onmouseover="this.style.transform='rotate(-15deg) scale(1.2)'"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                <div style="margin-top: 60px; padding: 40px; border-radius: 40px; border: 3px dashed var(--b-danger); background: rgba(239, 68, 68, 0.04); position: relative;">
                    <div style="position: absolute; top: -18px; left: 40px; background: var(--b-danger); color: #fff; padding: 6px 20px; border-radius: 12px; font-weight: 950; font-size: 0.75rem; letter-spacing: 1px;">STAFF CONTROL PANEL</div>
                    <h5 style="color: var(--b-danger); font-weight: 950; margin-bottom: 25px; font-size: 1.1rem;"><i class="fas fa-user-shield"></i> Security & Moderation Hub</h5>
                    <div style="display: flex; gap: 20px;">
                        <button onclick="activateModal('suspendModal')" class="btn-order-giant" style="background: var(--b-danger); padding: 18px; font-size: 1.1rem; box-shadow: none;">SUSPEND PRODUCT</button>
                        <a href="../admin/inspect_item.php?id=<?= $product_id ?>" class="btn-sq-action" style="width: 70px; height: 70px; background: #000;"><i class="fas fa-search-plus"></i></a>
                    </div>
                </div>
            <?php endif; ?>
            
            <button onclick="primeReport('<?= $product['shop_id'] ?>', 'shop')" style="background: none; border: none; color: #ef4444; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 12px; font-weight: 900; margin-top: 50px; opacity: 0.5; transition: 0.4s;" onmouseover="this.style.opacity='1'; this.style.letterSpacing='1px';">
                <i class="fas fa-exclamation-circle"></i> REPORT THIS MERCHANT FOR TERMS VIOLATION
            </button>
        </div>
    </div>

    <div class="review-master-hub">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 70px;">
            <div>
                <h2 style="font-size: 3.5rem; font-weight: 950; letter-spacing: -2px; line-height: 1;">Student Reviews</h2>
                <p style="font-weight: 700; opacity: 0.4; margin-top: 10px;">ขุมทรัพย์ทางความคิดจากเพื่อนสมาชิก BNCC</p>
            </div>
            <div style="background: var(--b-accent); color: #000; padding: 15px 45px; border-radius: 60px; font-weight: 950; font-size: 1.6rem; box-shadow: 0 15px 40px rgba(251, 191, 36, 0.4); display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-star"></i> <?= $avg_p_rating ?>
            </div>
        </div>

        <?php if (isLoggedIn()): ?>
            <?php 
            $spam_report = canUserReview($user_id, $product_id); 
            if ($spam_report['status']): 
            ?>
            <div class="review-solid-entry" style="border: 3.5px solid var(--b-primary); background: rgba(99, 102, 241, 0.02);">
                <h3 style="font-weight: 950; margin-bottom: 40px; font-size: 1.8rem; display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-pen-nib" style="color: var(--b-primary);"></i> Write a Review
                </h3>
                <form method="POST">
                    <div style="margin-bottom: 45px;">
                        <label style="display: block; font-weight: 900; font-size: 0.9rem; opacity: 0.5; margin-bottom: 20px; text-transform: uppercase;">Overall Rating</label>
                        <div class="star-rating-xl">
                            <?php for($i=5; $i>=1; $i--): ?>
                                <input type="radio" id="ux_star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                <label for="ux_star<?= $i ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea name="comment" required placeholder="Tell your friends what you think about this product... How was the quality? Did it match your expectations?" style="width: 100%; min-height: 220px; border-radius: 35px; padding: 35px; border: 3px solid var(--b-border); background: rgba(0,0,0,0.4); color: #fff; font-weight: 600; font-size: 1.25rem; outline: none; transition: 0.4s; box-shadow: inset 0 10px 20px rgba(0,0,0,0.2);" onfocus="this.style.borderColor='var(--b-primary)'; this.style.boxShadow='0 0 30px rgba(99, 102, 241, 0.2)';"></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn-order-giant" style="width: auto; padding: 22px 80px; margin-top: 45px; font-size: 1.2rem;">
                        <i class="fas fa-paper-plane"></i> PUBLISH REVIEW
                    </button>
                </form>
            </div>
            <?php else: ?>
                <div class="spam-blocker-shield">
                    <i class="fas fa-shield-virus"></i>
                    <h4 style="font-weight: 950; color: var(--b-primary); margin: 0; font-size: 1.6rem; letter-spacing: -0.5px;"><?= $spam_report['message'] ?></h4>
                    <p style="opacity: 0.5; font-weight: 700; margin-top: 15px;">ระบบป้องกันการสแปมของ BNCC Marketplace เปิดใช้งานอยู่</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="review-stream-container">
            <?php if (count($all_reviews) > 0): ?>
                <?php foreach ($all_reviews as $idx => $rev): 
                    $p_path = !empty($rev['profile_img']) ? "../assets/images/profiles/" . $rev['profile_img'] : "../assets/images/profiles/default_profile.png";
                ?>
                    <div class="review-solid-entry stagger-reveal">
                        <div class="review-header">
                            <div class="reviewer-meta-box">
                                <a href="view_profile.php?id=<?= $rev['author_id'] ?>">
                                    <img src="<?= $p_path ?>" class="reviewer-avatar-img">
                                </a>
                                <div>
                                    <h5 style="font-weight: 950; margin: 0; font-size: 1.5rem; display: flex; align-items: center; gap: 12px;">
                                        <?= e($rev['fullname']) ?> <?= getUserBadge($rev['author_role']) ?>
                                    </h5>
                                    <div class="rating-star-group" style="margin-top: 8px;">
                                        <?php for($k=0; $k<$rev['rating']; $k++) echo '<i class="fas fa-star"></i>'; ?>
                                        <?php for($k=$rev['rating']; $k<5; $k++) echo '<i class="fas fa-star" style="opacity:0.1;"></i>'; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span style="display: block; font-size: 0.9rem; font-weight: 900; opacity: 0.3;"><?= date('d M Y - H:i', strtotime($rev['created_at'])) ?></span>
                                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                    <button onclick="triggerReviewDeletion('<?= $rev['id'] ?>')" style="background: none; border: none; color: var(--b-danger); font-size: 1.5rem; margin-top: 20px; cursor: pointer; transition: 0.4s;" onmouseover="this.style.transform='scale(1.3) rotate(15deg)'"><i class="fas fa-trash-alt"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p style="margin-top: 35px; font-size: 1.4rem; line-height: 2; font-weight: 500; color: #e2e8f0; border-left: 4px solid var(--b-border); padding-left: 25px;">
                            <?= nl2br(e($rev['comment'])) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 150px 20px; border: 5px dashed var(--b-border); border-radius: 50px; opacity: 0.3;">
                    <i class="fas fa-comment-medical" style="font-size: 6rem; margin-bottom: 40px;"></i>
                    <h3 style="font-weight: 950; font-size: 2.2rem;">สินค้านี้ยังไม่มีเสียงตอบรับ</h3>
                    <p style="font-weight: 800; font-size: 1.2rem;">เป็นคนแรกที่รีวิวเพื่อช่วยเพื่อนตัดสินใจ!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="reportModal" class="modal-universal-bg">
    <div class="modal-universal-card">
        <h2 style="font-weight: 950; margin-bottom: 20px; color: var(--b-danger); display: flex; align-items: center; gap: 20px;"><i class="fas fa-shield-alt"></i> CONTENT REPORT</h2>
        <p style="opacity: 0.7; margin-bottom: 40px; font-weight: 700; font-size: 1.2rem;">ช่วยปกป้องความปลอดภัยของเพื่อนนักศึกษาด้วยการแจ้งเบาะแส</p>
        <form action="../auth/submit_report.php" method="POST">
            <input type="hidden" name="target_id" id="rep_target_val">
            <input type="hidden" name="target_type" id="rep_type_val">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <textarea name="reason" required placeholder="อธิบายสาเหตุที่คุณแจ้งรายงาน (เช่น ข้อมูลเท็จ, สินค้าอันตราย)..." style="width: 100%; min-height: 200px; border-radius: 30px; padding: 30px; background: rgba(0,0,0,0.4); border: 2.5px solid var(--b-border); color: #fff; font-weight: 600; font-size: 1.15rem;"></textarea>
            <div style="display: flex; gap: 25px; margin-top: 50px;">
                <button type="button" onclick="deactivateModal('reportModal')" class="btn-sq-action" style="flex: 1; font-size: 1.1rem; border-radius: 25px;">CANCEL</button>
                <button type="submit" class="btn-order-giant" style="flex: 2; background: var(--b-danger); border-radius: 25px; box-shadow: 0 15px 35px rgba(239, 68, 68, 0.4);">SUBMIT REPORT</button>
            </div>
        </form>
    </div>
</div>

<div id="suspendModal" class="modal-universal-bg">
    <div class="modal-universal-card" style="border-color: var(--b-danger);">
        <h2 style="font-weight: 950; color: var(--b-danger); margin-bottom: 25px; display: flex; align-items: center; gap: 20px;"><i class="fas fa-ban"></i> SUSPEND LISTING</h2>
        <p style="font-weight: 700; margin-bottom: 40px; font-size: 1.2rem;">ระงับการเข้าถึงสินค้านี้ทันที (เจ้าของร้านจะได้รับแจ้งเหตุผล)</p>
        <form action="../admin/action_suspend_item.php" method="POST">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <textarea name="admin_reason" required placeholder="เหตุผลที่เป็นทางการในการระงับรายการนี้..." style="width: 100%; min-height: 180px; border-radius: 30px; padding: 30px; background: rgba(0,0,0,0.4); border: 2.5px solid var(--b-border); color: #fff; font-weight: 600; font-size: 1.15rem;"></textarea>
            <div style="display: flex; gap: 25px; margin-top: 50px;">
                <button type="button" onclick="deactivateModal('suspendModal')" class="btn-sq-action" style="flex: 1; border-radius: 25px;">BACK</button>
                <button type="submit" class="btn-order-giant" style="flex: 2; background: var(--b-danger); border-radius: 25px;">CONFIRM SUSPEND</button>
            </div>
        </form>
    </div>
</div>

<script>
    /**
     * 🖼️ Advanced Gallery Switcher Engine
     * Transitions main display with smooth opacity and scale dynamics
     */
    function primeGalleryImage(url, node) {
        const stage = document.getElementById('activeGalleryImg');
        const thumbs = document.querySelectorAll('.thumb-item');
        
        // UI Feedback: Handle active state class switching
        thumbs.forEach(t => t.classList.remove('active'));
        node.classList.add('active');
        
        // Render: Smooth fade transition
        stage.style.opacity = '0.2';
        stage.style.transform = 'scale(0.97) rotate(-2deg)';
        
        setTimeout(() => {
            stage.src = url;
            stage.style.opacity = '1';
            stage.style.transform = 'scale(1) rotate(0deg)';
        }, 220);
    }

    /**
     * ❤️ Wishlist AJAX Management
     * High-speed interaction using Fetch API with direct icon feedback
     */
    const wishBtn = document.getElementById('wishlistTrigger');
    if (wishBtn) {
        wishBtn.addEventListener('click', function() {
            const btn = this;
            const icon = btn.querySelector('i');
            const pId = btn.dataset.id;
            
            // Interaction feedback (Pulse effect)
            btn.style.transform = "scale(0.7) rotate(15deg)";
            
            fetch('../auth/toggle_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + pId
            })
            .then(res => res.json())
            .then(data => {
                btn.style.transform = "scale(1) rotate(0deg)";
                if (data.status === 'added') {
                    icon.className = 'fas fa-heart';
                    btn.style.color = '#ef4444';
                    if(typeof showToast === 'function') showToast('Wishlist', 'เพิ่มสินค้าลงในรายการโปรดแล้ว');
                } else if (data.status === 'removed') {
                    icon.className = 'far fa-heart';
                    btn.style.color = '#fff';
                    if(typeof showToast === 'function') showToast('Wishlist', 'นำสินค้าออกจากรายการโปรด');
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            })
            .catch(error => {
                console.error("Critical: Wishlist Logic Fail", error);
                btn.style.transform = "scale(1)";
            });
        });
    }

    /**
     * 👁️ Universal Modal Controller
     * Handles display logic and state for all premium overlays
     */
    function activateModal(id) {
        const modal = document.getElementById(id);
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Lock scrolling
    }

    function deactivateModal(id) {
        const modal = document.getElementById(id);
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    }

    function primeReport(id, type) {
        document.getElementById('rep_target_val').value = id;
        document.getElementById('rep_type_val').value = type;
        activateModal('reportModal');
    }

    /**
     * 🚀 Intersection Observer Animation Engine
     * Orchestrates the reveal of review cards on scroll
     */
    const observerX = new IntersectionObserver((entries) => {
        entries.forEach((entry, idx) => {
            if (entry.isIntersecting) {
                // Cascading reveal delay
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0) scale(1)';
                }, idx * 120);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.stagger-reveal').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(40px) scale(0.95)';
        el.style.transition = '0.8s cubic-bezier(0.19, 1, 0.22, 1)';
        observerX.observe(el);
    });

    // Outside click dismissal logic for all modals
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-universal-bg')) {
            event.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>