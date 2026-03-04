<?php
/**
 * BNCC Market - Admin User Management Action
 * สำหรับประมวลผลการแบนและปลดแบนสมาชิก
 */
require_once '../includes/functions.php';

// 1. ตรวจสอบสิทธิ์: ต้องเป็น Admin เท่านั้นถึงจะเข้าถึงไฟล์นี้ได้
checkRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $target_user_id = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    // ป้องกันแอดมินแบนตัวเอง
    if ($target_user_id == $_SESSION['user_id']) {
        $_SESSION['flash_message'] = "คุณไม่สามารถดำเนินการกับบัญชีของตัวเองได้";
        $_SESSION['flash_type'] = "danger";
        redirect('../pages/view_profile.php?id=' . $target_user_id);
    }

    if ($target_user_id && $action) {
        try {
            $db->beginTransaction();

            if ($action === 'ban') {
                // 🚫 สั่งแบนผู้ใช้งาน
                $stmt = $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
                $stmt->execute([$target_user_id]);

                // 🏪 สั่งปิดร้านค้า (ถ้ามี) เพื่อไม่ให้สินค้าโชว์ในตลาด
                $shop_stmt = $db->prepare("UPDATE shops SET status = 'blocked' WHERE user_id = ?");
                $shop_stmt->execute([$target_user_id]);

                $_SESSION['flash_message'] = "ระงับการใช้งานสมาชิกเรียบร้อยแล้ว";
                $_SESSION['flash_type'] = "warning";

            } elseif ($action === 'unban') {
                // ✅ ปลดแบนผู้ใช้งาน
                $stmt = $db->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
                $stmt->execute([$target_user_id]);

                // คืนสถานะร้านค้าเป็น 'approved' (มึงอาจจะปรับเป็น 'pending' เพื่อตรวจใหม่ก็ได้)
                $shop_stmt = $db->prepare("UPDATE shops SET status = 'approved' WHERE user_id = ? AND status = 'blocked'");
                $shop_stmt->execute([$target_user_id]);

                $_SESSION['flash_message'] = "ปลดระงับการใช้งานสมาชิกเรียบร้อยแล้ว";
                $_SESSION['flash_type'] = "success";
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['flash_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// เด้งกลับไปหน้าโปรไฟล์ที่เพิ่งจัดการ
$redirect = $_POST['redirect_url'] ?? '../pages/view_profile.php?id=' . $target_user_id;
redirect($redirect);