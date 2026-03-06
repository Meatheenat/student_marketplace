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
    // ติดตาม
    $db->prepare("INSERT INTO follows (user_id, shop_id) VALUES (?, ?)")->execute([$user_id, $shop_id]);
    echo json_encode(['status' => 'followed']);
}