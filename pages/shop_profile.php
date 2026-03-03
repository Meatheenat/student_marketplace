<?php
/**
 * Student Marketplace - Shop Profile Page
 */
require_once '../includes/header.php';

$db = getDB();

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
    echo "<div style='text-align:center; padding:100px;'><h3>ไม่พบร้านค้านี้ หรือร้านค้ายังไม่ได้รับการอนุมัติ</h3><a href='index.php' class='btn btn-primary'>กลับหน้าแรก</a></div>";
    require_once '../includes/footer.php';
    exit;
}

// 3. ดึงสินค้าทั้งหมดของร้านนี้
$prod_sql = "SELECT p.*, c.category_name 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE p.shop_id = ? 
             ORDER BY p.created_at DESC";
$prod_stmt = $db->prepare($prod_sql);
$prod_stmt->execute([$shop_id]);
$products = $prod_stmt->fetchAll();

$pageTitle = "ร้าน " . $shop['shop_name'];
?>

<section style="background: var(--bg-card); border-radius: 20px; padding: 40px; margin-bottom: 40px; box-shadow: var(--shadow); border: 1px solid var(--border-color); position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: var(--primary-color); opacity: 0.05; border-radius: 50%;"></div>

    <div style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap; position: relative; z-index: 1;">
        <div style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary-color), #818cf8); color: white; border-radius: 24px; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 600; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);">
            <?php echo mb_substr($shop['shop_name'], 0, 1, 'UTF-8'); ?>
        </div>

        <div style="flex: 1; min-width: 300px;">
            <h1 style="font-size: 2.2rem; margin-bottom: 5px;"><?php echo e($shop['shop_name']); ?></h1>
            <p style="color: var(--text-muted); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-user-graduate"></i> เจ้าของร้าน: <?php echo e($shop['fullname']); ?> (<?php echo e($shop['class_room']); ?>)
            </p>
            <p style="font-size: 1.1rem; line-height: 1.6; color: var(--text-main);"><?php echo e($shop['description']); ?></p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php if(!empty($shop['contact_line'])): ?>
                <a href="<?php echo getContactLink('line', $shop['contact_line']); ?>" target="_blank" class="btn" style="background: #00c300; color: white; width: 180px;">
                    <i class="fab fa-line"></i> LINE: <?php echo e($shop['contact_line']); ?>
                </a>
            <?php endif; ?>
            <?php if(!empty($shop['contact_ig'])): ?>
                <a href="<?php echo getContactLink('ig', $shop['contact_ig']); ?>" target="_blank" class="btn" style="background: linear-gradient(45deg, #f09433 0%, #dc2743 50%, #bc1888 100%); color: white; width: 180px;">
                    <i class="fab fa-instagram"></i> IG: <?php echo e($shop['contact_ig']); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<h2 style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
    <i class="fas fa-box-open" style="color: var(--primary-color);"></i> สินค้าของร้านนี้ (<?php echo count($products); ?>)
</h2>

<?php if(count($products) > 0): ?>
    <div class="product-grid">
        <?php foreach($products as $p): ?>
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
                    
                    <a href="product_detail.php?id=<?php echo $p['id']; ?>" class="btn btn-outline" style="width: 100%; margin-top: 15px; font-size: 0.85rem;">
                        ดูรายละเอียด
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div style="text-align: center; padding: 50px 0; background: var(--bg-card); border-radius: 16px; border: 1px dashed var(--border-color);">
        <i class="fas fa-box-open" style="font-size: 3rem; color: var(--border-color); margin-bottom: 15px;"></i>
        <h3 style="color: var(--text-muted);">ยังไม่มีสินค้าในร้านนี้</h3>
        <p style="color: var(--text-muted);">รอติดตามผลงานของเพื่อนๆ ได้เลย</p>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>