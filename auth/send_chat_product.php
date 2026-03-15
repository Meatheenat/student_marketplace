<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$product_id  = (int)($_POST['product_id']  ?? 0);
$extra_msg   = trim($_POST['extra_msg']    ?? '');

if (!$receiver_id || !$product_id) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
    exit;
}

$db        = getDB();
$sender_id = $_SESSION['user_id'];

// ดึงข้อมูลสินค้า
$p = $db->prepare("
    SELECT p.id, p.title, p.price, p.image_url, pi.image_path as main_img
    FROM products p
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
    WHERE p.id = ? AND p.is_deleted = 0
");
$p->execute([$product_id]);
$product = $p->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้า']);
    exit;
}

// ตรวจว่า receiver มีอยู่
$u = $db->prepare("SELECT id FROM users WHERE id = ?");
$u->execute([$receiver_id]);
if (!$u->fetch()) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบผู้รับ']);
    exit;
}

$img_file  = !empty($product['main_img']) ? $product['main_img'] : $product['image_url'];
$image_url = BASE_URL . '/assets/images/products/' . $img_file;
$page_url  = BASE_URL . '/pages/product_detail.php?id=' . $product['id'];

// สร้าง payload JSON
$payload = json_encode([
    'product_id' => $product['id'],
    'title'      => $product['title'],
    'price'      => number_format($product['price'], 2),
    'image'      => $image_url,
    'url'        => $page_url,
    'extra'      => $extra_msg,
], JSON_UNESCAPED_UNICODE);

$ins = $db->prepare("
    INSERT INTO messages (sender_id, receiver_id, message, msg_type, created_at)
    VALUES (?, ?, ?, 'product_card', NOW())
");
$ok = $ins->execute([$sender_id, $receiver_id, $payload]);

if ($ok) {
    sendNotification(
        $receiver_id,
        'chat',
        '🛍️ ' . $_SESSION['fullname'] . ' ส่งสินค้า "' . $product['title'] . '" ให้คุณ',
        'chat.php?user=' . $sender_id
    );
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ']);
}