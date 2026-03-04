<?php
/**
 * BNCC Market - Toggle Wishlist AJAX
 */
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสสินค้า']);
    exit();
}

// เช็คว่ามีในรายการหรือยัง
$check = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
$check->execute([$user_id, $product_id]);
$wish = $check->fetch();

if ($wish) {
    // มีแล้ว -> ลบออก
    $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?")->execute([$user_id, $product_id]);
    echo json_encode(['status' => 'removed']);
} else {
    // ยังไม่มี -> เพิ่มเข้า
    $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)")->execute([$user_id, $product_id]);
    echo json_encode(['status' => 'added']);
}