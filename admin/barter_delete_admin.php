<?php
/**
 * 🛡️ BNCC Market - Admin Soft Delete Barter Item
 * ระบบลบข้อมูลแบบซ่อน (Soft Delete) สำหรับผู้ดูแลระบบ
 */

// 1. โหลด Functions หลักของระบบ
require_once '../includes/functions.php';

// 2. เช็กสิทธิ์ความปลอดภัย (Security Gate)
// บังคับว่าต้องล็อกอิน และต้องมี Role เป็น 'admin' เท่านั้นถึงจะทำได้!
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    $_SESSION['flash_message'] = "คุณไม่มีสิทธิ์เข้าถึงระบบนี้ครับ!";
    $_SESSION['flash_type'] = "danger";
    
    // เตะกลับไปหน้าหลัก
    redirect('../pages/index.php'); 
}

$db = getDB();

// 3. ตรวจสอบว่ามี ID ส่งมาให้ลบหรือไม่ และต้องเป็นตัวเลขเท่านั้น (กันแฮกเกอร์ใส่โค้ดแปลกๆ)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $barter_id = (int)$_GET['id'];

    try {
        // 🚀 4. ท่าไม้ตาย Soft Delete: อัปเดตสถานะแทนการลบจริง
        // ⚠️ หมายเหตุ: พี่แก้ชื่อตาราง 'barters' และคอลัมน์ 'status' ให้ตรงกับ Database ของพี่นะครับ
        $stmt = $db->prepare("UPDATE barters SET status = 'deleted' WHERE id = ?");
        $result = $stmt->execute([$barter_id]);

        if ($result && $stmt->rowCount() > 0) {
            // ลบ (ซ่อน) สำเร็จ
            $_SESSION['flash_message'] = "ลบรายการแลกเปลี่ยนสำเร็จแล้ว! (ซ่อนข้อมูลเรียบร้อย)";
            $_SESSION['flash_type'] = "success";
        } else {
            // หา ID ไม่เจอ หรือสถานะเป็น deleted อยู่แล้ว
            $_SESSION['flash_message'] = "ไม่พบข้อมูล หรือรายการนี้ถูกลบไปแล้วครับ";
            $_SESSION['flash_type'] = "warning";
        }

    } catch (PDOException $e) {
        // ดักจับ Error เผื่อ Database มีปัญหา
        $_SESSION['flash_message'] = "เกิดข้อผิดพลาดของระบบฐานข้อมูล: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
} else {
    // ไม่ได้ส่ง ID มา หรือ ID ไม่ใช่ตัวเลข
    $_SESSION['flash_message'] = "ข้อมูลไม่ถูกต้อง ไม่สามารถลบได้ครับ";
    $_SESSION['flash_type'] = "danger";
}

// 5. ลบเสร็จแล้วให้เด้งกลับไปหน้าไหน? (พี่แก้ชื่อไฟล์ด้านล่างให้ตรงกับหน้าตารางแอดมินของพี่ได้เลย)
// สมมติว่าให้เด้งกลับไปหน้าจัดการ Barter
redirect('barter_manage.php');
?>