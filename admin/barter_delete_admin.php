<?php
/**
 * 🛡️ BNCC Market - Admin Soft Delete Barter Item
 */
require_once '../includes/functions.php';

// เช็กสิทธิ์ความปลอดภัย
if (!isLoggedIn() || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    $_SESSION['flash_message'] = "คุณไม่มีสิทธิ์เข้าถึงระบบนี้ครับ!";
    $_SESSION['flash_type'] = "danger";
    redirect('../pages/index.php'); 
}

$db = getDB();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $barter_id = (int)$_GET['id'];

    try {
        // 🎯 ไฮไลท์: แก้ชื่อตารางเป็น barter_posts แล้ว!
        $stmt = $db->prepare("UPDATE barter_posts SET status = 'deleted' WHERE id = ?");
        $result = $stmt->execute([$barter_id]);

        if ($result && $stmt->rowCount() > 0) {
            $_SESSION['flash_message'] = "ลบรายการแลกเปลี่ยนสำเร็จแล้ว! (ซ่อนข้อมูลเรียบร้อย)";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "ไม่พบข้อมูล หรือรายการนี้ถูกลบไปแล้วครับ";
            $_SESSION['flash_type'] = "warning";
        }

    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "เกิดข้อผิดพลาดของระบบฐานข้อมูล: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
} else {
    $_SESSION['flash_message'] = "ข้อมูลไม่ถูกต้อง ไม่สามารถลบได้ครับ";
    $_SESSION['flash_type'] = "danger";
}

redirect('pages/barter_board');
?>