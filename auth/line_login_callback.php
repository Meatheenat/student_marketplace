<?php
/**
 * BNCC Market - LINE Login Callback (Production Fixed)
 * [Cite: User Summary] แก้ไขโดย Gemini & Ploy (Senior IT Support)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🎯 1. ลองประกาศตัวแปรเชื่อมต่อเองตรงนี้เลย (ห้ามเรียกจากไฟล์อื่น)
$host = 'hosting.bncc.ac.th';
$db   = 's673190104';   // 📍 ก๊อปจากหน้าโฮสต์มาวางใหม่!
$user = 's673190104'; // 📍 ก๊อปจากหน้าโฮสต์มาวางใหม่!
$pass = 's673190104';

try {
    $db_conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    // echo "ถ้าเห็นข้อความนี้ แสดงว่าข้อมูล DB ถูกต้อง!"; 
} catch (PDOException $e) {
    // 🎯 ถ้ามันยัง Error 1045 ตรงนี้ มึงเลิกแก้โค้ด แล้วไปคุยกับหน้าจัดการโฮสต์ทันที!
    die("❌ จุดตายอยู่ที่นี่: " . $e->getMessage());
}
// 🎯 1. เปิดระบบ Error Reporting เพื่อดูว่ามันตายที่บรรทัดไหนแน่
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/functions.php';

// รับค่าจาก LINE
$code = $_GET['code'] ?? null;
$user_id_system = $_GET['state'] ?? null; 

if (!$code) {
    $_SESSION['flash_message'] = "ไม่พบรหัสยืนยันจาก LINE";
    $_SESSION['flash_type'] = "danger";
    header("Location: ../pages/profile.php");
    exit();
}

// 🎯 2. แก้ไขจุดตาย: เปลี่ยนจาก localhost เป็น URL บน Host จริง
$channel_id = "2009322126";
$channel_secret = "b83df056e173fc49bd15155f243d70e1";
// 📍 จี้จุดที่ 1: ใส่ URL เต็มๆ ของมึงบนโฮสต์ BNCC
$callback_url = "https://hosting.bncc.ac.th/s673190104/student_marketplace/auth/line_login_callback.php";

// 3. แลก Access Token
$ch = curl_init("https://api.line.me/oauth2/v2.1/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $callback_url, // 📍 ต้องตรงกับใน LINE Developers Console
    'client_id' => $channel_id,
    'client_secret' => $channel_secret
]));
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($response['access_token'])) {
    $access_token = $response['access_token'];

    // 4. ดึง Profile
    $ch = curl_init("https://api.line.me/v2/profile");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $profile = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($profile['userId'])) {
        $line_uid = $profile['userId'];
        
        try {
            // 📍 จี้จุดที่ 2: ถ้าบรรทัดนี้ Error 1045 แสดงว่าข้อมูลใน includes/database.php ผิด!
            $db = getDB();

            $role = $_SESSION['role'] ?? 'buyer';

            if ($role === 'admin' || $role === 'teacher') {
                $stmt = $db->prepare("UPDATE users SET line_user_id = ? WHERE id = ?");
                $save_result = $stmt->execute([$line_uid, $user_id_system]);
            } else {
                $stmt = $db->prepare("UPDATE shops SET line_user_id = ? WHERE user_id = ?");
                $save_result = $stmt->execute([$line_uid, $user_id_system]);
            }
            
            if ($save_result) {
                $_SESSION['flash_message'] = "เชื่อมต่อระบบแจ้งเตือน LINE สำเร็จ!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "บันทึกข้อมูลไม่สำเร็จ";
                $_SESSION['flash_type'] = "danger";
            }
        } catch (PDOException $e) {
            // 🎯 แสดง Error ชัดๆ ว่าทำไม DB ถึงบล็อก (Access Denied)
            die("Database Error: " . $e->getMessage() . " (เช็กไฟล์ includes/database.php ด่วน!)");
        }
    }
} else {
    // 🎯 ถ้าขึ้นหน้านี้ แสดงว่า $callback_url ในโค้ดไม่ตรงกับใน LINE Console
    die("LINE Auth Error: " . json_encode($response));
}

header("Location: ../pages/profile.php");
exit();