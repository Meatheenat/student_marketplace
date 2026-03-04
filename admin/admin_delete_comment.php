<?php
/**
 * BNCC Market - Admin Delete Comment (Fixed Version)
 */
require_once '../includes/functions.php';

// 1. ลบ session_start() บรรทัดนี้ออก เพราะ functions.php จัดการให้แล้วครับ

// ตรวจสอบสิทธิ์ว่าเป็น Admin หรือ Teacher เท่านั้น
if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $comment_id = $_POST['comment_id'] ?? null;
    $product_id = $_POST['product_id'] ?? null;
    $reason = trim($_POST['reason'] ?? '');

    if ($comment_id && $product_id) {
        // 2. แก้จาก c.content เป็น c.comment (ให้ตรงกับตาราง reviews ของมึง)
        $stmt = $db->prepare("SELECT r.comment, u.fullname FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
        $stmt->execute([$comment_id]);
        $comment_data = $stmt->fetch();

        if ($comment_data) {
            // 3. ทำการลบจริง
            $del = $db->prepare("DELETE FROM reviews WHERE id = ?");
            if ($del->execute([$comment_id])) {
                
                // บันทึก Log การทำงาน
                $log_detail = "ลบคอมเมนต์ของ: {$comment_data['fullname']} | เหตุผล: $reason | ข้อความเดิม: {$comment_data['comment']}";
                logAdminAction('DELETE_COMMENT', 'comment', $comment_id, $log_detail);

                // ส่งแจ้งเตือน LINE
                $line_msg = "🚨 [Admin Action] ลบคอมเมนต์แล้ว\n"
                          . "👤 โดย: {$_SESSION['fullname']}\n"
                          . "🎯 เจ้าของคอมเมนต์: {$comment_data['fullname']}\n"
                          . "📄 เหตุผล: $reason";
                notifyAllAdmins($line_msg);

                $_SESSION['flash_message'] = "ลบคอมเมนต์และบันทึกประวัติเรียบร้อยแล้ว";
                $_SESSION['flash_type'] = "success";
            }
        }
    }
}
// เด้งกลับหน้าสินค้าเดิม
header("Location: ../pages/product_detail.php?id=" . $product_id);
exit();