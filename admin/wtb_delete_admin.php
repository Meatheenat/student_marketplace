<?php
/**
 * BNCC MARKET - ADMIN WTB SOFT DELETE
 */
session_start();
require_once '../includes/functions.php';

// 🛡️ เช็คสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("⚠️ Access Denied: คุณไม่มีสิทธิ์เข้าถึงส่วนนี้");
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

if ($post_id > 0) {
    // 🎯 ทำการ Soft Delete (เปลี่ยน is_deleted เป็น 1 และปิดประกาศ)
    $stmt = $db->prepare("UPDATE wtb_posts SET is_deleted = 1, deleted_at = NOW(), status = 'closed' WHERE id = ?");
    
    if ($stmt->execute([$post_id])) {
        $_SESSION['flash_message'] = "แอดมิน: ย้ายประกาศไปที่ถังขยะเรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "เกิดข้อผิดพลาดทางเทคนิค";
        $_SESSION['flash_type'] = "danger";
    }
}

// วิ่งกลับหน้าบอร์ด (หน้าบอร์ดจะใช้ Query กรอง is_deleted = 0 ให้เอง)
header("Location: ../pages/wtb_board.php");
exit();