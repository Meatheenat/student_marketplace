<?php
/**
 * ระบบตลาดออนไลน์นักเรียน (Student Marketplace)
 * หน้าหลักสำหรับแสดงรายการสินค้าและบริการ (Home Page)
 * ตรวจสอบสิทธิ์การเข้าถึง: ผู้ใช้ต้องเข้าสู่ระบบก่อนเท่านั้น
 */
require_once '../includes/functions.php';

// 1. ตรวจสอบสถานะการเข้าสู่ระบบ (Security Check)
if (!isLoggedIn()) {
    // หากยังไม่ได้เข้าสู่ระบบ ให้แจ้งเตือนและส่งกลับไปยังหน้าเข้าสู่ระบบ
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบเพื่อเข้าใช้งานส่วนนี้";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

// 2. การตั้งค่าหน้าเว็บและดึงข้อมูลพื้นฐาน
$pageTitle = "หน้าแรก - ค้นหาสินค้าและบริการ";
require_once '../includes/header.php';

$db = getDB();

// รับค่าการค้นหาและหมวดหมู่
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat_id = isset($_GET['cat']) ? $_GET['cat'] : '';

// 3. ดึงข้อมูลหมวดหมู่ทั้งหมดสำหรับ Sidebar
$cat_stmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $cat_stmt->fetchAll();

// 4. เตรียม SQL สำหรับดึงสินค้า (เฉพาะร้านที่ได้รับการอนุมัติแล้วเท่านั้น)
$sql = "SELECT p.*, s.shop_name, s.status as shop_status, c.category_name 
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        JOIN categories c ON p.category_id = c.id
        WHERE s.status = 'approved'";

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

<section style="text-align: center; padding: 40px 0; background: linear-gradient(135deg, var(--primary-color), #6366f1); border-radius: 20px; margin-bottom: 40px; color: white;">
    <h1 style="font-size: 2.5rem; margin-bottom: 10px;">ตลาดนัดนักเรียน</h1>
    <p style="opacity: 0.9; margin-bottom: 30px;">ยินดีต้อนรับคุณ <?php echo e($_SESSION['fullname']); ?> เข้าสู่ระบบการค้นหาสินค้า</p>
    
    <form action="index.php" method="GET" style="max-width: 600px; margin: 0 auto; display: flex; gap: 10px; padding: 0 20px;">
        <input type="text" name="q" class="form-control" placeholder="ค้นหาสินค้า เช่น คุกกี้, รับวาดรูป..." value="<?php echo e($search); ?>" style="border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <button type="submit" class="btn" style="background: var(--text-main); color: white; white-space: nowrap;">
            <i class="fas fa-search"></i> ค้นหา
        </button>
    </form>
</section>

<div style="display: grid; grid-template-columns: 250px 1fr; gap: 30px;" class="main-layout">
    <aside class="sidebar" style="background: var(--bg-card); padding: 20px; border-radius: 16px; height: fit-content; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
        <h3 style="margin-bottom: 15px; font-size: 1.1rem; border-bottom: 2px solid var(--primary-color); padding-bottom: 8px; display: inline-block;">หมวดหมู่</h3>
        <ul style="margin-top: 10px;">
            <li style="margin-bottom: 10px;">
                <a href="index.php" style="color: <?php echo empty($cat_id) ? 'var(--primary-color)' : 'inherit'; ?>; font-weight: <?php echo empty($cat_id) ? '600' : '400'; ?>;">
                    <i class="fas fa-th-large" style="width: 20px;"></i> ทั้งหมด
                </a>
            </li>
            <?php foreach ($categories as $cat): ?>
            <li style="margin-bottom: 10px;">
                <a href="index.php?cat=<?php echo $cat['id']; ?>" style="color: <?php echo $cat_id == $cat['id'] ? 'var(--primary-color)' : 'inherit'; ?>; font-weight: <?php echo $cat_id == $cat['id'] ? '600' : '400'; ?>;">
                    <i class="fas fa-chevron-right" style="font-size: 0.8rem; width: 20px;"></i> <?php echo e($cat['category_name']); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <section>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.5rem;">
                <?php 
                    if(!empty($search)) echo "ผลการค้นหา: " . e($search);
                    else if(!empty($cat_id)) echo "หมวดหมู่: " . e($products[0]['category_name'] ?? 'ไม่พบสินค้า');
                    else echo "สินค้ามาใหม่ล่าสุด";
                ?>
            </h2>
            <span style="color: var(--text-muted); font-size: 0.9rem;">พบสินค้าทั้งหมด <?php echo count($products); ?> รายการ</span>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="product-grid">
                <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <div style="position: absolute; top: 10px; right: 10px; z-index: 10;">
                            <?php echo getProductStatusBadge($p['product_status']); ?>
                        </div>

                        <a href="product_detail.php?id=<?php echo $p['id']; ?>">
                            <img src="<?php echo !empty($p['image_url']) ? '../assets/images/products/'.$p['image_url'] : 'https://via.placeholder.com/300x200?text=No+Image'; ?>" 
                                 alt="<?php echo e($p['title']); ?>" class="product-image">
                        </a>

                        <div class="product-info">
                            <span class="product-category"><?php echo e($p['category_name']); ?></span>
                            <h3 class="product-title">
                                <a href="product_detail.php?id=<?php echo $p['id']; ?>"><?php echo e($p['title']); ?></a>
                            </h3>
                            <div class="product-price"><?php echo formatPrice($p['price']); ?></div>
                            
                            <div class="product-seller">
                                <i class="fas fa-store" style="font-size: 0.8rem;"></i>
                                <a href="shop_profile.php?id=<?php echo $p['shop_id']; ?>" style="font-weight: 500; color: var(--primary-color);">
                                    <?php echo e($p['shop_name']); ?>
                                </a>
                            </div>
                            
                            <a href="product_detail.php?id=<?php echo $p['id']; ?>" class="btn btn-outline" style="width: 100%; margin-top: 15px; font-size: 0.85rem;">
                                ดูรายละเอียดเพิ่มเติม
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 100px 0; background: var(--bg-card); border-radius: 16px; margin-top: 20px; border: 1px dashed var(--border-color);">
                <i class="fas fa-search" style="font-size: 3rem; color: var(--border-color); margin-bottom: 15px;"></i>
                <h3 style="color: var(--text-muted);">ขออภัย ไม่พบสินค้าที่คุณกำลังค้นหา</h3>
                <p style="color: var(--text-muted);">ท่านสามารถลองใช้คำค้นหาอื่น หรือเลือกหมวดหมู่อื่นเพื่อตรวจสอบสินค้าได้ครับ</p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">แสดงสินค้าทั้งหมด</a>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
@media (max-width: 992px) {
    .main-layout { grid-template-columns: 1fr !important; }
    .sidebar { display: flex; overflow-x: auto; gap: 15px; padding: 15px !important; white-space: nowrap; }
    .sidebar h3 { display: none; }
    .sidebar ul { display: flex; gap: 15px; margin-top: 0 !important; }
    .sidebar li { margin-bottom: 0 !important; }
}
</style>

<?php require_once '../includes/footer.php'; ?>