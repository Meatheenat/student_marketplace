<?php
/**
 * BNCC Market - Notifications API (V 3.0.5 - THE FINAL PATH RESOLVER)
 * ระบบจัดการแจ้งเตือน: แก้ไขปัญหาลิงก์พาร์ทเบิ้ล, พาร์ทซ้อน และ 404
 */
require_once '../includes/functions.php';
header('Content-Type: application/json');

// 1. ตรวจสอบสถานะการเข้าสู่ระบบ
if (!isLoggedIn()) { 
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); 
    exit; 
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// --- 2. Action: FETCH (ดึงแจ้งเตือนล่าสุด 10 รายการ) ---
if ($action === 'fetch') {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // นับจำนวนแจ้งเตือนที่ยังไม่ได้อ่าน
    $unread_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->execute([$user_id]);
    $unread_count = (int)$unread_stmt->fetchColumn();

    foreach($notifs as &$n) {
        // จัดรูปแบบเวลา
        $n['time'] = date('d/m H:i', strtotime($n['created_at']));

        // 🎯 [CRITICAL PATH RESOLUTION]
        if (!empty($n['link']) && $n['link'] !== '#') {
            // ข้ามถ้าเป็นลิงก์ภายนอก
            if (stripos($n['link'], 'http') !== 0) {
                
                // กระบวนการ "ล้างพาธ" (Sanitize Path)
                // ตัด / ที่อยู่หน้าสุดออก
                $temp_path = ltrim($n['link'], '/'); 
                
                /**
                 * ใช้ Regex เพื่อกระชากพาร์ทส่วนเกินที่มักจะติดมาใน Database ออก
                 * - ^ หมายถึง เริ่มต้นจากหน้าสุด
                 * - ( ... )+ หมายถึง ทำซ้ำจนกว่าจะหมดสิ่งที่เข้าเงื่อนไข
                 * - ลบ ../, s673190104/, student_marketplace/ ออกทั้งหมด
                 */
                $clean_link = preg_replace('/^(\.\.\/|s673190104\/|student_marketplace\/)+/i', '', $temp_path);

                // ตรวจสอบ BASE_URL และรวมพาธใหม่
                // ตรวจสอบให้มั่นใจว่า BASE_URL ใน functions.php มี / ปิดท้ายเสมอ
                $base = rtrim(BASE_URL, '/') . '/';
                
                $n['link'] = $base . $clean_link;
            }
        }

        // กำหนดไอคอนตามประเภท
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

    if (count($new_notifs) > 0) {
        // อัปเดตเพื่อไม่ให้แจ้งเตือนซ้ำ
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")
           ->execute([$user_id]);
    }

    echo json_encode([
        'status' => 'success', 
        'notifications' => $new_notifs
    ]);
}

// --- 4. Action: MARK_READ (ทำเครื่องหมายว่าอ่านแล้วทั้งหมด) ---
elseif ($action === 'mark_read') {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
       ->execute([$user_id]);
    echo json_encode(['status' => 'success']);
}

else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}