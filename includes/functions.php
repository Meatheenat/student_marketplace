<?php
/**
 * Student Marketplace - Core Functions (Production Version)
 * [KEEP ALL FUNCTIONS - NO DELETION]
 */

// 🚀 1. ระบบตรวจหา URL เริ่มต้นอัตโนมัติ (แก้ไขให้ใช้ได้ทั้ง Local และ Host จริง)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// ปรับให้ตรงกับโครงสร้างโฟลเดอร์บน Host BNCC
$base_url = "$protocol://$host/s673190104/student_marketplace/"; 

if (!defined('BASE_URL')) define('BASE_URL', $base_url);

// 2. จัดการ Session และ Output Buffering
if (ob_get_level() == 0) ob_start(); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 🎯 🛠️ การดึงไฟล์ Database
 * ใช้ __DIR__ เพื่ออ้างอิงตำแหน่งไฟล์ที่แน่นอน ป้องกันปัญหา Path เพี้ยนเวลาเรียกจากคนละโฟลเดอร์
 */
require_once __DIR__ . '/../config/database.php'; 

/**
 * 🚀 LINE Notify Function (สำหรับส่งเข้ากลุ่มแอดมิน)
 */
function sendLineNotify($message, $token = "ใส่_TOKEN_LINE_NOTIFY_ของมึงตรงนี้") {
    if (empty($token)) return false;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://notify-api.line.me/api/notify");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "message=" . urlencode($message));
    
    $headers = [
        'Content-type: application/x-www-form-urlencoded',
        'Authorization: Bearer ' . $token
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * 3. Security & UI Helpers
 */
function e($item) {
    return htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * 👑 🛠️ [FIXED] ปลดล็อกสิทธิ์ให้ Teacher และแก้ปัญหา Redirect Path
 */
function checkRole($role) {
    if (!isLoggedIn()) {
        $_SESSION['flash_message'] = "คุณต้องเข้าสู่ระบบก่อน";
        $_SESSION['flash_type'] = "danger";
        // 🎯 แก้จาก Path ตายตัว เป็นการใช้ BASE_URL เพื่อให้ทำงานบน Host ได้
        redirect(BASE_URL . "auth/login.php");
    }

    // ถ้าเป็นครู (Teacher) และพยายามเข้าหน้า Admin ให้ปล่อยผ่านได้เลย
    if ($_SESSION['role'] === 'teacher' && $role === 'admin') {
        return; 
    }

    if ($_SESSION['role'] !== $role) {
        $_SESSION['flash_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
        $_SESSION['flash_type'] = "danger";
        redirect(BASE_URL . "auth/login.php");
    }
}

function formatPrice($amount) {
    return "฿" . number_format($amount, 2);
}

function getProductStatusBadge($status) {
    switch ($status) {
        case 'in-stock':
            return '<span class="badge badge-instock"><i class="fas fa-check"></i> พร้อมจำหน่าย</span>';
        case 'pre-order':
            return '<span class="badge badge-preorder"><i class="fas fa-clock"></i> พรีออเดอร์</span>';
        case 'out-of-stock':
            return '<span class="badge badge-danger">สินค้าหมด</span>';
        default:
            return '<span class="badge">' . e($status) . '</span>';
    }
}

function getContactLink($platform, $id) {
    if (empty($id)) return "#";
    if ($platform === 'line') return "https://line.me/ti/p/~" . urlencode($id);
    if ($platform === 'ig') return "https://www.instagram.com/" . urlencode($id);
    return "#";
}

function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        $icon = '<i class="fas fa-info-circle"></i>';
        if ($type === 'danger') $icon = '<i class="fas fa-exclamation-circle"></i>';
        if ($type === 'success') $icon = '<i class="fas fa-check-circle"></i>';
        if ($type === 'warning') $icon = '<i class="fas fa-exclamation-triangle"></i>';

        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return "<div class='alert alert-{$type}'>{$icon} <span>" . e($msg) . "</span></div>";
    }
    return "";
}

/**
 * 👑 🛠️ ฟังก์ชัน Redirect อัจฉริยะ (แก้ปัญหา Headers already sent 100%)
 */
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
    } else {
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url' /></noscript>";
    }
    exit();
}

function uploadImage($file, $targetDir = "../assets/images/products/") {
    $fileName = time() . "_" . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
    if (in_array(strtolower($fileType), $allowTypes)) {
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) return $fileName;
    }
    return false;
}

/**
 * BNCC Market - LINE Messaging API Function
 */
function sendLineMessagingAPI($userId, $message) {
    $channelAccessToken = 'guV8+F0mODk1GVFbH8IsksOyytFfYWAM7M8Mn1YsjFYDyL+2j7LcZcyHnFf3l/6gEx+zijEn0b0PUPwyfLjpxPhz+qD2LXYnlHNcIXQEA21pRmE5SIdN1IstTob1xlsMcupFcbi12wXEPDmutiB3OQdB04t89/1O/w1cDnyilFU='; 
    
    $url = 'https://api.line.me/v2/bot/message/push';
    $data = [
        'to' => $userId,
        'messages' => [['type' => 'text', 'text' => $message]]
    ];

    $postData = json_encode($data);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $channelAccessToken
    ]);

    $result = curl_exec($ch);
    file_put_contents('line_log.txt', date('Y-m-d H:i:s') . " - Push Res: " . $result . PHP_EOL, FILE_APPEND);
    curl_close($ch);
    return $result;
}

/**
 * ดึงรหัส LINE ของ Admin และ Teacher ทุกคน
 */
function getAllAdminLineIds() {
    $db = getDB();
    $stmt = $db->prepare("SELECT line_user_id FROM users WHERE role IN ('admin', 'teacher') AND line_user_id IS NOT NULL");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * วนลูปส่งแจ้งเตือนหาแอดมินและครูทุกคน
 */
function notifyAllAdmins($message) {
    sendLineNotify($message);
    $adminIds = getAllAdminLineIds();
    foreach ($adminIds as $id) {
        sendLineMessagingAPI($id, $message);
    }
}

/**
 * ฟังก์ชันบันทึก Log การทำงานของ Admin
 */
function logAdminAction($action_type, $target_type, $target_id, $details) {
    $db = getDB();
    $admin_id = $_SESSION['user_id'];
    $admin_name = $_SESSION['fullname'];

    $stmt = $db->prepare("INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$admin_id, $action_type, $target_type, $target_id, $details]);

    $message = "🛡️ [Admin Audit Log]\n"
             . "ผู้ดำเนินการ: $admin_name\n"
             . "การกระทำ: $action_type\n"
             . "เป้าหมาย: $target_type (ID: $target_id)\n"
             . "รายละเอียด: $details";
    
    notifyAllAdmins($message);
}

/**
 * 🔔 ฟังก์ชันส่งการแจ้งเตือน (In-App Notification)
 */
function sendNotification($user_id, $type, $message, $link = '#') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $type, $message, $link]);
}
?>