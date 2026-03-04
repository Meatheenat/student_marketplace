<?php
require_once '../includes/functions.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $db = getDB();
    $target_id = $_POST['target_id'];
    $target_type = $_POST['target_type'];
    $reason = trim($_POST['reason']);
    $product_id = $_POST['product_id'];

    $stmt = $db->prepare("INSERT INTO reports (reporter_id, target_id, target_type, reason) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $target_id, $target_type, $reason])) {
        
        // 🔔 แจ้งเตือนแอดมินทุกคนทาง LINE
        $admin_msg = "🚩 [Report] มีการแจ้งรีพอร์ตใหม่!\n"
                   . "ประเภท: " . strtoupper($target_type) . "\n"
                   . "เหตุผล: " . $reason . "\n"
                   . "จาก: " . $_SESSION['fullname'];
        
        notifyAllAdmins($admin_msg);

        $_SESSION['flash_message'] = "ส่งรายงานเรียบร้อยแล้ว แอดมินจะดำเนินการตรวจสอบโดยเร็วที่สุด";
        $_SESSION['flash_type'] = "success";
    }
}
header("Location: ../pages/product_detail.php?id=" . $product_id);
exit();