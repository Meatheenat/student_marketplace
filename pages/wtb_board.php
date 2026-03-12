<?php
/**
 * BNCC MARKET - WTB BOARD (Want To Buy)
 * Version: 4.0 - FULL PROFESSIONAL RECONSTRUCTION
 * บอร์ดตามหาของ: สำหรับนักศึกษา BNCC
 * ------------------------------------------------------------
 * แก้ไขล่าสุด: 12 มีนาคม 2026
 * รายการแก้ไข:
 * - เพิ่มระบบ Soft Delete Filter ใน SQL
 * - ปรับสัดส่วนรูปภาพ (Image Ratio Fix) 
 * - เพิ่มสิทธิ์การเข้าถึงสำหรับ เจ้าของ และ แอดมิน
 */
$pageTitle = "กระดานตามหาของ - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

$db = getDB();

/* ==========================================
   1. LOGIC: SYSTEM ACTIONS
   ========================================== */

// ระบบปิดประกาศสำหรับเจ้าของโพสต์ (เปลี่ยนสถานะเป็น closed)
if (isset($_GET['delete']) && isLoggedIn()) {
    $del_id = (int)$_GET['delete'];
    
    // เช็คก่อนว่าเป็นเจ้าของโพสต์จริงไหม
    $check_stmt = $db->prepare("SELECT user_id FROM wtb_posts WHERE id = ?");
    $check_stmt->execute([$del_id]);
    $post_owner = $check_stmt->fetchColumn();
    
    if ($post_owner == $_SESSION['user_id']) {
        $stmt = $db->prepare("UPDATE wtb_posts SET status = 'closed' WHERE id = ?");
        if ($stmt->execute([$del_id])) {
            $_SESSION['flash_message'] = "ปิดประกาศตามหาเรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "success";
        }
    } else {
        $_SESSION['flash_message'] = "คุณไม่มีสิทธิ์เข้าถึงส่วนนี้";
        $_SESSION['flash_type'] = "danger";
    }
    redirect('wtb_board.php');
}

/* ==========================================
   2. DATA RETRIEVAL (SQL QUERY)
   ========================================== */

// ดึงข้อมูลประกาศตามหา (ดักจับเฉพาะที่ Active และ "ไม่ถูกลบ" is_deleted = 0)
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
       🎨 CUSTOM PREMIUM DESIGN SYSTEM
       ============================================================ */
    :root {
        --wtb-primary: #4f46e5;
        --wtb-primary-hover: #4338ca;
        --wtb-bg: #f8fafc;
        --wtb-card: #ffffff;
        --wtb-border: #e2e8f0;
        --wtb-text-main: #0f172a;
        --wtb-text-muted: #64748b;
        --wtb-danger: #ef4444;
        --wtb-success: #10b981;
    }

    .dark-theme {
        --wtb-bg: #0b0e14;
        --wtb-card: #161b26;
        --wtb-border: #2d3748;
        --wtb-text-main: #f8fafc;
        --wtb-text-muted: #94a3b8;
    }

    body { 
        background-color: var(--wtb-bg) !important; 
        color: var(--wtb-text-main);
    }

    .wtb-wrapper {
        max-width: 1300px;
        margin: 50px auto;
        padding: 0 20px;
        animation: fadeIn 0.5s ease-in-out;
    }

    /* --- Page Header Styling --- */
    .wtb-header {
        margin-bottom: 45px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        padding-bottom: 30px;
        border-bottom: 3px solid var(--wtb-border);
    }

    .wtb-header h1 {
        font-size: 2.8rem;
        font-weight: 900;
        margin: 0;
        letter-spacing: -1.5px;
        color: var(--wtb-text-main);
    }

    /* --- WTB Professional Card --- */
    .wtb-pro-card {
        background: var(--wtb-card);
        border: 2px solid var(--wtb-border);
        border-radius: 32px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }

    .wtb-pro-card:hover {
        transform: translateY(-12px);
        border-color: var(--wtb-primary);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }

    /* --- 🎯 IMAGE FIX: ป้องกันรูปแนวนอนล้นหน้าประกาศ --- */
    .wtb-image-container {
        width: 100%;
        height: 250px; /* บังคับความสูงคงที่ */
        background: var(--wtb-bg);
        overflow: hidden;
        border-bottom: 1px solid var(--wtb-border);
        position: relative;
    }

    .wtb-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* ทำให้รูปเติมกรอบพอดี ไม่เบี้ยว ไม่ยืด */
        object-position: center;
        transition: transform 0.5s ease;
    }

    .wtb-pro-card:hover .wtb-image-container img {
        transform: scale(1.05);
    }

    .wtb-no-image {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--wtb-text-muted);
        opacity: 0.3;
    }

    /* --- Card Content Section --- */
    .wtb-body {
        padding: 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .wtb-user-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
    }

    .wtb-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--wtb-primary);
    }

    .wtb-item-title {
        font-size: 1.4rem;
        font-weight: 900;
        margin-bottom: 10px;
        color: var(--wtb-text-main);
        line-height: 1.3;
    }

    .wtb-desc-text {
        font-size: 0.95rem;
        color: var(--wtb-text-muted);
        line-height: 1.6;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 2; /* ตัดบรรทัดให้อ่านง่าย */
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* --- Tags & Badges --- */
    .wtb-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
    }

    .tag-item {
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        background: var(--wtb-bg);
        border: 1px solid var(--wtb-border);
    }

    .tag-cat { color: var(--wtb-primary); background: rgba(79, 70, 229, 0.1); border-color: transparent; }

    /* --- Footer & Action Buttons --- */
    .wtb-footer {
        margin-top: auto;
        padding-top: 20px;
        border-top: 2px solid var(--wtb-border);
    }

    .budget-display {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .budget-val {
        font-size: 1.4rem;
        font-weight: 900;
        color: var(--wtb-success);
    }

    /* Professional Buttons */
    .btn-wtb-action {
        width: 100%;
        padding: 12px;
        border-radius: 16px;
        font-weight: 800;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn-offer { background: var(--wtb-primary); color: #fff !important; }
    .btn-offer:hover { background: var(--wtb-primary-hover); transform: translateY(-2px); }

    .btn-edit { background: rgba(79, 70, 229, 0.1); color: var(--wtb-primary) !important; border: 2px solid var(--wtb-primary); }
    .btn-close { background: #fee2e2; color: var(--wtb-danger) !important; }
    .btn-admin { background: var(--wtb-danger); color: #fff !important; margin-top: 10px; }

    /* Keyframes */
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    @media (max-width: 768px) {
        .wtb-header { flex-direction: column; align-items: flex-start; gap: 20px; }
        .wtb-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="wtb-wrapper">
    
    <header class="wtb-header">
        <div>
            <h1>กระดานตามหาของ</h1>
            <p class="text-muted fw-bold mb-0">ประกาศสิ่งที่ท่านต้องการ เพื่อให้ผู้ที่มีเสนอสินค้าให้โดยตรง</p>
        </div>
        <a href="wtb_create.php" class="btn btn-primary" style="border-radius: 20px; padding: 15px 35px; font-weight: 800; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);">
            <i class="fas fa-plus-circle"></i> สร้างประกาศใหม่
        </a>
    </header>

    <?php echo displayFlashMessage(); ?>

    <div class="row g-4">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): 
                $avatar = !empty($post['profile_img']) ? "../assets/images/profiles/" . $post['profile_img'] : "../assets/images/profiles/default_profile.png";
                $condition_labels = ['any' => 'ทุกสภาพ', 'good' => 'สภาพดี', 'new' => 'ของใหม่'];
            ?>
                <div class="col-md-6 col-lg-4">
                    <article class="wtb-pro-card">
                        
                        <div class="wtb-image-container">
                            <?php if ($post['image_url']): ?>
                                <img src="../assets/images/products/<?= htmlspecialchars($post['image_url']) ?>" alt="Reference Product">
                            <?php else: ?>
                                <div class="wtb-no-image">
                                    <i class="fas fa-image fa-5x"></i>
                                    <p class="mt-2 fw-bold">ไม่มีรูปภาพอ้างอิง</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="wtb-body">
                            <div class="wtb-user-meta">
                                <img src="<?= $avatar ?>" class="wtb-avatar">
                                <div>
                                    <div style="font-weight: 800; font-size: 0.95rem;"><?= htmlspecialchars($post['fullname']) ?></div>
                                    <small class="text-muted"><?= date('d M Y • H:i', strtotime($post['created_at'])) ?></small>
                                </div>
                            </div>

                            <h2 class="wtb-item-title"><?= htmlspecialchars($post['title']) ?></h2>
                            
                            <div class="wtb-tags">
                                <span class="tag-item tag-cat"><i class="fas fa-tag"></i> <?= htmlspecialchars($post['category_name'] ?? 'ทั่วไป') ?></span>
                                <span class="tag-item text-success"><i class="fas fa-sparkles"></i> <?= $condition_labels[$post['expected_condition']] ?? 'ไม่ระบุ' ?></span>
                            </div>

                            <p class="wtb-desc-text"><?= nl2br(htmlspecialchars($post['description'])) ?></p>

                            <div class="wtb-footer">
                                <div class="budget-display">
                                    <span class="text-muted small fw-bold text-uppercase">งบประมาณ</span>
                                    <span class="budget-val"><?= $post['budget'] > 0 ? '฿' . number_format($post['budget']) : 'ตกลงราคาเอง' ?></span>
                                </div>

                                <div class="d-flex flex-column gap-2">
                                    <?php if (isLoggedIn()): ?>
                                        <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                                            <div class="d-flex gap-2">
                                                <a href="wtb_edit.php?id=<?= $post['id'] ?>" class="btn-wtb-action btn-edit flex-grow-1">
                                                    <i class="fas fa-edit"></i> แก้ไขข้อมูล
                                                </a>
                                                <a href="wtb_board.php?delete=<?= $post['id'] ?>" class="btn-wtb-action btn-close flex-grow-1" onclick="return confirm('ยืนยันการปิดประกาศนี้?')">
                                                    <i class="fas fa-check-circle"></i> ปิดประกาศ
                                                </a>
                                            </div>
                                        <?php elseif ($_SESSION['role'] == 'admin'): ?>
                                            <a href="chat.php?user=<?= $post['user_id'] ?>" class="btn-wtb-action btn-offer mb-1">
                                                <i class="fas fa-comment-alt"></i> ติดต่อเสนอสินค้า
                                            </a>
                                            <a href="../admin/wtb_delete_admin.php?id=<?= $post['id'] ?>" class="btn-wtb-action btn-admin" onclick="return confirm('แอดมิน: ยืนยันการย้ายประกาศนี้ไปที่ถังขยะ?')">
                                                <i class="fas fa-trash-alt"></i> ลบประกาศ (Admin)
                                            </a>
                                        <?php else: ?>
                                            <a href="chat.php?user=<?= $post['user_id'] ?>" class="btn-wtb-action btn-offer">
                                                <i class="fas fa-comment-alt"></i> ติดต่อเสนอสินค้า
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="../auth/login.php" class="btn-wtb-action btn-offer">เข้าสู่ระบบเพื่อติดต่อ</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-search fa-5x text-muted opacity-25 mb-4"></i>
                <h3 class="text-muted">ยังไม่มีประกาศตามหาในขณะนี้</h3>
                <p class="text-muted">หากคุณกำลังมองหาสินค้าบางอย่าง ลองสร้างประกาศเพื่อให้ผู้อื่นทราบ</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>