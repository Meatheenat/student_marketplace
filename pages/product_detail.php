<?php
/**
 * ============================================================
 * 💎 BNCC MARKET - PREMIUM PRODUCT DETAIL SYSTEM (5-IMAGE SUPPORT)
 * ============================================================
 * Features: 
 * - High-Contrast Solid UI
 * - Multiple Image Slider (Gallery)
 * - Advanced Order Management Integration
 * - Dynamic Review & Rating System
 * - Session-based View Counter
 * - Admin Control Panel
 * - [UPDATED] User Badges & Anti-Spam Reviews
 * * Project: BNCC Student Marketplace
 * Developer: Meeatheenat
 * ============================================================
 */
require_once '../includes/functions.php';

$product_id = $_GET['id'] ?? null;
if (!$product_id) redirect('index.php');

$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;

// 1. SQL: ดึงข้อมูลสินค้า (🎯 ปรับเพิ่ม Join users เพื่อเอา role เจ้าของร้านมาโชว์ Badge)
$stmt = $db->prepare("SELECT p.*, s.shop_name, s.contact_line, s.contact_ig, s.line_user_id, s.user_id as owner_id, u.role as owner_role 
                      FROM products p 
                      JOIN shops s ON p.shop_id = s.id 
                      JOIN users u ON s.user_id = u.id
                      WHERE p.id = ? AND p.is_deleted = 0");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// 🚨 ถ้าโดนลบไปแล้ว ให้เด้งกลับ
if (!$product) {
    redirect('index.php');
}

// -------------------------------------------------------------------
// 🎯 🛠️ [เพิ่มใหม่] SQL: ดึงรูปภาพทั้งหมดจากตาราง product_images
// -------------------------------------------------------------------
$img_stmt = $db->prepare("SELECT image_path, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
$img_stmt->execute([$product_id]);
$product_images = $img_stmt->fetchAll();

// ถ้าไม่มีรูปในตาราง product_images ให้ดึงจากตาราง products (รองรับข้อมูลเก่า)
if (count($product_images) === 0) {
    $product_images[] = ['image_path' => $product['image_url'], 'is_main' => 1];
}

// กำหนดรูปหลัก (รูปแรกที่จะโชว์)
$main_image = $product_images[0]['image_path'];

// -------------------------------------------------------------------

// 🎯 🛠️ ระบบนับยอดวิวแบบกันปั๊ม (Session Based)
if (!isset($_SESSION['viewed_products'])) {
    $_SESSION['viewed_products'] = []; 
}

if (!in_array($product_id, $_SESSION['viewed_products'])) {
    $update_views = $db->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
    $update_views->execute([$product_id]);
    $_SESSION['viewed_products'][] = $product_id;
}

// 2. คำนวณเรตติ้ง
$rating_summary_stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ? AND is_deleted = 0");
$rating_summary_stmt->execute([$product_id]);
$rating_info = $rating_summary_stmt->fetch();
$avg_p_rating = round($rating_info['avg_rating'] ?? 0, 1);
$total_p_reviews = $rating_info['total_reviews'];

$tag_stmt = $db->prepare("SELECT t.tag_name FROM tags t JOIN product_tag_map ptm ON t.id = ptm.tag_id WHERE ptm.product_id = ?");
$tag_stmt->execute([$product_id]);
$product_tags = $tag_stmt->fetchAll();

// 3. ตรวจสอบ Wishlist
$is_wishlisted = false;
if (isLoggedIn()) {
    $check_wish = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check_wish->execute([$user_id, $product_id]);
    $is_wishlisted = $check_wish->fetch() ? true : false;
}

// 🎯 🛠️ ประมวลผลการสั่งซื้อสินค้า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isLoggedIn()) redirect('../auth/login.php');
    
    if ($user_id == $product['owner_id']) {
        $_SESSION['flash_message'] = "คุณไม่สามารถสั่งซื้อสินค้าของร้านตัวเองได้";
        $_SESSION['flash_type'] = "error";
    } else {
        $ins_order = $db->prepare("INSERT INTO orders (buyer_id, shop_id, product_id) VALUES (?, ?, ?)");
        if ($ins_order->execute([$user_id, $product['shop_id'], $product_id])) {
            
            // แจ้งเตือนกระดิ่งบนเว็บ (ส่งให้เจ้าของร้าน)
            $notif_msg = "🛒 มีคำสั่งซื้อใหม่สำหรับสินค้า {$product['title']} จากคุณ {$_SESSION['fullname']}";
            sendNotification($product['owner_id'], 'order', $notif_msg, "../seller/dashboard.php");

            // แจ้งเตือนคนขายผ่าน LINE (ถ้าผูกไว้)
            if (!empty($product['line_user_id'])) {
                $msg = "🛒 มีคำสั่งซื้อใหม่!\nสินค้า: " . $product['title'] . "\nจากคุณ: " . $_SESSION['fullname'] . "\nกรุณาตรวจสอบในหน้า Dashboard ของคุณ";
                sendLineMessagingAPI($product['line_user_id'], $msg);
            }
            $_SESSION['flash_message'] = "ส่งคำสั่งซื้อสำเร็จ! กรุณารอผู้ขายยืนยันและติดต่อกลับ";
            $_SESSION['flash_type'] = "success";
        }
    }
    redirect("product_detail.php?id=$product_id");
}
// --- แทนที่ตั้งแต่ if (isset($_POST['submit_review'])) จนถึง redirect บรรทัด 137 ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
        $rating = $_POST['rating'];
        $comment = trim($_POST['comment']);
        $spam_check = canUserReview($user_id, $product_id);
        if (!$spam_check['status']) {
            $_SESSION['flash_message'] = $spam_check['message'];
            $_SESSION['flash_type'] = "danger";
        } else {
            $ins = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
            if ($ins->execute([$product_id, $user_id, $rating, $comment])) {
                $_SESSION['flash_message'] = "บันทึกรีวิวสำเร็จ";
                $_SESSION['flash_type'] = "success";
            }
        }
        redirect("product_detail.php?id=$product_id");
    }

    // 🟢 วางต่อท้ายตรงนี้เลย: ส่วนแก้ไขและลบรีวิว
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_review_submit'])) {
        $r_id = (int)$_POST['review_id'];
        $r_rating = (int)$_POST['rating'];
        $r_comment = trim($_POST['comment']);
        $stmt = $db->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$r_rating, $r_comment, $r_id, $user_id])) {
            $_SESSION['flash_message'] = "แก้ไขรีวิวเรียบร้อย"; $_SESSION['flash_type'] = "success";
        }
        redirect("product_detail.php?id=$product_id");
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete_my_review') {
        $del_id = (int)$_GET['rev_id'];
        $stmt = $db->prepare("UPDATE reviews SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$del_id, $user_id])) {
            $_SESSION['flash_message'] = "ลบรีวิวแล้ว"; $_SESSION['flash_type'] = "success";
        }
        redirect("product_detail.php?id=$product_id");
    }
// 4. ส่งรีวิว (🎯 [UPDATED] เพิ่มระบบ Anti-Spam Check ก่อนบันทึก)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) 
    {
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);
    
    // เรียกใช้ฟังก์ชันกันสแปมที่เราเพิ่งสร้างไว้ใน functions.php
    $spam_check = canUserReview($user_id, $product_id);

    if (!$spam_check['status']) {
        $_SESSION['flash_message'] = $spam_check['message'];
        $_SESSION['flash_type'] = "danger";
        redirect("product_detail.php?id=$product_id");
    }

    $ins = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    if ($ins->execute([$product_id, $user_id, $rating, $comment])) {
        
        // แจ้งเตือนกระดิ่งเมื่อมีคนมารีวิว
        $notif_msg = "⭐ มีรีวิวใหม่ ({$rating} ดาว) ในสินค้า {$product['title']}";
        sendNotification($product['owner_id'], 'review', $notif_msg, "product_detail.php?id=$product_id");

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
        redirect("product_detail.php?id=$product_id");
    }
}

// 5. ดึงรีวิวที่ยังไม่โดนลบ
$rev_stmt = $db->prepare("
    SELECT r.*, u.fullname, u.profile_img, u.id as author_id 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? AND r.is_deleted = 0
    ORDER BY r.created_at DESC
");
$rev_stmt->execute([$product_id]);
$all_reviews = $rev_stmt->fetchAll();

require_once '../includes/header.php';
?>

<style>
    /* ============================================================
       🛠️ SOLID DESIGN SYSTEM - HIGH FIDELITY
       ============================================================ */
    :root {
        --solid-bg: #f8fafc;
        --solid-card: #ffffff;
        --solid-text: #0f172a;
        --solid-border: #cbd5e1;
        --solid-primary: #4f46e5;
        --solid-secondary: #10b981;
        --solid-danger: #ef4444;
        --solid-warning: #fbbf24;
    }

    .dark-theme {
        --solid-bg: #0b0e14;
        --solid-card: #161b26;
        --solid-text: #ffffff;
        --solid-border: #2d3748;
        --solid-primary: #6366f1;
    }

    body {
        background-color: var(--solid-bg) !important;
        color: var(--solid-text);
        transition: background 0.3s ease;
    }

    /* 📦 Main Container Animation */
    .product-master-wrapper {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px;
        opacity: 0;
        transform: translateY(20px);
        animation: revealPage 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes revealPage {
        to { opacity: 1; transform: translateY(0); }
    }

    /* 🖼️ Product Section Card */
    .product-main-card {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 50px;
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 32px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        margin-bottom: 50px;
    }

    /* ============================================================
       🛠️ GALLERY SYSTEM STYLES (รูปเลื่อน)
       ============================================================ */
    .gallery-wrapper {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    /* รูปใหญ่ */
    .img-showcase {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        background: #000;
        border: 1px solid var(--solid-border);
        height: 450px; /* ลดความสูงลงนิดนึงเพื่อแบ่งที่ให้ Thumbnail */
        cursor: zoom-in;
    }

    .img-showcase img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1), opacity 0.3s ease;
    }

    .img-showcase:hover img { transform: scale(1.05); }
    
    .img-animating {
        opacity: 0.5;
        transform: scale(0.98);
    }

    /* รูปรองด้านล่าง (Thumbnails) */
    .thumbnail-track {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 5px; /* เผื่อที่ให้ Scrollbar ถ้าจอเล็ก */
    }

    .thumbnail-track::-webkit-scrollbar { height: 6px; }
    .thumbnail-track::-webkit-scrollbar-thumb { background: var(--solid-border); border-radius: 10px; }

    .thumb-btn {
        width: 80px;
        height: 80px;
        flex-shrink: 0;
        border-radius: 16px;
        border: 3px solid transparent;
        overflow: hidden;
        cursor: pointer;
        opacity: 0.6;
        transition: all 0.3s ease;
    }

    .thumb-btn img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .thumb-btn:hover { opacity: 0.9; transform: translateY(-3px); }
    .thumb-btn.active {
        border-color: var(--solid-primary);
        opacity: 1;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    /* Info Side */
    .product-title-h1 {
        font-size: 2.8rem;
        font-weight: 900;
        letter-spacing: -1.5px;
        line-height: 1.1;
        margin-bottom: 15px;
        color: var(--solid-text);
    }

    .price-large {
        font-size: 2.5rem;
        font-weight: 900;
        color: var(--solid-primary);
        margin: 25px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* 🏷️ Tags & Badges */
    .tag-pill {
        background: var(--solid-bg);
        border: 1px solid var(--solid-border);
        color: var(--solid-text);
        padding: 6px 16px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* 🔘 Action Buttons */
    .btn-buy-now {
        flex: 1;
        padding: 20px;
        border-radius: 18px;
        background: var(--solid-primary);
        color: #fff !important;
        font-weight: 800;
        font-size: 1.2rem;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn-buy-now:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(79, 70, 229, 0.5);
    }

    .btn-chat-seller {
        padding: 20px 30px;
        border-radius: 18px;
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        color: var(--solid-text);
        font-weight: 800;
        font-size: 1.2rem;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-chat-seller:hover { background: var(--solid-bg); border-color: var(--solid-primary); color: var(--solid-primary); }

    /* 🏪 Shop Card Area */
    .shop-portal {
        background: var(--solid-bg);
        border: 2px solid var(--solid-border);
        border-radius: 24px;
        padding: 25px;
        margin-top: 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .shop-avatar {
        width: 60px; height: 60px;
        background: var(--solid-primary);
        color: #fff;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
    }

    /* ⭐ Review System */
    .star-rating-solid {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 10px;
    }

    .star-rating-solid input { display: none; }
    .star-rating-solid label {
        font-size: 2.2rem;
        color: #cbd5e1;
        cursor: pointer;
        transition: 0.2s;
    }

    .star-rating-solid input:checked ~ label,
    .star-rating-solid label:hover,
    .star-rating-solid label:hover ~ label { color: var(--solid-warning); }

    .review-card-solid {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 24px;
        padding: 30px;
        margin-bottom: 20px;
        transition: 0.3s;
        opacity: 0;
        transform: translateX(-20px);
    }

    .review-card-solid.show { opacity: 1; transform: translateX(0); }

    /* 📱 Responsive Adjustments */
    @media (max-width: 992px) {
        .product-main-card { grid-template-columns: 1fr; padding: 25px; }
        .img-showcase { height: 400px; }
    }

    /* 🚨 Admin & Modal Styles */
    .modal-overlay {
        position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.9); display: none; align-items: center; justify-content: center;
    }
    .modal-solid {
        background: var(--solid-card); padding: 40px; border-radius: 32px; width: 90%; max-width: 480px;
        border: 2px solid var(--solid-border);
    }
</style>

<div class="product-master-wrapper">
    
    <?php echo displayFlashMessage(); ?>

    <div class="product-main-card">
        
        <div class="gallery-wrapper">
            <div class="img-showcase">
                <img id="mainDisplayImage" src="../assets/images/products/<?= e($main_image) ?>" alt="<?= e($product['title']) ?>">
                
                <?php if ($total_p_reviews > 0): ?>
                    <div style="position: absolute; top: 20px; left: 20px; background: rgba(0,0,0,0.8); color: #fff; padding: 8px 15px; border-radius: 12px; font-weight: 800; font-size: 0.9rem;">
                        <i class="fas fa-star" style="color: var(--solid-warning);"></i> <?= $avg_p_rating ?>
                    </div>
                <?php endif; ?>

                <div style="position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.9); color: #000; padding: 8px 15px; border-radius: 12px; font-weight: 800; font-size: 0.8rem;">
                    <i class="fas fa-eye"></i> <?= number_format($product['views']) ?> Views
                </div>
            </div>

            <?php if (count($product_images) > 1): ?>
            <div class="thumbnail-track">
                <?php foreach ($product_images as $idx => $img): ?>
                    <div class="thumb-btn <?= $idx === 0 ? 'active' : '' ?>" 
                         onclick="changeMainImage('../assets/images/products/<?= e($img['image_path']) ?>', this)">
                        <img src="../assets/images/products/<?= e($img['image_path']) ?>" alt="Thumbnail">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="product-details-content">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <div>
                    <h1 class="product-title-h1"><?= e($product['title']) ?></h1>
                    <p style="color: var(--text-muted); font-weight: 600;">BNCC Student Collection</p>
                </div>
                <button id="main-wish-btn" data-id="<?= $product['id'] ?>" 
                        style="background: var(--solid-bg); border: 2px solid var(--solid-border); width: 60px; height: 60px; border-radius: 50%; cursor: pointer; transition: 0.3s; font-size: 1.5rem; color: <?= $is_wishlisted ? 'var(--solid-danger)' : 'var(--solid-border)' ?>;">
                    <i class="<?= $is_wishlisted ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
            </div>

            <div style="display: flex; gap: 8px; margin-bottom: 30px; flex-wrap: wrap;">
                <?php foreach ($product_tags as $tag): ?>
                    <span class="tag-pill">#<?= e($tag['tag_name']) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="price-large">
                <span>฿<?= number_format($product['price'], 2) ?></span>
            </div>

            <div style="margin-bottom: 40px;">
                <label style="display: block; font-weight: 900; text-transform: uppercase; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 10px; letter-spacing: 1px;">Description</label>
                <p style="font-size: 1.1rem; line-height: 1.8; color: var(--solid-text);"><?= nl2br(e($product['description'])) ?></p>
            </div>

            <form method="POST" style="display: flex; gap: 15px; margin-bottom: 40px;">
                <?php if ($user_id && $user_id != $product['owner_id']): ?>
                    <button type="submit" name="place_order" class="btn-buy-now" onclick="return confirm('ยืนยันคำสั่งซื้อ?')">
                        <i class="fas fa-shopping-bag"></i> ORDER NOW
                    </button>
                    <a href="chat.php?user=<?= $product['owner_id'] ?>" class="btn-chat-seller">
                        <i class="fas fa-comment-alt"></i>
                    </a>
                <?php elseif (!$user_id): ?>
                    <a href="../auth/login.php" class="btn-buy-now" style="text-decoration: none;">
                        <i class="fas fa-user-lock"></i> LOGIN TO PURCHASE
                    </a>
                <?php endif; ?>
            </form>

            <div class="shop-portal">
    <div style="display:flex; align-items:center; gap:20px;">
        
        <div class="shop-avatar">
            <i class="fas fa-store"></i>
        </div>

        <div style="display:flex; flex-direction:column; gap:4px;">
            
            <div style="font-size:0.7rem; font-weight:800; color:var(--solid-primary); text-transform:uppercase;">
                VERIFIED SELLER <?= getUserBadge($product['owner_role']) ?>
            </div>

            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <a href="shop_profile.php?id=<?= $product['shop_id'] ?>" 
                   style="text-decoration:none; color:var(--solid-text); font-weight:900; font-size:1.3rem;">
                    <?= e($product['shop_name']) ?>
                </a>

                <span style="display:inline-flex;">
                    <?= getShopBadge($product['shop_id']) ?>
                </span>
            </div>

        </div>
    </div>
    
    <div style="display:flex; gap:10px; align-items:center;">
        <?php if(!empty($product['contact_line'])): ?>
            <a href="https://line.me/ti/p/~<?= e($product['contact_line']) ?>" target="_blank" style="color:#06c755; font-size:1.6rem;">
                <i class="fab fa-line"></i>
            </a>
        <?php endif; ?>

        <?php if(!empty($product['contact_ig'])): ?>
            <a href="https://www.instagram.com/<?= e($product['contact_ig']) ?>/" target="_blank" style="color:#e1306c; font-size:1.6rem;">
                <i class="fab fa-instagram"></i>
            </a>
        <?php endif; ?>
    </div>
</div>

            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher')): ?>
                <div style="margin-top: 25px; padding: 20px; border-radius: 20px; border: 2px dashed var(--solid-danger); background: rgba(239, 68, 68, 0.05);">
                    <h4 style="color: var(--solid-danger); font-weight: 800; margin-bottom: 15px; font-size: 0.85rem;">ADMINISTRATOR PANEL</h4>
                    <button onclick="openDeleteProductModal()" class="btn-buy-now" style="background: var(--solid-danger); padding: 12px; font-size: 0.95rem;">
                        <i class="fas fa-ban"></i> SUSPEND PRODUCT
                    </button>
                </div>
            <?php endif; ?>
            
            <button onclick="openReportModal(<?= $product['shop_id'] ?>, 'shop')" style="background: none; border: none; color: var(--solid-danger); font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 6px; font-weight: 700; margin-top: 20px; opacity: 0.6;">
                <i class="fas fa-flag"></i> REPORT THIS SHOP
            </button>
        </div>
    </div>

    <div class="reviews-master-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <h2 style="font-size: 2rem; font-weight: 900; letter-spacing: -1px;">Customer Reviews (<?= count($all_reviews) ?>)</h2>
            <div style="background: var(--solid-warning); color: #000; padding: 8px 20px; border-radius: 50px; font-weight: 900; font-size: 1.1rem;">
                ★ <?= $avg_p_rating ?>
            </div>
        </div>

        <?php if (isLoggedIn()): ?>
            <?php 
            $spam_check_ui = canUserReview($user_id, $product_id); 
            if ($spam_check_ui['status']): 
            ?>
            <div class="product-main-card" style="grid-template-columns: 1fr; margin-bottom: 40px;">
                <h3 style="font-weight: 800; margin-bottom: 25px;">Share your experience</h3>
                <form method="POST">
                    <div style="margin-bottom: 25px;">
                        <label class="field-label" style="margin-left: 0;">Rating Score</label>
                        <div class="star-rating-solid">
                            <?php for($i=5; $i>=1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea name="comment" class="form-control" required placeholder="Tell us what you think about this product..." style="min-height: 150px; border-radius: 20px; padding: 20px; border-width: 2px; background: var(--solid-bg); font-weight: 600;"></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn-buy-now" style="width: auto; padding: 15px 50px; margin-top: 20px;">
                        POST REVIEW
                    </button>
                </form>
            </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: rgba(99, 102, 241, 0.05); border-radius: 24px; border: 2px solid var(--solid-border); margin-bottom: 40px;">
                    <p style="font-weight: 800; color: var(--solid-primary); margin: 0;">
                        <i class="fas fa-info-circle"></i> <?= $spam_check_ui['message'] ?>
                    </p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="review-stream">
            <?php if (count($all_reviews) > 0): ?>
                <?php foreach ($all_reviews as $idx => $rev): 
                    $avatar = !empty($rev['profile_img']) ? "../assets/images/profiles/" . $rev['profile_img'] : "../assets/images/profiles/default_profile.png";
                ?>
                    <div class="review-card-solid stagger-reveal" style="transition-delay: <?= $idx * 0.1 ?>s;">
                        <div style="display: flex; gap: 20px;">
                            <a href="view_profile.php?id=<?= $rev['author_id'] ?>">
                                <img src="<?= $avatar ?>" style="width: 65px; height: 65px; border-radius: 20px; object-fit: cover; border: 2px solid var(--solid-border);">
                            </a>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <?php 
                                    // ดึงบทบาทคนรีวิวมาโชว์ Badge (ถ้ามี)
                                    $author_role_stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
                                    $author_role_stmt->execute([$rev['author_id']]);
                                    $author_role = $author_role_stmt->fetchColumn();
                                    ?>
                                    <div>
                                        <a href="view_profile.php?id=<?= $rev['author_id'] ?>" style="text-decoration: none; color: var(--solid-text); font-weight: 800; font-size: 1.1rem;">
                                            <?= e($rev['fullname']) ?> <?= getUserBadge($author_role) ?>
                                        </a>
                                        <div style="color: var(--solid-warning); font-size: 0.85rem; margin-top: 4px;">
                                            <?php for($j=0; $j<$rev['rating']; $j++) echo '<i class="fas fa-star"></i>'; ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button onclick="openReportModal(<?= $rev['id'] ?>, 'comment')" class="toolbar-icon" style="color: var(--solid-border);"><i class="fas fa-flag"></i></button>
                                        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher')): ?>
                                            <button onclick="openDeleteCommentModal(<?= $rev['id'] ?>, '<?= e($rev['fullname']) ?>')" style="color: var(--solid-danger); background: none; border: none; cursor: pointer; font-size: 1.2rem;"><i class="fas fa-trash-alt"></i></button>
                                        <?php endif; ?>
                            <?php if ($user_id == $rev['author_id']): ?>
            <div style="margin-top: 10px; display: flex; gap: 15px; border-top: 1px solid var(--solid-border); padding-top: 10px;">
                <button type="button" onclick="openEditMyReview(<?= $rev['id'] ?>, <?= $rev['rating'] ?>, '<?= e(str_replace(["\r", "\n"], ' ', $rev['comment'])) ?>')" 
                        style="background:none; border:none; color:var(--solid-primary); font-size:0.8rem; cursor:pointer; font-weight:800; padding:0;">
                    <i class="fas fa-edit"></i> แก้ไข
                </button>
                <a href="product_detail.php?id=<?= $product_id ?>&action=delete_my_review&rev_id=<?= $rev['id'] ?>" 
                   onclick="return confirm('ลบรีวิวนี้?')" style="text-decoration:none; color:var(--solid-danger); font-size:0.8rem; font-weight:800;">
                    <i class="fas fa-trash"></i> ลบ
                </a>
            </div>
        <?php endif; ?>
                                    </div>
                                </div>
                                <p style="margin-top: 15px; font-size: 1.05rem; line-height: 1.7; color: var(--solid-text);"><?= nl2br(e($rev['comment'])) ?></p>
                                <div style="margin-top: 20px; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); opacity: 0.6;">
                                    PUBLISHED AT: <?= date('d M Y - H:i', strtotime($rev['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 100px 20px; border: 3px dashed var(--solid-border); border-radius: 32px;">
                    <i class="fas fa-comment-slash" style="font-size: 4rem; opacity: 0.1;"></i>
                    <h3 style="margin-top: 20px; font-weight: 800; opacity: 0.4;">No reviews yet.</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="reportModal" class="modal-overlay">
    <div class="modal-solid">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background: rgba(239,68,68,0.1); color: var(--solid-danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 2.2rem;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3 style="font-size: 1.6rem; font-weight: 900;">Report Content</h3>
            <p style="color: var(--text-muted); font-weight: 600;">ช่วยให้ BNCC Market ปลอดภัยขึ้น</p>
        </div>
        <form action="../auth/submit_report.php" method="POST">
            <input type="hidden" name="target_id" id="report_target_id">
            <input type="hidden" name="target_type" id="report_target_type">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <div class="form-group">
                <textarea name="reason" class="form-control" required style="width:100%; min-height:130px; border-radius: 18px; padding: 20px; border: 2px solid var(--solid-border); background: var(--solid-bg); font-weight: 600;" placeholder="เหตุผลที่ต้องการรายงาน..."></textarea>
            </div>
            <div style="display:flex; gap:12px; margin-top: 30px;">
                <button type="button" onclick="closeReportModal()" class="btn-chat-seller" style="flex:1; font-size: 1rem; padding: 15px;">CANCEL</button>
                <button type="submit" class="btn-buy-now" style="flex:1; font-size: 1rem; padding: 15px; background: var(--solid-danger);">SUBMIT REPORT</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteCommentModal" class="modal-overlay">
    <div class="modal-solid" style="border-color: var(--solid-danger);">
        <h3 style="color: var(--solid-danger); font-weight: 900; margin-bottom: 10px;"><i class="fas fa-trash-alt"></i> DELETE COMMENT</h3>
        <p style="font-weight: 600; margin-bottom: 25px;">User: <span id="target_user_name" style="color: var(--solid-primary);"></span></p>
        <form action="../admin/admin_delete_comment.php" method="POST">
            <input type="hidden" name="comment_id" id="delete_comment_id">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <div class="form-group">
                <label style="display:block; margin-bottom:10px; font-weight:800; font-size: 0.8rem;">REASON (LOGGING REQUIRED):</label>
                <textarea name="reason" class="form-control" required style="width:100%; min-height:120px; border-radius:18px; padding:15px; border: 2px solid var(--solid-border); background: var(--solid-bg);" placeholder="Describe why this comment is being removed..."></textarea>
            </div>
            <div style="display:flex; gap:12px; margin-top: 30px;">
                <button type="button" onclick="closeDeleteCommentModal()" class="btn-chat-seller" style="flex:1;">CANCEL</button>
                <button type="submit" class="btn-buy-now" style="flex:1; background: var(--solid-danger);">CONFIRM DELETE</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteProductModal" class="modal-overlay">
    <div class="modal-solid" style="border-color: var(--solid-danger);">
        <h3 style="color: var(--solid-danger); font-weight: 900; margin-bottom: 10px;"><i class="fas fa-box-open"></i> SUSPEND PRODUCT</h3>
        <p style="font-weight: 600; margin-bottom: 25px;">สินค้านี้จะถูกซ่อนจากสาธารณะทันที</p>
        <form action="../admin/admin_delete_product.php" method="POST">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <div class="form-group">
                <label style="display:block; margin-bottom:10px; font-weight:800; font-size: 0.8rem;">SUSPENSION REASON:</label>
                <textarea name="reason" class="form-control" required style="width:100%; min-height:120px; border-radius:18px; padding:15px; border: 2px solid var(--solid-border); background: var(--solid-bg);" placeholder="เช่น ขัดต่อกฎระเบียบ, ขายของต้องห้าม..."></textarea>
            </div>
            <div style="display:flex; gap:12px; margin-top: 30px;">
                <button type="button" onclick="closeDeleteProductModal()" class="btn-chat-seller" style="flex:1;">CANCEL</button>
                <button type="submit" class="btn-buy-now" style="flex:1; background: var(--solid-danger);">REMOVE NOW</button>
            </div>
        </form>
    </div>
</div>
<div id="editMyReviewModal" class="modal-overlay">
    <div class="modal-solid">
        <h3 style="font-weight: 900; margin-bottom: 20px;"><i class="fas fa-pen"></i> แก้ไขรีวิวของคุณ</h3>
        <form method="POST">
            <input type="hidden" name="review_id" id="edit_rev_id">
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:800;">คะแนน</label>
                <select name="rating" id="edit_rev_rating" class="form-control" style="border-radius:12px;">
                    <option value="5">5 ดาว</option><option value="4">4 ดาว</option><option value="3">3 ดาว</option><option value="2">2 ดาว</option><option value="1">1 ดาว</option>
                </select>
            </div>
            <textarea name="comment" id="edit_rev_comment" class="form-control" required style="min-height: 120px; border-radius: 18px; padding: 15px; border: 2px solid var(--solid-border);"></textarea>
            <div style="display:flex; gap:12px; margin-top: 25px;">
                <button type="button" onclick="document.getElementById('editMyReviewModal').style.display='none'" class="btn-chat-seller" style="flex:1;">ยกเลิก</button>
                <button type="submit" name="edit_review_submit" class="btn-buy-now" style="flex:1;">บันทึก</button>
            </div>
        </form>
    </div>
</div>
<script>
    /**
     * 🎯 🛠️ ฟังก์ชันสำหรับเปลี่ยนรูปภาพ (Gallery Slider Animation)
     */
    function changeMainImage(imgUrl, clickedThumb) {
        const mainImage = document.getElementById('mainDisplayImage');
        
        // 1. ลบ Active ออกจากทุกปุ่ม แล้วใส่ให้อันที่โดนคลิก
        document.querySelectorAll('.thumb-btn').forEach(btn => btn.classList.remove('active'));
        clickedThumb.classList.add('active');

        // 2. ใส่คลาสให้รูปเฟดจางลง + ย่อลงนิดนึง
        mainImage.classList.add('img-animating');
        
        // 3. รอ 150ms ให้แอนิเมชันวิ่งไปครึ่งนึง ค่อยสลับ Source รูป
        setTimeout(() => {
            mainImage.src = imgUrl;
            // ถอดคลาสออกเพื่อให้รูปเด้งกลับมาสว่าง
            mainImage.classList.remove('img-animating');
        }, 150);
    }

    /**
     * 🚀 REVEAL ANIMATIONS (Intersection Observer)
     */
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('show');
                }, index * 100);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.stagger-reveal').forEach(el => observer.observe(el));

    /**
     * 👁️ MODAL CONTROLS
     */
    function openReportModal(id, type) {
        document.getElementById('report_target_id').value = id;
        document.getElementById('report_target_type').value = type;
        document.getElementById('reportModal').style.display = 'flex';
    }
    function closeReportModal() { document.getElementById('reportModal').style.display = 'none'; }

    function openDeleteCommentModal(id, name) {
        document.getElementById('delete_comment_id').value = id;
        document.getElementById('target_user_name').innerText = name;
        document.getElementById('deleteCommentModal').style.display = 'flex';
    }
    function closeDeleteCommentModal() { document.getElementById('deleteCommentModal').style.display = 'none'; }

    function openDeleteProductModal() { document.getElementById('deleteProductModal').style.display = 'flex'; }
    function closeDeleteProductModal() { document.getElementById('deleteProductModal').style.display = 'none'; }

    /**
     * ❤️ WISHLIST DYNAMIC ACTION
     */
    document.getElementById('main-wish-btn').addEventListener('click', function() {
        const btn = this;
        const icon = btn.querySelector('i');
        const productId = btn.dataset.id;
        
        btn.style.transform = "scale(0.8)";
        setTimeout(() => btn.style.transform = "scale(1)", 150);

        fetch('../auth/toggle_wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'product_id=' + productId
        }).then(res => res.json()).then(data => {
            if (data.status === 'added') { 
                icon.classList.replace('far', 'fas'); 
                btn.style.color = 'var(--solid-danger)'; 
            }
            else if (data.status === 'removed') { 
                icon.classList.replace('fas', 'far'); 
                btn.style.color = 'var(--solid-border)'; 
            }
        }).catch(err => console.error("Wishlist Error:", err));
    });

    window.onclick = function(event) {
        const reportM = document.getElementById('reportModal');
        const commentM = document.getElementById('deleteCommentModal');
        const productM = document.getElementById('deleteProductModal');
        if (event.target == reportM) closeReportModal();
        if (event.target == commentM) closeDeleteCommentModal();
        if (event.target == productM) closeDeleteProductModal();
    }
    // วางก่อนบรรทัด 755
function openEditMyReview(id, rating, comment) {
    document.getElementById('edit_rev_id').value = id;
    document.getElementById('edit_rev_rating').value = rating;
    document.getElementById('edit_rev_comment').value = comment;
    document.getElementById('editMyReviewModal').style.display = 'flex';
}
</script>

<?php require_once '../includes/footer.php'; ?>