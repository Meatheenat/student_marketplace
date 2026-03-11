<?php
/**
 * BNCC MARKET - PREMIUM WTB BOARD (Want To Buy)
 */
$pageTitle = "กระดานตามหาของ - Student Marketplace";
require_once '../includes/header.php';
require_once '../includes/functions.php';

$db = getDB();

// 1. ดักจับการลบโพสต์ของตัวเอง (เปลี่ยนเป็นสถานะ closed)
if (isset($_GET['delete']) && isLoggedIn()) {
    $del_id = (int)$_GET['delete'];
    $stmt = $db->prepare("UPDATE wtb_posts SET status = 'closed' WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$del_id, $_SESSION['user_id']])) {
        $_SESSION['flash_message'] = "ลบประกาศตามหาเรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "success";
    }
    redirect('wtb_board.php');
}

// 2. ดึงข้อมูลโพสต์เฉพาะสถานะ 'active' (ที่แอดมินอนุมัติแล้ว)
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
    /* 🎨 DESIGN SYSTEM */
    :root {
        --wtb-card-bg: #ffffff;
        --wtb-border: #e2e8f0;
        --wtb-text: #0f172a;
    }
    .dark-theme {
        --wtb-card-bg: #1e293b;
        --wtb-border: #334155;
        --wtb-text: #f8fafc;
    }

    .wtb-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

    /* Card Styling */
    .wtb-item-card {
        background: var(--wtb-card-bg);
        border: 2px solid var(--wtb-border);
        border-radius: 28px;
        padding: 25px;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
    }
    .wtb-item-card:hover {
        transform: translateY(-8px);
        border-color: #6366f1;
        box-shadow: 0 20px 40px rgba(99, 102, 241, 0.1);
    }

    /* รูปภาพพรีวิวในหน้ากระดาน */
    .wtb-img-preview {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-radius: 20px;
        margin-bottom: 20px;
        background: var(--bg-main);
        border: 1px solid var(--wtb-border);
    }

    .user-meta { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
    .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #6366f1; }

    .wtb-title { font-size: 1.3rem; font-weight: 900; color: var(--wtb-text); margin-bottom: 10px; line-height: 1.3; }
    .wtb-desc { color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; flex-grow: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    /* Badges */
    .badge-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
    .badge-solid { padding: 6px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .bg-cat { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
    .bg-cond { background: rgba(16, 185, 129, 0.1); color: #10b981; }

    .price-box {
        padding: 15px;
        background: var(--bg-main);
        border-radius: 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .budget-val { font-size: 1.2rem; font-weight: 900; color: #f59e0b; }

    .btn-contact {
        background: #6366f1;
        color: white !important;
        padding: 12px 20px;
        border-radius: 14px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.3s;
        text-align: center;
        display: block;
        margin-top: 15px;
    }
    .btn-contact:hover { filter: brightness(1.1); transform: scale(1.02); }

    /* แอนิเมชันตอนโหลด */
    .fade-up { opacity: 0; transform: translateY(20px); animation: fadeInUp 0.6s ease forwards; }
    @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
</style>

<div class="wtb-container">
    
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-4">
        <div>
            <h1 style="font-weight: 900; color: var(--wtb-text); font-size: 2.5rem; letter-spacing: -1.5px; margin: 0;">
                <i class="fas fa-bullhorn text-warning"></i> กระดานตามหาของ
            </h1>
            <p style="color: var(--text-muted); font-weight: 600; font-size: 1.1rem;">ประกาศสิ่งที่มึงอยากได้ เดี๋ยวคนที่มีเขาจะทักมาเอง!</p>
        </div>
        <a href="wtb_create.php" class="btn" style="background: #6366f1; color: #fff; font-weight: 900; border-radius: 20px; padding: 18px 35px; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);">
            <i class="fas fa-plus-circle"></i> สร้างโพสต์ตามหา
        </a>
    </div>

    <?php echo displayFlashMessage(); ?>

    <div class="row g-4">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $index => $post): 
                $avatar = !empty($post['profile_img']) ? "../assets/images/profiles/" . $post['profile_img'] : "../assets/images/profiles/default_profile.png";
                
                // แปลงสถานะสภาพสินค้าเป็นคำอ่านง่ายๆ
                $cond_labels = ['any' => 'รับทุกสภาพ', 'good' => 'สภาพดี 80%+', 'new' => 'มือหนึ่งเท่านั้น'];
                $cond_label = $cond_labels[$post['expected_condition']] ?? 'ไม่ระบุสภาพ';
            ?>
                <div class="col-md-6 col-lg-4 fade-up" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <div class="wtb-item-card">
                        
                        <?php if ($post['image_url']): ?>
                            <img src="../assets/images/products/<?= htmlspecialchars($post['image_url']) ?>" class="wtb-img-preview">
                        <?php else: ?>
                            <div class="wtb-img-preview d-flex align-items-center justify-content-center">
                                <i class="fas fa-box-open" style="font-size: 3rem; opacity: 0.2;"></i>
                            </div>
                        <?php endif; ?>

                        <div class="user-meta">
                            <img src="<?= $avatar ?>" class="user-avatar">
                            <div>
                                <div style="font-weight: 800; font-size: 0.9rem; color: var(--wtb-text);"><?= htmlspecialchars($post['fullname']) ?></div>
                                <div style="font-size: 0.7rem; color: var(--text-muted);"><?= date('d M Y • H:i', strtotime($post['created_at'])) ?></div>
                            </div>
                        </div>

                        <h3 class="wtb-title"><?= htmlspecialchars($post['title']) ?></h3>

                        <div class="badge-row">
                            <span class="badge-solid bg-cat"><i class="fas fa-tag"></i> <?= htmlspecialchars($post['category_name'] ?? 'ทั่วไป') ?></span>
                            <span class="badge-solid bg-cond"><i class="fas fa-star"></i> <?= $cond_label ?></span>
                        </div>

                        <p class="wtb-desc"><?= nl2br(htmlspecialchars($post['description'])) ?></p>

                        <div class="price-box">
                            <span style="font-size: 0.8rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">งบประมาณ</span>
                            <span class="budget-val">
                                <?= $post['budget'] > 0 ? '฿' . number_format($post['budget']) : 'ตกลงกันเอง' ?>
                            </span>
                        </div>

                        <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
                            <a href="wtb_board.php?delete=<?= $post['id'] ?>" class="btn btn-danger w-100" style="margin-top: 15px; border-radius: 14px; font-weight: 800;" onclick="return confirm('ปิดประกาศตามหานี้ใช่ไหม?');">
                                <i class="fas fa-check-circle"></i> ปิดประกาศนี้
                            </a>
                        <?php else: ?>
                            <a href="chat.php?user=<?= $post['user_id'] ?>" class="btn-contact">
                                <i class="fas fa-comment-dots"></i> มึง! กูมีของชิ้นนี้
                            </a>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center" style="padding: 120px 20px;">
                <div style="width: 100px; height: 100px; background: var(--wtb-border); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; opacity: 0.5;">
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--text-muted);"></i>
                </div>
                <h2 style="font-weight: 900; color: var(--wtb-text);">ยังไม่มีใครประกาศตามหาของ</h2>
                <p style="color: var(--text-muted);">ถ้ามึงอยากได้อะไรเป็นคนแรก กดปุ่มสีม่วงด้านบนเลย!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>