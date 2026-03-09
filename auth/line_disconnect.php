<?php
/**
 * BNCC Market - LINE Disconnect System (Fixed Version)
 * ทำหน้าที่ยกเลิกการเชื่อมต่อ LINE โดยการลบ line_user_id ออกจากฐานข้อมูล
 */
require_once '../includes/functions.php';
// [REMOVED] ลบ session_start() ออกเพราะมีอยู่ใน functions.php แล้ว

// 1. ตรวจสอบสิทธิ์การเข้าถึง
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];

try {
    // 2. ล้างค่า line_user_id ทั้งในตาราง users และ shops เพื่อความชัวร์
    // แก้ไข: เปลี่ยนจากเช็กแค่ shops เป็นเช็กที่ตาราง users หลักด้วย
    $stmt1 = $db->prepare("UPDATE users SET line_user_id = NULL WHERE id = ?");
    $stmt2 = $db->prepare("UPDATE shops SET line_user_id = NULL WHERE user_id = ?");
    
    $res1 = $stmt1->execute([$user_id]);
    $res2 = $stmt2->execute([$user_id]);

    if ($res1 || $res2) {
        // 3. ตั้งค่า Flash Message เพื่อแจ้งเตือนผู้ใช้
        $_SESSION['flash_message'] = "✅ ยกเลิกการเชื่อมต่อ LINE เรียบร้อยแล้ว!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "❌ ไม่พบข้อมูลการเชื่อมต่อที่ต้องการยกเลิก";
        $_SESSION['flash_type'] = "warning";
    }
} catch (PDOException $e) {
    // กรณีเกิด Error เกี่ยวกับฐานข้อมูล
    $_SESSION['flash_message'] = "Database Error: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
}

// 4. ส่งกลับไปยังหน้าโปรไฟล์
header("Location: ../pages/profile.php");
exit();