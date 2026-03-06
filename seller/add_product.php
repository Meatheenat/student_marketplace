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
    $p_status    = $_POST['product_status']; // สถานะสต็อกสินค้า
    $description = trim($_POST['description']);
    $image_url   = $product['image_url'] ?? '';

    if (empty($title) || $price <= 0 || empty($category_id)) {
        $_SESSION['flash_message'] = "กรุณากรอกข้อมูลสำคัญให้ครบถ้วน";
        $_SESSION['flash_type'] = "warning";
    } else {
        
        // -------------------------------------------------------------
        // 🛠️ ส่วนที่เพิ่มใหม่: จัดการรูปภาพหลายรูป (ไม่แตะฟังก์ชันเดิมมึง)
        // -------------------------------------------------------------
        $uploadedImages = [];
        $main_image_index = isset($_POST['main_image_index']) ? (int)$_POST['main_image_index'] : 0;

        // เช็คว่ามีการอัปโหลดไฟล์เข้ามาไหม (ใช้แบบ array)
        if (!empty($_FILES['product_images']['name'][0])) {
            $fileCount = count($_FILES['product_images']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($i >= 5) break; // บังคับสูงสุด 5 รูป
                
                // จำลอง $_FILES แบบเดี่ยวเพื่อส่งเข้าฟังก์ชัน uploadImage เดิมของมึง
                $singleFile = [
                    'name'     => $_FILES['product_images']['name'][$i],
                    'type'     => $_FILES['product_images']['type'][$i],
                    'tmp_name' => $_FILES['product_images']['tmp_name'][$i],
                    'error'    => $_FILES['product_images']['error'][$i],
                    'size'     => $_FILES['product_images']['size'][$i]
                ];
                
                $uploadedFile = uploadImage($singleFile); // ฟังก์ชันเดิมมึงเป๊ะๆ ไม่แก้เลย
                if ($uploadedFile) {
                    $uploadedImages[] = $uploadedFile;
                    // ถ้ารูปนี้ตรงกับ Index ที่ติ๊กเลือกว่าเป็น "รูปหลัก" ให้โยนชื่อเข้า $image_url เดิม
                    if ($i === $main_image_index) {
                        $image_url = $uploadedFile;
                    }
                }
            }
            
            // กันเหนียว: ถ้าอัปโหลดสำเร็จแต่ Index ไม่ตรง ให้เอารูปแรกเป็นรูปหลัก
            if (empty($image_url) && !empty($uploadedImages)) {
                $image_url = $uploadedImages[0];
            }
        }

        // -------------------------------------------------------------
        // 🛠️ SQL เดิมของมึง (ไม่ลด ไม่แก้ โค้ดมึงอยู่ครบ 100%)
        // -------------------------------------------------------------
        if ($product_id > 0) {
            // เมื่อแก้ไข: รีเซ็ตสถานะเป็น 'pending' เพื่อให้แอดมินตรวจใหม่
            $sql = "UPDATE products SET title = ?, price = ?, category_id = ?, status = 'pending', product_status = ?, description = ?, image_url = ? 
                    WHERE id = ? AND shop_id = ?";
            $params = [$title, $price, $category_id, $p_status, $description, $image_url, $product_id, $shop_id];
            $success_msg = "แก้ไขข้อมูลสำเร็จ! สินค้าเข้าสู่สถานะรอแอดมินตรวจสอบอีกครั้ง";
            $admin_notify_msg = "🔄 [Admin] มีการแก้ไขข้อมูลสินค้า!\n📦 สินค้า: $title\n🏪 ร้านค้า: $shop_name\n🔗 ตรวจสอบ: https://hosting.bncc.ac.th/s673190104/student_marketplace/admin/manage_products.php";
        } else {
            // เมื่อเพิ่มใหม่: ตั้งค่าเริ่มต้นเป็น 'pending'
            $sql = "INSERT INTO products (title, price, category_id, status, product_status, description, image_url, shop_id) 
                    VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)";
            $params = [$title, $price, $category_id, $p_status, $description, $image_url, $shop_id];
            $success_msg = "ลงขายสินค้าใหม่สำเร็จ! กรุณารอแอดมินอนุมัติก่อนแสดงในตลาด";
            $admin_notify_msg = "🆕 [Admin] มีการลงขายสินค้าใหม่!\n📦 สินค้า: $title\n💰 ราคา: ฿" . number_format($price, 2) . "\n🏪 ร้านค้า: $shop_name\n🔗 ตรวจสอบ: https://hosting.bncc.ac.th/s673190104/student_marketplace/admin/manage_products.php";
        }

        $stmt_save = $db->prepare($sql);
        if ($stmt_save->execute($params)) {
            
            // -------------------------------------------------------------
            // 🛠️ ส่วนที่เพิ่มใหม่: เอาชื่อรูปทั้งหมด 5 รูปไปเก็บลงตาราง product_images
            // -------------------------------------------------------------
            $current_p_id = ($product_id > 0) ? $product_id : $db->lastInsertId();
            if (!empty($uploadedImages)) {
                // ถ้าเป็นโหมดแก้ไข ลบรูปย่อยของเก่าทิ้งก่อนลงใหม่
                if ($product_id > 0) {
                    $db->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$product_id]);
                }
                foreach ($uploadedImages as $idx => $path) {
                    $is_main = ($path === $image_url) ? 1 : 0; // มาร์คว่ารูปไหนคือรูปหลัก
                    $db->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)")
                       ->execute([$current_p_id, $path, $is_main]);
                }
            }

            // 🔔 ส่งแจ้งเตือนหา Admin ทุกคนทันที (ฟังก์ชันเดิมมึง)
            notifyAllAdmins($admin_notify_msg);

            $_SESSION['flash_message'] = $success_msg;
            $_SESSION['flash_type'] = "success";
            redirect('dashboard.php');
        }
    }
}
?>

<style>
    .upload-box {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .upload-box:hover {
        border-color: #6c5ce7 !important; /* เปลี่ยนสีขอบตอน Hover ให้เข้ากับปุ่มม่วงของมึง */
        box-shadow: 0 0 15px rgba(108, 92, 231, 0.2);
        transform: translateY(-2px);
    }
    .thumb-item {
        opacity: 0;
        transform: translateY(10px);
        animation: fadeUp 0.4s ease forwards;
        transition: transform 0.2s;
    }
    .thumb-item:hover {
        transform: scale(1.05);
    }
    #image_preview {
        animation: fadeUp 0.5s ease forwards;
        transition: opacity 0.3s;
    }
    @keyframes fadeUp {
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
</style>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 30px;">
        <a href="dashboard.php" style="color: var(--text-muted); font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> กลับไปยัง Dashboard</a>
        <h1 style="margin-top: 10px;"><?php echo $product ? 'แก้ไขสินค้า' : 'ลงขายสินค้าใหม่'; ?></h1>
    </div>

    <form action="add_product.php<?php echo $product ? '?id='.$product['id'] : ''; ?>" method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px;" class="form-layout">
            
            <div class="upload-section">
                <div class="upload-box" style="background: var(--bg-card); padding: 20px; border-radius: 16px; border: 2px dashed var(--border-color); text-align: center;">
                    <label style="display: block; cursor: pointer; margin-bottom: 0;" title="คลิกเพื่อเพิ่มรูปภาพ (สะสมได้ 5 รูป)">
                        <img id="image_preview" 
                             src="<?php echo ($product && $product['image_url']) ? '../assets/images/products/'.$product['image_url'] : ''; ?>" 
                             style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 12px; display: <?php echo ($product && $product['image_url']) ? 'block' : 'none'; ?>;">
                        
                        <div id="upload_placeholder" style="<?php echo ($product && $product['image_url']) ? 'display:none;' : 'padding: 40px 0;'; ?>">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: var(--border-color); margin-bottom: 10px; transition: color 0.3s;"></i>
                            <p style="color: var(--text-muted); font-size: 0.9rem;">คลิกเพื่อเลือกรูปภาพ<br><small>(คลิกเพิ่มทีละรูป หรือลากคลุมได้สูงสุด 5 รูป)</small></p>
                        </div>
                        
                        <input type="file" name="product_images[]" id="product_image" accept="image/*" multiple style="display: none;">
                    </label>

                    <div id="thumbnails_container" style="display: none; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-top: 15px;">
                        </div>
                    <div id="clear_images_btn" style="display: none; margin-top: 15px; font-size: 0.8rem; color: #ff7675; cursor: pointer; text-decoration: underline;">
                        ล้างรูปภาพทั้งหมด
                    </div>
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

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; margin-top: 10px; background-color: #5f42e4; border: none; border-radius: 8px; font-weight: bold; transition: background 0.3s;">
                    <i class="fas fa-save"></i> <?php echo $product ? 'บันทึกการแก้ไข (ส่งตรวจใหม่)' : 'ลงขายสินค้า (รออนุมัติ)'; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// ใช้ DataTransfer เพื่อสะสมไฟล์ ไม่ให้มันรีเซ็ตเวลากดเลือกรูปใหม่
let accumulatedFiles = new DataTransfer(); 
const fileInput = document.getElementById('product_image');
const preview = document.getElementById('image_preview');
const placeholder = document.getElementById('upload_placeholder');
const thumbsContainer = document.getElementById('thumbnails_container');
const clearBtn = document.getElementById('clear_images_btn');

fileInput.addEventListener('change', function() {
    const newFiles = this.files;
    
    // วนลูปเก็บไฟล์ที่เพิ่งเลือกเข้ามาเพิ่ม (สูงสุด 5 รูป)
    for(let i = 0; i < newFiles.length; i++) {
        if(accumulatedFiles.files.length < 5) {
            accumulatedFiles.items.add(newFiles[i]);
        } else {
            alert("เพิ่มได้สูงสุด 5 รูปเท่านั้นครับ!");
            break;
        }
    }
    
    // อัปเดต input ให้อมไฟล์ทั้งหมดที่เราสะสมไว้ (ส่งไป PHP ครบแน่นอน)
    fileInput.files = accumulatedFiles.files;
    renderUI();
});

// ฟังก์ชันสำหรับเคลียร์รูปเผื่อผู้ใช้เลือกผิด
clearBtn.addEventListener('click', function() {
    accumulatedFiles = new DataTransfer();
    fileInput.files = accumulatedFiles.files;
    renderUI();
});

function renderUI() {
    if (fileInput.files.length > 0) {
        placeholder.style.display = 'none';
        preview.style.display = 'block';
        thumbsContainer.style.display = 'grid';
        clearBtn.style.display = 'block';
        thumbsContainer.innerHTML = ''; // ล้าง UI เดิมทิ้งเพื่อวาดใหม่
        
        // รูปแรกเป็นหน้าปก Default และเล่น Animation กระพริบเบาๆ
        preview.style.opacity = '0';
        setTimeout(() => {
            preview.src = URL.createObjectURL(fileInput.files[0]);
            preview.style.opacity = '1';
        }, 100);
        
        // วาดรูปย่อยพร้อม Animation Staggering (เด้งขึ้นมาทีละอัน)
        for(let i = 0; i < fileInput.files.length; i++) {
            const objectUrl = URL.createObjectURL(fileInput.files[i]);
            
            const thumbDiv = document.createElement('div');
            thumbDiv.className = 'thumb-item'; // ดึง CSS Animation มาใช้
            thumbDiv.style.animationDelay = (i * 0.1) + 's'; // หน่วงเวลาให้โผล่ทีละอัน
            thumbDiv.style.textAlign = 'center';
            
            const img = document.createElement('img');
            img.src = objectUrl;
            img.style.width = '100%';
            img.style.aspectRatio = '1';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '6px';
            img.style.border = (i === 0) ? '2px solid #5f42e4' : '2px solid transparent';
            img.style.cursor = 'pointer';
            img.style.transition = 'all 0.3s ease';
            
            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'main_image_index';
            radio.value = i;
            if(i === 0) radio.checked = true;
            radio.style.display = 'inline-block';
            radio.style.marginTop = '4px';

            // กดที่รูปย่อยเพื่อพรีวิวและเลือกเป็นรูปหลัก
            img.onclick = () => {
                preview.style.opacity = '0.5';
                setTimeout(() => {
                    preview.src = objectUrl;
                    preview.style.opacity = '1';
                }, 150);
                
                radio.checked = true;
                // อัปเดตกรอบสีม่วง
                thumbsContainer.querySelectorAll('img').forEach(el => el.style.border = '2px solid transparent');
                img.style.border = '2px solid #5f42e4';
            };

            thumbDiv.appendChild(img);
            thumbDiv.appendChild(radio);
            thumbsContainer.appendChild(thumbDiv);
        }
    } else {
        // กรณีลบรูปหมด
        placeholder.style.display = 'block';
        preview.style.display = 'none';
        thumbsContainer.style.display = 'none';
        clearBtn.style.display = 'none';
        thumbsContainer.innerHTML = '';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>