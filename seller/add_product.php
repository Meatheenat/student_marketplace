<?php
/**
 * Student Marketplace - Add/Edit Product (Approval System Version)
 */
$pageTitle = "จัดการข้อมูลสินค้า";
require_once '../includes/header.php';
require_once '../includes/functions.php';

checkRole('seller');

$db = getDB();
$user_id = $_SESSION['user_id'];

// 1. ดึง ID ร้านค้าของผู้ใช้ปัจจุบัน
$shop_stmt = $db->prepare("SELECT id FROM shops WHERE user_id = ?");
$shop_stmt->execute([$user_id]);
$shop = $shop_stmt->fetch();
$shop_id = $shop['id'];

// 2. ตรวจสอบโหมด: แก้ไข (Edit) หรือ เพิ่มใหม่ (Add)
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($product_id > 0) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND shop_id = ?");
    $stmt->execute([$product_id, $shop_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $_SESSION['flash_message'] = "ไม่พบสินค้า หรือคุณไม่มีสิทธิ์เข้าถึง";
        $_SESSION['flash_type'] = "danger";
        redirect('dashboard.php');
    }
}

$cat_stmt = $db->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $cat_stmt->fetchAll();

// 4. จัดการการส่งข้อมูล (Form Submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $price       = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    $p_status    = $_POST['product_status']; // สถานะสต็อกสินค้า
    $description = trim($_POST['description']);
    $image_url   = $product['image_url'] ?? '';

    if (empty($title) || $price <= 0 || empty($category_id)) {
        $_SESSION['flash_message'] = "กรุณากรอกข้อมูลสำคัญให้ครบถ้วน";
        $_SESSION['flash_type'] = "warning";
    } else {
        if (!empty($_FILES['product_image']['name'])) {
            $uploadedFile = uploadImage($_FILES['product_image']);
            if ($uploadedFile) {
                $image_url = $uploadedFile;
            } else {
                $_SESSION['flash_message'] = "อัปโหลดรูปภาพไม่สำเร็จ";
                $_SESSION['flash_type'] = "danger";
            }
        }

        // 🛠️ แก้ไข SQL: เพิ่มระบบ 'pending' เมื่อมีการบันทึกข้อมูล
        if ($product_id > 0) {
            // เมื่อแก้ไข: รีเซ็ตสถานะเป็น 'pending' เพื่อให้แอดมินตรวจใหม่
            $sql = "UPDATE products SET title = ?, price = ?, category_id = ?, status = 'pending', product_status = ?, description = ?, image_url = ? 
                    WHERE id = ? AND shop_id = ?";
            $params = [$title, $price, $category_id, $p_status, $description, $image_url, $product_id, $shop_id];
            $success_msg = "แก้ไขข้อมูลสำเร็จ! สินค้าเข้าสู่สถานะรอแอดมินตรวจสอบอีกครั้ง";
        } else {
            // เมื่อเพิ่มใหม่: ตั้งค่าเริ่มต้นเป็น 'pending'
            $sql = "INSERT INTO products (title, price, category_id, status, product_status, description, image_url, shop_id) 
                    VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)";
            $params = [$title, $price, $category_id, $p_status, $description, $image_url, $shop_id];
            $success_msg = "ลงขายสินค้าใหม่สำเร็จ! กรุณารอแอดมินอนุมัติก่อนแสดงในตลาด";
        }

        $stmt_save = $db->prepare($sql);
        if ($stmt_save->execute($params)) {
            $_SESSION['flash_message'] = $success_msg;
            $_SESSION['flash_type'] = "success";
            redirect('dashboard.php');
        }
    }
}
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 30px;">
        <a href="dashboard.php" style="color: var(--text-muted); font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> กลับไปยัง Dashboard</a>
        <h1 style="margin-top: 10px;"><?php echo $product ? 'แก้ไขสินค้า' : 'ลงขายสินค้าใหม่'; ?></h1>
    </div>

    <form action="add_product.php<?php echo $product ? '?id='.$product['id'] : ''; ?>" method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px;" class="form-layout">
            
            <div class="upload-section">
                <div style="background: var(--bg-card); padding: 20px; border-radius: 16px; border: 2px dashed var(--border-color); text-align: center;">
                    <label style="display: block; cursor: pointer;">
                        <img id="image_preview" 
                             src="<?php echo ($product && $product['image_url']) ? '../assets/images/products/'.$product['image_url'] : ''; ?>" 
                             style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 12px; margin-bottom: 15px; display: <?php echo ($product && $product['image_url']) ? 'block' : 'none'; ?>;">
                        
                        <div id="upload_placeholder" style="<?php echo ($product && $product['image_url']) ? 'display:none;' : ''; ?>">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: var(--border-color); margin-bottom: 10px;"></i>
                            <p style="color: var(--text-muted); font-size: 0.9rem;">คลิกเพื่อเลือกรูปภาพสินค้า</p>
                        </div>
                        <input type="file" name="product_image" id="product_image" accept="image/*" style="display: none;">
                    </label>
                </div>
            </div>

            <div class="info-section" style="background: var(--bg-card); padding: 30px; border-radius: 16px; border: 1px solid var(--border-color);">
                <div class="form-group">
                    <label>ชื่อสินค้า <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?php echo e($product['title'] ?? ''); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>ราคา (บาท)</label>
                        <input type="number" name="price" step="0.01" class="form-control" value="<?php echo e($product['price'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>หมวดหมู่</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">เลือกหมวดหมู่</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (isset($product['category_id']) && $product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>สถานะสต็อก (ไม่ใช่สถานะการอนุมัติ)</label>
                    <select name="product_status" class="form-control">
                        <option value="in-stock" <?php echo (isset($product['product_status']) && $product['product_status'] == 'in-stock') ? 'selected' : ''; ?>>พร้อมส่ง</option>
                        <option value="pre-order" <?php echo (isset($product['product_status']) && $product['product_status'] == 'pre-order') ? 'selected' : ''; ?>>พรีออเดอร์</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>รายละเอียด</label>
                    <textarea name="description" class="form-control" rows="4"><?php echo e($product['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; margin-top: 10px;">
                    <i class="fas fa-save"></i> <?php echo $product ? 'บันทึกการแก้ไข (ส่งตรวจใหม่)' : 'ลงขายสินค้า (รออนุมัติ)'; ?>
                </button>
            </div>
        </div>
    </form>
</div>



<script>
document.getElementById('product_image').addEventListener('change', function() {
    const [file] = this.files;
    if (file) {
        document.getElementById('upload_placeholder').style.display = 'none';
        const preview = document.getElementById('image_preview');
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>