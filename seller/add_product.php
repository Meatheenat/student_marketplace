<?php
/**
 * Student Marketplace - Add/Edit Product (Multi-Image Support)
 * [Cite: User Summary] แก้ไขโดย Ploy IT Support & Gemini
 */
$pageTitle = "จัดการข้อมูลสินค้า";
require_once '../includes/header.php';
require_once '../includes/functions.php';

checkRole('seller');

$db = getDB();
$user_id = $_SESSION['user_id'];

// 1. ดึง ID ร้านค้าของผู้ใช้ปัจจุบัน
$shop_stmt = $db->prepare("SELECT id, shop_name FROM shops WHERE user_id = ?");
$shop_stmt->execute([$user_id]);
$shop = $shop_stmt->fetch();
$shop_id = $shop['id'];
$shop_name = $shop['shop_name'] ?? 'ร้านค้าไม่ทราบชื่อ';

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
    $p_status    = $_POST['product_status']; 
    $description = trim($_POST['description']);
    $image_url   = $product['image_url'] ?? ''; // รูปหลักเดิม
    $main_image_index = (int)($_POST['main_image_index'] ?? 0); // ดึงค่าว่ารูปไหนคือรูปหลัก

    if (empty($title) || $price <= 0 || empty($category_id)) {
        $_SESSION['flash_message'] = "กรุณากรอกข้อมูลสำคัญให้ครบถ้วน";
        $_SESSION['flash_type'] = "warning";
    } else {
        // --- ส่วนที่เพิ่ม: จัดการหลายรูปภาพ ---
        $uploadedImages = [];
        if (!empty($_FILES['product_images']['name'][0])) {
            $files = $_FILES['product_images'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($i >= 5) break; // จำกัด 5 รูป
                
                $fileArray = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $uploadedFile = uploadImage($fileArray);
                if ($uploadedFile) {
                    $uploadedImages[] = $uploadedFile;
                    // ถ้าเป็นรูปที่เลือกเป็นรูปหลัก ให้บันทึกลง table products ด้วยเพื่อไม่ให้หน้าอื่นพัง
                    if ($i === $main_image_index) {
                        $image_url = $uploadedFile;
                    }
                }
            }
        }

        if ($product_id > 0) {
            $sql = "UPDATE products SET title = ?, price = ?, category_id = ?, status = 'pending', product_status = ?, description = ?, image_url = ? 
                    WHERE id = ? AND shop_id = ?";
            $params = [$title, $price, $category_id, $p_status, $description, $image_url, $product_id, $shop_id];
            $success_msg = "แก้ไขข้อมูลสำเร็จ! สินค้าเข้าสู่สถานะรอแอดมินตรวจสอบ";
            $admin_notify_msg = "🔄 [Admin] สินค้าแก้ไข: $title\n🏪 ร้านค้า: $shop_name";
        } else {
            $sql = "INSERT INTO products (title, price, category_id, status, product_status, description, image_url, shop_id) 
                    VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)";
            $params = [$title, $price, $category_id, $p_status, $description, $image_url, $shop_id];
            $success_msg = "ลงขายสินค้าใหม่สำเร็จ! รอแอดมินอนุมัติ";
            $admin_notify_msg = "🆕 [Admin] สินค้าใหม่: $title\n💰 ฿" . number_format($price, 2);
        }

        $stmt_save = $db->prepare($sql);
        if ($stmt_save->execute($params)) {
            $current_p_id = ($product_id > 0) ? $product_id : $db->lastInsertId();

            // บันทึกรูปลงตารางแยก (product_images)
            if (!empty($uploadedImages)) {
                // ถ้าเป็นโหมดแก้ไข ให้ล้างรูปเก่าออกก่อน (ตามความต้องการ IT Support)
                if ($product_id > 0) {
                    $db->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$product_id]);
                }
                foreach ($uploadedImages as $idx => $path) {
                    $is_main = ($idx === $main_image_index) ? 1 : 0;
                    $db->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)")
                       ->execute([$current_p_id, $path, $is_main]);
                }
            }

            notifyAllAdmins($admin_notify_msg);
            $_SESSION['flash_message'] = $success_msg;
            $_SESSION['flash_type'] = "success";
            redirect('dashboard.php');
        }
    }
}
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="margin-bottom: 30px;">
        <a href="dashboard.php" style="color: var(--text-muted); font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> กลับไปยัง Dashboard</a>
        <h1 style="margin-top: 10px;"><?php echo $product ? 'แก้ไขสินค้า' : 'ลงขายสินค้าใหม่'; ?></h1>
    </div>

    <form action="add_product.php<?php echo $product ? '?id='.$product['id'] : ''; ?>" method="POST" enctype="multipart/form-data">
        <div style="background: var(--bg-card); padding: 30px; border-radius: 16px; border: 1px solid var(--border-color);">
            
            <label style="font-weight: bold; display: block; margin-bottom: 10px;">รูปภาพสินค้า (สูงสุด 5 รูป) <span style="color: var(--color-danger);">*</span></label>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">คลิกที่วงกลมใต้รูปเพื่อเลือกเป็นรูปหลักที่จะแสดงหน้าตลาด</p>
            
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px;">
                <?php for($i=0; $i<5; $i++): ?>
                <div style="text-align: center;">
                    <div onclick="document.getElementById('product_images').click()" style="width: 100%; aspect-ratio: 1; background: #f0f0f0; border: 2px dashed var(--border-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer;">
                        <img id="prev_<?php echo $i; ?>" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                        <i id="icon_<?php echo $i; ?>" class="fas fa-plus" style="color: var(--text-muted);"></i>
                    </div>
                    <div style="margin-top: 8px;">
                        <input type="radio" name="main_image_index" value="<?php echo $i; ?>" <?php echo $i===0 ? 'checked' : ''; ?>>
                        <span style="font-size: 0.8rem;">รูปหลัก</span>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <input type="file" name="product_images[]" id="product_images" accept="image/*" multiple style="display: none;">

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
                <label>สถานะสต็อก</label>
                <select name="product_status" class="form-control">
                    <option value="in-stock" <?php echo (isset($product['product_status']) && $product['product_status'] == 'in-stock') ? 'selected' : ''; ?>>พร้อมส่ง</option>
                    <option value="pre-order" <?php echo (isset($product['product_status']) && $product['product_status'] == 'pre-order') ? 'selected' : ''; ?>>พรีออเดอร์</option>
                </select>
            </div>

            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea name="description" class="form-control" rows="4"><?php echo e($product['description'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-weight: bold; font-size: 1.1rem;">
                <i class="fas fa-save"></i> <?php echo $product ? 'ยืนยันการแก้ไขข้อมูล' : 'ลงขายสินค้าทันที'; ?>
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('product_images').addEventListener('change', function() {
    const files = this.files;
    // ล้างพรีวิวเก่าก่อน
    for(let i=0; i<5; i++) {
        document.getElementById('prev_'+i).style.display = 'none';
        document.getElementById('icon_'+i).style.display = 'block';
    }
    
    // แสดงพรีวิวใหม่
    Array.from(files).forEach((file, i) => {
        if(i < 5) {
            const preview = document.getElementById('prev_'+i);
            const icon = document.getElementById('icon_'+i);
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
            icon.style.display = 'none';
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>