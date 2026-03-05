<?php
/**
 * BNCC Market - Admin Delete Product (Soft Delete + Tracking)
 */
require_once '../includes/functions.php';

if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $product_id = $_POST['product_id'] ?? null;
    $reason = trim($_POST['reason'] ?? 'ไม่ระบุเหตุผล');
    $admin_id = $_SESSION['user_id']; // 🎯 เก็บ ID แอดมินคนที่กดลบ

    if ($product_id) {
        // 🎯 🛠️ อัปเดตสถานะเป็น ลบแล้ว + เก็บคนลบ + เก็บเวลาปัจจุบัน
        $stmt = $db->prepare("UPDATE products SET is_deleted = 1, deleted_by = ?, deleted_at = NOW() WHERE id = ?");
        
        if ($stmt->execute([$admin_id, $product_id])) {
            $p_stmt = $db->prepare("SELECT title FROM products WHERE id = ?");
            $p_stmt->execute([$product_id]);
            $p_name = $p_stmt->fetchColumn();

            logAdminAction('SOFT_DELETE_PRODUCT', 'product', $product_id, "ลบสินค้า: $p_name | เหตุผล: $reason");

            $_SESSION['flash_message'] = "ย้ายสินค้าลงถังขยะเรียบร้อยแล้ว (เก็บไว้ 30 วัน)";
            $_SESSION['flash_type'] = "warning";
        }
    }
}
redirect("../pages/index.php");