<?php
/**
 * BNCC Market - Reported Comments Management
 * หน้าจัดการคอมเมนต์ที่ถูกรีพอร์ต (แสดงสถิติและประวัติคนโดนรีพอร์ต)
 */
require_once '../includes/functions.php';

// 1. ตรวจสอบสิทธิ์ (ต้องเป็น Admin หรือ Teacher เท่านั้น)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    redirect('../pages/index.php');
}

$pageTitle = "จัดการรีพอร์ตคอมเมนต์ - BNCC Market";
require_once '../includes/header.php';

$db = getDB();

// --- 🛠️ 2. จัดการ Action เมื่อแอดมินกดปุ่ม (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $comment_id = $_POST['comment_id'] ?? null;

    if ($comment_id) {
        if ($action === 'delete_comment') {
           
            // ✅ แก้เป็น Soft Delete
            $db->prepare("UPDATE reviews SET is_deleted = 1 WHERE id = ?")->execute([$comment_id]);
            $db->prepare("DELETE FROM reports WHERE target_type = 'comment' AND target_id = ?")->execute([$comment_id]);
            
            $_SESSION['flash_message'] = "ลบคอมเมนต์ที่ผิดกฎเรียบร้อยแล้ว";
            $_SESSION['flash_type'] = "success";
            logAdminAction('DELETE_REPORTED_COMMENT', 'comment', $comment_id, "ลบคอมเมนต์ที่ถูกรีพอร์ต");
            
        } elseif ($action === 'dismiss_reports') {
            // ยกเลิกการรายงาน (มองว่าไม่ผิด) ลบรีพอร์ตทิ้งแต่เก็บคอมเมนต์ไว้
            $db->prepare("DELETE FROM reports WHERE target_type = 'comment' AND target_id = ?")->execute([$comment_id]);
            
            $_SESSION['flash_message'] = "ยกเลิกการรายงานสำหรับคอมเมนต์นี้แล้ว";
            $_SESSION['flash_type'] = "info";
        }
        redirect('manage_reported_comments.php');
    }
}

// --- 🛠️ 3. SQL อัจฉริยะ: ดึงข้อมูลสรุปการรีพอร์ต ---
// Query นี้จะรวบรวมรีพอร์ตของคอมเมนต์เดียวกันเข้าด้วยกัน และนับจำนวนครั้งที่คนๆ นี้เคยโดนรีพอร์ต
$sql = "
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
        COUNT(r.id) AS report_count, -- จำนวนคนที่รีพอร์ตคอมเมนต์นี้
        GROUP_CONCAT(DISTINCT r.reason SEPARATOR ' <br>• ') AS all_reasons, -- รวมเหตุผลทั้งหมด
        (
            -- นับว่า 'ผู้เขียนคอมเมนต์นี้' เคยถูกรีพอร์ตคอมเมนต์รวมทั้งหมดกี่ครั้ง (ประวัติความประพฤติ)
            SELECT COUNT(*) 
            FROM reports r2 
            JOIN reviews rev2 ON r2.target_id = rev2.id 
            WHERE r2.target_type = 'comment' AND rev2.user_id = u.id
        ) AS author_total_reports
    FROM reports r
    JOIN reviews rev ON r.target_id = rev.id AND r.target_type = 'comment'
    JOIN users u ON rev.user_id = u.id
    JOIN products p ON rev.product_id = p.id
    GROUP BY rev.id
    ORDER BY report_count DESC, r.created_at DESC
";

$stmt = $db->query($sql);
$reported_comments = $stmt->fetchAll();
?>

<style>
    .mgmt-header { margin-bottom: 30px; border-left: 6px solid #f87171; padding-left: 20px; }
    .mgmt-card { background: var(--bg-card); border-radius: 24px; padding: 30px; border: 1px solid var(--border-color); margin-bottom: 40px; box-shadow: var(--shadow-md); }
    .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 15px; }
    .table-custom tr { background: var(--bg-body); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .table-custom td, .table-custom th { padding: 15px 20px; }
    .table-custom th { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; border: none; background: transparent; box-shadow: none; }
    
    .danger-badge { background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; }
    .warning-badge { background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; }
</style>

<div class="mgmt-header">
    <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-main);">
        <i class="fas fa-exclamation-triangle text-danger"></i> จัดการคอมเมนต์ที่ถูกรายงาน
    </h1>
    <p style="color: var(--text-muted);">วิเคราะห์บริบท ดูประวัติผู้กระทำผิด และจัดการขั้นเด็ดขาด (ระดับ: <?= strtoupper($_SESSION['role']) ?>)</p>
</div>

<?php echo displayFlashMessage(); ?>

<div class="mgmt-card">
    <div style="overflow-x: auto;">
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 35%;">ข้อความที่ถูกรายงาน</th>
                    <th style="width: 25%;">ผู้คอมเมนต์ (ประวัติ)</th>
                    <th style="width: 20%;">เหตุผลจากผู้ใช้</th>
                    <th style="text-align: right; width: 20%;">การกระทำ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reported_comments) > 0): ?>
                    <?php foreach ($reported_comments as $report): 
                        $avatar = (!empty($report['profile_img'])) ? "../assets/images/profiles/" . $report['profile_img'] : "../assets/images/profiles/default_profile.png";
                    ?>
                    <tr>
                        <td style="border-radius: 15px 0 0 15px;">
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px;">
                                <i class="fas fa-box"></i> สินค้า: <a href="../pages/product_detail.php?id=<?= $report['product_id'] ?>" target="_blank" style="color: var(--primary); text-decoration: none; font-weight: 600;"><?= e($report['product_name']) ?></a>
                            </div>
                            <div style="background: rgba(239, 68, 68, 0.05); border-left: 3px solid #ef4444; padding: 10px 15px; border-radius: 8px; font-size: 0.95rem; color: var(--text-main); font-style: italic;">
                                "<?= nl2br(e($report['comment_text'])) ?>"
                            </div>
                        </td>
                        
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?= $avatar ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <div>
                                    <div style="font-weight: 700; color: var(--text-main);">
                                        <?= e($report['author_name']) ?>
                                        <?php if($report['is_banned']): ?> <span class="danger-badge" style="font-size: 0.6rem;">BANNED</span> <?php endif; ?>
                                    </div>
                                    
                                    <div style="margin-top: 5px;">
                                        <?php if ($report['author_total_reports'] >= 3): ?>
                                            <span class="danger-badge"><i class="fas fa-skull"></i> โดนรีพอร์ตรวม <?= $report['author_total_reports'] ?> ครั้ง</span>
                                        <?php elseif ($report['author_total_reports'] > 0): ?>
                                            <span class="warning-badge"><i class="fas fa-history"></i> โดนรีพอร์ตรวม <?= $report['author_total_reports'] ?> ครั้ง</span>
                                        <?php else: ?>
                                            <span style="font-size: 0.75rem; color: #10b981;"><i class="fas fa-check-circle"></i> เพิ่งเคยโดนครั้งแรก</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <td>
                            <div style="margin-bottom: 5px;">
                                <span class="danger-badge" style="font-size: 0.85rem;"><i class="fas fa-users"></i> รีพอร์ต <?= $report['report_count'] ?> ครั้ง</span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.6;">
                                • <?= $report['all_reasons'] ?>
                            </div>
                        </td>
                        
                        <td style="border-radius: 0 15px 15px 0; text-align: right;">
                            <form method="POST" style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                <input type="hidden" name="comment_id" value="<?= $report['comment_id'] ?>">
                                
                                <button type="submit" name="action" value="delete_comment" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.85rem; border-radius: 8px; width: 140px; text-align: center;" onclick="return confirm('ยืนยันการลบคอมเมนต์นี้?')">
                                    <i class="fas fa-trash-alt"></i> ลบคอมเมนต์
                                </button>
                                
                                <button type="submit" name="action" value="dismiss_reports" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.85rem; border-radius: 8px; width: 140px; text-align: center;">
                                    <i class="fas fa-times"></i> ปล่อยผ่าน
                                </button>
                                
                                <a href="manage_members.php" class="btn" style="background: #334155; color: white; padding: 8px 15px; font-size: 0.85rem; border-radius: 8px; width: 140px; text-align: center; text-decoration: none;">
                                    <i class="fas fa-user-slash"></i> จัดการผู้ใช้
                                </a>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 60px; border-radius: 15px; color: var(--text-muted);">
                            <i class="fas fa-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 15px; opacity: 0.5;"></i>
                            <h3 style="margin: 0; color: var(--text-main);">ไม่มีรายการที่ต้องตรวจสอบ</h3>
                            <p style="margin-top: 5px;">ตลาดนักเรียนของเราสงบสุขดีในตอนนี้</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>