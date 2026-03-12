<?php
/**
 * BNCC Market - Notifications API (V 3.0.6 - THE BULLETPROOF PATH)
 */
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { 
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); 
    exit; 
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'fetch') {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unread_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->execute([$user_id]);
    $unread_count = (int)$unread_stmt->fetchColumn();

    foreach($notifs as &$n) {
        $n['time'] = date('d/m H:i', strtotime($n['created_at']));

        if (!empty($n['link']) && $n['link'] !== '#') {
            if (stripos($n['link'], 'http') !== 0) {
                
                // --- ขั้นตอนการล้างพาร์ทแบบเด็ดขาด ---
                $clean_link = ltrim($n['link'], '/'); 

                // ลบคำว่า student_marketplace, s673190104 และ ../ ออกให้หมดจากหน้าริงก์ที่มาจาก DB
                // ไม่ว่าจะกี่รอบก็ตาม จนกว่าจะเหลือแค่ path ไฟล์จริงๆ
                $clean_link = str_ireplace('s673190104/student_marketplace/', '', $clean_link);
                $clean_link = str_ireplace('student_marketplace/', '', $clean_link);
                $clean_link = str_ireplace('s673190104/', '', $clean_link);
                $clean_link = str_ireplace('../', '', $clean_link);
                $clean_link = ltrim($clean_link, '/');

                // กำหนด Base ที่ถูกต้อง (ดึงจาก functions.php แต่ถ้าไม่มีให้ใช้ค่ามาตรฐาน)
                // ตรวจสอบว่า BASE_URL ถูกต้องหรือไม่
                $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/s673190104/student_marketplace/';
                
                // ผลลัพธ์สุดท้าย: จะไม่มีทางเบิ้ลแน่นอน
                $n['link'] = $base . $clean_link;
            }
        }

        // ส่วนของไอคอน (เหมือนเดิม)
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
// ... (Action อื่นๆ คงเดิม) ...
elseif ($action === 'check_new') {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $new_notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($new_notifs) > 0) {
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);
    }
    echo json_encode(['status' => 'success', 'notifications' => $new_notifs]);
}
elseif ($action === 'mark_read') {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    echo json_encode(['status' => 'success']);
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}