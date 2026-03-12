<?php
/**
 * BNCC Market - Notifications API (V 3.0.4 - THE PATH RECONSTRUCTOR)
 * รวมฟังก์ชัน Fetch, Mark Read และแก้ไขปัญหา 404/Double Path
 */
require_once '../includes/functions.php';
header('Content-Type: application/json');

// 1. ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) { 
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); 
    exit; 
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// --- 2. Action: FETCH (ดึงแจ้งเตือน 10 รายการล่าสุด) ---
if ($action === 'fetch') {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // นับจำนวนแจ้งเตือนที่ยังไม่ได้อ่าน
    $unread_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->execute([$user_id]);
    $unread_count = (int)$unread_stmt->fetchColumn();

    // ปรับแต่งข้อมูลก่อนส่งกลับ
    foreach($notifs as &$n) {
        $n['time'] = date('d/m H:i', strtotime($n['created_at']));

        // 🎯 [CRITICAL FIX] แก้ไขปัญหาลิงก์ผิดพาร์ท / พาร์ทเบิ้ล
        if (!empty($n['link']) && $n['link'] !== '#') {
            // ถ้าเป็นลิงก์ภายนอก (http) ให้ใช้ได้เลย
            if (strpos($n['link'], 'http') === false) {
                
                // เริ่มกระบวนการทำความสะอาดลิงก์ (Clean Path)
                $link_path = ltrim($n['link'], '/'); // ลบ / ข้างหน้าออก
                
                // ลบคำที่ไม่ต้องการออกทั้งหมด (เพื่อป้องกันการเบิ้ลพาร์ท)
                $remove_list = [
                    '../', 
                    's673190104/student_marketplace/', 
                    's673190104/'
                ];
                $clean_link = str_replace($remove_list, '', $link_path);

                // ตรวจสอบ BASE_URL (ต้องมั่นใจว่าใน functions.php คือ /s673190104/student_marketplace/)
                // ถ้า BASE_URL ไม่มี / ปิดท้าย ให้เติมเข้าไป
                $base = rtrim(BASE_URL, '/') . '/';
                
                $n['link'] = $base . $clean_link;
            }
        }

        // กำหนดไอคอนตามประเภท (Type)
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

// --- 3. Action: CHECK_NEW (สำหรับ Real-time Toast) ---
elseif ($action === 'check_new') {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $new_notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // มาร์กเป็นอ่านแล้วทันทีเพื่อไม่ให้เด้งซ้ำ
    if (count($new_notifs) > 0) {
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")
           ->execute([$user_id]);
    }

    echo json_encode([
        'status' => 'success', 
        'notifications' => $new_notifs
    ]);
}

// --- 4. Action: MARK_READ (อ่านทั้งหมด) ---
elseif ($action === 'mark_read') {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
       ->execute([$user_id]);
    echo json_encode(['status' => 'success']);
}

// กรณีอื่นๆ
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}