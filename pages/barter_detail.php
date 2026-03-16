<?php
/**
 * 🔍 BNCC Market - Barter Detail Page
 * หน้าแสดงรายละเอียดประกาศแลกเปลี่ยนสินค้า
 */
require_once '../includes/functions.php';

// รับค่า ID จาก URL และป้องกันการยิงโค้ดแปลกๆ
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = "ไม่พบรหัสประกาศที่คุณต้องการดูครับ";
    $_SESSION['flash_type'] = "warning";
    redirect('index.php'); // หรือเด้งไปหน้า barter_board.php ถ้ามี
}

$post_id = (int)$_GET['id'];
$db = getDB();

// 📦 ดึงข้อมูลประกาศ พร้อมข้อมูลคนโพสต์
$stmt = $db->prepare("
    SELECT b.*, u.fullname, u.student_id 
    FROM barter_posts b 
    LEFT JOIN users u ON b.user_id = u.id 
    WHERE b.id = ? AND b.status != 'deleted'
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

// ถ้าหาข้อมูลไม่เจอ (เช่น โดนลบไปแล้ว หรือพิมพ์ ID มั่ว)
if (!$post) {
    redirect('404.php'); // เด้งไปหน้าน้องผี 404 ที่เราทำไว้!
}

$pageTitle = htmlspecialchars($post['title']) . " | BNCC Barter";
require_once '../includes/header.php';
?>

<style>
    /* ============================================================
       💎 TITAN DETAIL UI - MODERN SPLIT LAYOUT
       ============================================================ */
    :root {
        --dt-bg: #f8fafc;
        --dt-card: #ffffff;
        --dt-text-main: #0f172a;
        --dt-text-sub: #64748b;
        --dt-border: #e2e8f0;
        --dt-surface: #f1f5f9;
        --dt-primary: #4f46e5;
        --dt-primary-hover: #4338ca;
    }

    .dark-theme {
        --dt-bg: #0b0f19;
        --dt-card: #161b26;
        --dt-text-main: #f8fafc;
        --dt-text-sub: #94a3b8;
        --dt-border: #334155;
        --dt-surface: #1e293b;
    }

    .detail-wrapper {
        padding: 50px 20px;
        background-color: var(--dt-bg);
        min-height: calc(100vh - 100px);
        font-family: 'Prompt', sans-serif;
        transition: background-color 0.4s ease;
    }

    .btn-back-board {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--dt-text-sub);
        font-weight: 700;
        text-decoration: none;
        margin-bottom: 20px;
        transition: 0.3s;
        font-size: 1.05rem;
    }
    .btn-back-board:hover {
        color: var(--dt-primary);
        transform: translateX(-5px);
    }

    .detail-card-glass {
        background: var(--dt-card);
        border: 2px solid var(--dt-border);
        border-radius: 32px;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.05);
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
        overflow: hidden;
        transition: all 0.4s ease;
    }

    .dark-theme .detail-card-glass {
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);
    }

    /* 📸 ฝั่งซ้าย: รูปภาพ */
    .detail-image-side {
        background: var(--dt-surface);
        padding: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .detail-main-img {
        width: 100%;
        max-width: 500px;
        aspect-ratio: 1/1;
        object-fit: cover;
        border-radius: 24px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    .detail-main-img:hover {
        transform: scale(1.02);
    }

    /* 📝 ฝั่งขวา: ข้อมูล */
    .detail-info-side {
        padding: 50px 40px;
        display: flex;
        flex-direction: column;
    }

    .post-status-badge {
        align-self: flex-start;
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 20px;
        letter-spacing: 0.5px;
    }
    .status-open { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .status-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .status-closed { background: rgba(99, 102, 241, 0.1); color: var(--dt-primary); }

    .detail-title {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--dt-text-main);
        margin-bottom: 20px;
        line-height: 1.3;
        letter-spacing: -0.5px;
    }

    /* กล่อง ข้อมูลแลกเปลี่ยน (Have & Want) */
    .exchange-focus-box {
        background: var(--dt-surface);
        border: 2px solid var(--dt-border);
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 30px;
    }
    .focus-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }
    .focus-item:not(:last-child) {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 2px dashed var(--dt-border);
    }
    .focus-icon {
        width: 45px; height: 45px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .icon-have { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .icon-want { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    
    .focus-label { font-size: 0.85rem; font-weight: 800; color: var(--dt-text-sub); text-transform: uppercase; }
    .focus-value { font-size: 1.2rem; font-weight: 700; color: var(--dt-text-main); margin-top: 2px; }

    /* รายละเอียด */
    .detail-desc {
        font-size: 1.05rem;
        color: var(--dt-text-sub);
        line-height: 1.7;
        margin-bottom: 35px;
        font-weight: 500;
        white-space: pre-line; /* รองรับการขึ้นบรรทัดใหม่จาก textarea */
    }

    /* ข้อมูลคนโพสต์ */
    .poster-profile {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: auto; /* ดันลงไปล่างสุด */
        padding-top: 25px;
        border-top: 2px solid var(--dt-border);
    }
    .poster-avatar {
        width: 50px; height: 50px;
        border-radius: 50%;
        background: var(--dt-primary);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; font-weight: bold;
    }
    .poster-name { font-size: 1.1rem; font-weight: 800; color: var(--dt-text-main); }
    .poster-id { font-size: 0.85rem; font-weight: 600; color: var(--dt-text-sub); }

    /* ปุ่ม Action */
    .btn-make-offer {
        width: 100%;
        padding: 20px;
        border-radius: 16px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        font-size: 1.15rem;
        font-weight: 800;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: 0.3s;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        text-decoration: none;
        margin-top: 30px;
    }
    .btn-make-offer:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(79, 70, 229, 0.5);
        color: white;
    }
    .btn-disabled {
        background: var(--dt-surface);
        color: var(--dt-text-sub);
        box-shadow: none;
        cursor: not-allowed;
    }
    .btn-disabled:hover { transform: none; box-shadow: none; color: var(--dt-text-sub); }

    /* Responsive ปรับสำหรับมือถือ */
    @media (max-width: 992px) {
        .detail-card-glass { grid-template-columns: 1fr; }
        .detail-image-side { padding: 30px; }
        .detail-info-side { padding: 30px 20px; }
    }
</style>

<div class="detail-wrapper">
    <div class="container" style="max-width: 1200px;">
        
        <a href="javascript:history.back()" class="btn-back-board">
            <i class="fas fa-arrow-left"></i> ย้อนกลับ
        </a>

        <?php echo displayFlashMessage(); ?>

        <div class="detail-card-glass">
            
            <div class="detail-image-side">
                <?php 
                    $img_src = !empty($post['image_url']) ? "../assets/images/barter/" . $post['image_url'] : "../assets/images/no_image.png";
                ?>
                <img src="<?= htmlspecialchars($img_src) ?>" class="detail-main-img" alt="ภาพสิ่งของ">
            </div>

            <div class="detail-info-side">
                
                <?php if ($post['status'] === 'open'): ?>
                    <div class="post-status-badge status-open"><i class="fas fa-bolt"></i> กำลังเปิดรับข้อเสนอ</div>
                <?php elseif ($post['status'] === 'pending'): ?>
                    <div class="post-status-badge status-pending"><i class="fas fa-hourglass-half"></i> รอการอนุมัติ</div>
                <?php else: ?>
                    <div class="post-status-badge status-closed"><i class="fas fa-check-circle"></i> ปิดการแลกเปลี่ยนแล้ว</div>
                <?php endif; ?>

                <h1 class="detail-title"><?= htmlspecialchars($post['title']) ?></h1>

                <div class="exchange-focus-box">
                    <div class="focus-item">
                        <div class="focus-icon icon-have"><i class="fas fa-box-open"></i></div>
                        <div>
                            <div class="focus-label">สิ่งที่มี (I HAVE)</div>
                            <div class="focus-value"><?= htmlspecialchars($post['item_have']) ?></div>
                        </div>
                    </div>
                    <div class="focus-item">
                        <div class="focus-icon icon-want"><i class="fas fa-hand-holding-heart"></i></div>
                        <div>
                            <div class="focus-label">สิ่งที่อยากได้ (I WANT)</div>
                            <div class="focus-value"><?= htmlspecialchars($post['item_want']) ?></div>
                        </div>
                    </div>
                </div>

                <div style="font-weight: 800; color: var(--dt-text-main); margin-bottom: 10px;">รายละเอียด / สภาพของ:</div>
                <div class="detail-desc"><?= htmlspecialchars($post['description']) ?></div>

                <div class="poster-profile">
                    <div class="poster-avatar">
                        <?= mb_substr($post['fullname'] ?? 'U', 0, 1) ?>
                    </div>
                    <div>
                        <div class="poster-name"><?= htmlspecialchars($post['fullname'] ?? 'ผู้ใช้งานที่ไม่ระบุตัวตน') ?></div>
                        <div class="poster-id">รหัสนักศึกษา: <?= htmlspecialchars($post['student_id'] ?? '-') ?> • โพสต์เมื่อ <?= date('d M Y', strtotime($post['created_at'])) ?></div>
                    </div>
                </div>

                <?php if ($post['status'] === 'open'): ?>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                        <div class="btn-make-offer btn-disabled">
                            <i class="fas fa-crown"></i> นี่คือประกาศของคุณเอง
                        </div>
                    <?php else: ?>
                        <a href="chat.php?user=<?= $post['user_id'] ?>" class="btn-make-offer">
                            <i class="fas fa-handshake"></i> ยื่นข้อเสนอแลกเปลี่ยน
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="btn-make-offer btn-disabled">
                        <i class="fas fa-lock"></i> ไม่สามารถยื่นข้อเสนอได้
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>