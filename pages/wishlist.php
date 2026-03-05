<?php
/**
 * BNCC Market - My Wishlist Page
 * [SOLID HIGH-CONTRAST REDESIGN]
 * Project: BNCC Student Marketplace [Cite: User Summary]
 */
$pageTitle = "รายการที่ฉันชอบ - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../auth/login.php');

$db = getDB();
$user_id = $_SESSION['user_id'];

// ดึงสินค้าที่ถูกใจ JOIN กับข้อมูลสินค้าและร้านค้า
$stmt = $db->prepare("
    SELECT p.*, s.shop_name, c.category_name 
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    JOIN shops s ON p.shop_id = s.id
    JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();
?>

<style>
    /* ============================================================
       🛠️ SOLID UI SYSTEM - HIGH CONTRAST
       ============================================================ */
    :root {
        --solid-bg: #f8fafc;
        --solid-card: #ffffff;
        --solid-text: #0f172a;
        --solid-border: #cbd5e1;
        --solid-primary: #4f46e5;
        --solid-danger: #ef4444;
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

    .wishlist-wrapper {
        max-width: 1100px;
        margin: 40px auto 80px;
        padding: 0 20px;
    }

    /* 🏰 Page Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 3px solid var(--solid-border);
        opacity: 0;
        transform: translateY(-20px);
        animation: dropIn 0.6s ease forwards;
    }

    .page-header h1 {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--solid-text);
        letter-spacing: -1px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .page-header h1 i { color: var(--solid-danger); }

    .item-count-badge {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        padding: 8px 20px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 1rem;
        color: var(--solid-text);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    /* 🧱 Product Card - Solid Style (เหมือนหน้า Index) */
    .product-box {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        opacity: 0;
        transform: translateY(30px);
    }
    
    .product-box.show { opacity: 1; transform: translateY(0); }
    
    .product-box:hover {
        transform: translateY(-10px);
        border-color: var(--solid-danger); /* เน้นขอบเป็นสีแดงเวลากดโฮเวอร์ในหน้า Wishlist */
        box-shadow: 0 20px 30px rgba(239, 68, 68, 0.15);
    }

    .img-area { 
        height: 220px; 
        width: 100%; 
        position: relative; 
        border-bottom: 2px solid var(--solid-border); 
        overflow: hidden; 
        background: #000;
    }
    
    .img-area img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        transition: 0.5s; 
        opacity: 0.9;
    }
    
    .product-box:hover .img-area img { 
        transform: scale(1.1); 
        opacity: 1;
    }

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

    /* ❤️ Wishlist Button Overlay */
    .wishlist-btn-overlay {
        position: absolute;
        top: 15px;
        right: 15px;
        z-index: 10;
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        width: 45px;
        height: 45px;
        border-radius: 50%;
        color: var(--solid-danger);
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .wishlist-btn-overlay:hover {
        transform: scale(1.15) rotate(10deg);
        border-color: var(--solid-danger);
        background: rgba(239, 68, 68, 0.1);
    }

    .info-wrap { padding: 25px; }
    .info-wrap h3 { font-size: 1.2rem; font-weight: 800; margin-bottom: 10px; color: var(--solid-text); }
    
    .shop-info { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        border-top: 1px solid var(--solid-border); 
        padding-top: 15px; 
        margin-top: 15px; 
        color: var(--text-muted); 
        font-size: 0.85rem; 
        font-weight: 600; 
    }

    /* 📱 Responsive Adjustment */
    @media (max-width: 768px) {
        .page-header { flex-direction: column; gap: 15px; align-items: flex-start; }
    }

    @keyframes dropIn { to { opacity: 1; transform: translateY(0); } }
</style>

<div class="wishlist-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-heart"></i> รายการโปรดของคุณ</h1>
        <div class="item-count-badge">บันทึกไว้ <?= count($wishlist_items) ?> รายการ</div>
    </div>

    <?php if (count($wishlist_items) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 30px;">
            <?php foreach ($wishlist_items as $index => $p): ?>
                <div class="product-box" id="product-<?= $p['id'] ?>" style="transition-delay: <?= $index * 0.05 ?>s;">
                    
                    <button class="wishlist-btn-overlay active" data-id="<?= $p['id'] ?>" title="เอาออกจากรายการโปรด">
                        <i class="fas fa-heart"></i>
                    </button>

                    <a href="product_detail.php?id=<?= $p['id'] ?>" style="text-decoration: none; color: inherit;">
                        <div class="img-area">
                            <img src="../assets/images/products/<?= $p['image_url'] ?>" alt="<?= e($p['title']) ?>">
                            
                            <div class="price-badge">฿<?= number_format($p['price'], 0) ?></div>
                        </div>

                        <div class="info-wrap">
                            <span style="font-size: 0.7rem; font-weight: 900; color: var(--solid-primary); text-transform: uppercase;"><?= e($p['category_name']) ?></span>
                            <h3><?= e($p['title']) ?></h3>
                            
                            <div class="shop-info">
                                <span><i class="fas fa-store"></i> <?= e($p['shop_name']) ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 120px 20px; background: var(--solid-card); border-radius: 32px; border: 3px dashed var(--solid-border); opacity: 0; transform: translateY(20px); animation: dropIn 0.8s ease forwards;">
            <i class="far fa-heart" style="font-size: 5rem; color: var(--solid-border); margin-bottom: 25px;"></i>
            <h2 style="font-weight: 900; font-size: 2rem; margin-bottom: 10px; color: var(--solid-text);">คุณยังไม่มีรายการที่ถูกใจ</h2>
            <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 500; margin-bottom: 30px;">เริ่มสำรวจสินค้าและกดรูปหัวใจเพื่อบันทึกสิ่งที่คุณชอบ</p>
            <a href="index.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem; font-weight: 800; border-radius: 14px;">
                <i class="fas fa-shopping-basket"></i> ไปช้อปปิ้งกันเลย
            </a>
        </div>
    <?php endif; ?>
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

    /**
     * 💔 AJAX Toggle Wishlist Script (Remove Only Mode)
     */
    document.querySelectorAll('.wishlist-btn-overlay').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.id;
            const card = document.getElementById('product-' + productId);

            // Visual feedback before AJAX call
            this.style.transform = 'scale(0.8)';

            fetch('../auth/toggle_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'removed') {
                    // ถ้าถูกลบ ให้สไลด์การ์ดทิ้งอย่างนุ่มนวล
                    card.style.transform = 'scale(0.9) translateY(20px)';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 300);
                }
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>