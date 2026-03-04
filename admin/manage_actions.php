<?php
require_once '../includes/functions.php';
// ปรับให้รองรับทั้ง admin และ teacher 
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    redirect('../pages/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $target_id = $_POST['target_id'];
    $type = $_POST['type']; // 'user', 'shop' หรือ 'change_role'
    $action = $_POST['action'] ?? null;

    if ($type === 'user') {
        // จัดการสถานะแบนสมาชิก
        $status = ($action === 'ban') ? 1 : 0;
        $stmt = $db->prepare("UPDATE users SET is_banned = ? WHERE id = ?");
        
        if ($stmt->execute([$status, $target_id])) {
            $action_text = ($status) ? "BAN_USER" : "UNBAN_USER";
            $detail_text = ($status) ? "ระงับสิทธิ์การใช้งานบัญชีสมาชิก" : "ยกเลิกการระงับสิทธิ์สมาชิก";
            logAdminAction($action_text, 'user', $target_id, $detail_text);
            $_SESSION['flash_message'] = ($status) ? "แบนสมาชิกเรียบร้อยแล้ว" : "ปลดแบนสมาชิกเรียบร้อยแล้ว";
        }
    } 
    elseif ($type === 'shop') {
        // จัดการสถานะร้านค้า (approved/blocked)
        $status_text = ($action === 'block') ? 'blocked' : 'approved';
        $stmt = $db->prepare("UPDATE shops SET status = ? WHERE id = ?");
        
        if ($stmt->execute([$status_text, $target_id])) {
            $action_text = ($action === 'block') ? "BLOCK_SHOP" : "UNBLOCK_SHOP";
            $detail_text = ($action === 'block') ? "สั่งปิดหน้าร้านค้าชั่วคราว" : "อนุญาตให้เปิดร้านค้าตามปกติ";
            logAdminAction($action_text, 'shop', $target_id, $detail_text);
            $_SESSION['flash_message'] = ($action === 'block') ? "สั่งปิดร้านค้าชั่วคราวแล้ว" : "อนุญาตให้เปิดร้านค้าตามปกติแล้ว";
        }
    }
    // 👑 🛡️ [เพิ่มใหม่] เปลี่ยนยศแบบอิสระ (เฉพาะ Teacher เท่านั้น!)
    elseif ($type === 'change_role') {
        if ($_SESSION['role'] === 'teacher') {
            $new_role = $_POST['new_role']; // รับค่าจาก Dropdown (buyer, seller, admin, teacher)
            $allowed_roles = ['buyer', 'seller', 'admin', 'teacher']; // ป้องกันคนแฮก Inspect element ส่งค่ามั่ว

            if (in_array($new_role, $allowed_roles)) {
                $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
                
                if ($stmt->execute([$new_role, $target_id])) {
                    $log_text = "อัปเดตยศบัญชีนี้เป็น: " . strtoupper($new_role);
                    logAdminAction('CHANGE_ROLE', 'user', $target_id, $log_text);
                    
                    $_SESSION['flash_message'] = "ปรับเปลี่ยนยศเป็น " . strtoupper($new_role) . " สำเร็จแล้ว!";
                    $_SESSION['flash_type'] = "success";
                }
            } else {
                $_SESSION['flash_message'] = "เกิดข้อผิดพลาด: ยศไม่ถูกต้อง";
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            // ถ้านักเรียนแอบพยายามส่งค่ามาแก้สิทธิ์
            $_SESSION['flash_message'] = "ขออภัย เฉพาะระดับคุณครูเท่านั้นที่มีสิทธิ์ปรับยศได้";
            $_SESSION['flash_type'] = "danger";
        }
    }

    // กรณีไม่ได้เซ็ตเป็น danger ให้เป็น success ไว้ก่อน
    if (!isset($_SESSION['flash_type']) || $_SESSION['flash_type'] !== 'danger') {
        $_SESSION['flash_type'] = "success";
    }
}

redirect('manage_members.php');