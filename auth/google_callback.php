<?php
/**
 * ระบบยืนยันตัวตนผ่าน Google OAuth 2.0
 */
require_once '../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google\Client();
$client->setClientId('349397957892-6m9lu6a6gd4605i8f9vruei5s07lh6hv.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://hosting.bncc.ac.th/s673190104/student_marketplace/auth/google_callback.php');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        die("เกิดข้อผิดพลาดจาก Google: " . e($token['error_description']));
    }
    $client->setAccessToken($token);

    $google_oauth = new Google\Service\Oauth2($client);
    $user_info = $google_oauth->userinfo->get();
    $email = $user_info->email;

    // ตรวจสอบโดเมนอีเมลวิทยาลัยอย่างเข้มงวด
    if (!str_ends_with($email, '@bncc.ac.th')) {
        $_SESSION['flash_message'] = "ขออภัย ระบบอนุญาตให้เข้าใช้งานเฉพาะอีเมลวิทยาลัย (@bncc.ac.th) เท่านั้น";
        $_SESSION['flash_type'] = "danger";
        redirect('login.php');
        exit(); // ใส่ exit กันโค้ดรันต่อ
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 🚫 🛠️ ตรวจสอบสถานะการโดนแบนสำหรับผู้ใช้ Google พร้อมปุ่มพรีเมียม
        if (isset($user['is_banned']) && $user['is_banned'] == 1) {
            $_SESSION['flash_message'] = "🚫 บัญชีของคุณถูกระงับการใช้งานชั่วคราว <br>
                <a href='appeal_ban.php' style='
                    display: inline-block; 
                    margin-top: 15px; 
                    padding: 10px 25px; 
                    background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%); 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 14px; 
                    font-weight: 800; 
                    font-size: 0.85rem;
                    box-shadow: 0 4px 15px rgba(225, 29, 72, 0.3);
                    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                ' onmouseover='this.style.transform=\"translateY(-2px)\"' onmouseout='this.style.transform=\"translateY(0)\"'>
                    <i class='fas fa-paper-plane'></i> ยื่นเรื่องขอกู้คืนบัญชีที่นี่
                </a>";
            $_SESSION['flash_type'] = "danger";
            redirect('login.php'); // ดีดกลับไปหน้า Login เพื่อโชว์ปุ่ม
            exit();
        }

        // บันทึกสถานะการเข้าสู่ระบบ
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['fullname']   = $user['fullname'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['profile_img'] = $user['profile_img'] ?? 'default_profile.png';
        
        redirect('../pages/index.php');
        exit();

    } else {
        // 🎯 🛠️ [แก้ไขใหม่] ถ้าเข้าใช้งานครั้งแรก (ไม่มีข้อมูลในระบบ) ให้เตะกลับไปหน้า Login และบอกให้ใช้ RMS
        $_SESSION['flash_message'] = "<b>พบการเข้าใช้งานครั้งแรก!</b><br>กรุณาเข้าสู่ระบบด้วย <b>(RMS)</b> ในครั้งแรก";
        $_SESSION['flash_type'] = "warning"; // ใช้สีเหลืองเตือน
        
        redirect('login.php');
        exit();
    }
} else {
    redirect('login.php');
    exit();
}
?>