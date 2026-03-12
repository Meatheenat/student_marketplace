<?php
/**
 * BNCC MARKET - WTB BOARD (Want To Buy)
 * Version: 5.0 Premium UI (Full Structure - Admin/Teacher Soft Delete Support)
 * -------------------------------------------------------------------------
 * โครงสร้างเดิมของมึง 100% เพิ่มเติมฟังก์ชันตามสั่ง ไม่ตัดทอนบรรทัดให้มึงด่าแน่นอน
 * ระบบนี้รองรับ Soft Delete เพื่อความปลอดภัยของข้อมูลนักศึกษา
 */
$pageTitle = "กระดานตามหาของ - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

$db = getDB();

/* ============================================================
   1. SYSTEM LOGIC: ACTIONS (Owner/Member)
   ============================================================ */

// 🎯 ระบบจัดการปิดประกาศสำหรับเจ้าของ (เปลี่ยนสถานะเป็น closed)
// ฟังก์ชันนี้จะทำงานเมื่อเจ้าของกดปุ่ม "ปิดประกาศ"
if (isset($_GET['delete']) && isLoggedIn()) {
    $del_id = (int)$_GET['delete'];
    
    // ตรวจสอบความเป็นเจ้าของโพสต์เพื่อป้องกันการแฮก URL
    $check_stmt = $db->prepare("SELECT user_id FROM wtb_posts WHERE id = ?");
    $check_stmt->execute([$del_id]);
    $post_owner = $check_stmt->fetchColumn();
    
    // ถ้าเป็นเจ้าของจริง ให้ทำการปิดโพสต์ (Closed)
    if ($post_owner == $_SESSION['user_id']) {
        $stmt = $db->prepare("UPDATE wtb_posts SET status = 'closed' WHERE id = ?");
        if ($stmt->execute([$del_id])) {
            $_SESSION['flash_message'] = "ปิดประกาศตามหาเรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "success";
        }
    } else {
        // กรณีคนอื่นแอบมาส่งค่า ID ของโพสต์คนอื่น
        $_SESSION['flash_message'] = "มึงไม่ใช่เจ้าของโพสต์ อย่าหาทำไอสัส";
        $_SESSION['flash_type'] = "danger";
    }
    redirect('wtb_board.php');
}

/* ============================================================
   2. DATA RETRIEVAL (SQL WITH SOFT DELETE FILTER)
   ============================================================ */

// 🎯 ดึงข้อมูลประกาศตามหา (ดักจับเฉพาะที่ Active และ "ไม่ถูกลบ" is_deleted = 0)
// ใช้การ Join กับตาราง Users และ Categories เพื่อดึงข้อมูลมาแสดงผลในการ์ด
$stmt = $db->query("
    SELECT w.*, u.fullname, u.profile_img, c.category_name 
    FROM wtb_posts w 
    JOIN users u ON w.user_id = u.id 
    LEFT JOIN categories c ON w.category_id = c.id
    WHERE w.status = 'active' 
    AND w.is_deleted = 0 
    ORDER BY w.created_at DESC
");
$posts = $stmt->fetchAll();
?>

<style>
    /* ============================================================
       🎨 PREMIUM DESIGN SYSTEM (UX/UI & ANIMATIONS)
       ============================================================ */
    :root {
        --wtb-bg-page: #f1f5f9;
        --wtb-card-bg: #ffffff;
        --wtb-accent: #4f46e5;
        --wtb-accent-soft: rgba(79, 70, 229, 0.1);
        --wtb-text-dark: #1e293b;
        --wtb-text-light: #64748b;
        --wtb-border: #e2e8f0;
        --wtb-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        --wtb-danger: #ef4444;
        --wtb-success: #10b981;
    }

    /* สไตล์สำหรับโหมดมืด (Dark Mode) */
    .dark-theme {
        --wtb-bg-page: #0f172a;
        --wtb-card-bg: #1e293b;
        --wtb-text-dark: #f8fafc;
        --wtb-text-light: #94a3b8;
        --wtb-border: #334155;
        --wtb-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
    }

    body { 
        background-color: var(--wtb-bg-page) !important; 
        color: var(--wtb-text-dark); 
        transition: background 0.3s ease; 
    }

    .wtb-main-container {
        max-width: 1300px;
        margin: 50px auto;
        padding: 0 25px;
        animation: fadeInBoard 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* --- Header Section --- */
    .wtb-header-section {
        margin-bottom: 50px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        border-bottom: 3px solid var(--wtb-border);
        padding-bottom: 30px;
        position: relative;
    }

    .wtb-header-title h1 {
        font-size: 2.8rem;
        font-weight: 900;
        color: var(--wtb-text-dark);
        letter-spacing: -1.5px;
        margin-bottom: 5px;
    }

    .btn-create-wtb {
        background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
        color: #fff !important;
        padding: 16px 35px;
        border-radius: 20px;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 10px 20px rgba(67, 56, 202, 0.3);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .btn-create-wtb:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 15px 30px rgba(67, 56, 202, 0.4);
    }

    /* --- Grid System สำหรับแสดงผลการ์ด --- */
    .wtb-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 35px;
    }

    /* --- การ์ดประกาศดีไซน์พรีเมียม --- */
    .wtb-pro-card {
        background: var(--wtb-card-bg);
        border: 1px solid var(--wtb-border);
        border-radius: 30px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        box-shadow: var(--wtb-shadow);
        height: 100%;
        position: relative;
    }

    .wtb-pro-card:hover {
        transform: translateY(-12px);
        border-color: var(--wtb-accent);
        box-shadow: 0 30px 60px rgba(0,0,0,0.12);
    }

    /* 📸 สัดส่วนรูปภาพ 240px แบบ Fixed Ratio ป้องกันรูปเบี้ยว */
    .wtb-card-image-wrap {
        width: 100%;
        height: 240px; 
        background-color: var(--wtb-bg-page);
        overflow: hidden;
        position: relative;
        border-bottom: 1px solid var(--wtb-border);
    }

    .wtb-card-image-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover; 
        object-position: center;
        transition: transform 0.6s ease;
    }

    .wtb-pro-card:hover .wtb-card-image-wrap img {
        transform: scale(1.1);
    }

    .wtb-no-image-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--wtb-text-light);
        opacity: 0.4;
    }

    /* --- ส่วนข้อมูลด้านในของการ์ด --- */
    .wtb-card-content {
        padding: 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .wtb-user-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .wtb-user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--wtb-accent);
    }

    .wtb-user-name {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--wtb-text-dark);
    }

    .wtb-post-date {
        font-size: 0.75rem;
        color: var(--wtb-text-light);
    }

    .wtb-item-title {
        font-size: 1.4rem;
        font-weight: 900;
        color: var(--wtb-text-dark);
        margin-bottom: 12px;
        line-height: 1.2;
    }

    .wtb-item-desc {
        font-size: 0.95rem;
        color: var(--wtb-text-light);
        line-height: 1.6;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* --- ส่วนของ Tags --- */
    .wtb-tags-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 25px;
    }

    .wtb-tag {
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        border: 1px solid var(--wtb-border);
    }

    .tag-category { background: var(--wtb-accent-soft); color: var(--wtb-accent); border-color: transparent; }
    .tag-condition { background: #fef3c7; color: #d97706; border-color: transparent; }

    /* --- ส่วนล่างของการ์ดและปุ่มกด --- */
    .wtb-card-footer {
        margin-top: auto;
        padding-top: 20px;
        border-top: 2px solid var(--wtb-border);
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .wtb-budget-display {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .budget-label { font-size: 0.8rem; font-weight: 700; color: var(--wtb-text-light); text-transform: uppercase; }
    .budget-amount { font-size: 1.4rem; font-weight: 900; color: var(--wtb-success); }

    .btn-wtb-action {
        width: 100%;
        padding: 14px;
        border-radius: 16px;
        font-weight: 800;
        text-align: center;
        text-decoration: none;
        transition: 0.3s;
        display: block;
        border: none;
        cursor: pointer;
    }

    .btn-wtb-primary { background: var(--wtb-accent); color: #fff !important; }
    .btn-wtb-primary:hover { transform: scale(1.02); filter: brightness(1.1); }

    .btn-wtb-outline { border: 2px solid var(--wtb-accent); color: var(--wtb-accent) !important; background: transparent; }
    .btn-wtb-danger { background: #fee2e2; color: var(--wtb-danger) !important; }
    .btn-wtb-admin { background: var(--wtb-danger); color: #fff !important; margin-top: 5px; }

    /* Keyframes สำหรับ Animation */
    @keyframes fadeInBoard { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .wtb-header-section { flex-direction: column; align-items: flex-start; gap: 20px; }
        .wtb-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="wtb-main-container">
    
    <header class="wtb-header-section">
        <div class="wtb-header-title">
            <h1>กระดานตามหาของ</h1>
            <p class="text-muted fw-bold">พื้นที่สำหรับประกาศหาสิ่งที่ต้องการ ใครมีของก็ทักไปขายได้เลย</p>
        </div>
        <a href="wtb_create.php" class="btn-create-wtb">
            <i class="fas fa-plus-circle fa-lg"></i> สร้างประกาศตามหา
        </a>
    </header>

    <?php echo displayFlashMessage(); ?>

    <div class="wtb-grid">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): 
                $avatar = !empty($post['profile_img']) ? "../assets/images/profiles/" . $post['profile_img'] : "../assets/images/profiles/default_profile.png";
                $cond_labels = ['any' => 'รับทุกสภาพ', 'good' => 'สภาพดีเท่านั้น', 'new' => 'มือหนึ่งเท่านั้น'];
                $cond_text = $cond_labels[$post['expected_condition']] ?? 'ไม่ระบุสภาพ';
            ?>
                <article class="wtb-pro-card">
                    
                    <div class="wtb-card-image-wrap">
                        <?php if ($post['image_url']): ?>
                            <img src="../assets/images/products/<?= htmlspecialchars($post['image_url']) ?>" alt="ประกาศตามหา: <?= htmlspecialchars($post['title']) ?>">
                        <?php else: ?>
                            <div class="wtb-no-image-placeholder">
                                <i class="fas fa-image fa-4x mb-2"></i>
                                <p class="fw-bold">ไม่มีรูปอ้างอิง</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="wtb-card-content">
                        <div class="wtb-user-bar">
                            <img src="<?= $avatar ?>" class="wtb-user-avatar">
                            <div>
                                <div class="wtb-user-name"><?= htmlspecialchars($post['fullname']) ?></div>
                                <div class="wtb-post-date"><i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($post['created_at'])) ?></div>
                            </div>
                        </div>

                        <h2 class="wtb-item-title"><?= htmlspecialchars($post['title']) ?></h2>
                        
                        <div class="wtb-tags-wrap">
                            <span class="wtb-tag tag-category"><i class="fas fa-layer-group"></i> <?= htmlspecialchars($post['category_name'] ?? 'ทั่วไป') ?></span>
                            <span class="wtb-tag tag-condition"><i class="fas fa-sparkles"></i> <?= $cond_text ?></span>
                        </div>

                        <p class="wtb-item-desc"><?= nl2br(htmlspecialchars($post['description'])) ?></p>

                        <div class="wtb-card-footer">
                            <div class="wtb-budget-display">
                                <span class="budget-label">งบประมาณที่มี</span>
                                <span class="budget-amount">
                                    <?= $post['budget'] > 0 ? '฿' . number_format($post['budget']) : 'คุยราคากันเอง' ?>
                                </span>
                            </div>

                            <div class="d-flex flex-column gap-2">
                                <?php if (isLoggedIn()): ?>
                                    
                                    <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                                        <div class="d-flex gap-2">
                                            <a href="wtb_edit.php?id=<?= $post['id'] ?>" class="btn-wtb-action btn-wtb-outline flex-grow-1">
                                                <i class="fas fa-edit"></i> แก้ไข
                                            </a>
                                            <a href="wtb_board.php?delete=<?= $post['id'] ?>" class="btn-wtb-action btn-wtb-danger flex-grow-1" onclick="return confirm('ยืนยันการปิดประกาศนี้?')">
                                                <i class="fas fa-check-circle"></i> ปิดประกาศ
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                        <?php if ($_SESSION['user_id'] != $post['user_id']): ?>
                                            <a href="chat.php?user=<?= $post['user_id'] ?>" class="btn-wtb-action btn-wtb-primary mb-1">
                                                <i class="fas fa-comment-alt"></i> ติดต่อเสนอสินค้า
                                            </a>
                                        <?php endif; ?>
                                        <a href="../admin/wtb_delete_admin.php?id=<?= $post['id'] ?>" class="btn-wtb-action btn-wtb-admin" onclick="return confirm('⚠️ ยืนยันการลบประกาศนี้โดยแอดมิน/อาจารย์ (Soft Delete)?')">
                                            <i class="fas fa-trash-alt"></i> ลบประกาศ (Admin/Teacher)
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($_SESSION['user_id'] != $post['user_id'] && !in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                        <a href="chat.php?user=<?= $post['user_id'] ?>" class="btn-wtb-action btn-wtb-primary">
                                            <i class="fas fa-comment-alt"></i> ติดต่อเสนอราคา
                                        </a>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <a href="../auth/login.php" class="btn-wtb-action btn-wtb-primary">เข้าสู่ระบบเพื่อติดต่อ</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="wtb-empty-state">
                <div class="mb-4"><i class="fas fa-search fa-5x text-muted opacity-25"></i></div>
                <h2 class="text-muted fw-bold">ยังไม่มีประกาศตามหาในขณะนี้</h2>
                <p class="text-muted">มึงอยากได้อะไรเป็นเป็นพิเศษไหม? ลองสร้างประกาศดูสิ!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>