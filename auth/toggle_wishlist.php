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
    if ($db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)")->execute([$user_id, $product_id])) {
        
        // 🎯 [เพิ่มใหม่] ค้นหาข้อมูลเจ้าของสินค้าเพื่อส่งแจ้งเตือน
        $stmt = $db->prepare("SELECT p.title, s.user_id as owner_id, s.line_user_id 
                              FROM products p 
                              JOIN shops s ON p.shop_id = s.id 
                              WHERE p.id = ?");
        $stmt->execute([$product_id]);
        $p_info = $stmt->fetch();

        // แจ้งเตือนเฉพาะกรณีที่ไม่ใช่การกดถูกใจสินค้าของตัวเอง
        if ($p_info && $p_info['owner_id'] != $user_id) { 
            $buyer_name = $_SESSION['fullname'] ?? 'มีผู้ใช้';
            
            // 🔔 1. แจ้งเตือนกระดิ่งบนหน้าเว็บ
            $notif_msg = "❤️ {$buyer_name} ถูกใจสินค้า '{$p_info['title']}' ของคุณ!";
            sendNotification($p_info['owner_id'], 'system', $notif_msg, "product_detail.php?id=" . $product_id);

            // 🔔 2. แจ้งเตือนเข้า LINE คนขาย (ถ้าคนขายเชื่อมต่อไว้)
            if (!empty($p_info['line_user_id'])) {
                $line_msg = "❤️ มีคนกดถูกใจสินค้าของคุณ!\n"
                          . "👤 จากคุณ: {$buyer_name}\n"
                          . "📦 สินค้า: {$p_info['title']}\n"
                          . "🔗 ดูสินค้า: " . BASE_URL . "pages/product_detail.php?id=" . $product_id;
                sendLineMessagingAPI($p_info['line_user_id'], $line_msg);
            }
        }

        echo json_encode(['status' => 'added']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึก']);
    }
}