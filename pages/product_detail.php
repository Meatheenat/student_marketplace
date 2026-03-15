<?php
require_once '../includes/functions.php';

$product_id = $_GET['id'] ?? null;
if (!$product_id) redirect('index.php');

$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;

$stmt = $db->prepare("SELECT p.*, s.shop_name, s.contact_line, s.contact_ig, s.line_user_id, s.user_id as owner_id, u.role as owner_role 
                      FROM products p 
                      JOIN shops s ON p.shop_id = s.id 
                      JOIN users u ON s.user_id = u.id
                      WHERE p.id = ? AND p.is_deleted = 0");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    require_once '../includes/header.php';
    ?>
    <style>
        .not-found-wrapper {
            min-height: 70vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 60px 20px;
        }
        .not-found-icon {
            font-size: 6rem;
            opacity: 0.12;
            margin-bottom: 30px;
            animation: floatIcon 3s ease-in-out infinite;
        }
        @keyframes floatIcon {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        .not-found-title {
            font-size: 2.5rem;
            font-weight: 900;
            letter-spacing: -1.5px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .not-found-sub {
            font-size: 1.1rem;
            opacity: 0.4;
            font-weight: 600;
            margin-bottom: 40px;
        }
        .btn-go-home {
            background: #4f46e5;
            color: #fff;
            padding: 18px 50px;
            border-radius: 18px;
            font-weight: 800;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
        }
        .btn-go-home:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 40px rgba(79, 70, 229, 0.5);
            color: #fff;
        }
    </style>
    <div class="not-found-wrapper">
        <div class="not-found-icon"><i class="fas fa-box-open"></i></div>
        <h1 class="not-found-title">ไม่พบสินค้านี้</h1>
        <p class="not-found-sub">สินค้าอาจถูกลบออกหรือยังไม่เผยแพร่</p>
        <a href="index.php" class="btn-go-home">
            <i class="fas fa-home"></i> กลับหน้าหลัก
        </a>
    </div>
    <?php
    require_once '../includes/footer.php';
    exit;
}

$img_stmt = $db->prepare("SELECT image_path, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
$img_stmt->execute([$product_id]);
$product_images = $img_stmt->fetchAll();

if (count($product_images) === 0) {
    $product_images[] = ['image_path' => $product['image_url'], 'is_main' => 1];
}

$main_image = $product_images[0]['image_path'];

if (!isset($_SESSION['viewed_products'])) {
    $_SESSION['viewed_products'] = [];
}

if (!in_array($product_id, $_SESSION['viewed_products'])) {
    $update_views = $db->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
    $update_views->execute([$product_id]);
    $_SESSION['viewed_products'][] = $product_id;
}

$rating_summary_stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ? AND is_deleted = 0");
$rating_summary_stmt->execute([$product_id]);
$rating_info = $rating_summary_stmt->fetch();
$avg_p_rating = round($rating_info['avg_rating'] ?? 0, 1);
$total_p_reviews = $rating_info['total_reviews'];

$rating_dist_stmt = $db->prepare("SELECT rating, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND is_deleted = 0 GROUP BY rating ORDER BY rating DESC");
$rating_dist_stmt->execute([$product_id]);
$rating_dist_raw = $rating_dist_stmt->fetchAll();
$rating_dist = [];
foreach ($rating_dist_raw as $rd) {
    $rating_dist[$rd['rating']] = $rd['cnt'];
}

$tag_stmt = $db->prepare("SELECT t.tag_name FROM tags t JOIN product_tag_map ptm ON t.id = ptm.tag_id WHERE ptm.product_id = ?");
$tag_stmt->execute([$product_id]);
$product_tags = $tag_stmt->fetchAll();

$is_wishlisted = false;
if (isLoggedIn()) {
    $check_wish = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check_wish->execute([$user_id, $product_id]);
    $is_wishlisted = $check_wish->fetch() ? true : false;
}

$related_stmt = $db->prepare("SELECT p.id, p.title, p.price, p.image_url, p.views FROM products p WHERE p.shop_id = ? AND p.id != ? AND p.is_deleted = 0 ORDER BY p.views DESC LIMIT 4");
$related_stmt->execute([$product['shop_id'], $product_id]);
$related_products = $related_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isLoggedIn()) redirect('../auth/login.php');
    
    if ($user_id == $product['owner_id']) {
        $_SESSION['flash_message'] = "คุณไม่สามารถสั่งซื้อสินค้าของร้านตัวเองได้";
        $_SESSION['flash_type'] = "error";
    } else {
        $ins_order = $db->prepare("INSERT INTO orders (buyer_id, shop_id, product_id) VALUES (?, ?, ?)");
        if ($ins_order->execute([$user_id, $product['shop_id'], $product_id])) {
            $notif_msg = "🛒 มีคำสั่งซื้อใหม่สำหรับสินค้า {$product['title']} จากคุณ {$_SESSION['fullname']}";
            sendNotification($product['owner_id'], 'order', $notif_msg, "../seller/dashboard.php");

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
            $notif_msg = "⭐ มีรีวิวใหม่ ({$rating} ดาว) ในสินค้า {$product['title']}";
            sendNotification($product['owner_id'], 'review', $notif_msg, "product_detail.php?id=$product_id");

            if (!empty($product['line_user_id'])) {
                $message = "📢 มีรีวิวใหม่ถึงสินค้าของคุณ!\n"
                         . "📦 สินค้า: " . $product['title'] . "\n"
                         . "⭐️ คะแนน: " . $rating . " ดาว\n"
                         . "💬 ความเห็น: " . $comment . "\n"
                         . "🔗 ดูรีวิว: " . BASE_URL . "/pages/product_detail.php?id=" . $product_id;
                sendLineMessagingAPI($product['line_user_id'], $message);
            }

            $_SESSION['flash_message'] = "บันทึกรีวิวสำเร็จ ระบบแจ้งเตือนผู้ขายเรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "success";
        }
    }
    redirect("product_detail.php?id=$product_id");
}

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

$rev_stmt = $db->prepare("
    SELECT r.*, u.fullname, u.profile_img, u.id as author_id, u.role as author_role
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? AND r.is_deleted = 0
    ORDER BY r.created_at DESC
");
$rev_stmt->execute([$product_id]);
$all_reviews = $rev_stmt->fetchAll();

// ── Open Graph / Social Share meta tags ──
// บังคับใช้ HTTPS absolute URL เสมอ (Facebook crawler ต้องการ)
$_og_base   = 'https://hosting.bncc.ac.th/s673190104/student_marketplace';
$og_url     = $_og_base . '/pages/product_detail.php?id=' . (int)$product_id;
$og_title   = htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') . ' - BNCC Market';
$og_desc    = htmlspecialchars(mb_strimwidth(strip_tags($product['description']), 0, 160, '...'), ENT_QUOTES, 'UTF-8');
$og_price   = number_format($product['price'], 2);

// รูปภาพ: ดึงจาก product_images ของ product_id นั้นโดยตรง
// $main_image = ชื่อไฟล์รูปหลักจาก product_images table (is_main=1)
$og_image   = $_og_base . '/assets/images/products/' . $main_image;

$pageTitle  = htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') . ' ฿' . $og_price . ' - BNCC Market';

$extra_head = '
    <meta property="og:type"         content="product">
    <meta property="og:url"          content="' . $og_url . '">
    <meta property="og:title"        content="' . $og_title . '">
    <meta property="og:description"  content="' . $og_desc . '">
    <meta property="og:image"        content="' . $og_image . '">
    <meta property="og:image:secure_url" content="' . $og_image . '">
    <meta property="og:image:type"   content="image/jpeg">
    <meta property="og:site_name"    content="BNCC Market">
    <meta property="og:locale"       content="th_TH">
    <meta property="product:price:amount"   content="' . $og_price . '">
    <meta property="product:price:currency" content="THB">
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="' . $og_title . '">
    <meta name="twitter:description" content="' . $og_desc . '">
    <meta name="twitter:image"       content="' . $og_image . '">
    <meta name="description"         content="' . $og_desc . '">
';

require_once '../includes/header.php';
?>

<style>
    :root {
        --pd-bg: #f0f2f8;
        --pd-card: #ffffff;
        --pd-text: #0f172a;
        --pd-muted: #64748b;
        --pd-border: #e2e8f0;
        --pd-primary: #4f46e5;
        --pd-primary-light: rgba(79,70,229,0.08);
        --pd-secondary: #10b981;
        --pd-danger: #ef4444;
        --pd-warning: #f59e0b;
        --pd-warning-light: rgba(245,158,11,0.1);
        --pd-radius-xl: 28px;
        --pd-radius-lg: 20px;
        --pd-radius-md: 14px;
        --pd-shadow-sm: 0 2px 8px rgba(15,23,42,0.06);
        --pd-shadow-md: 0 8px 24px rgba(15,23,42,0.08);
        --pd-shadow-lg: 0 20px 50px rgba(15,23,42,0.12);
        --pd-shadow-primary: 0 12px 30px rgba(79,70,229,0.25);
        --pd-transition: cubic-bezier(0.16, 1, 0.3, 1);
    }

    html[data-theme="dark"],
    html.dark-theme {
        --pd-bg: #080b12;
        --pd-card: #111827;
        --pd-text: #f1f5f9;
        --pd-muted: #94a3b8;
        --pd-border: #1e2d3d;
        --pd-primary: #6366f1;
        --pd-primary-light: rgba(99,102,241,0.12);
    }

    * { box-sizing: border-box; }

    body {
        background: var(--pd-bg) !important;
        color: var(--pd-text);
    }

    .pd-page-shell {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px 80px;
        opacity: 0;
        transform: translateY(30px);
        animation: pd-reveal 0.9s var(--pd-transition) forwards;
    }

    @keyframes pd-reveal {
        to { opacity: 1; transform: translateY(0); }
    }

    .pd-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 30px;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--pd-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .pd-breadcrumb a {
        color: var(--pd-muted);
        text-decoration: none;
        transition: color 0.2s;
    }

    .pd-breadcrumb a:hover { color: var(--pd-primary); }

    .pd-breadcrumb .sep { opacity: 0.4; }

    .pd-breadcrumb .current { color: var(--pd-text); opacity: 0.7; }

    .pd-hero-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 40px;
    }

    @media (max-width: 1024px) {
        .pd-hero-grid { grid-template-columns: 1fr; gap: 30px; }
    }

    .pd-gallery-col { display: flex; flex-direction: column; gap: 16px; }

    .pd-main-img-frame {
        position: relative;
        border-radius: var(--pd-radius-xl);
        overflow: hidden;
        background: linear-gradient(135deg, #1a1f35, #0f1420);
        border: 1px solid var(--pd-border);
        aspect-ratio: 1 / 1;
        cursor: zoom-in;
        box-shadow: var(--pd-shadow-md);
    }

    .pd-main-img-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.7s var(--pd-transition), opacity 0.25s ease;
        display: block;
    }

    .pd-main-img-frame:hover img { transform: scale(1.06); }

    .pd-main-img-frame.transitioning img {
        opacity: 0;
        transform: scale(0.96);
    }

    .pd-img-badge-tl {
        position: absolute;
        top: 16px;
        left: 16px;
        background: rgba(0,0,0,0.75);
        backdrop-filter: blur(10px);
        color: #fff;
        padding: 7px 14px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 0.82rem;
        display: flex;
        align-items: center;
        gap: 6px;
        z-index: 2;
    }

    .pd-img-badge-br {
        position: absolute;
        bottom: 16px;
        right: 16px;
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(10px);
        color: #0f172a;
        padding: 7px 14px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 0.82rem;
        display: flex;
        align-items: center;
        gap: 6px;
        z-index: 2;
    }

    .pd-img-counter {
        position: absolute;
        bottom: 16px;
        left: 16px;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(10px);
        color: rgba(255,255,255,0.8);
        padding: 5px 12px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.78rem;
        z-index: 2;
    }

    .pd-zoom-btn {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 40px;
        height: 40px;
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 12px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 2;
        transition: 0.2s;
        font-size: 0.9rem;
    }

    .pd-zoom-btn:hover { background: rgba(255,255,255,0.3); }

    .pd-thumbs-row {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: thin;
        scrollbar-color: var(--pd-border) transparent;
    }

    .pd-thumbs-row::-webkit-scrollbar { height: 4px; }
    .pd-thumbs-row::-webkit-scrollbar-thumb { background: var(--pd-border); border-radius: 10px; }

    .pd-thumb {
        flex-shrink: 0;
        width: 76px;
        height: 76px;
        border-radius: var(--pd-radius-md);
        overflow: hidden;
        border: 2.5px solid transparent;
        cursor: pointer;
        transition: all 0.3s var(--pd-transition);
        opacity: 0.55;
        background: var(--pd-border);
    }

    .pd-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.3s ease;
    }

    .pd-thumb:hover {
        opacity: 0.85;
        transform: translateY(-3px);
        box-shadow: var(--pd-shadow-md);
    }

    .pd-thumb:hover img { transform: scale(1.08); }

    .pd-thumb.active {
        border-color: var(--pd-primary);
        opacity: 1;
        box-shadow: 0 4px 16px rgba(79,70,229,0.35);
        transform: translateY(-3px);
    }

    .pd-info-col {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .pd-info-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 18px;
    }

    .pd-category-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--pd-primary-light);
        color: var(--pd-primary);
        padding: 5px 14px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        margin-bottom: 14px;
    }

    .pd-product-title {
        font-size: clamp(1.8rem, 3vw, 2.8rem);
        font-weight: 900;
        letter-spacing: -1.5px;
        line-height: 1.1;
        margin: 0 0 10px;
        color: var(--pd-text);
    }

    .pd-meta-row {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .pd-rating-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--pd-warning-light);
        border: 1px solid rgba(245,158,11,0.2);
        color: #92400e;
        padding: 5px 12px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 0.82rem;
    }

    html[data-theme="dark"] .pd-rating-chip,
    html.dark-theme .pd-rating-chip { color: #fbbf24; }

    .pd-views-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--pd-border);
        color: var(--pd-muted);
        padding: 5px 12px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.82rem;
    }

    .pd-price-block {
        display: flex;
        align-items: baseline;
        gap: 12px;
        margin: 22px 0;
        padding: 24px;
        background: var(--pd-primary-light);
        border: 1.5px solid rgba(79,70,229,0.15);
        border-radius: var(--pd-radius-lg);
    }

    .pd-price-main {
        font-size: 2.8rem;
        font-weight: 900;
        color: var(--pd-primary);
        letter-spacing: -2px;
        line-height: 1;
    }

    .pd-price-label {
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--pd-muted);
        text-transform: uppercase;
        letter-spacing: 0.7px;
    }

    .pd-tags-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }

    .pd-tag {
        background: var(--pd-card);
        border: 1.5px solid var(--pd-border);
        color: var(--pd-muted);
        padding: 5px 14px;
        border-radius: 50px;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: 0.2s;
        cursor: default;
    }

    .pd-tag:hover {
        border-color: var(--pd-primary);
        color: var(--pd-primary);
        background: var(--pd-primary-light);
    }

    .pd-desc-block {
        background: var(--pd-card);
        border: 1.5px solid var(--pd-border);
        border-radius: var(--pd-radius-lg);
        padding: 24px;
        margin-bottom: 28px;
    }

    .pd-desc-label {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--pd-muted);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pd-desc-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--pd-border);
    }

    .pd-desc-text {
        font-size: 1.02rem;
        line-height: 1.85;
        color: var(--pd-text);
        opacity: 0.85;
        font-weight: 500;
        max-height: 140px;
        overflow: hidden;
        transition: max-height 0.5s var(--pd-transition);
        position: relative;
    }

    .pd-desc-text.expanded { max-height: 1000px; }

    .pd-desc-fade {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 50px;
        background: linear-gradient(transparent, var(--pd-card));
        pointer-events: none;
        transition: opacity 0.3s;
    }

    .pd-desc-fade.hidden { opacity: 0; }

    .pd-desc-toggle {
        background: none;
        border: none;
        color: var(--pd-primary);
        font-weight: 800;
        font-size: 0.85rem;
        cursor: pointer;
        padding: 8px 0 0;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: 0.2s;
    }

    .pd-desc-toggle:hover { opacity: 0.75; }

    .pd-actions-row {
        display: flex;
        gap: 12px;
        margin-bottom: 28px;
    }

    .pd-btn-primary {
        flex: 1;
        padding: 18px 28px;
        border-radius: var(--pd-radius-lg);
        background: var(--pd-primary);
        color: #fff;
        font-weight: 800;
        font-size: 1.05rem;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-decoration: none;
        box-shadow: var(--pd-shadow-primary);
        transition: all 0.35s var(--pd-transition);
        position: relative;
        overflow: hidden;
    }

    .pd-btn-primary::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
        opacity: 0;
        transition: 0.3s;
    }

    .pd-btn-primary:hover::before { opacity: 1; }

    .pd-btn-primary:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(79,70,229,0.45);
        color: #fff;
    }

    .pd-btn-primary:active { transform: translateY(-1px); }

    .pd-btn-secondary {
        padding: 18px 20px;
        border-radius: var(--pd-radius-lg);
        background: var(--pd-card);
        border: 2px solid var(--pd-border);
        color: var(--pd-text);
        font-weight: 800;
        font-size: 1.05rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-decoration: none;
        transition: all 0.3s var(--pd-transition);
    }

    .pd-btn-secondary:hover {
        border-color: var(--pd-primary);
        color: var(--pd-primary);
        background: var(--pd-primary-light);
        transform: translateY(-3px);
    }

    .pd-btn-chat {
        position: relative;
        padding: 18px 22px;
        border-radius: var(--pd-radius-lg);
        background: linear-gradient(135deg, #059669, #10b981);
        border: 2px solid rgba(16,185,129,0.3);
        color: #fff;
        font-weight: 800;
        font-size: 1.05rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        text-decoration: none;
        transition: all 0.35s var(--pd-transition);
        box-shadow: 0 10px 25px rgba(16,185,129,0.28);
        overflow: hidden;
        white-space: nowrap;
    }

    .pd-btn-chat::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
        opacity: 0;
        transition: 0.3s;
    }

    .pd-btn-chat:hover::before { opacity: 1; }

    .pd-btn-chat:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(16,185,129,0.45);
        color: #fff;
        background: linear-gradient(135deg, #047857, #059669);
    }

    .pd-btn-chat:active { transform: translateY(-1px); }

    .pd-btn-chat-ping {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #fff;
        opacity: 0.9;
    }

    .pd-btn-chat-ping::before {
        content: '';
        position: absolute;
        inset: -3px;
        border-radius: 50%;
        background: rgba(255,255,255,0.4);
        animation: chatPing 1.8s cubic-bezier(0,0,0.2,1) infinite;
    }

    @keyframes chatPing {
        0% { transform: scale(1); opacity: 0.7; }
        75%, 100% { transform: scale(2.2); opacity: 0; }
    }

    .pd-btn-icon {
        width: 58px;
        height: 58px;
        border-radius: var(--pd-radius-md);
        border: 2px solid var(--pd-border);
        background: var(--pd-card);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.3rem;
        transition: all 0.3s var(--pd-transition);
        color: var(--pd-muted);
        position: relative;
        overflow: hidden;
        text-decoration: none;
    }

    .pd-btn-icon.wishlisted { color: #ef4444; border-color: rgba(239,68,68,0.3); background: rgba(239,68,68,0.05); }

    .pd-btn-icon:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: var(--pd-shadow-md);
    }

    .pd-btn-icon.wish-pop {
        animation: wishPop 0.45s var(--pd-transition);
    }

    @keyframes wishPop {
        0% { transform: scale(1); }
        40% { transform: scale(1.4); }
        70% { transform: scale(0.9); }
        100% { transform: scale(1); }
    }

    /* ===== SHARE DROPDOWN ===== */
    .pd-share-wrap {
        position: relative;
    }

    .pd-share-menu {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        background: var(--pd-card);
        border: 1.5px solid var(--pd-border);
        border-radius: var(--pd-radius-lg);
        padding: 6px;
        min-width: 215px;
        box-shadow: var(--pd-shadow-lg);
        z-index: 600;
        opacity: 0;
        pointer-events: none;
        transform: translateY(-8px) scale(0.96);
        transition: all 0.22s var(--pd-transition);
        transform-origin: top right;
    }

    .pd-share-menu.open {
        opacity: 1;
        pointer-events: all;
        transform: translateY(0) scale(1);
    }

    .pd-share-section-label {
        font-size: 0.64rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.9px;
        color: var(--pd-muted);
        padding: 8px 12px 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pd-share-section-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--pd-border);
    }

    .pd-share-item {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 9px 12px;
        border-radius: 12px;
        font-size: 0.87rem;
        font-weight: 700;
        color: var(--pd-text);
        cursor: pointer;
        transition: background 0.18s;
        text-decoration: none;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }

    .pd-share-item:hover {
        background: var(--pd-primary-light);
        color: var(--pd-primary);
    }

    .pd-share-item-icon {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .pd-share-divider {
        height: 1px;
        background: var(--pd-border);
        margin: 5px 6px;
    }

    /* ===== PRODUCT CARD PREVIEW (modal) ===== */
    .pd-product-card-preview {
        border: 1.5px solid var(--pd-border);
        border-radius: var(--pd-radius-lg);
        overflow: hidden;
        background: var(--pd-bg);
        margin-bottom: 20px;
        transition: box-shadow 0.2s;
    }

    .pd-product-card-preview:hover {
        box-shadow: var(--pd-shadow-md);
    }

    .pd-product-card-img {
        width: 100%;
        aspect-ratio: 16/9;
        object-fit: cover;
        display: block;
    }

    .pd-product-card-body {
        padding: 14px 16px;
    }

    .pd-product-card-badge {
        font-size: 0.65rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--pd-primary);
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .pd-product-card-title {
        font-weight: 800;
        font-size: 0.98rem;
        color: var(--pd-text);
        margin-bottom: 5px;
        line-height: 1.35;
    }

    .pd-product-card-price {
        font-weight: 900;
        font-size: 1.15rem;
        color: var(--pd-primary);
        margin-bottom: 8px;
    }

    .pd-product-card-url {
        font-size: 0.7rem;
        color: var(--pd-muted);
        font-weight: 600;
        opacity: 0.55;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    /* ===== END SHARE ===== */

    .pd-shop-card {
        background: var(--pd-card);
        border: 1.5px solid var(--pd-border);
        border-radius: var(--pd-radius-lg);
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        transition: all 0.3s var(--pd-transition);
        margin-bottom: 18px;
    }

    .pd-shop-card:hover {
        border-color: var(--pd-primary);
        box-shadow: var(--pd-shadow-md);
        transform: translateY(-2px);
    }

    .pd-shop-avatar {
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, var(--pd-primary), #7c3aed);
        color: #fff;
        border-radius: var(--pd-radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    .pd-shop-info { flex: 1; min-width: 0; }

    .pd-shop-verified {
        font-size: 0.68rem;
        font-weight: 800;
        color: var(--pd-secondary);
        text-transform: uppercase;
        letter-spacing: 0.7px;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .pd-shop-name {
        font-size: 1.15rem;
        font-weight: 900;
        color: var(--pd-text);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    .pd-shop-name:hover { color: var(--pd-primary); }

    .pd-shop-socials {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-shrink: 0;
    }

    .pd-social-link {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        border: 1.5px solid var(--pd-border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        text-decoration: none;
        transition: all 0.25s;
    }

    .pd-social-link.line { color: #06c755; }
    .pd-social-link.line:hover { background: rgba(6,199,85,0.1); border-color: #06c755; transform: scale(1.1); }
    .pd-social-link.ig { color: #e1306c; }
    .pd-social-link.ig:hover { background: rgba(225,48,108,0.1); border-color: #e1306c; transform: scale(1.1); }

    .pd-admin-panel {
        margin-top: 20px;
        padding: 18px;
        border-radius: var(--pd-radius-lg);
        border: 2px dashed rgba(239,68,68,0.4);
        background: rgba(239,68,68,0.04);
    }

    .pd-admin-title {
        font-size: 0.72rem;
        font-weight: 900;
        color: var(--pd-danger);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pd-contact-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px;
        background: var(--pd-card);
        border: 1.5px solid var(--pd-border);
        border-radius: var(--pd-radius-lg);
        margin-bottom: 18px;
    }

    .pd-contact-label {
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--pd-muted);
        text-transform: uppercase;
        letter-spacing: 0.6px;
        display: flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .pd-contact-btns {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .pd-contact-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 0.82rem;
        font-weight: 800;
        text-decoration: none;
        transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 1.5px solid transparent;
    }

    .pd-contact-line {
        background: rgba(6,199,85,0.1);
        color: #06c755;
        border-color: rgba(6,199,85,0.25);
    }

    .pd-contact-line:hover {
        background: #06c755;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(6,199,85,0.35);
    }

    .pd-contact-ig {
        background: rgba(225,48,108,0.08);
        color: #e1306c;
        border-color: rgba(225,48,108,0.2);
    }

    .pd-contact-ig:hover {
        background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
        color: #fff;
        border-color: transparent;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(225,48,108,0.35);
    }

    .pd-report-btn {
        background: none;
        border: none;
        color: var(--pd-danger);
        font-size: 0.8rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 700;
        margin-top: 16px;
        opacity: 0.5;
        transition: opacity 0.2s;
        padding: 0;
    }

    .pd-report-btn:hover { opacity: 1; }

    .pd-section {
        margin-bottom: 40px;
    }

    .pd-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
        padding-bottom: 18px;
        border-bottom: 2px solid var(--pd-border);
    }

    .pd-section-title {
        font-size: 1.6rem;
        font-weight: 900;
        letter-spacing: -0.8px;
        color: var(--pd-text);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .pd-section-title .count-badge {
        background: var(--pd-border);
        color: var(--pd-muted);
        padding: 3px 12px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 800;
    }

    .pd-rating-overview {
        background: var(--pd-card);
        border: 1.5px solid var(--pd-border);
        border-radius: var(--pd-radius-xl);
        padding: 32px;
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 40px;
        margin-bottom: 32px;
        align-items: center;
    }

    @media (max-width: 640px) {
        .pd-rating-overview { grid-template-columns: 1fr; gap: 24px; }
    }

    .pd-rating-big {
        text-align: center;
        padding-right: 40px;
        border-right: 2px solid var(--pd-border);
    }

    @media (max-width: 640px) {
        .pd-rating-big { border-right: none; padding-right: 0; border-bottom: 2px solid var(--pd-border); padding-bottom: 24px; }
    }

    .pd-rating-number {
        font-size: 4.5rem;
        font-weight: 900;
        color: var(--pd-primary);
        line-height: 1;
        letter-spacing: -3px;
    }

    .pd-rating-stars-display {
        display: flex;
        gap: 4px;
        justify-content: center;
        margin: 10px 0 8px;
        font-size: 1.1rem;
        color: var(--pd-warning);
    }

    .pd-rating-total {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--pd-muted);
    }

    .pd-rating-bars { flex: 1; display: flex; flex-direction: column; gap: 10px; }

    .pd-rating-bar-row {
        display: grid;
        grid-template-columns: 18px 1fr 32px;
        gap: 12px;
        align-items: center;
    }

    .pd-rating-bar-label {
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--pd-muted);
        text-align: right;
    }

    .pd-bar-track {
        height: 8px;
        background: var(--pd-border);
        border-radius: 50px;
        overflow: hidden;
    }

    .pd-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--pd-warning), #f97316);
        border-radius: 50px;
        transform-origin: left;
        animation: barGrow 1.2s var(--pd-transition) both;
    }

    @keyframes barGrow {
        from { transform: scaleX(0); }
        to { transform: scaleX(1); }
    }

    .pd-bar-count {
        font-size: 0.72rem;
        font-weight: 800;
        color: var(--pd-muted);
        text-align: right;
    }

    .pd-review-form-card {
        background: var(--pd-card);
        border: 1.5px solid var(--pd-border);
        border-radius: var(--pd-radius-xl);
        padding: 32px;
        margin-bottom: 28px;
    }

    .pd-review-form-title {
        font-size: 1.15rem;
        font-weight: 800;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pd-star-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 8px;
        margin-bottom: 22px;
    }

    .pd-star-input input[type="radio"] { display: none; }

    .pd-star-input label {
        font-size: 2.4rem;
        color: var(--pd-border);
        cursor: pointer;
        transition: all 0.2s var(--pd-transition);
        display: flex;
        line-height: 1;
    }

    .pd-star-input input:checked ~ label,
    .pd-star-input label:hover,
    .pd-star-input label:hover ~ label {
        color: var(--pd-warning);
        transform: scale(1.2);
    }

    .pd-review-textarea {
        width: 100%;
        min-height: 130px;
        border-radius: var(--pd-radius-lg);
        padding: 18px 22px;
        border: 2px solid var(--pd-border);
        background: var(--pd-bg);
        color: var(--pd-text);
        font-size: 1rem;
        font-weight: 500;
        font-family: inherit;
        resize: vertical;
        transition: border-color 0.2s;
        outline: none;
    }

    .pd-review-textarea:focus { border-color: var(--pd-primary); }

    .pd-char-counter {
        text-align: right;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--pd-muted);
        margin-top: 6px;
    }

    .pd-review-card {
        background: var(--pd-card);
        border: 1.5px solid var(--pd-border);
        border-radius: var(--pd-radius-xl);
        padding: 28px;
        margin-bottom: 16px;
        transition: all 0.4s var(--pd-transition);
        opacity: 0;
        transform: translateX(-20px);
    }

    .pd-review-card.visible { opacity: 1; transform: translateX(0); }

    .pd-review-card:hover {
        border-color: rgba(79,70,229,0.2);
        box-shadow: var(--pd-shadow-sm);
    }

    .pd-review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 16px;
    }

    .pd-reviewer-left {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        flex: 1;
        min-width: 0;
    }

    .pd-reviewer-avatar {
        width: 52px;
        height: 52px;
        border-radius: var(--pd-radius-md);
        object-fit: cover;
        border: 2px solid var(--pd-border);
        flex-shrink: 0;
        transition: 0.3s;
    }

    .pd-reviewer-avatar:hover { border-color: var(--pd-primary); transform: scale(1.05); }

    .pd-reviewer-name-link {
        font-weight: 800;
        font-size: 1rem;
        color: var(--pd-text);
        text-decoration: none;
        transition: color 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pd-reviewer-name-link:hover { color: var(--pd-primary); }

    .pd-stars-small {
        display: flex;
        gap: 3px;
        color: var(--pd-warning);
        font-size: 0.78rem;
        margin-top: 5px;
    }

    .pd-review-date {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--pd-muted);
        opacity: 0.6;
        margin-top: 4px;
    }

    .pd-review-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-shrink: 0;
    }

    .pd-icon-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1.5px solid var(--pd-border);
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.82rem;
        color: var(--pd-muted);
        transition: 0.2s;
        text-decoration: none;
    }

    .pd-icon-btn:hover { border-color: var(--pd-primary); color: var(--pd-primary); background: var(--pd-primary-light); }
    .pd-icon-btn.danger:hover { border-color: var(--pd-danger); color: var(--pd-danger); background: rgba(239,68,68,0.08); }

    .pd-review-body {
        font-size: 1rem;
        line-height: 1.8;
        color: var(--pd-text);
        opacity: 0.85;
    }

    .pd-review-my-actions {
        display: flex;
        gap: 12px;
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid var(--pd-border);
    }

    .pd-my-review-btn {
        background: none;
        border: none;
        font-size: 0.82rem;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 8px;
        transition: 0.2s;
        text-decoration: none;
    }

    .pd-my-review-btn.edit { color: var(--pd-primary); }
    .pd-my-review-btn.edit:hover { background: var(--pd-primary-light); }
    .pd-my-review-btn.delete { color: var(--pd-danger); }
    .pd-my-review-btn.delete:hover { background: rgba(239,68,68,0.08); }

    .pd-empty-state {
        text-align: center;
        padding: 80px 20px;
        border: 2px dashed var(--pd-border);
        border-radius: var(--pd-radius-xl);
    }

    .pd-empty-state-icon { font-size: 4rem; opacity: 0.12; margin-bottom: 16px; }
    .pd-empty-state-title { font-size: 1.3rem; font-weight: 800; opacity: 0.35; margin-bottom: 8px; }
    .pd-empty-state-sub { font-size: 0.9rem; color: var(--pd-muted); opacity: 0.6; }

    .pd-related-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }

    .pd-related-card {
        background: var(--pd-card);
        border: 1.5px solid var(--pd-border);
        border-radius: var(--pd-radius-lg);
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: all 0.35s var(--pd-transition);
        display: flex;
        flex-direction: column;
        opacity: 0;
        transform: translateY(20px);
    }

    .pd-related-card.visible { opacity: 1; transform: translateY(0); }

    .pd-related-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--pd-shadow-lg);
        border-color: var(--pd-primary);
    }

    .pd-related-img {
        aspect-ratio: 1/1;
        overflow: hidden;
        background: var(--pd-border);
    }

    .pd-related-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s var(--pd-transition);
        display: block;
    }

    .pd-related-card:hover .pd-related-img img { transform: scale(1.08); }

    .pd-related-body { padding: 16px; }

    .pd-related-title {
        font-weight: 800;
        font-size: 0.92rem;
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .pd-related-price {
        font-weight: 900;
        font-size: 1.1rem;
        color: var(--pd-primary);
    }

    .pd-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 99999;
        background: rgba(8,11,18,0.85);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .pd-modal-overlay.open {
        display: flex;
        animation: modalFadeIn 0.3s forwards;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .pd-modal-box {
        background: var(--pd-card);
        border: 1.5px solid var(--pd-border);
        border-radius: var(--pd-radius-xl);
        padding: 40px;
        width: 100%;
        max-width: 480px;
        transform: translateY(30px) scale(0.97);
        animation: modalSlideUp 0.35s var(--pd-transition) forwards;
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
    }

    @keyframes modalSlideUp {
        from { transform: translateY(30px) scale(0.97); }
        to { transform: translateY(0) scale(1); }
    }

    .pd-modal-close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: 1.5px solid var(--pd-border);
        background: var(--pd-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.9rem;
        color: var(--pd-muted);
        transition: 0.2s;
    }

    .pd-modal-close:hover { color: var(--pd-danger); border-color: var(--pd-danger); }

    .pd-modal-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
    }

    .pd-modal-title {
        font-size: 1.5rem;
        font-weight: 900;
        text-align: center;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }

    .pd-modal-sub {
        text-align: center;
        color: var(--pd-muted);
        font-weight: 600;
        margin-bottom: 28px;
        font-size: 0.92rem;
    }

    .pd-form-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        color: var(--pd-muted);
        margin-bottom: 10px;
    }

    .pd-form-textarea {
        width: 100%;
        min-height: 120px;
        border-radius: var(--pd-radius-lg);
        padding: 16px 18px;
        border: 2px solid var(--pd-border);
        background: var(--pd-bg);
        color: var(--pd-text);
        font-size: 0.95rem;
        font-family: inherit;
        font-weight: 500;
        resize: vertical;
        transition: border-color 0.2s;
        outline: none;
    }

    .pd-form-textarea:focus { border-color: var(--pd-primary); }

    .pd-form-select {
        width: 100%;
        padding: 13px 18px;
        border-radius: var(--pd-radius-md);
        border: 2px solid var(--pd-border);
        background: var(--pd-bg);
        color: var(--pd-text);
        font-size: 0.95rem;
        font-weight: 700;
        outline: none;
        cursor: pointer;
        transition: border-color 0.2s;
    }

    .pd-form-select:focus { border-color: var(--pd-primary); }

    .pd-modal-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .pd-modal-btn-cancel {
        flex: 1;
        padding: 14px;
        border-radius: var(--pd-radius-md);
        border: 2px solid var(--pd-border);
        background: var(--pd-bg);
        color: var(--pd-text);
        font-weight: 800;
        font-size: 0.95rem;
        cursor: pointer;
        transition: 0.2s;
    }

    .pd-modal-btn-cancel:hover { border-color: var(--pd-primary); color: var(--pd-primary); }

    .pd-modal-btn-submit {
        flex: 1;
        padding: 14px;
        border-radius: var(--pd-radius-md);
        border: none;
        background: var(--pd-primary);
        color: #fff;
        font-weight: 800;
        font-size: 0.95rem;
        cursor: pointer;
        box-shadow: var(--pd-shadow-primary);
        transition: all 0.3s var(--pd-transition);
    }

    .pd-modal-btn-submit:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(79,70,229,0.4); }

    .pd-modal-btn-danger {
        flex: 1;
        padding: 14px;
        border-radius: var(--pd-radius-md);
        border: none;
        background: var(--pd-danger);
        color: #fff;
        font-weight: 800;
        font-size: 0.95rem;
        cursor: pointer;
        box-shadow: 0 8px 20px rgba(239,68,68,0.3);
        transition: all 0.3s var(--pd-transition);
    }

    .pd-modal-btn-danger:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(239,68,68,0.45); }

    .pd-zoom-overlay {
        position: fixed;
        inset: 0;
        z-index: 999999;
        background: rgba(0,0,0,0.95);
        display: none;
        align-items: center;
        justify-content: center;
        cursor: zoom-out;
    }

    .pd-zoom-overlay.open {
        display: flex;
        animation: zoomFade 0.3s forwards;
    }

    @keyframes zoomFade {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .pd-zoom-img {
        max-width: 92vw;
        max-height: 92vh;
        object-fit: contain;
        border-radius: 16px;
        animation: zoomImgIn 0.4s var(--pd-transition) forwards;
    }

    @keyframes zoomImgIn {
        from { transform: scale(0.85); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .pd-zoom-close-hint {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        color: rgba(255,255,255,0.7);
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .pd-sticky-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 9000;
        background: var(--pd-card);
        border-top: 1.5px solid var(--pd-border);
        padding: 14px 20px;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        box-shadow: 0 -10px 30px rgba(0,0,0,0.1);
        transform: translateY(100%);
        transition: transform 0.4s var(--pd-transition);
    }

    .pd-sticky-bar.visible {
        display: flex;
        transform: translateY(0);
    }

    .pd-sticky-info { flex: 1; min-width: 0; }

    .pd-sticky-title {
        font-weight: 800;
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pd-sticky-price {
        font-weight: 900;
        font-size: 1.1rem;
        color: var(--pd-primary);
    }

    .pd-sticky-action {
        flex-shrink: 0;
        padding: 13px 28px;
        border-radius: var(--pd-radius-md);
        background: var(--pd-primary);
        color: #fff;
        font-weight: 800;
        font-size: 0.95rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: var(--pd-shadow-primary);
        transition: all 0.3s var(--pd-transition);
    }

    .pd-sticky-action:hover { transform: scale(1.04); color: #fff; }

    .pd-flash-banner {
        padding: 16px 24px;
        border-radius: var(--pd-radius-lg);
        margin-bottom: 28px;
        display: flex;
        align-items: center;
        gap: 14px;
        font-weight: 700;
        font-size: 0.95rem;
        animation: flashSlide 0.5s var(--pd-transition) both;
    }

    @keyframes flashSlide {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .pd-flash-success {
        background: rgba(16,185,129,0.1);
        border: 1.5px solid rgba(16,185,129,0.3);
        color: #065f46;
    }

    .pd-flash-danger, .pd-flash-error {
        background: rgba(239,68,68,0.08);
        border: 1.5px solid rgba(239,68,68,0.25);
        color: #991b1b;
    }

    html[data-theme="dark"] .pd-flash-success,
    html.dark-theme .pd-flash-success { color: #6ee7b7; }
    html[data-theme="dark"] .pd-flash-danger,
    html[data-theme="dark"] .pd-flash-error,
    html.dark-theme .pd-flash-danger,
    html.dark-theme .pd-flash-error { color: #fca5a5; }

    .pd-scroll-progress {
        position: fixed;
        top: 0;
        left: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--pd-primary), #7c3aed);
        z-index: 99998;
        transition: width 0.1s linear;
        border-radius: 0 2px 2px 0;
    }

    .pd-info-blocked {
        background: var(--pd-primary-light);
        border: 1.5px solid rgba(79,70,229,0.2);
        border-radius: var(--pd-radius-lg);
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 28px;
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--pd-primary);
    }

    .pd-views-anim {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    @media (max-width: 768px) {
        .pd-rating-overview { padding: 24px; }
        .pd-review-form-card { padding: 24px; }
        .pd-modal-box { padding: 28px 24px; }
        .pd-product-title { font-size: 1.8rem; }
        .pd-price-main { font-size: 2.2rem; }
    }
</style>

<div class="pd-scroll-progress" id="scrollProgress"></div>

<div class="pd-page-shell">

    <?php
    $flash_msg = $_SESSION['flash_message'] ?? null;
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    if ($flash_msg): 
    ?>
    <div class="pd-flash-banner pd-flash-<?= $flash_type ?>">
        <i class="fas <?= $flash_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
        <?= e($flash_msg) ?>
    </div>
    <?php endif; ?>

    <nav class="pd-breadcrumb">
        <a href="index.php"><i class="fas fa-home"></i></a>
        <span class="sep">/</span>
        <a href="index.php">สินค้าทั้งหมด</a>
        <span class="sep">/</span>
        <a href="shop_profile.php?id=<?= $product['shop_id'] ?>"><?= e($product['shop_name']) ?></a>
        <span class="sep">/</span>
        <span class="current"><?= mb_strimwidth(e($product['title']), 0, 30, '...') ?></span>
    </nav>

    <div class="pd-hero-grid">

        <div class="pd-gallery-col">
            <div class="pd-main-img-frame" id="mainImgFrame" onclick="openZoom(this.querySelector('img').src)">

                <?php if ($total_p_reviews > 0): ?>
                <div class="pd-img-badge-tl">
                    <i class="fas fa-star" style="color: var(--pd-warning);"></i>
                    <?= $avg_p_rating ?> / 5
                </div>
                <?php endif; ?>

                <div class="pd-zoom-btn" onclick="event.stopPropagation(); openZoom(document.getElementById('mainImg').src)" title="ขยาย">
                    <i class="fas fa-expand-alt"></i>
                </div>

                <img id="mainImg" 
                     src="../assets/images/products/<?= e($main_image) ?>" 
                     alt="<?= e($product['title']) ?>">

                <div class="pd-img-badge-br">
                    <i class="fas fa-eye"></i>
                    <?= number_format($product['views']) ?> views
                </div>

                <?php if (count($product_images) > 1): ?>
                <div class="pd-img-counter" id="imgCounter">1 / <?= count($product_images) ?></div>
                <?php endif; ?>

            </div>

            <?php if (count($product_images) > 1): ?>
            <div class="pd-thumbs-row" id="thumbsRow">
                <?php foreach ($product_images as $idx => $img): ?>
                <div class="pd-thumb <?= $idx === 0 ? 'active' : '' ?>"
                     data-index="<?= $idx ?>"
                     onclick="switchImage('../assets/images/products/<?= e($img['image_path']) ?>', this, <?= $idx + 1 ?>, <?= count($product_images) ?>)">
                    <img src="../assets/images/products/<?= e($img['image_path']) ?>" 
                         alt="รูปที่ <?= $idx + 1 ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="pd-info-col">

            <div class="pd-category-pill">
                <i class="fas fa-tag"></i>
                BNCC Student Marketplace
            </div>

            <!-- pd-info-header: title + share + wishlist -->
            <div class="pd-info-header">
                <div style="flex:1; min-width:0;">
                    <h1 class="pd-product-title"><?= e($product['title']) ?></h1>
                </div>
                <div style="display:flex; gap:8px; align-items:center; flex-shrink:0;">

                    <!-- ปุ่ม Share -->
                    <div class="pd-share-wrap">
                        <button id="shareToggleBtn"
                                class="pd-btn-icon"
                                title="แชร์สินค้า"
                                onclick="toggleShareMenu(event)"
                                style="font-size:1.1rem;">
                            <i class="fas fa-share-nodes"></i>
                        </button>

                        <div class="pd-share-menu" id="pdShareMenu">

                            <div class="pd-share-section-label">แชร์ภายนอก</div>

                            <button class="pd-share-item" onclick="doShare('copy')">
                                <span class="pd-share-item-icon" style="background:rgba(99,102,241,0.1); color:var(--pd-primary);">
                                    <i class="fas fa-link"></i>
                                </span>
                                คัดลอกลิงก์
                            </button>

                            <a id="shareLineSocial" class="pd-share-item" href="#" target="_blank" rel="noopener">
                                <span class="pd-share-item-icon" style="background:rgba(6,199,85,0.1); color:#06c755;">
                                    <i class="fab fa-line"></i>
                                </span>
                                แชร์ LINE
                            </a>

                            <a id="shareFbSocial" class="pd-share-item" href="#" target="_blank" rel="noopener">
                                <span class="pd-share-item-icon" style="background:rgba(24,119,242,0.1); color:#1877f2;">
                                    <i class="fab fa-facebook"></i>
                                </span>
                                แชร์ Facebook
                            </a>

                            <a id="shareXSocial" class="pd-share-item" href="#" target="_blank" rel="noopener">
                                <span class="pd-share-item-icon" style="background:rgba(15,15,15,0.07); color:var(--pd-text);">
                                    <i class="fab fa-x-twitter"></i>
                                </span>
                                แชร์ Twitter / X
                            </a>

                            <?php if ($user_id && $user_id != $product['owner_id']): ?>
                            <div class="pd-share-divider"></div>
                            <div class="pd-share-section-label">ส่งในแชท</div>
                            <button class="pd-share-item" onclick="openShareChatModal()">
                                <span class="pd-share-item-icon" style="background:rgba(16,185,129,0.1); color:#10b981;">
                                    <i class="fas fa-comment-dots"></i>
                                </span>
                                ส่งให้ผู้ขาย
                            </button>
                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- ปุ่ม Wishlist -->
                    <button id="wishBtn"
                            data-id="<?= $product['id'] ?>"
                            class="pd-btn-icon <?= $is_wishlisted ? 'wishlisted' : '' ?>"
                            title="<?= $is_wishlisted ? 'ลบออกจาก Wishlist' : 'เพิ่มใน Wishlist' ?>">
                        <i class="<?= $is_wishlisted ? 'fas' : 'far' ?> fa-heart"></i>
                    </button>

                </div>
            </div>

            <div class="pd-meta-row">
                <?php if ($total_p_reviews > 0): ?>
                <span class="pd-rating-chip">
                    <i class="fas fa-star"></i>
                    <?= $avg_p_rating ?> / 5
                    <span style="opacity:0.6; font-weight:600;">(<?= $total_p_reviews ?>)</span>
                </span>
                <?php endif; ?>
                <span class="pd-views-chip pd-views-anim">
                    <i class="fas fa-eye"></i>
                    <?= number_format($product['views']) ?> ครั้ง
                </span>
            </div>

            <?php if (!empty($product_tags)): ?>
            <div class="pd-tags-row">
                <?php foreach ($product_tags as $tag): ?>
                <span class="pd-tag">#<?= e($tag['tag_name']) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="pd-price-block">
                <div>
                    <div class="pd-price-label">ราคา</div>
                    <div class="pd-price-main">฿<?= number_format($product['price'], 2) ?></div>
                </div>
            </div>

            <div class="pd-desc-block">
                <div class="pd-desc-label"><i class="fas fa-align-left"></i> รายละเอียดสินค้า</div>
                <div class="pd-desc-text" id="descText">
                    <?= nl2br(e($product['description'])) ?>
                    <div class="pd-desc-fade" id="descFade"></div>
                </div>
                <button class="pd-desc-toggle" id="descToggle" onclick="toggleDesc()">
                    <i class="fas fa-chevron-down" id="descChevron"></i> ดูเพิ่มเติม
                </button>
            </div>

            <div class="pd-actions-row">
                <?php if ($user_id && $user_id != $product['owner_id']): ?>
                    <a href="checkout.php?id=<?= $product_id ?>" class="pd-btn-primary" id="checkoutBtn">
                        <i class="fas fa-shopping-bag"></i>
                        นัดรับสินค้า
                    </a>
                    <a href="chat.php?user=<?= $product['owner_id'] ?>" class="pd-btn-chat" id="chatSellerBtn" title="แชทกับผู้ขาย">
                        <span class="pd-btn-chat-ping"></span>
                        <i class="fas fa-comment-dots"></i>
                        <span>แชทผู้ขาย</span>
                    </a>
                <?php elseif ($user_id == $product['owner_id']): ?>
                    <a href="../seller/edit_product.php?id=<?= $product_id ?>" class="pd-btn-primary">
                        <i class="fas fa-edit"></i> แก้ไขสินค้า
                    </a>
                <?php else: ?>
                    <a href="../auth/login.php" class="pd-btn-primary">
                        <i class="fas fa-user-lock"></i>
                        เข้าสู่ระบบเพื่อสั่งซื้อ
                    </a>
                <?php endif; ?>
            </div>

            <a href="shop_profile.php?id=<?= $product['shop_id'] ?>" style="text-decoration:none;" class="pd-shop-card">
                <div style="display:flex; align-items:center; gap:16px; flex:1; min-width:0;">
                    <div class="pd-shop-avatar">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="pd-shop-info">
                        <div class="pd-shop-verified">
                            <i class="fas fa-circle-check"></i>
                            VERIFIED SELLER
                            <?= getUserBadge($product['owner_role']) ?>
                        </div>
                        <div class="pd-shop-name">
                            <?= e($product['shop_name']) ?>
                            <span style="display:inline-flex;"><?= getShopBadge($product['shop_id']) ?></span>
                        </div>
                    </div>
                </div>
                <div style="flex-shrink:0; color:var(--pd-muted); font-size:0.8rem; font-weight:700; display:flex; align-items:center; gap:4px;">
                    ดูร้าน <i class="fas fa-chevron-right" style="font-size:0.7rem;"></i>
                </div>
            </a>

            <?php if (!empty($product['contact_line']) || !empty($product['contact_ig'])): ?>
            <div class="pd-contact-row">
                <span class="pd-contact-label"><i class="fas fa-address-card"></i> ติดต่อผู้ขาย</span>
                <div class="pd-contact-btns">
                    <?php if (!empty($product['contact_line'])): ?>
                    <a href="https://line.me/ti/p/~<?= e($product['contact_line']) ?>" target="_blank" class="pd-contact-btn pd-contact-line">
                        <i class="fab fa-line"></i>
                        <span>LINE</span>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($product['contact_ig'])): ?>
                    <a href="https://www.instagram.com/<?= e($product['contact_ig']) ?>/" target="_blank" class="pd-contact-btn pd-contact-ig">
                        <i class="fab fa-instagram"></i>
                        <span>Instagram</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher')): ?>
            <div class="pd-admin-panel">
                <div class="pd-admin-title">
                    <i class="fas fa-shield-alt"></i>
                    ADMINISTRATOR PANEL
                </div>
                <button onclick="openModal('deleteProductModal')" 
                        class="pd-btn-primary" 
                        style="background: var(--pd-danger); box-shadow: 0 8px 20px rgba(239,68,68,0.3); padding: 13px 24px; font-size: 0.92rem; width: auto;">
                    <i class="fas fa-ban"></i> SUSPEND PRODUCT
                </button>
            </div>
            <?php endif; ?>

            <button onclick="openModal('reportModal')" 
                    class="pd-report-btn"
                    data-target="<?= $product['shop_id'] ?>"
                    data-type="shop">
                <i class="fas fa-flag"></i>
                รายงานร้านนี้
            </button>

        </div>
    </div>

    <?php if (!empty($related_products)): ?>
    <div class="pd-section">
        <div class="pd-section-header">
            <h2 class="pd-section-title">
                <i class="fas fa-th-large" style="color: var(--pd-primary); font-size: 1.2rem;"></i>
                สินค้าอื่นจากร้านนี้
            </h2>
            <a href="shop_profile.php?id=<?= $product['shop_id'] ?>" 
               style="color: var(--pd-primary); font-weight: 800; font-size: 0.85rem; text-decoration: none;">
                ดูทั้งหมด <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="pd-related-grid">
            <?php foreach ($related_products as $idx => $rp): ?>
            <a href="product_detail.php?id=<?= $rp['id'] ?>" 
               class="pd-related-card stagger-item"
               style="animation-delay: <?= $idx * 0.1 ?>s;">
                <div class="pd-related-img">
                    <img src="../assets/images/products/<?= e($rp['image_url']) ?>" alt="<?= e($rp['title']) ?>">
                </div>
                <div class="pd-related-body">
                    <div class="pd-related-title"><?= e($rp['title']) ?></div>
                    <div class="pd-related-price">฿<?= number_format($rp['price'], 2) ?></div>
                    <div style="margin-top:6px; font-size:0.75rem; color:var(--pd-muted); font-weight:700;">
                        <i class="fas fa-eye"></i> <?= number_format($rp['views']) ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="pd-section">
        <div class="pd-section-header">
            <h2 class="pd-section-title">
                <i class="fas fa-star" style="color: var(--pd-warning); font-size: 1.2rem;"></i>
                รีวิวสินค้า
                <span class="count-badge"><?= count($all_reviews) ?></span>
            </h2>
            <?php if ($total_p_reviews > 0): ?>
            <div style="background: var(--pd-warning-light); border: 1px solid rgba(245,158,11,0.25); color: #92400e; padding: 6px 18px; border-radius: 50px; font-weight: 900; font-size: 1rem;">
                ★ <?= $avg_p_rating ?> / 5
            </div>
            <?php endif; ?>
        </div>

        <?php if ($total_p_reviews > 0): ?>
        <div class="pd-rating-overview">
            <div class="pd-rating-big">
                <div class="pd-rating-number"><?= $avg_p_rating ?></div>
                <div class="pd-rating-stars-display">
                    <?php 
                    $full_stars = floor($avg_p_rating);
                    $half_star = ($avg_p_rating - $full_stars) >= 0.5;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $full_stars) echo '<i class="fas fa-star"></i>';
                        elseif ($i == $full_stars + 1 && $half_star) echo '<i class="fas fa-star-half-alt"></i>';
                        else echo '<i class="far fa-star"></i>';
                    }
                    ?>
                </div>
                <div class="pd-rating-total"><?= $total_p_reviews ?> รีวิว</div>
            </div>
            <div class="pd-rating-bars">
                <?php for ($s = 5; $s >= 1; $s--): 
                    $cnt = $rating_dist[$s] ?? 0;
                    $pct = $total_p_reviews > 0 ? ($cnt / $total_p_reviews * 100) : 0;
                ?>
                <div class="pd-rating-bar-row">
                    <span class="pd-rating-bar-label"><?= $s ?></span>
                    <div class="pd-bar-track">
                        <div class="pd-bar-fill" style="width: <?= $pct ?>%; animation-delay: <?= (5 - $s) * 0.1 ?>s;"></div>
                    </div>
                    <span class="pd-bar-count"><?= $cnt ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isLoggedIn()): ?>
            <?php $spam_check_ui = canUserReview($user_id, $product_id); ?>

            <?php if ($spam_check_ui['status']): ?>
            <div class="pd-review-form-card">
                <div class="pd-review-form-title">
                    <i class="fas fa-pen" style="color: var(--pd-primary);"></i>
                    แบ่งปันประสบการณ์ของคุณ
                </div>
                <form method="POST" id="reviewForm">
                    <div style="margin-bottom: 20px;">
                        <label class="pd-form-label">คะแนนความพึงพอใจ</label>
                        <div class="pd-star-input" id="starInputRow">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="pds<?= $i ?>" name="rating" value="<?= $i ?>" required>
                            <label for="pds<?= $i ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                        <div id="starHint" style="font-size:0.8rem; color: var(--pd-muted); font-weight:700; margin-top:6px; min-height:18px;"></div>
                    </div>
                    <div>
                        <label class="pd-form-label">ความคิดเห็น</label>
                        <textarea name="comment" 
                                  class="pd-review-textarea" 
                                  id="reviewCommentField"
                                  required 
                                  maxlength="500"
                                  placeholder="บอกเราว่าคุณคิดอย่างไรกับสินค้านี้..."></textarea>
                        <div class="pd-char-counter"><span id="charCount">0</span> / 500</div>
                    </div>
                    <div style="margin-top: 20px; display:flex; gap:12px; align-items:center;">
                        <button type="submit" name="submit_review" class="pd-btn-primary" style="width:auto; padding: 14px 40px;">
                            <i class="fas fa-paper-plane"></i> โพสต์รีวิว
                        </button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="pd-info-blocked">
                <i class="fas fa-info-circle" style="font-size: 1.3rem; flex-shrink:0;"></i>
                <?= $spam_check_ui['message'] ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
        <div class="pd-info-blocked" style="background: rgba(99,102,241,0.06); margin-bottom: 28px;">
            <i class="fas fa-user-lock" style="font-size:1.3rem; flex-shrink:0;"></i>
            <div>
                <div style="font-weight:800; margin-bottom:4px;">ต้องการรีวิวสินค้านี้?</div>
                <div style="font-size:0.88rem; opacity:0.7; font-weight:600;">
                    <a href="../auth/login.php" style="color: var(--pd-primary);">เข้าสู่ระบบ</a> เพื่อเขียนรีวิวและแบ่งปันความคิดเห็นของคุณ
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div id="reviewsContainer">
            <?php if (count($all_reviews) > 0): ?>
                <?php foreach ($all_reviews as $idx => $rev):
                    $avatar = !empty($rev['profile_img']) 
                        ? "../assets/images/profiles/" . $rev['profile_img'] 
                        : "../assets/images/profiles/default_profile.png";
                ?>
                <div class="pd-review-card review-item" style="transition-delay: <?= min($idx * 0.08, 0.5) ?>s;">
                    <div class="pd-review-header">
                        <div class="pd-reviewer-left">
                            <a href="view_profile.php?id=<?= $rev['author_id'] ?>">
                                <img class="pd-reviewer-avatar" src="<?= $avatar ?>" alt="<?= e($rev['fullname']) ?>">
                            </a>
                            <div style="flex:1; min-width:0;">
                                <a href="view_profile.php?id=<?= $rev['author_id'] ?>" class="pd-reviewer-name-link">
                                    <?= e($rev['fullname']) ?>
                                    <?= getUserBadge($rev['author_role'] ?? '') ?>
                                </a>
                                <div class="pd-stars-small">
                                    <?php for ($j = 0; $j < 5; $j++): ?>
                                    <i class="<?= $j < $rev['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="pd-review-date">
                                    <i class="fas fa-clock" style="margin-right:4px;"></i>
                                    <?= date('d M Y - H:i', strtotime($rev['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <div class="pd-review-actions">
                            <button onclick="openReportWithData(<?= $rev['id'] ?>, 'comment')" 
                                    class="pd-icon-btn" title="รายงาน">
                                <i class="fas fa-flag"></i>
                            </button>
                            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher')): ?>
                            <button onclick="openAdminDeleteComment(<?= $rev['id'] ?>, '<?= e(addslashes($rev['fullname'])) ?>')" 
                                    class="pd-icon-btn danger" title="ลบรีวิว">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pd-review-body"><?= nl2br(e($rev['comment'])) ?></div>
                    <?php if ($user_id == $rev['author_id']): ?>
                    <div class="pd-review-my-actions">
                        <button class="pd-my-review-btn edit"
                                onclick="openEditReview(<?= $rev['id'] ?>, <?= $rev['rating'] ?>, '<?= e(addslashes(str_replace(["\r\n","\n","\r"], ' ', $rev['comment']))) ?>')">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <a href="product_detail.php?id=<?= $product_id ?>&action=delete_my_review&rev_id=<?= $rev['id'] ?>"
                           class="pd-my-review-btn delete"
                           onclick="return confirm('ต้องการลบรีวิวนี้?')">
                            <i class="fas fa-trash"></i> ลบ
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="pd-empty-state">
                <div class="pd-empty-state-icon"><i class="far fa-comment-dots"></i></div>
                <div class="pd-empty-state-title">ยังไม่มีรีวิว</div>
                <div class="pd-empty-state-sub">เป็นคนแรกที่รีวิวสินค้านี้!</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($user_id && $user_id != $product['owner_id']): ?>
<div class="pd-sticky-bar" id="stickyBar">
    <div class="pd-sticky-info">
        <div class="pd-sticky-title"><?= e($product['title']) ?></div>
        <div class="pd-sticky-price">฿<?= number_format($product['price'], 2) ?></div>
    </div>
    <a href="checkout.php?id=<?= $product_id ?>" class="pd-sticky-action">
        <i class="fas fa-shopping-bag"></i>
        นัดรับสินค้า
    </a>
</div>
<?php endif; ?>

<div class="pd-zoom-overlay" id="zoomOverlay" onclick="closeZoom()">
    <img class="pd-zoom-img" id="zoomImg" src="" alt="ขยายรูป">
    <div class="pd-zoom-close-hint"><i class="fas fa-times" style="margin-right:6px;"></i> คลิกเพื่อปิด</div>
</div>

<!-- Modal: รายงาน -->
<div class="pd-modal-overlay" id="reportModal">
    <div class="pd-modal-box">
        <button class="pd-modal-close" onclick="closeModal('reportModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="pd-modal-icon" style="background: rgba(239,68,68,0.1); color: var(--pd-danger);">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="pd-modal-title">รายงานเนื้อหา</div>
        <div class="pd-modal-sub">ช่วยให้ BNCC Market ปลอดภัยขึ้น</div>
        <form action="../auth/submit_report.php" method="POST">
            <input type="hidden" name="target_id" id="report_target_id">
            <input type="hidden" name="target_type" id="report_target_type">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <label class="pd-form-label">เหตุผลที่รายงาน</label>
            <textarea name="reason" class="pd-form-textarea" required placeholder="อธิบายเหตุผลที่ต้องการรายงาน..."></textarea>
            <div class="pd-modal-actions">
                <button type="button" class="pd-modal-btn-cancel" onclick="closeModal('reportModal')">ยกเลิก</button>
                <button type="submit" class="pd-modal-btn-danger">ส่งรายงาน</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ลบ comment (admin) -->
<div class="pd-modal-overlay" id="deleteCommentModal">
    <div class="pd-modal-box">
        <button class="pd-modal-close" onclick="closeModal('deleteCommentModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="pd-modal-icon" style="background: rgba(239,68,68,0.1); color: var(--pd-danger);">
            <i class="fas fa-trash-alt"></i>
        </div>
        <div class="pd-modal-title">ลบความคิดเห็น</div>
        <div class="pd-modal-sub">ของ: <strong id="delCommentUserName" style="color:var(--pd-primary);"></strong></div>
        <form action="../admin/admin_delete_comment.php" method="POST">
            <input type="hidden" name="comment_id" id="delCommentId">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <label class="pd-form-label">เหตุผล (บันทึกล็อก)</label>
            <textarea name="reason" class="pd-form-textarea" required placeholder="ระบุเหตุผลที่ลบความคิดเห็นนี้..."></textarea>
            <div class="pd-modal-actions">
                <button type="button" class="pd-modal-btn-cancel" onclick="closeModal('deleteCommentModal')">ยกเลิก</button>
                <button type="submit" class="pd-modal-btn-danger"><i class="fas fa-trash"></i> ลบเลย</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ระงับสินค้า (admin) -->
<div class="pd-modal-overlay" id="deleteProductModal">
    <div class="pd-modal-box">
        <button class="pd-modal-close" onclick="closeModal('deleteProductModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="pd-modal-icon" style="background: rgba(239,68,68,0.1); color: var(--pd-danger);">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="pd-modal-title">ระงับสินค้า</div>
        <div class="pd-modal-sub">สินค้านี้จะถูกซ่อนจากสาธารณะทันที</div>
        <form action="../admin/admin_delete_product.php" method="POST">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <label class="pd-form-label">เหตุผลในการระงับ</label>
            <textarea name="reason" class="pd-form-textarea" required placeholder="เช่น ขัดต่อกฎระเบียบ, ขายของต้องห้าม..."></textarea>
            <div class="pd-modal-actions">
                <button type="button" class="pd-modal-btn-cancel" onclick="closeModal('deleteProductModal')">ยกเลิก</button>
                <button type="submit" class="pd-modal-btn-danger"><i class="fas fa-ban"></i> ระงับเดี๋ยวนี้</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: แก้ไขรีวิว -->
<div class="pd-modal-overlay" id="editReviewModal">
    <div class="pd-modal-box">
        <button class="pd-modal-close" onclick="closeModal('editReviewModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="pd-modal-icon" style="background: var(--pd-primary-light); color: var(--pd-primary);">
            <i class="fas fa-pen"></i>
        </div>
        <div class="pd-modal-title">แก้ไขรีวิว</div>
        <div class="pd-modal-sub">ปรับปรุงความคิดเห็นของคุณ</div>
        <form method="POST">
            <input type="hidden" name="review_id" id="editRevId">
            <div style="margin-bottom:18px;">
                <label class="pd-form-label">คะแนน</label>
                <select name="rating" id="editRevRating" class="pd-form-select">
                    <option value="5">★★★★★ — 5 ดาว ยอดเยี่ยม</option>
                    <option value="4">★★★★☆ — 4 ดาว ดีมาก</option>
                    <option value="3">★★★☆☆ — 3 ดาว พอใช้ได้</option>
                    <option value="2">★★☆☆☆ — 2 ดาว ไม่ค่อยดี</option>
                    <option value="1">★☆☆☆☆ — 1 ดาว ไม่พอใจ</option>
                </select>
            </div>
            <div>
                <label class="pd-form-label">ความคิดเห็น</label>
                <textarea name="comment" id="editRevComment" class="pd-form-textarea" required></textarea>
            </div>
            <div class="pd-modal-actions">
                <button type="button" class="pd-modal-btn-cancel" onclick="closeModal('editReviewModal')">ยกเลิก</button>
                <button type="submit" name="edit_review_submit" class="pd-modal-btn-submit">
                    <i class="fas fa-save"></i> บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ส่งสินค้าในแชท -->
<div class="pd-modal-overlay" id="shareChatModal">
    <div class="pd-modal-box" style="max-width:420px;">
        <button class="pd-modal-close" onclick="closeModal('shareChatModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="pd-modal-title" style="text-align:left; font-size:1.2rem; margin-bottom:4px;">
            <i class="fas fa-paper-plane" style="color:var(--pd-primary); margin-right:8px;"></i>
            ส่งสินค้าให้ผู้ขาย
        </div>
        <div class="pd-modal-sub" style="text-align:left; margin-bottom:18px; font-size:0.83rem;">
            Preview ที่ผู้ขายจะเห็นในแชท
        </div>

        <!-- Product Card Preview -->
        <div class="pd-product-card-preview">
            <img class="pd-product-card-img"
                 src="../assets/images/products/<?= e($main_image) ?>"
                 alt="<?= e($product['title']) ?>">
            <div class="pd-product-card-body">
                <div class="pd-product-card-badge">
                    <i class="fas fa-tag"></i> สินค้า
                </div>
                <div class="pd-product-card-title"><?= e($product['title']) ?></div>
                <div class="pd-product-card-price">฿<?= number_format($product['price'], 2) ?></div>
                <div class="pd-product-card-url">
                    <i class="fas fa-link"></i>
                    <?= BASE_URL ?>/pages/product_detail.php?id=<?= $product_id ?>
                </div>
            </div>
        </div>

        <label class="pd-form-label">
            ข้อความเพิ่มเติม
            <span style="opacity:.45; font-weight:600; text-transform:none; letter-spacing:0;">(ไม่บังคับ)</span>
        </label>
        <textarea id="shareChatExtraMsg"
                  class="pd-form-textarea"
                  placeholder="เช่น อยากสอบถามรายละเอียดเพิ่มเติมครับ..."
                  style="min-height:72px;"></textarea>

        <div class="pd-modal-actions" style="margin-top:18px;">
            <button type="button" class="pd-modal-btn-cancel" onclick="closeModal('shareChatModal')">
                ยกเลิก
            </button>
            <button type="button" class="pd-modal-btn-submit" id="sendProductChatBtn" onclick="confirmSendToChat()">
                <i class="fas fa-paper-plane"></i> ส่งเลย
            </button>
        </div>
    </div>
</div>

<script>
(function() {

    const totalImages = <?= count($product_images) ?>;
    let currentImgIndex = 1;

    function switchImage(url, thumbEl, num, total) {
        const frame = document.getElementById('mainImgFrame');
        const img = document.getElementById('mainImg');
        const counter = document.getElementById('imgCounter');

        document.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('active'));
        thumbEl.classList.add('active');

        frame.classList.add('transitioning');

        setTimeout(() => {
            img.src = url;
            img.onload = () => frame.classList.remove('transitioning');
            if (counter) counter.textContent = num + ' / ' + total;
            currentImgIndex = num;
        }, 200);
    }

    window.switchImage = switchImage;

    function openZoom(src) {
        const overlay = document.getElementById('zoomOverlay');
        const zImg = document.getElementById('zoomImg');
        zImg.src = src;
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeZoom() {
        document.getElementById('zoomOverlay').classList.remove('open');
        document.body.style.overflow = '';
    }

    window.openZoom = openZoom;
    window.closeZoom = closeZoom;

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeZoom();
    });

    function openModal(id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.remove('open');
        document.body.style.overflow = '';
    }

    window.openModal = openModal;
    window.closeModal = closeModal;

    document.querySelectorAll('.pd-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('open');
                document.body.style.overflow = '';
            }
        });
    });

    function openReportWithData(id, type) {
        document.getElementById('report_target_id').value = id;
        document.getElementById('report_target_type').value = type;
        openModal('reportModal');
    }

    window.openReportWithData = openReportWithData;

    const reportBtn = document.querySelector('.pd-report-btn');
    if (reportBtn) {
        reportBtn.addEventListener('click', function() {
            const id = this.dataset.target;
            const type = this.dataset.type;
            document.getElementById('report_target_id').value = id;
            document.getElementById('report_target_type').value = type;
            openModal('reportModal');
        });
    }

    function openAdminDeleteComment(id, name) {
        document.getElementById('delCommentId').value = id;
        document.getElementById('delCommentUserName').textContent = name;
        openModal('deleteCommentModal');
    }

    window.openAdminDeleteComment = openAdminDeleteComment;

    function openEditReview(id, rating, comment) {
        document.getElementById('editRevId').value = id;
        document.getElementById('editRevRating').value = rating;
        document.getElementById('editRevComment').value = comment;
        openModal('editReviewModal');
    }

    window.openEditReview = openEditReview;

    const wishBtn = document.getElementById('wishBtn');
    if (wishBtn) {
        wishBtn.addEventListener('click', function() {
            const btn = this;
            const icon = btn.querySelector('i');
            const productId = btn.dataset.id;

            btn.classList.add('wish-pop');
            setTimeout(() => btn.classList.remove('wish-pop'), 500);

            fetch('../auth/toggle_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'added') {
                    icon.className = 'fas fa-heart';
                    btn.classList.add('wishlisted');
                    btn.title = 'ลบออกจาก Wishlist';
                    showToast('❤️ เพิ่มในรายการโปรดแล้ว', 'success');
                } else if (data.status === 'removed') {
                    icon.className = 'far fa-heart';
                    btn.classList.remove('wishlisted');
                    btn.title = 'เพิ่มใน Wishlist';
                    showToast('💔 ลบออกจากรายการโปรดแล้ว', 'info');
                }
            })
            .catch(() => showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error'));
        });
    }

    function showToast(msg, type = 'success') {
        const existing = document.getElementById('pd-toast');
        if (existing) existing.remove();

        const colors = {
            success: ['rgba(16,185,129,0.15)', 'rgba(16,185,129,0.35)', '#065f46'],
            info: ['rgba(99,102,241,0.1)', 'rgba(99,102,241,0.3)', '#4338ca'],
            error: ['rgba(239,68,68,0.1)', 'rgba(239,68,68,0.3)', '#991b1b']
        };
        const [bg, border, color] = colors[type] || colors.success;

        const toast = document.createElement('div');
        toast.id = 'pd-toast';
        toast.style.cssText = `
            position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%) translateY(20px);
            background: ${bg}; border: 1.5px solid ${border}; color: ${color};
            padding: 12px 24px; border-radius: 50px; font-weight: 800; font-size: 0.9rem;
            z-index: 99999; opacity: 0; transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
            backdrop-filter: blur(10px); white-space: nowrap;
        `;
        toast.textContent = msg;
        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(-50%) translateY(0)';
        });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(20px)';
            setTimeout(() => toast.remove(), 400);
        }, 2800);
    }

    window.showToast = showToast;

    const descText = document.getElementById('descText');
    const descFade = document.getElementById('descFade');
    const descToggle = document.getElementById('descToggle');
    const descChevron = document.getElementById('descChevron');
    let descExpanded = false;

    function toggleDesc() {
        descExpanded = !descExpanded;
        if (descExpanded) {
            descText.classList.add('expanded');
            descFade.classList.add('hidden');
            descToggle.innerHTML = '<i class="fas fa-chevron-up" id="descChevron"></i> ย่อลง';
        } else {
            descText.classList.remove('expanded');
            descFade.classList.remove('hidden');
            descToggle.innerHTML = '<i class="fas fa-chevron-down" id="descChevron"></i> ดูเพิ่มเติม';
        }
    }

    window.toggleDesc = toggleDesc;

    if (descText && descText.scrollHeight <= 145) {
        if (descToggle) descToggle.style.display = 'none';
        if (descFade) descFade.style.display = 'none';
        descText.style.maxHeight = 'none';
    }

    const reviewTextarea = document.getElementById('reviewCommentField');
    const charCounter = document.getElementById('charCount');
    if (reviewTextarea && charCounter) {
        reviewTextarea.addEventListener('input', () => {
            charCounter.textContent = reviewTextarea.value.length;
        });
    }

    const starHints = {
        5: '⭐ ยอดเยี่ยมมาก!',
        4: '👍 ดีมาก',
        3: '😐 พอใช้ได้',
        2: '😕 ไม่ค่อยดี',
        1: '😞 ไม่พอใจเลย'
    };

    document.querySelectorAll('.pd-star-input input').forEach(input => {
        input.addEventListener('change', function() {
            const hint = document.getElementById('starHint');
            if (hint) hint.textContent = starHints[this.value] || '';
        });
    });

    const stickyBar = document.getElementById('stickyBar');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (stickyBar && checkoutBtn) {
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (!entry.isIntersecting) {
                    stickyBar.classList.add('visible');
                } else {
                    stickyBar.classList.remove('visible');
                }
            },
            { threshold: 0.5 }
        );
        observer.observe(checkoutBtn);
    }

    const scrollProgress = document.getElementById('scrollProgress');

    function updateScrollProgress() {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
        if (scrollProgress) scrollProgress.style.width = progress + '%';
    }

    window.addEventListener('scroll', updateScrollProgress, { passive: true });

    const ioReviews = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, i * 80);
                ioReviews.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.pd-review-card').forEach(el => ioReviews.observe(el));

    const ioRelated = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, i * 100);
                ioRelated.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.pd-related-card').forEach(el => ioRelated.observe(el));

    const thumbsRow = document.getElementById('thumbsRow');
    if (thumbsRow && totalImages > 1) {
        document.addEventListener('keydown', (e) => {
            const thumbs = document.querySelectorAll('.pd-thumb');
            let activeIdx = Array.from(thumbs).findIndex(t => t.classList.contains('active'));
            if (e.key === 'ArrowRight' && activeIdx < thumbs.length - 1) {
                thumbs[activeIdx + 1].click();
            } else if (e.key === 'ArrowLeft' && activeIdx > 0) {
                thumbs[activeIdx - 1].click();
            }
        });
    }

    let touchStartX = 0;
    const mainFrame = document.getElementById('mainImgFrame');
    if (mainFrame) {
        mainFrame.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
        mainFrame.addEventListener('touchend', e => {
            const diff = touchStartX - e.changedTouches[0].clientX;
            const thumbs = document.querySelectorAll('.pd-thumb');
            if (!thumbs.length) return;
            let activeIdx = Array.from(thumbs).findIndex(t => t.classList.contains('active'));
            if (Math.abs(diff) > 50) {
                if (diff > 0 && activeIdx < thumbs.length - 1) thumbs[activeIdx + 1].click();
                else if (diff < 0 && activeIdx > 0) thumbs[activeIdx - 1].click();
            }
        }, { passive: true });
    }

    // ========== SHARE FEATURE ==========
    const _shareUrl   = '<?= BASE_URL ?>/pages/product_detail.php?id=<?= (int)$product_id ?>';
    const _shareTitle = <?= json_encode($product['title']) ?>;
    const _sharePrice = '฿<?= number_format($product['price'], 2) ?>';
    const _sellerId   = <?= (int)$product['owner_id'] ?>;
    const _productId  = <?= (int)$product_id ?>;

    // ตั้งค่า href ของปุ่ม social share
    (function initShareLinks() {
        const encodedUrl  = encodeURIComponent(_shareUrl);
        const lineText    = encodeURIComponent('🛍️ ' + _shareTitle + '\n💰 ' + _sharePrice + '\n🔗 ' + _shareUrl);
        const xText       = encodeURIComponent('🛍️ ' + _shareTitle + ' ' + _sharePrice + ' ' + _shareUrl);

        const elLine = document.getElementById('shareLineSocial');
        const elFb   = document.getElementById('shareFbSocial');
        const elX    = document.getElementById('shareXSocial');

        if (elLine) elLine.href = 'https://social-plugins.line.me/lineit/share?url=' + encodedUrl + '&text=' + lineText;
        if (elFb)   elFb.href   = 'https://www.facebook.com/sharer/sharer.php?u=' + encodedUrl;
        if (elX)    elX.href    = 'https://twitter.com/intent/tweet?text=' + xText;
    })();

    // toggle dropdown
    function toggleShareMenu(e) {
        e.stopPropagation();
        const menu = document.getElementById('pdShareMenu');
        if (menu) menu.classList.toggle('open');
    }
    window.toggleShareMenu = toggleShareMenu;

    // ปิด dropdown เมื่อคลิกที่อื่น
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('pdShareMenu');
        const btn  = document.getElementById('shareToggleBtn');
        if (menu && btn && !menu.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            menu.classList.remove('open');
        }
    });

    // copy link / ปิด dropdown
    function doShare(type) {
        const menu = document.getElementById('pdShareMenu');
        if (menu) menu.classList.remove('open');

        if (type === 'copy') {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(_shareUrl)
                    .then(() => showToast('📋 คัดลอกลิงก์แล้ว!', 'success'))
                    .catch(() => fallbackCopy(_shareUrl));
            } else {
                fallbackCopy(_shareUrl);
            }
        }
    }
    window.doShare = doShare;

    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0;';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try {
            document.execCommand('copy');
            showToast('📋 คัดลอกลิงก์แล้ว!', 'success');
        } catch(err) {
            showToast('ไม่สามารถคัดลอกได้ กรุณาคัดลอกเอง', 'error');
        }
        document.body.removeChild(ta);
    }

    // เปิด modal ส่งสินค้าในแชท
    function openShareChatModal() {
        const menu = document.getElementById('pdShareMenu');
        if (menu) menu.classList.remove('open');
        const extra = document.getElementById('shareChatExtraMsg');
        if (extra) extra.value = '';
        openModal('shareChatModal');
    }
    window.openShareChatModal = openShareChatModal;

    // ยืนยันส่งสินค้าในแชท → POST ไป chat_api.php (send action เดิม)
    function confirmSendToChat() {
        const btn      = document.getElementById('sendProductChatBtn');
        const extraMsg = (document.getElementById('shareChatExtraMsg')?.value || '').trim();

        // สร้าง JSON payload เหมือน product card
        const payload = JSON.stringify({
            product_id: _productId,
            title:      _shareTitle,
            price:      _sharePrice,
            image:      '<?= BASE_URL ?>/assets/images/products/<?= e($main_image) ?>',
            url:        _shareUrl,
            extra:      extraMsg
        });

        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> กำลังส่ง...';

        // ใช้ FormData ส่งผ่าน chat_api.php → action=send
        // message = JSON payload, ไม่มี image file
        // frontend ของ chat.php จะ detect JSON แล้ว render เป็น product card
        const fd = new FormData();
        fd.append('action',      'send');
        fd.append('receiver_id', _sellerId);
        fd.append('message',     payload);

        fetch('../ajax/chat_api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> ส่งเลย';

                if (data.status === 'success') {
                    closeModal('shareChatModal');
                    showToast('✅ ส่งสินค้าในแชทแล้ว!', 'success');
                    setTimeout(() => {
                        window.location.href = 'chat.php?user=' + _sellerId;
                    }, 1400);
                } else {
                    showToast('❌ ' + (data.msg || 'เกิดข้อผิดพลาด'), 'error');
                }
            })
            .catch(() => {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> ส่งเลย';
                showToast('❌ เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
            });
    }
    window.confirmSendToChat = confirmSendToChat;
    // ========== END SHARE ==========

})();
</script>

<?php require_once '../includes/footer.php'; ?>