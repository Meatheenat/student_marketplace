<?php
$pageTitle = "อนุมัติโพสต์ตามหาของ - Admin Panel";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// 🛡️ เช็คสิทธิ์ (เฉพาะ admin หรือ teacher)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    $_SESSION['flash_message'] = "⚠️ เฉพาะผู้ดูแลระบบเท่านั้นที่เข้าถึงหน้านี้ได้";
    $_SESSION['flash_type'] = "danger";
    redirect('../pages/index.php');
}

$db = getDB();

// ดึงข้อมูลเฉพาะโพสต์ที่รออนุมัติ (pending)
$stmt = $db->query("
    SELECT w.*, u.fullname, u.profile_img, c.category_name 
    FROM wtb_posts w 
    JOIN users u ON w.user_id = u.id 
    LEFT JOIN categories c ON w.category_id = c.id
    WHERE w.status = 'pending' 
    ORDER BY w.created_at ASC
");
$pending_posts = $stmt->fetchAll();
?>

<style>
    :root {
        --adm-bg: #f8fafc;
        --adm-card: #ffffff;
        --adm-border: #e2e8f0;
        --adm-text: #0f172a;
    }
    .dark-theme {
        --adm-bg: #0b0e14;
        --adm-card: #161b26;
        --adm-border: #2d3748;
        --adm-text: #ffffff;
    }

    body { background-color: var(--adm-bg) !important; }

    .admin-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
    
    .wtb-review-card {
        background: var(--adm-card);
        border: 2px solid var(--adm-border);
        border-radius: 24px;
        padding: 25px;
        margin-bottom: 20px;
        display: grid;
        grid-template-columns: 180px 1fr 200px;
        gap: 25px;
        align-items: start;
        transition: 0.3s;
    }
    .wtb-review-card:hover { border-color: #6366f1; transform: translateY(-3px); }

    .review-img { width: 100%; aspect-ratio: 1; object-fit: contain; border-radius: 16px; background: var(--bg-main); border: 1px solid var(--adm-border); }
    
    .btn-approve { background: #10b981; color: #fff; border: none; padding: 12px; border-radius: 12px; font-weight: 800; width: 100%; margin-bottom: 10px; cursor: pointer; transition: 0.3s; }
    .btn-reject { background: #ef4444; color: #fff; border: none; padding: 12px; border-radius: 12px; font-weight: 800; width: 100%; cursor: pointer; transition: 0.3s; }
    .btn-approve:hover { filter: brightness(1.1); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }
    .btn-reject:hover { filter: brightness(1.1); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3); }

    @media (max-width: 768px) {
        .wtb-review-card { grid-template-columns: 1fr; text-align: center; }
        .review-img { max-width: 200px; margin: 0 auto; }
    }
</style>

<div class="admin-container">
    <div class="mb-5 d-flex justify-content-between align-items-center">
        <div>
            <h1 style="font-weight: 900; color: var(--adm-text);"><i class="fas fa-user-shield text-primary"></i> จัดการโพสต์ตามหาของ</h1>
            <p class="text-muted">มีโพสต์รอตรวจสอบทั้งหมด <b><?= count($pending_posts) ?></b> รายการ</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary" style="border-radius: 12px; font-weight: 800;">กลับ Dashboard</a>
    </div>

    <?php echo displayFlashMessage(); ?>

    <?php if (count($pending_posts) > 0): ?>
        <?php foreach ($pending_posts as $post): ?>
            <div class="wtb-review-card">
                <div>
                    <?php if ($post['image_url']): ?>
                        <img src="../assets/images/products/<?= htmlspecialchars($post['image_url']) ?>" class="review-img">
                    <?php else: ?>
                        <div class="review-img d-flex align-items-center justify-content-center text-muted" style="font-size: 0.8rem; background: #eee;">ไม่มีรูปภาพ</div>
                    <?php endif; ?>
                </div>

                <div>
                    <span class="badge bg-primary mb-2"><?= htmlspecialchars($post['category_name'] ?? 'ทั่วไป') ?></span>
                    <h3 style="font-weight: 800; color: var(--adm-text); margin-bottom: 10px;"><?= htmlspecialchars($post['title']) ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;"><?= nl2br(htmlspecialchars($post['description'])) ?></p>
                    <hr style="border-color: var(--adm-border);">
                    <div class="d-flex align-items-center gap-3">
                        <img src="../assets/images/profiles/<?= $post['profile_img'] ?: 'default_profile.png' ?>" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                        <small class="text-muted">โพสต์โดย: <b><?= htmlspecialchars($post['fullname']) ?></b> | งบ: ฿<?= number_format($post['budget'] ?? 0) ?></small>
                    </div>
                </div>

                <div>
                    <form action="process_wtb_approval.php" method="POST">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <button type="submit" name="action" value="approve" class="btn-approve">
                            <i class="fas fa-check"></i> อนุมัติโพสต์
                        </button>
                        <button type="submit" name="action" value="reject" class="btn-reject" onclick="return confirm('ยืนยันการไม่อนุมัติโพสต์นี้?')">
                            <i class="fas fa-ban"></i> ไม่อนุมัติ
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
       <div class="col-12 text-center" style="grid-column: 1 / -1; width: 100%; padding: 100px 20px; border: 3px dashed var(--wtb-border-color); border-radius: 32px; background: var(--wtb-card-bg); margin: 20px 0;">
            <div style="max-width: 500px; margin: 0 auto;">
                <i class="fas fa-check-circle" style="font-size: 5rem; color: #10b981; opacity: 0.3; margin-bottom: 20px; display: block;"></i>
                <h3 style="font-weight: 900; color: var(--wtb-text-dark); font-size: 1.8rem; letter-spacing: -0.5px;">ไม่มีโพสต์ค้างรออนุมัติ</h3>
                <p class="text-muted fw-bold" style="font-size: 1rem;">ยอดเยี่ยมมาก! คุณตรวจสอบและจัดการโพสต์ทั้งหมดเรียบร้อยแล้ว</p>
                
                <div class="mt-4">
                    <a href="admin_dashboard.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                        <i class="fas fa-arrow-left me-2"></i> กลับสู่แดชบอร์ด
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>