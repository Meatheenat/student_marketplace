<?php
/**
 * BNCC Market - Notifications API
 * รวมฟังก์ชัน Fetch, Mark Read และ Real-time Check
 */
require_once '../includes/functions.php';
header('Content-Type: application/json');

// ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) { 
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); 
    exit; 
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// --- 1. ดึงแจ้งเตือน 10 รายการล่าสุด (สำหรับหน้าต่างแจ้งเตือนหลัก) ---
if ($action === 'fetch') {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // นับจำนวนแจ้งเตือนที่ยังไม่ได้อ่าน
    $unread_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->execute([$user_id]);
    $unread_count = $unread_stmt->fetchColumn();

    // ปรับแต่งข้อมูลก่อนส่งกลับ (แปลงเวลาและใส่ไอคอน)
    foreach($notifs as &$n) {
        $n['time'] = date('d/m H:i', strtotime($n['created_at']));
        
        // กำหนดไอคอนตามประเภท (Type) ในฐานข้อมูล
        if($n['type'] == 'order') {
            $n['icon'] = '<i class="fas fa-shopping-bag" style="color: #10b981;"></i>';
        } elseif($n['type'] == 'review') {
            $n['icon'] = '<i class="fas fa-star" style="color: #f59e0b;"></i>';
        } elseif($n['type'] == 'system') {
            $n['icon'] = '<i class="fas fa-cog" style="color: #6366f1;"></i>';
        } elseif($n['type'] == 'danger') {
            $n['icon'] = '<i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>';
        } else {
            $n['icon'] = '<i class="fas fa-bell" style="color: #94a3b8;"></i>';
        }
    }

    echo json_encode([
        'status' => 'success', 
        'notifications' => $notifs, 
        'unread_count' => $unread_count
    ]);
} 

// --- 2. เช็กแจ้งเตือนใหม่ (สำหรับระบบ Real-time Toast) ---
elseif ($action === 'check_new') {
    // ดึงเฉพาะแจ้งเตือนที่ยังไม่ได้อ่าน (is_read = 0)
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $new_notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($new_notifs) > 0) {
        // เมื่อดึงไปแสดงผลแล้ว ให้มาร์กเป็นอ่านแล้วทันที เพื่อไม่ให้เด้งซ้ำในการเช็กครั้งหน้า
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")
           ->execute([$user_id]);
    }

    echo json_encode([
        'status' => 'success', 
        'notifications' => $new_notifs
    ]);
}

// --- 3. อัปเดตเป็นอ่านแล้วทั้งหมด (เมื่อคนกดเปิดหน้าต่างแจ้งเตือน) ---
elseif ($action === 'mark_read') {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
       ->execute([$user_id]);
    echo json_encode(['status' => 'success']);
}

// กรณีระบุ Action ไม่ถูกต้อง
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}