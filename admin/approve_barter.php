<?php
/**
 * การทำงานส่วนที่ 1: การตั้งค่าพื้นฐานและการดึงไฟล์ที่จำเป็น
 * -------------------------------------------------------------------------
 */
$pageTitle = "ระบบอนุมัติประกาศแลกเปลี่ยน (Admin) - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

/**
 * การทำงานส่วนที่ 2: ระบบรักษาความปลอดภัย (Security Auth Guard)
 * -------------------------------------------------------------------------
 * ใช้โค้ดเช็คสิทธิ์แบบ Manual ตามโครงสร้างเดิมของพี่เป๊ะๆ (ไม่แตะ functions.php)
 */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    $_SESSION['flash_message'] = "⚠️ การเข้าถึงถูกปฏิเสธ: เฉพาะผู้ดูแลระบบและอาจารย์เท่านั้น";
    $_SESSION['flash_type'] = "danger";
    redirect('../pages/index.php');
    exit();
}

$db = getDB();

/**
 * การทำงานส่วนที่ 3: ระบบจัดการคำร้อง (Approve / Reject Logic)
 * -------------------------------------------------------------------------
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $post_id = (int)$_POST['post_id'];
    $owner_id = (int)$_POST['owner_id'];
    $item_have = trim($_POST['item_have']);

    if ($post_id > 0) {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE barter_posts SET status = 'open' WHERE id = ?");
            if ($stmt->execute([$post_id])) {
                sendNotification($owner_id, 'system', "✅ ประกาศแลกเปลี่ยน [{$item_have}] ของคุณได้รับการอนุมัติแล้ว", "../pages/barter_detail.php?id={$post_id}");
                $_SESSION['flash_message'] = "อนุมัติรายการ [{$item_have}] เรียบร้อยแล้ว";
                $_SESSION['flash_type'] = "success";
            }
        } elseif ($action === 'reject') {
            $stmt = $db->prepare("UPDATE barter_posts SET status = 'rejected' WHERE id = ?");
            if ($stmt->execute([$post_id])) {
                sendNotification($owner_id, 'system', "❌ ขออภัย ประกาศแลกเปลี่ยน [{$item_have}] ของคุณไม่ผ่านการตรวจสอบ", "../pages/barter_board.php");
                $_SESSION['flash_message'] = "ปฏิเสธรายการ [{$item_have}] เรียบร้อยแล้ว";
                $_SESSION['flash_type'] = "danger";
            }
        }
    }
    redirect('approve_barter.php');
    exit();
}

/**
 * การทำงานส่วนที่ 4: การดึงข้อมูลประกาศแลกเปลี่ยนที่รออนุมัติ
 * -------------------------------------------------------------------------
 */
$sql_pending = "
    SELECT 
        b.*, 
        u.fullname, 
        u.profile_img,
        u.role as user_role
    FROM barter_posts b 
    INNER JOIN users u ON b.user_id = u.id 
    WHERE b.status = 'pending' 
    ORDER BY b.created_at ASC
";

$stmt = $db->query($sql_pending);
$pending_posts = $stmt->fetchAll();
?>

<style>
    /**
     * SECTION: CORE DESIGN SYSTEM VARIABLES
     * -------------------------------------------------------------------------
     */
    :root {
        --adm-primary: #6366f1;
        --adm-primary-dark: #4f46e5;
        --adm-success: #10b981;
        --adm-danger: #ef4444;
        --adm-warning: #f59e0b;
        --adm-bg: #f8fafc;
        --adm-card: #ffffff;
        --adm-border: #e2e8f0;
        --adm-text: #0f172a;
        --adm-text-muted: #64748b;
        --adm-shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        --adm-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        --adm-radius-lg: 28px;
        --adm-radius-md: 18px;
        --adm-radius-sm: 12px;
    }

    /**
     * SECTION: DARK MODE SYSTEM
     * -------------------------------------------------------------------------
     */
    .dark-theme {
        --adm-bg: #0b0e14;
        --adm-card: #161b26;
        --adm-border: #2d3748;
        --adm-text: #f8fafc;
        --adm-text-muted: #94a3b8;
    }

    /**
     * SECTION: GLOBAL LAYOUT RESET
     * -------------------------------------------------------------------------
     */
    body {
        background-color: var(--adm-bg) !important;
        color: var(--adm-text);
        font-family: 'Kanit', sans-serif;
        line-height: 1.6;
        transition: background-color 0.3s ease;
    }

    .admin-main-wrapper {
        max-width: 1200px;
        margin: 50px auto;
        padding: 0 25px;
        animation: fadeInPage 0.6s ease-out;
    }

    /**
     * SECTION: ADMIN HEADER COMPONENT
     * -------------------------------------------------------------------------
     */
    .adm-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 50px;
        padding-bottom: 30px;
        border-bottom: 2px solid var(--adm-border);
    }

    .adm-page-title h1 {
        font-size: 2.2rem;
        font-weight: 900;
        letter-spacing: -1px;
        margin: 0;
        background: linear-gradient(to right, var(--adm-primary), #a855f7);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .adm-page-title p {
        color: var(--adm-text-muted);
        font-weight: 600;
        margin-top: 5px;
    }

    .btn-back-dash {
        padding: 12px 25px;
        border-radius: 14px;
        font-weight: 800;
        text-decoration: none;
        background: var(--adm-card);
        color: var(--adm-text) !important;
        border: 2px solid var(--adm-border);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-back-dash:hover {
        background: var(--adm-primary);
        color: #fff !important;
        border-color: var(--adm-primary);
        transform: translateX(-5px);
    }

    /**
     * SECTION: REVIEW CARD GRID (UX FIX)
     * -------------------------------------------------------------------------
     */
    .wtb-review-card {
        background: var(--adm-card);
        border: 2px solid var(--adm-border);
        border-radius: var(--adm-radius-lg);
        padding: 30px;
        margin-bottom: 25px;
        display: grid;
        grid-template-columns: 200px 1fr 220px;
        gap: 30px;
        align-items: center;
        box-shadow: var(--adm-shadow-sm);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .wtb-review-card:hover {
        border-color: var(--adm-primary);
        box-shadow: var(--adm-shadow-lg);
        transform: translateY(-5px);
    }

    .adm-review-img-wrap {
        width: 100%;
        aspect-ratio: 1 / 1;
        background: var(--adm-bg);
        border-radius: var(--adm-radius-md);
        overflow: hidden;
        border: 1px solid var(--adm-border);
    }

    .adm-review-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .adm-no-img-placeholder {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--adm-text-muted);
        font-size: 0.8rem;
    }

    /**
     * SECTION: CONTENT ELEMENTS
     * -------------------------------------------------------------------------
     */
    .adm-post-meta {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .adm-cat-tag {
        display: inline-block;
        padding: 5px 12px;
        background: rgba(99, 102, 241, 0.1);
        color: var(--adm-primary);
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 800;
        width: fit-content;
    }

    .adm-post-title {
        font-size: 1.4rem;
        font-weight: 900;
        margin: 0;
        color: var(--adm-text);
    }

    .adm-post-desc {
        color: var(--adm-text-muted);
        font-size: 0.95rem;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .adm-user-info-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid var(--adm-border);
    }

    .adm-user-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
    }

    /**
     * SECTION: BUTTONS CONTROLS
     * -------------------------------------------------------------------------
     */
    .adm-action-btns {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .btn-adm-action {
        width: 100%;
        padding: 14px;
        border-radius: var(--adm-radius-sm);
        font-weight: 800;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: 0.3s;
    }

    .btn-approve-post {
        background: var(--adm-success);
        color: #fff;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    .btn-reject-post {
        background: var(--adm-danger);
        color: #fff;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
    }

    .btn-adm-action:hover {
        filter: brightness(1.1);
        transform: translateY(-2px);
    }

    /**
     * SECTION: THE CENTERED EMPTY STATE
     * -------------------------------------------------------------------------
     */
    .adm-empty-centered-container {
        width: 100%;
        min-height: 450px;
        display: flex;
        flex-direction: column;
        align-items: center;    
        justify-content: center; 
        text-align: center;
        background: var(--adm-card);
        border: 3px dashed var(--adm-border);
        border-radius: 40px;
        padding: 60px 30px;
        margin: 40px 0;
        animation: scaleIn 0.5s ease-out;
    }

    .adm-empty-icon-wrap {
        width: 120px;
        height: 120px;
        background: rgba(16, 185, 129, 0.1);
        color: var(--adm-success);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        margin-bottom: 30px;
        animation: pulseGreen 2s infinite;
    }

    .adm-empty-title {
        font-size: 2rem;
        font-weight: 900;
        color: var(--adm-text);
        margin-bottom: 15px;
    }

    .adm-empty-subtitle {
        color: var(--adm-text-muted);
        font-size: 1.1rem;
        font-weight: 600;
        max-width: 400px;
    }

    /**
     * SECTION: ANIMATIONS & RESPONSIVE
     * -------------------------------------------------------------------------
     */
    @keyframes fadeInPage { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes scaleIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    @keyframes pulseGreen { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); } 70% { box-shadow: 0 0 0 20px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }

    @media (max-width: 992px) {
        .wtb-review-card { grid-template-columns: 160px 1fr; }
        .adm-action-btns { grid-column: 1 / -1; flex-direction: row; }
        .btn-adm-action { flex: 1; }
    }

    @media (max-width: 768px) {
        .adm-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
        .wtb-review-card { grid-template-columns: 1fr; text-align: center; }
        .adm-review-img-wrap { max-width: 250px; margin: 0 auto; }
        .adm-cat-tag { margin: 0 auto; }
        .adm-user-info-row { justify-content: center; }
        .adm-action-btns { flex-direction: column; }
    }

    .spacer-md { height: 50px; width: 100%; }
    .utility-full-width { width: 100%; }
</style>

<div class="admin-main-wrapper">
    
    <header class="adm-header-flex">
        <div class="adm-page-title">
            <h1><i class="fas fa-exchange-alt me-2"></i> อนุมัติประกาศแลกเปลี่ยน</h1>
            <p>ตรวจสอบความเหมาะสมและอนุมัติการแลกเปลี่ยนสิ่งของ (Barter System)</p>
        </div>
        <a href="admin_dashboard.php" class="btn-back-dash">
            <i class="fas fa-th-large"></i> กลับเมนูแอดมิน
        </a>
    </header>

    <div class="utility-full-width mb-4">
        <?php echo displayFlashMessage(); ?>
    </div>

    <main class="adm-content-area">
        <?php if (count($pending_posts) > 0): ?>
            
            <div class="mb-4">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                    <i class="fas fa-clock me-2"></i> รอดำเนินการ: <?= count($pending_posts) ?> รายการ
                </span>
            </div>

            <?php foreach ($pending_posts as $post): 
                $avatar_img = !empty($post['profile_img']) 
                            ? "../assets/images/profiles/" . $post['profile_img'] 
                            : "../assets/images/profiles/default_profile.png";
            ?>
                <section class="wtb-review-card">
                    
                    <div class="adm-review-img-wrap">
                        <?php if ($post['image_url']): ?>
                            <img src="../assets/images/barter/<?= htmlspecialchars($post['image_url']) ?>" alt="Barter Image">
                        <?php else: ?>
                            <div class="adm-no-img-placeholder">
                                <i class="fas fa-box-open fa-3x mb-2 opacity-25"></i>
                                <span class="fw-bold">ไม่มีรูปประกอบ</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="adm-post-meta">
                        <span class="adm-cat-tag">
                            <i class="fas fa-cube me-1"></i> มีของ: <?= htmlspecialchars($post['item_have']) ?>
                        </span>
                        <h2 class="adm-post-title"><?= htmlspecialchars($post['title']) ?></h2>
                        <p class="adm-post-desc"><?= nl2br(htmlspecialchars($post['description'])) ?></p>
                        
                        <div class="adm-user-info-row">
                            <img src="<?= $avatar_img ?>" class="adm-user-avatar">
                            <div>
                                <div class="fw-bold" style="font-size: 0.9rem;"><?= htmlspecialchars($post['fullname']) ?></div>
                                <div class="text-muted" style="font-size: 0.8rem;">
                                    สถานะ: <span class="text-primary fw-bold"><?= htmlspecialchars($post['user_role']) ?></span>
                                    <span class="mx-2">|</span>
                                    <i class="far fa-calendar-alt"></i> <?= date('d M Y H:i', strtotime($post['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="adm-action-btns">
                        <form action="" method="POST" class="utility-full-width">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="owner_id" value="<?= $post['user_id'] ?>">
                            <input type="hidden" name="item_have" value="<?= htmlspecialchars($post['item_have']) ?>">
                            
                            <button type="submit" name="action" value="approve" class="btn-adm-action btn-approve-post mb-3">
                                <i class="fas fa-check-circle"></i> อนุมัติประกาศ
                            </button>
                            
                            <button type="submit" name="action" value="reject" class="btn-adm-action btn-reject-post" 
                                    onclick="return confirm('🚨 ยืนยันการปฏิเสธประกาศแลกเปลี่ยนนี้?')">
                                <i class="fas fa-times-circle"></i> ปฏิเสธรายการ
                            </button>
                        </form>
                    </div>

                </section>
            <?php endforeach; ?>

        <?php else: ?>
            
            <section class="adm-empty-centered-container">
                <div class="adm-empty-icon-wrap">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="adm-empty-title">เคลียร์เรียบร้อย!</h2>
                <p class="adm-empty-subtitle">
                    ขณะนี้ไม่มีประกาศแลกเปลี่ยนค้างรอการอนุมัติในระบบ 
                    คุณตรวจสอบรายการทั้งหมดครบถ้วนแล้ว
                </p>
            </section>

        <?php endif; ?>
    </main>

    <div class="spacer-md"></div>
</div>

<?php 
/**
 * การทำงานส่วนที่ 5: ปิดท้ายไฟล์และ Render Footer
 * -------------------------------------------------------------------------
 */
require_once '../includes/footer.php'; 
?>