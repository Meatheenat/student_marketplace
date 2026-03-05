<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['status' => 'error']); exit; }

$db = getDB();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'fetch') {
    // ดึงแจ้งเตือน 10 รายการล่าสุด
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // นับจำนวนที่ยังไม่ได้อ่าน
    $unread_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->execute([$user_id]);
    $unread_count = $unread_stmt->fetchColumn();

    // แปลงเวลาให้สวยๆ
    foreach($notifs as &$n) {
        $n['time'] = date('d/m H:i', strtotime($n['created_at']));
        // กำหนดไอคอนตามประเภท
        if($n['type'] == 'order') $n['icon'] = '<i class="fas fa-shopping-bag" style="color: #10b981;"></i>';
        elseif($n['type'] == 'review') $n['icon'] = '<i class="fas fa-star" style="color: #f59e0b;"></i>';
        elseif($n['type'] == 'system') $n['icon'] = '<i class="fas fa-cog" style="color: #6366f1;"></i>';
        elseif($n['type'] == 'danger') $n['icon'] = '<i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>';
        else $n['icon'] = '<i class="fas fa-bell" style="color: #94a3b8;"></i>';
    }

    echo json_encode(['status' => 'success', 'notifications' => $notifs, 'unread_count' => $unread_count]);
} 
elseif ($action === 'mark_read') {
    // อัปเดตให้เป็นอ่านแล้วทั้งหมด
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    echo json_encode(['status' => 'success']);
}