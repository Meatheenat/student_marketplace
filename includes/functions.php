<?php
/**
 * Student Marketplace - Core Functions (Production Version)
 * [KEEP ALL FUNCTIONS - NO DELETION]
 */

// 🚀 1. ระบบตรวจหา URL เริ่มต้นอัตโนมัติ (🎯 FIXED: ป้องกันปัญหาลิงก์พัง/เบิ้ลโฟลเดอร์)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];

/**
 * 🎯 จุดแก้ไขสำคัญ: 
 * ใช้ Path เต็มรูปแบบเพื่อให้ระบบอ้างอิงจาก Root ของ Domain เสมอ
 * ป้องกันการเกิด pages/admin/... หรือโฟลเดอร์ซ้อนกัน
 */
// 🎯 FIXED: บังคับ Path ให้ตรงกับโฟลเดอร์โปรเจกต์บน Host BNCC
$project_folder = "/s673190104/student_marketplace";
$base_url = "$protocol://$host/$project_folder/"; 

// คาดว่าของเดิมอาจจะเป็น: define('BASE_URL', '/s673190104/');
// ให้แก้เป็น:
define('BASE_URL', '/s673190104/student_marketplace/');
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
        // 🎯 ใช้ BASE_URL นำหน้าเพื่อให้ Redirect ถูกต้องทุกลำดับโฟลเดอร์
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
        return "<div class='alert alert-{$type}'>{$icon} <span>" . $msg . "</span></div>";
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
    curl_setopt($ch, CURLOPT_URL, $url);
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
 * 🎯 FIXED: บังคับให้ Link เป็นแบบ Absolute Path อัตโนมัติ ป้องกัน 404
 */
/**
 * 🔔 ฟังก์ชันส่งการแจ้งเตือน (In-App Notification)
 * 🎯 FIXED: แก้ปัญหาลิงก์ขาดโฟลเดอร์ student_marketplace (404)
 */
function sendNotification($user_id, $type, $message, $link = '#') {
    $db = getDB();
    
    if ($link !== '#' && !filter_var($link, FILTER_VALIDATE_URL)) {
        $clean_link = ltrim($link, '/');
        
        // 🎯 ตรวจสอบว่าลิงก์มีชื่อโฟลเดอร์โปรเจกต์ "ครบถ้วน" หรือยัง
        // ถ้าไม่มีก้อน student_marketplace ให้เอา BASE_URL (ที่มีพาร์ทครบ) ไปแปะหน้าทันที
        if (strpos($clean_link, 'student_marketplace') === false) {
            $link = BASE_URL . $clean_link;
        } else {
            // ถ้ามีชื่อโปรเจกต์แล้ว แต่ยังไม่มี http (เป็นพาร์ทจาก root domain)
            // ให้เติม protocol และ host เข้าไปข้างหน้าให้สมบูรณ์
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $link = "$protocol://$host/" . $clean_link;
        }
    }

    // บันทึกลงฐานข้อมูลพร้อมเวลาปัจจุบัน
    $stmt = $db->prepare("INSERT INTO notifications (user_id, type, message, link, created_at) VALUES (?, ?, ?, ?, NOW())");
    return $stmt->execute([$user_id, $type, $message, $link]);
}
/**
 * 🎯 [ADDED] ฟังก์ชันเสริมสำหรับระบบติดตามร้านค้า (Follow System Helper)
 */
function isFollowing($user_id, $shop_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM follows WHERE user_id = ? AND shop_id = ?");
    $stmt->execute([$user_id, $shop_id]);
    return $stmt->fetch() ? true : false;
}

function notifyShopFollowers($shop_id, $message, $link = '#') {
    $db = getDB();
    $stmt = $db->prepare("SELECT user_id FROM follows WHERE shop_id = ?");
    $stmt->execute([$shop_id]);
    $followers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($followers as $follower_id) {
        sendNotification($follower_id, 'system', $message, $link);
    }
}

/**
 * 🥇 [NEW] ฟังก์ชันดึงป้ายยศ (User Roles & Badges)
 */
function getUserBadge($role) {
    if ($role === 'admin') {
        return '<span class="badge" style="background:#ef4444; color:white; border-radius:6px; padding:3px 8px; font-size:0.75rem; font-weight:bold; margin-left:5px;"><i class="fas fa-shield-alt"></i> Admin</span>';
    }
    if ($role === 'teacher') {
        return '<span class="badge" style="background:#6366f1; color:white; border-radius:6px; padding:3px 8px; font-size:0.75rem; font-weight:bold; margin-left:5px;"><i class="fas fa-graduation-cap"></i> Teacher</span>';
    }
    return '';
}

/**
 * 🥇 [NEW] ฟังก์ชันตรวจสอบและดึงป้าย "ร้านค้าแนะนำ"
 */
function getShopBadge($shop_id) {
    $db = getDB();
    try {
        $top_follow = $db->query("SELECT shop_id FROM follows GROUP BY shop_id ORDER BY COUNT(id) DESC LIMIT 1")->fetchColumn();
        $top_rating = $db->query("SELECT p.shop_id FROM reviews r JOIN products p ON r.product_id = p.id WHERE r.is_deleted = 0 GROUP BY p.shop_id HAVING COUNT(r.id) >= 3 ORDER BY AVG(r.rating) DESC LIMIT 1")->fetchColumn();

        if ($shop_id == $top_follow && $shop_id == $top_rating && $shop_id != false) {
            return '<span class="badge" style="background: linear-gradient(45deg, #fbbf24, #f59e0b); color:#000; border-radius:6px; padding:4px 10px; font-size:0.8rem; font-weight:900; box-shadow:0 4px 12px rgba(251,191,36,0.3); border:1px solid #000; margin-left:8px;"><i class="fas fa-medal"></i> ร้านค้าแนะนำ</span>';
        }
    } catch (Exception $e) { return ''; }
    return '';
}

/**
 * 🛡️ [UPDATED] ฟังก์ชันเช็กสิทธิ์รีวิว
 */
function canUserReview($user_id, $product_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND is_deleted = 0");
    $stmt->execute([$user_id, $product_id]);
    if ($stmt->fetch()) {
        return ['status' => false, 'message' => 'คุณเคยรีวิวสินค้านี้ไปแล้ว'];
    }
    return ['status' => true];
}

/**
 * 📧 [ACTIVE] ฟังก์ชันส่งรหัส OTP เข้า Email
 */
function sendOTPToEmail($to_email, $otp_code) {
    $subject = "รหัสยืนยันตัวตน (OTP) - BNCC Market";
    $logo_url = "https://cdn-icons-png.flaticon.com/512/3081/3081986.png"; 

    $message = "
    <html>
    <head><title>รหัสยืนยันตัวตน</title></head>
    <body style='background-color: #f1f5f9; padding: 40px 20px; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif;'>
        <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; padding: 40px 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; border: 1px solid #e2e8f0;'>
            <img src='{$logo_url}' alt='BNCC Market' style='width: 80px; height: 80px; margin-bottom: 15px;'>
            <h2 style='color: #4f46e5; margin-top: 0; font-size: 24px; font-weight: 900; letter-spacing: -0.5px;'>BNCC Market</h2>
            <p style='color: #475569; font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>สวัสดีครับ,<br>นี่คือรหัสความปลอดภัย (OTP) สำหรับยืนยันตัวตนของคุณเพื่อเข้าใช้งานระบบ</p>
            <div style='background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); padding: 25px; border-radius: 16px; margin-bottom: 25px; border: 1px solid #a5b4fc;'>
                <span style='font-size: 40px; font-weight: 900; color: #4338ca; letter-spacing: 10px; display: block;'>{$otp_code}</span>
            </div>
            <p style='color: #ef4444; font-size: 14px; font-weight: bold; margin-bottom: 0; background: #fef2f2; padding: 12px; border-radius: 10px; display: inline-block;'>⚠️ โปรดอย่าเปิดเผยรหัสนี้ให้ใครทราบเด็ดขาด</p>
            <hr style='border: none; border-top: 1px dashed #cbd5e1; margin: 35px 0 25px;'>
            <p style='color: #94a3b8; font-size: 12px; margin: 0;'>อีเมลฉบับนี้ถูกส่งจากระบบอัตโนมัติ กรุณาอย่าตอบกลับอีเมลนี้<br>&copy; " . date('Y') . " BNCC Market. All rights reserved.</p>
        </div>
    </body>
    </html>";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: BNCC Market <system@bncc.ac.th>\r\n";
    @mail($to_email, $subject, $message, $headers);
    return true;
}

/**
 * 📢 ฟังก์ชันแจ้งเตือนคนขาย (Web + LINE)
 */
function notifySeller($seller_id, $message, $link = '#') {
    $db = getDB();
    sendNotification($seller_id, 'system', $message, $link);
    $stmt = $db->prepare("SELECT line_user_id FROM users WHERE id = ? AND line_user_id IS NOT NULL");
    $stmt->execute([$seller_id]);
    $line_id = $stmt->fetchColumn();
    if ($line_id) {
        sendLineMessagingAPI($line_id, "📢 BNCC Market: " . $message . "\nตรวจสอบได้ที่: " . $link);
    }
    return true;
}

/* =========================================================================

   🚀 [FUTURE USE] โค้ดสำหรับใช้ PHPMailer (รอใช้อีเมลทางการของวิทยาลัย)

   

   วิธีสลับมาใช้ตัวนี้: 

   1. ลบหรือคอมเมนต์ฟังก์ชัน sendOTPToEmail() ด้านบนทิ้ง

   2. ลบเครื่องหมายคอมเมนต์ (/* และ * /) ของบล็อกด้านล่างนี้ออก

   3. แก้ไข Username และ Password ให้เป็นอีเมลของวิทยาลัย

   ========================================================================= */



/*

function sendOTPToEmail($to_email, $otp_code) {

    require_once __DIR__ . '/../vendor/autoload.php';

    

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    

    try {

        // ⚙️ ตั้งค่าเซิร์ฟเวอร์ (สมมติว่าวิทยาลัยใช้ระบบของ Gmail/Google Workspace)

        $mail->isSMTP();

        $mail->Host       = 'smtp.gmail.com'; 

        $mail->SMTPAuth   = true;

        

        // 🔑 ใส่ข้อมูลอีเมลทางการตรงนี้

        $mail->Username   = 'official_email@bncc.ac.th'; // เปลี่ยนเป็นอีเมลวิทยาลัย

        $mail->Password   = 'รหัสผ่าน_หรือ_App_Password'; // เปลี่ยนเป็นรหัสผ่าน

        

        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port       = 587;

        $mail->CharSet    = 'UTF-8';



        // 🎯 ตั้งค่าผู้ส่งและผู้รับ

        $mail->setFrom('official_email@bncc.ac.th', 'BNCC Market');

        $mail->addAddress($to_email);



        // 🎨 เนื้อหาอีเมล (HTML สวยๆ เหมือนเดิม)

        $mail->isHTML(true);

        $mail->Subject = 'รหัสยืนยันตัวตน (OTP) - BNCC Market';

     



        $mail->Body = "

        <html>

        <head><title>รหัสยืนยันตัวตน</title></head>

        <body style='background-color: #f1f5f9; padding: 40px 20px; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif;'>

            <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; padding: 40px 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; border: 1px solid #e2e8f0;'>

                <img src='{$logo_url}' alt='BNCC Market' style='width: 80px; height: 80px; margin-bottom: 15px;'>

                <h2 style='color: #4f46e5; margin-top: 0; font-size: 24px; font-weight: 900; letter-spacing: -0.5px;'>BNCC Market</h2>

                <p style='color: #475569; font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>

                    สวัสดีครับ,<br>นี่คือรหัสความปลอดภัย (OTP) สำหรับยืนยันตัวตนของคุณเพื่อเข้าใช้งานระบบ

                </p>

                <div style='background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); padding: 25px; border-radius: 16px; margin-bottom: 25px; border: 1px solid #a5b4fc;'>

                    <span style='font-size: 40px; font-weight: 900; color: #4338ca; letter-spacing: 10px; display: block;'>{$otp_code}</span>

                </div>

                <p style='color: #ef4444; font-size: 14px; font-weight: bold; margin-bottom: 0; background: #fef2f2; padding: 12px; border-radius: 10px; display: inline-block;'>

                    ⚠️ โปรดอย่าเปิดเผยรหัสนี้ให้ใครทราบเด็ดขาด

                </p>

                <hr style='border: none; border-top: 1px dashed #cbd5e1; margin: 35px 0 25px;'>

                <p style='color: #94a3b8; font-size: 12px; margin: 0;'>

                    อีเมลฉบับนี้ถูกส่งจากระบบอัตโนมัติ กรุณาอย่าตอบกลับอีเมลนี้<br>

                    &copy; " . date('Y') . " BNCC Market. All rights reserved.

                </p>

            </div>

        </body>

        </html>

        ";



        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log("ส่งอีเมล OTP ไม่สำเร็จ: {$mail->ErrorInfo}");

        return false;

    }

}
    */