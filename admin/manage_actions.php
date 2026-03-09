<?php
require_once '../includes/functions.php';

// ปรับให้รองรับทั้ง admin และ teacher 
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    redirect('../pages/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $target_id = $_POST['target_id'];
    $type = $_POST['type']; // 'user', 'shop', 'product' หรือ 'change_role'
    $action = $_POST['action'] ?? null;

    if ($type === 'user') {
        // ----------------------------------------------------
        // 👤 จัดการสถานะแบนสมาชิก
        // ----------------------------------------------------
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
        // ----------------------------------------------------
        // 🏪 จัดการสถานะร้านค้า (approved/blocked)
        // 🎯 ตรวจสอบค่าในฐานข้อมูลให้ตรงกับ 'blocked' และ 'approved'
        // ----------------------------------------------------
        $status_text = ($action === 'block') ? 'blocked' : 'approved';
        $stmt = $db->prepare("UPDATE shops SET status = ? WHERE id = ?");
        
        if ($stmt->execute([$status_text, $target_id])) {
            $action_text = ($action === 'block') ? "BLOCK_SHOP" : "UNBLOCK_SHOP";
            $detail_text = ($action === 'block') ? "สั่งปิดหน้าร้านค้าชั่วคราว" : "อนุญาตให้เปิดร้านค้าตามปกติ";
            logAdminAction($action_text, 'shop', $target_id, $detail_text);
            $_SESSION['flash_message'] = ($action === 'block') ? "สั่งปิดร้านค้าชั่วคราวแล้ว" : "อนุญาตให้เปิดร้านค้าตามปกติแล้ว";
        }
    } 
    elseif ($type === 'product') {
        // ----------------------------------------------------
        // 📦 จัดการสินค้า (Soft Delete)
        // ----------------------------------------------------
        $status_action = ($action === 'delete') ? 1 : 0; 
        $stmt = $db->prepare("UPDATE products SET is_deleted = ? WHERE id = ?");
        
        if ($stmt->execute([$status_action, $target_id])) {
            $action_text = ($status_action) ? "SOFT_DELETE_PRODUCT" : "RESTORE_PRODUCT";
            $detail_text = ($status_action) ? "ลบสินค้าออกจากระบบ (Soft Delete)" : "กู้คืนสินค้ากลับเข้าระบบ";
            logAdminAction($action_text, 'product', $target_id, $detail_text);
            
            $_SESSION['flash_message'] = ($status_action) ? "ลบสินค้าเรียบร้อยแล้ว (สามารถกู้คืนได้)" : "กู้คืนสินค้าเรียบร้อยแล้ว";
            $_SESSION['flash_type'] = ($status_action) ? "warning" : "success";
        }
    }
    elseif ($type === 'change_role') {
        // ----------------------------------------------------
        // 👑 เปลี่ยนยศ (เฉพาะ Teacher เท่านั้น)
        // ----------------------------------------------------
        if ($_SESSION['role'] === 'teacher') {
            $new_role = $_POST['new_role']; 
            $allowed_roles = ['buyer', 'seller', 'admin', 'teacher'];

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
            $_SESSION['flash_message'] = "ขออภัย เฉพาะระดับคุณครูเท่านั้นที่มีสิทธิ์ปรับยศได้";
            $_SESSION['flash_type'] = "danger";
        }
    }

    // กรณีไม่ได้เซ็ตเป็น danger ให้เป็น success ไว้ก่อน
    if (!isset($_SESSION['flash_type']) || $_SESSION['flash_type'] !== 'danger') {
        if (!isset($_SESSION['flash_type']) || $_SESSION['flash_type'] !== 'warning') {
            $_SESSION['flash_type'] = "success";
        }
    }
}

// ส่งกลับหน้าเดิม
redirect('manage_members.php');