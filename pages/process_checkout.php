<?php
/**
 * ระบบประมวลผลการสั่งซื้อ (นัดรับสินค้า)
 */
session_start();
require_once '../includes/functions.php';

// 1. เช็คว่าล็อกอินหรือยัง
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนสั่งซื้อสินค้า";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // 2. รับค่าจากฟอร์ม Checkout
    $product_id = $_POST['product_id'] ?? 0;
    $meetup_location = $_POST['meetup_location'] ?? '';
    $meetup_time = $_POST['meetup_time'] ?? '';
    $buyer_note = trim($_POST['buyer_note'] ?? '');

    // เช็คว่าเลือกจุดนัดรับหรือยัง
    if (empty($meetup_location) || empty($meetup_time)) {
        $_SESSION['flash_message'] = "กรุณาเลือกสถานที่และเวลานัดรับสินค้า";
        $_SESSION['flash_type'] = "danger";
        redirect("checkout.php?id=$product_id");
    }

    // 3. ดึงข้อมูลสินค้า + ข้อมูลเจ้าของร้าน
    $stmt = $db->prepare("SELECT p.*, s.user_id as owner_id, s.line_user_id, s.shop_name 
                          FROM products p 
                          JOIN shops s ON p.shop_id = s.id 
                          WHERE p.id = ? AND p.is_deleted = 0");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        $_SESSION['flash_message'] = "ไม่พบสินค้า หรือสินค้านี้ถูกลบไปแล้ว";
        $_SESSION['flash_type'] = "danger";
        redirect('index.php');
    }

    // กันเด็กซน กดซื้อของร้านตัวเอง
    if ($user_id == $product['owner_id']) {
        $_SESSION['flash_message'] = "คุณไม่สามารถสั่งซื้อสินค้าของร้านตัวเองได้";
        $_SESSION['flash_type'] = "danger";
        redirect("product_detail.php?id=$product_id");
    }

    try {
        // 4. บันทึกคำสั่งซื้อลงฐานข้อมูล (รวมสถานที่+เวลานัดรับ)
        $ins_order = $db->prepare("INSERT INTO orders (buyer_id, shop_id, product_id, meetup_location, meetup_time, buyer_note) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($ins_order->execute([$user_id, $product['shop_id'], $product_id, $meetup_location, $meetup_time, $buyer_note])) {
            
            // 🔔 5. แจ้งเตือนกระดิ่งบนเว็บ ส่งไปหาแม่ค้า
            $notif_msg = "🛒 มีออเดอร์ใหม่: {$product['title']}<br>📍 นัดรับ: {$meetup_location} ({$meetup_time})";
            sendNotification($product['owner_id'], 'order', $notif_msg, "../seller/dashboard.php");

            // 🔔 6. ยิงแจ้งเตือนผ่าน LINE ทะลุจอแม่ค้า (ถ้าแม่ค้าผูก LINE ไว้)
            if (!empty($product['line_user_id'])) {
                $line_msg = "🛒 มีคำสั่งซื้อใหม่!\n"
                          . "📦 สินค้า: " . $product['title'] . "\n"
                          . "👤 จากคุณ: " . $_SESSION['fullname'] . "\n"
                          . "📍 นัดรับที่: " . $meetup_location . "\n"
                          . "⏰ เวลา: " . $meetup_time . "\n"
                          . "📝 หมายเหตุ: " . ($buyer_note != '' ? $buyer_note : "-");
                sendLineMessagingAPI($product['line_user_id'], $line_msg);
            }

            // (Optional) อัปเดตสถานะสินค้าเป็น "reserved" (จองแล้ว)
            // $update_status = $db->prepare("UPDATE products SET status = 'reserved' WHERE id = ?");
            // $update_status->execute([$product_id]);

            $_SESSION['flash_message'] = "ส่งคำสั่งซื้อสำเร็จ! เตรียมตัวไปนัดรับที่ <b>{$meetup_location}</b> ได้เลย";
            $_SESSION['flash_type'] = "success";
            redirect("product_detail.php?id=$product_id"); // เด้งกลับไปหน้าสินค้าพร้อมข้อความสีเขียว
        }

    } catch (Exception $e) {
        $_SESSION['flash_message'] = "เกิดข้อผิดพลาดของระบบ: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
        redirect("checkout.php?id=$product_id");
    }
} else {
    redirect('index.php');
}