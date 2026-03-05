<?php
/**
 * Student Marketplace - Shop Profile Page
 * [SOLID HIGH-CONTRAST REDESIGN + CHAT BUTTON + HIDDEN DELETED PRODUCTS]
 */
require_once '../includes/header.php';
require_once '../includes/functions.php';

$db = getDB();
$current_user_id = $_SESSION['user_id'] ?? 0; // 🎯 รับค่า User ID ของคนที่กำลังดูเว็บอยู่

// 1. รับ ID ร้านค้า
$shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($shop_id <= 0) {
    redirect('index.php');
}

// 2. ดึงข้อมูลร้านค้าและเจ้าของร้าน
$shop_sql = "SELECT s.*, u.fullname, u.class_room 
             FROM shops s 
             JOIN users u ON s.user_id = u.id 
             WHERE s.id = ? AND s.status = 'approved'";
$shop_stmt = $db->prepare($shop_sql);
$shop_stmt->execute([$shop_id]);
$shop = $shop_stmt->fetch();

if (!$shop) {
    echo "<div style='text-align:center; padding:100px; background: var(--bg-body); min-height: 60vh;'>
            <h3 style='color: var(--text-main); font-weight: 800; margin-bottom: 20px;'>ไม่พบร้านค้านี้ หรือร้านค้ายังไม่ได้รับการอนุมัติ</h3>
            <a href='index.php' class='btn btn-primary' style='padding: 12px 30px; border-radius: 14px;'>กลับหน้าแรก</a>
          </div>";
    require_once '../includes/footer.php';
    exit;
}

// 3. ดึงสินค้าทั้งหมดของร้านนี้ (ซ่อนอันที่โดน Soft Delete)
// 🎯 🛠️ เพิ่ม AND p.is_deleted = 0 ตรงนี้
$prod_sql = "SELECT p.*, c.category_name 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE p.shop_id = ? AND p.is_deleted = 0
             ORDER BY p.created_at DESC";
$prod_stmt = $db->prepare($prod_sql);
$prod_stmt->execute([$shop_id]);
$products = $prod_stmt->fetchAll();

$pageTitle = "ร้าน " . $shop['shop_name'];
?>

<style>
    /* ============================================================
       🛠️ SOLID UI SYSTEM - SHOP PROFILE
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

    .shop-wrapper {
        max-width: 1100px;
        margin: 40px auto 80px;
        padding: 0 20px;
    }

    /* 🏰 Shop Banner Card */
    .shop-card-solid {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 32px;
        padding: 40px;
        margin-bottom: 50px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 40px;
        position: relative;
        overflow: hidden;
        animation: dropIn 0.8s ease forwards;
    }

    .shop-card-solid::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 150px; height: 100%;
        background: linear-gradient(135deg, transparent, rgba(99, 102, 241, 0.05));
        border-left: 2px dashed var(--solid-border);
    }

    .shop-logo-solid {
        width: 120px; height: 120px;
        background: var(--solid-primary);
        color: white;
        border-radius: 28px;
        display: flex; align-items: center; justify-content: center;
        font-size: 3.5rem; font-weight: 900;
        border: 4px solid var(--solid-bg);
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        flex-shrink: 0;
        z-index: 1;
    }

    .shop-info-solid {
        flex: 1;
        z-index: 1;
    }
    
    .shop-info-solid h1 {
        font-size: 2.5rem;
        font-weight: 900;
        color: var(--solid-text);
        letter-spacing: -1px;
        margin-bottom: 10px;
    }

    .owner-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--solid-bg);
        border: 1px solid var(--solid-border);
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--solid-text);
        margin-bottom: 20px;
    }

    .shop-desc {
        font-size: 1.1rem;
        line-height: 1.7;
        color: var(--text-muted);
        font-weight: 500;
    }

    .shop-contacts-solid {
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-width: 200px;
        z-index: 1;
    }

    .contact-btn-solid {
        padding: 15px 25px;
        border-radius: 16px;
        font-weight: 800;
        font-size: 0.95rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: 0.2s;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .contact-btn-solid:hover { transform: translateY(-3px) scale(1.02); }
    
    .btn-line-solid { background: #06c755; color: white !important; }
    .btn-ig-solid { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); color: white !important; }
    .btn-chat-solid { background: var(--solid-primary); color: white !important; box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3); }

    /* 🧱 Product Card */
    .section-title {
        font-size: 1.8rem;
        font-weight: 900;
        color: var(--solid-text);
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 3px solid var(--solid-border);
        padding-bottom: 15px;
    }

    .product-box {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        opacity: 0;
        transform: translateY(30px);
        position: relative;
    }
    .product-box.show { opacity: 1; transform: translateY(0); }
    .product-box:hover {
        transform: translateY(-10px);
        border-color: var(--solid-primary);
        box-shadow: 0 20px 30px rgba(0,0,0,0.1);
    }

    .img-area { height: 220px; width: 100%; position: relative; border-bottom: 2px solid var(--solid-border); overflow: hidden; background: #000; }
    .img-area img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; opacity: 0.9; }
    .product-box:hover .img-area img { transform: scale(1.1); opacity: 1; }

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
        z-index: 10;
    }

    @keyframes subtlePulse {
        from { transform: scale(1); }
        to { transform: scale(1.05); }
    }

    .status-badge-top {
        position: absolute;
        top: 15px; right: 15px;
        z-index: 10;
    }

    .info-wrap { padding: 25px; }
    .info-wrap h3 { font-size: 1.2rem; font-weight: 800; margin-bottom: 10px; color: var(--solid-text); }
    
    .btn-view-product {
        display: block;
        text-align: center;
        background: var(--solid-bg);
        border: 2px solid var(--solid-border);
        color: var(--solid-text);
        padding: 12px;
        border-radius: 12px;
        font-weight: 800;
        text-decoration: none;
        margin-top: 20px;
        transition: 0.2s;
    }
    .product-box:hover .btn-view-product { background: var(--solid-primary); color: #fff; border-color: var(--solid-primary); }

    @keyframes dropIn { to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .shop-card-solid { flex-direction: column; text-align: center; padding: 30px 20px; gap: 25px; }
        .shop-logo-solid { margin: 0 auto; }
        .shop-contacts-solid { width: 100%; }
        .shop-card-solid::before { display: none; }
    }
</style>

<div class="shop-wrapper">
    
    <div class="shop-card-solid">
        <div class="shop-logo-solid">
            <?php echo mb_substr($shop['shop_name'], 0, 1, 'UTF-8'); ?>
        </div>

        <div class="shop-info-solid">
            <h1><?php echo e($shop['shop_name']); ?></h1>
            
            <div class="owner-badge">
                <i class="fas fa-user-graduate text-primary"></i> 
                เจ้าของร้าน: <?php echo e($shop['fullname']); ?> (<?php echo e($shop['class_room']); ?>)
            </div>
            
            <p class="shop-desc"><?php echo nl2br(e($shop['description'])); ?></p>
        </div>

        <div class="shop-contacts-solid">
            <?php if ($current_user_id > 0 && $current_user_id != $shop['user_id']): ?>
                <a href="chat.php?user=<?php echo $shop['user_id']; ?>" class="contact-btn-solid btn-chat-solid">
                    <i class="fas fa-comment-dots" style="font-size: 1.2rem;"></i> ทักแชทสอบถาม
                </a>
            <?php endif; ?>

            <?php if(!empty($shop['contact_line'])): ?>
                <a href="<?php echo getContactLink('line', $shop['contact_line']); ?>" target="_blank" class="contact-btn-solid btn-line-solid">
                    <i class="fab fa-line" style="font-size: 1.2rem;"></i> LINE ติดต่อ
                </a>
            <?php endif; ?>
            <?php if(!empty($shop['contact_ig'])): ?>
                <a href="<?php echo getContactLink('ig', $shop['contact_ig']); ?>" target="_blank" class="contact-btn-solid btn-ig-solid">
                    <i class="fab fa-instagram" style="font-size: 1.2rem;"></i> Instagram
                </a>
            <?php endif; ?>
        </div>
    </div>

    <h2 class="section-title">
        <i class="fas fa-box-open" style="color: var(--solid-primary);"></i> สินค้าจากร้านนี้ 
        <span style="font-size: 1rem; background: var(--solid-card); border: 2px solid var(--solid-border); padding: 4px 15px; border-radius: 20px; color: var(--text-muted); margin-left: auto;">ทั้งหมด <?php echo count($products); ?> รายการ</span>
    </h2>

    <?php if(count($products) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 30px;">
            <?php foreach($products as $index => $p): ?>
                <div class="product-box" style="animation-delay: <?= $index * 0.05 ?>s;">
                    
                    <div class="status-badge-top">
                        <?php echo getProductStatusBadge($p['product_status']); ?>
                    </div>

                    <a href="product_detail.php?id=<?php echo $p['id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="img-area">
                            <img src="<?php echo !empty($p['image_url']) ? '../assets/images/products/'.$p['image_url'] : 'https://via.placeholder.com/400x300'; ?>" alt="<?php echo e($p['title']); ?>">
                            <div class="price-badge">฿<?php echo number_format($p['price'], 0); ?></div>
                        </div>

                        <div class="info-wrap">
                            <span style="font-size: 0.7rem; font-weight: 900; color: var(--solid-primary); text-transform: uppercase;"><?php echo e($p['category_name']); ?></span>
                            <h3><?php echo e($p['title']); ?></h3>
                            <div class="btn-view-product">ดูรายละเอียดสินค้า</div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 100px 20px; background: var(--solid-card); border-radius: 32px; border: 3px dashed var(--solid-border);">
            <i class="fas fa-box-open" style="font-size: 5rem; color: var(--solid-border); margin-bottom: 20px;"></i>
            <h3 style="font-weight: 900; font-size: 1.8rem; color: var(--solid-text);">ยังไม่มีสินค้าในร้านนี้</h3>
            <p style="color: var(--text-muted); font-weight: 600; font-size: 1.1rem;">รอติดตามผลงานของเพื่อนๆ ได้เลยครับ</p>
        </div>
    <?php endif; ?>

</div>

<script>
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
</script>

<?php require_once '../includes/footer.php'; ?>