<?php
/**
 * Student Marketplace - Add/Edit Product (Approval System Version)
 */
$pageTitle = "จัดการข้อมูลสินค้า";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// 1. ตรวจสอบว่าล็อกอินหรือไม่
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// 2. 🛡️ [ดักสิทธิ์] ตรวจสอบสถานะร้านค้าของ User นี้
$shop_stmt = $db->prepare("SELECT id, shop_name, status FROM shops WHERE user_id = ?");
$shop_stmt->execute([$user_id]);
$shop_data = $shop_stmt->fetch();

// 3. เช็กเงื่อนไขการอนุมัติร้านค้า
if (!$shop_data) {
    $_SESSION['flash_message'] = "กรุณาสร้างโปรไฟล์ร้านค้าก่อนลงขายสินค้า";
    $_SESSION['flash_type'] = "warning";
    redirect('create_shop.php'); // แก้ชื่อไฟล์ให้ตรงกับหน้าสร้างร้านของคุณ
} 
elseif ($shop_data['status'] === 'pending') {
    $_SESSION['flash_message'] = "⏳ ไม่สามารถลงสินค้าได้! ร้านค้าของคุณกำลังรอการตรวจสอบจากแอดมิน";
    $_SESSION['flash_type'] = "warning";
    redirect('dashboard.php');
} 
elseif ($shop_data['status'] !== 'approved') {
    $_SESSION['flash_message'] = "🚫 ร้านค้าของคุณถูกระงับหรือไม่อนุมัติให้ขายสินค้า";
    $_SESSION['flash_type'] = "danger";
    redirect('dashboard.php');
}

// ✅ ถ้าผ่านลงมาได้ แปลว่า status == 'approved' แล้ว
// สามารถตรวจสอบ role ต่อเพื่อความปลอดภัย
checkRole('seller');

$shop_id = $shop_data['id'];
$shop_name = $shop_data['shop_name'] ?? 'ร้านค้าไม่ทราบชื่อ';

// 4. ตรวจสอบโหมด: แก้ไข (Edit) หรือ เพิ่มใหม่ (Add)
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$existing_images = []; 

if ($product_id > 0) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND shop_id = ?");
    $stmt->execute([$product_id, $shop_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $_SESSION['flash_message'] = "ไม่พบสินค้า หรือคุณไม่มีสิทธิ์เข้าถึง";
        $_SESSION['flash_type'] = "danger";
        redirect('dashboard.php');
    }

    $img_stmt = $db->prepare("SELECT image_path, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
    $img_stmt->execute([$product_id]);
    $existing_images = $img_stmt->fetchAll(PDO::FETCH_ASSOC);

    if(count($existing_images) === 0 && !empty($product['image_url'])) {
        $existing_images[] = ['image_path' => $product['image_url'], 'is_main' => 1];
    }
}

$cat_stmt = $db->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $cat_stmt->fetchAll();

// 5. จัดการการส่งข้อมูล (Form Submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $_SESSION['flash_message'] = "❌ ขนาดไฟล์รวมกันใหญ่เกินกว่าที่ระบบจะรับได้ กรุณาลองใหม่อีกครั้ง";
        $_SESSION['flash_type'] = "danger";
        redirect('add_product.php' . ($product_id > 0 ? '?id='.$product_id : ''));
        exit;
    }

    $title       = trim($_POST['title'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $p_status    = $_POST['product_status'] ?? 'in-stock'; 
    $description = trim($_POST['description'] ?? '');
    $image_url   = $product['image_url'] ?? ''; 

    if (empty($title) || $price <= 0 || empty($category_id)) {
        $_SESSION['flash_message'] = "กรุณากรอกข้อมูลสำคัญให้ครบถ้วน";
        $_SESSION['flash_type'] = "warning";
    } else {
        
        $uploadedImages = [];
        $main_image_index = isset($_POST['main_image_index']) ? (int)$_POST['main_image_index'] : 0;
        
        $keptOldImages = $_POST['kept_old_images'] ?? [];

        foreach($keptOldImages as $oldImg) {
            $uploadedImages[] = $oldImg;
        }

        if (!empty($_FILES['product_images']['name'][0])) {
            $fileCount = count($_FILES['product_images']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if (count($uploadedImages) >= 5) break; 
                
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
                }
            }
        }

        if (!empty($uploadedImages) && isset($uploadedImages[$main_image_index])) {
            $image_url = $uploadedImages[$main_image_index];
        } else if (!empty($uploadedImages)) {
            $image_url = $uploadedImages[0];
        }

        if ($product_id > 0) {
            $sql = "UPDATE products SET title = ?, price = ?, category_id = ?, status = 'pending', product_status = ?, description = ?, image_url = ? 
                    WHERE id = ? AND shop_id = ?";
            $params = [$title, $price, $category_id, $p_status, $description, $image_url, $product_id, $shop_id];
            $success_msg = "แก้ไขข้อมูลสำเร็จ! สินค้าเข้าสู่สถานะรอแอดมินตรวจสอบอีกครั้ง";
            $admin_notify_msg = "🔄 [Admin] มีการแก้ไขข้อมูลสินค้า!\n📦 สินค้า: $title\n🏪 ร้านค้า: $shop_name\n🔗 ตรวจสอบ: http://localhost/student_marketplace/admin/approve_product.php";
        } else {
            $sql = "INSERT INTO products (title, price, category_id, status, product_status, description, image_url, shop_id) 
                    VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)";
            $params = [$title, $price, $category_id, $p_status, $description, $image_url, $shop_id];
            $success_msg = "ลงขายสินค้าใหม่สำเร็จ! กรุณารอแอดมินอนุมัติก่อนแสดงในตลาด";
            $admin_notify_msg = "🆕 [Admin] มีการลงขายสินค้าใหม่!\n📦 สินค้า: $title\n💰 ราคา: ฿" . number_format($price, 2) . "\n🏪 ร้านค้า: $shop_name\n🔗 ตรวจสอบ: http://localhost/student_marketplace/admin/approve_product.php";
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

            // 🎯 [NEW] ส่งแจ้งเตือนทั้งผ่าน LINE และหน้าเว็บให้แอดมินทุกคน
            if (function_exists('notifyAllAdmins')) {
                notifyAllAdmins($admin_notify_msg);
            }

            $adminStmt = $db->query("SELECT id FROM users WHERE role IN ('admin', 'teacher')");
            $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $adm_id) {
                if (function_exists('sendNotification')) {
                    sendNotification($adm_id, 'system', "รออนุมัติสินค้า: " . $title, "../admin/approve_product.php");
                }
            }

            $_SESSION['flash_message'] = $success_msg;
            $_SESSION['flash_type'] = "success";
            redirect('dashboard.php');
        }
    }
}
?>

<style>
    .upload-box { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; }
    .upload-box:hover { border-color: #6c5ce7 !important; box-shadow: 0 0 20px rgba(108, 92, 231, 0.25); transform: translateY(-3px); }
    #thumbnails_container { display: none; flex-wrap: wrap; gap: 15px; margin-top: 20px; justify-content: center; }
    @keyframes popIn { 0% { transform: scale(0.5); opacity: 0; } 70% { transform: scale(1.1); opacity: 1; } 100% { transform: scale(1); opacity: 1; } }
    @keyframes shrinkOut { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(0); opacity: 0; } }
    .thumb-item { position: relative; width: 90px; height: 90px; opacity: 0; animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .thumb-item:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.3); }
    .thumb-img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid transparent; transition: all 0.3s ease; }
    .thumb-item:hover .thumb-img { opacity: 0.8; }
    .remove-btn { position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; width: 24px; height: 24px; border-radius: 50%; font-size: 14px; font-weight: bold; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.4); z-index: 10; transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    .remove-btn:hover { transform: scale(1.2) rotate(90deg); background: #ff6b81; }
    .cover-badge { position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(6, 199, 85, 0.95); color: white; font-size: 0.75rem; font-weight: bold; text-align: center; padding: 4px 0; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; pointer-events: none; opacity: 0; transform: translateY(10px); transition: all 0.3s ease; }
    .thumb-item.is-cover .cover-badge { opacity: 1; transform: translateY(0); }
    .thumb-item.is-cover .thumb-img { border-color: #06C755; }
    #image_preview { transition: opacity 0.3s ease, transform 0.3s ease; }
    .preview-animating { opacity: 0.3 !important; transform: scale(0.98); }
    .custom-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; z-index: 9999; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
    .custom-modal-overlay.show { opacity: 1; visibility: visible; }
    .custom-modal-content { background: var(--bg-card, #1e1e2f); color: white; padding: 30px; border-radius: 16px; text-align: center; width: 90%; max-width: 400px; transform: scale(0.8) translateY(20px); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 15px 40px rgba(0,0,0,0.6); border: 1px solid var(--border-color, #333); }
    .custom-modal-overlay.show .custom-modal-content { transform: scale(1) translateY(0); }
    .custom-modal-icon { font-size: 3.5rem; color: #ff4757; margin-bottom: 15px; animation: popIn 0.5s ease 0.2s both; }
    .custom-modal-btn { background: #5f42e4; color: white; border: none; padding: 12px 35px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 25px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(95, 66, 228, 0.4); }
    .custom-modal-btn:hover { background: #4a33b8; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(95, 66, 228, 0.6); }
</style>

<div class="custom-modal-overlay" id="customAlertModal">
    <div class="custom-modal-content">
        <div class="custom-modal-icon"><i class="fas fa-exclamation-circle"></i></div>
        <h3 style="margin-bottom: 10px; font-weight: bold;">แจ้งเตือน</h3>
        <p id="customAlertMessage" style="color: var(--text-muted, #ccc); margin-bottom: 0; line-height: 1.5;">ข้อความ</p>
        <button class="custom-modal-btn" onclick="closeCustomAlert()">ตกลง</button>
    </div>
</div>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 30px;">
        <a href="dashboard.php" style="color: var(--text-muted); font-size: 0.9rem; transition: color 0.3s;"><i class="fas fa-arrow-left"></i> กลับไปยัง Dashboard</a>
        <h1 style="margin-top: 10px;"><?php echo $product ? 'แก้ไขสินค้า' : 'ลงขายสินค้าใหม่'; ?></h1>
    </div>

    <form id="productForm" action="add_product.php<?php echo $product ? '?id='.$product['id'] : ''; ?>" method="POST" enctype="multipart/form-data">
        
        <div id="hidden_old_images_container"></div>

        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px;" class="form-layout">
            
            <div class="upload-section">
                <div class="upload-box" style="background: var(--bg-card); padding: 20px; border-radius: 16px; border: 2px dashed var(--border-color); text-align: center;">
                    
                    <label style="display: block; cursor: pointer; margin-bottom: 0;" title="คลิกเพื่อเพิ่มรูปภาพ (สะสมได้ 5 รูป)">
                        <img id="image_preview" 
                             src="" 
                             style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 12px; display: none;">
                        
                        <div id="upload_placeholder" style="padding: 40px 0;">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3.5rem; color: var(--border-color); margin-bottom: 10px; transition: color 0.3s;"></i>
                            <p style="color: var(--text-muted); font-size: 0.95rem;">คลิกเพื่อเพิ่มรูปภาพ<br><small style="opacity: 0.7;">(คลิกซ้ำเพื่อเพิ่ม หรือลากคลุมได้สูงสุด 5 รูป)</small></p>
                        </div>
                        
                        <input type="file" id="product_image" accept="image/*" multiple style="display: none;">
                    </label>

                    <div id="thumbnails_container"></div>
                    
                </div>
                <div style="text-align: center; margin-top: 12px;">
                    <small style="color: var(--text-muted); font-size: 0.85rem;"><i class="fas fa-lightbulb" style="color: #f1c40f;"></i> <b>Tips:</b> คลิกที่รูปเล็กด้านล่างเพื่อเลือกเป็น <b>"รูปหน้าปก"</b></small>
                </div>
            </div>

            <div class="info-section" style="background: var(--bg-card); padding: 30px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="form-group">
                    <label>ชื่อสินค้า <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?php echo e($product['title'] ?? ''); ?>" required style="transition: border-color 0.3s;">
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

                <button id="submit_btn" type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; margin-top: 15px; background-color: #5f42e4; border: none; border-radius: 8px; font-weight: bold; font-size: 1.05rem; letter-spacing: 0.5px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(95, 66, 228, 0.3);">
                    <i class="fas fa-save" style="margin-right: 5px;"></i> <?php echo $product ? 'บันทึกการแก้ไข (ส่งตรวจใหม่)' : 'ลงขายสินค้า (รออนุมัติ)'; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let mixedGallery = []; 
let currentMainIndex = 0; 

const fileInput = document.getElementById('product_image');
const preview = document.getElementById('image_preview');
const placeholder = document.getElementById('upload_placeholder');
const thumbsContainer = document.getElementById('thumbnails_container');
const submitBtn = document.getElementById('submit_btn');
const productForm = document.getElementById('productForm');

const existingImagesFromDB = <?php echo json_encode($existing_images); ?>;

if (existingImagesFromDB.length > 0) {
    existingImagesFromDB.forEach((img, index) => {
        mixedGallery.push({
            type: 'old',
            url: '../assets/images/products/' + img.image_path,
            filename: img.image_path 
        });
        if (img.is_main == 1) {
            currentMainIndex = index;
        }
    });
    renderUI();
}

function showCustomAlert(msg) {
    document.getElementById('customAlertMessage').innerText = msg;
    document.getElementById('customAlertModal').classList.add('show');
}
function closeCustomAlert() {
    document.getElementById('customAlertModal').classList.remove('show');
}

function compressImage(file, maxWidth = 1000, quality = 0.8) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = event => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                canvas.toBlob((blob) => {
                    if(!blob) return reject('Compression failed');
                    const newFile = new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() });
                    resolve(newFile);
                }, 'image/jpeg', quality);
            };
            img.onerror = error => reject(error);
        };
        reader.onerror = error => reject(error);
    });
}

fileInput.addEventListener('change', async function() {
    const newFiles = this.files;
    if (newFiles.length === 0) return;

    let limitReached = false;
    const pTag = placeholder.querySelector('p');
    const originalPlaceholderHtml = pTag.innerHTML;
    
    pTag.innerHTML = "<i class='fas fa-spinner fa-spin' style='margin-right: 5px;'></i> กำลังบีบอัดและประมวลผลรูปภาพ...";
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.5';
    
    for(let i = 0; i < newFiles.length; i++) {
        if(mixedGallery.length < 5) {
            try {
                const compressedFile = await compressImage(newFiles[i], 1000, 0.8);
                mixedGallery.push({
                    type: 'new',
                    file: compressedFile,
                    url: URL.createObjectURL(compressedFile)
                });
            } catch(e) {
                console.error("บีบอัดล้มเหลว", e);
                mixedGallery.push({
                    type: 'new',
                    file: newFiles[i],
                    url: URL.createObjectURL(newFiles[i])
                });
            }
        } else {
            limitReached = true;
            break;
        }
    }
    
    if (limitReached) {
        showCustomAlert("คุณสามารถรวมรูปภาพได้สูงสุด 5 รูปเท่านั้นครับ!");
    }
    
    fileInput.value = ''; 
    
    pTag.innerHTML = originalPlaceholderHtml;
    submitBtn.disabled = false;
    submitBtn.style.opacity = '1';
    renderUI();
});

function updatePreviewImage(url) {
    preview.classList.add('preview-animating');
    setTimeout(() => {
        preview.src = url;
        preview.classList.remove('preview-animating');
    }, 150); 
}

function renderUI() {
    if (mixedGallery.length > 0) {
        placeholder.style.display = 'none';
        preview.style.display = 'block';
        thumbsContainer.style.display = 'flex';
        thumbsContainer.innerHTML = ''; 
        
        if(currentMainIndex >= mixedGallery.length) {
            currentMainIndex = 0;
        }

        updatePreviewImage(mixedGallery[currentMainIndex].url);
        
        mixedGallery.forEach((item, i) => {
            const thumbDiv = document.createElement('div');
            thumbDiv.className = `thumb-item ${i === currentMainIndex ? 'is-cover' : ''}`;
            thumbDiv.style.animationDelay = (i * 0.08) + 's'; 
            
            const removeBtn = document.createElement('div');
            removeBtn.className = 'remove-btn';
            removeBtn.innerHTML = '<i class="fas fa-times" style="font-size:12px;"></i>';
            removeBtn.title = 'ลบรูปภาพนี้';
            removeBtn.onclick = (e) => {
                e.stopPropagation(); 
                e.preventDefault();
                
                thumbDiv.style.animation = 'shrinkOut 0.3s ease forwards';
                
                setTimeout(() => {
                    mixedGallery.splice(i, 1);
                    if(i === currentMainIndex) currentMainIndex = 0;
                    else if(i < currentMainIndex) currentMainIndex--;
                    renderUI();
                }, 300); 
            };

            const img = document.createElement('img');
            img.className = 'thumb-img';
            img.src = item.url;
            
            const badge = document.createElement('div');
            badge.className = 'cover-badge';
            badge.innerHTML = '<i class="fas fa-star" style="font-size: 0.6rem;"></i> หน้าปก';

            img.onclick = (e) => {
                e.preventDefault();
                if (currentMainIndex !== i) {
                    currentMainIndex = i; 
                    updatePreviewImage(item.url);
                    renderUI(); 
                }
            };

            thumbDiv.appendChild(removeBtn);
            thumbDiv.appendChild(img);
            thumbDiv.appendChild(badge);
            thumbsContainer.appendChild(thumbDiv);
        });
    } else {
        placeholder.style.display = 'block';
        preview.style.display = 'none';
        thumbsContainer.style.display = 'none';
        thumbsContainer.innerHTML = '';
        currentMainIndex = 0;
    }
}

productForm.addEventListener('submit', function(e) {
    const dt = new DataTransfer();
    mixedGallery.forEach(item => {
        if (item.type === 'new') {
            dt.items.add(item.file);
        }
    });
    
    const newFileInput = document.createElement('input');
    newFileInput.type = 'file';
    newFileInput.name = 'product_images[]';
    newFileInput.multiple = true;
    newFileInput.files = dt.files;
    newFileInput.style.display = 'none';
    productForm.appendChild(newFileInput);

    const hiddenContainer = document.getElementById('hidden_old_images_container');
    hiddenContainer.innerHTML = '';
    mixedGallery.forEach(item => {
        if (item.type === 'old') {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'kept_old_images[]';
            hidden.value = item.filename;
            hiddenContainer.appendChild(hidden);
        }
    });

    const mainIndexHidden = document.createElement('input');
    mainIndexHidden.type = 'hidden';
    mainIndexHidden.name = 'main_image_index';
    mainIndexHidden.value = currentMainIndex;
    hiddenContainer.appendChild(mainIndexHidden);
});

</script>

<?php require_once '../includes/footer.php'; ?>