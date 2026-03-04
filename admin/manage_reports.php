<?php
/**
 * BNCC Market - Report Management (Admin Only)
 * หน้าจัดการคำร้องเรียนทั้งหมดในระบบ
 */
$pageTitle = "จัดการคำร้องเรียน - BNCC Market";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// 1. ตรวจสอบสิทธิ์แอดมิน
checkRole('admin');

$db = getDB();

// 2. ประมวลผลการอัปเดตสถานะรีพอร์ต (ถ้ามีการกดปุ่ม)
if (isset($_GET['action']) && isset($_GET['report_id'])) {
    $report_id = (int)$_GET['report_id'];
    $new_status = ($_GET['action'] === 'resolve') ? 'resolved' : 'dismissed';
    
    $update = $db->prepare("UPDATE reports SET status = ? WHERE id = ?");
    if ($update->execute([$new_status, $report_id])) {
        $_SESSION['flash_message'] = "ดำเนินการเรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "success";
        redirect('manage_reports.php');
    }
}

// 3. ดึงรายการรีพอร์ตทั้งหมด JOIN กับชื่อคนรีพอร์ต
$stmt = $db->query("
    SELECT r.*, u.fullname as reporter_name 
    FROM reports r 
    JOIN users u ON r.reporter_id = u.id 
    ORDER BY r.status ASC, r.created_at DESC
");
$reports = $stmt->fetchAll();
?>

<div class="dashboard-header" style="margin-bottom: 30px; border-left: 6px solid #ef4444; padding-left: 20px;">
    <h1 style="font-size: 2.2rem; font-weight: 800; margin: 0;">จัดการคำร้องเรียน</h1>
    <p style="color: var(--text-muted);">ตรวจสอบความประพฤติและดำเนินการกับสมาชิกที่ทำผิดกฎ</p>
</div>

<div class="card shadow-lg" style="border-radius: 24px; padding: 30px; border: 1px solid var(--border-color); background: var(--bg-card);">
    <?php if (count($reports) > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 15px;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">
                        <th style="padding: 10px;">วันที่แจ้ง</th>
                        <th style="padding: 10px;">ผู้รายงาน</th>
                        <th style="padding: 10px;">ประเภท</th>
                        <th style="padding: 10px;">เหตุผล</th>
                        <th style="padding: 10px;">สถานะ</th>
                        <th style="padding: 10px; text-align: right;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): 
                        // ตกแต่ง Badge ตามสถานะ
                        $status_color = '#94a3b8';
                        if ($r['status'] === 'pending') $status_color = '#ef4444';
                        if ($r['status'] === 'resolved') $status_color = '#10b981';
                    ?>
                        <tr style="background: var(--bg-body); transition: 0.2s;">
                            <td style="padding: 20px; border-radius: 12px 0 0 12px; font-size: 0.85rem;">
                                <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                            </td>
                            <td style="padding: 20px; font-weight: 600;">
                                <?= e($r['reporter_name']) ?>
                            </td>
                            <td style="padding: 20px;">
                                <span style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;">
                                    <?= strtoupper($r['target_type']) ?>
                                </span>
                            </td>
                            <td style="padding: 20px; color: var(--text-muted); font-size: 0.9rem; max-width: 300px;">
                                <?= e($r['reason']) ?>
                            </td>
                            <td style="padding: 20px;">
                                <span style="color: <?= $status_color ?>; font-weight: 700; font-size: 0.85rem;">
                                    <i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i> 
                                    <?= strtoupper($r['status']) ?>
                                </span>
                            </td>
                            <td style="padding: 20px; border-radius: 0 12px 12px 0; text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <?php 
                                    // กำหนดลิงก์ตามประเภทที่โดนรายงาน เพื่อให้แอดมินไปดูของจริง
                                    $view_url = "#";
                                    if ($r['target_type'] === 'user') $view_url = "../pages/view_profile.php?id=" . $r['target_id'];
                                    if ($r['target_type'] === 'shop') $view_url = "../pages/view_profile.php?id=" . $db->query("SELECT user_id FROM shops WHERE id = ".$r['target_id'])->fetchColumn();
                                    if ($r['target_type'] === 'comment') $view_url = "../pages/product_detail.php?id=" . $db->query("SELECT product_id FROM reviews WHERE id = ".$r['target_id'])->fetchColumn();
                                    ?>
                                    
                                    <a href="<?= $view_url ?>" class="btn btn-sm btn-outline" title="ดูรายละเอียด" style="padding: 8px 12px;"><i class="fas fa-eye"></i></a>
                                    
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <a href="?action=resolve&report_id=<?= $r['id'] ?>" class="btn btn-sm btn-primary" title="ทำเครื่องหมายว่าจัดการแล้ว" style="padding: 8px 12px; background: #10b981; border: none;"><i class="fas fa-check"></i></a>
                                        <a href="?action=dismiss&report_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline" title="ยกเลิกคำร้อง" style="padding: 8px 12px;"><i class="fas fa-times"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 0; color: var(--text-muted);">
            <i class="fas fa-shield-heart" style="font-size: 3rem; opacity: 0.2; margin-bottom: 15px;"></i>
            <p>ยังไม่มีการแจ้งรีพอร์ตเข้ามา สงบสุขสุด ๆ!</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>