<?php
/**
 * ระบบตลาดออนไลน์นักเรียน (Student Marketplace)
 * หน้าเข้าสู่ระบบ (Login Page) - [THEME DYNAMIC REDESIGN + RMS API]
 */

$pageTitle = "เข้าสู่ระบบ - Student Marketplace";
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

// ------------------------------------------------------------------
// 🚀 [เพิ่มใหม่] ฟังก์ชันเจาะระบบ RMS (ป้องกันไฟล์ Cookie ตีกัน)
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// 🚀 ฟังก์ชันเจาะระบบ RMS พร้อมสแกนดึง "ชื่อ-นามสกุล" จริง (แก้บั๊กคำติดกัน)
// ------------------------------------------------------------------
function loginWithRMS($username, $password){
    $loginPage = "https://rms.bncc.ac.th/?p=login";
    $loginAction = "https://rms.bncc.ac.th/check.php";
    $cookie = tempnam(sys_get_temp_dir(), 'RMSC'); 

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $loginPage,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    curl_exec($ch);
    curl_close($ch);

    $postData = http_build_query([
        'username_log' => $username,
        'password_log' => $password,
        'school_id' => '01'
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $loginAction,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if(file_exists($cookie)) unlink($cookie);

    // แปลงภาษาไทย
    $response = iconv("TIS-620", "UTF-8//IGNORE", $response);
    
    if(strpos($response, "สถานการณ์") !== false){
        $real_name = "นักศึกษา " . $username;
        
        // 🛠️ ทริคแก้คำติดกัน: เปลี่ยนแท็กตารางให้เป็นช่องว่างก่อน แล้วค่อยลบ HTML ทิ้ง
        $clean_resp = str_replace(['<td', '<th', '</td', '</th', '<tr', '</tr'], ' <', $response);
        $clean_resp = strip_tags($clean_resp);
        $clean_resp = str_replace('&nbsp;', ' ', $clean_resp);
        
        // 🛠️ เตะคำขยะที่ชอบติดมาทิ้งไปให้หมด
        $clean_resp = str_replace(['คำนำหน้าและชื่อ', 'ชื่อ-สกุล', 'รหัสนักศึกษา'], '', $clean_resp);
        
        // ยุบช่องว่างที่มันห่างเกินไปให้เหลือเคาะเดียว
        $clean_resp = preg_replace('/\s+/u', ' ', $clean_resp);
        
        // จับคำว่า นาย/นางสาว/นาง ตามด้วยชื่อและนามสกุล
        if (preg_match('/(นาย|นางสาว|นาง)\s*([ก-๙]+)\s+([ก-๙]+)/u', $clean_resp, $matches)) {
            $real_name = $matches[1] . $matches[2] . " " . $matches[3]; 
        }

        return ['success' => true, 'fullname' => $real_name];
    }
    return ['success' => false, 'fullname' => ''];
}
// 1. ตรวจสอบสถานะ: หากเข้าสู่ระบบอยู่แล้ว ให้เปลี่ยนหน้าไปยังหน้าแรกทันที
if (isLoggedIn()) {
    redirect('../pages/index.php');
}

// 2. ตั้งค่าระบบยืนยันตัวตนผ่าน Google (Google OAuth)
$client = new Google\Client();
$client->setClientId('349397957892-6m9lu6a6gd4605i8f9vruei5s07lh6hv.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://hosting.bncc.ac.th/s673190104/student_marketplace/auth/google_callback.php');
$client->addScope("email");
$client->addScope("profile");
$client->setPrompt('select_account'); 
$googleAuthUrl = $client->createAuthUrl();

// 3. จัดการการเข้าสู่ระบบแบบเบ็ดเสร็จ (Turnstile + Local DB + RMS)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 🛡️ ตรวจสอบ Cloudflare Turnstile
    $secret_key = '0x4AAAAAACoR11ccsBAXDbqrr_1J8UB8UXw';
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';

    $verify_url = "https://challenges.cloudflare.com/turnstile/v0/siteverify";
    $data = [
        'secret' => $secret_key,
        'response' => $turnstile_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($verify_url, false, $context);
    $result = json_decode($response);

    if (!$result->success) {
        $_SESSION['flash_message'] = "กรุณายืนยันตัวตนว่าคุณไม่ใช่บอท";
        $_SESSION['flash_type'] = "danger";
        redirect('login.php');
        exit(); 
    }

    $login_input = trim($_POST['login_input']);
    $password    = $_POST['password'];

    if (!empty($login_input) && !empty($password)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR student_id = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        $login_success = false;

        // 🎯 เช็คกับฐานข้อมูลระบบเราก่อน
        if ($user && password_verify($password, $user['password'])) {
            $login_success = true;
        } 
        // 🎯 ถ้ารหัสในระบบเราผิด หรือไม่มีบัญชี ให้วิ่งไปเจาะ RMS!
        else {
            $rmsResult = loginWithRMS($login_input, $password);
            if ($rmsResult['success']) {
                $login_success = true;
                $real_name = $rmsResult['fullname']; // ได้ชื่อจริงมาละ

                if (!$user) {
                    $student_email = is_numeric($login_input) ? $login_input . "@bncc.ac.th" : $login_input;
                    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insert = $db->prepare("INSERT INTO users (student_id, email, password, fullname, role) VALUES (?, ?, ?, ?, 'buyer')");
                    $insert->execute([$login_input, $student_email, $hashed_pass, $real_name]);
                    
                    $user_id = $db->lastInsertId();
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                } else {
                    // ถ้ามีข้อมูลอยู่แล้ว ให้อัปเดตรหัสผ่านและ "ชื่อจริง" ให้เป็นปัจจุบันเสมอ!
                    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                    $update = $db->prepare("UPDATE users SET password = ?, fullname = ? WHERE id = ?");
                    $update->execute([$hashed_pass, $real_name, $user['id']]);
                    $user['fullname'] = $real_name; // อัปเดตตัวแปรเพื่อส่งเข้า Session
                }
            }
        }
        // ✅ สรุปผลการล็อกอิน (ผ่านแล้วเช็คต่อว่าโดนแบนไหม)
        if ($login_success) {
            if (isset($user['is_banned']) && $user['is_banned'] == 1) {
                $_SESSION['flash_message'] = "🚫 บัญชีของคุณถูกระงับการใช้งานชั่วคราว <br>
                    <a href='appeal_ban.php' style='display: inline-block; margin-top: 12px; padding: 10px 25px; background: #ef4444; color: white; text-decoration: none; border-radius: 14px; font-weight: 800; font-size: 0.85rem; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); transition: all 0.3s ease;'>ยื่นเรื่องขอกู้คืนบัญชีที่นี่</a>";
                $_SESSION['flash_type'] = "danger";
                redirect('login.php');
                exit();
            }

            $_SESSION['user_id']    = $user['id'];
            $_SESSION['fullname']   = $user['fullname'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['student_id'] = $user['student_id'] ?? $login_input;
            redirect('../pages/index.php');
        } else {
            $_SESSION['flash_message'] = "รหัสนักศึกษา หรือ รหัสผ่านไม่ถูกต้อง";
            $_SESSION['flash_type'] = "danger";
        }
    }
}
?>

<style>
    /* ============================================================
       🎨 DYNAMIC THEME VARIABLES FOR LOGIN PAGE
       ============================================================ */
    :root {
        --login-bg: #f8fafc;
        --login-orb-1: rgba(79, 70, 229, 0.1);
        --login-orb-2: rgba(168, 85, 247, 0.1);
        --login-card-bg: rgba(255, 255, 255, 0.7);
        --login-card-border: rgba(0, 0, 0, 0.05);
        --login-card-shadow: 0 30px 60px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255,255,255,0.5);
        --login-text-glow: rgba(0, 0, 0, 0.1);
        --login-input-bg: rgba(255, 255, 255, 0.5);
        --login-input-focus: rgba(255, 255, 255, 0.9);
        --login-icon-color: rgba(30, 41, 59, 0.4);
    }

    .dark-theme {
        --login-bg: #0b0e14;
        --login-orb-1: rgba(99, 102, 241, 0.15);
        --login-orb-2: rgba(168, 85, 247, 0.1);
        --login-card-bg: rgba(20, 25, 40, 0.55);
        --login-card-border: rgba(255, 255, 255, 0.05);
        --login-card-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255,255,255,0.05);
        --login-text-glow: rgba(255, 255, 255, 0.4);
        --login-input-bg: rgba(0, 0, 0, 0.3);
        --login-input-focus: rgba(0, 0, 0, 0.5);
        --login-icon-color: rgba(255, 255, 255, 0.3);
    }

    /* ============================================================
       🌌 1. DYNAMIC SPACE BACKGROUND WITH GLOW ORBS
       ============================================================ */
    body {
        margin: 0;
        background-color: var(--login-bg) !important;
        font-family: 'Prompt', sans-serif;
        transition: background-color 0.5s ease;
    }

    .login-master-wrapper {
        position: relative;
        min-height: calc(100vh - 75px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        overflow: hidden;
        z-index: 1;
    }

    .glow-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(120px);
        z-index: -1;
        opacity: 0.8;
        animation: pulseOrb 8s infinite alternate ease-in-out;
        transition: background 0.5s ease;
    }
    .glow-orb-1 {
        width: 600px; height: 600px;
        background: radial-gradient(circle, var(--login-orb-1) 0%, transparent 70%);
        top: -10%; left: 0%;
    }
    .glow-orb-2 {
        width: 500px; height: 500px;
        background: radial-gradient(circle, var(--login-orb-2) 0%, transparent 70%);
        bottom: -10%; right: 0%;
        animation-delay: -4s;
    }

    @keyframes pulseOrb {
        0% { transform: scale(0.9); opacity: 0.6; }
        100% { transform: scale(1.1); opacity: 1; }
    }

    /* ============================================================
       💎 2. DYNAMIC GLASS CARD
       ============================================================ */
    .login-card {
        position: relative;
        width: 100%;
        max-width: 440px;
        background: var(--login-card-bg);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid var(--login-card-border);
        border-radius: 32px;
        padding: 50px 45px;
        box-shadow: var(--login-card-shadow);
        opacity: 0;
        transform: translateY(30px);
        animation: entranceCard 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        transition: background 0.5s ease, border 0.5s ease, box-shadow 0.5s ease;
    }

    @keyframes entranceCard {
        to { opacity: 1; transform: translateY(0); }
    }

    /* ============================================================
       ✨ 3. HEADINGS & TYPOGRAPHY
       ============================================================ */
    .login-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .login-header h2 {
        font-size: 2.8rem;
        font-weight: 900;
        margin: 0 0 10px 0;
        color: var(--text-main);
        letter-spacing: -1px;
        text-shadow: 0 0 25px var(--login-text-glow);
        animation: textGlow 4s infinite alternate;
        transition: color 0.5s ease, text-shadow 0.5s ease;
    }
    
    @keyframes textGlow {
        0% { filter: brightness(1); }
        100% { filter: brightness(1.2); }
    }

    .login-header p {
        color: var(--text-muted);
        font-size: 0.95rem;
        font-weight: 500;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: color 0.5s ease;
    }

    /* ============================================================
       ⌨️ 4. DYNAMIC INPUTS
       ============================================================ */
    .form-group {
        margin-bottom: 25px;
    }
    .field-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 10px;
        margin-left: 5px;
        transition: color 0.5s ease;
    }
    .input-wrapper {
        position: relative;
    }
    .form-control-custom {
        width: 100%;
        padding: 18px 20px 18px 55px;
        border-radius: 18px;
        background: var(--login-input-bg);
        border: 1px solid var(--login-card-border);
        color: var(--text-main);
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        outline: none;
    }
    .form-control-custom::placeholder {
        color: var(--login-icon-color);
    }
    .form-control-custom:focus {
        background: var(--login-input-focus);
        border-color: rgba(99, 102, 241, 0.6);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        transform: translateY(-2px);
    }

    .input-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #000000 !important; /* 🎯 บังคับสีขาวล้วน */
        filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.4)); /* 🎯 ใส่เงาดำบางๆ ให้ไอคอนลอยเด่นชัดขึ้น */
        font-size: 1.2rem;
        transition: 0.3s;
        pointer-events: none;
        opacity: 1 !important;
    }
    .form-control-custom:focus + .input-icon {
        color: #ffffff !important;
        filter: drop-shadow(0px 0px 6px rgba(255,255,255,0.8)); /* 🎯 เรืองแสงตอนกดพิมพ์ */
    }

    .pass-toggle-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #000000 !important; /* 🎯 บังคับสีขาวล้วน */
        filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.4)); /* 🎯 ใส่เงาให้มองเห็นชัดๆ */
        padding: 5px;
        transition: 0.2s;
        opacity: 1 !important;
    }
    .pass-toggle-icon:hover { 
        color: #ffffff !important; 
        filter: drop-shadow(0px 0px 6px rgba(255,255,255,0.8)); 
    }

    .forgot-link {
        color: var(--text-muted);
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        transition: 0.3s;
    }
    .forgot-link:hover { color: var(--primary-color); }

    /* ============================================================
       🔥 5. RADIANT BUTTON
       ============================================================ */
    .btn-login-main {
        width: 100%;
        padding: 18px;
        border-radius: 18px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #ffffff;
        font-weight: 800;
        font-size: 1.05rem;
        border: none;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 15px;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-login-main:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.6);
        filter: brightness(1.15);
    }

    /* ============================================================
       🌐 6. DIVIDER & GOOGLE BUTTON (DYNAMIC MODE)
       ============================================================ */
    .divider-custom {
        position: relative;
        text-align: center;
        margin: 40px 0 30px;
    }
    .divider-custom::before {
        content: "";
        position: absolute;
        top: 50%; left: 0; width: 100%; height: 1px;
        background: var(--login-card-border);
    }
    .divider-text {
        position: relative;
        background: var(--bg-card);
        padding: 4px 15px;
        border-radius: 8px;
        color: var(--text-muted);
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 2px;
        text-transform: uppercase;
        border: 1px solid var(--login-card-border);
        transition: background 0.5s ease, color 0.5s ease;
    }

    .google-btn-custom {
        width: 100%;
        background: var(--login-input-bg);
        color: var(--text-main);
        border: 1px solid var(--login-card-border);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 16px;
        font-weight: 600;
        font-size: 0.95rem;
        border-radius: 18px;
        text-decoration: none;
        transition: 0.3s;
    }
    .google-btn-custom:hover {
        background: var(--login-input-focus);
        border-color: rgba(99, 102, 241, 0.3);
        transform: translateY(-2px);
    }

    .register-link-box {
        text-align: center;
        margin-top: 40px;
    }
    .register-link-box p {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin: 0;
        transition: color 0.5s ease;
    }
    .register-link-box a {
        color: #818cf8;
        font-weight: 800;
        text-decoration: none;
        margin-left: 5px;
        transition: 0.3s;
    }
    .register-link-box a:hover {
        color: var(--primary-color);
        text-shadow: 0 0 10px rgba(129, 140, 248, 0.5);
    }

    .alert {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #ef4444;
        padding: 15px;
        border-radius: 14px;
        margin-bottom: 25px;
        font-size: 0.9rem;
        text-align: center;
    }
    .dark-theme .alert {
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
    }
</style>

<div class="login-master-wrapper">
    
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>

    <div class="login-card" id="tilt-card-premium">
        
        <div class="login-header">
            <h2>BNCC Market</h2>
            <p>ยินดีต้อนรับกลับมาอีกครั้ง 👋</p>
        </div>

        <?php echo displayFlashMessage(); ?>

        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="field-label">ACCOUNT IDENTITY</label>
                <div class="input-wrapper">
                    <input type="text" name="login_input" class="form-control-custom" placeholder="รหัสนักศึกษา หรือ อีเมล" required>
                    <i class="fas fa-id-badge input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <label class="field-label" style="margin-bottom: 0;">SECURITY CODE</label>
                    <a href="forgot_password.php" class="forgot-link">ลืมรหัสผ่าน?</a>
                </div>
                <div class="input-wrapper">
                    <input type="password" name="password" id="login_pass" class="form-control-custom" placeholder="••••••••" required>
                    <i class="fas fa-shield-alt input-icon"></i>
                    <i class="fas fa-eye pass-toggle-icon" id="toggleLoginPass"></i>
                </div>
            </div>

            <div class="cf-turnstile" data-sitekey="0x4AAAAAACoR1z-q8h6byzeX" style="margin-bottom: 20px; display: flex; justify-content: center; position: relative; z-index: 9999;"></div>

            <button type="submit" class="btn-login-main">
                เข้าสู่ระบบ <i class="fas fa-arrow-right" style="font-size: 0.9rem;"></i>
            </button>
        </form>

        <div class="divider-custom">
            <span class="divider-text">CONNECT WITH</span>
        </div>

        <a href="<?php echo $googleAuthUrl; ?>" class="google-btn-custom">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
                <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24s8.955,20,20,20s20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
            </svg>
            ใช้งานผ่านบัญชีวิทยาลัย (@bncc.ac.th)
        </a>

        <div class="register-link-box">
            <p>
                ยังไม่มีบัญชีสมาชิกใช่ไหม? 
                <a href="register.php">สมัครใช้งานฟรีที่นี่</a>
            </p>
        </div>
    </div>
</div>

<script>
    const togglePass = document.querySelector('#toggleLoginPass');
    const passwordField = document.querySelector('#login_pass');

    togglePass.addEventListener('click', function () {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
        this.classList.toggle('fa-eye');
        
        // 🎯 บังคับให้ไอคอนเป็นสีขาวเสมอ ไม่ว่าจะเปิดตาหรือปิดตา
        this.style.color = '#000000'; 
        
        this.animate([
            { transform: 'translateY(-50%) scale(1)' },
            { transform: 'translateY(-50%) scale(1.3)' },
            { transform: 'translateY(-50%) scale(1)' }
        ], { duration: 250 });
    });

    const card = document.querySelector('#tilt-card-premium');
    const wrapper = document.querySelector('.login-master-wrapper');

    wrapper.addEventListener('mousemove', (e) => {
        const x = (window.innerWidth / 2 - e.pageX) / 40;
        const y = (window.innerHeight / 2 - e.pageY) / 40;
        card.style.transform = `perspective(1000px) rotateX(${y}deg) rotateY(${-x}deg) scale3d(1.02, 1.02, 1.02)`;
        card.style.transition = "transform 0.1s ease-out";
    });

    wrapper.addEventListener('mouseleave', () => {
        card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
        card.style.transition = "transform 0.8s ease-out";
    });

    window.onload = () => {
        setTimeout(() => {
            document.querySelector('input[name="login_input"]').focus();
        }, 500);
    };

    // 🎯 ถ้ามีการกดปุ่มเปลี่ยนธีมเว็บ ก็ยังบังคับให้ไอคอนตาเป็นสีขาวเหมือนเดิม
    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        setTimeout(() => {
            togglePass.style.color = '#000000';
        }, 100);
    });
</script>

<?php require_once '../includes/footer.php'; ?>