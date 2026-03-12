<?php
$pageTitle = "จัดการคำร้องเปิดร้านค้า (Shop Approvals) - BNCC Market";
require_once '../includes/header.php';
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
            
            // หมายเหตุ: ในระบบจริงอาจจะต้องอัปเดต role ของ user เป็น 'seller' ด้วย
            // $updateUser = $db->prepare("UPDATE users SET role = 'seller' WHERE id = (SELECT user_id FROM shops WHERE id = ?)");
            // $updateUser->execute([$shop_id]);

            $_SESSION['flash_message'] = "✅ อนุมัติร้านค้าเรียบร้อยแล้ว ร้านค้าสามารถเริ่มลงขายสินค้าได้ทันที";
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

    // Refresh หน้าเพื่อป้องกันการกด Submit ซ้ำ
    header("Location: approve_shop.php");
    exit();
}

// ⚙️ Logic 2: ดึงข้อมูลร้านค้าที่รออนุมัติ (Pending)
$sql = "SELECT s.*, u.id as user_id, u.fullname, u.class_room, u.email, u.profile_img 
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.status = 'pending' 
        ORDER BY s.created_at ASC";
$stmt = $db->query($sql);
$pending_shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    }

    /* --- PAGE HEADER --- */
    .approval-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--app-border);
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
        gap: 8px;
        padding: 12px 20px;
        background: var(--app-bg-card);
        color: var(--app-text-title);
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius-md);
        font-weight: 700;
        text-decoration: none;
        transition: var(--app-transition);
        box-shadow: var(--app-shadow-sm);
    }

    .btn-back-dashboard:hover {
        background: var(--app-bg-hover);
        border-color: var(--app-primary);
        color: var(--app-primary);
        transform: translateX(-4px);
    }

    /* --- STATS COUNTER --- */
    .pending-stats-pill {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 8px 16px;
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
        border-radius: var(--app-radius-full);
        font-weight: 800;
        font-size: 0.9rem;
        margin-bottom: 25px;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    /* --- DATA GRID SYSTEM --- */
    .approval-grid {
        display: grid;
        grid-template-columns: 1fr; /* 1 column for detailed view */
        gap: 25px;
        padding-bottom: 60px;
    }

    /* --- APPROVAL CARD UI --- */
    .shop-request-card {
        background: var(--app-bg-card);
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius-lg);
        padding: 30px;
        display: grid;
        grid-template-columns: 1fr 300px; /* Content | Actions */
        gap: 30px;
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
        width: 6px;
        height: 100%;
        background: linear-gradient(to bottom, var(--app-primary), #a855f7);
        border-radius: 6px 0 0 6px;
    }

    .shop-request-card:hover {
        box-shadow: var(--app-shadow-lg);
        border-color: var(--app-border-focus);
        transform: translateY(-5px);
    }

    /* Left Side: Shop Details */
    .shop-content-zone {
        display: flex;
        flex-direction: column;
    }

    .shop-title-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .shop-icon-circle {
        width: 40px;
        height: 40px;
        background: rgba(79, 70, 229, 0.1);
        color: var(--app-primary);
        border-radius: 12px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.2rem;
    }

    .shop-name {
        font-size: 1.6rem;
        font-weight: 900;
        color: var(--app-text-title);
        margin: 0;
    }

    .shop-desc {
        font-size: 1rem;
        color: var(--app-text-body);
        line-height: 1.7;
        margin-bottom: 25px;
        background: var(--app-bg-main);
        padding: 15px 20px;
        border-radius: var(--app-radius-md);
        border: 1px solid var(--app-border);
    }

    /* Owner Information Profile Pill */
    .owner-profile-pill {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-radius: var(--app-radius-md);
        border: 1px dashed var(--app-border);
        background: transparent;
    }

    .owner-avatar {
        width: 45px;
        height: 45px;
        border-radius: var(--app-radius-full);
        object-fit: cover;
        border: 2px solid var(--app-primary);
    }

    .owner-details {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .owner-name {
        font-size: 1rem;
        font-weight: 800;
        color: var(--app-text-title);
    }

    .owner-meta {
        font-size: 0.8rem;
        color: var(--app-text-muted);
        display: flex;
        gap: 15px;
        margin-top: 2px;
    }

    .owner-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Contact Buttons (Profile / Chat) */
    .contact-action-group {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .btn-contact {
        padding: 8px 16px;
        border-radius: var(--app-radius-sm);
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: var(--app-transition);
        border: 1px solid var(--app-border);
        background: var(--app-bg-card);
        color: var(--app-text-body);
    }

    .btn-contact:hover {
        background: var(--app-bg-hover);
        color: var(--app-text-title);
    }

    .btn-contact.profile i { color: var(--app-primary); }
    .btn-contact.chat i { color: var(--app-info); }

    /* Right Side: Approval Actions */
    .decision-zone {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 15px;
        padding-left: 30px;
        border-left: 1px dashed var(--app-border);
    }

    .decision-title {
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--app-text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
        text-align: center;
    }

    .btn-decision {
        width: 100%;
        padding: 14px;
        border-radius: var(--app-radius-md);
        font-size: 1rem;
        font-weight: 800;
        border: none;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        transition: var(--app-bounce);
    }

    .btn-approve {
        background: var(--app-success);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
    }

    .btn-approve:hover {
        background: var(--app-success-hover);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }

    .btn-reject {
        background: #fee2e2;
        color: var(--app-danger);
    }

    .dark-theme .btn-reject {
        background: rgba(239, 68, 68, 0.1);
    }

    .btn-reject:hover {
        background: var(--app-danger);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
    }

    /* --- EMPTY STATE UI --- */
    .empty-approval-state {
        grid-column: 1 / -1;
        background: var(--app-bg-card);
        border: 3px dashed var(--app-border);
        border-radius: var(--app-radius-lg);
        padding: 80px 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        animation: scaleIn 0.5s ease-out;
    }

    .empty-state-icon {
        width: 100px;
        height: 100px;
        background: rgba(16, 185, 129, 0.1);
        color: var(--app-success);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 3.5rem;
        margin-bottom: 25px;
        animation: pulseSuccess 2s infinite;
    }

    .empty-state-title {
        font-size: 2rem;
        font-weight: 900;
        color: var(--app-text-title);
        margin-bottom: 10px;
    }

    .empty-state-desc {
        font-size: 1.1rem;
        color: var(--app-text-muted);
        max-width: 400px;
    }

    /* --- KEYFRAME ANIMATIONS --- */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUpFade {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    @keyframes pulseSuccess {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        70% { box-shadow: 0 0 0 20px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* --- RESPONSIVE BREAKPOINTS --- */
    @media (max-width: 992px) {
        .shop-request-card {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .decision-zone {
            padding-left: 0;
            border-left: none;
            border-top: 1px dashed var(--app-border);
            padding-top: 20px;
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
            flex-direction: column;
            gap: 5px;
            align-items: center;
        }
        .contact-action-group {
            justify-content: center;
            width: 100%;
        }
        .btn-contact { flex: 1; justify-content: center; }
        .decision-zone { flex-direction: column; }
    }
</style>

<div class="approval-master-container">
    
    <div class="mb-4">
        <?php echo displayFlashMessage(); ?>
    </div>

    <header class="approval-header">
        <div class="header-title-box">
            <h1><i class="fas fa-clipboard-check text-primary me-2"></i> จัดการคำร้องเปิดร้านค้า</h1>
            <p>ตรวจสอบและอนุมัติร้านค้าใหม่ เพื่อให้ระบบตลาดกลางมีคุณภาพและปลอดภัย</p>
        </div>
        <a href="admin_dashboard.php" class="btn-back-dashboard">
            <i class="fas fa-arrow-left"></i> กลับสู่แผงควบคุม
        </a>
    </header>

    <?php if (count($pending_shops) > 0): ?>
        
        <div class="pending-stats-pill">
            <i class="fas fa-clock fa-spin"></i> มีร้านค้ารอการตรวจสอบทั้งหมด <?= count($pending_shops) ?> รายการ
        </div>

        <div class="approval-grid">
            <?php foreach ($pending_shops as $index => $s): 
                // จัดการรูปโปรไฟล์ (Fallback to default if empty)
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
                                        <i class="fas fa-id-badge"></i> ดูประวัติผู้ใช้
                                    </a>
                                    <a href="../pages/chat.php?user=<?php echo $s['user_id']; ?>" class="btn-contact chat" target="_blank" title="ส่งข้อความสอบถามเพิ่มเติม">
                                        <i class="fas fa-comment-dots"></i> แชทสอบถาม
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="decision-zone">
                        <div class="decision-title">การดำเนินการ</div>
                        
                        <form action="approve_shop.php" method="POST" style="width: 100%;">
                            <input type="hidden" name="shop_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn-decision btn-approve" aria-label="Approve Shop">
                                <i class="fas fa-check-circle"></i> อนุมัติร้านค้านี้
                            </button>
                        </form>
                        
                        <form action="approve_shop.php" method="POST" style="width: 100%;">
                            <input type="hidden" name="shop_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" name="action" value="reject" class="btn-decision btn-reject" 
                                    onclick="return confirm('⚠️ ยืนยันการปฏิเสธคำร้องนี้? คำร้องจะถูกลบออกจากระบบทันทีและไม่สามารถกู้คืนได้');" 
                                    aria-label="Reject Shop">
                                <i class="fas fa-times-circle"></i> ไม่อนุมัติ
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="empty-approval-state">
            <div class="empty-state-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="empty-state-title">ตรวจสอบครบถ้วนแล้ว!</h2>
            <p class="empty-state-desc">ขณะนี้ไม่มีคำร้องขอเปิดร้านค้าใหม่ในระบบ ทุกอย่างเป็นระเบียบเรียบร้อย</p>
            <div style="margin-top: 30px;">
                <a href="admin_dashboard.php" class="btn-back-dashboard" style="background: var(--app-primary); color: white; border-color: var(--app-primary);">
                    <i class="fas fa-home"></i> กลับหน้า Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Optionally add extra interactive scripts here if needed in the future
    // Currently, CSS animations handle the smooth entry of elements.
    console.log('Approval UI Loaded Successfully.');
});
</script>

<?php require_once '../includes/footer.php'; ?>