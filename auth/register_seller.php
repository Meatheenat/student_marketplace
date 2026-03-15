<?php
/**
 * =========================================================================
 * BNCC MARKETPLACE: SELLER REGISTRATION MODULE
 * =========================================================================
 */
require_once '../includes/functions.php';

// ---------------------------------------------------------
// 1. AUTHENTICATION & ROLE CHECK GUARD
// ---------------------------------------------------------
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนดำเนินการสมัครเป็นผู้ขาย";
    $_SESSION['flash_type'] = "warning";
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'seller') {
    $_SESSION['flash_message'] = "บัญชีของคุณได้รับการอนุมัติเป็นร้านค้าเรียบร้อยแล้ว ยินดีต้อนรับสู่ระบบผู้ขาย!";
    $_SESSION['flash_type'] = "info";
    header("Location: ../seller/dashboard.php");
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$user_fullname = $_SESSION['fullname'] ?? 'ผู้ใช้ระบบ';

// ---------------------------------------------------------
// 2. CHECK PENDING SHOP STATUS
// ---------------------------------------------------------
$stmt_check = $db->prepare("SELECT status, shop_name FROM shops WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt_check->execute([$user_id]);
$existing_shop = $stmt_check->fetch(PDO::FETCH_ASSOC);

$is_pending = false;
if ($existing_shop && $existing_shop['status'] === 'pending') {
    $is_pending = true;
}

$form_errors = [];

// ---------------------------------------------------------
// 3. TARGETED LINE NOTIFY FUNCTION (The Engine)
// ---------------------------------------------------------
function notifyAdminsViaLineTargeted($db, $message) {
    $line_bot_token = "YOUR_LINE_MESSAGING_API_TOKEN"; 
    
    if (empty($line_bot_token) || $line_bot_token === "YOUR_LINE_MESSAGING_API_TOKEN") {
        return;
    }

    try {
        $stmt = $db->query("SELECT line_user_id FROM users WHERE role IN ('admin', 'teacher') AND line_user_id IS NOT NULL AND line_user_id != ''");
        $admin_lines = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($admin_lines) > 0) {
            $url = 'https://api.line.me/v2/bot/message/multicast';
            $data = [
                'to' => $admin_lines,
                'messages' => [['type' => 'text', 'text' => $message]]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $line_bot_token
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    } catch (Exception $e) {
        error_log("LINE Notify Error: " . $e->getMessage());
    }
}

// ---------------------------------------------------------
// 4. FORM PROCESSING & NOTIFICATION LOGIC
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_pending) {
    $shop_name        = trim($_POST['shop_name']        ?? '');
    $shop_desc        = trim($_POST['shop_description'] ?? '');
    $contact_ig       = trim($_POST['contact_ig']       ?? '');
    $contact_line     = trim($_POST['contact_line']     ?? '');
    $contact_facebook = trim($_POST['contact_facebook'] ?? '');
    $contact_phone    = trim($_POST['contact_phone']    ?? '');
    $accept_terms     = isset($_POST['accept_terms']);

    // Validation
    if (empty($shop_name)) {
        $form_errors[] = "กรุณาระบุชื่อร้านค้าของคุณ";
    } elseif (mb_strlen($shop_name) > 50) {
        $form_errors[] = "ชื่อร้านค้าต้องมีความยาวไม่เกิน 50 ตัวอักษร";
    }

    if (empty($shop_desc)) {
        $form_errors[] = "กรุณาระบุรายละเอียดร้านค้าเพื่อเป็นข้อมูลให้ผู้ดูแลระบบตรวจสอบ";
    } elseif (mb_strlen($shop_desc) > 500) {
        $form_errors[] = "รายละเอียดร้านค้าไม่ควรเกิน 500 ตัวอักษร";
    }

    if (!$accept_terms) {
        $form_errors[] = "คุณต้องกดยอมรับเงื่อนไขการให้บริการก่อนส่งคำขอ";
    }

    if (empty($form_errors)) {
        try {
            $db->beginTransaction();
            
            // INSERT พร้อม contact fields ทั้ง 4
            $stmt = $db->prepare("
                INSERT INTO shops 
                    (user_id, shop_name, description, contact_ig, contact_line, contact_facebook, contact_phone, status, created_at) 
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $user_id,
                $shop_name,
                $shop_desc,
                $contact_ig       ?: null,
                $contact_line     ?: null,
                $contact_facebook ?: null,
                $contact_phone    ?: null,
            ]);
            
            // Internal web notification
            $admin_stmt = $db->query("SELECT id FROM users WHERE role IN ('admin', 'teacher')");
            $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($admins && count($admins) > 0) {
                $notif_msg  = "🔥 คำร้องขอเปิดร้านใหม่!\nผู้สมัคร: " . $user_fullname . "\nชื่อร้าน: " . $shop_name;
                $notif_link = "admin/approve_shop.php"; 
                $notif_insert = $db->prepare("INSERT INTO notifications (user_id, type, message, link, is_read, created_at) VALUES (?, 'system', ?, ?, 0, NOW())");
                foreach ($admins as $adm) {
                    $notif_insert->execute([$adm['id'], $notif_msg, $notif_link]);
                }
            }

            // LINE notification
            $line_msg = "🔔 แจ้งเตือนจากระบบ BNCC Market\n\n"
                      . "มีนักศึกษาส่งคำร้องขอเปิดร้านค้าใหม่\n"
                      . "👤 ผู้สมัคร: " . $user_fullname . "\n"
                      . "🏪 ชื่อร้าน: " . $shop_name . "\n"
                      . "📝 รายละเอียด: " . mb_strimwidth($shop_desc, 0, 50, "...") . "\n\n"
                      . "👉 โปรดตรวจสอบและอนุมัติในระบบหลังบ้าน";
            notifyAdminsViaLineTargeted($db, $line_msg);

            $db->commit();
            
            $_SESSION['flash_message'] = "ส่งคำขอเปิดร้านค้าสำเร็จ! ระบบได้ส่งแจ้งเตือนไปหาผู้ดูแลระบบแล้ว กรุณารอ 1-2 วันทำการ";
            $_SESSION['flash_type'] = "success";
            header("Location: register_seller.php");
            exit();
            
        } catch (PDOException $e) {
            $db->rollBack();
            $form_errors[] = "เกิดข้อผิดพลาดในการบันทึกข้อมูลระดับระบบ: " . $e->getMessage();
        }
    }
}

// ---------------------------------------------------------
// 5. RENDER FRONTEND UI
// ---------------------------------------------------------
$pageTitle = "ลงทะเบียนเปิดร้านค้า (Seller Program) - BNCC Market";
require_once '../includes/header.php';
?>

<style>
    :root {
        --seller-hue-primary: 238;
        --seller-color-primary: hsl(var(--seller-hue-primary), 80%, 60%);
        --seller-color-primary-dark: hsl(var(--seller-hue-primary), 80%, 50%);
        --seller-color-primary-light: hsl(var(--seller-hue-primary), 80%, 95%);
        --seller-color-primary-soft: rgba(79, 70, 229, 0.08);
        
        --seller-color-success: #10b981;
        --seller-color-danger: #ef4444;
        --seller-color-warning: #f59e0b;
        
        --seller-surface-bg: #ffffff;
        --seller-surface-alt: #f8fafc;
        --seller-border-color: #e2e8f0;
        
        --seller-text-main: #0f172a;
        --seller-text-muted: #64748b;
        --seller-text-light: #94a3b8;
        
        --seller-radius-sm: 8px;
        --seller-radius-md: 16px;
        --seller-radius-lg: 24px;
        --seller-radius-pill: 9999px;
        
        --seller-shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        --seller-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        --seller-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
        --seller-shadow-glow: 0 0 20px rgba(79, 70, 229, 0.2);
        
        --seller-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --seller-bounce: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .dark-theme {
        --seller-surface-bg: #111827;
        --seller-surface-alt: #0b0f19;
        --seller-border-color: #1f2937;
        --seller-text-main: #f8fafc;
        --seller-text-muted: #94a3b8;
        --seller-text-light: #475569;
        --seller-color-primary-light: rgba(79, 70, 229, 0.15);
        --seller-color-primary-soft: rgba(79, 70, 229, 0.1);
        --seller-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
    }

    .onboarding-container {
        max-width: 1100px;
        margin: 60px auto;
        padding: 0 20px;
        min-height: calc(100vh - 250px);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: scaleFadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .onboarding-layout {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        background: var(--seller-surface-bg);
        border-radius: var(--seller-radius-lg);
        border: 1px solid var(--seller-border-color);
        box-shadow: var(--seller-shadow-lg);
        overflow: hidden;
        width: 100%;
        position: relative;
    }

    .onboarding-presentation {
        padding: 60px 45px;
        background: linear-gradient(145deg, var(--seller-color-primary-light) 0%, var(--seller-surface-alt) 100%);
        border-right: 1px solid var(--seller-border-color);
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .onboarding-presentation::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%;
        width: 200%; height: 200%;
        background: radial-gradient(circle, var(--seller-color-primary-soft) 10%, transparent 10%);
        background-size: 20px 20px;
        opacity: 0.3;
        z-index: 0;
        animation: slowDrift 60s linear infinite;
        pointer-events: none;
    }

    .presentation-content-wrapper { position: relative; z-index: 1; }

    .presentation-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        background: linear-gradient(135deg, var(--seller-color-primary), var(--seller-color-primary-dark));
        color: #ffffff;
        border-radius: var(--seller-radius-pill);
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 25px;
        width: fit-content;
        box-shadow: var(--seller-shadow-md);
        animation: floatUpDown 3s ease-in-out infinite;
    }

    .presentation-headline {
        font-size: 2.4rem;
        font-weight: 900;
        color: var(--seller-text-main);
        line-height: 1.25;
        margin-bottom: 18px;
        letter-spacing: -0.5px;
    }

    .presentation-subheadline {
        font-size: 1.1rem;
        color: var(--seller-text-muted);
        line-height: 1.6;
        margin-bottom: 45px;
        font-weight: 500;
    }

    .feature-list-group { display: flex; flex-direction: column; gap: 28px; }

    .feature-list-item {
        display: flex;
        align-items: flex-start;
        gap: 18px;
        transition: var(--seller-transition);
        padding: 10px;
        border-radius: var(--seller-radius-md);
    }

    .feature-list-item:hover {
        background: var(--seller-color-primary-soft);
        transform: translateX(10px);
    }

    .feature-icon-wrapper {
        width: 52px; height: 52px;
        border-radius: var(--seller-radius-md);
        background: var(--seller-surface-bg);
        border: 2px solid var(--seller-color-primary-light);
        display: flex;
        justify-content: center;
        align-items: center;
        color: var(--seller-color-primary);
        font-size: 1.4rem;
        flex-shrink: 0;
        box-shadow: var(--seller-shadow-sm);
        transition: var(--seller-bounce);
    }

    .feature-list-item:hover .feature-icon-wrapper {
        transform: scale(1.1) rotate(5deg);
        background: var(--seller-color-primary);
        color: #ffffff;
        border-color: var(--seller-color-primary);
    }

    .feature-text-content h4 { font-size: 1.1rem; font-weight: 800; color: var(--seller-text-main); margin: 0 0 6px 0; }
    .feature-text-content p  { font-size: 0.95rem; color: var(--seller-text-muted); margin: 0; line-height: 1.5; }

    .onboarding-interaction {
        padding: 50px 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: var(--seller-surface-bg);
        overflow-y: auto;
        max-height: 100vh;
    }

    .interaction-header { margin-bottom: 28px; }
    .interaction-header h2 { font-weight: 900; color: var(--seller-text-main); margin-bottom: 8px; font-size: 1.7rem; }
    .interaction-header p  { color: var(--seller-text-muted); font-size: 0.95rem; margin: 0; line-height: 1.5; }

    .validation-alert-box {
        background: rgba(239, 68, 68, 0.08);
        border-left: 4px solid var(--seller-color-danger);
        padding: 16px 20px;
        border-radius: 0 var(--seller-radius-sm) var(--seller-radius-sm) 0;
        margin-bottom: 22px;
        animation: shakeError 0.5s cubic-bezier(.36,.07,.19,.97) both;
    }

    .validation-alert-list {
        margin: 0; padding-left: 20px;
        color: var(--seller-color-danger);
        font-size: 0.95rem; font-weight: 600; line-height: 1.6;
    }

    .input-field-group { margin-bottom: 20px; position: relative; }

    .input-field-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--seller-text-main);
        margin-bottom: 8px;
    }

    .input-required-mark { color: var(--seller-color-danger); margin-left: 4px; }

    .input-character-count {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--seller-text-light);
        transition: color 0.3s;
        background: var(--seller-surface-alt);
        padding: 2px 8px;
        border-radius: var(--seller-radius-pill);
    }

    .input-character-count.limit-reached { color: #ffffff; background: var(--seller-color-danger); }

    .form-input-element {
        width: 100%;
        padding: 13px 18px;
        border-radius: var(--seller-radius-md);
        border: 2px solid var(--seller-border-color);
        background: var(--seller-surface-alt);
        color: var(--seller-text-main);
        font-size: 1rem;
        font-family: inherit;
        transition: var(--seller-transition);
        outline: none;
    }

    .form-input-element:focus {
        border-color: var(--seller-color-primary);
        box-shadow: 0 0 0 4px var(--seller-color-primary-light);
        background: var(--seller-surface-bg);
    }

    .form-input-element::placeholder { color: var(--seller-text-light); font-weight: 400; }
    .form-input-element.is-valid { border-color: var(--seller-color-success); background: rgba(16, 185, 129, 0.02); }

    /* ── Contact Fields Section ── */
    .contact-section-label {
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--seller-text-light);
        margin-bottom: 14px;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .contact-section-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--seller-border-color);
    }

    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 20px;
    }

    @media (max-width: 576px) {
        .contact-grid { grid-template-columns: 1fr; }
    }

    .contact-input-wrap { position: relative; }

    .contact-input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1rem;
        pointer-events: none;
        z-index: 1;
    }

    .contact-input-icon.ig       { color: #e1306c; }
    .contact-input-icon.line     { color: #06c755; }
    .contact-input-icon.facebook { color: #1877f2; }
    .contact-input-icon.phone    { color: var(--seller-color-primary); }

    .form-input-element.with-icon { padding-left: 42px; }

    .terms-agreement-wrapper {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 28px;
        padding: 18px;
        background: var(--seller-color-primary-light);
        border-radius: var(--seller-radius-md);
        border: 1px dashed rgba(79, 70, 229, 0.3);
        transition: var(--seller-transition);
    }

    .terms-agreement-wrapper:hover { background: rgba(79, 70, 229, 0.1); border-style: solid; }

    .custom-check-element {
        width: 26px; height: 26px;
        border-radius: 6px;
        border: 2px solid var(--seller-color-primary);
        appearance: none;
        cursor: pointer;
        position: relative;
        transition: var(--seller-bounce);
        margin-top: 2px;
        flex-shrink: 0;
        background: var(--seller-surface-bg);
    }

    .custom-check-element:checked { background: var(--seller-color-primary); border-color: var(--seller-color-primary); transform: scale(1.05); }

    .custom-check-element:checked::after {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        color: #ffffff;
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        font-size: 0.9rem;
    }

    .custom-check-label { font-size: 0.95rem; color: var(--seller-text-muted); line-height: 1.6; cursor: pointer; user-select: none; }
    .custom-check-label strong { color: var(--seller-color-primary); font-weight: 800; text-decoration: underline; text-decoration-color: transparent; transition: 0.3s; }
    .custom-check-label strong:hover { text-decoration-color: var(--seller-color-primary); }

    .action-submit-btn {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, var(--seller-color-primary), var(--seller-color-primary-dark));
        color: #ffffff;
        border: none;
        border-radius: var(--seller-radius-md);
        font-size: 1.1rem;
        font-weight: 800;
        cursor: pointer;
        transition: var(--seller-bounce);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
        box-shadow: var(--seller-shadow-md);
        position: relative;
        overflow: hidden;
    }

    .action-submit-btn::before {
        content: '';
        position: absolute;
        top: 0; left: -100%;
        width: 100%; height: 100%;
        background: linear-gradient(to right, transparent, rgba(255,255,255,0.3), transparent);
        transition: 0.5s;
    }

    .action-submit-btn:not(:disabled):hover { transform: translateY(-4px); box-shadow: var(--seller-shadow-glow); }
    .action-submit-btn:not(:disabled):hover::before { left: 100%; }
    .action-submit-btn:not(:disabled):active { transform: translateY(0) scale(0.98); }
    .action-submit-btn:disabled {
        background: var(--seller-surface-alt);
        color: var(--seller-text-light);
        border: 2px solid var(--seller-border-color);
        cursor: not-allowed;
        box-shadow: none;
    }

    /* PENDING STATE */
    .pending-review-state { text-align: center; padding: 40px 20px; animation: scaleFadeIn 0.5s ease; }

    .pending-anim-icon {
        width: 130px; height: 130px;
        background: rgba(245, 158, 11, 0.1);
        color: var(--seller-color-warning);
        border-radius: 50%;
        display: flex; justify-content: center; align-items: center;
        font-size: 4.5rem;
        margin: 0 auto 35px auto;
        position: relative;
    }

    .pending-anim-icon::after {
        content: '';
        position: absolute;
        top: -10px; left: -10px; right: -10px; bottom: -10px;
        border: 4px dashed var(--seller-color-warning);
        border-radius: 50%;
        animation: spinnerBorder 12s linear infinite;
        opacity: 0.4;
    }

    .pending-review-title  { font-size: 2.2rem; font-weight: 900; color: var(--seller-text-main); margin-bottom: 18px; letter-spacing: -0.5px; }
    .pending-review-desc   { color: var(--seller-text-muted); font-size: 1.1rem; line-height: 1.6; max-width: 400px; margin: 0 auto 35px auto; }

    .pending-shop-preview {
        background: var(--seller-surface-alt);
        border: 1px solid var(--seller-border-color);
        border-radius: var(--seller-radius-md);
        padding: 25px;
        text-align: left;
        margin-bottom: 35px;
        display: flex; align-items: center; gap: 20px;
        box-shadow: var(--seller-shadow-sm);
    }

    .preview-shop-icon {
        width: 60px; height: 60px;
        background: var(--seller-surface-bg);
        border-radius: var(--seller-radius-sm);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; color: var(--seller-color-primary);
        border: 1px solid var(--seller-border-color);
    }

    .preview-meta-label { font-size: 0.85rem; color: var(--seller-text-light); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
    .preview-meta-value { font-size: 1.4rem; font-weight: 900; color: var(--seller-text-main); }

    .btn-return-profile {
        display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        padding: 16px 30px;
        border-radius: var(--seller-radius-md);
        font-weight: 800; font-size: 1.05rem;
        background: var(--seller-surface-alt);
        color: var(--seller-text-main);
        border: 2px solid var(--seller-border-color);
        text-decoration: none;
        transition: var(--seller-transition);
        width: 100%;
    }
    .btn-return-profile:hover { background: var(--seller-border-color); color: var(--seller-text-main); }

    /* KEYFRAMES */
    @keyframes scaleFadeIn    { 0%   { opacity: 0; transform: scale(0.96) translateY(20px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
    @keyframes slowDrift      { 0%   { transform: rotate(0deg) translate(0, 0); } 100% { transform: rotate(360deg) translate(-50px, -50px); } }
    @keyframes floatUpDown    { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    @keyframes spinnerBorder  { 100% { transform: rotate(360deg); } }
    @keyframes shakeError     { 10%, 90% { transform: translate3d(-1px, 0, 0); } 20%, 80% { transform: translate3d(2px, 0, 0); } 30%, 50%, 70% { transform: translate3d(-4px, 0, 0); } 40%, 60% { transform: translate3d(4px, 0, 0); } }

    /* RESPONSIVE */
    @media (max-width: 992px) {
        .onboarding-layout { grid-template-columns: 1fr; }
        .onboarding-presentation { border-right: none; border-bottom: 1px solid var(--seller-border-color); padding: 50px 30px; text-align: center; }
        .presentation-content-wrapper { display: flex; flex-direction: column; align-items: center; }
        .feature-list-item { text-align: left; }
        .onboarding-interaction { padding: 40px 30px; max-height: none; }
    }
    @media (max-width: 576px) {
        .onboarding-presentation, .onboarding-interaction { padding: 35px 20px; }
        .presentation-headline { font-size: 2rem; }
        .feature-list-item { flex-direction: column; align-items: center; text-align: center; }
        .pending-review-title { font-size: 1.8rem; }
    }
</style>

<div class="onboarding-container">
    <div class="onboarding-layout">
        
        <!-- Left: Presentation -->
        <div class="onboarding-presentation">
            <div class="presentation-content-wrapper">
                <div class="presentation-badge">
                    <i class="fas fa-rocket"></i> Seller Program
                </div>
                <h1 class="presentation-headline">เริ่มต้นธุรกิจบนพื้นที่ของวิทยาลัย</h1>
                <p class="presentation-subheadline">
                    เปลี่ยนความสามารถ สิ่งของมือสอง หรือไอเดียสร้างสรรค์ของคุณให้เป็นรายได้เสริมที่มั่นคงผ่านแพลตฟอร์ม BNCC Market อย่างเป็นทางการ
                </p>
                
                <div class="feature-list-group">
                    <div class="feature-list-item">
                        <div class="feature-icon-wrapper"><i class="fas fa-bullseye"></i></div>
                        <div class="feature-text-content">
                            <h4>เข้าถึงกลุ่มเป้าหมายโดยตรง</h4>
                            <p>สินค้าของคุณจะแสดงต่อนักศึกษาและบุคลากรภายในวิทยาลัยนับพันคนโดยอัตโนมัติ</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-icon-wrapper"><i class="fas fa-shield-alt"></i></div>
                        <div class="feature-text-content">
                            <h4>ปลอดภัยและตรวจสอบได้</h4>
                            <p>ระบบคัดกรองข้อมูลร้านค้าเพื่อสร้างความมั่นใจสูงสุดให้กับผู้ซื้อทุกคนในชุมชน</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-icon-wrapper"><i class="fas fa-chart-line"></i></div>
                        <div class="feature-text-content">
                            <h4>จัดการร้านค้าครบวงจรฟรี</h4>
                            <p>มีระบบจัดการหลังบ้าน สต๊อกสินค้า และดูสถิติยอดขายฟรี 100% ไม่มีหักค่าธรรมเนียม</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Form -->
        <div class="onboarding-interaction">
            
            <?php if ($is_pending): ?>
                
                <div class="pending-review-state">
                    <div class="pending-anim-icon"><i class="fas fa-hourglass-half"></i></div>
                    <h2 class="pending-review-title">คำขออยู่ระหว่างพิจารณา</h2>
                    <p class="pending-review-desc">
                        ระบบได้รับข้อมูลการขอเปิดร้านค้าของคุณเรียบร้อยแล้ว ระบบได้แจ้งเตือนผู้ดูแลให้ทราบแล้ว โปรดรอการพิจารณาประมาณ 1-2 วันทำการ
                    </p>
                    <div class="pending-shop-preview">
                        <div class="preview-shop-icon"><i class="fas fa-store"></i></div>
                        <div>
                            <div class="preview-meta-label">ชื่อร้านค้าที่เสนอ</div>
                            <div class="preview-meta-value"><?= htmlspecialchars($existing_shop['shop_name']) ?></div>
                        </div>
                    </div>
                    <a href="../pages/profile.php" class="btn-return-profile">
                        <i class="fas fa-arrow-left"></i> กลับไปหน้าโปรไฟล์
                    </a>
                </div>

            <?php else: ?>
                
                <div class="interaction-header">
                    <h2>ข้อมูลร้านค้าเบื้องต้น</h2>
                    <p>กรอกรายละเอียดเพื่อส่งให้ผู้ดูแลระบบพิจารณาอนุมัติเปิดร้านในระบบ</p>
                </div>

                <?php if (!empty($form_errors)): ?>
                    <div class="validation-alert-box">
                        <ul class="validation-alert-list">
                            <?php foreach ($form_errors as $error_msg): ?>
                                <li><?= htmlspecialchars($error_msg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="register_seller.php" method="POST" id="sellerRegistrationForm">
                    
                    <!-- ชื่อร้านค้า -->
                    <div class="input-field-group">
                        <div class="input-field-label">
                            <label for="inputShopName">ชื่อร้านค้า <span class="input-required-mark">*</span></label>
                            <span class="input-character-count" id="counterShopName">0 / 50</span>
                        </div>
                        <input type="text" name="shop_name" id="inputShopName" class="form-input-element"
                               placeholder="เช่น BNCC Stationery"
                               value="<?= htmlspecialchars($_POST['shop_name'] ?? '') ?>"
                               maxlength="50" autocomplete="off" autofocus>
                    </div>

                    <!-- รายละเอียดร้าน -->
                    <div class="input-field-group">
                        <div class="input-field-label">
                            <label for="inputShopDesc">รายละเอียดและจุดเด่นของร้าน <span class="input-required-mark">*</span></label>
                            <span class="input-character-count" id="counterShopDesc">0 / 500</span>
                        </div>
                        <textarea name="shop_description" id="inputShopDesc" class="form-input-element" rows="3"
                                  placeholder="อธิบายสั้นๆ ว่าร้านของคุณขายอะไร มีสินค้าประเภทไหน..."
                                  maxlength="500"><?= htmlspecialchars($_POST['shop_description'] ?? '') ?></textarea>
                    </div>

                    <!-- ── ช่องทางติดต่อ ── -->
                    <div class="contact-section-label">
                        <i class="fas fa-address-card"></i> ช่องทางติดต่อร้าน (ไม่บังคับ)
                    </div>

                    <div class="contact-grid">

                        <!-- Instagram -->
                        <div class="input-field-group" style="margin-bottom:0;">
                            <div class="input-field-label">
                                <label for="inputContactIg"><i class="fab fa-instagram" style="color:#e1306c;"></i> Instagram</label>
                            </div>
                            <div class="contact-input-wrap">
                                <i class="fab fa-instagram contact-input-icon ig"></i>
                                <input type="text" name="contact_ig" id="inputContactIg"
                                       class="form-input-element with-icon"
                                       placeholder="username (ไม่ใส่ @)"
                                       value="<?= htmlspecialchars($_POST['contact_ig'] ?? '') ?>"
                                       maxlength="100">
                            </div>
                        </div>

                        <!-- LINE -->
                        <div class="input-field-group" style="margin-bottom:0;">
                            <div class="input-field-label">
                                <label for="inputContactLine"><i class="fab fa-line" style="color:#06c755;"></i> LINE ID</label>
                            </div>
                            <div class="contact-input-wrap">
                                <i class="fab fa-line contact-input-icon line"></i>
                                <input type="text" name="contact_line" id="inputContactLine"
                                       class="form-input-element with-icon"
                                       placeholder="LINE ID ของคุณ"
                                       value="<?= htmlspecialchars($_POST['contact_line'] ?? '') ?>"
                                       maxlength="100">
                            </div>
                        </div>

                        <!-- Facebook -->
                        <div class="input-field-group" style="margin-bottom:0;">
                            <div class="input-field-label">
                                <label for="inputContactFb"><i class="fab fa-facebook" style="color:#1877f2;"></i> Facebook</label>
                            </div>
                            <div class="contact-input-wrap">
                                <i class="fab fa-facebook contact-input-icon facebook"></i>
                                <input type="text" name="contact_facebook" id="inputContactFb"
                                       class="form-input-element with-icon"
                                       placeholder="ชื่อ Facebook หรือ URL"
                                       value="<?= htmlspecialchars($_POST['contact_facebook'] ?? '') ?>"
                                       maxlength="100">
                            </div>
                        </div>

                        <!-- เบอร์โทร -->
                        <div class="input-field-group" style="margin-bottom:0;">
                            <div class="input-field-label">
                                <label for="inputContactPhone"><i class="fas fa-phone" style="color:var(--seller-color-primary);"></i> เบอร์โทร</label>
                            </div>
                            <div class="contact-input-wrap">
                                <i class="fas fa-phone contact-input-icon phone"></i>
                                <input type="tel" name="contact_phone" id="inputContactPhone"
                                       class="form-input-element with-icon"
                                       placeholder="0XX-XXX-XXXX"
                                       value="<?= htmlspecialchars($_POST['contact_phone'] ?? '') ?>"
                                       maxlength="20">
                            </div>
                        </div>

                    </div><!-- /.contact-grid -->

                    <!-- ยอมรับเงื่อนไข -->
                    <div class="terms-agreement-wrapper">
                        <input type="checkbox" name="accept_terms" id="inputAcceptTerms" class="custom-check-element"
                               <?= isset($_POST['accept_terms']) ? 'checked' : '' ?>>
                        <label for="inputAcceptTerms" class="custom-check-label">
                            ข้าพเจ้ายืนยันว่าข้อมูลถูกต้อง และยอมรับ <strong>กฎระเบียบการซื้อขาย</strong> ภายในวิทยาลัย หากมีการฉ้อโกงหรือละเมิดกฎ ยินยอมให้ระงับบัญชีถาวร
                        </label>
                    </div>

                    <button type="submit" class="action-submit-btn" id="btnSubmitRequest" disabled>
                        <span>ส่งคำขอเปิดร้านค้า</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    
                </form>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const elShopName  = document.getElementById('inputShopName');
    const elShopDesc  = document.getElementById('inputShopDesc');
    const elTerms     = document.getElementById('inputAcceptTerms');
    const elBtnSubmit = document.getElementById('btnSubmitRequest');
    const cntName     = document.getElementById('counterShopName');
    const cntDesc     = document.getElementById('counterShopDesc');
    const regForm     = document.getElementById('sellerRegistrationForm');

    if (!regForm) return;

    function evaluateFormValidity() {
        const valName = elShopName.value.trim();
        const valDesc = elShopDesc.value.trim();
        
        const isNameValid    = valName.length >= 1 && valName.length <= 50;
        const isDescValid    = valDesc.length >= 1 && valDesc.length <= 500;
        const isTermsChecked = elTerms.checked;

        isNameValid ? elShopName.classList.add('is-valid') : elShopName.classList.remove('is-valid');
        isDescValid ? elShopDesc.classList.add('is-valid') : elShopDesc.classList.remove('is-valid');

        if (isNameValid && isDescValid && isTermsChecked) {
            elBtnSubmit.removeAttribute('disabled');
        } else {
            elBtnSubmit.setAttribute('disabled', 'true');
        }
    }

    function updateCharCounters() {
        const lenName = elShopName.value.length;
        const lenDesc = elShopDesc.value.length;
        cntName.textContent = `${lenName} / 50`;
        cntDesc.textContent = `${lenDesc} / 500`;
        cntName.classList.toggle('limit-reached', lenName >= 50);
        cntDesc.classList.toggle('limit-reached', lenDesc >= 500);
    }

    const trackingEvents = ['input', 'keyup', 'change', 'paste', 'cut'];
    
    trackingEvents.forEach(evt => {
        elShopName?.addEventListener(evt, () => { updateCharCounters(); evaluateFormValidity(); });
        elShopDesc?.addEventListener(evt, () => { updateCharCounters(); evaluateFormValidity(); });
    });

    elTerms?.addEventListener('change', evaluateFormValidity);

    regForm.addEventListener('submit', function() {
        if (!elBtnSubmit.disabled) {
            elBtnSubmit.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i><span>กำลังส่งข้อมูลและแจ้งแอดมิน...</span>';
            elBtnSubmit.style.opacity = '0.9';
            elBtnSubmit.style.cursor  = 'wait';
        }
    });

    updateCharCounters();
    evaluateFormValidity();
});
</script>

<?php require_once '../includes/footer.php'; ?>