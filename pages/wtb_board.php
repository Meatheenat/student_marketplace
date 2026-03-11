<?php
$pageTitle = "กระดานตามหาของ - Student Marketplace";
require_once '../includes/header.php';
require_once '../includes/functions.php';

$db = getDB();

// ดักจับการลบโพสต์ของตัวเอง
if (isset($_GET['delete']) && isLoggedIn()) {
    $del_id = $_GET['delete'];
    $stmt = $db->prepare("UPDATE wtb_posts SET status = 'closed' WHERE id = ? AND user_id = ?");
    $stmt->execute([$del_id, $_SESSION['user_id']]);
    $_SESSION['flash_message'] = "ลบโพสต์ตามหาเรียบร้อยแล้ว";
    $_SESSION['flash_type'] = "success";
    redirect('wtb_board.php');
}

// ดึงข้อมูลโพสต์ทั้งหมดที่ยัง active
$stmt = $db->query("
    SELECT w.*, u.fullname, u.profile_img, u.role 
    FROM wtb_posts w 
    JOIN users u ON w.user_id = u.id 
    WHERE w.status = 'active' 
    ORDER BY w.created_at DESC
");
$posts = $stmt->fetchAll();
?>

<style>
    .wtb-card {
        background: var(--bg-card);
        border: 2px solid var(--border-color);
        border-radius: 24px;
        padding: 25px;
        transition: all 0.3s ease;
        margin-bottom: 20px;
    }
    .wtb-card:hover {
        border-color: #6366f1;
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.1);
        transform: translateY(-3px);
    }
    .btn-offer {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 2px solid rgba(16, 185, 129, 0.2);
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-offer:hover {
        background: #10b981;
        color: #fff;
    }
    .badge-budget {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 800;
        font-size: 0.85rem;
    }
</style>

<div class="container mt-5 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div>
            <h1 style="font-weight: 900; color: var(--text-main); margin: 0;">
                <i class="fas fa-search-dollar text-warning"></i> กระดานตามหาของ (WTB)
            </h1>
            <p style="color: var(--text-muted); margin-top: 5px;">ใครมีของที่เพื่อนตามหาอยู่ ทักแชทไปเสนอขายได้เลย!</p>
        </div>
        <a href="wtb_create.php" class="btn" style="background: #6366f1; color: #fff; font-weight: 800; border-radius: 14px; padding: 12px 25px;">
            <i class="fas fa-plus"></i> สร้างโพสต์ตามหา
        </a>
    </div>

    <?php echo displayFlashMessage(); ?>

    <div class="row">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): 
                $avatar = !empty($post['profile_img']) ? "../assets/images/profiles/" . $post['profile_img'] : "../assets/images/profiles/default_profile.png";
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="wtb-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= $avatar ?>" style="width: 45px; height: 45px; border-radius: 12px; object-fit: cover; margin-right: 12px;">
                            <div>
                                <div style="font-weight: 800; color: var(--text-main); font-size: 0.95rem;">
                                    <?= htmlspecialchars($post['fullname']) ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <i class="far fa-clock"></i> <?= date('d M Y - H:i', strtotime($post['created_at'])) ?>
                                </div>
                            </div>
                        </div>

                        <h4 style="font-weight: 800; color: var(--text-main); font-size: 1.2rem; margin-bottom: 10px;">
                            <?= htmlspecialchars($post['title']) ?>
                        </h4>
                        
                        <?php if (!empty($post['description'])): ?>
                            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= nl2br(htmlspecialchars($post['description'])) ?>
                            </p>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3" style="border-top: 1px solid var(--border-color);">
                            <div>
                                <?php if (!empty($post['budget'])): ?>
                                    <span class="badge-budget">งบ: ฿<?= number_format($post['budget']) ?></span>
                                <?php else: ?>
                                    <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">ตกลงราคากันเอง</span>
                                <?php endif; ?>
                            </div>

                            <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
                                <a href="wtb_board.php?delete=<?= $post['id'] ?>" class="btn btn-sm" style="color: #ef4444; background: rgba(239,68,68,0.1); font-weight: 800; border-radius: 10px;" onclick="return confirm('ลบโพสต์นี้ใช่ไหม?');">
                                    <i class="fas fa-trash"></i> ลบโพสต์
                                </a>
                            <?php else: ?>
                                <a href="chat.php?user=<?= $post['user_id'] ?>" class="btn-offer">
                                    <i class="fas fa-comment-dots"></i> ฉันมีของชิ้นนี้!
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center" style="padding: 100px 20px; border: 3px dashed var(--border-color); border-radius: 32px;">
                <i class="fas fa-box-open" style="font-size: 4rem; color: var(--text-muted); opacity: 0.3;"></i>
                <h3 class="mt-4" style="font-weight: 800; color: var(--text-main);">ยังไม่มีใครตามหาของเลย</h3>
                <p style="color: var(--text-muted);">เป็นคนแรกที่สร้างโพสต์ตามหาสินค้าสิ!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>