<?php
/**
 * BNCC Market - Master Profile Dashboard (Full Version)
 * - แก้ไขปัญหาสี Navbar เพี้ยนด้วยระบบ Force CSS Variables
 * - บูรณาการระบบ Wishlist เข้ากับ Sidebar
 * - ระบบอัปโหลดรูปโปรไฟล์พร้อมการตรวจสอบความปลอดภัย
 * - บูรณาการ LINE Login (OAuth 2.0) เพื่อรับแจ้งเตือนอัตโนมัติ
 */

// 🚀 1. โหลด Functions มาก่อนเสมอ! (ห้ามมี HTML หลุดออกมาก่อนหน้านี้เด็ดขาด)
require_once '../includes/functions.php';

// 2. ตรวจสอบสิทธิ์การเข้าถึง (Security Gate)
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// 3. ดึงข้อมูลผู้ใช้จากฐานข้อมูล student_market_db (รวมถึงคอลัมน์ line_user_id สำหรับ Admin/Teacher)
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 🛠️ ดึงข้อมูลร้านค้าเพื่อเช็คสถานะ LINE ของคนขาย
$shop_stmt = $db->prepare("SELECT line_user_id FROM shops WHERE user_id = ?");
$shop_stmt->execute([$user_id]);
$shop_info = $shop_stmt->fetch();

// 4. ดึงจำนวนรายการที่ถูกใจ (Wishlist Count)
$wish_stmt = $db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$wish_stmt->execute([$user_id]);
$wish_count = $wish_stmt->fetchColumn();

// --- 🛑 5. **จุดสำคัญ!** ประมวลผลการบันทึกข้อมูล (POST) ตรงนี้ก่อนเลย ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $bio = trim($_POST['bio']);
    $profile_img = $user['profile_img'];

   // จัดการอัปโหลดรูปภาพใหม่
    if (!empty($_FILES['avatar']['name'])) {
        // 🎯 ชี้ไปที่โฟลเดอร์ที่มึงมีอยู่แล้วเป๊ะๆ
        $target_dir = "../assets/images/profiles/"; 
        
        $file_ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        $new_file_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_ext, $allowed)) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $profile_img = $new_file_name;
                $_SESSION['profile_img'] = $new_file_name; 
            }
        }
    }

    $update = $db->prepare("UPDATE users SET fullname = ?, phone = ?, bio = ?, profile_img = ? WHERE id = ?");
    if ($update->execute([$fullname, $phone, $bio, $profile_img, $user_id])) {
        $_SESSION['fullname'] = $fullname;
        $_SESSION['flash_message'] = "บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว!";
        $_SESSION['flash_type'] = "success";
        
        // 🛠️ เปลี่ยนมาใช้ redirect() ที่เราสร้างไว้ใน functions.php แทน header()
        redirect("profile.php");
    }
}

// 🟩 6. เมื่อคำนวณและเช็ก POST เสร็จแล้ว ค่อยโหลด Header (UI) ขึ้นมา
$pageTitle = "โปรไฟล์ของฉัน - BNCC Market";
require_once '../includes/header.php';
?>

<style>
    :root {
        --bg-body: #f1f5f9;
        --bg-card-rgb: 255, 255, 255;
    }
    .dark-theme {
        --bg-body: #020617;
        --bg-card-rgb: 15, 23, 42;
    }

    body {
        background-color: var(--bg-body) !important;
        margin: 0;
        padding: 0;
    }

    .profile-page-wrapper {
        padding: 30px 0 60px 0;
        min-height: 100vh;
    }

    .profile-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        box-shadow: var(--shadow-lg);
        overflow: hidden;
    }

    .input-modern {
        width: 100%;
        padding: 14px 20px;
        border-radius: 14px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-main);
        transition: all 0.3s ease;
        font-family: 'Prompt', sans-serif;
    }

    .input-modern:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        outline: none;
    }

    .btn-camera-overlay {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: var(--primary);
        color: #fff;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 4px solid var(--bg-card);
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-camera-overlay:hover { transform: scale(1.15); }

    .badge-role.admin { background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.2); }
    .badge-role.seller { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
    /* 👑 🛠️ เพิ่มสี Badge สำหรับยศครู (Red Version) */
    .badge-role.teacher { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

    .sidebar-menu-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 18px;
        color: var(--text-muted);
        text-decoration: none;
        border-radius: 12px;
        transition: 0.3s;
        margin-bottom: 5px;
    }
    .sidebar-menu-item:hover, .sidebar-menu-item.active {
        background: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
    }

    .btn-line-connect {
        background: #06c755;
        color: white !important;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-top: 15px;
        transition: 0.3s;
        border: none;
        width: 100%;
        justify-content: center;
    }
    .btn-line-connect:hover {
        background: #05b34c;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(6, 199, 85, 0.3);
    }
</style>

<div class="profile-page-wrapper">
    <div class="container" style="display: grid; grid-template-columns: 350px 1fr; gap: 30px;">
        
        <aside>
            <div class="profile-card text-center" style="position: sticky; top: 100px; padding: 45px 20px;">
                <div style="position: absolute; top:0; left:0; width:100%; height:110px; background: linear-gradient(135deg, var(--primary), #6366f1); z-index: 0;"></div>
                
                <div style="position: relative; z-index: 1;">
                    <div class="avatar-wrapper" style="position: relative; display: inline-block;">
                       <?php
    $display_avatar = (!empty($user['profile_img'])) 
                                            ? "../assets/images/profiles/" . $user['profile_img'] 
                                            : "../assets/images/profiles/default_profile.png";
?>
                        <img src="<?= $display_avatar ?>" 
                             id="img-preview" alt="Profile"
                             style="width: 155px; height: 155px; border-radius: 50%; object-fit: cover; border: 6px solid var(--bg-card); box-shadow: 0 10px 30px rgba(0,0,0,0.15); background-color: var(--bg-body);">
                        <label for="avatar-input" class="btn-camera-overlay">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>

                    <div style="margin-top: 25px;">
                        <h2 style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px;"><?= htmlspecialchars($user['fullname']) ?></h2>
                        
                        <?php if (in_array($user['role'], ['admin', 'seller', 'teacher'])): ?>
                            <div>
                                <span class="badge-role <?= $user['role'] ?>" style="padding: 6px 18px; border-radius: 50px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">
                                    <i class="fas <?= $user['role'] === 'teacher' ? 'fa-chalkboard-teacher' : ($user['role'] === 'admin' ? 'fa-user-shield' : 'fa-store') ?>"></i> <?= strtoupper($user['role']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 40px; text-align: left; padding: 0 15px; border-top: 1px solid var(--border-color); padding-top: 30px;">
                      
                        <a href="wishlist.php" class="sidebar-menu-item">
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <span><i class="fas fa-heart" style="width: 25px; color: #ef4444;"></i> รายการที่ชอบ</span>
                                <span style="background: #ef4444; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: 700;"><?= $wish_count ?></span>
                            </div>
                        </a>

                        <?php if (in_array($user['role'], ['admin', 'seller', 'teacher'])): ?>
                        <div style="margin-top: 20px; padding: 15px; background: rgba(6, 199, 85, 0.05); border-radius: 16px; border: 1px solid rgba(6, 199, 85, 0.1);">
                            <h4 style="font-size: 0.85rem; color: #06c755; font-weight: 700; margin-bottom: 8px;">
                                <i class="fab fa-line"></i> แจ้งเตือนผ่าน LINE
                            </h4>
                            
                            <?php 
                            // 🛠️ เช็คการเชื่อมต่อ: สำหรับ Admin/Teacher เช็คจากตาราง users, สำหรับ Seller เช็คจากตาราง shops
                            $line_id = (in_array($user['role'], ['admin', 'teacher'])) ? ($user['line_user_id'] ?? '') : ($shop_info['line_user_id'] ?? '');
                            
                            if (empty($line_id)): 
                            ?>
                                <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 10px;">เชื่อมต่อเพื่อรับแจ้งเตือนระบบอัตโนมัติ</p>
                                <?php
                                $client_id = "2009322126"; 
                                $redirect_uri = urlencode("http://localhost/student_marketplace/auth/line_login_callback.php");
                                $state = $_SESSION['user_id'];
                                $line_auth_url = "https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id=$client_id&redirect_uri=$redirect_uri&state=$state&scope=profile%20openid";
                                ?>
                                <a href="<?= $line_auth_url ?>" class="btn-line-connect" style="font-size: 0.8rem; padding: 10px;">
                                    <i class="fas fa-link"></i> เชื่อมต่อ LINE
                                </a>
                            <?php else: ?>
                                <div style="display: flex; align-items: center; gap: 8px; color: #06c755; font-size: 0.85rem; font-weight: 600;">
                                    <i class="fas fa-check-circle"></i> เชื่อมต่อแล้ว
                                    <a href="../auth/line_disconnect.php" style="color: #ef4444; font-size: 0.7rem; font-weight: 400; margin-left: auto;">ยกเลิก</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div style="margin-top: 20px; border-top: 1px dashed var(--border-color); padding-top: 20px;">
                            <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 12px; font-size: 0.9rem; color: var(--text-muted);">
                                <i class="fas fa-envelope text-primary" style="width: 20px;"></i> <?= htmlspecialchars($user['email']) ?>
                            </div>
                            <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 12px; font-size: 0.9rem; color: var(--text-muted);">
                                <i class="fas fa-id-card text-primary" style="width: 20px;"></i> <?= htmlspecialchars($user['student_id']) ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; font-size: 0.9rem; color: var(--text-muted);">
                                <i class="fas fa-calendar-alt text-primary" style="width: 20px;"></i> เข้าร่วม: <?= date('d/m/Y', strtotime($user['created_at'] ?? 'now')) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <main>
            <div class="profile-card" style="padding: 45px; min-height: 600px;">
                <h1 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 40px; color: var(--text-main); display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-sliders-h text-primary"></i> ตั้งค่าบัญชีผู้ใช้
                </h1>

                <?php echo displayFlashMessage(); ?>

                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display: none;">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-muted);">ชื่อ-นามสกุลจริง</label>
                            <input type="text" name="fullname" class="input-modern" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-muted);">เบอร์โทรศัพท์ติดต่อ</label>
                            <input type="text" name="phone" class="input-modern" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="0xx-xxx-xxxx">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 30px;">
                        <label style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-muted);">สโลแกนหรือประวัติส่วนตัว (Bio)</label>
                        <textarea name="bio" class="input-modern" style="min-height: 150px; resize: vertical;" placeholder="บอกเล่าเรื่องราวของคุณให้โลกรู้..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <div style="margin-top: 50px; padding-top: 35px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 15px;">
                        <a href="index.php" class="btn btn-outline" style="padding: 14px 35px; border-radius: 12px; font-weight: 600;">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary" style="padding: 14px 45px; border-radius: 12px; font-weight: 700; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);">
                            <i class="fas fa-check-circle"></i> บันทึกข้อมูล
                        </button>
                    </div>
                </form>

                <div style="margin-top: 40px; padding: 30px; border-radius: 20px; background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.1); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin-bottom: 5px; color: var(--text-main); font-weight: 700;"><i class="fas fa-lock text-danger"></i> ความปลอดภัยของบัญชี</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">ควรเปลี่ยนรหัสผ่านทุก ๆ 3 เดือนเพื่อความปลอดภัยสูงสุด</p>
                    </div>
                    <a href="change_password.php" class="btn btn-danger" style="border-radius: 10px; padding: 10px 25px; font-size: 0.9rem;">
                        <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.getElementById('avatar-input').onchange = evt => {
        const [file] = evt.target.files;
        if (file) {
            const preview = document.getElementById('img-preview');
            preview.style.filter = 'grayscale(100%) blur(3px)';
            preview.style.opacity = '0.5';
            
            const reader = new FileReader();
            reader.onload = (e) => {
                setTimeout(() => {
                    preview.src = e.target.result;
                    preview.style.filter = 'none';
                    preview.style.opacity = '1';
                }, 400); 
            };
            reader.readAsDataURL(file);
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>