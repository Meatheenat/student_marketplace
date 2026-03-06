<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

$user_id = $_SESSION['user_id'];
$shop_id = $_POST['shop_id'] ?? null;

if (!$shop_id) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสร้านค้า']);
    exit;
}

$db = getDB();

// เช็กว่าตามอยู่แล้วหรือยัง
$check = $db->prepare("SELECT id FROM follows WHERE user_id = ? AND shop_id = ?");
$check->execute([$user_id, $shop_id]);

if ($check->fetch()) {
    // เลิกติดตาม
    $db->prepare("DELETE FROM follows WHERE user_id = ? AND shop_id = ?")->execute([$user_id, $shop_id]);
    echo json_encode(['status' => 'unfollowed']);
} else {
    // 🎯 [เพิ่มใหม่] ติดตามและแจ้งเตือนคนขาย
    $db->prepare("INSERT INTO follows (user_id, shop_id) VALUES (?, ?)")->execute([$user_id, $shop_id]);

    // 🛠️ 1. ดึงชื่อคนกดติดตาม (ตัวมึงเอง)
    $me_stmt = $db->prepare("SELECT fullname FROM users WHERE id = ?");
    $me_stmt->execute([$user_id]);
    $my_name = $me_stmt->fetchColumn();

    // 🛠️ 2. ดึง User ID ของเจ้าของร้าน
    $owner_stmt = $db->prepare("SELECT user_id FROM shops WHERE id = ?");
    $owner_stmt->execute([$shop_id]);
    $owner_id = $owner_stmt->fetchColumn();

    // 🛠️ 3. ยัดแจ้งเตือนลงตาราง notifications
    $msg = "👤 คุณ $my_name เริ่มติดตามร้านค้าของคุณแล้ว!";
    $link = "../seller/dashboard.php"; // หรือลิงก์หน้าจัดการร้านค้า
    
    $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, 'system', ?, ?, 0)");
    $notif_stmt->execute([$owner_id, $msg, $link]);

    echo json_encode(['status' => 'followed']);
}