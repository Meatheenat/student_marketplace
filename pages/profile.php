<?php
/**
 * BNCC Market - Master Profile Dashboard (Full Version)
 * [SOLID HIGH-CONTRAST REDESIGN]
 * Project: BNCC Student Marketplace [Cite: User Summary]
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
    /* ============================================================
       🛠️ SOLID DESIGN SYSTEM - HIGH CONTRAST (เหมือน Index/Product)
       ============================================================ */
    :root {
        --solid-bg: #f8fafc;
        --solid-card: #ffffff;
        --solid-text: #0f172a;
        --solid-border: #cbd5e1;
        --solid-primary: #4f46e5;
        --solid-danger: #ef4444;
    }

    .dark-theme {
        --solid-bg: #0b0e14;
        --solid-card: #161b26;
        --solid-text: #ffffff;
        --solid-border: #2d3748;
        --solid-primary: #6366f1;
    }

    body {
        background-color: var(--solid-bg) !important;
        color: var(--solid-text);
        transition: background 0.3s ease;
    }

    .profile-page-wrapper {
        padding: 50px 0 80px 0;
        min-height: calc(100vh - 75px);
    }

    /* 🧱 🛠️ Solid Cards */
    .profile-card {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 32px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        position: relative;
    }

    .sidebar-card {
        position: sticky;
        top: 100px;
        text-align: center;
        padding: 0; /* Padding ใส่ใน Element ย่อยแทนเพราะมี Header Banner */
    }

    .sidebar-banner {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 120px;
        background: linear-gradient(135deg, var(--solid-primary), #a855f7);
        z-index: 0;
    }

    .sidebar-content {
        position: relative;
        z-index: 1;
        padding: 50px 30px 40px;
    }

    /* 📸 Avatar Styles */
    .avatar-wrapper {
        position: relative;
        display: inline-block;
        margin-bottom: 20px;
    }

    .avatar-img {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        object-fit: cover;
        border: 6px solid var(--solid-card);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        background-color: var(--solid-bg);
        transition: filter 0.3s ease;
    }

    .btn-camera-overlay {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: var(--solid-primary);
        color: #fff;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 4px solid var(--solid-card);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-camera-overlay:hover { transform: scale(1.15); }

    /* 🏷️ Role Badges */
    .badge-role {
        padding: 6px 18px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 0.75rem;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 2px solid transparent;
    }
    .badge-role.admin { background: rgba(99, 102, 241, 0.1); color: var(--solid-primary); border-color: rgba(99, 102, 241, 0.3); }
    .badge-role.seller { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.3); }
    .badge-role.teacher { background: rgba(239, 68, 68, 0.1); color: var(--solid-danger); border-color: rgba(239, 68, 68, 0.3); }

    /* 🔗 Sidebar Menus */
    .sidebar-menu-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 20px;
        color: var(--solid-text);
        text-decoration: none;
        border-radius: 16px;
        font-weight: 700;
        transition: 0.3s;
        border: 2px solid transparent;
        background: var(--solid-bg);
        margin-bottom: 10px;
    }
    .sidebar-menu-item:hover {
        border-color: var(--solid-primary);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        transform: translateY(-2px);
    }

    .wishlist-count {
        background: var(--solid-danger);
        color: #fff;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 900;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
    }

    /* 🟩 Line Connect */
    .btn-line-connect {
        background: #06c755;
        color: white !important;
        padding: 15px 20px;
        border-radius: 16px;
        font-weight: 800;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 15px;
        transition: 0.3s;
        border: none;
        width: 100%;
        box-shadow: 0 10px 20px rgba(6, 199, 85, 0.2);
    }
    .btn-line-connect:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(6, 199, 85, 0.4); }

    /* ⌨️ Form Inputs (Solid Style) */
    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 800;
        font-size: 0.85rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .input-solid {
        width: 100%;
        padding: 18px 25px;
        border-radius: 18px;
        border: 2px solid var(--solid-border);
        background: var(--solid-bg);
        color: var(--solid-text);
        font-size: 1.05rem;
        font-weight: 600;
        transition: all 0.3s ease;
        outline: none;
        font-family: 'Prompt', sans-serif;
    }

    .input-solid:focus {
        border-color: var(--solid-primary);
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.15);
        background: var(--solid-card);
        transform: translateY(-2px);
    }

    /* 🔥 Main Buttons */
    .btn-save-solid {
        padding: 16px 45px;
        border-radius: 16px;
        font-weight: 800;
        background: var(--solid-primary);
        color: #fff;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 1.05rem;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-save-solid:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(99, 102, 241, 0.5); }

    /* 🪄 Animations */
    .reveal-left { opacity: 0; transform: translateX(-30px); animation: slideReveal 0.8s ease forwards; }
    .reveal-right { opacity: 0; transform: translateX(30px); animation: slideReveal 0.8s ease 0.2s forwards; }
    @keyframes slideReveal { to { opacity: 1; transform: translateX(0); } }

    /* 📱 Responsive */
    @media (max-width: 992px) {
        .profile-layout { grid-template-columns: 1fr !important; }
        .sidebar-card { position: static; margin-bottom: 30px; }
    }
</style>

<div class="profile-page-wrapper">
    <div class="container profile-layout" style="display: grid; grid-template-columns: 360px 1fr; gap: 40px;">
        
        <aside class="reveal-left">
            <div class="profile-card sidebar-card">
                <div class="sidebar-banner"></div>
                
                <div class="sidebar-content">
                    <div class="avatar-wrapper">
                       <?php
                            $display_avatar = (!empty($user['profile_img'])) 
                                            ? "../assets/images/profiles/" . $user['profile_img'] 
                                            : "../assets/images/profiles/default_profile.png";
                        ?>
                        <img src="<?= $display_avatar ?>" id="img-preview" class="avatar-img" alt="Profile">
                        <label for="avatar-input" class="btn-camera-overlay">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>

                    <div style="margin-bottom: 35px;">
                        <h2 style="font-size: 1.8rem; font-weight: 900; color: var(--solid-text); margin-bottom: 12px; letter-spacing: -0.5px;">
                            <?= htmlspecialchars($user['fullname']) ?>
                        </h2>
                        
                        <?php if (in_array($user['role'], ['admin', 'seller', 'teacher'])): ?>
                            <span class="badge-role <?= $user['role'] ?>">
                                <i class="fas <?= $user['role'] === 'teacher' ? 'fa-chalkboard-teacher' : ($user['role'] === 'admin' ? 'fa-user-shield' : 'fa-store') ?>"></i> 
                                <?= strtoupper($user['role']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="text-align: left; padding-top: 30px; border-top: 2px solid var(--solid-border);">
                        
                        <a href="wishlist.php" class="sidebar-menu-item">
                            <span><i class="fas fa-heart" style="width: 25px; color: var(--solid-danger); font-size: 1.2rem;"></i> รายการที่ชอบ</span>
                            <span class="wishlist-count"><?= $wish_count ?></span>
                        </a>

                        <?php if (in_array($user['role'], ['admin', 'seller', 'teacher'])): ?>
                        <div style="margin-top: 25px; padding: 25px 20px; background: rgba(6, 199, 85, 0.05); border-radius: 20px; border: 2px solid rgba(6, 199, 85, 0.2); text-align: center;">
                            <h4 style="font-size: 0.9rem; color: #06c755; font-weight: 900; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">
                                <i class="fab fa-line" style="font-size: 1.2rem;"></i> LINE Notifications
                            </h4>
                            
                            <?php 
                            $line_id = (in_array($user['role'], ['admin', 'teacher'])) ? ($user['line_user_id'] ?? '') : ($shop_info['line_user_id'] ?? '');
                            if (empty($line_id)): 
                            ?>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px; font-weight: 600;">เชื่อมต่อเพื่อรับแจ้งเตือนระบบอัตโนมัติเมื่อมีออเดอร์ใหม่</p>
                                <?php
                                $client_id = "2009322126"; 
                                $redirect_uri = urlencode("http://hosting.bncc.ac.th/student_marketplace/auth/line_login_callback.php");
                                $state = $_SESSION['user_id'];
                                $line_auth_url = "https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id=$client_id&redirect_uri=$redirect_uri&state=$state&scope=profile%20openid";
                                ?>
                                <a href="<?= $line_auth_url ?>" class="btn-line-connect">
                                    เชื่อมต่อ LINE
                                </a>
                            <?php else: ?>
                                <div style="display: flex; justify-content: center; align-items: center; gap: 8px; color: #06c755; font-size: 0.95rem; font-weight: 800; margin-bottom: 10px;">
                                    <i class="fas fa-check-circle"></i> เชื่อมต่อเรียบร้อย
                                </div>
                                <a href="../auth/line_disconnect.php" style="color: var(--solid-danger); font-size: 0.75rem; font-weight: 700; text-decoration: underline;">ยกเลิกการเชื่อมต่อ</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div style="margin-top: 30px; padding: 20px; background: var(--solid-bg); border-radius: 16px; border: 2px solid var(--solid-border);">
                            <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 15px; font-size: 0.9rem; font-weight: 600; color: var(--solid-text);">
                                <i class="fas fa-envelope" style="color: var(--solid-primary); font-size: 1.1rem;"></i> <?= htmlspecialchars($user['email']) ?>
                            </div>
                            <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 15px; font-size: 0.9rem; font-weight: 600; color: var(--solid-text);">
                                <i class="fas fa-id-badge" style="color: var(--solid-primary); font-size: 1.1rem;"></i> <?= htmlspecialchars($user['student_id']) ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px; font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">
                                <i class="fas fa-calendar-check" style="color: var(--solid-primary); font-size: 1.1rem;"></i> Joined: <?= date('d M Y', strtotime($user['created_at'] ?? 'now')) ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </aside>

        <main class="reveal-right">
            <div class="profile-card" style="padding: 50px; min-height: 100%;">
                <h1 style="font-size: 2.2rem; font-weight: 900; margin-bottom: 40px; color: var(--solid-text); display: flex; align-items: center; gap: 15px; border-bottom: 3px solid var(--solid-bg); padding-bottom: 20px;">
                    <i class="fas fa-user-cog" style="color: var(--solid-primary);"></i> Profile Settings
                </h1>

                <?php echo displayFlashMessage(); ?>

                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display: none;">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullname" class="input-solid" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" class="input-solid" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="0xx-xxx-xxxx">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 40px;">
                        <label>Bio & Slogan</label>
                        <textarea name="bio" class="input-solid" style="min-height: 160px; resize: vertical; line-height: 1.6;" placeholder="บอกเล่าเรื่องราวหรือสโลแกนของคุณให้โลกรู้..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 20px; padding-top: 30px; border-top: 2px solid var(--solid-bg);">
                        <a href="index.php" class="btn btn-outline" style="padding: 16px 40px; border-radius: 16px; font-weight: 800; font-size: 1.05rem; border-width: 2px;">CANCEL</a>
                        <button type="submit" class="btn-save-solid">
                            SAVE CHANGES <i class="fas fa-check"></i>
                        </button>
                    </div>
                </form>

                <div style="margin-top: 50px; padding: 35px; border-radius: 24px; background: rgba(239, 68, 68, 0.05); border: 2px dashed var(--solid-danger); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                    <div>
                        <h3 style="margin-bottom: 8px; color: var(--solid-danger); font-weight: 900; font-size: 1.2rem; text-transform: uppercase;">
                            <i class="fas fa-shield-alt"></i> Account Security
                        </h3>
                        <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600; margin: 0;">ควรเปลี่ยนรหัสผ่านทุก ๆ 3 เดือนเพื่อความปลอดภัยสูงสุดของบัญชี</p>
                    </div>
                    <a href="change_password.php" class="btn btn-danger" style="border-radius: 14px; padding: 14px 30px; font-size: 1rem; font-weight: 800; box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);">
                        <i class="fas fa-key"></i> CHANGE PASSWORD
                    </a>
                </div>
            </div>
        </main>

    </div>
</div>

<script>
    // 📸 Image Upload Preview System with Feedback
    document.getElementById('avatar-input').onchange = evt => {
        const [file] = evt.target.files;
        if (file) {
            const preview = document.getElementById('img-preview');
            
            // Visual feedback before loading
            preview.style.filter = 'grayscale(100%) blur(5px)';
            preview.style.opacity = '0.5';
            
            const reader = new FileReader();
            reader.onload = (e) => {
                setTimeout(() => {
                    preview.src = e.target.result;
                    // Restore visual
                    preview.style.filter = 'none';
                    preview.style.opacity = '1';
                    preview.style.transform = 'scale(1.05)';
                    setTimeout(() => preview.style.transform = 'scale(1)', 200);
                }, 300); // Fake delay for smooth transition
            };
            reader.readAsDataURL(file);
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>