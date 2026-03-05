<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'msg' => 'Unauthorized']);
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. ส่งข้อความใหม่
if ($action === 'send') {
    $receiver_id = $_POST['receiver_id'];
    $msg = trim($_POST['message']);
    
    if (!empty($msg) && $receiver_id) {
        $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$user_id, $receiver_id, $msg])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }
} 
// 2. ดึงข้อความมาแสดง (ดึงเฉพาะอันที่ยังไม่เคยโชว์)
elseif ($action === 'fetch') {
    $other_user_id = $_GET['other_user_id'];
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

    // อัปเดตสถานะว่า 'อ่านแล้ว'
    $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")->execute([$other_user_id, $user_id]);

    // ดึงแชท
    $stmt = $db->prepare("
        SELECT * FROM messages 
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
        AND id > ? 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // แปลงวันที่ให้สวยๆ
    foreach($messages as &$m) {
        $m['time'] = date('H:i', strtotime($m['created_at']));
        $m['is_mine'] = ($m['sender_id'] == $user_id) ? true : false;
    }
    
    echo json_encode(['status' => 'success', 'messages' => $messages]);
}
?>