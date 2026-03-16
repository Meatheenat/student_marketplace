<?php
/**
 * 🛡️ BNCC Market - Barter Management Dashboard
 */
require_once '../includes/functions.php';

if (!isLoggedIn() || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    $_SESSION['flash_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ครับ!";
    $_SESSION['flash_type'] = "danger";
    redirect('../pages/index.php');
}

$db = getDB();

// 🎯 ไฮไลท์: เปลี่ยนชื่อตารางเป็น barter_posts แล้ว
$stmt = $db->query("
    SELECT b.*, u.fullname, u.student_id 
    FROM barter_posts b 
    LEFT JOIN users u ON b.user_id = u.id 
    WHERE b.status != 'deleted' 
    ORDER BY b.created_at DESC
");
$barters = $stmt->fetchAll();

$pageTitle = "จัดการระบบแลกเปลี่ยน (Barter) - BNCC Market";
require_once '../includes/header.php';
?>

<style>
    :root {
        --admin-bg: #f8fafc;
        --admin-card: #ffffff;
        --admin-text: #0f172a;
        --admin-border: #e2e8f0;
        --admin-primary: #4f46e5;
        --admin-danger: #ef4444;
        --admin-hover: #f1f5f9;
    }
    .dark-theme {
        --admin-bg: #0b0f19;
        --admin-card: #161b26;
        --admin-text: #f8fafc;
        --admin-border: #334155;
        --admin-hover: #1e293b;
    }
    .admin-wrapper {
        padding: 40px 20px;
        background-color: var(--admin-bg);
        min-height: calc(100vh - 80px);
        font-family: 'Prompt', sans-serif;
        transition: background-color 0.4s ease;
    }
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    .admin-title {
        font-size: 2rem;
        font-weight: 900;
        color: var(--admin-text);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .admin-card {
        background: var(--admin-card);
        border: 2px solid var(--admin-border);
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.04);
        overflow-x: auto;
        transition: all 0.4s ease;
    }
    .solid-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .solid-table th {
        background: var(--admin-hover);
        color: var(--text-muted);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.85rem;
        padding: 18px 20px;
        text-align: left;
        letter-spacing: 1px;
        border-bottom: 2px solid var(--admin-border);
    }
    .solid-table th:first-child { border-top-left-radius: 16px; border-bottom-left-radius: 16px; }
    .solid-table th:last-child { border-top-right-radius: 16px; border-bottom-right-radius: 16px; }
    .solid-table td {
        padding: 20px;
        color: var(--admin-text);
        font-weight: 600;
        vertical-align: middle;
        border-bottom: 1px solid var(--admin-border);
        transition: background 0.2s;
    }
    .solid-table tbody tr:hover td { background: var(--admin-hover); }
    .solid-table tbody tr:last-child td { border-bottom: none; }
    .item-img-thumb {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid var(--admin-border);
    }
    .badge-status {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-active { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .badge-traded { background: rgba(99, 102, 241, 0.1); color: var(--admin-primary); }
    .btn-action {
        width: 40px; height: 40px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: 0.3s;
        text-decoration: none;
        font-size: 1.1rem;
    }
    .btn-view { background: rgba(99, 102, 241, 0.1); color: var(--admin-primary); }
    .btn-view:hover { background: var(--admin-primary); color: #fff; transform: translateY(-3px); }
    .btn-delete { background: rgba(239, 68, 68, 0.1); color: var(--admin-danger); margin-left: 8px; }
    .btn-delete:hover { background: var(--admin-danger); color: #fff; transform: translateY(-3px); }
    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .empty-state i { font-size: 4rem; color: var(--admin-border); margin-bottom: 15px; }
</style>

<div class="admin-wrapper">
    <div class="container" style="max-width: 1400px;">
        
        <div class="admin-header">
            <h1 class="admin-title">
                <i class="fas fa-exchange-alt" style="color: var(--admin-primary);"></i> 
                จัดการรายการแลกเปลี่ยน
            </h1>
        </div>

        <?php echo displayFlashMessage(); ?>

        <div class="admin-card">
            <?php if (count($barters) > 0): ?>
                <table class="solid-table">
                    <thead>
                        <tr>
                            <th width="80">ID</th>
                            <th width="90">รูปภาพ</th>
                            <th>หัวข้อประกาศ</th>
                            <th>ผู้ลงประกาศ</th>
                            <th width="150">สถานะ</th>
                            <th width="180">วันที่ลง</th>
                            <th width="150" style="text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($barters as $item): ?>
                        <tr>
                            <td style="color: var(--text-muted);">#<?= htmlspecialchars($item['id']) ?></td>
                            <td>
                                <?php 
                                    $img = !empty($item['image_url']) ? "../assets/images/barters/" . $item['image_url'] : "../assets/images/no_image.png";
                                ?>
                                <img src="<?= $img ?>" class="item-img-thumb" alt="Item">
                            </td>
                            <td>
                                <div style="font-size: 1.05rem; font-weight: 800; color: var(--admin-text); margin-bottom: 4px;">
                                    <?= htmlspecialchars($item['title'] ?? 'ไม่มีชื่อประกาศ') ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">
                                    ต้องการแลก: <?= htmlspecialchars($item['item_want'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 800;"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($item['fullname'] ?? 'Unknown User') ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">รหัส: <?= htmlspecialchars($item['student_id'] ?? '-') ?></div>
                            </td>
                            <td>
                                <?php if (($item['status'] ?? 'open') === 'open'): ?>
                                    <span class="badge-status badge-active"><i class="fas fa-circle" style="font-size: 8px;"></i> กำลังหาแลก</span>
                                <?php else: ?>
                                    <span class="badge-status badge-traded"><i class="fas fa-check-circle"></i> ปิดรับแลกแล้ว</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.9rem; color: var(--text-muted);">
                                <?= date('d M Y, H:i', strtotime($item['created_at'])) ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="../pages/barter_detail.php?id=<?= $item['id'] ?>" class="btn-action btn-view" title="ดูรายละเอียด" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <a href="barter_delete_admin.php?id=<?= $item['id'] ?>" class="btn-action btn-delete" title="ลบรายการนี้" onclick="return confirmDelete();">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3 style="font-weight: 900; color: var(--admin-text); margin-bottom: 10px;">ยังไม่มีรายการแลกเปลี่ยน</h3>
                    <p style="font-weight: 500;">ขณะนี้ยังไม่มีนักศึกษาลงประกาศแลกเปลี่ยนสิ่งของในระบบครับ</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    function confirmDelete() {
        return confirm("⚠️ คุณแน่ใจหรือไม่ว่าต้องการลบรายการแลกเปลี่ยนนี้?\n(ข้อมูลจะถูกซ่อนออกจากระบบ)");
    }
</script>

<?php require_once '../includes/footer.php'; ?>