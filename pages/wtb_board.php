<?php



$pageTitle = "กระดานตามหาของ - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';


$db = getDB();


if (isset($_GET['delete']) && isLoggedIn()) {
    $del_id = (int)$_GET['delete'];
    
    // ตรวจสอบความปลอดภัย: ดึง User ID ของเจ้าของโพสต์มาเทียบ
    $check_stmt = $db->prepare("SELECT user_id FROM wtb_posts WHERE id = ?");
    $check_stmt->execute([$del_id]);
    $post_owner = $check_stmt->fetchColumn();
    
    // เงื่อนไข: ต้องเป็นเจ้าของเท่านั้นถึงจะปิดประกาศผ่านฟังก์ชันนี้ได้
    if ($post_owner == $_SESSION['user_id']) {
        $stmt = $db->prepare("UPDATE wtb_posts SET status = 'closed' WHERE id = ?");
        
        if ($stmt->execute([$del_id])) {
            $_SESSION['flash_message'] = "ดำเนินการปิดประกาศเรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "เกิดข้อผิดพลาดในการเข้าถึงเซิร์ฟเวอร์";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "⚠️ ตรวจพบการพยายามเข้าถึงที่ไม่ได้รับอนุญาต";
        $_SESSION['flash_type'] = "danger";
    }
    
    // ดีดกลับหน้าบอร์ดทันทีหลังทำรายการ
    redirect('wtb_board.php');
}

/* ============================================================
   SECTION 2: DATA RETRIEVAL (DATABASE QUERY)
   ============================================================ */

/**
 * 🎯 การดึงข้อมูลประกาศ WTB มาแสดงผล
 * เงื่อนไขหลัก: 
 * 1. status ต้องเป็น 'active' (ผ่านการอนุมัติ)
 * 2. is_deleted ต้องเป็น 0 (ยังไม่ถูกลบโดย Soft Delete)
 */
$stmt = $db->query("
    SELECT 
        w.*, 
        u.fullname, 
        u.profile_img, 
        u.role as user_role,
        c.category_name 
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
   SECTION 3: PREMIUM DESIGN SYSTEM (CSS)
   ============================================================ */

:root {
    --wtb-primary: #4f46e5;
    --wtb-primary-light: #818cf8;
    --wtb-bg-page: #f1f5f9;
    --wtb-card-bg: #ffffff;
    --wtb-border-color: #e2e8f0;
    --wtb-text-dark: #1e293b;
    --wtb-text-muted: #64748b;
    --wtb-danger: #ef4444;
    --wtb-success: #10b981;
    --wtb-warning: #f59e0b;
    --wtb-shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.1);
    --wtb-shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.1);
    --wtb-radius: 28px;
}

/* Dark Mode */
.dark-theme{
    --wtb-bg-page:#0f172a;
    --wtb-card-bg:#1e293b;
    --wtb-text-dark:#f8fafc;
    --wtb-text-muted:#94a3b8;
    --wtb-border-color:#334155;
}

body{
    background-color:var(--wtb-bg-page)!important;
    color:var(--wtb-text-dark);
    transition:background-color .4s ease;
}

.wtb-main-wrapper{
    max-width:1300px;
    margin:50px auto;
    padding:0 25px;
}

/* Header */
.wtb-header{
    margin-bottom:50px;
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    border-bottom:3px solid var(--wtb-border-color);
    padding-bottom:30px;
}

.wtb-header-title h1{
    font-size:2.8rem;
    font-weight:900;
    letter-spacing:-1.5px;
    margin:0;
    background:linear-gradient(135deg,var(--wtb-primary) 0%,#a855f7 100%);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.btn-create{
    background:linear-gradient(135deg,#6366f1 0%,#4338ca 100%);
    color:#fff!important;
    padding:16px 35px;
    border-radius:20px;
    font-weight:800;
    text-decoration:none;
    box-shadow:0 10px 20px rgba(67,56,202,.3);
    transition:.3s;
    display:flex;
    align-items:center;
    gap:12px;
}

.btn-create:hover{
    transform:translateY(-4px);
    box-shadow:0 15px 30px rgba(67,56,202,.4);
}

/* Grid */
.wtb-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
    gap:35px;
    margin-bottom:80px;
}

/* Card */
.wtb-pro-card{
    background:var(--wtb-card-bg);
    border:2px solid var(--wtb-border-color);
    border-radius:var(--wtb-radius);
    overflow:hidden;
    display:flex;
    flex-direction:column;
    height:100%;
    transition:.35s;
    box-shadow:var(--wtb-shadow-sm);
}

.wtb-pro-card:hover{
    transform:translateY(-10px);
    border-color:var(--wtb-primary);
    box-shadow:var(--wtb-shadow-lg);
}

/* Image */
.wtb-card-img-wrap{
    width:100%;
    height:250px;
    background:var(--wtb-bg-page);
    overflow:hidden;
    border-bottom:1px solid var(--wtb-border-color);
}

.wtb-card-img-wrap img{
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:center;
    transition:.7s;
}

.wtb-pro-card:hover .wtb-card-img-wrap img{
    transform:scale(1.1);
}

.wtb-placeholder{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    height:100%;
    color:var(--wtb-text-muted);
    opacity:.35;
}

/* Body */
.wtb-card-body{
    padding:28px;
    flex-grow:1;
    display:flex;
    flex-direction:column;
}

/* User */
.wtb-user-info{
    display:flex;
    align-items:center;
    gap:15px;
    margin-bottom:20px;
}

.wtb-user-avatar{
    width:45px;
    height:45px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid var(--wtb-primary);
    padding:2px;
}

.wtb-user-name{
    font-size:1.05rem;
    font-weight:800;
    margin:0;
}

.wtb-date{
    font-size:.8rem;
    color:var(--wtb-text-muted);
}

/* Title */
.wtb-post-title{
    font-size:1.45rem;
    font-weight:900;
    margin-bottom:10px;
}

/* Description */
.wtb-description{
    font-size:.95rem;
    color:var(--wtb-text-muted);
    line-height:1.65;
    margin-bottom:22px;

    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
}

/* Tags */
.wtb-tags{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:22px;
}

.wtb-tag-pill{
    padding:7px 14px;
    border-radius:10px;
    font-size:.72rem;
    font-weight:800;
    background:var(--wtb-bg-page);
}

.pill-category{
    background:rgba(79,70,229,.1);
    color:var(--wtb-primary);
}

.pill-condition{
    background:#fef3c7;
    color:#d97706;
}

/* Footer */
.wtb-card-footer{
    margin-top:auto;
    padding-top:22px;
    border-top:2px solid var(--wtb-border-color);
    display:flex;
    flex-direction:column;
    gap:14px;
}

/* Budget */
.wtb-budget-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.budget-amount{
    font-size:1.5rem;
    font-weight:900;
    color:var(--wtb-success);
}

/* Buttons */
.btn-action{
    width:100%;
    height:48px;
    padding:0 16px;
    border-radius:16px;
    font-weight:800;
    text-decoration:none;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    transition:.25s;
    border:none;
    cursor:pointer;
}

/* Button layout fix */
.wtb-card-footer .d-flex{
    flex-direction:column;
    gap:12px;
}

.wtb-card-footer .d-flex.gap-2{
    display:grid!important;
    grid-template-columns:1fr 1fr;
    gap:10px!important;
}

/* Primary */
.btn-pro-primary{
    background:var(--wtb-primary);
    color:#fff!important;
}

.btn-pro-primary:hover{
    filter:brightness(1.1);
}

/* Edit */
.btn-pro-edit{
    background:rgba(79,70,229,.08);
    color:var(--wtb-primary)!important;
    border:2px solid var(--wtb-primary);
}

.btn-pro-edit:hover{
    background:rgba(79,70,229,.15);
}

/* Danger */
.btn-pro-danger{
    background:#fee2e2;
    color:var(--wtb-danger)!important;
}

.btn-pro-danger:hover{
    background:#fecaca;
}

/* Admin */
.btn-pro-admin{
    background:var(--wtb-danger);
    color:#fff!important;
    margin-top:4px;
}

.btn-pro-admin:hover{
    filter:brightness(1.1);
}

/* Animation */
@keyframes fadeInBoard{
    0%{opacity:0;transform:translateY(30px)}
    100%{opacity:1;transform:translateY(0)}
}

/* Responsive */
@media (max-width:992px){
    .wtb-header-title h1{
        font-size:2.3rem;
    }
}

@media (max-width:768px){

    .wtb-header{
        flex-direction:column;
        align-items:flex-start;
        gap:25px;
    }

    .wtb-grid{
        grid-template-columns:1fr;
    }

    .wtb-card-img-wrap{
        height:200px;
    }
}

@media (max-width:500px){

    .wtb-card-footer .d-flex.gap-2{
        grid-template-columns:1fr;
    }

}
</style>


<div class="wtb-main-wrapper">
    
    <header class="wtb-header">
        <div class="wtb-header-title">
            <h1>กระดานตามหาของ (WTB)</h1>
            <p class="text-muted fw-bold">พื้นที่สำหรับประกาศความต้องการสินค้าภายในวิทยาลัย BNCC</p>
        </div>
        <a href="wtb_create.php" class="btn-create">
            <i class="fas fa-plus-circle fa-lg"></i> สร้างโพสต์ตามหา
        </a>
    </header>

    <?php echo displayFlashMessage(); ?>

    <div class="wtb-grid">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): 
                // จัดการเรื่องรูปโปรไฟล์ ถ้าไม่มีให้ใช้ Default
                $avatar = !empty($post['profile_img']) ? "../assets/images/profiles/" . $post['profile_img'] : "../assets/images/profiles/default_profile.png";
                
                // แปลงข้อความ Condition ให้เป็นภาษาไทยอ่านง่าย
                $cond_map = ['any' => 'ทุกสภาพ', 'good' => 'สภาพดีมาก', 'new' => 'มือหนึ่งเท่านั้น'];
                $display_cond = $cond_map[$post['expected_condition']] ?? 'ไม่ระบุ';
            ?>
                <article class="wtb-pro-card">
                    
                    <div class="wtb-card-img-wrap">
                        <?php if ($post['image_url']): ?>
                            <img src="../assets/images/products/<?= htmlspecialchars($post['image_url']) ?>" alt="Reference for: <?= htmlspecialchars($post['title']) ?>">
                        <?php else: ?>
                            <div class="wtb-placeholder">
                                <i class="fas fa-image fa-5x mb-3"></i>
                                <p class="fw-bold">ไม่มีรูปภาพอ้างอิง</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="wtb-card-body">
                        <div class="wtb-user-info">
                            <img src="<?= $avatar ?>" class="wtb-user-avatar" alt="Avatar">
                            <div>
                                <h4 class="wtb-user-name"><?= htmlspecialchars($post['fullname']) ?></h4>
                                <span class="wtb-date"><i class="far fa-calendar-alt"></i> <?= date('d M Y • H:i', strtotime($post['created_at'])) ?></span>
                            </div>
                        </div>

                        <h2 class="wtb-post-title"><?= htmlspecialchars($post['title']) ?></h2>
                        
                        <div class="wtb-tags">
                            <span class="wtb-tag-pill pill-category">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($post['category_name'] ?? 'ทั่วไป') ?>
                            </span>
                            <span class="wtb-tag-pill pill-condition">
                                <i class="fas fa-sparkles"></i> <?= $display_cond ?>
                            </span>
                        </div>

                        <p class="wtb-description"><?= nl2br(htmlspecialchars($post['description'])) ?></p>

                        <div class="wtb-card-footer">
                            <div class="wtb-budget-row">
                                <span class="text-muted small fw-bold text-uppercase">งบประมาณ</span>
                                <span class="budget-amount">
                                    <?= $post['budget'] > 0 ? '฿' . number_format($post['budget']) : 'ตกลงภายหลัง' ?>
                                </span>
                            </div>

                            <div class="d-flex flex-column gap-2">
                                <?php if (isLoggedIn()): ?>
                                    
                                    <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                                        <div class="d-flex gap-2">
                                            <a href="wtb_edit.php?id=<?= $post['id'] ?>" class="btn-action btn-pro-edit flex-grow-1">
                                                <i class="fas fa-edit"></i> แก้ไขข้อมูล
                                            </a>
                                            <a href="wtb_board.php?delete=<?= $post['id'] ?>" class="btn-action btn-pro-danger flex-grow-1" onclick="return confirm('ยืนยันการปิดประกาศตามหานี้?')">
                                                <i class="fas fa-check-circle"></i> ปิดประกาศ
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                        <?php if ($_SESSION['user_id'] != $post['user_id']): ?>
                                            <a href="chat.php?user=<?= $post['user_id'] ?>" class="btn-action btn-pro-primary">
                                                <i class="fas fa-comment-alt"></i> ติดต่อเสนอเสนอสินค้า
                                            </a>
                                        <?php endif; ?>
                                        <a href="../admin/wtb_delete_admin.php?id=<?= $post['id'] ?>" class="btn-action btn-pro-admin" onclick="return confirm('⚠️ ยืนยันการลบประกาศนี้โดยแอดมิน/อาจารย์ (ข้อมูลจะถูกย้ายไปถังขยะ)?')">
                                            <i class="fas fa-trash-alt"></i> ลบประกาศนี้ (Admin/Teacher)
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($_SESSION['user_id'] != $post['user_id'] && !in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                        <a href="chat.php?user=<?= $post['user_id'] ?>" class="btn-action btn-pro-primary">
                                            <i class="fas fa-comment-alt"></i> ติดต่อเสนอสินค้า
                                        </a>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <a href="../auth/login.php" class="btn-action btn-pro-primary">
                                        <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบเพื่อติดต่อ
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="wtb-empty-state col-12 text-center py-5">
                <i class="fas fa-search fa-5x text-muted opacity-20 mb-4"></i>
                <h2 class="text-muted fw-bold">ยังไม่มีประกาศตามหาในขณะนี้</h2>
                <p class="text-muted">มึงอยากได้อะไรไหม? ลองสร้างประกาศเป็นคนแรกสิ!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 

require_once '../includes/footer.php'; 
?>