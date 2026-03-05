<?php
/**
 * ระบบตลาดออนไลน์นักเรียน (Student Marketplace)
 * หน้าหลัก (Home Page) - ปรับปรุง UI ส่วนการค้นหาให้ชัดเจน
 */
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบเพื่อเข้าใช้งานส่วนนี้";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

$pageTitle = "หน้าแรก - ค้นหาสินค้าและบริการ";
require_once '../includes/header.php';

$db = getDB();
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat_id = isset($_GET['cat']) ? $_GET['cat'] : '';

// ดึงข้อมูลหมวดหมู่
$cat_stmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $cat_stmt->fetchAll();

// 🎯 🛠️ เตรียม SQL ดึงสินค้า (โชว์เฉพาะร้านที่อนุมัติแล้ว, สินค้าที่อนุมัติแล้ว และ "ยังไม่ถูกลบ")
$sql = "SELECT p.*, s.shop_name, s.status as shop_status, c.category_name 
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        JOIN categories c ON p.category_id = c.id
        WHERE s.status = 'approved' AND p.status = 'approved' AND p.is_deleted = 0"; // เพิ่ม AND p.is_deleted = 0

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
$sql .= " ORDER BY p.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<style>
    .search-input-box::placeholder {
        color: #94a3b8 !important; /* สีเทาฟ้า ตัดกับพื้นหลังขาวชัดเจน */
        opacity: 1;
    }
    .search-input-box:focus {
        outline: none;
        background: #ffffff !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2) !important;
    }
    .hero-section {
        text-align: center; 
        padding: 60px 20px; 
        background: linear-gradient(135deg, #4f46e5, #6366f1); 
        border-radius: 24px; 
        margin-bottom: 40px; 
        color: white;
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.2);
    }
</style>

<section class="hero-section">
    <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 10px; letter-spacing: -1px;">ตลาดนัด BNCC</h1>
    <p style="opacity: 0.9; font-size: 1.1rem; margin-bottom: 35px;">ยินดีต้อนรับคุณ <?php echo e($_SESSION['fullname']); ?> | เลือกช้อปสินค้าจากเพื่อนร่วมวิทยาลัย</p>
    
    <form action="index.php" method="GET" style="max-width: 650px; margin: 0 auto; display: flex; gap: 0; background: white; padding: 5px; border-radius: 18px; box-shadow: 0 8px 25px rgba(0,0,0,0.1);">
        <div style="position: relative; flex: 1; display: flex; align-items: center;">
            <i class="fas fa-search" style="position: absolute; left: 18px; color: #64748b; font-size: 1.1rem;"></i>
            <input type="text" name="q" class="search-input-box" 
                   placeholder="ค้นหาสินค้า เช่น คุกกี้, รับวาดรูป..." 
                   value="<?php echo e($search); ?>" 
                   style="width: 100%; padding: 14px 15px 14px 50px; border: none; font-size: 1rem; color: #1e293b; background: transparent;">
        </div>
        <button type="submit" class="btn" style="background: #1e293b; color: white; padding: 0 35px; border-radius: 14px; font-weight: 600; margin-left: 5px; transition: 0.3s;">
            ค้นหา
        </button>
    </form>
</section>

<div style="display: grid; grid-template-columns: 250px 1fr; gap: 30px;" class="main-layout">
    <aside class="sidebar" style="background: var(--bg-card); padding: 25px; border-radius: 20px; height: fit-content; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
        <h3 style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 700; color: var(--text-main); border-left: 4px solid var(--primary-color); padding-left: 12px;">หมวดหมู่</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="margin-bottom: 12px;">
                <a href="index.php" style="text-decoration: none; color: <?php echo empty($cat_id) ? 'var(--primary-color)' : 'var(--text-muted)'; ?>; font-weight: <?php echo empty($cat_id) ? '700' : '400'; ?>;">
                    <i class="fas fa-border-all" style="width: 25px;"></i> ทั้งหมด
                </a>
            </li>
            <?php foreach ($categories as $cat): ?>
            <li style="margin-bottom: 12px;">
                <a href="index.php?cat=<?php echo $cat['id']; ?>" style="text-decoration: none; color: <?php echo $cat_id == $cat['id'] ? 'var(--primary-color)' : 'var(--text-muted)'; ?>; font-weight: <?php echo $cat_id == $cat['id'] ? '700' : '400'; ?>;">
                    <i class="fas fa-chevron-right" style="font-size: 0.8rem; width: 25px;"></i> <?php echo e($cat['category_name']); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <section>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="font-size: 1.4rem; font-weight: 700;">
                <?php 
                    if(!empty($search)) echo "🔍 ผลการค้นหา: " . e($search);
                    else if(!empty($cat_id)) echo "📂 " . e($products[0]['category_name'] ?? 'หมวดหมู่นี้');
                    else echo "✨ สินค้ามาใหม่ล่าสุด";
                ?>
            </h2>
            <span style="color: var(--text-muted); font-size: 0.85rem; background: var(--bg-card); padding: 5px 12px; border-radius: 20px; border: 1px solid var(--border-color);">
                ทั้งหมด <?php echo count($products); ?> รายการ
            </span>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
                <?php foreach ($products as $p): ?>
                    <div class="product-card" style="background: var(--bg-card); border-radius: 20px; overflow: hidden; border: 1px solid var(--border-color); transition: 0.3s; position: relative;">
                        <a href="product_detail.php?id=<?php echo $p['id']; ?>" style="text-decoration: none; color: inherit;">
                            <img src="<?php echo !empty($p['image_url']) ? '../assets/images/products/'.$p['image_url'] : 'https://via.placeholder.com/400x300'; ?>" 
                                 alt="<?php echo e($p['title']); ?>" style="width: 100%; height: 200px; object-fit: cover;">
                            <div style="padding: 20px;">
                                <span style="font-size: 0.75rem; color: var(--primary-color); font-weight: 600; text-transform: uppercase;"><?php echo e($p['category_name']); ?></span>
                                <h3 style="font-size: 1.1rem; margin: 8px 0; font-weight: 700;"><?php echo e($p['title']); ?></h3>
                                <div style="font-size: 1.2rem; font-weight: 800; color: var(--primary-color); margin-bottom: 15px;">฿<?php echo number_format($p['price'], 2); ?></div>
                                <div style="display: flex; align-items: center; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px; font-size: 0.85rem; color: var(--text-muted);">
                                    <i class="fas fa-store"></i> <?php echo e($p['shop_name']); ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px; background: var(--bg-card); border-radius: 24px; border: 2px dashed var(--border-color);">
                <div style="font-size: 4rem; margin-bottom: 20px;">🏜️</div>
                <h3 style="color: var(--text-main); font-weight: 700;">ไม่พบสินค้าที่คุณค้นหา</h3>
                <p style="color: var(--text-muted); margin-bottom: 25px;">ลองเปลี่ยนคำค้นหา หรือเลือกหมวดหมู่อื่นดูนะครับ</p>
                <a href="index.php" class="btn btn-primary" style="padding: 12px 30px; border-radius: 12px;">ดูสินค้าทั้งหมด</a>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>