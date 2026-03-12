<?php
/**
 * -------------------------------------------------------------------------
 * 1. CORE INITIALIZATION & LOGIC PROCESSING (MUST BE AT THE VERY TOP)
 * -------------------------------------------------------------------------
 * คำเตือน: ห้ามมีเว้นวรรค, บรรทัดว่าง หรือ HTML ใดๆ ก่อนเปิด tag <?php 
 * เพื่อป้องกัน Error: Cannot modify header information
 */
require_once '../includes/functions.php';

// 🛡️ Security Check: ตรวจสอบสิทธิ์ว่าต้องเป็น Admin เท่านั้น
checkRole('admin');

$db = getDB();

// ⚙️ Logic 1: ประมวลผลเมื่อมีการกดปุ่ม อนุมัติ (Approve) หรือ ปฏิเสธ (Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['shop_id'])) {
    $shop_id = (int)$_POST['shop_id'];
    $action  = $_POST['action'];

    try {
        $db->beginTransaction();

        if ($action === 'approve') {
            // อัปเดตสถานะร้านค้าเป็น 'approved'
            $stmt = $db->prepare("UPDATE shops SET status = 'approved', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$shop_id]);
            
            // ดึง user_id ของร้านค้านี้เพื่อไปอัปเดต Role เป็น seller
            $getUserId = $db->prepare("SELECT user_id FROM shops WHERE id = ?");
            $getUserId->execute([$shop_id]);
            $shop_owner_id = $getUserId->fetchColumn();

            if ($shop_owner_id) {
                $updateUser = $db->prepare("UPDATE users SET role = 'seller' WHERE id = ?");
                $updateUser->execute([$shop_owner_id]);
            }

            $_SESSION['flash_message'] = "✅ อนุมัติร้านค้าและปรับสถานะผู้ใช้เป็นผู้ขายเรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "success";

        } elseif ($action === 'reject') {
            // กรณีปฏิเสธ จะทำการลบ Record ทิ้งเพื่อไม่ให้รกฐานข้อมูล
            $stmt = $db->prepare("DELETE FROM shops WHERE id = ?");
            $stmt->execute([$shop_id]);
            
            $_SESSION['flash_message'] = "❌ ปฏิเสธและลบคำร้องขอเปิดร้านค้าเรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "warning";
        }

        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['flash_message'] = "เกิดข้อผิดพลาดของระบบ: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }

    // 🎯 Redirect กลับไปหน้าเดิมเพื่อล้างค่า POST (นี่คือจุดที่แก้ Headers already sent)
    header("Location: approve_shop.php");
    exit();
}

/**
 * -------------------------------------------------------------------------
 * 2. DATA RETRIEVAL
 * -------------------------------------------------------------------------
 */
$sql = "SELECT s.*, u.id as user_id, u.fullname, u.class_room, u.email, u.profile_img 
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.status = 'pending' 
        ORDER BY s.created_at ASC";
$stmt = $db->query($sql);
$pending_shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * -------------------------------------------------------------------------
 * 3. FRONTEND RENDER
 * -------------------------------------------------------------------------
 * โค้ด HTML เริ่มทำงานตั้งแต่บรรทัดนี้เป็นต้นไป
 */
$pageTitle = "จัดการคำร้องเปิดร้านค้า (Shop Approvals) - BNCC Market";
require_once '../includes/header.php';
?>

<style>
    /* ============================================================
       💎 ENTERPRISE DESIGN SYSTEM (ADMIN APPROVAL DASHBOARD)
       ============================================================ */
    :root {
        --app-primary: #4f46e5;
        --app-primary-hover: #4338ca;
        --app-success: #10b981;
        --app-success-hover: #059669;
        --app-danger: #ef4444;
        --app-danger-hover: #dc2626;
        --app-warning: #f59e0b;
        --app-info: #0ea5e9;
        
        --app-bg-main: #f8fafc;
        --app-bg-card: #ffffff;
        --app-bg-hover: #f1f5f9;
        
        --app-text-title: #0f172a;
        --app-text-body: #475569;
        --app-text-muted: #94a3b8;
        
        --app-border: #e2e8f0;
        --app-border-focus: #cbd5e1;
        
        --app-shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        --app-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
        --app-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        --app-shadow-glow: 0 0 20px rgba(79, 70, 229, 0.15);
        
        --app-radius-sm: 8px;
        --app-radius-md: 16px;
        --app-radius-lg: 24px;
        --app-radius-full: 9999px;
        
        --app-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --app-bounce: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    /* 🌙 DARK MODE OVERRIDES */
    .dark-theme {
        --app-bg-main: #0b0f19;
        --app-bg-card: #111827;
        --app-bg-hover: #1f2937;
        --app-text-title: #f8fafc;
        --app-text-body: #cbd5e1;
        --app-text-muted: #64748b;
        --app-border: #374151;
        --app-border-focus: #4b5563;
        --app-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
        --app-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
    }

    /* --- GLOBAL WRAPPER --- */
    body {
        background-color: var(--app-bg-main) !important;
        color: var(--app-text-body);
        font-family: 'Prompt', sans-serif;
    }

    .approval-master-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
        animation: fadeIn 0.6s ease-out;
        min-height: calc(100vh - 200px);
    }

    /* --- PAGE HEADER --- */
    .approval-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--app-border);
        position: relative;
    }

    .approval-header::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100px;
        height: 2px;
        background: var(--app-primary);
        border-radius: var(--app-radius-full);
    }

    .header-title-box h1 {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--app-text-title);
        margin: 0 0 5px 0;
        letter-spacing: -0.5px;
    }

    .header-title-box p {
        color: var(--app-text-muted);
        font-size: 1rem;
        margin: 0;
        font-weight: 500;
    }

    .btn-back-dashboard {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        background: var(--app-bg-card);
        color: var(--app-text-title);
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius-md);
        font-weight: 800;
        text-decoration: none;
        transition: var(--app-bounce);
        box-shadow: var(--app-shadow-sm);
    }

    .btn-back-dashboard:hover {
        background: var(--app-primary);
        border-color: var(--app-primary);
        color: #ffffff;
        transform: translateY(-3px);
        box-shadow: var(--app-shadow-md);
    }

    /* --- STATS COUNTER --- */
    .pending-stats-pill {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 10px 20px;
        background: rgba(245, 158, 11, 0.1);
        color: var(--app-warning);
        border-radius: var(--app-radius-full);
        font-weight: 800;
        font-size: 0.95rem;
        margin-bottom: 30px;
        border: 1px solid rgba(245, 158, 11, 0.2);
        box-shadow: var(--app-shadow-sm);
        animation: pulseWarning 2s infinite;
    }

    /* --- DATA GRID SYSTEM --- */
    .approval-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
        padding-bottom: 60px;
    }

    /* --- APPROVAL CARD UI --- */
    .shop-request-card {
        background: var(--app-bg-card);
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius-lg);
        padding: 35px;
        display: grid;
        grid-template-columns: 1fr 320px; /* Content | Actions */
        gap: 35px;
        box-shadow: var(--app-shadow-sm);
        transition: var(--app-bounce);
        position: relative;
        overflow: hidden;
        opacity: 0;
        transform: translateY(20px);
        animation: slideUpFade 0.6s forwards;
    }

    .shop-request-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 8px;
        height: 100%;
        background: linear-gradient(to bottom, var(--app-primary), #a855f7);
        border-radius: 8px 0 0 8px;
    }

    .shop-request-card:hover {
        box-shadow: var(--app-shadow-lg);
        border-color: var(--app-border-focus);
        transform: translateY(-5px) scale(1.01);
    }

    /* Left Side: Shop Details */
    .shop-content-zone {
        display: flex;
        flex-direction: column;
    }

    .shop-title-wrapper {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }

    .shop-icon-circle {
        width: 45px;
        height: 45px;
        background: rgba(79, 70, 229, 0.1);
        color: var(--app-primary);
        border-radius: 12px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.3rem;
        box-shadow: var(--app-shadow-sm);
    }

    .shop-name {
        font-size: 1.8rem;
        font-weight: 900;
        color: var(--app-text-title);
        margin: 0;
        letter-spacing: -0.5px;
    }

    .shop-desc {
        font-size: 1.05rem;
        color: var(--app-text-body);
        line-height: 1.7;
        margin-bottom: 30px;
        background: var(--app-bg-main);
        padding: 20px 25px;
        border-radius: var(--app-radius-md);
        border: 1px solid var(--app-border);
        position: relative;
    }
    
    .shop-desc::before {
        content: '\f10d';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: -10px;
        left: 20px;
        background: var(--app-bg-card);
        color: var(--app-text-muted);
        padding: 0 10px;
        font-size: 1.2rem;
    }

    /* Owner Information Profile Pill */
    .owner-profile-pill {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 18px 25px;
        border-radius: var(--app-radius-md);
        border: 2px dashed var(--app-border);
        background: var(--app-bg-main);
        transition: var(--app-transition);
    }

    .owner-profile-pill:hover {
        border-style: solid;
        border-color: var(--app-primary);
        background: rgba(79, 70, 229, 0.05);
    }

    .owner-avatar {
        width: 55px;
        height: 55px;
        border-radius: var(--app-radius-full);
        object-fit: cover;
        border: 3px solid var(--app-primary);
        padding: 2px;
        background: var(--app-bg-card);
    }

    .owner-details {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .owner-name {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--app-text-title);
        margin-bottom: 4px;
    }

    .owner-meta {
        font-size: 0.85rem;
        color: var(--app-text-muted);
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .owner-meta span {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
    }

    /* Contact Buttons (Profile / Chat) */
    .contact-action-group {
        display: flex;
        gap: 12px;
        margin-top: 15px;
    }

    .btn-contact {
        flex: 1;
        padding: 10px 15px;
        border-radius: var(--app-radius-sm);
        font-size: 0.9rem;
        font-weight: 800;
        text-decoration: none;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        transition: var(--app-bounce);
        border: 1px solid var(--app-border);
        background: var(--app-bg-card);
        color: var(--app-text-body);
    }

    .btn-contact:hover {
        transform: translateY(-3px);
        box-shadow: var(--app-shadow-md);
    }

    .btn-contact.profile:hover { background: var(--app-primary); color: white; border-color: var(--app-primary); }
    .btn-contact.chat:hover { background: var(--app-info); color: white; border-color: var(--app-info); }

    /* Right Side: Approval Actions */
    .decision-zone {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 18px;
        padding-left: 35px;
        border-left: 2px dashed var(--app-border);
    }

    .decision-title {
        font-size: 0.9rem;
        font-weight: 900;
        color: var(--app-text-muted);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 10px;
        text-align: center;
    }

    .btn-decision {
        width: 100%;
        padding: 16px;
        border-radius: var(--app-radius-md);
        font-size: 1.05rem;
        font-weight: 800;
        border: none;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        transition: var(--app-bounce);
    }

    .btn-approve {
        background: linear-gradient(135deg, var(--app-success), #34d399);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .btn-approve:hover {
        background: var(--app-success-hover);
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
    }

    .btn-reject {
        background: var(--app-bg-main);
        color: var(--app-danger);
        border: 2px solid var(--app-danger);
    }

    .dark-theme .btn-reject {
        background: rgba(239, 68, 68, 0.05);
    }

    .btn-reject:hover {
        background: var(--app-danger);
        color: white;
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
    }

    /* --- EMPTY STATE UI --- */
    .empty-approval-state {
        grid-column: 1 / -1;
        background: var(--app-bg-card);
        border: 3px dashed var(--app-border);
        border-radius: var(--app-radius-lg);
        padding: 100px 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        animation: scaleIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: var(--app-shadow-sm);
    }

    .empty-state-icon {
        width: 120px;
        height: 120px;
        background: rgba(16, 185, 129, 0.1);
        color: var(--app-success);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 4rem;
        margin-bottom: 30px;
        animation: pulseSuccess 2s infinite;
        position: relative;
    }

    .empty-state-icon::after {
        content: '';
        position: absolute;
        top: -15px; left: -15px; right: -15px; bottom: -15px;
        border: 2px dashed var(--app-success);
        border-radius: 50%;
        animation: spin 10s linear infinite;
        opacity: 0.3;
    }

    .empty-state-title {
        font-size: 2.4rem;
        font-weight: 900;
        color: var(--app-text-title);
        margin-bottom: 15px;
        letter-spacing: -1px;
    }

    .empty-state-desc {
        font-size: 1.15rem;
        color: var(--app-text-muted);
        max-width: 450px;
        line-height: 1.6;
    }

    /* --- KEYFRAME ANIMATIONS --- */
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUpFade { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes scaleIn { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }
    @keyframes pulseSuccess { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); } 70% { box-shadow: 0 0 0 25px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
    @keyframes pulseWarning { 0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); } 70% { box-shadow: 0 0 0 15px rgba(245, 158, 11, 0); } 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); } }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    /* --- RESPONSIVE BREAKPOINTS --- */
    @media (max-width: 992px) {
        .shop-request-card {
            grid-template-columns: 1fr;
            gap: 25px;
        }
        .decision-zone {
            padding-left: 0;
            border-left: none;
            border-top: 2px dashed var(--app-border);
            padding-top: 25px;
            flex-direction: row;
        }
        .decision-title { display: none; }
        .btn-decision { flex: 1; }
    }

    @media (max-width: 768px) {
        .approval-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }
        .owner-profile-pill {
            flex-direction: column;
            text-align: center;
        }
        .owner-meta {
            justify-content: center;
        }
        .contact-action-group {
            flex-direction: column;
            width: 100%;
        }
        .decision-zone { flex-direction: column; }
        .empty-state-title { font-size: 1.8rem; }
    }
</style>

<div class="approval-master-container">
    
    <div class="mb-4">
        <?php echo displayFlashMessage(); ?>
    </div>

    <header class="approval-header">
        <div class="header-title-box">
            <h1><i class="fas fa-clipboard-check text-primary me-3"></i>จัดการคำร้องเปิดร้านค้า</h1>
            <p>ตรวจสอบและอนุมัติข้อมูลร้านค้าใหม่ เพื่อคัดกรองคุณภาพผู้ขายในระบบ</p>
        </div>
        <a href="admin_dashboard.php" class="btn-back-dashboard">
            <i class="fas fa-arrow-left"></i> กลับแผงควบคุม
        </a>
    </header>

    <?php if (count($pending_shops) > 0): ?>
        
        <div class="pending-stats-pill">
            <i class="fas fa-clock fa-spin"></i> มีร้านค้ารอการตรวจสอบทั้งหมด <?= count($pending_shops) ?> รายการ
        </div>

        <div class="approval-grid">
            <?php foreach ($pending_shops as $index => $s): 
                // จัดการรูปโปรไฟล์
                $owner_avatar = !empty($s['profile_img']) 
                    ? "../assets/images/profiles/" . htmlspecialchars($s['profile_img']) 
                    : "../assets/images/profiles/default_profile.png";
            ?>
                <article class="shop-request-card" style="animation-delay: <?= $index * 0.1 ?>s;">
                    
                    <div class="shop-content-zone">
                        
                        <div class="shop-title-wrapper">
                            <div class="shop-icon-circle">
                                <i class="fas fa-store"></i>
                            </div>
                            <h2 class="shop-name"><?php echo htmlspecialchars($s['shop_name']); ?></h2>
                        </div>

                        <div class="shop-desc">
                            <?php echo nl2br(htmlspecialchars($s['description'])); ?>
                        </div>

                        <div class="owner-profile-pill">
                            <img src="<?= $owner_avatar ?>" class="owner-avatar" alt="Owner Profile">
                            <div class="owner-details">
                                <div class="owner-name"><?php echo htmlspecialchars($s['fullname']); ?></div>
                                <div class="owner-meta">
                                    <span><i class="fas fa-chalkboard-teacher"></i> ห้องเรียน: <?php echo htmlspecialchars($s['class_room']); ?></span>
                                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($s['email']); ?></span>
                                </div>
                                
                                <div class="contact-action-group">
                                    <a href="../pages/view_profile.php?id=<?php echo $s['user_id']; ?>" class="btn-contact profile" target="_blank" title="ตรวจสอบประวัติผู้ใช้">
                                        <i class="fas fa-user-circle"></i> โปรไฟล์ผู้ใช้
                                    </a>
                                    <a href="../pages/chat.php?user=<?php echo $s['user_id']; ?>" class="btn-contact chat" target="_blank" title="ส่งข้อความสอบถามเพิ่มเติม">
                                        <i class="fas fa-comment-dots"></i> ส่งข้อความ
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="decision-zone">
                        <div class="decision-title">การตัดสินใจ</div>
                        
                        <form action="approve_shop.php" method="POST" style="width: 100%;">
                            <input type="hidden" name="shop_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn-decision btn-approve" 
                                    onclick="return confirm('ยืนยันการอนุมัติร้านค้านี้? ระบบจะปรับสถานะผู้ใช้นี้เป็นผู้ขาย (Seller) ทันที');" >
                                <i class="fas fa-check-circle"></i> อนุมัติคำร้อง
                            </button>
                        </form>
                        
                        <form action="approve_shop.php" method="POST" style="width: 100%;">
                            <input type="hidden" name="shop_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" name="action" value="reject" class="btn-decision btn-reject" 
                                    onclick="return confirm('🚨 คำเตือน: ยืนยันการปฏิเสธคำร้องนี้ ข้อมูลคำร้องจะถูกลบออกจากระบบและไม่สามารถกู้คืนได้');" >
                                <i class="fas fa-times-circle"></i> ไม่อนุมัติ (ลบทิ้ง)
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="empty-approval-state">
            <div class="empty-state-icon">
                <i class="fas fa-check-double"></i>
            </div>
            <h2 class="empty-state-title">ยอดเยี่ยม! ตรวจสอบครบแล้ว</h2>
            <p class="empty-state-desc">ขณะนี้ไม่มีคำร้องขอเปิดร้านค้าใหม่ค้างอยู่ในระบบ คุณสามารถไปทำอย่างอื่นต่อได้เลย</p>
            <div style="margin-top: 40px;">
                <a href="admin_dashboard.php" class="btn-back-dashboard" style="background: var(--app-primary); color: white; border-color: var(--app-primary); padding: 15px 30px; font-size: 1.1rem;">
                    <i class="fas fa-home"></i> กลับไปหน้า Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Add subtle entrance animation for cards
    const cards = document.querySelectorAll('.shop-request-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>