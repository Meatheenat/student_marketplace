<?php
/**
 * BNCC Market - LINE Login Callback (Stable Version)
 * [Cite: User Summary] พัฒนาโดย Ploy IT Support & Gemini
 */

// 1. โหลดไฟล์ฟังก์ชันด้วย Absolute Path ที่มึงเทสผ่านแล้ว
require_once '/var/www/html/s673190104/student_marketplace/includes/functions.php';

// 2. รับ Auth Code และ State จาก LINE
$code = $_GET['code'] ?? null;
$user_id_system = $_GET['state'] ?? null; 

if (!$code) {
    $_SESSION['flash_message'] = "ไม่พบรหัสยืนยันจาก LINE";
    $_SESSION['flash_type'] = "danger";
    redirect(BASE_URL . "pages/profile.php");
}

// 3. ตั้งค่า LINE API (ต้องตรงกับใน LINE Developers Console เป๊ะๆ)
$channel_id = "2009322126";
$channel_secret = "b83df056e173fc49bd15155f243d70e1";
$callback_url = "https://hosting.bncc.ac.th/s673190104/student_marketplace/auth/line_login_callback.php";

// 4. แลก Code เป็น Access Token
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

    // 5. ใช้ Access Token ไปดึง Profile ของผู้ใช้
    $ch = curl_init("https://api.line.me/v2/profile");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $profile = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($profile['userId'])) {
        $line_uid = $profile['userId'];
        
        try {
            // 🎯 ใช้การเชื่อมต่อจาก database.php ที่มึงแก้รหัสผ่านสำเร็จแล้ว
            $db = getDB(); 
            $role = $_SESSION['role'] ?? 'buyer';

            // 🛡️ บันทึก LINE ID ตามบทบาทผู้ใช้
            if ($role === 'admin' || $role === 'teacher') {
                $stmt = $db->prepare("UPDATE users SET line_user_id = ? WHERE id = ?");
            } else {
                $stmt = $db->prepare("UPDATE shops SET line_user_id = ? WHERE user_id = ?");
            }
            
            $save_result = $stmt->execute([$line_uid, $user_id_system]);

            if ($save_result) {
                $_SESSION['flash_message'] = "เชื่อมต่อระบบแจ้งเตือน LINE สำเร็จ!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "บันทึกข้อมูลไม่สำเร็จ (SQL Error)";
                $_SESSION['flash_type'] = "danger";
            }
        } catch (PDOException $e) {
            // กรณีเกิด Error ทางฐานข้อมูลให้เก็บลง Log หรือแจ้งเตือน
            $_SESSION['flash_message'] = "ระบบฐานข้อมูลขัดข้อง: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
} else {
    $_SESSION['flash_message'] = "การเชื่อมต่อ LINE ล้มเหลว (Invalid Token)";
    $_SESSION['flash_type'] = "danger";
}

// 6. ส่งผู้ใช้กลับหน้า Profile
redirect(BASE_URL . "pages/profile.php");