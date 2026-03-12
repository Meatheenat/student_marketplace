<?php
/**
 * =========================================================================
 * BNCC MARKETPLACE - THE ULTIMATE WTB BOARD SYSTEM
 * =========================================================================
 * Version: 6.0 (Enterprise Edition)
 * Last Updated: March 12, 2026
 * * DEVELOPMENT LOG & SPECIFICATIONS:
 * 1. SQL Engine: High-performance JOIN with is_deleted and status filtering.
 * 2. UX Logic: Multi-role permission checking (Owner, Admin, Teacher, Guest).
 * 3. UI Design: Pixel-Perfect Grid with fixed aspect ratio image processing.
 * 4. Soft Delete: Integrated with ../admin/wtb_delete_admin.php.
 * 5. Responsiveness: Fully dynamic layout for Mobile, Tablet, and Desktop.
 * =========================================================================
 */

$pageTitle = "กระดานตามหาของ - BNCC Market Marketplace";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// --- ⚙️ DATABASE INITIALIZATION ---
$db = getDB();

/* * -------------------------------------------------------------------------
 * SECTION 1: ACTION HANDLERS (LOGIC)
 * -------------------------------------------------------------------------
 * จัดการการส่งค่าผ่าน URL (GET) เพื่อทำการปิดประกาศ (Status Update)
 */

if (isset($_GET['delete']) && isLoggedIn()) {
    $del_id = (int)$_GET['delete'];
    
    // 🛡️ Security Check: ดึงข้อมูลเจ้าของโพสต์เพื่อป้องกันการปลอมแปลง ID
    $check_stmt = $db->prepare("SELECT user_id, title FROM wtb_posts WHERE id = ?");
    $check_stmt->execute([$del_id]);
    $post_data = $check_stmt->fetch();
    
    if ($post_data && $post_data['user_id'] == $_SESSION['user_id']) {
        // อัปเดตสถานะเป็น closed (ปิดการมองเห็นปกติ แต่ข้อมูลยังอยู่)
        $stmt = $db->prepare("UPDATE wtb_posts SET status = 'closed' WHERE id = ?");
        
        if ($stmt->execute([$del_id])) {
            $_SESSION['flash_message'] = "ปิดประกาศ '" . htmlspecialchars($post_data['title']) . "' เรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "⚠️ ไม่สามารถปิดประกาศได้ในขณะนี้ กรุณาลองใหม่";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        // กรณีไม่ใช่เจ้าของโพสต์แต่พยายามใช้ฟังก์ชันปิดประกาศ
        $_SESSION['flash_message'] = "🛑 ตรวจพบความเสี่ยง: มึงไม่ใช่เจ้าของโพสต์ กรุณาหยุดการกระทำนี้";
        $_SESSION['flash_type'] = "danger";
    }
    
    // รีเฟรชหน้าบอร์ดเพื่ออัปเดตข้อมูลล่าสุด
    redirect('wtb_board.php');
}

/* * -------------------------------------------------------------------------
 * SECTION 2: DATA RETRIEVAL (OPTIMIZED SQL)
 * -------------------------------------------------------------------------
 * ดึงข้อมูลประกาศตามหาที่ยังมีสถานะ Active และ "ยังไม่ถูกลบ" (Soft Delete)
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
    :root {
        /* Palette Definition */
        --wtb-primary: #4f46e5;
        --wtb-primary-hover: #4338ca;
        --wtb-primary-soft: rgba(79, 70, 229, 0.08);
        --wtb-secondary: #8b5cf6;
        --wtb-success: #10b981;
        --wtb-danger: #ef4444;
        --wtb-warning: #f59e0b;
        --wtb-bg-main: #f8fafc;
        --wtb-card-bg: #ffffff;
        --wtb-text-title: #0f172a;
        --wtb-text-body: #475569;
        --wtb-text-muted: #94a3b8;
        --wtb-border: #e2e8f0;
        
        /* Shadows & Borders */
        --wtb-shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
        --wtb-shadow-md: 0 10px 15px -3px rgba(0,0,0,0.1);
        --wtb-shadow-lg: 0 25px 50px -12px rgba(0,0,0,0.15);
        --wtb-radius-card: 32px;
        --wtb-radius-btn: 18px;
    }

    /* 🌙 DARK THEME SUPPORT (Automated Implementation) */
    .dark-theme {
        --wtb-bg-main: #0b0f1a;
        --wtb-card-bg: #161e2e;
        --wtb-text-title: #f8fafc;
        --wtb-text-body: #cbd5e1;
        --wtb-border: #2d3748;
        --wtb-shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
    }

    body { 
        background-color: var(--wtb-bg-main) !important; 
        color: var(--wtb-text-body); 
        transition: all 0.4s ease; 
        font-family: 'Kanit', sans-serif;
    }

    /* --- 🏗️ LAYOUT WRAPPER --- */
    .wtb-page-container {
        max-width: 1400px;
        margin: 60px auto;
        padding: 0 30px;
    }

    /* --- 🏯 HEADER SECTION --- */
    .wtb-header-box {
        margin-bottom: 60px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 40px;
        border-bottom: 3px solid var(--wtb-border);
        animation: slideDown 0.8s ease;
    }

    .wtb-title-group h1 {
        font-size: 3.2rem;
        font-weight: 900;
        margin: 0;
        letter-spacing: -2px;
        background: linear-gradient(135deg, var(--wtb-primary) 0%, var(--wtb-secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .btn-create-wtb {
        background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
        color: #ffffff !important;
        padding: 18px 45px;
        border-radius: 22px;
        font-weight: 800;
        font-size: 1.1rem;
        text-decoration: none;
        box-shadow: 0 15px 30px rgba(67, 56, 202, 0.25);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn-create-wtb:hover {
        transform: translateY(-8px) scale(1.05);
        box-shadow: 0 20px 40px rgba(67, 56, 202, 0.35);
    }

    /* --- 📦 THE ULTIMATE GRID SYSTEM (Fixes Overlap) --- */
    .wtb-main-grid {
        display: grid;
        /* ใช้คำนวณขนาดการ์ดอัตโนมัติ ห้ามเดินทับกัน */
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); 
        gap: 40px;
        padding-bottom: 100px;
    }

    /* --- 💳 PREMIUM CARD DESIGN --- */
    .wtb-post-card {
        background: var(--wtb-card-bg);
        border: 2px solid var(--wtb-border);
        border-radius: var(--wtb-radius-card);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        box-shadow: var(--wtb-shadow-sm);
        transition: all 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
        min-width: 0; /* ป้องกัน Content ดัน Grid */
    }

    .wtb-post-card:hover {
        transform: translateY(-15px);
        border-color: var(--wtb-primary);
        box-shadow: var(--wtb-shadow-lg);
    }

    /* --- 🖼️ IMAGE PROCESSING: FIXED RATIO (Fixes Distortion) --- */
    .wtb-image-master-wrap {
        width: 100%;
        height: 320px; /* บังคับสัดส่วนพรีเมียม */
        background-color: var(--wtb-bg-main);
        overflow: hidden;
        position: relative;
        display: block;
        border-bottom: 1px solid var(--wtb-border);
    }

    .wtb-image-master-wrap img {
        width: 100%;
        height: 100%;
        /* 🎯 เคล็ดลับความงาม: object-fit cover จะตัดรูปให้พอดีกรอบ */
        object-fit: cover; 
        object-position: center;
        transition: transform 1.2s ease;
    }

    .wtb-post-card:hover .wtb-image-master-wrap img {
        transform: scale(1.15);
    }

    .wtb-no-img {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        background: linear-gradient(135deg, var(--wtb-bg-main) 0%, #e2e8f0 100%);
        color: var(--wtb-text-muted);
    }

    /* --- 📝 CARD CONTENT BODY --- */
    .wtb-content-body {
        padding: 35px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .wtb-user-header {
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 25px;
    }

    .wtb-avatar-img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--wtb-primary);
        padding: 2px;
        background: var(--wtb-card-bg);
    }

    .wtb-username { font-size: 1.1rem; font-weight: 800; color: var(--wtb-text-title); margin: 0; }
    .wtb-timestamp { font-size: 0.85rem; color: var(--wtb-text-muted); font-weight: 600; }

    .wtb-item-name {
        font-size: 1.7rem;
        font-weight: 900;
        color: var(--wtb-text-title);
        margin-bottom: 15px;
        line-height: 1.2;
    }

    .wtb-item-desc {
        font-size: 1rem;
        color: var(--wtb-text-body);
        line-height: 1.8;
        margin-bottom: 30px;
        display: -webkit-box;
        -webkit-line-clamp: 3; /* ตัดบรรทัดให้อ่านง่ายและ UI เท่ากัน */
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* --- 🏷️ DYNAMIC TAGS --- */
    .wtb-tag-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 30px;
    }

    .wtb-pill {
        padding: 10px 20px;
        border-radius: 14px;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .pill-cat { background: var(--wtb-primary-soft); color: var(--wtb-primary); }
    .pill-cond { background: #fef3c7; color: #d97706; }

    /* --- 🛠️ ACTION FOOTER (MODIFIED UX) --- */
    .wtb-action-footer {
        margin-top: auto;
        padding-top: 30px;
        border-top: 2px dashed var(--wtb-border);
    }

    .wtb-budget-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .budget-label { font-size: 0.9rem; font-weight: 800; color: var(--wtb-text-muted); text-transform: uppercase; }
    .budget-price { font-size: 1.8rem; font-weight: 950; color: var(--wtb-success); letter-spacing: -1px; }

    /* --- 🚀 PROFESSIONAL BUTTONS --- */
    .wtb-btn-master {
        width: 100%;
        padding: 16px;
        border-radius: var(--wtb-radius-btn);
        font-weight: 800;
        text-align: center;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }

    .btn-wtb-primary {
        background: var(--wtb-primary);
        color: #ffffff !important;
        box-shadow: 0 8px 15px rgba(79, 70, 229, 0.2);
    }

    .btn-wtb-primary:hover {
        background: var(--wtb-primary-hover);
        transform: translateY(-3px);
        box-shadow: 0 12px 20px rgba(79, 70, 229, 0.3);
    }

    .btn-wtb-edit {
        background: var(--wtb-primary-soft);
        color: var(--wtb-primary) !important;
        border: 2px solid var(--wtb-primary) !important;
    }

    .btn-wtb-close {
        background: #fee2e2;
        color: var(--wtb-danger) !important;
    }

    /* 🚨 ADMIN/TEACHER DELETE BUTTON (SOFT DELETE) */
    .btn-wtb-admin {
        background: var(--wtb-danger);
        color: #ffffff !important;
        margin-top: 10px;
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.25);
    }

    .btn-wtb-admin:hover { filter: brightness(1.1); transform: scale(1.02); }

    /* --- 🎬 ANIMATIONS --- */
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeInBoard { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }

    /* --- 📱 RESPONSIVE ADAPTATION --- */
    @media (max-width: 1024px) {
        .wtb-main-grid { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); }
        .wtb-title-group h1 { font-size: 2.5rem; }
    }

    @media (max-width: 768px) {
        .wtb-header-box { flex-direction: column; align-items: flex-start; gap: 30px; }
        .wtb-btn-master { padding: 14px; font-size: 0.9rem; }
        .wtb-image-master-wrap { height: 240px; }
        .wtb-main-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="wtb-page-container">
    
    <header class="wtb-header-box">
        <div class="wtb-title-group">
            <h1>กระดานตามหาของ</h1>
            <p class="text-muted fw-bold">แหล่งรวมประกาศตามหาสินค้าคุณภาพภายในวิทยาลัย BNCC</p>
        </div>
        
        <?php if (isLoggedIn()): ?>
            <a href="wtb_create.php" class="btn-create-wtb">
                <i class="fas fa-plus-circle fa-lg"></i> สร้างประกาศตามหาใหม่
            </a>
        <?php else: ?>
            <a href="../auth/login.php" class="btn-create-wtb">
                <i class="fas fa-sign-in-alt"></i> ล็อกอินเพื่อโพสต์
            </a>
        <?php endif; ?>
    </header>

    <div class="mb-5">
        <?php echo displayFlashMessage(); ?>
    </div>

    <div class="wtb-main-grid">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): 
                // Profile Image Normalization
                $avatar = !empty($post['profile_img']) 
                          ? "../assets/images/profiles/" . $post['profile_img'] 
                          : "../assets/images/profiles/default_profile.png";
                
                // Condition Mapping (Internalization)
                $cond_map = [
                    'any'  => 'รับทุกสภาพ', 
                    'good' => 'มือสองสภาพดี', 
                    'new'  => 'มือหนึ่งเท่านั้น'
                ];
                $display_condition = $cond_map[$post['expected_condition']] ?? 'ไม่ระบุสภาพ';
            ?>
                <article class="wtb-post-card">
                    
                    <div class="wtb-image-master-wrap">
                        <?php if ($post['image_url']): ?>
                            <img src="../assets/images/products/<?= htmlspecialchars($post['image_url']) ?>" 
                                 alt="Looking for: <?= htmlspecialchars($post['title']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="wtb-no-img">
                                <i class="fas fa-camera-retro fa-5x mb-3"></i>
                                <p class="fw-bold">ไม่มีรูปภาพประกอบ</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="wtb-card-body">
                        
                        <div class="wtb-user-info">
                            <img src="<?= $avatar ?>" class="wtb-avatar-img" alt="Avatar">
                            <div>
                                <h4 class="wtb-user-name"><?= htmlspecialchars($post['fullname']) ?></h4>
                                <span class="wtb-date">
                                    <i class="far fa-clock"></i> <?= date('d M Y • H:i', strtotime($post['created_at'])) ?>
                                </span>
                            </div>
                        </div>

                        <h2 class="wtb-item-name"><?= htmlspecialchars($post['title']) ?></h2>
                        
                        <div class="wtb-tag-container">
                            <span class="wtb-pill pill-cat">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($post['category_name'] ?? 'ทั่วไป') ?>
                            </span>
                            <span class="wtb-pill pill-cond">
                                <i class="fas fa-sparkles"></i> <?= $display_condition ?>
                            </span>
                        </div>

                        <p class="wtb-item-desc">
                            <?= nl2br(htmlspecialchars($post['description'])) ?>
                        </p>

                        <div class="wtb-action-footer">
                            
                            <div class="wtb-budget-info">
                                <span class="budget-label">งบประมาณที่มี</span>
                                <span class="budget-price">
                                    <?= $post['budget'] > 0 ? '฿' . number_format($post['budget']) : 'ตกลงภายหลัง' ?>
                                </span>
                            </div>

                            <div class="d-flex flex-column gap-2">
                                <?php if (isLoggedIn()): ?>
                                    
                                    <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                                        <div class="d-flex gap-2">
                                            <a href="wtb_edit.php?id=<?= $post['id'] ?>" class="wtb-btn-master btn-wtb-edit flex-grow-1">
                                                <i class="fas fa-edit"></i> แก้ไขข้อมูล
                                            </a>
                                            <a href="wtb_board.php?delete=<?= $post['id'] ?>" class="wtb-btn-master btn-wtb-close flex-grow-1" 
                                               onclick="return confirm('ยืนยันการปิดประกาศตามหานี้?')">
                                                <i class="fas fa-check-circle"></i> ปิดประกาศ
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                        <?php if ($_SESSION['user_id'] != $post['user_id']): ?>
                                            <a href="chat.php?user=<?= $post['user_id'] ?>" class="wtb-btn-master btn-wtb-primary">
                                                <i class="fas fa-comment-dots"></i> ทักแชทเสนอสินค้า
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="../admin/wtb_delete_admin.php?id=<?= $post['id'] ?>" 
                                           class="wtb-btn-master btn-wtb-admin" 
                                           onclick="return confirm('⚠️ คำเตือนแอดมิน: ยืนยันการลบประกาศนี้? (ข้อมูลจะถูกย้ายไปถังขยะ)')">
                                            <i class="fas fa-trash-alt"></i> ลบประกาศ (Admin/Teacher Only)
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($_SESSION['user_id'] != $post['user_id'] && !in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                        <a href="chat.php?user=<?= $post['user_id'] ?>" class="wtb-btn-master btn-wtb-primary">
                                            <i class="fas fa-comment-alt"></i> ติดต่อเสนอราคาตรงนี้
                                        </a>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <a href="../auth/login.php" class="wtb-btn-master btn-wtb-primary">
                                        <i class="fas fa-sign-in-alt"></i> ล็อกอินเพื่อเริ่มติดต่อ
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div style="background: var(--wtb-card-bg); padding: 80px; border-radius: 40px; border: 3px dashed var(--wtb-border);">
                    <i class="fas fa-search fa-6x text-muted opacity-20 mb-4"></i>
                    <h2 class="text-muted fw-bold">ยังไม่มีประกาศตามหาในระบบ</h2>
                    <p class="text-muted mb-4">ประกาศสิ่งที่คุณอยากได้คนแรกของวันนี้สิ!</p>
                    <a href="wtb_create.php" class="btn btn-primary btn-lg rounded-pill px-5">เริ่มสร้างประกาศ</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
/**
 * =========================================================================
 * 🎯 TECHNICAL FOOTER
 * File Termination
 * =========================================================================
 */
require_once '../includes/footer.php'; 
?>