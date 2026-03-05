<?php
/**
 * BNCC Market - Admin Delete Comment (Soft Delete + Tracking)
 */
require_once '../includes/functions.php';

// ตรวจสอบสิทธิ์ว่าเป็น Admin หรือ Teacher เท่านั้น
if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $comment_id = $_POST['comment_id'] ?? null;
    $product_id = $_POST['product_id'] ?? null;
    $reason = trim($_POST['reason'] ?? '');
    $admin_id = $_SESSION['user_id']; // 🎯 เก็บ ID แอดมินที่กดลบ

    if ($comment_id && $product_id) {
        $stmt = $db->prepare("SELECT r.comment, u.fullname FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
        $stmt->execute([$comment_id]);
        $comment_data = $stmt->fetch();

        if ($comment_data) {
            // 🎯 🛠️ ทำ Soft Delete + เก็บคนลบ + เก็บเวลาปัจจุบัน
            $del = $db->prepare("UPDATE reviews SET is_deleted = 1, deleted_by = ?, deleted_at = NOW() WHERE id = ?");
            if ($del->execute([$admin_id, $comment_id])) {
                
                // บันทึก Log การทำงาน
                $log_detail = "ระงับคอมเมนต์ของ: {$comment_data['fullname']} | เหตุผล: $reason | ข้อความ: {$comment_data['comment']}";
                logAdminAction('SOFT_DELETE_COMMENT', 'comment', $comment_id, $log_detail);

                // ส่งแจ้งเตือน LINE
                $line_msg = "🚨 [Admin Action] ซ่อนคอมเมนต์แล้ว (เก็บ 30 วัน)\n"
                          . "👤 โดย: {$_SESSION['fullname']}\n"
                          . "🎯 เจ้าของคอมเมนต์: {$comment_data['fullname']}\n"
                          . "📄 เหตุผล: $reason";
                notifyAllAdmins($line_msg);

                $_SESSION['flash_message'] = "ระงับการแสดงผลคอมเมนต์เรียบร้อยแล้ว (เก็บไว้ในถังขยะ 30 วัน)";
                $_SESSION['flash_type'] = "warning";
            }
        }
    }
}

redirect("../pages/product_detail.php?id=" . $product_id);