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
    $p_status    = $_POST['product_status']; 
    $description = trim($_POST['description']);
    $image_url   = $product['image_url'] ?? '';

    if (empty($title) || $price <= 0 || empty($category_id)) {
        $_SESSION['flash_message'] = "กรุณากรอกข้อมูลสำคัญให้ครบถ้วน";
        $_SESSION['flash_type'] = "warning";
    } else {
        
        $uploadedImages = [];
        $main_image_index = isset($_POST['main_image_index']) ? (int)$_POST['main_image_index'] : 0;

        if (!empty($_FILES['product_images']['name'][0])) {
            $fileCount = count($_FILES['product_images']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($i >= 5) break; 
                
                $singleFile = [
                    'name'     => $_FILES['product_images']['name'][$i],
                    'type'     => $_FILES['product_images']['type'][$i],
                    'tmp_name' => $_FILES['product_images']['tmp_name'][$i],
                    'error'    => $_FILES['product_images']['error'][$i],
                    'size'     => $_FILES['product_images']['size'][$i]
                ];
                
                $uploadedFile = uploadImage($singleFile); 
                if ($uploadedFile) {
                    $uploadedImages[] = $uploadedFile;
                    if ($i === $main_image_index) {
                        $image_url = $uploadedFile;
                    }
                }
            }
            
            if (empty($image_url) && !empty($uploadedImages)) {
                $image_url = $uploadedImages[0];
            }
        }

        if ($product_id > 0) {
            $sql = "UPDATE products SET title = ?, price = ?, category_id = ?, status = 'pending', product_status = ?, description = ?, image_url = ? 
                    WHERE id = ? AND shop_id = ?";
            $params = [$title, $price, $category_id, $p_status, $description, $image_url, $product_id, $shop_id];
            $success_msg = "แก้ไขข้อมูลสำเร็จ! สินค้าเข้าสู่สถานะรอแอดมินตรวจสอบอีกครั้ง";
            $admin_notify_msg = "🔄 [Admin] มีการแก้ไขข้อมูลสินค้า!\n📦 สินค้า: $title\n🏪 ร้านค้า: $shop_name\n🔗 ตรวจสอบ: https://hosting.bncc.ac.th/s673190104/student_marketplace/admin/manage_products.php";
        } else {
            $sql = "INSERT INTO products (title, price, category_id, status, product_status, description, image_url, shop_id) 
                    VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)";
            $params = [$title, $price, $category_id, $p_status, $description, $image_url, $shop_id];
            $success_msg = "ลงขายสินค้าใหม่สำเร็จ! กรุณารอแอดมินอนุมัติก่อนแสดงในตลาด";
            $admin_notify_msg = "🆕 [Admin] มีการลงขายสินค้าใหม่!\n📦 สินค้า: $title\n💰 ราคา: ฿" . number_format($price, 2) . "\n🏪 ร้านค้า: $shop_name\n🔗 ตรวจสอบ: https://hosting.bncc.ac.th/s673190104/student_marketplace/admin/manage_products.php";
        }

        $stmt_save = $db->prepare($sql);
        if ($stmt_save->execute($params)) {
            $current_p_id = ($product_id > 0) ? $product_id : $db->lastInsertId();
            if (!empty($uploadedImages)) {
                if ($product_id > 0) {
                    $db->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$product_id]);
                }
                foreach ($uploadedImages as $idx => $path) {
                    $is_main = ($path === $image_url) ? 1 : 0; 
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

<style>
    /* 🎨 CSS แก้มือ ปรับขนาดให้สวยหรูดูแพง */
    .upload-box {
        transition: all 0.3s ease;
        position: relative;
    }
    
    #thumbnails_container {
        display: none;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 20px;
        justify-content: center;
    }

    /* ขยายขนาดรูป Thumbnail ให้ดูรู้เรื่อง ไม่เล็กเกินไป */
    .thumb-item {
        position: relative;
        width: 90px; 
        height: 90px;
        opacity: 0;
        transform: translateY(10px);
        animation: fadeUp 0.3s ease forwards;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s ease;
    }
    
    .thumb-item:hover .thumb-img {
        opacity: 0.8;
    }

    /* ปุ่ม X แบบมินิมอล ไม่บังรูป */
    .remove-btn {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ff4757;
        color: white;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.4);
        z-index: 10;
        transition: transform 0.2s, background 0.2s;
    }
    .remove-btn:hover {
        transform: scale(1.15);
        background: #ff6b81;
    }

    /* ป้าย "หน้าปก" คาดล่างสวยๆ ไม่กวนสายตา */
    .cover-badge {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background: rgba(6, 199, 85, 0.9);
        color: white;
        font-size: 0.75rem;
        font-weight: bold;
        text-align: center;
        padding: 4px 0;
        border-bottom-left-radius: 6px;
        border-bottom-right-radius: 6px;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .thumb-item.is-cover .cover-badge {
        opacity: 1;
    }
    .thumb-item.is-cover .thumb-img {
        border-color: #06C755;
    }

    #image_preview {
        animation: fadeUp 0.5s ease forwards;
    }
    @keyframes fadeUp {
        to { opacity: 1; transform: translateY(0); }
    }

    /* 🌟 CSS สำหรับ Custom Popup แทนที่ Alert โง่ๆ */
    .custom-modal-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    .custom-modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }
    .custom-modal-content {
        background: var(--bg-card, #1e1e2f); /* ใช้สีโทนดาร์กตามเว็บมึง */
        color: white;
        padding: 30px;
        border-radius: 16px;
        text-align: center;
        width: 90%;
        max-width: 400px;
        transform: scale(0.8);
        transition: all 0.3s ease;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        border: 1px solid var(--border-color, #333);
    }
    .custom-modal-overlay.show .custom-modal-content {
        transform: scale(1);
    }
    .custom-modal-icon {
        font-size: 3rem;
        color: #ff4757;
        margin-bottom: 15px;
    }
    .custom-modal-btn {
        background: #5f42e4;
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 20px;
        transition: background 0.3s;
    }
    .custom-modal-btn:hover {
        background: #4a33b8;
    }
</style>

<div class="custom-modal-overlay" id="customAlertModal">
    <div class="custom-modal-content">
        <div class="custom-modal-icon"><i class="fas fa-exclamation-circle"></i></div>
        <h3 style="margin-bottom: 10px;">แจ้งเตือน</h3>
        <p id="customAlertMessage" style="color: var(--text-muted, #ccc); margin-bottom: 0;">ข้อความ</p>
        <button class="custom-modal-btn" onclick="closeCustomAlert()">ตกลง</button>
    </div>
</div>

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
                            <p style="color: var(--text-muted); font-size: 0.9rem;">คลิกเพื่อเพิ่มรูปภาพ<br><small>(คลิกซ้ำเพื่อเพิ่ม หรือลากคลุมได้สูงสุด 5 รูป)</small></p>
                        </div>
                        
                        <input type="file" name="product_images[]" id="product_image" accept="image/*" multiple style="display: none;">
                    </label>

                    <div id="thumbnails_container"></div>
                    
                </div>
                <div style="text-align: center; margin-top: 10px;">
                    <small style="color: var(--text-muted);">💡 <b>Tips:</b> คลิกที่รูปเล็กด้านล่างเพื่อเลือกเป็น <b>"รูปหน้าปก"</b></small>
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
let accumulatedFiles = new DataTransfer(); 
let currentMainIndex = 0; 

const fileInput = document.getElementById('product_image');
const preview = document.getElementById('image_preview');
const placeholder = document.getElementById('upload_placeholder');
const thumbsContainer = document.getElementById('thumbnails_container');

// 🌟 ฟังก์ชันเรียกใช้ Custom Alert แทน alert() ของบราวเซอร์
function showCustomAlert(msg) {
    document.getElementById('customAlertMessage').innerText = msg;
    document.getElementById('customAlertModal').classList.add('show');
}
function closeCustomAlert() {
    document.getElementById('customAlertModal').classList.remove('show');
}

fileInput.addEventListener('change', function() {
    const newFiles = this.files;
    let limitReached = false;
    
    for(let i = 0; i < newFiles.length; i++) {
        if(accumulatedFiles.files.length < 5) {
            accumulatedFiles.items.add(newFiles[i]);
        } else {
            limitReached = true;
            break;
        }
    }
    
    if (limitReached) {
        showCustomAlert("คุณสามารถเพิ่มรูปภาพได้สูงสุดเพียง 5 รูปเท่านั้นครับ!");
    }
    
    fileInput.files = accumulatedFiles.files;
    
    if(currentMainIndex >= fileInput.files.length) {
        currentMainIndex = 0;
    }
    
    renderUI();
});

function renderUI() {
    if (fileInput.files.length > 0) {
        placeholder.style.display = 'none';
        preview.style.display = 'block';
        thumbsContainer.style.display = 'flex';
        thumbsContainer.innerHTML = ''; 
        
        preview.style.opacity = '0';
        setTimeout(() => {
            preview.src = URL.createObjectURL(fileInput.files[currentMainIndex]);
            preview.style.opacity = '1';
        }, 100);
        
        for(let i = 0; i < fileInput.files.length; i++) {
            const objectUrl = URL.createObjectURL(fileInput.files[i]);
            
            const thumbDiv = document.createElement('div');
            thumbDiv.className = `thumb-item ${i === currentMainIndex ? 'is-cover' : ''}`;
            thumbDiv.style.animationDelay = (i * 0.05) + 's'; 
            
            // ปุ่ม X ลบรูป
            const removeBtn = document.createElement('div');
            removeBtn.className = 'remove-btn';
            removeBtn.innerHTML = '×';
            removeBtn.title = 'ลบรูปภาพนี้';
            removeBtn.onclick = (e) => {
                e.stopPropagation(); 
                e.preventDefault();
                
                const dt = new DataTransfer();
                for(let j = 0; j < fileInput.files.length; j++) {
                    if(j !== i) dt.items.add(fileInput.files[j]);
                }
                accumulatedFiles = dt;
                fileInput.files = accumulatedFiles.files;
                
                if(i === currentMainIndex) currentMainIndex = 0;
                else if(i < currentMainIndex) currentMainIndex--;

                renderUI();
            };

            // ตัวรูป
            const img = document.createElement('img');
            img.className = 'thumb-img';
            img.src = objectUrl;
            
            // ป้าย "หน้าปก" แบบแถบคาดล่าง (ไม่บังรูปกลางจอแล้ว)
            const badge = document.createElement('div');
            badge.className = 'cover-badge';
            badge.innerHTML = '<i class="fas fa-star" style="font-size: 0.6rem;"></i> หน้าปก';

            // Radio ซ่อนส่งค่าไป Backend
            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'main_image_index';
            radio.value = i;
            radio.style.display = 'none';
            if(i === currentMainIndex) radio.checked = true;

            // กดที่รูประบุหน้าปก
            img.onclick = (e) => {
                e.preventDefault();
                currentMainIndex = i; 
                renderUI(); 
            };

            thumbDiv.appendChild(removeBtn);
            thumbDiv.appendChild(img);
            thumbDiv.appendChild(badge);
            thumbDiv.appendChild(radio);
            thumbsContainer.appendChild(thumbDiv);
        }
    } else {
        placeholder.style.display = 'block';
        preview.style.display = 'none';
        thumbsContainer.style.display = 'none';
        thumbsContainer.innerHTML = '';
        currentMainIndex = 0;
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>