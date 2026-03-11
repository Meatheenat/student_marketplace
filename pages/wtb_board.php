<?php
/**
 * BNCC MARKET - WTB BOARD (Want To Buy)
 * Professional UX/UI Version
 */
$pageTitle = "กระดานตามหาของ - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

$db = getDB();

// 1. จัดการการปิดประกาศ (เปลี่ยนสถานะเป็น closed)
if (isset($_GET['delete']) && isLoggedIn()) {
    $del_id = (int)$_GET['delete'];
    $stmt = $db->prepare("UPDATE wtb_posts SET status = 'closed' WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$del_id, $_SESSION['user_id']])) {
        $_SESSION['flash_message'] = "ปิดประกาศตามหาเรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "success";
    }
    redirect('wtb_board.php');
}

// 2. ดึงข้อมูลประกาศตามหา (เฉพาะที่อนุมัติแล้ว)
$stmt = $db->query("
    SELECT w.*, u.fullname, u.profile_img, c.category_name 
    FROM wtb_posts w 
    JOIN users u ON w.user_id = u.id 
    LEFT JOIN categories c ON w.category_id = c.id
    WHERE w.status = 'active' 
    ORDER BY w.created_at DESC
");
$posts = $stmt->fetchAll();
?>

<style>
    :root {
        --wtb-bg: #f8fafc;
        --wtb-card: #ffffff;
        --wtb-border: #e2e8f0;
        --wtb-text-main: #0f172a;
        --wtb-primary: #4f46e5;
    }

    .dark-theme {
        --wtb-bg: #0b0e14;
        --wtb-card: #161b26;
        --wtb-border: #2d3748;
        --wtb-text-main: #f8fafc;
        --wtb-primary: #6366f1;
    }

    .wtb-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

    /* 📦 Card Design */
    .wtb-card-item {
        background: var(--wtb-card);
        border: 2px solid var(--wtb-border);
        border-radius: 24px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: transform 0.3s ease, border-color 0.3s ease;
    }

    .wtb-card-item:hover {
        transform: translateY(-5px);
        border-color: var(--wtb-primary);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }

    /* 🖼️ Image Handling (Fixed Aspect Ratio) */
    .wtb-img-container {
        width: 100%;
        height: 200px; /* จำกัดความสูงคงที่ */
        background: var(--bg-main);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid var(--wtb-border);
    }

    .wtb-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* ป้องกันรูปบีบ/ยืด */
    }

    .wtb-no-img {
        font-size: 3rem;
        opacity: 0.2;
        color: var(--wtb-text-main);
    }

    /* 📝 Content Area */
    .wtb-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }

    .wtb-user-info { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
    .wtb-avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }
    
    .wtb-title { font-size: 1.25rem; font-weight: 800; color: var(--wtb-text-main); margin-bottom: 10px; line-height: 1.4; }
    .wtb-desc { font-size: 0.95rem; color: var(--text-muted); margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.6; }

    /* 🏷️ Badges */
    .wtb-badge-group { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
    .wtb-badge { padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; background: var(--bg-main); border: 1px solid var(--wtb-border); }

    /* 💰 Budget Box */
    .wtb-footer {
        padding: 15px 20px;
        background: var(--bg-main);
        border-top: 1px solid var(--wtb-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .wtb-budget { font-size: 1.1rem; font-weight: 900; color: #f59e0b; }

    .btn-wtb-action {
        width: 100%;
        padding: 12px;
        border-radius: 12px;
        font-weight: 800;
        text-align: center;
        text-decoration: none;
        transition: 0.3s;
        margin-top: 15px;
        display: inline-block;
    }
    
    .btn-offer { background: var(--wtb-primary); color: #fff !important; }
    .btn-offer:hover { filter: brightness(1.1); }
    
    .btn-close-post { background: rgba(239, 68, 68, 0.1); color: #ef4444 !important; border: 1px solid rgba(239, 68, 68, 0.2); }
    .btn-close-post:hover { background: #ef4444; color: #fff !important; }

</style>

<div class="wtb-container">
    
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div>
            <h1 style="font-weight: 900; font-size: 2.5rem; letter-spacing: -1px; margin: 0; color: var(--wtb-text-main);">
                <i class="fas fa-search text-primary"></i> กระดานตามหาของ
            </h1>
            <p class="text-muted" style="font-weight: 600;">ประกาศหาสิ่งที่ต้องการ เพื่อให้ผู้ที่มีสินค้าติดต่อคุณโดยตรง</p>
        </div>
        <a href="wtb_create.php" class="btn btn-primary shadow-sm" style="border-radius: 15px; padding: 12px 25px; font-weight: 800;">
            <i class="fas fa-plus-circle"></i> สร้างประกาศใหม่
        </a>
    </div>

    <?php echo displayFlashMessage(); ?>

    <div class="row g-4">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): 
                $avatar = !empty($post['profile_img']) ? "../assets/images/profiles/" . $post['profile_img'] : "../assets/images/profiles/default_profile.png";
                $cond_labels = ['any' => 'ทุกสภาพ', 'good' => 'สภาพดี', 'new' => 'ของใหม่'];
                $cond_text = $cond_labels[$post['expected_condition']] ?? 'ไม่ระบุ';
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="wtb-card-item">
                        <div class="wtb-img-container">
                            <?php if ($post['image_url']): ?>
                                <img src="../assets/images/products/<?= htmlspecialchars($post['image_url']) ?>" alt="Reference Image">
                            <?php else: ?>
                                <i class="fas fa-box-open wtb-no-img"></i>
                            <?php endif; ?>
                        </div>

                        <div class="wtb-body">
                            <div class="wtb-user-info">
                                <img src="<?= $avatar ?>" class="wtb-avatar">
                                <div>
                                    <div style="font-weight: 800; font-size: 0.9rem; color: var(--wtb-text-main);"><?= htmlspecialchars($post['fullname']) ?></div>
                                    <small class="text-muted"><?= date('d M Y', strtotime($post['created_at'])) ?></small>
                                </div>
                            </div>

                            <h3 class="wtb-title"><?= htmlspecialchars($post['title']) ?></h3>
                            
                            <div class="wtb-badge-group">
                                <span class="wtb-badge text-primary"><i class="fas fa-tag"></i> <?= htmlspecialchars($post['category_name'] ?? 'ทั่วไป') ?></span>
                                <span class="wtb-badge text-success"><i class="fas fa-check-circle"></i> <?= $cond_text ?></span>
                            </div>

                            <p class="wtb-desc"><?= nl2br(htmlspecialchars($post['description'])) ?></p>

                            <div class="mt-auto">
                                <div class="wtb-footer rounded-3">
                                    <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted);">งบประมาณ</span>
                                    <span class="wtb-budget"><?= $post['budget'] > 0 ? '฿' . number_format($post['budget']) : 'ตกลงภายหลัง' ?></span>
                                </div>

                                <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
                                    <a href="wtb_board.php?delete=<?= $post['id'] ?>" class="btn-wtb-action btn-close-post" onclick="return confirm('ยืนยันการปิดประกาศนี้?')">
                                        <i class="fas fa-check"></i> ปิดประกาศนี้
                                    </a>
                                <?php else: ?>
                                    <a href="chat.php?user=<?= $post['user_id'] ?>" class="btn-wtb-action btn-offer">
                                        <i class="fas fa-comment-alt"></i> ติดต่อเสนอสินค้า
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-search mb-3 opacity-25" style="font-size: 4rem;"></i>
                <h3 class="text-muted">ยังไม่มีประกาศตามหาในขณะนี้</h3>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>