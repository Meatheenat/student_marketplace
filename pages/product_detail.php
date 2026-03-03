<?php
/**
 * Student Marketplace - Product Detail Page
 */
require_once '../includes/header.php';

$db = getDB();

// 1. รับ ID สินค้าและตรวจสอบความถูกต้อง
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    redirect('index.php');
}

// 2. ดึงข้อมูลสินค้า Join กับร้านค้า, หมวดหมู่ และผู้ใช้งาน (เพื่อเอาชื่อจริงและห้องเรียน)
$sql = "SELECT p.*, s.shop_name, s.contact_line, s.contact_ig, s.description as shop_desc, 
               c.category_name, u.fullname as seller_name, u.class_room 
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON s.user_id = u.id
        WHERE p.id = ? AND s.status = 'approved'";

$stmt = $db->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// ถ้าไม่พบสินค้า
if (!$product) {
    echo "<div style='text-align:center; padding:100px;'><h3>ไม่พบข้อมูลสินค้า หรือสินค้าถูกนำออกไปแล้ว</h3><a href='index.php' class='btn btn-primary'>กลับหน้าแรก</a></div>";
    require_once '../includes/footer.php';
    exit;
}

// ตั้งค่าหัวข้อหน้าเว็บ
$pageTitle = $product['title'];
?>

<nav style="margin-bottom: 20px; font-size: 0.9rem; color: var(--text-muted);">
    <a href="index.php">หน้าแรก</a> / 
    <a href="index.php?cat=<?php echo $product['category_id']; ?>"><?php echo e($product['category_name']); ?></a> / 
    <span style="color: var(--text-main);"><?php echo e($product['title']); ?></span>
</nav>

<div class="detail-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; background: var(--bg-card); padding: 30px; border-radius: 20px; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
    
    <div class="detail-image">
        <img src="<?php echo !empty($product['image_url']) ? '../assets/images/products/'.$product['image_url'] : 'https://via.placeholder.com/600x600?text=No+Image'; ?>" 
             alt="<?php echo e($product['title']); ?>" 
             style="width: 100%; border-radius: 12px; object-fit: cover; box-shadow: var(--shadow);">
    </div>

    <div class="detail-info">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
            <span class="product-category" style="font-size: 1rem;"><?php echo e($product['category_name']); ?></span>
            <?php echo getProductStatusBadge($product['product_status']); ?>
        </div>
        
        <h1 style="font-size: 2rem; margin-bottom: 15px; line-height: 1.2;"><?php echo e($product['title']); ?></h1>
        
        <div class="product-price" style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 25px;">
            <?php echo formatPrice($product['price']); ?>
        </div>

        <div style="margin-bottom: 30px; padding: 20px; background: var(--bg-body); border-radius: 12px; border-left: 4px solid var(--primary-color);">
            <h4 style="margin-bottom: 10px;">รายละเอียดสินค้า</h4>
            <p style="white-space: pre-line; color: var(--text-muted);"><?php echo e($product['description']); ?></p>
        </div>

        <div class="seller-box" style="padding: 20px; border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 30px;">
            <h4 style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-user-circle"></i> ข้อมูลผู้ขาย
            </h4>
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 50px; height: 50px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                    <?php echo mb_substr($product['seller_name'], 0, 1, 'UTF-8'); ?>
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 1.1rem;"><?php echo e($product['seller_name']); ?> (<?php echo e($product['class_room']); ?>)</div>
                    <div style="font-size: 0.9rem; color: var(--text-muted);">ร้านค้า: <?php echo e($product['shop_name']); ?></div>
                </div>
            </div>
            
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">
                * การซื้อขายเป็นการตกลงกันโดยตรงระหว่างนักเรียน โปรดตรวจสอบสินค้าก่อนชำระเงิน
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <?php if(!empty($product['contact_line'])): ?>
                    <a href="<?php echo getContactLink('line', $product['contact_line']); ?>" target="_blank" class="btn" style="background: #00c300; color: white;">
                        <i class="fab fa-line"></i> ติดต่อทาง LINE
                    </a>
                <?php endif; ?>

                <?php if(!empty($product['contact_ig'])): ?>
                    <a href="<?php echo getContactLink('ig', $product['contact_ig']); ?>" target="_blank" class="btn" style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: white;">
                        <i class="fab fa-instagram"></i> ติดต่อทาง IG
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <a href="shop_profile.php?id=<?php echo $product['shop_id']; ?>" class="btn btn-outline" style="width: 100%;">
            <i class="fas fa-store"></i> ดูสินค้าอื่นในร้านนี้
        </a>
    </div>
</div>

<style>
/* Responsive สำหรับหน้า Product Detail */
@media (max-width: 768px) {
    .detail-container {
        grid-template-columns: 1fr !important;
        padding: 15px !important;
    }
    .detail-info h1 {
        font-size: 1.5rem !important;
    }
    .product-price {
        font-size: 1.8rem !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>