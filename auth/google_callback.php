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
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 🚫 🛠️ เงื่อนไขเพิ่มเติม: ตรวจสอบสถานะการโดนแบนก่อนเข้าสู่ระบบ
        if (isset($user['is_banned']) && $user['is_banned'] == 1) {
            $_SESSION['flash_message'] = "🚫 บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อแอดมินเพื่อตรวจสอบ";
            $_SESSION['flash_type'] = "danger";
            redirect('login.php'); // ส่งกลับหน้า Login ทันที
        }

        // บันทึกสถานะการเข้าสู่ระบบ
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['fullname']   = $user['fullname'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['student_id'] = $user['student_id'];
        redirect('../pages/index.php');
    } else {
        // หากยังไม่มีข้อมูลสมาชิก ให้ไปหน้าลงทะเบียนเพิ่มเติม
        $_SESSION['temp_email'] = $email;
        redirect('register_google.php');
    }
} else {
    redirect('login.php');
}