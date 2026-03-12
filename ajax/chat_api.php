<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'msg' => 'Unauthorized']);
    exit;
}

$db      = getDB();
$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

// ─────────────────────────────────────────────
// 1. ส่งข้อความใหม่ (text + optional image)
// schema จริง: id, sender_id, receiver_id, message, image_path, is_read, created_at
// ─────────────────────────────────────────────
if ($action === 'send') {
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $msg         = trim($_POST['message'] ?? '');
    $image_path  = null;

    if (!$receiver_id) {
        echo json_encode(['status' => 'error', 'msg' => 'Missing receiver_id']);
        exit;
    }

    // ต้องมีข้อความ หรือ รูป อย่างน้อย 1 อย่าง
    if ($msg === '' && empty($_FILES['chat_image']['name'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Message or image required']);
        exit;
    }

    // ─── Handle image upload ───
    if (!empty($_FILES['chat_image']['name']) && $_FILES['chat_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['chat_image'];

        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'msg' => 'ไฟล์ต้องไม่เกิน 5MB']);
            exit;
        }

        // ตรวจสอบ MIME จริงจาก magic bytes (ปลอดภัยกว่าดู extension)
        $finfo     = new finfo(FILEINFO_MIME_TYPE);
        $real_mime = $finfo->file($file['tmp_name']);
        $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($real_mime, $allowed)) {
            echo json_encode(['status' => 'error', 'msg' => 'รองรับเฉพาะ JPG, PNG, GIF, WEBP เท่านั้น']);
            exit;
        }

        $ext_map  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $filename = 'chat_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext_map[$real_mime];
        $dir      = __DIR__ . '/../assets/images/chat/';

        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            echo json_encode(['status' => 'error', 'msg' => 'อัปโหลดรูปไม่สำเร็จ']);
            exit;
        }

        $image_path = $filename;
    }

    // INSERT อิง schema จริง — message อนุญาตให้เป็น '' ได้ (กรณีส่งแค่รูป)
    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, image_path, is_read, created_at)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");

    if ($stmt->execute([$user_id, $receiver_id, $msg, $image_path])) {

        // แจ้งเตือน bell (ถ้ามี sendNotification ในระบบ)
        if (function_exists('sendNotification')) {
            $sender_name = $_SESSION['fullname'] ?? 'ผู้ใช้';
            $notif_msg   = $image_path
                ? "📷 {$sender_name} ส่งรูปภาพมาให้คุณ"
                : "💬 {$sender_name}: " . mb_substr($msg, 0, 40) . (mb_strlen($msg) > 40 ? '...' : '');
            sendNotification($receiver_id, 'message', $notif_msg, "chat.php?user={$user_id}");
        }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'บันทึกข้อความไม่สำเร็จ']);
    }
    exit;
}

// ─────────────────────────────────────────────
// 2. ดึงข้อความมาแสดง (ดึงเฉพาะอันที่ยังไม่เคยโชว์ id > last_id)
// ─────────────────────────────────────────────
if ($action === 'fetch') {
    $other_user_id = (int)($_GET['other_user_id'] ?? 0);
    $last_id       = (int)($_GET['last_id'] ?? 0);

    if (!$other_user_id) {
        echo json_encode(['status' => 'error', 'msg' => 'Missing other_user_id']);
        exit;
    }

    // อัปเดตสถานะว่า 'อ่านแล้ว'
    $db->prepare("
        UPDATE messages SET is_read = 1
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ")->execute([$other_user_id, $user_id]);

    // ดึงแชท — เพิ่ม image_path เข้ามา
    $stmt = $db->prepare("
        SELECT id, sender_id, receiver_id, message, image_path, is_read, created_at
        FROM messages
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
          AND id > ?
        ORDER BY created_at ASC
        LIMIT 60
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // แปลงข้อมูลให้ frontend ใช้ได้เลย
    foreach ($messages as &$m) {
        $m['time']       = date('H:i', strtotime($m['created_at']));
        $m['is_mine']    = ((int)$m['sender_id'] === (int)$user_id);
        $m['image_path'] = $m['image_path'] ?? null;  // null-safe
    }
    unset($m);

    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Unknown action']);