<?php
/**
 * BNCC Market - LINE Login Callback (Production Version)
 * ไฟล์นี้ทำหน้าที่รับ Auth Code มาแลกเป็น User ID และบันทึกลงฐานข้อมูล
 */
require_once '../includes/functions.php';
// ลบ session_start(); บรรทัดนี้ออก เพราะใน functions.php มีการสั่ง start ไปแล้ว

// รับค่าจาก LINE
$code = $_GET['code'] ?? null;
$user_id_system = $_GET['state'] ?? null; 

if (!$code) {
    $_SESSION['flash_message'] = "ไม่พบรหัสยืนยันจาก LINE กรุณาลองใหม่อีกครั้ง";
    $_SESSION['flash_type'] = "danger";
    header("Location: ../pages/profile.php");
    exit();
}

// 1. ตั้งค่า API
$channel_id = "2009322126";
$channel_secret = "b83df056e173fc49bd15155f243d70e1";
$callback_url = "http://localhost/student_marketplace/auth/line_login_callback.php";

// 2. ขั้นตอนแลก Code เป็น Access Token
$ch = curl_init("https://api.line.me/oauth2/v2.1/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $callback_url,
    'client_id' => $channel_id,
    'client_secret' => $channel_secret
]));
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($response['access_token'])) {
    $access_token = $response['access_token'];

    // 3. ใช้ Access Token ไปดึง Profile
    $ch = curl_init("https://api.line.me/v2/profile");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $profile = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($profile['userId'])) {
        $line_uid = $profile['userId'];
        $db = getDB();

        // 🛡️ เช็ก Role เพื่อเลือกตารางบันทึก
        $role = $_SESSION['role'] ?? 'buyer';

        // 🛠️ [แก้ไขใหม่] ปรับเงื่อนไขให้ครอบคลุมทั้ง admin และ teacher
        if ($role === 'admin' || $role === 'teacher') {
            // บันทึกลงตาราง users (สำหรับ Admin และ Teacher เพื่อรับ Audit Log)
            $stmt = $db->prepare("UPDATE users SET line_user_id = ? WHERE id = ?");
            $save_result = $stmt->execute([$line_uid, $user_id_system]);
        } else {
            // บันทึกลงตาราง shops สำหรับ Seller เพื่อรับแจ้งเตือนรีวิวสินค้า
            $stmt = $db->prepare("UPDATE shops SET line_user_id = ? WHERE user_id = ?");
            $save_result = $stmt->execute([$line_uid, $user_id_system]);
        }
        
        if ($save_result) {
            $_SESSION['flash_message'] = "เชื่อมต่อระบบแจ้งเตือน LINE สำเร็จ!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "บันทึกข้อมูลไม่สำเร็จ กรุณาเช็กฐานข้อมูล";
            $_SESSION['flash_type'] = "danger";
        }
    }
} else {
    $_SESSION['flash_message'] = "เกิดข้อผิดพลาดในการเชื่อมต่อ LINE (Invalid Token)";
    $_SESSION['flash_type'] = "danger";
}

header("Location: ../pages/profile.php");
exit();