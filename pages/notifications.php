<?php
/**
 * BNCC MARKETPLACE - ALL NOTIFICATIONS PAGE
 * รวมแจ้งเตือนทั้งหมดของผู้ใช้ พร้อมระบบจัดการสถานะการอ่าน
 */
require_once '../includes/functions.php';

// 1. ความปลอดภัย: ต้องเข้าสู่ระบบก่อน
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "โปรดเข้าสู่ระบบเพื่อดูการแจ้งเตือน";
    $_SESSION['flash_type'] = "warning";
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// 2. ดึงข้อมูลแจ้งเตือนทั้งหมด (แบ่งหน้า หรือดึงล่าสุด 50 รายการ)
try {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

$pageTitle = "การแจ้งเตือนทั้งหมด";
include '../includes/header.php'; // เรียกใช้ Header ที่คุณส่งมาให้ดูตอนแรก
?>

<div class="ui-container ui-py-8" style="max-width: 800px; margin: 0 auto; padding: 0 15px;">
    
    <div class="ui-flex ui-justify-between ui-items-center ui-mb-6">
        <div>
            <h1 class="ui-text-2xl ui-font-bold ui-text-main">การแจ้งเตือน</h1>
            <p class="ui-text-sm ui-text-muted">รายการอัปเดตและข่าวสารล่าสุดของคุณ</p>
        </div>
        <?php if (count($notifications) > 0): ?>
            <button id="markAllReadPage" class="ui-btn ui-btn-secondary ui-text-xs">
                <i class="fas fa-check-double"></i> อ่านแล้วทั้งหมด
            </button>
        <?php endif; ?>
    </div>

    <div class="ui-card ui-shadow-md">
        <div class="ui-p-0">
            <?php if (count($notifications) > 0): ?>
                <div class="notif-list-group">
                    <?php foreach ($notifications as $n): 
                        // --- 🎯 [PATH FIX LOGIC] แก้ไขลิงก์ให้ถูกต้องป้องกันพาร์ทเบิ้ล ---
                        $final_link = '#';
                        if (!empty($n['link']) && $n['link'] !== '#') {
                            if (stripos($n['link'], 'http') === 0) {
                                $final_link = $n['link'];
                            } else {
                                // ล้างพาร์ทขยะออกเหมือนใน API
                                $temp_path = ltrim($n['link'], '/');
                                $clean_link = preg_replace('/^(\.\.\/|s673190104\/|student_marketplace\/)+/i', '', $temp_path);
                                $final_link = rtrim(BASE_URL, '/') . '/' . $clean_link;
                            }
                        }

                        // เลือกไอคอนตามประเภท
                        $icon = '<i class="fas fa-bell"></i>';
                        $icon_bg = 'var(--bncc-primary-500)';
                        
                        if($n['type'] == 'order') { $icon = '<i class="fas fa-shopping-cart"></i>'; $icon_bg = 'var(--bncc-success-500)'; }
                        if($n['type'] == 'review') { $icon = '<i class="fas fa-star"></i>'; $icon_bg = 'var(--bncc-warning-500)'; }
                        if($n['type'] == 'danger') { $icon = '<i class="fas fa-exclamation-circle"></i>'; $icon_bg = 'var(--bncc-danger-500)'; }
                    ?>
                        
                        <a href="<?= $final_link ?>" class="notif-item-row <?= $n['is_read'] == 0 ? 'unread' : '' ?>">
                            <div class="notif-icon-circle" style="background-color: <?= $icon_bg ?>;">
                                <?= $icon ?>
                            </div>
                            <div class="notif-content-text">
                                <p class="notif-msg"><?= htmlspecialchars($n['message']) ?></p>
                                <span class="notif-time">
                                    <i class="far fa-clock"></i> <?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                                </span>
                            </div>
                            <?php if ($n['is_read'] == 0): ?>
                                <div class="unread-dot"></div>
                            <?php endif; ?>
                        </a>

                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ui-p-12 ui-text-center">
                    <div class="ui-mb-4" style="font-size: 4rem; color: var(--theme-text-tertiary); opacity: 0.3;">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h3 class="ui-font-bold ui-text-lg">ยังไม่มีการแจ้งเตือน</h3>
                    <p class="ui-text-muted">เมื่อมีข่าวสารใหม่ๆ เราจะแจ้งให้คุณทราบที่นี่</p>
                    <a href="<?= BASE_URL ?>pages/index.php" class="ui-btn ui-btn-primary ui-mt-6">กลับหน้าหลัก</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .notif-item-row {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--theme-border);
        text-decoration: none;
        transition: all 0.2s ease;
        position: relative;
    }
    .notif-item-row:last-child { border-bottom: none; }
    .notif-item-row:hover { background-color: var(--theme-hover-bg); }
    .notif-item-row.unread { background-color: rgba(99, 102, 241, 0.05); }
    
    .notif-icon-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .notif-content-text { flex-grow: 1; }
    .notif-msg {
        font-size: var(--bncc-font-sm);
        color: var(--theme-text-primary);
        font-weight: 500;
        margin-bottom: 0.25rem;
        line-height: 1.4;
    }
    .unread .notif-msg { font-weight: 700; }
    
    .notif-time {
        font-size: 0.75rem;
        color: var(--theme-text-tertiary);
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .unread-dot {
        width: 10px;
        height: 10px;
        background-color: var(--bncc-primary-500);
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* Dark Mode Adjustments */
    [data-theme="dark"] .notif-item-row.unread { background-color: rgba(99, 102, 241, 0.12); }
</style>

<script>
document.getElementById('markAllReadPage')?.addEventListener('click', async function() {
    try {
        const response = await fetch('<?= BASE_URL ?>ajax/notifications_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_read'
        });
        const data = await response.json();
        if (data.status === 'success') {
            location.reload();
        }
    } catch (error) {
        console.error("Error marking as read:", error);
    }
});
</script>

<?php include '../includes/footer.php'; ?>