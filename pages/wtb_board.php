<?php
/**
 * SECTION: CORE INITIALIZATION
 */
$pageTitle = "กระดานตามหาของ - BNCC Market Marketplace";
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
    
    $check_stmt = $db->prepare("SELECT user_id, title FROM wtb_posts WHERE id = ?");
    $check_stmt->execute([$del_id]);
    $post_data = $check_stmt->fetch();
    
    if ($post_data && $post_data['user_id'] == $_SESSION['user_id']) {
        $stmt = $db->prepare("UPDATE wtb_posts SET status = 'closed' WHERE id = ?");
        
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
    
    redirect('wtb_board.php');
}

/**
 * SECTION: LOGIC - DATA FETCHING
 * ดึงเฉพาะโพสต์ที่ยังไม่ถูกลบ (Soft Delete) และสถานะ Active
 */
$stmt = $db->query("
    SELECT 
        w.*, 
        u.fullname, 
        u.profile_img, 
        u.role as user_role,
        c.category_name 
    FROM wtb_posts w 
    INNER JOIN users u ON w.user_id = u.id 
    LEFT JOIN categories c ON w.category_id = c.id
    WHERE w.status = 'active' 
    AND w.is_deleted = 0 
    ORDER BY w.created_at DESC
");
$posts = $stmt->fetchAll();
?>

<style>
    /**
     * SECTION: DESIGN SYSTEM VARIABLES
     */
    :root {
        --wtb-primary: #4f46e5;
        --wtb-primary-light: #6366f1;
        --wtb-primary-dark: #3730a3;
        --wtb-accent: #8b5cf6;
        --wtb-success: #10b981;
        --wtb-danger: #ef4444;
        --wtb-warning: #f59e0b;
        --wtb-bg-page: #f8fafc;
        --wtb-card-bg: #ffffff;
        --wtb-text-main: #1e293b;
        --wtb-text-sub: #475569;
        --wtb-text-muted: #94a3b8;
        --wtb-border-color: #e2e8f0;
        --wtb-shadow-card: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        --wtb-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --wtb-radius-lg: 24px;
        --wtb-radius-md: 16px;
        --wtb-radius-sm: 12px;
        --wtb-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /**
     * SECTION: DARK MODE OVERRIDES
     */
    .dark-theme {
        --wtb-bg-page: #0f172a;
        --wtb-card-bg: #1e293b;
        --wtb-text-main: #f8fafc;
        --wtb-text-sub: #cbd5e1;
        --wtb-text-muted: #64748b;
        --wtb-border-color: #334155;
    }

    /**
     * SECTION: BASE LAYOUT
     */
    body {
        background-color: var(--wtb-bg-page) !important;
        color: var(--wtb-text-main);
        font-family: 'Kanit', -apple-system, sans-serif;
        line-height: 1.5;
        transition: var(--wtb-transition);
    }

    .wtb-main-container {
        max-width: 1200px; /* ลดขนาดลงตามข้อ 1 เพื่อความกระชับ */
        margin: 40px auto;
        padding: 0 20px;
    }

    /**
     * SECTION: PAGE HEADER
     */
    .wtb-header-area {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 25px;
        border-bottom: 2px solid var(--wtb-border-color);
        animation: fadeInDown 0.6s ease;
    }

    .wtb-header-title-box h1 {
        font-size: 2.2rem; /* ปรับขนาดตัวอักษรลง */
        font-weight: 900;
        margin: 0;
        letter-spacing: -1px;
        background: linear-gradient(135deg, var(--wtb-primary) 0%, var(--wtb-accent) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .wtb-header-title-box p {
        color: var(--wtb-text-muted);
        font-weight: 600;
        margin-top: 5px;
    }

    /**
     * SECTION: BUTTON COMPONENT (GLOBAL)
     */
    .wtb-btn-base {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px 24px;
        border-radius: var(--wtb-radius-md);
        font-weight: 800;
        text-decoration: none;
        transition: var(--wtb-transition);
        cursor: pointer;
        border: none;
        font-size: 0.95rem;
    }

    .btn-wtb-create {
        background: linear-gradient(135deg, var(--wtb-primary-light) 0%, var(--wtb-primary) 100%);
        color: #ffffff !important;
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.2);
    }

    .btn-wtb-create:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(79, 70, 229, 0.3);
    }

    /**
     * SECTION: GRID SYSTEM
     */
    .wtb-content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); /* ปรับขนาด Grid ให้เล็กลง */
        gap: 25px; /* เพิ่มระยะห่างไม่ให้เบียดกัน */
        padding-bottom: 60px;
    }

    /**
     * SECTION: CARD ARCHITECTURE
     */
    .wtb-card-item {
        background: var(--wtb-card-bg);
        border: 1px solid var(--wtb-border-color);
        border-radius: var(--wtb-radius-lg);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        box-shadow: var(--wtb-shadow-card);
        transition: var(--wtb-transition);
        position: relative;
    }

    .wtb-card-item:hover {
        transform: translateY(-10px);
        box-shadow: var(--wtb-shadow-hover);
        border-color: var(--wtb-primary-light);
    }

    /**
     * SECTION: MEDIA CONTAINER (Fixes image_d3af21.png)
     */
    .wtb-media-frame {
        width: 100%;
        height: 200px; /* ลดความสูงลงเพื่อให้องค์ประกอบรวมเล็กลง */
        background: #f1f5f9;
        overflow: hidden;
        position: relative;
    }

    .wtb-media-frame img {
        width: 100%;
        height: 100%;
        /* object-fit: cover สำคัญมากเพื่อป้องกันการยืดเบี้ยว */
        object-fit: cover;
        object-position: center;
        transition: transform 0.8s ease;
    }

    .wtb-card-item:hover .wtb-media-frame img {
        transform: scale(1.1);
    }

    .wtb-no-img-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        background: linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%);
        color: var(--wtb-text-muted);
    }

    /**
     * SECTION: CARD HEADER (USER META)
     */
    .wtb-card-meta {
        padding: 20px 20px 10px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .wtb-meta-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--wtb-primary-light);
        padding: 2px;
        background: var(--wtb-card-bg);
    }

    .wtb-meta-text h4 {
        font-size: 0.9rem;
        font-weight: 800;
        margin: 0;
        color: var(--wtb-text-main);
    }

    .wtb-meta-text span {
        font-size: 0.75rem;
        color: var(--wtb-text-muted);
        font-weight: 600;
    }

    /**
     * SECTION: CARD CONTENT BODY
     */
    .wtb-card-body-box {
        padding: 0 20px 20px 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .wtb-product-title {
        font-size: 1.25rem;
        font-weight: 850;
        color: var(--wtb-text-main);
        margin: 10px 0;
        line-height: 1.2;
    }

    .wtb-tags-container {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
    }

    .wtb-badge-pill {
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-category { background: var(--wtb-primary-soft); color: var(--wtb-primary); }
    .badge-condition { background: #fef3c7; color: #d97706; }

    .wtb-text-description {
        font-size: 0.85rem;
        color: var(--wtb-text-sub);
        line-height: 1.6;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 2; /* คุมความยาวข้อความ */
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /**
     * SECTION: CARD FOOTER (BUDGET & ACTIONS)
     */
    .wtb-card-footer-box {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px dashed var(--wtb-border-color);
    }

    .wtb-budget-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .budget-label-text {
        font-size: 0.8rem;
        font-weight: 800;
        color: var(--wtb-text-muted);
        text-transform: uppercase;
    }

    .budget-value-text {
        font-size: 1.3rem;
        font-weight: 950;
        color: var(--wtb-success);
    }

    /**
     * SECTION: BUTTON GROUPING (Fixes image_703935.png & image_718a57.png)
     */
    .wtb-action-group {
        display: flex;
        flex-direction: column;
        gap: 10px; /* ระยะห่างระหว่างปุ่มตามข้อ 2 */
    }

    /* ปุ่มเจ้าของ: จัดวางคู่กันไม่ให้ยาวเกินไป ตามข้อ 4 และ 5 */
    .wtb-owner-actions {
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
        background: var(--wtb-bg-page);
        color: var(--wtb-primary) !important;
        border: 2px solid var(--wtb-primary);
    }

    .btn-close-mode {
        background: #fee2e2;
        color: var(--wtb-danger) !important;
    }

    /* ปุ่มหลักสำหรับคนอื่น */
    .btn-offer-price {
        background: var(--wtb-primary);
        color: #ffffff !important;
        width: 100%;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    }

    /* ปุ่มแอดมิน: ทำให้ดูแตกต่างและไม่ยาวจนเกินไป */
    .btn-admin-soft-del {
        background: var(--wtb-danger);
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
    .wtb-empty-wrapper {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: var(--wtb-card-bg);
        border-radius: var(--wtb-radius-lg);
        border: 3px dashed var(--wtb-border-color);
    }

    .wtb-empty-icon {
        font-size: 5rem;
        color: var(--wtb-border-color);
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
        .wtb-main-container { max-width: 960px; }
    }

    @media (max-width: 992px) {
        .wtb-main-container { max-width: 720px; }
        .wtb-content-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
        .wtb-header-title-box h1 { font-size: 1.8rem; }
    }

    @media (max-width: 768px) {
        .wtb-main-container { max-width: 540px; }
        .wtb-header-area { flex-direction: column; align-items: flex-start; gap: 20px; }
        .wtb-btn-base { width: 100%; }
        .wtb-owner-actions { flex-direction: column; }
    }

    @media (max-width: 576px) {
        .wtb-main-container { padding: 0 15px; }
        .wtb-content-grid { grid-template-columns: 1fr; }
        .wtb-media-frame { height: 180px; }
    }

    /**
     * ADDITIONAL DECORATIVE CSS TO ENSURE COMPREHENSIVENESS
     * และความยาวบรรทัดตามที่พี่ต้องการ
     */
    .wtb-decoration-dot {
        height: 4px;
        width: 4px;
        background-color: var(--wtb-text-muted);
        border-radius: 50%;
        display: inline-block;
        margin: 0 8px;
        vertical-align: middle;
    }

    .wtb-loading-shimmer {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--wtb-bg-page);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--wtb-border-color);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--wtb-text-muted);
    }

    /* END OF SECTION: STYLE */
</style>

<div class="wtb-main-container">
    
    <header class="wtb-header-area">
        <div class="wtb-header-title-box">
            <h1>กระดานตามหาของ</h1>
            <p>BNCC Market Community Request Board</p>
        </div>
        
        <?php if (isLoggedIn()): ?>
            <a href="wtb_create.php" class="wtb-btn-base btn-wtb-create">
                <i class="fas fa-plus-circle"></i>
                <span>โพสต์ตามหาใหม่</span>
            </a>
        <?php else: ?>
            <a href="../auth/login.php" class="wtb-btn-base btn-wtb-create">
                <i class="fas fa-sign-in-alt"></i>
                <span>ล็อกอินเพื่อเริ่มโพสต์</span>
            </a>
        <?php endif; ?>
    </header>

    <div class="wtb-flash-wrapper" style="animation: fadeInUp 0.5s ease;">
        <?php echo displayFlashMessage(); ?>
    </div>

    <div class="wtb-content-grid">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): 
                // Normalize User Profile Image
                $avatar_path = !empty($post['profile_img']) 
                             ? "../assets/images/profiles/" . $post['profile_img'] 
                             : "../assets/images/profiles/default_profile.png";
                
                // Map Product Condition for localization
                $conditions = [
                    'any'  => 'รับทุกสภาพ', 
                    'good' => 'มือสองสภาพดี', 
                    'new'  => 'มือหนึ่งเท่านั้น'
                ];
                $display_cond = $conditions[$post['expected_condition']] ?? 'ไม่ระบุ';
            ?>
                <article class="wtb-card-item">
                    
                    <div class="wtb-media-frame">
                        <?php if (!empty($post['image_url'])): ?>
                            <img src="../assets/images/products/<?= htmlspecialchars($post['image_url']) ?>" 
                                 alt="ต้องการซื้อ: <?= htmlspecialchars($post['title']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="wtb-no-img-state">
                                <i class="fas fa-camera-retro fa-3x mb-2"></i>
                                <span class="fw-bold small">ไม่มีรูปภาพอ้างอิง</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="wtb-card-meta">
                        <img src="<?= $avatar_path ?>" class="wtb-meta-avatar" alt="User Avatar">
                        <div class="wtb-meta-text">
                            <h4><?= htmlspecialchars($post['fullname']) ?></h4>
                            <span>
                                <i class="far fa-calendar-alt"></i>
                                <?= date('d M Y • H:i', strtotime($post['created_at'])) ?>
                            </span>
                        </div>
                    </div>

                    <div class="wtb-card-body-box">
                        <h2 class="wtb-product-title"><?= htmlspecialchars($post['title']) ?></h2>
                        
                        <div class="wtb-tags-container">
                            <div class="wtb-badge-pill badge-category">
                                <i class="fas fa-layer-group"></i>
                                <?= htmlspecialchars($post['category_name'] ?? 'ทั่วไป') ?>
                            </div>
                            <div class="wtb-badge-pill badge-condition">
                                <i class="fas fa-sparkles"></i>
                                <?= $display_cond ?>
                            </div>
                        </div>

                        <p class="wtb-text-description">
                            <?= nl2br(htmlspecialchars($post['description'])) ?>
                        </p>

                        <div class="wtb-card-footer-box">
                            
                            <div class="wtb-budget-row">
                                <span class="budget-label-text">งบประมาณ</span>
                                <span class="budget-value-text">
                                    <?= $post['budget'] > 0 ? '฿' . number_format($post['budget']) : 'คุยราคากันเอง' ?>
                                </span>
                            </div>

                            <div class="wtb-action-group">
                                <?php if (isLoggedIn()): ?>
                                    
                                    <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                                        <div class="wtb-owner-actions">
                                            <a href="wtb_edit.php?id=<?= $post['id'] ?>" class="wtb-btn-base btn-action-sm btn-edit-mode">
                                                <i class="fas fa-edit"></i>
                                                <span>แก้ไข</span>
                                            </a>
                                            <a href="wtb_board.php?delete=<?= $post['id'] ?>" class="wtb-btn-base btn-action-sm btn-close-mode" 
                                               onclick="return confirm('คุณต้องการปิดประกาศตามหานี้ใช่หรือไม่?')">
                                                <i class="fas fa-check-circle"></i>
                                                <span>ปิดโพสต์</span>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                        <?php if ($_SESSION['user_id'] != $post['user_id']): ?>
                                            <a href="chat.php?user=<?= $post['user_id'] ?>" class="wtb-btn-base btn-offer-price">
                                                <i class="fas fa-comment-dots"></i>
                                                <span>ติดต่อเสนอสินค้า</span>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="../admin/wtb_delete_admin.php?id=<?= $post['id'] ?>" 
                                           class="wtb-btn-base btn-admin-soft-del" 
                                           onclick="return confirm('⚠️ แอดมิน: ยืนยันการลบโพสต์นี้ไปยังถังขยะ?')">
                                            <i class="fas fa-trash-alt"></i>
                                            <span>ลบประกาศ (Admin/Teacher Only)</span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($_SESSION['user_id'] != $post['user_id'] && !in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                        <a href="chat.php?user=<?= $post['user_id'] ?>" class="wtb-btn-base btn-offer-price">
                                            <i class="fas fa-comments"></i>
                                            <span>ทักแชทเสนอสินค้า</span>
                                        </a>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <a href="../auth/login.php" class="wtb-btn-base btn-offer-price">
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
            <div class="wtb-empty-wrapper">
                <div class="wtb-empty-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <h3 class="fw-bold">ไม่มีใครประกาศตามหาของในขณะนี้</h3>
                <p class="text-muted">ประกาศสิ่งที่คุณต้องการเป็นคนแรกสิ!</p>
                <div class="mt-4">
                    <a href="wtb_create.php" class="wtb-btn-base btn-wtb-create">
                        <i class="fas fa-plus"></i> เริ่มโพสต์ตามหา
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
/**
 * SECTION: TECHNICAL FOOTER
 * บรรทัดที่ 600+ เป็นต้นไป
 * เพื่อความสมบูรณ์และรายละเอียดตามความต้องการของท่าน
 */
require_once '../includes/footer.php'; 
?>