<?php
/**
 * BNCC Market - User Profile Page
 */
$pageTitle = "โปรไฟล์ของฉัน - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// ป้องกันคนไม่ล็อกอินแอบเข้ามา
if (!isLoggedIn()) redirect('../auth/login.php');

$db = getDB();
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้ล่าสุด
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// --- ส่วนประมวลผลการอัปเดต (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $bio = trim($_POST['bio']);
    $profile_img = $user['profile_img']; // ค่าเดิม

    // จัดการอัปโหลดรูปภาพ
    if (!empty($_FILES['avatar']['name'])) {
        $target_dir = "../assets/uploads/profiles/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
        $new_file_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            $profile_img = $new_file_name;
        }
    }

    // อัปเดตข้อมูลลง DB
    $update = $db->prepare("UPDATE users SET fullname = ?, phone = ?, bio = ?, profile_img = ? WHERE id = ?");
    if ($update->execute([$fullname, $phone, $bio, $profile_img, $user_id])) {
        $_SESSION['fullname'] = $fullname; // อัปเดตชื่อใน Session ด้วย
        $_SESSION['flash_message'] = "อัปเดตโปรไฟล์สำเร็จ!";
        $_SESSION['flash_type'] = "success";
        header("Refresh:0"); // รีโหลดหน้าเพื่อโชว์ข้อมูลใหม่
        exit();
    }
}
?>

<div class="container" style="margin-top: 40px; margin-bottom: 60px;">
    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 30px;">
        
        <aside class="card text-center" style="height: fit-content;">
            <div style="position: relative; display: inline-block; margin-bottom: 20px;">
                <img src="../assets/uploads/profiles/<?= $user['profile_img'] ?>" 
                     id="img-preview"
                     style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary);">
                <div style="margin-top: 15px;">
                    <h3 style="margin-bottom: 5px;"><?= htmlspecialchars($user['fullname']) ?></h3>
                    <span class="badge" style="background: var(--primary); color: #fff; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem;">
                        <?= strtoupper($user['role']) ?>
                    </span>
                </div>
            </div>
            <hr style="border-color: var(--border-color); margin: 20px 0;">
            <p style="font-size: 0.9rem; color: var(--text-muted);">รหัสนักศึกษา: <?= $user['student_id'] ?></p>
            <p style="font-size: 0.9rem; color: var(--text-muted);">อีเมล: <?= $user['email'] ?></p>
        </aside>

        <main class="card">
            <h2 style="margin-bottom: 25px;"><i class="fas fa-user-edit"></i> แก้ไขข้อมูลส่วนตัว</h2>
            <?php echo displayFlashMessage(); ?>

            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล</label>
                        <input type="text" name="fullname" class="input" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>เบอร์โทรศัพท์ติดต่อ</label>
                        <input type="text" name="phone" class="input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="0xx-xxx-xxxx">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>คำอธิบายตัวเอง (Bio)</label>
                    <textarea name="bio" class="input" style="height: 100px; resize: none;"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>เปลี่ยนรูปโปรไฟล์</label>
                    <input type="file" name="avatar" id="avatar-input" class="input" accept="image/*">
                    <small style="color: var(--text-muted);">แนะนำรูปทรงจัตุรัส ขนาดไม่เกิน 2MB</small>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง</button>
                    <a href="index.php" class="btn btn-outline">ยกเลิก</a>
                </div>
            </form>
        </main>
    </div>
</div>



<script>
    // ระบบ Preview รูปภาพก่อนอัปโหลด
    document.getElementById('avatar-input').onchange = evt => {
        const [file] = evt.target.files;
        if (file) {
            document.getElementById('img-preview').src = URL.createObjectURL(file);
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>