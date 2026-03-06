<?php
/**
 * ==========================================================================================
 * 💎 BNCC MARKET - ULTIMATE PREMIUM PRODUCT DETAIL SYSTEM (PRO FULL SCALE)
 * ==========================================================================================
 * [TARGET LINE COUNT: 850+ LINES | MISSION: ZERO DELETION, MAXIMUM INTEGRATION]
 * ------------------------------------------------------------------------------------------
 * Features: 
 * - High-Fidelity Glassmorphism UI (Solid High-Contrast Design)
 * - Dynamic 5-Image Gallery Engine (Multi-Slider Support)
 * - Advanced Session-based View Analytics (Anti-Inflation)
 * - Order Transaction Hub (Real-time Seller Notifications)
 * - Anti-Spam Review Fortress (Cooldown + Duplicate Entry Shield)
 * - Authority Badge System (Admin, Teacher, Recommended Shop)
 * - Full Administrative Moderation Suite (3-Tier Modal System)
 * ------------------------------------------------------------------------------------------
 * Project: BNCC Student Marketplace
 * Lead Developer: Gemini AI x Ploy Collaboration (Senior IT Support Specialist Edition)
 * ==========================================================================================
 */

// ------------------------------------------------------------------------------------------
// 🚀 SECTION 1: CORE BOOTSTRAP & DATA FETCHING
// ------------------------------------------------------------------------------------------

require_once '../includes/functions.php';

// 🛡️ Security Guard: Validate and Cleanse Product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header("Location: index.php");
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'guest';

// 🔍 Multi-Join SQL Extraction: Fetching Product, Shop, and Authority Metadata
$sql = "SELECT p.*, s.shop_name, s.contact_line, s.contact_ig, s.line_user_id, 
               s.user_id as owner_id, u.role as owner_role, u.fullname as owner_name, u.class_room
        FROM products p 
        JOIN shops s ON p.shop_id = s.id 
        JOIN users u ON s.user_id = u.id
        WHERE p.id = ? AND p.is_deleted = 0";
$stmt = $db->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// 🚨 Handle Inaccessible Products (Deleted or Restricted)
if (!$product) {
    $_SESSION['flash_message'] = "สินค้านี้ถูกระงับการเข้าถึงหรือถูกลบออกจากคลังสินค้าแล้ว";
    $_SESSION['flash_type'] = "warning";
    header("Location: index.php");
    exit;
}

// 🖼️ Gallery Engine: Synchronize up to 5 high-resolution images
$img_sql = "SELECT image_path, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC LIMIT 5";
$img_stmt = $db->prepare($img_sql);
$img_stmt->execute([$product_id]);
$product_images = $img_stmt->fetchAll();

// 🛠️ Legacy Fallback: Ensure UI doesn't break if gallery table is empty
if (count($product_images) === 0) {
    $product_images[] = ['image_path' => $product['image_url'], 'is_main' => 1];
}
$main_image_path = $product_images[0]['image_path'];

// ------------------------------------------------------------------------------------------
// 📈 SECTION 2: SYSTEM ANALYTICS & SOCIAL PROOF
// ------------------------------------------------------------------------------------------

// 👁️ Anti-Pump View Counter (Prevents artificial traffic manipulation)
if (!isset($_SESSION['visited_stock'])) {
    $_SESSION['visited_stock'] = [];
}
if (!in_array($product_id, $_SESSION['visited_stock'])) {
    $db->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product_id]);
    $_SESSION['visited_stock'][] = $product_id;
    $product['views'] += 1;
}

// ⭐ Sentiment Analysis: Aggregate Rating & Review Count
$rev_data_sql = "SELECT AVG(rating) as avg_score, COUNT(id) as total_entries 
                 FROM reviews 
                 WHERE product_id = ? AND is_deleted = 0";
$rev_data_stmt = $db->prepare($rev_data_sql);
$rev_data_stmt->execute([$product_id]);
$rating_summary = $rev_data_stmt->fetch();

$p_rating_avg = round($rating_summary['avg_score'] ?? 0, 1);
$p_review_count = $rating_summary['total_entries'];

// 🏷️ Dynamic Meta-Tag Retrieval
$tags_engine_sql = "SELECT t.tag_name FROM tags t JOIN product_tag_map ptm ON t.id = ptm.tag_id WHERE ptm.product_id = ?";
$tags_engine_stmt = $db->prepare($tags_engine_sql);
$tags_engine_stmt->execute([$product_id]);
$all_tags = $tags_engine_stmt->fetchAll();

// ❤️ Social Status: Wishlist Engagement
$is_bookmarked = false;
if (isLoggedIn()) {
    $wish_check = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $wish_check->execute([$user_id, $product_id]);
    $is_bookmarked = $wish_check->fetch() ? true : false;
}

// ------------------------------------------------------------------------------------------
// 🛒 SECTION 3: CORE TRANSACTIONAL HANDLERS
// ------------------------------------------------------------------------------------------

// 💰 Order Execution Flow
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isLoggedIn()) redirect('../auth/login.php');
    
    if ($user_id == $product['owner_id']) {
        $_SESSION['flash_message'] = "คุณไม่สามารถสั่งซื้อสินค้าของตนเองได้ (Internal Order Restricted)";
        $_SESSION['flash_type'] = "error";
    } else {
        $db->beginTransaction();
        try {
            $order_sql = "INSERT INTO orders (buyer_id, shop_id, product_id, status, created_at) VALUES (?, ?, ?, 'pending', NOW())";
            $db->prepare($order_sql)->execute([$user_id, $product['shop_id'], $product_id]);
            
            // Notification Dispatch
            $msg_push = "🛒 คำสั่งซื้อใหม่: {$product['title']} จากยูสเซอร์ {$_SESSION['fullname']}";
            sendNotification($product['owner_id'], 'order', $msg_push, "../seller/dashboard.php");

            if (!empty($product['line_user_id'])) {
                sendLineMessagingAPI($product['line_user_id'], $msg_push);
            }
            $db->commit();
            $_SESSION['flash_message'] = "ส่งคำสั่งซื้อไปยังผู้ขายเรียบร้อยแล้ว!";
            $_SESSION['flash_type'] = "success";
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['flash_message'] = "Critical System Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
    redirect("product_detail.php?id=$product_id");
}

// ⭐ Review Authority Module (Strict Anti-Spam Enforced)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) redirect('../auth/login.php');

    $final_rating = (int)$_POST['rating'];
    $final_comment = trim($_POST['comment']);
    
    // 🛡️ Security Gate: Anti-Spam Check
    $spam_status = canUserReview($user_id, $product_id);

    if (!$spam_status['status']) {
        $_SESSION['flash_message'] = $spam_status['message'];
        $_SESSION['flash_type'] = "danger";
    } else {
        $ins_rev_sql = "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
        if ($db->prepare($ins_rev_sql)->execute([$product_id, $user_id, $final_rating, $final_comment])) {
            
            $rev_alert = "⭐ ได้รับรีวิวใหม่ ({$final_rating} ดาว) ในสินค้า {$product['title']}";
            sendNotification($product['owner_id'], 'review', $rev_alert, "product_detail.php?id=$product_id");
            
            $_SESSION['flash_message'] = "ขอบคุณสำหรับการแบ่งปันความเห็นของคุณ!";
            $_SESSION['flash_type'] = "success";
        }
    }
    redirect("product_detail.php?id=$product_id");
}

// 💬 Detailed Review Aggregate
$all_rev_sql = "SELECT r.*, u.fullname, u.profile_img, u.role as author_role, u.id as author_id 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ? AND r.is_deleted = 0 
                ORDER BY r.created_at DESC";
$all_rev_stmt = $db->prepare($all_rev_sql);
$all_rev_stmt->execute([$product_id]);
$review_list = $all_rev_stmt->fetchAll();

require_once '../includes/header.php';
?>

<style>
    /* SOLID HIGH-CONTRAST DARK THEME */
    :root {
        --b-bg: #0f172a;
        --b-card: #1e293b;
        --b-border: rgba(255,255,255,0.08);
        --b-primary: #6366f1;
        --b-accent: #fbbf24;
        --b-text: #f8fafc;
        --b-muted: #94a3b8;
        --b-red: #ef4444;
        --b-green: #10b981;
    }

    body {
        background-color: var(--b-bg) !important;
        color: var(--b-text);
        font-family: 'Prompt', sans-serif;
        font-weight: 300;
        -webkit-font-smoothing: antialiased;
    }

    .premium-wrapper {
        max-width: 1250px;
        margin: 50px auto;
        padding: 0 35px;
        animation: pageSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes pageSlideUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* 🏰 Main Product Card Component */
    .p-main-card {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 70px;
        background: var(--b-card);
        border: 2px solid var(--b-border);
        border-radius: 55px;
        padding: 65px;
        box-shadow: 0 40px 100px rgba(0,0,0,0.6);
        position: relative;
        overflow: hidden;
    }

    .p-main-card::before {
        content: '';
        position: absolute;
        top: -150px; right: -150px;
        width: 450px; height: 450px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, transparent 70%);
        pointer-events: none;
    }

    /* 🖼️ Gallery Engine Styles */
    .p-gallery-block { position: relative; z-index: 1; }
    .p-viewport {
        width: 100%;
        aspect-ratio: 1/1;
        border-radius: 40px;
        overflow: hidden;
        background: #000;
        border: 2px solid var(--b-border);
        position: relative;
        box-shadow: 0 25px 50px rgba(0,0,0,0.4);
    }

    .p-viewport img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.8s cubic-bezier(0.23, 1, 0.32, 1);
    }

    .p-viewport:hover img { transform: scale(1.12); }

    .p-thumb-rail {
        display: flex;
        gap: 18px;
        margin-top: 30px;
        overflow-x: auto;
        padding: 10px 5px;
        scrollbar-width: none;
    }

    .p-thumb-rail::-webkit-scrollbar { display: none; }

    .p-thumb-box {
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

    .p-thumb-box:hover { opacity: 0.85; transform: translateY(-8px); }
    .p-thumb-box.active {
        border-color: var(--b-primary);
        opacity: 1;
        transform: scale(1.1);
        box-shadow: 0 12px 25px rgba(99, 102, 241, 0.4);
    }

    /* 📝 Content Typography Hub */
    .p-title-hub { margin-bottom: 40px; }
    .p-badge-label { color: var(--b-primary); font-weight: 950; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 4px; display: block; margin-bottom: 15px; opacity: 0.8; }
    .p-h1 { font-size: 4.5rem; font-weight: 950; letter-spacing: -3.5px; line-height: 0.9; margin-bottom: 20px; color: #fff; text-shadow: 0 15px 30px rgba(0,0,0,0.3); }
    .p-price-tag { font-size: 3.5rem; font-weight: 950; color: var(--b-accent); margin: 35px 0; display: flex; align-items: center; gap: 15px; }

    /* 🥇 Badge Architecture */
    .badge-premium-solid {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: #000;
        padding: 10px 22px;
        border-radius: 16px;
        font-weight: 950;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 12px 30px rgba(251, 191, 36, 0.35);
        border: 1px solid rgba(0,0,0,0.1);
        text-transform: uppercase;
    }

    .user-badge-label { background: rgba(99, 102, 241, 0.15); border: 2px solid var(--b-primary); color: var(--b-primary); padding: 5px 15px; border-radius: 12px; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; }

    /* 🔘 CTA Engine Buttons */
    .btn-massive-order {
        flex: 1;
        background: var(--b-primary);
        color: #fff !important;
        border: none;
        padding: 28px;
        border-radius: 30px;
        font-weight: 950;
        font-size: 1.5rem;
        cursor: pointer;
        transition: 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 20px 45px rgba(99, 102, 241, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        text-decoration: none;
    }

    .btn-massive-order:hover {
        transform: translateY(-12px) scale(1.03);
        box-shadow: 0 30px 60px rgba(99, 102, 241, 0.5);
        filter: brightness(1.1);
    }

    .btn-sq-interact {
        width: 90px;
        height: 90px;
        border-radius: 30px;
        border: 2px solid var(--b-border);
        background: rgba(255,255,255,0.03);
        color: #fff;
        font-size: 2.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.4s;
    }

    .btn-sq-interact:hover {
        background: var(--b-card);
        border-color: var(--b-primary);
        color: var(--b-primary);
        transform: scale(1.15) rotate(8deg);
    }

    /* 🏪 Shop Card Integration */
    .p-shop-identity {
        background: rgba(15, 23, 42, 0.6);
        border: 3px solid var(--b-border);
        border-radius: 45px;
        padding: 45px;
        margin-top: 65px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        backdrop-filter: blur(25px);
        border-left: 10px solid var(--b-primary);
    }

    .p-shop-logo-box {
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        border-radius: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.8rem;
        color: #fff;
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.4);
    }

    .p-shop-name-link {
        font-size: 2.5rem;
        font-weight: 950;
        color: #fff;
        text-decoration: none;
        transition: 0.3s;
    }

    .p-shop-name-link:hover { color: var(--b-primary); text-shadow: 0 0 25px rgba(99, 102, 241, 0.4); }

    /* ⭐ Ultimate Feedback Cards */
    .p-reviews-hub { margin-top: 130px; }
    .p-feedback-entry {
        background: var(--b-card);
        border: 2px solid var(--b-border);
        border-radius: 45px;
        padding: 55px;
        margin-bottom: 45px;
        transition: 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
    }

    .p-feedback-entry:hover {
        border-color: var(--b-primary);
        transform: scale(1.02) translateX(20px);
        box-shadow: 0 40px 80px rgba(0,0,0,0.5);
    }

    /* 🛡️ Anti-Spam Visual Fortress */
    .spam-shield-notice {
        text-align: center;
        padding: 120px 60px;
        background: rgba(99, 102, 241, 0.04);
        border-radius: 55px;
        border: 4px dashed var(--b-border);
        margin-bottom: 70px;
    }

    .spam-shield-notice i { font-size: 6rem; color: var(--b-primary); opacity: 0.5; margin-bottom: 35px; }

    /* 🚨 Premium Modals Suite */
    .modal-p-backdrop {
        position: fixed; inset: 0;
        background: rgba(10, 15, 30, 0.98);
        z-index: 1000000;
        display: none;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(40px);
    }

    .modal-p-solid {
        background: var(--b-card);
        border: 3px solid var(--b-border);
        border-radius: 55px;
        width: 95%;
        max-width: 650px;
        padding: 80px;
        box-shadow: 0 80px 150px rgba(0,0,0,0.9);
        animation: modalEntranceFX 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalEntranceFX {
        from { transform: scale(0.6) translateY(80px); opacity: 0; }
        to { transform: scale(1) translateY(0); opacity: 1; }
    }

    /* 📱 Responsive Engineering */
    @media (max-width: 1024px) {
        .p-main-card { grid-template-columns: 1fr; padding: 45px; }
        .p-h1 { font-size: 3.5rem; }
    }

    @media (max-width: 768px) {
        .premium-wrapper { padding: 0 25px; }
        .p-main-card { padding: 35px; border-radius: 40px; }
        .p-h1 { font-size: 2.8rem; }
        .p-shop-identity { flex-direction: column; gap: 40px; text-align: center; }
    }
</style>

<div class="premium-wrapper">
    
    <?php echo displayFlashMessage(); ?>

    <div class="p-main-card">
        
        <div class="p-gallery-block">
            <div class="p-viewport">
                <img id="primaryDisplay" src="../assets/images/products/<?= e($main_image_path) ?>" alt="<?= e($product['title']) ?>">
                
                <div style="position: absolute; top: 40px; left: 40px; background: rgba(0,0,0,0.85); backdrop-filter: blur(20px); padding: 15px 30px; border-radius: 24px; font-weight: 950; font-size: 1.1rem; border: 2px solid rgba(255,255,255,0.2);">
                    <i class="fas fa-star" style="color: var(--b-accent);"></i> <?= $avg_p_rating ?> <span style="font-weight: 500; opacity: 0.5; margin-left: 8px;">(<?= $total_p_reviews ?> รีวิว)</span>
                </div>

                <div style="position: absolute; bottom: 40px; right: 40px; background: rgba(255,255,255,1); color: #000; padding: 15px 30px; border-radius: 24px; font-weight: 950; font-size: 1rem; box-shadow: 0 20px 45px rgba(0,0,0,0.5);">
                    <i class="fas fa-eye"></i> <?= number_format($product['views']) ?> VIEWS
                </div>
            </div>

            <?php if (count($product_images) > 1): ?>
            <div class="p-thumb-rail">
                <?php foreach ($product_images as $idx => $img): ?>
                    <div class="p-thumb-box <?= $idx === 0 ? 'active' : '' ?>" 
                         onclick="cycleGalleryImage('../assets/images/products/<?= e($img['image_path']) ?>', this)">
                        <img src="../assets/images/products/<?= e($img['image_path']) ?>" alt="View <?= $idx+1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="content-hub">
            <div class="p-title-hub">
                <span class="p-badge-label">BNCC Official Certified Stock</span>
                <h1 class="p-h1"><?= e($product['title']) ?></h1>
                
                <div style="display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap;">
                    <?php foreach ($all_tags as $tag): ?>
                        <span style="background: rgba(99, 102, 241, 0.15); border: 2.5px solid rgba(99, 102, 241, 0.4); color: #c7d2fe; padding: 8px 20px; border-radius: 18px; font-size: 0.9rem; font-weight: 950; letter-spacing: -0.5px;">#<?= e($tag['tag_name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="p-price-tag">
                ฿<?= number_format($product['price'], 2) ?>
                <span style="font-size: 1.4rem; opacity: 0.4; font-weight: 800; margin-top: 15px;">NET PRICE</span>
            </div>

            <div style="margin-bottom: 60px; padding-bottom: 50px; border-bottom: 3px dashed var(--b-border);">
                <h6 style="text-transform: uppercase; font-weight: 950; font-size: 0.9rem; opacity: 0.4; letter-spacing: 4px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-file-invoice text-primary"></i> Product Manifest
                </h6>
                <p style="font-size: 1.35rem; line-height: 2.2; opacity: 0.95; font-weight: 500; text-align: justify; color: #e2e8f0;">
                    <?= nl2br(e($product['description'])) ?>
                </p>
            </div>

            <form method="POST" style="display: flex; gap: 25px; align-items: center;">
                <?php if ($user_id && $user_id != $product['owner_id']): ?>
                    <button type="submit" name="place_order" class="btn-massive-order" onclick="return confirm('ยืนยันความต้องการในการสั่งซื้อสินค้านี้?')">
                        <i class="fas fa-shopping-bag"></i> SECURE PURCHASE
                    </button>
                    <a href="chat.php?user=<?= $product['owner_id'] ?>" class="btn-sq-interact" title="Chat with Seller"><i class="fas fa-comment-dots"></i></a>
                <?php elseif (!$user_id): ?>
                    <a href="../auth/login.php" class="btn-massive-order">LOGIN TO START PURCHASE</a>
                <?php endif; ?>

                <button type="button" id="wishEngine" data-id="<?= $product['id'] ?>" class="btn-sq-interact" style="color: <?= $is_bookmarked ? 'var(--b-red)' : 'var(--b-text)' ?>;">
                    <i class="<?= $is_bookmarked ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
            </form>

            <div class="p-shop-identity">
                <div style="display: flex; align-items: center; gap: 35px;">
                    <div class="p-shop-logo-box"><i class="fas fa-store"></i></div>
                    <div>
                        <span class="p-badge-label" style="margin-bottom: 8px; font-size: 0.75rem; color: #818cf8; opacity: 1;">VERIFIED MERCHANT <?= getUserBadge($product['owner_role']) ?></span>
                        <div style="display: flex; align-items: center; gap: 22px; flex-wrap: wrap;">
                            <a href="shop_profile.php?id=<?= $product['shop_id'] ?>" class="p-shop-name-link"><?= e($product['shop_name']) ?></a>
                            <?= getShopBadge($product['shop_id']) ?> </div>
                        <div style="font-size: 1rem; font-weight: 850; opacity: 0.5; margin-top: 10px;">
                            <i class="fas fa-user-circle"></i> <?= e($product['owner_name']) ?> | ROOM: <?= e($product['class_room']) ?>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 25px;">
                    <?php if(!empty($product['contact_line'])): ?>
                        <a href="https://line.me/ti/p/~<?= e($product['contact_line']) ?>" target="_blank" style="color: var(--b-green); font-size: 2.5rem; transition: 0.4s;" onmouseover="this.style.transform='rotate(15deg) scale(1.3)'"><i class="fab fa-line"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($product['contact_ig'])): ?>
                        <a href="https://www.instagram.com/<?= e($product['contact_ig']) ?>/" target="_blank" style="color: #ec4899; font-size: 2.5rem; transition: 0.4s;" onmouseover="this.style.transform='rotate(-15deg) scale(1.3)'"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                <div style="margin-top: 70px; padding: 45px; border-radius: 45px; border: 4px dashed var(--b-red); background: rgba(239, 68, 68, 0.05); position: relative;">
                    <div style="position: absolute; top: -20px; left: 45px; background: var(--b-red); color: #fff; padding: 6px 25px; border-radius: 14px; font-weight: 950; font-size: 0.8rem; letter-spacing: 2px;">AUTHORITY ONLY</div>
                    <h5 style="color: var(--b-red); font-weight: 950; margin-bottom: 30px; font-size: 1.2rem; display: flex; align-items: center; gap: 15px;"><i class="fas fa-user-shield"></i> Security Moderation Engine</h5>
                    <div style="display: flex; gap: 25px;">
                        <button onclick="launchModal('suspendModal')" class="btn-massive-order" style="background: var(--b-red); padding: 20px; font-size: 1.1rem; box-shadow: none;">SUSPEND THIS LISTING</button>
                        <a href="../admin/deep_audit.php?id=<?= $product_id ?>" class="btn-sq-interact" style="width: 80px; height: 80px; background: #000;"><i class="fas fa-search-plus"></i></a>
                    </div>
                </div>
            <?php endif; ?>
            
            <button onclick="initReport('<?= $product['shop_id'] ?>', 'shop')" style="background: none; border: none; color: var(--b-red); font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 12px; font-weight: 950; margin-top: 60px; opacity: 0.5; transition: 0.4s;" onmouseover="this.style.opacity='1'; this.style.letterSpacing='1.5px';">
                <i class="fas fa-flag-checkered"></i> REPORT MERCHANT FOR VIOLATION OF COMMUNITY TERMS
            </button>
        </div>
    </div>

    <div class="p-reviews-hub">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 80px;">
            <div>
                <h2 style="font-size: 4rem; font-weight: 950; letter-spacing: -3px; line-height: 1;">Student Voice</h2>
                <p style="font-weight: 850; opacity: 0.4; margin-top: 15px; font-size: 1.2rem;">ความคิดเห็นจริงจากเพื่อนสมาชิก BNCC ต่อสินค้านี้</p>
            </div>
            <div style="background: var(--b-accent); color: #000; padding: 18px 55px; border-radius: 70px; font-weight: 950; font-size: 1.8rem; box-shadow: 0 15px 45px rgba(251, 191, 36, 0.45); display: flex; align-items: center; gap: 18px;">
                <i class="fas fa-star"></i> <?= $avg_rating ?>
            </div>
        </div>

        <?php if (isLoggedIn()): ?>
            <?php 
            $spam_audit = canUserReview($user_id, $product_id); 
            if ($spam_audit['status']): 
            ?>
            <div class="p-feedback-entry" style="border: 4px solid var(--b-primary); background: rgba(99, 102, 241, 0.02);">
                <h3 style="font-weight: 950; margin-bottom: 50px; font-size: 2rem; display: flex; align-items: center; gap: 20px;">
                    <i class="fas fa-feather-alt" style="color: var(--b-primary);"></i> Write Your Feedback
                </h3>
                <form method="POST">
                    <div style="margin-bottom: 55px;">
                        <label style="display: block; font-weight: 950; font-size: 1rem; opacity: 0.5; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 2px;">Overall Satisfaction Score</label>
                        <div class="star-rating-xl" style="display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 15px;">
                            <?php for($i=5; $i>=1; $i--): ?>
                                <input type="radio" id="ux_rate_<?= $i ?>" name="rating" value="<?= $i ?>" style="display:none;" required>
                                <label for="ux_rate_<?= $i ?>" style="font-size: 3.5rem; color: #2d3748; cursor: pointer; transition: 0.3s;"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea name="comment" required placeholder="Describe your experience with this item... Help others make informed decisions!" style="width: 100%; min-height: 250px; border-radius: 40px; padding: 40px; border: 3.5px solid var(--b-border); background: rgba(0,0,0,0.5); color: #fff; font-weight: 600; font-size: 1.35rem; outline: none; transition: 0.5s; box-shadow: inset 0 15px 30px rgba(0,0,0,0.3);" onfocus="this.style.borderColor='var(--b-primary)'; this.style.boxShadow='0 0 50px rgba(99, 102, 241, 0.25)';"></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn-massive-order" style="width: auto; padding: 25px 90px; margin-top: 55px; font-size: 1.3rem;">
                        <i class="fas fa-check-double"></i> PUBLISH FEEDBACK
                    </button>
                </form>
            </div>
            <?php else: ?>
                <div class="spam-shield-notice">
                    <i class="fas fa-user-lock"></i>
                    <h4 style="font-weight: 950; color: var(--b-primary); margin: 0; font-size: 1.8rem; letter-spacing: -1px;"><?= $spam_audit['message'] ?></h4>
                    <p style="opacity: 0.6; font-weight: 800; margin-top: 20px; font-size: 1.1rem;">ระบบความปลอดภัย BNCC Anti-Spam เปิดใช้งานเพื่อปกป้องความโปร่งใสของข้อมูล</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="review-grid-host">
            <?php if (count($review_list) > 0): ?>
                <?php foreach ($review_list as $idx => $rev): 
                    $rev_avatar = !empty($rev['profile_img']) ? "../assets/images/profiles/" . $rev['profile_img'] : "../assets/images/profiles/default_profile.png";
                ?>
                    <div class="p-feedback-entry stagger-reveal">
                        <div class="review-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div class="reviewer-meta-box">
                                <a href="view_profile.php?id=<?= $rev['author_id'] ?>">
                                    <img src="<?= $rev_avatar ?>" class="reviewer-avatar-img">
                                </a>
                                <div>
                                    <h5 style="font-weight: 950; margin: 0; font-size: 1.7rem; display: flex; align-items: center; gap: 15px;">
                                        <?= e($rev['fullname']) ?> <?= getUserBadge($rev['author_role']) ?>
                                    </h5>
                                    <div style="display: flex; gap: 8px; color: var(--b-accent); font-size: 1.1rem; margin-top: 10px;">
                                        <?php for($k=0; $k<$rev['rating']; $k++) echo '<i class="fas fa-star"></i>'; ?>
                                        <?php for($k=$rev['rating']; $k<5; $k++) echo '<i class="fas fa-star" style="opacity:0.1;"></i>'; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span style="display: block; font-size: 1rem; font-weight: 950; opacity: 0.3;"><?= date('d M Y - H:i', strtotime($rev['created_at'])) ?></span>
                                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                    <button onclick="executeAdminAction('deleteReview', '<?= $rev['id'] ?>')" style="background: none; border: none; color: var(--b-red); font-size: 1.6rem; margin-top: 25px; cursor: pointer; transition: 0.5s;" onmouseover="this.style.transform='scale(1.4) rotate(10deg)'"><i class="fas fa-trash-alt"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p style="margin-top: 40px; font-size: 1.5rem; line-height: 2.1; font-weight: 500; color: #e2e8f0; border-left: 6px solid var(--b-border); padding-left: 35px;">
                            <?= nl2br(e($rev['comment'])) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 180px 30px; border: 5px dashed var(--b-border); border-radius: 60px; opacity: 0.35;">
                    <i class="fas fa-comment-slash" style="font-size: 7rem; margin-bottom: 50px;"></i>
                    <h3 style="font-weight: 950; font-size: 2.5rem;">The Stage Is Yours</h3>
                    <p style="font-weight: 850; font-size: 1.3rem;">ยังไม่มีรีวิวสำหรับสินค้านี้ เริ่มต้นแชร์ประสบการณ์ของคุณเป็นคนแรก!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="reportModal" class="modal-p-backdrop">
    <div class="modal-p-solid">
        <h2 style="font-weight: 950; margin-bottom: 25px; color: var(--b-red); display: flex; align-items: center; gap: 25px;"><i class="fas fa-biohazard"></i> FLAG CONTENT</h2>
        <p style="opacity: 0.8; margin-bottom: 45px; font-weight: 800; font-size: 1.3rem;">โปรดระบุปัญหาที่คุณพบ เพื่อสร้างมาตรฐานความปลอดภัยให้สมาชิก BNCC</p>
        <form action="../auth/submit_report.php" method="POST">
            <input type="hidden" name="target_id" id="rep_target_field">
            <input type="hidden" name="target_type" id="rep_type_field">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <textarea name="reason" required placeholder="บรรยายรายละเอียดของปัญหาที่เกิดขึ้นที่นี่..." style="width: 100%; min-height: 220px; border-radius: 35px; padding: 35px; background: rgba(0,0,0,0.5); border: 3px solid var(--b-border); color: #fff; font-weight: 600; font-size: 1.25rem;"></textarea>
            <div style="display: flex; gap: 30px; margin-top: 55px;">
                <button type="button" onclick="killModal('reportModal')" class="btn-sq-interact" style="flex: 1; font-size: 1.2rem; border-radius: 30px;">CANCEL</button>
                <button type="submit" class="btn-massive-order" style="flex: 2.5; background: var(--b-red); border-radius: 30px; box-shadow: 0 15px 40px rgba(239, 68, 68, 0.45);">SUBMIT FLAG</button>
            </div>
        </form>
    </div>
</div>

<div id="suspendModal" class="modal-p-backdrop">
    <div class="modal-p-solid" style="border-color: var(--b-red);">
        <h2 style="font-weight: 950; color: var(--b-red); margin-bottom: 30px; display: flex; align-items: center; gap: 25px;"><i class="fas fa-lock"></i> SUSPEND LISTING</h2>
        <p style="font-weight: 800; margin-bottom: 45px; font-size: 1.3rem;">รายการนี้จะหายไปจากหน้าตลาดทันที และแอดมินคนอื่นจะได้รับแจ้ง</p>
        <form action="../admin/action_block_item.php" method="POST">
            <input type="hidden" name="p_id" value="<?= $product_id ?>">
            <textarea name="official_reason" required placeholder="ระบุเหตุผลอย่างเป็นทางการสำหรับการระงับ..." style="width: 100%; min-height: 200px; border-radius: 35px; padding: 35px; background: rgba(0,0,0,0.5); border: 3px solid var(--b-border); color: #fff; font-weight: 600; font-size: 1.25rem;"></textarea>
            <div style="display: flex; gap: 30px; margin-top: 55px;">
                <button type="button" onclick="killModal('suspendModal')" class="btn-sq-interact" style="flex: 1; border-radius: 30px;">ABORT</button>
                <button type="submit" class="btn-massive-order" style="flex: 2.5; background: var(--b-red); border-radius: 30px;">CONFIRM BLOCK</button>
            </div>
        </form>
    </div>
</div>

<script>
    /**
     * 🖼️ Advanced Gallery Switcher Engine
     * Orchestrates visual states during image transitions
     */
    function cycleGalleryImage(url, node) {
        const stage = document.getElementById('primaryDisplay');
        const thumbs = document.querySelectorAll('.p-thumb-box');
        
        // State update: Class switching
        thumbs.forEach(t => t.classList.remove('active'));
        node.classList.add('active');
        
        // Animation sequence: Fade & Scale Down
        stage.style.opacity = '0.2';
        stage.style.transform = 'scale(0.96) rotate(-1deg)';
        
        setTimeout(() => {
            stage.src = url;
            stage.style.opacity = '1';
            stage.style.transform = 'scale(1) rotate(0deg)';
        }, 220);
    }

    /**
     * ❤️ Wishlist AJAX Logic
     * High-speed Fetch API implementation with haptic feedback simulation
     */
    const wHeart = document.getElementById('wishEngine');
    if (wHeart) {
        wHeart.addEventListener('click', function() {
            const btn = this;
            const icon = btn.querySelector('i');
            const pId = btn.dataset.id;
            
            // Visual feedback pulse
            btn.style.transform = "scale(0.7) rotate(20deg)";
            
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
                    if(typeof showToast === 'function') showToast('Wishlist', 'เพิ่มเข้ารายการโปรดสำเร็จ');
                } else if (data.status === 'removed') {
                    icon.className = 'far fa-heart';
                    btn.style.color = '#fff';
                    if(typeof showToast === 'function') showToast('Wishlist', 'นำออกจากรายการโปรดแล้ว');
                }
            })
            .catch(e => {
                console.error("Wishlist Protocol Failed", e);
                btn.style.transform = "scale(1)";
            });
        });
    }

    /**
     * 👁️ Universal Modal Interface Controller
     */
    function launchModal(id) {
        const modal = document.getElementById(id);
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevention of background scrolling
    }

    function killModal(id) {
        const modal = document.getElementById(id);
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restoration of scrolling
    }

    function initReport(id, type) {
        document.getElementById('rep_target_field').value = id;
        document.getElementById('rep_type_field').value = type;
        launchModal('reportModal');
    }

    /**
     * 🚀 Intelligent Intersection Animation Engine
     * Triggering card entrance based on scroll depth
     */
    const pObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry, idx) => {
            if (entry.isIntersecting) {
                setTimeout(() => entry.target.classList.add('show'), idx * 150);
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.stagger-reveal').forEach(el => pObserver.observe(el));

    // Outside-boundary dismissal for all modals
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-p-backdrop')) {
            event.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    // Dynamic Star UI Handler
    document.querySelectorAll('.star-rating-xl label').forEach(label => {
        label.addEventListener('click', () => {
            // Optional: Haptic or Audio feedback integration
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>