<?php
/**
 * BNCC Market - Master Report Management (Ultimate Admin Panel)
 * ควบรวมหน้ารายงานทั้งหมด + ระบบ Soft Delete และเก็บ Log 
 * เพิ่มเติม: ระบบแจ้งเตือนตักเตือนผู้ใช้ (Warning System)
 */
$pageTitle = "จัดการคำร้องเรียน - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// 1. ตรวจสอบสิทธิ์ (ต้องเป็น Admin หรือ Teacher เท่านั้น)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    redirect('../pages/index.php');
}

$db = getDB();

// =========================================================================
// 🚀 2. ACTION HANDLERS (จัดการการกดปุ่มต่างๆ)
// =========================================================================

// --- A. จัดการรีพอร์ตทั่วไป (ร้านค้า / ผู้ใช้) ผ่าน GET ---
if (isset($_GET['action']) && isset($_GET['report_id'])) {
    $report_id = (int)$_GET['report_id'];
    $action = $_GET['action'];
    
    if ($action === 'resolve') {
        $update = $db->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
        if ($update->execute([$report_id])) {
            logAdminAction('RESOLVE_REPORT', 'report', $report_id, "แอดมินทำเครื่องหมายจัดการรีพอร์ตแล้ว");
            $_SESSION['flash_message'] = "ทำเครื่องหมายว่าจัดการแล้วเรียบร้อย";
            $_SESSION['flash_type'] = "success";
        }
    } 
    elseif ($action === 'delete_report') {
        // ทำ Soft Delete สำหรับรีพอร์ต
        $update = $db->prepare("UPDATE reports SET is_deleted = 1 WHERE id = ?");
        if ($update->execute([$report_id])) {
            logAdminAction('SOFT_DELETE_REPORT', 'report', $report_id, "แอดมินลบรีพอร์ตนี้ (Soft Delete)");
            $_SESSION['flash_message'] = "ลบคำร้องเรียนออกจากระบบเรียบร้อยแล้ว (Soft Delete)";
            $_SESSION['flash_type'] = "success";
        }
    }
    redirect('manage_reports.php');
}

// --- B. จัดการคอมเมนต์ที่ถูกรีพอร์ต & ระบบตักเตือน (ผ่าน POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 🎯 🛠️ ระบบที่ 1: จัดการลบคอมเมนต์และตักเตือนอัตโนมัติ
    if ($action === 'delete_comment' || $action === 'dismiss_reports') {
        $comment_id = $_POST['comment_id'] ?? null;
        if ($comment_id) {
            if ($action === 'delete_comment') {
                // ดึง ID เจ้าของคอมเมนต์เพื่อส่งคำเตือน
                $rev_info = $db->prepare("SELECT user_id FROM reviews WHERE id = ?");
                $rev_info->execute([$comment_id]);
                $review_data = $rev_info->fetch();

                // 1. Soft Delete คอมเมนต์
                $db->prepare("UPDATE reviews SET is_deleted = 1 WHERE id = ?")->execute([$comment_id]);
                
                // 2. เปลี่ยนสถานะรีพอร์ตเป็น 'resolved'
                $db->prepare("UPDATE reports SET status = 'resolved' WHERE target_type = 'comment' AND target_id = ?")->execute([$comment_id]);
                
                // 3. 🚨 ส่งการแจ้งเตือนตักเตือนอัตโนมัติ 🚨
                if ($review_data) {
                    $warn_msg = "⚠️ ข้อความของคุณถูกลบโดยผู้ดูแลระบบเนื่องจากละเมิดกฎของชุมชน กรุณาระมัดระวังการแสดงความคิดเห็น หากผิดกฎซ้ำอาจถูกระงับบัญชี";
                    sendNotification($review_data['user_id'], 'warning', $warn_msg, '#');
                }

                // 4. เก็บ Log แอดมิน
                logAdminAction('DELETE_REPORTED_COMMENT', 'comment', $comment_id, "แอดมินลบคอมเมนต์ที่ผิดกฎและส่งแจ้งเตือนตักเตือน");
                
                $_SESSION['flash_message'] = "ลบคอมเมนต์พร้อมแจ้งเตือนผู้ใช้เรียบร้อยแล้ว";
                $_SESSION['flash_type'] = "success";
                
            } elseif ($action === 'dismiss_reports') {
                // ปล่อยผ่าน (Dismiss) 
                $db->prepare("UPDATE reports SET status = 'dismissed' WHERE target_type = 'comment' AND target_id = ?")->execute([$comment_id]);
                logAdminAction('DISMISS_COMMENT_REPORT', 'comment', $comment_id, "แอดมินเลือกปล่อยผ่านการรายงานคอมเมนต์นี้");

                $_SESSION['flash_message'] = "ปล่อยผ่านคำร้องเรียนสำหรับคอมเมนต์นี้แล้ว";
                $_SESSION['flash_type'] = "info";
            }
            redirect('manage_reports.php');
        }
    }

    // 🎯 🛠️ ระบบที่ 2: จัดการส่งคำเตือนแบบกำหนดเอง (Custom Warning)
    if ($action === 'send_custom_warning') {
        $target_user_id = $_POST['target_user_id'] ?? null;
        $report_id = $_POST['report_id'] ?? null;
        $warning_message = trim($_POST['warning_message'] ?? '');

        if ($target_user_id && !empty($warning_message)) {
            // 1. ส่งการแจ้งเตือนเข้าระบบ
            $full_msg = "🚨 คำเตือนจากผู้ดูแลระบบ: " . $warning_message;
            sendNotification($target_user_id, 'warning', $full_msg, '#');

            // 2. อัปเดตสถานะรีพอร์ตว่า "จัดการแล้ว"
            if ($report_id) {
                $db->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$report_id]);
            }

            // 3. เก็บ Log
            logAdminAction('SEND_CUSTOM_WARNING', 'user', $target_user_id, "ส่งคำเตือน: " . mb_substr($warning_message, 0, 50) . "...");

            $_SESSION['flash_message'] = "ส่งคำเตือนไปยังผู้ใช้และเคลียร์เคสเรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "success";
        }
        redirect('manage_reports.php');
    }
}

// =========================================================================
// 🚀 3. DATA FETCHING (ดึงข้อมูลมาแสดงผล)
// =========================================================================

// --- C. SQL ดึงข้อมูลคอมเมนต์ที่ถูกรีพอร์ต ---
$sql_comments = "
    SELECT 
        rev.id AS comment_id,
        rev.comment AS comment_text,
        rev.created_at AS comment_date,
        p.id AS product_id,
        p.title AS product_name,
        u.id AS author_id,
        u.fullname AS author_name,
        u.profile_img,
        u.is_banned,
        COUNT(r.id) AS report_count, 
        GROUP_CONCAT(DISTINCT r.reason SEPARATOR ' <br>• ') AS all_reasons, 
        (
            SELECT COUNT(*) 
            FROM reports r2 
            JOIN reviews rev2 ON r2.target_id = rev2.id 
            WHERE r2.target_type = 'comment' AND rev2.user_id = u.id AND r2.is_deleted = 0
        ) AS author_total_reports
    FROM reports r
    JOIN reviews rev ON r.target_id = rev.id AND r.target_type = 'comment'
    JOIN users u ON rev.user_id = u.id
    JOIN products p ON rev.product_id = p.id
    WHERE r.is_deleted = 0 AND rev.is_deleted = 0 AND r.status = 'pending'
    GROUP BY rev.id
    ORDER BY report_count DESC, r.created_at DESC
";
$reported_comments = $db->query($sql_comments)->fetchAll();

// --- D. SQL ดึงข้อมูลรีพอร์ตทั่วไป (เพิ่มการดึง offender_id เพื่อส่งคำเตือน) ---
$sql_general = "
    SELECT r.*, u.fullname as reporter_name,
    CASE 
        WHEN r.target_type = 'user' THEN r.target_id 
        WHEN r.target_type = 'shop' THEN (SELECT user_id FROM shops WHERE id = r.target_id)
    END as offender_id
    FROM reports r 
    JOIN users u ON r.reporter_id = u.id 
    WHERE r.target_type != 'comment' AND r.is_deleted = 0
    ORDER BY r.status ASC, r.created_at DESC
";
$general_reports = $db->query($sql_general)->fetchAll();
?>

<style>
    /* ============================================================
       🛠️ SOLID DESIGN SYSTEM - ADMIN DASHBOARD
       ============================================================ */
    :root {
        --solid-bg: #f8fafc;
        --solid-card: #ffffff;
        --solid-text: #0f172a;
        --solid-border: #cbd5e1;
        --solid-primary: #4f46e5;
        --solid-danger: #ef4444;
        --solid-warning: #f59e0b;
        --solid-success: #10b981;
    }

    .dark-theme {
        --solid-bg: #0f172a;
        --solid-card: #1e293b;
        --solid-text: #ffffff;
        --solid-border: #334155;
    }

    body { background-color: var(--solid-bg) !important; color: var(--solid-text); }

    .admin-wrapper { max-width: 1200px; margin: 40px auto 80px; padding: 0 20px; }

    /* 🏰 Header */
    .mgmt-header { 
        margin-bottom: 40px; 
        border-left: 6px solid var(--solid-danger); 
        padding-left: 25px; 
        animation: dropIn 0.5s ease forwards;
    }
    .mgmt-header h1 { font-size: 2.2rem; font-weight: 900; margin: 0 0 5px; color: var(--solid-text); letter-spacing: -1px; }

    /* 🧱 Section Cards */
    .section-title {
        font-size: 1.5rem;
        font-weight: 900;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--solid-text);
        border-bottom: 3px solid var(--solid-border);
        padding-bottom: 10px;
    }

    .mgmt-card { 
        background: var(--solid-card); 
        border-radius: 24px; 
        border: 2px solid var(--solid-border); 
        margin-bottom: 50px; 
        box-shadow: 0 20px 40px rgba(0,0,0,0.05); 
        overflow: hidden;
        opacity: 0;
        transform: translateY(20px);
        animation: dropIn 0.8s ease forwards;
    }

    /* 📋 Solid Tables */
    .table-custom { width: 100%; border-collapse: collapse; }
    .table-custom th { 
        background: var(--solid-bg); 
        color: var(--text-muted); 
        font-size: 0.85rem; 
        text-transform: uppercase; 
        letter-spacing: 1px; 
        padding: 20px; 
        border-bottom: 2px solid var(--solid-border); 
        text-align: left;
    }
    .table-custom td { 
        padding: 20px; 
        border-bottom: 1px solid var(--solid-border); 
        vertical-align: top;
    }
    .table-custom tr:last-child td { border-bottom: none; }
    .table-custom tr:hover td { background: rgba(79, 70, 229, 0.03); }

    /* 🏷️ Badges */
    .solid-badge { padding: 6px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; border: 2px solid transparent; }
    .badge-red { background: rgba(239, 68, 68, 0.1); color: var(--solid-danger); border-color: rgba(239, 68, 68, 0.3); }
    .badge-yellow { background: rgba(245, 158, 11, 0.1); color: var(--solid-warning); border-color: rgba(245, 158, 11, 0.3); }
    .badge-green { background: rgba(16, 185, 129, 0.1); color: var(--solid-success); border-color: rgba(16, 185, 129, 0.3); }
    .badge-blue { background: rgba(99, 102, 241, 0.1); color: var(--solid-primary); border-color: rgba(99, 102, 241, 0.3); }

    /* 🔘 Buttons */
    .btn-solid-action {
        padding: 10px 18px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 0.85rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
        transition: 0.2s;
        text-decoration: none;
        width: 100%;
    }
    .btn-solid-danger { background: var(--solid-danger); color: white; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }
    .btn-solid-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(239, 68, 68, 0.4); }
    
    .btn-solid-success { background: var(--solid-success); color: white; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); }
    .btn-solid-success:hover { transform: translateY(-2px); filter: brightness(1.1); }

    /* 🎯 🛠️ ปุ่มตักเตือนสีเหลือง */
    .btn-solid-warning { background: var(--solid-warning); color: #000; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3); }
    .btn-solid-warning:hover { transform: translateY(-2px); filter: brightness(1.1); }

    .btn-solid-outline { background: var(--solid-bg); border: 2px solid var(--solid-border); color: var(--solid-text); }
    .btn-solid-outline:hover { border-color: var(--solid-text); }

    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .empty-state i { font-size: 4rem; color: var(--solid-border); margin-bottom: 15px; }

    @keyframes dropIn { to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .table-custom th { display: none; }
        .table-custom tr { display: block; border-bottom: 3px solid var(--solid-border); }
        .table-custom td { display: block; text-align: right; padding: 15px; border-bottom: 1px dashed var(--solid-border); }
        .table-custom td::before { content: attr(data-label); float: left; font-weight: 800; color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; }
    }

    /* 🚨 Admin Modal Styles */
    .modal-overlay {
        position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.9); display: none; align-items: center; justify-content: center;
    }
    .modal-solid {
        background: var(--solid-card); padding: 40px; border-radius: 32px; width: 90%; max-width: 480px;
        border: 2px solid var(--solid-border); box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    }
</style>

<div class="admin-wrapper">
    
    <div class="mgmt-header">
        <h1><i class="fas fa-shield-alt text-danger"></i> ศูนย์ควบคุมคำร้องเรียน (Report Center)</h1>
        <p style="color: var(--text-muted); font-weight: 600; font-size: 1.1rem;">ตรวจสอบและดำเนินการกับสมาชิกที่ทำผิดกฎ (สิทธิ์การเข้าถึง: <?= strtoupper($_SESSION['role']) ?>)</p>
    </div>

    <?php echo displayFlashMessage(); ?>

    <h2 class="section-title"><i class="fas fa-comments text-primary"></i> รีวิว/คอมเมนต์ที่ถูกรายงาน (รอการตรวจสอบ)</h2>
    <div class="mgmt-card" style="animation-delay: 0.1s;">
        <?php if (count($reported_comments) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 35%;">ข้อความที่ถูกรายงาน</th>
                            <th style="width: 25%;">ผู้คอมเมนต์ (ประวัติ)</th>
                            <th style="width: 20%;">เหตุผลจากผู้ใช้</th>
                            <th style="text-align: right; width: 20%;">การจัดการ (เลือก 1 อย่าง)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reported_comments as $report): 
                            $avatar = (!empty($report['profile_img'])) ? "../assets/images/profiles/" . $report['profile_img'] : "../assets/images/profiles/default_profile.png";
                        ?>
                        <tr>
                            <td data-label="ข้อความที่ถูกรายงาน">
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px; font-weight: 700;">
                                    <i class="fas fa-box text-primary"></i> สินค้า: 
                                    <a href="../pages/product_detail.php?id=<?= $report['product_id'] ?>" target="_blank" style="color: var(--solid-text); text-decoration: underline;"><?= e($report['product_name']) ?></a>
                                </div>
                                <div style="background: rgba(239, 68, 68, 0.05); border-left: 4px solid var(--solid-danger); padding: 15px; border-radius: 12px; font-size: 1rem; color: var(--solid-text); font-weight: 600;">
                                    "<?= nl2br(e($report['comment_text'])) ?>"
                                </div>
                            </td>
                            
                            <td data-label="ผู้คอมเมนต์">
                                <div style="display: flex; align-items: center; gap: 15px; justify-content: flex-end;">
                                    <img src="<?= $avatar ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--solid-border);">
                                    <div style="text-align: left;">
                                        <div style="font-weight: 800; color: var(--solid-text); font-size: 1.1rem;">
                                            <?= e($report['author_name']) ?>
                                            <?php if($report['is_banned']): ?> <span class="solid-badge badge-red" style="padding: 2px 6px; font-size: 0.6rem;">BANNED</span> <?php endif; ?>
                                        </div>
                                        <div style="margin-top: 5px;">
                                            <?php if ($report['author_total_reports'] >= 3): ?>
                                                <span class="solid-badge badge-red"><i class="fas fa-skull"></i> ผิดกฎ <?= $report['author_total_reports'] ?> ครั้ง</span>
                                            <?php elseif ($report['author_total_reports'] > 0): ?>
                                                <span class="solid-badge badge-yellow"><i class="fas fa-history"></i> ผิดกฎ <?= $report['author_total_reports'] ?> ครั้ง</span>
                                            <?php else: ?>
                                                <span class="solid-badge badge-green"><i class="fas fa-check-circle"></i> เพิ่งเคยโดนครั้งแรก</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td data-label="เหตุผล">
                                <div style="margin-bottom: 5px;">
                                    <span class="solid-badge badge-red"><i class="fas fa-users"></i> รีพอร์ต <?= $report['report_count'] ?> คน</span>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; font-weight: 600; margin-top: 8px;">
                                    • <?= $report['all_reasons'] ?>
                                </div>
                            </td>
                            
                            <td data-label="การจัดการ" style="text-align: right;">
                                <form method="POST" style="display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
                                    <input type="hidden" name="comment_id" value="<?= $report['comment_id'] ?>">
                                    
                                    <button type="submit" name="action" value="dismiss_reports" class="btn-solid-action btn-solid-outline" onclick="return confirm('ยืนยันที่จะปล่อยผ่านคำร้องเรียนนี้ใช่หรือไม่? (จะไม่ลบคอมเมนต์)')">
                                        <i class="fas fa-times"></i> 1. ปล่อยผ่าน
                                    </button>
                                    
                                    <button type="submit" name="action" value="delete_comment" class="btn-solid-action btn-solid-danger" onclick="return confirm('ยืนยันลบคอมเมนต์นี้และแจ้งเตือนผู้กระทำผิดใช่หรือไม่?')">
                                        <i class="fas fa-trash-alt"></i> 2. ลบคอมเมนต์ + ตักเตือน
                                    </button>
                                    
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-comment-dots text-success"></i>
                <h3 style="font-weight: 800; font-size: 1.5rem; color: var(--solid-text);">ไม่มีคอมเมนต์ที่ถูกรายงานค้างอยู่</h3>
            </div>
        <?php endif; ?>
    </div>

    <h2 class="section-title" style="margin-top: 60px;"><i class="fas fa-store-alt-slash text-warning"></i> รายงานทั่วไป (ร้านค้า / ผู้ใช้)</h2>
    <div class="mgmt-card" style="animation-delay: 0.2s;">
        <?php if (count($general_reports) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>วันที่แจ้ง</th>
                            <th>ผู้รายงาน</th>
                            <th>เป้าหมาย</th>
                            <th style="width: 30%;">เหตุผล</th>
                            <th>สถานะ</th>
                            <th style="text-align: right;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($general_reports as $r): 
                            $status_class = 'badge-yellow'; $status_icon = 'fa-hourglass-half'; $status_text = 'รอตรวจสอบ';
                            if ($r['status'] === 'resolved') { $status_class = 'badge-green'; $status_icon = 'fa-check'; $status_text = 'จัดการแล้ว'; }
                            if ($r['status'] === 'dismissed') { $status_class = 'badge-red'; $status_icon = 'fa-times'; $status_text = 'เพิกเฉย'; }
                        ?>
                            <tr>
                                <td data-label="วันที่แจ้ง">
                                    <div style="font-weight: 700; color: var(--solid-text);"><?= date('d/m/Y', strtotime($r['created_at'])) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= date('H:i', strtotime($r['created_at'])) ?> น.</div>
                                </td>
                                <td data-label="ผู้รายงาน" style="font-weight: 800; color: var(--solid-text);">
                                    <?= e($r['reporter_name']) ?>
                                </td>
                                <td data-label="เป้าหมาย">
                                    <span class="solid-badge badge-blue">
                                        <i class="fas <?= $r['target_type'] == 'shop' ? 'fa-store' : 'fa-user' ?>"></i> 
                                        <?= strtoupper($r['target_type']) ?>
                                    </span>
                                </td>
                                <td data-label="เหตุผล" style="font-weight: 600; color: var(--text-muted); line-height: 1.5;">
                                    <?= e($r['reason']) ?>
                                </td>
                                <td data-label="สถานะ">
                                    <span class="solid-badge <?= $status_class ?>">
                                        <i class="fas <?= $status_icon ?>"></i> <?= $status_text ?>
                                    </span>
                                </td>
                                <td data-label="การจัดการ" style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <?php 
                                        $view_url = "#";
                                        if ($r['target_type'] === 'user') $view_url = "../pages/view_profile.php?id=" . $r['target_id'];
                                        if ($r['target_type'] === 'shop') $view_url = "../pages/shop_profile.php?id=" . $r['target_id'];
                                        ?>
                                        
                                        <a href="<?= $view_url ?>" target="_blank" class="btn-solid-action btn-solid-outline" title="ตรวจสอบของจริง" style="width: auto; padding: 10px;"><i class="fas fa-eye"></i></a>
                                        
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <button type="button" onclick="openWarningModal(<?= $r['offender_id'] ?>, <?= $r['id'] ?>)" class="btn-solid-action btn-solid-warning" title="ส่งข้อความตักเตือนและเคลียร์เคส" style="width: auto; padding: 10px;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </button>
                                            
                                            <a href="?action=resolve&report_id=<?= $r['id'] ?>" class="btn-solid-action btn-solid-success" title="ทำเครื่องหมายว่าจัดการแล้ว" style="width: auto; padding: 10px;" onclick="return confirm('ยืนยันว่าจัดการเคสนี้แล้ว?')"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                        
                                        <a href="?action=delete_report&report_id=<?= $r['id'] ?>" class="btn-solid-action btn-solid-danger" title="ลบรีพอร์ตนี้ (Soft Delete)" style="width: auto; padding: 10px;" onclick="return confirm('ต้องการลบการแจ้งเตือนนี้ใช่หรือไม่?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shield-heart text-primary"></i>
                <h3 style="font-weight: 800; font-size: 1.5rem; color: var(--solid-text);">ไม่มีการรายงานร้านค้าหรือผู้ใช้</h3>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="warningModal" class="modal-overlay">
    <div class="modal-solid" style="border-color: var(--solid-warning);">
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="width: 70px; height: 70px; background: rgba(245, 158, 11, 0.1); color: var(--solid-warning); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 2rem;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="font-size: 1.5rem; font-weight: 900; margin: 0;">ส่งคำเตือนจากระบบ</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">ข้อความนี้จะถูกส่งตรงไปยังกระดิ่งแจ้งเตือนของผู้กระทำผิด</p>
        </div>
        
        <form action="manage_reports.php" method="POST">
            <input type="hidden" name="action" value="send_custom_warning">
            <input type="hidden" name="target_user_id" id="warning_target_user_id">
            <input type="hidden" name="report_id" id="warning_report_id">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <textarea name="warning_message" class="form-control" required style="width:100%; min-height:120px; border-radius: 16px; padding: 20px; background: var(--solid-bg); border: 2px solid var(--solid-border); color: var(--solid-text); font-weight: 600; outline: none; font-family: 'Prompt', sans-serif;" placeholder="พิมพ์ข้อความตักเตือน เช่น 'กรุณาใช้คำสุภาพในการตั้งชื่อร้าน หากพบอีกจะทำการระงับบัญชี'"></textarea>
            </div>
            
            <div style="display:flex; gap:12px;">
                <button type="button" onclick="closeWarningModal()" class="btn-solid-action btn-solid-outline" style="flex:1; padding: 15px;">ยกเลิก</button>
                <button type="submit" class="btn-solid-action btn-solid-warning" style="flex:1; padding: 15px; font-size: 1rem;">ส่งคำเตือนทันที</button>
            </div>
        </form>
    </div>
</div>

<script>
    // สคริปต์เพื่อให้ตารางเด้งขึ้นมาสวยๆ
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.mgmt-card').forEach(el => observer.observe(el));

    // 🎯 🛠️ Script ควบคุม Modal ตักเตือน
    function openWarningModal(userId, reportId) {
        document.getElementById('warning_target_user_id').value = userId;
        document.getElementById('warning_report_id').value = reportId;
        document.getElementById('warningModal').style.display = 'flex';
    }
    
    function closeWarningModal() {
        document.getElementById('warningModal').style.display = 'none';
    }

    // ปิด Modal เมื่อคลิกพื้นที่ว่าง
    window.onclick = function(event) {
        const warningM = document.getElementById('warningModal');
        if (event.target == warningM) {
            closeWarningModal();
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>