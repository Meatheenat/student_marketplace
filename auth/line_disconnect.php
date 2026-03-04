<?php
/**
 * BNCC Market - LINE Disconnect System
 * ทำหน้าที่ยกเลิกการเชื่อมต่อ LINE โดยการลบ line_user_id ออกจากฐานข้อมูล
 */
require_once '../includes/functions.php';
session_start();

// 1. ตรวจสอบสิทธิ์การเข้าถึง (Security Gate)
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];

try {
    // 2. ล้างค่า line_user_id ในตาราง shops ให้เป็น NULL
    $stmt = $db->prepare("UPDATE shops SET line_user_id = NULL WHERE user_id = ?");
    
    if ($stmt->execute([$user_id])) {
        // 3. ตั้งค่า Flash Message เพื่อแจ้งเตือนผู้ใช้
        $_SESSION['flash_message'] = "ยกเลิกการเชื่อมต่อ LINE เรียบร้อยแล้ว!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "เกิดข้อผิดพลาด ไม่สามารถยกเลิกการเชื่อมต่อได้";
        $_SESSION['flash_type'] = "danger";
    }
} catch (PDOException $e) {
    // กรณีเกิด Error เกี่ยวกับฐานข้อมูล
    $_SESSION['flash_message'] = "Database Error: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
}

// 4. ส่งกลับไปยังหน้าโปรไฟล์
header("Location: ../pages/profile.php");
exit();