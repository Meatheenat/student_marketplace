<?php
/**
 * Student Marketplace - Home Page
 * [SOLID CENTERED EDITION - BOLD PRICE & ANIMATED]
 * [UPDATED: USER BADGES & SHOP RECOMMENDATIONS]
 * Project: BNCC Student Marketplace
 */
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบเพื่อเข้าใช้งานส่วนนี้";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

$pageTitle = "ค้นหาสินค้า - BNCC Market";
require_once '../includes/header.php';

$db = getDB();

// --- 1. Filter Logic (คงเดิม 100%) ---
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat_id = isset($_GET['cat']) ? $_GET['cat'] : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$cat_stmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $cat_stmt->fetchAll();

// --- 2. SQL Query (🎯 🛠️ เพิ่ม JOIN users เพื่อดึง role ของเจ้าของร้านมาโชว์ Badge)
$sql = "SELECT p.*, s.shop_name, s.status as shop_status, c.category_name, u.role as owner_role,
               IFNULL(AVG(r.rating), 0) as avg_rating,
               COUNT(r.id) as review_count
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN reviews r ON p.id = r.product_id AND r.is_deleted = 0
        WHERE s.status = 'approved' AND p.status = 'approved' AND p.is_deleted = 0";

$params = [];
if (!empty($search)) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($cat_id)) {
    $sql .= " AND p.category_id = ?";
    $params[] = $cat_id;
}
if ($min_price !== null) {
    $sql .= " AND p.price >= ?";
    $params[] = $min_price;
}
if ($max_price !== null) {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price;
}

$sql .= " GROUP BY p.id";

switch ($sort_by) {
    case 'oldest': $sql .= " ORDER BY p.created_at ASC"; break;
    case 'price_low': $sql .= " ORDER BY p.price ASC"; break;
    case 'price_high': $sql .= " ORDER BY p.price DESC"; break;
    case 'top_rated': $sql .= " ORDER BY avg_rating DESC, review_count DESC"; break;
    default: $sql .= " ORDER BY p.created_at DESC";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
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

    /* 🏰 Centered Hero Section */
    .hero-center {
        padding: 80px 20px;
        text-align: center;
        background: var(--solid-card);
        border-bottom: 3px solid var(--solid-primary);
        margin-bottom: 50px;
    }

    .hero-center h1 {
        font-size: 3.5rem;
        font-weight: 900;
        color: var(--solid-text);
        letter-spacing: -2px;
        margin-bottom: 15px;
        opacity: 0;
        transform: translateY(-20px);
        animation: dropIn 0.8s ease forwards;
    }

    .hero-center p {
        font-size: 1.1rem;
        color: var(--text-muted);
        margin-bottom: 40px;
        opacity: 0;
        animation: fadeIn 1s ease 0.4s forwards;
    }

    /* 🔍 Search Bar - Centered & Sharp */
    .search-wrap {
        max-width: 650px;
        margin: 0 auto;
        display: flex;
        gap: 12px;
        padding: 5px;
        background: var(--solid-bg);
        border-radius: 16px;
        border: 2px solid var(--solid-border);
        position: relative; /* 🎯 เพิ่มเพื่อให้ Dropdown เกาะติด */
    }

    .search-wrap input {
        flex: 1;
        background: transparent;
        border: none;
        padding: 15px 20px;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--solid-text);
        outline: none;
    }

    .btn-search-solid {
        background: var(--solid-primary);
        color: #fff;
        border: none;
        padding: 0 30px;
        border-radius: 12px;
        font-weight: 800;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .btn-search-solid:hover { transform: scale(1.05); }

    /* 🎯 🛠️ CSS สำหรับ Search Auto-Complete Dropdown */
    .search-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 16px;
        margin-top: 10px;
        display: none;
        z-index: 9999;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        overflow: hidden;
        animation: fadeUp suggestions 0.3s ease;
    }
    .search-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 20px;
        text-decoration: none;
        color: var(--solid-text);
        border-bottom: 1px solid var(--solid-border);
        transition: background 0.2s;
    }
    .search-item:last-child { border-bottom: none; }
    .search-item:hover { background: rgba(99, 102, 241, 0.1); color: var(--solid-primary); }
    .search-item img { width: 45px; height: 45px; border-radius: 10px; object-fit: cover; border: 1px solid var(--solid-border); }
    .search-item .suggest-title { font-weight: 800; font-size: 0.95rem; margin: 0; }
    .search-item .suggest-price { font-weight: 700; color: #10b981; font-size: 0.85rem; }

    /* 📂 Sidebar */
    .sidebar-sticky { position: sticky; top: 100px; }
    .cat-title { font-size: 0.8rem; font-weight: 900; text-transform: uppercase; color: var(--solid-primary); margin-bottom: 20px; }
    
    .cat-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        margin-bottom: 8px;
        background: var(--solid-card);
        border: 1px solid var(--solid-border);
        border-radius: 12px;
        text-decoration: none;
        color: var(--solid-text);
        font-weight: 700;
        transition: 0.2s;
    }
    .cat-btn:hover { border-color: var(--solid-primary); padding-left: 25px; }
    .cat-btn.active { background: var(--solid-primary); color: #fff; border-color: var(--solid-primary); box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4); }

    /* 🧱 Product Card - Solid Style */
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

    /* 🎯 🛠️ BIG PRICE BADGE (เน้นราคาให้เด่นสุดๆ) */
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
    .shop-info { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--solid-border); padding-top: 15px; margin-top: 15px; color: var(--text-muted); font-size: 0.85rem; font-weight: 600; }

    /* 🎢 Keyframes */
    @keyframes dropIn { to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeIn { to { opacity: 1; } }
    @keyframes suggestions { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .hero-center h1 { font-size: 2.2rem; }
        .main-layout { grid-template-columns: 1fr !important; }
        .sidebar-sticky { display: none; }


</style>

<div class="hero-center">
    <div class="container">
        <h1>ค้นหาสิ่งที่คุณต้องการใน BNCC Market</h1>
        <p>ยินดีต้อนรับคุณ <?php echo e($_SESSION['fullname']); ?> | แหล่งรวมของดีที่เหล่านักศึกษายอมรับ</p>
        
        <form action="index.php" method="GET" class="search-wrap">
            <input type="text" id="main-search" name="q" placeholder="เช่น คุกกี้, อุปกรณ์การเรียน, รับจ้าง..." value="<?= e($search) ?>" autofocus autocomplete="off">
            <button type="submit" class="btn-search-solid">ค้นหาสินค้า</button>
            
            <div id="search-results" class="search-dropdown"></div>
        </form>
    </div>
</div>

<div class="container">
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px;" class="main-layout">
        
        <aside>
            <div class="sidebar-sticky">
                <h3 class="cat-title">Categories</h3>
                <nav>
                    <a href="index.php" class="cat-btn <?= empty($cat_id) ? 'active' : '' ?>">
                        <i class="fas fa-th-large"></i> ทั้งหมด
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?cat=<?= $cat['id'] ?>&sort=<?= $sort_by ?>" class="cat-btn <?= $cat_id == $cat['id'] ? 'active' : '' ?>">
                            <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i> <?= e($cat['category_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <h3 class="cat-title" style="margin-top: 40px;">Filters</h3>
                <form action="index.php" method="GET" style="background: var(--solid-card); padding: 20px; border-radius: 16px; border: 1px solid var(--solid-border);">
                    <input type="hidden" name="q" value="<?= e($search) ?>">
                    <input type="hidden" name="cat" value="<?= e($cat_id) ?>">
                    
                    <div style="margin-bottom: 15px;">
                        <label style="font-size: 0.75rem; font-weight: 800; display: block; margin-bottom: 8px;">เรียงลำดับรายการ</label>
                        <select name="sort" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--solid-border); background: var(--solid-bg); color: var(--solid-text); font-weight: 600;">
                            <option value="newest" <?= $sort_by == 'newest' ? 'selected' : '' ?>>มาใหม่ล่าสุด</option>
                            <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : '' ?>>ราคา: ถูกไปแพง</option>
                            <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : '' ?>>ราคา: แพงไปถูก</option>
                            <option value="top_rated" <?= $sort_by == 'top_rated' ? 'selected' : '' ?>>รีวิวสูงสุด</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 800;">ใช้ตัวกรอง</button>
                    <a href="index.php" style="display: block; text-align: center; margin-top: 15px; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-decoration: none;">ล้างค่าทั้งหมด</a>
                </form>
            </div>
        </aside>

        <main>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
                <h2 style="font-size: 1.8rem; font-weight: 900; letter-spacing: -1px;">
                    <?php 
                        if(!empty($search)) echo "🔎 ผลการค้นหา: " . e($search);
                        else if(!empty($cat_id)) echo "📂 " . e($products[0]['category_name'] ?? 'หมวดหมู่');
                        else echo "สินค้ามาใหม่วันนี้";
                    ?>
                </h2>
                <div style="font-weight: 800; color: var(--solid-primary); font-size: 0.9rem; background: var(--solid-card); padding: 5px 15px; border-radius: 10px; border: 2px solid var(--solid-primary);">
                    <?= count($products) ?> รายการ
                </div>
            </div>

            <?php if (count($products) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 30px;">
                    <?php foreach ($products as $p): ?>
                        <div class="product-box">
                            <a href="product_detail.php?id=<?= $p['id'] ?>" style="text-decoration: none; color: inherit;">
                                <div class="img-area">
                                    <img src="<?= !empty($p['image_url']) ? '../assets/images/products/'.$p['image_url'] : 'https://via.placeholder.com/400x300' ?>" alt="<?= e($p['title']) ?>">
                                    
                                    <div class="price-badge">฿<?= number_format($p['price'], 0) ?></div>
                                    
                                    <?php if($p['review_count'] > 0): ?>
                                        <div style="position: absolute; top: 15px; right: 15px; background: #fbbf24; color: #000; padding: 4px 10px; border-radius: 8px; font-weight: 900; font-size: 0.75rem; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                                            ⭐ <?= round($p['avg_rating'], 1) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="info-wrap">
                                    <span style="font-size: 0.7rem; font-weight: 900; color: var(--solid-primary); text-transform: uppercase;"><?= e($p['category_name']) ?></span>
                                    <h3><?= e($p['title']) ?></h3>
                                    
                                    <div class="shop-info">
                                        <span onclick="window.location.href='<?= BASE_URL ?>pages/shop_profile.php?id=<?= $p['shop_id'] ?>'; return false;" 
                                              style="cursor: pointer; transition: 0.2s;" 
                                              onmouseover="this.style.color='var(--solid-primary)'" 
                                              onmouseout="this.style.color='inherit'">
                                            <i class="fas fa-store"></i> 
                                            <?= e($p['shop_name']) ?> 
                                            <?= getShopBadge($p['shop_id']) ?> 
                                            <?= getUserBadge($p['owner_role']) ?>
                                        </span>
                                        <span><i class="fas fa-eye"></i> <?= number_format($p['views']) ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 120px 20px; border: 3px dashed var(--solid-border); border-radius: 30px;">
                    <i class="fas fa-search" style="font-size: 4rem; color: var(--solid-border); margin-bottom: 20px;"></i>
                    <h3 style="font-weight: 900;">ไม่พบสินค้าที่มึงตามหา</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">ลองเปลี่ยนคำค้นหาหรือตัวกรองดูนะครับเพื่อน</p>
                    <a href="index.php" class="btn btn-primary" style="padding: 12px 40px; border-radius: 12px;">กลับไปดูสินค้าทั้งหมด</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
    /**
     * 🚀 Intersection Observer (สำหรับการ์ดเด้งขึ้นมาทีละอันแบบมีคิว)
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
     * 🎯 🛠️ [เพิ่มใหม่] JavaScript สำหรับระบบ Search Auto-Complete
     */
    const searchInput = document.getElementById('main-search');
    const resultsBox = document.getElementById('search-results');
    let debounceTimer;

    if (searchInput && resultsBox) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const q = this.value.trim();

            if (q.length < 2) {
                resultsBox.style.display = 'none';
                return;
            }

            // หน่วงเวลา 300ms เพื่อไม่ให้ยิง API ถี่เกินไป (ประหยัด Resource เซิร์ฟเวอร์)
            debounceTimer = setTimeout(() => {
                // เรียกไฟล์ API ที่เราสร้างไว้ในขั้นตอนก่อนหน้า
                fetch(`../ajax/search_suggestions.php?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            let html = '';
                            data.forEach(item => {
                                html += `
                                    <a href="product_detail.php?id=${item.id}" class="search-item">
                                        <img src="../assets/images/products/${item.image_url}" onerror="this.src='https://via.placeholder.com/50'">
                                        <div class="info">
                                            <p class="suggest-title">${item.title}</p>
                                            <span class="suggest-price">฿${parseFloat(item.price).toLocaleString()}</span>
                                        </div>
                                    </a>`;
                            });
</script>

<?php require_once '../includes/footer.php'; ?>