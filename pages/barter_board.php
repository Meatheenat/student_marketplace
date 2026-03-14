<?php
/**
 * SECTION: CORE INITIALIZATION
 */
$pageTitle = "กระดานแลกเปลี่ยนของ - BNCC Market Marketplace";
require_once '../includes/header.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบเพื่อเข้าใช้งานส่วนนี้";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}
$db = getDB();

/**
 * SECTION: LOGIC - POST CLOSURE HANDLER
 * สำหรับเจ้าของประกาศปิดสถานะเป็น Closed
 */
if (isset($_GET['delete']) && isLoggedIn()) {
    $del_id = (int)$_GET['delete'];
    
    $check_stmt = $db->prepare("SELECT user_id, title FROM barter_posts WHERE id = ?");
    $check_stmt->execute([$del_id]);
    $post_data = $check_stmt->fetch();
    
    if ($post_data && $post_data['user_id'] == $_SESSION['user_id']) {
        // อัปเดตสถานะเป็น closed
        $stmt = $db->prepare("UPDATE barter_posts SET status = 'closed' WHERE id = ?");
        
        if ($stmt->execute([$del_id])) {
            $_SESSION['flash_message'] = "ปิดประกาศ '" . htmlspecialchars($post_data['title']) . "' เรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "เกิดข้อผิดพลาดทางระบบ กรุณาลองใหม่อีกครั้ง";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "คุณไม่มีสิทธิ์ในการจัดการประกาศนี้";
        $_SESSION['flash_type'] = "danger";
    }
    
    redirect('barter_board.php');
}

/**
 * SECTION: LOGIC - DATA FETCHING
 * ดึงเฉพาะโพสต์ที่ได้รับการอนุมัติแล้ว (status = 'open')
 */
$stmt = $db->query("
    SELECT 
        b.*, 
        u.fullname, 
        u.profile_img, 
        u.role as user_role
    FROM barter_posts b 
    INNER JOIN users u ON b.user_id = u.id 
    WHERE b.status = 'open' 
    ORDER BY b.created_at DESC
");
$posts = $stmt->fetchAll();
?>

<style>
    /**
     * SECTION: DESIGN SYSTEM VARIABLES
     */
    :root {
        --btr-primary: #4f46e5;
        --btr-primary-light: #6366f1;
        --btr-primary-dark: #3730a3;
        --btr-accent: #8b5cf6;
        --btr-success: #10b981;
        --btr-danger: #ef4444;
        --btr-warning: #f59e0b;
        --btr-bg-page: #f8fafc;
        --btr-card-bg: #ffffff;
        --btr-text-main: #1e293b;
        --btr-text-sub: #475569;
        --btr-text-muted: #94a3b8;
        --btr-border-color: #e2e8f0;
        --btr-shadow-card: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        --btr-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --btr-radius-lg: 24px;
        --btr-radius-md: 16px;
        --btr-radius-sm: 12px;
        --btr-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /**
     * SECTION: DARK MODE OVERRIDES
     */
    .dark-theme {
        --btr-bg-page: #0f172a;
        --btr-card-bg: #1e293b;
        --btr-text-main: #f8fafc;
        --btr-text-sub: #cbd5e1;
        --btr-text-muted: #64748b;
        --btr-border-color: #334155;
    }

    /**
     * SECTION: BASE LAYOUT
     */
    body {
        background-color: var(--btr-bg-page) !important;
        color: var(--btr-text-main);
        font-family: 'Kanit', -apple-system, sans-serif;
        line-height: 1.5;
        transition: var(--btr-transition);
    }

    .btr-main-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    /**
     * SECTION: PAGE HEADER
     */
    .btr-header-area {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 25px;
        border-bottom: 2px solid var(--btr-border-color);
        animation: fadeInDown 0.6s ease;
    }

    .btr-header-title-box h1 {
        font-size: 2.2rem;
        font-weight: 900;
        margin: 0;
        letter-spacing: -1px;
        background: linear-gradient(135deg, var(--btr-primary) 0%, var(--btr-accent) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .btr-header-title-box p {
        color: var(--btr-text-muted);
        font-weight: 600;
        margin-top: 5px;
    }

    /**
     * SECTION: BUTTON COMPONENT (GLOBAL)
     */
    .btr-btn-base {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px 24px;
        border-radius: var(--btr-radius-md);
        font-weight: 800;
        text-decoration: none;
        transition: var(--btr-transition);
        cursor: pointer;
        border: none;
        font-size: 0.95rem;
    }

    .btn-btr-create {
        background: linear-gradient(135deg, var(--btr-primary-light) 0%, var(--btr-primary) 100%);
        color: #ffffff !important;
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.2);
    }

    .btn-btr-create:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(79, 70, 229, 0.3);
    }

    /**
     * SECTION: GRID SYSTEM
     */
    .btr-content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        padding-bottom: 60px;
    }

    /**
     * SECTION: CARD ARCHITECTURE
     */
    .btr-card-item {
        background: var(--btr-card-bg);
        border: 1px solid var(--btr-border-color);
        border-radius: var(--btr-radius-lg);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        box-shadow: var(--btr-shadow-card);
        transition: var(--btr-transition);
        position: relative;
    }

    .btr-card-item:hover {
        transform: translateY(-10px);
        box-shadow: var(--btr-shadow-hover);
        border-color: var(--btr-primary-light);
    }

    /**
     * SECTION: MEDIA CONTAINER
     */
    .btr-media-frame {
        width: 100%;
        height: 200px;
        background: #f1f5f9;
        overflow: hidden;
        position: relative;
    }

    .btr-media-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transition: transform 0.8s ease;
    }

    .btr-card-item:hover .btr-media-frame img {
        transform: scale(1.1);
    }

    .btr-no-img-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        background: linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%);
        color: var(--btr-text-muted);
    }

    /**
     * SECTION: CARD HEADER (USER META)
     */
    .btr-card-meta {
        padding: 20px 20px 10px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .btr-meta-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--btr-primary-light);
        padding: 2px;
        background: var(--btr-card-bg);
    }

    .btr-meta-text h4 {
        font-size: 0.9rem;
        font-weight: 800;
        margin: 0;
        color: var(--btr-text-main);
    }

    .btr-meta-text span {
        font-size: 0.75rem;
        color: var(--btr-text-muted);
        font-weight: 600;
    }

    /**
     * SECTION: CARD CONTENT BODY
     */
    .btr-card-body-box {
        padding: 0 20px 20px 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .btr-product-title {
        font-size: 1.25rem;
        font-weight: 850;
        color: var(--btr-text-main);
        margin: 10px 0;
        line-height: 1.2;
    }

    .btr-tags-container {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
    }

    .btr-badge-pill {
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-category { background: rgba(79, 70, 229, 0.1); color: var(--btr-primary); }

    .btr-text-description {
        font-size: 0.85rem;
        color: var(--btr-text-sub);
        line-height: 1.6;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /**
     * SECTION: CARD FOOTER (BUDGET & ACTIONS)
     */
    .btr-card-footer-box {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px dashed var(--btr-border-color);
    }

    .btr-budget-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .budget-label-text {
        font-size: 0.8rem;
        font-weight: 800;
        color: var(--btr-text-muted);
        text-transform: uppercase;
    }

    .budget-value-text {
        font-size: 1rem;
        font-weight: 950;
        color: var(--btr-primary);
    }

    /**
     * SECTION: BUTTON GROUPING
     */
    .btr-action-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .btr-owner-actions {
        display: flex;
        gap: 10px;
        width: 100%;
    }

    .btn-action-sm {
        flex: 1;
        padding: 10px;
        font-size: 0.85rem;
    }

    .btn-edit-mode {
        background: var(--btr-bg-page);
        color: var(--btr-primary) !important;
        border: 2px solid var(--btr-primary);
    }

    .btn-close-mode {
        background: #fee2e2;
        color: var(--btr-danger) !important;
    }

    .btn-offer-price {
        background: var(--btr-primary);
        color: #ffffff !important;
        width: 100%;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    }

    .btn-admin-soft-del {
        background: var(--btr-danger);
        color: #ffffff !important;
        width: 100%;
        font-size: 0.85rem;
        padding: 10px;
        opacity: 0.9;
    }

    .btn-admin-soft-del:hover {
        opacity: 1;
        filter: brightness(1.1);
    }

    /**
     * SECTION: EMPTY STATE
     */
    .btr-empty-wrapper {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: var(--btr-card-bg);
        border-radius: var(--btr-radius-lg);
        border: 3px dashed var(--btr-border-color);
    }

    .btr-empty-icon {
        font-size: 5rem;
        color: var(--btr-border-color);
        margin-bottom: 20px;
    }

    /**
     * SECTION: KEYFRAME ANIMATIONS
     */
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /**
     * SECTION: RESPONSIVE TIERS
     */
    @media (max-width: 1200px) {
        .btr-main-container { max-width: 960px; }
    }

    @media (max-width: 992px) {
        .btr-main-container { max-width: 720px; }
        .btr-content-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
        .btr-header-title-box h1 { font-size: 1.8rem; }
    }

    @media (max-width: 768px) {
        .btr-main-container { max-width: 540px; }
        .btr-header-area { flex-direction: column; align-items: flex-start; gap: 20px; }
        .btr-btn-base { width: 100%; }
        .btr-owner-actions { flex-direction: column; }
    }

    @media (max-width: 576px) {
        .btr-main-container { padding: 0 15px; }
        .btr-content-grid { grid-template-columns: 1fr; }
        .btr-media-frame { height: 180px; }
    }

    .btr-decoration-dot {
        height: 4px;
        width: 4px;
        background-color: var(--btr-text-muted);
        border-radius: 50%;
        display: inline-block;
        margin: 0 8px;
        vertical-align: middle;
    }

</style>

<div class="btr-main-container">
    
    <header class="btr-header-area">
        <div class="btr-header-title-box">
            <h1>กระดานแลกเปลี่ยนสิ่งของ</h1>
            <p>BNCC Market Barter & Exchange Board</p>
        </div>
        
        <?php if (isLoggedIn()): ?>
            <a href="post_barter.php" class="btr-btn-base btn-btr-create">
                <i class="fas fa-plus-circle"></i>
                <span>โพสต์แลกเปลี่ยนใหม่</span>
            </a>
        <?php else: ?>
            <a href="../auth/login.php" class="btr-btn-base btn-btr-create">
                <i class="fas fa-sign-in-alt"></i>
                <span>ล็อกอินเพื่อเริ่มโพสต์</span>
            </a>
        <?php endif; ?>
    </header>

    <div class="btr-flash-wrapper" style="animation: fadeInUp 0.5s ease;">
        <?php echo displayFlashMessage(); ?>
    </div>

    <div class="btr-content-grid">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): 
                // Normalize User Profile Image
                $avatar_path = !empty($post['profile_img']) 
                             ? "../assets/images/profiles/" . $post['profile_img'] 
                             : "../assets/images/profiles/default_profile.png";
            ?>
                <article class="btr-card-item">
                    
                    <div class="btr-media-frame">
                        <?php if (!empty($post['image_url'])): ?>
                            <img src="../assets/images/barter/<?= htmlspecialchars($post['image_url']) ?>" 
                                 alt="แลกเปลี่ยน: <?= htmlspecialchars($post['title']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="btr-no-img-state">
                                <i class="fas fa-camera-retro fa-3x mb-2"></i>
                                <span class="fw-bold small">ไม่มีรูปภาพอ้างอิง</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="btr-card-meta">
                        <img src="<?= $avatar_path ?>" class="btr-meta-avatar" alt="User Avatar">
                        <div class="btr-meta-text">
                            <h4><?= htmlspecialchars($post['fullname']) ?></h4>
                            <span>
                                <i class="far fa-calendar-alt"></i>
                                <?= date('d M Y • H:i', strtotime($post['created_at'])) ?>
                            </span>
                        </div>
                    </div>

                    <div class="btr-card-body-box">
                        <h2 class="btr-product-title"><?= htmlspecialchars($post['title']) ?></h2>
                        
                        <div class="btr-tags-container">
                            <div class="btr-badge-pill badge-category">
                                <i class="fas fa-box-open"></i> มี: <?= htmlspecialchars($post['item_have']) ?>
                            </div>
                        </div>

                        <p class="btr-text-description">
                            <?= nl2br(htmlspecialchars($post['description'])) ?>
                        </p>

                        <div class="btr-card-footer-box">
                            
                            <div class="btr-budget-row">
                                <span class="budget-label-text">ต้องการแลกกับ</span>
                                <span class="budget-value-text">
                                    <?= htmlspecialchars($post['item_want']) ?>
                                </span>
                            </div>

                            <div class="btr-action-group">
                                <?php if (isLoggedIn()): ?>
                                    
                                    <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                                        <div class="btr-owner-actions">
                                            <a href="../pages/edit_barter.php?id=<?= $post['id'] ?>" class="btr-btn-base btn-action-sm btn-edit-mode">
                                                <i class="fas fa-edit"></i>
                                                <span>แก้ไข</span>
                                            </a>
                                            <a href="barter_board.php?delete=<?= $post['id'] ?>" class="btr-btn-base btn-action-sm btn-close-mode" 
                                               onclick="return confirm('คุณต้องการปิดประกาศแลกเปลี่ยนนี้ใช่หรือไม่?')">
                                                <i class="fas fa-check-circle"></i>
                                                <span>ปิดโพสต์</span>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                        <?php if ($_SESSION['user_id'] != $post['user_id']): ?>
                                            <a href="chat.php?user=<?= $post['user_id'] ?>" class="btr-btn-base btn-offer-price">
                                                <i class="fas fa-comment-dots"></i>
                                                <span>ทักแชทเสนอแลกเปลี่ยน</span>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="../admin/barter_delete_admin.php?id=<?= $post['id'] ?>" 
                                           class="btr-btn-base btn-admin-soft-del" 
                                           onclick="return confirm('⚠️ แอดมิน: ยืนยันการปิดหรือลบโพสต์แลกเปลี่ยนนี้?')">
                                            <i class="fas fa-trash-alt"></i>
                                            <span>ลบประกาศ (Admin/Teacher Only)</span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($_SESSION['user_id'] != $post['user_id'] && !in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                        <a href="chat.php?user=<?= $post['user_id'] ?>" class="btr-btn-base btn-offer-price">
                                            <i class="fas fa-comments"></i>
                                            <span>ทักแชทเสนอสิ่งของ</span>
                                        </a>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <a href="../auth/login.php" class="btr-btn-base btn-offer-price">
                                        <i class="fas fa-lock"></i>
                                        <span>เข้าสู่ระบบเพื่อติดต่อ</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="btr-empty-wrapper">
                <div class="btr-empty-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h3 class="fw-bold">ยังไม่มีประกาศแลกเปลี่ยนในขณะนี้</h3>
                <p class="text-muted">นำของที่คุณไม่ได้ใช้ มาเริ่มเปิดประเดิมลงแลกเปลี่ยนกันสิ!</p>
                <div class="mt-4">
                    <a href="post_barter.php" class="btr-btn-base btn-btr-create">
                        <i class="fas fa-plus"></i> เริ่มโพสต์แลกเปลี่ยน
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
/**
 * SECTION: TECHNICAL FOOTER
 */
require_once '../includes/footer.php'; 
?>