<?php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนดำเนินการสมัครเป็นผู้ขาย";
    $_SESSION['flash_type'] = "warning";
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['role'] === 'seller') {
    $_SESSION['flash_message'] = "บัญชีของคุณได้รับการอนุมัติเป็นร้านค้าเรียบร้อยแล้ว";
    $_SESSION['flash_type'] = "info";
    header("Location: ../seller/dashboard.php");
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];

$stmt_check = $db->prepare("SELECT status, shop_name FROM shops WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt_check->execute([$user_id]);
$existing_shop = $stmt_check->fetch(PDO::FETCH_ASSOC);

$is_pending = false;
if ($existing_shop && $existing_shop['status'] === 'pending') {
    $is_pending = true;
}

$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_pending) {
    $shop_name = isset($_POST['shop_name']) ? trim($_POST['shop_name']) : '';
    $shop_desc = isset($_POST['shop_description']) ? trim($_POST['shop_description']) : '';
    $accept_terms = isset($_POST['accept_terms']) ? true : false;

    if (empty($shop_name)) {
        $form_errors[] = "กรุณาระบุชื่อร้านค้าของคุณ";
    } elseif (mb_strlen($shop_name) < 3 || mb_strlen($shop_name) > 50) {
        $form_errors[] = "ชื่อร้านค้าต้องมีความยาวระหว่าง 3 ถึง 50 ตัวอักษร";
    }

    if (empty($shop_desc)) {
        $form_errors[] = "กรุณาระบุรายละเอียดร้านค้าเพื่อเป็นข้อมูลให้ผู้ซื้อ";
    } elseif (mb_strlen($shop_desc) < 10) {
        $form_errors[] = "รายละเอียดร้านค้าควรมีความยาวอย่างน้อย 10 ตัวอักษร";
    }

    if (!$accept_terms) {
        $form_errors[] = "คุณต้องกดยอมรับเงื่อนไขการให้บริการก่อนส่งคำขอ";
    }

    if (empty($form_errors)) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO shops (user_id, shop_name, shop_description, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $shop_name, $shop_desc]);
            $db->commit();
            
            $_SESSION['flash_message'] = "ส่งคำขอเปิดร้านค้าสำเร็จ กรุณารอแอดมินตรวจสอบข้อมูล 1-2 วันทำการ";
            $_SESSION['flash_type'] = "success";
            header("Location: register_seller.php");
            exit();
        } catch (PDOException $e) {
            $db->rollBack();
            $form_errors[] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
        }
    }
}

$pageTitle = "ลงทะเบียนเปิดร้านค้า - BNCC Market";
require_once '../includes/header.php';
?>

<style>
    :root {
        --seller-hue-primary: 238;
        --seller-color-primary: hsl(var(--seller-hue-primary), 80%, 60%);
        --seller-color-primary-dark: hsl(var(--seller-hue-primary), 80%, 50%);
        --seller-color-primary-light: hsl(var(--seller-hue-primary), 80%, 95%);
        
        --seller-color-success: #10b981;
        --seller-color-danger: #ef4444;
        --seller-color-warning: #f59e0b;
        
        --seller-surface-bg: #ffffff;
        --seller-surface-alt: #f8fafc;
        --seller-border-color: #e2e8f0;
        
        --seller-text-main: #0f172a;
        --seller-text-muted: #64748b;
        
        --seller-radius-sm: 8px;
        --seller-radius-md: 16px;
        --seller-radius-lg: 24px;
        
        --seller-shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        --seller-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        --seller-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
        
        --seller-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dark-theme {
        --seller-surface-bg: #111827;
        --seller-surface-alt: #0b0f19;
        --seller-border-color: #1f2937;
        --seller-text-main: #f8fafc;
        --seller-text-muted: #94a3b8;
        --seller-color-primary-light: rgba(79, 70, 229, 0.15);
        --seller-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
    }

    .onboarding-container {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px;
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: scaleFadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
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
    }

    .onboarding-presentation {
        padding: 50px 40px;
        background: linear-gradient(135deg, var(--seller-color-primary-light) 0%, var(--seller-surface-alt) 100%);
        border-right: 1px solid var(--seller-border-color);
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .presentation-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: var(--seller-color-primary);
        color: #ffffff;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 24px;
        width: fit-content;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
    }

    .presentation-headline {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--seller-text-main);
        line-height: 1.2;
        margin-bottom: 16px;
        letter-spacing: -0.5px;
    }

    .presentation-subheadline {
        font-size: 1.05rem;
        color: var(--seller-text-muted);
        line-height: 1.6;
        margin-bottom: 40px;
    }

    .feature-list-group {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .feature-list-item {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .feature-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: var(--seller-radius-sm);
        background: var(--seller-surface-bg);
        border: 1px solid var(--seller-border-color);
        display: flex;
        justify-content: center;
        align-items: center;
        color: var(--seller-color-primary);
        font-size: 1.25rem;
        flex-shrink: 0;
        box-shadow: var(--seller-shadow-sm);
        transition: var(--seller-transition);
    }

    .feature-list-item:hover .feature-icon-wrapper {
        transform: scale(1.1) rotate(5deg);
        background: var(--seller-color-primary);
        color: #ffffff;
        border-color: var(--seller-color-primary);
    }

    .feature-text-content h4 {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--seller-text-main);
        margin: 0 0 4px 0;
    }

    .feature-text-content p {
        font-size: 0.9rem;
        color: var(--seller-text-muted);
        margin: 0;
        line-height: 1.5;
    }

    .onboarding-interaction {
        padding: 50px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: var(--seller-surface-bg);
    }

    .interaction-header {
        margin-bottom: 32px;
    }

    .interaction-header h2 {
        font-weight: 900;
        color: var(--seller-text-main);
        margin-bottom: 8px;
        font-size: 1.8rem;
    }

    .interaction-header p {
        color: var(--seller-text-muted);
        font-size: 0.95rem;
        margin: 0;
    }

    .validation-alert-box {
        background: rgba(239, 68, 68, 0.1);
        border-left: 4px solid var(--seller-color-danger);
        padding: 16px 20px;
        border-radius: 0 var(--seller-radius-sm) var(--seller-radius-sm) 0;
        margin-bottom: 24px;
    }

    .validation-alert-list {
        margin: 0;
        padding-left: 20px;
        color: var(--seller-color-danger);
        font-size: 0.9rem;
        font-weight: 600;
    }

    .input-field-group {
        margin-bottom: 24px;
        position: relative;
    }

    .input-field-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--seller-text-main);
        margin-bottom: 8px;
    }

    .input-required-mark {
        color: var(--seller-color-danger);
    }

    .input-character-count {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--seller-text-muted);
        transition: color 0.3s;
    }

    .input-character-count.limit-reached {
        color: var(--seller-color-danger);
    }

    .form-input-element {
        width: 100%;
        padding: 14px 18px;
        border-radius: var(--seller-radius-md);
        border: 2px solid var(--seller-border-color);
        background: var(--seller-surface-alt);
        color: var(--seller-text-main);
        font-size: 1rem;
        font-family: inherit;
        transition: var(--seller-transition);
    }

    .form-input-element:focus {
        border-color: var(--seller-color-primary);
        box-shadow: 0 0 0 4px var(--seller-color-primary-light);
        background: var(--seller-surface-bg);
    }

    .form-input-element::placeholder {
        color: var(--seller-text-muted);
        opacity: 0.5;
    }

    .form-input-element.is-valid {
        border-color: var(--seller-color-success);
    }

    .terms-agreement-wrapper {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 32px;
        padding: 16px;
        background: var(--seller-color-primary-light);
        border-radius: var(--seller-radius-md);
        border: 1px dashed rgba(79, 70, 229, 0.3);
        transition: var(--seller-transition);
    }

    .terms-agreement-wrapper:hover {
        background: rgba(79, 70, 229, 0.1);
    }

    .custom-check-element {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        border: 2px solid var(--seller-color-primary);
        appearance: none;
        cursor: pointer;
        position: relative;
        transition: var(--seller-transition);
        margin-top: 2px;
        flex-shrink: 0;
        background: var(--seller-surface-bg);
    }

    .custom-check-element:checked {
        background: var(--seller-color-primary);
        border-color: var(--seller-color-primary);
        animation: checkBounce 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .custom-check-element:checked::after {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        color: #ffffff;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 0.8rem;
    }

    .custom-check-label {
        font-size: 0.9rem;
        color: var(--seller-text-muted);
        line-height: 1.6;
        cursor: pointer;
        user-select: none;
    }

    .custom-check-label strong {
        color: var(--seller-color-primary);
        font-weight: 800;
    }

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
        transition: var(--seller-transition);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
    }

    .action-submit-btn:not(:disabled):hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(79, 70, 229, 0.4);
    }

    .action-submit-btn:not(:disabled):active {
        transform: translateY(0);
    }

    .action-submit-btn:disabled {
        background: var(--seller-surface-alt);
        color: var(--seller-text-muted);
        border: 2px solid var(--seller-border-color);
        cursor: not-allowed;
        box-shadow: none;
        opacity: 0.7;
    }

    /* Pending State UI */
    .pending-review-state {
        text-align: center;
        padding: 40px 20px;
        animation: scaleFadeIn 0.5s ease;
    }

    .pending-anim-icon {
        width: 120px;
        height: 120px;
        background: rgba(245, 158, 11, 0.1);
        color: var(--seller-color-warning);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 4rem;
        margin: 0 auto 30px auto;
        position: relative;
    }

    .pending-anim-icon::after {
        content: '';
        position: absolute;
        top: -10px; left: -10px; right: -10px; bottom: -10px;
        border: 3px dashed var(--seller-color-warning);
        border-radius: 50%;
        animation: spinnerBorder 15s linear infinite;
        opacity: 0.4;
    }

    .pending-review-title {
        font-size: 2rem;
        font-weight: 900;
        color: var(--seller-text-main);
        margin-bottom: 16px;
    }

    .pending-review-desc {
        color: var(--seller-text-muted);
        font-size: 1.05rem;
        line-height: 1.6;
        max-width: 400px;
        margin: 0 auto 32px auto;
    }

    .pending-shop-preview {
        background: var(--seller-surface-alt);
        border: 1px solid var(--seller-border-color);
        border-radius: var(--seller-radius-md);
        padding: 20px;
        text-align: left;
        margin-bottom: 32px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .preview-shop-icon {
        width: 50px;
        height: 50px;
        background: var(--seller-surface-bg);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--seller-color-primary);
        box-shadow: var(--seller-shadow-sm);
    }

    @keyframes scaleFadeIn {
        0% { opacity: 0; transform: scale(0.95) translateY(20px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }

    @keyframes checkBounce {
        0% { transform: scale(0.8); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    @keyframes spinnerBorder {
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 992px) {
        .onboarding-layout { grid-template-columns: 1fr; }
        .onboarding-presentation { border-right: none; border-bottom: 1px solid var(--seller-border-color); padding: 40px 30px; }
        .onboarding-interaction { padding: 40px 30px; }
    }

    @media (max-width: 576px) {
        .onboarding-presentation, .onboarding-interaction { padding: 30px 20px; }
        .presentation-headline { font-size: 1.8rem; }
    }
</style>

<div class="onboarding-container">
    <div class="onboarding-layout">
        
        <div class="onboarding-presentation">
            <div class="presentation-badge">
                <i class="fas fa-rocket"></i> Seller Program
            </div>
            <h1 class="presentation-headline">เริ่มต้นธุรกิจบนพื้นที่ของวิทยาลัย</h1>
            <p class="presentation-subheadline">
                เปลี่ยนความสามารถ สิ่งของ หรือไอเดียของคุณให้เป็นรายได้เสริมที่มั่นคงผ่านแพลตฟอร์ม BNCC Market
            </p>
            
            <div class="feature-list-group">
                <div class="feature-list-item">
                    <div class="feature-icon-wrapper"><i class="fas fa-bullseye"></i></div>
                    <div class="feature-text-content">
                        <h4>เข้าถึงกลุ่มเป้าหมายโดยตรง</h4>
                        <p>สินค้าของคุณจะแสดงต่อนักศึกษาและบุคลากรภายในวิทยาลัยโดยอัตโนมัติ</p>
                    </div>
                </div>
                <div class="feature-list-item">
                    <div class="feature-icon-wrapper"><i class="fas fa-shield-check"></i></div>
                    <div class="feature-text-content">
                        <h4>ปลอดภัยและตรวจสอบได้</h4>
                        <p>ระบบคัดกรองร้านค้าเพื่อสร้างความมั่นใจให้กับผู้ซื้อทุกคนในชุมชน</p>
                    </div>
                </div>
                <div class="feature-list-item">
                    <div class="feature-icon-wrapper"><i class="fas fa-chart-pie"></i></div>
                    <div class="feature-text-content">
                        <h4>จัดการร้านค้าครบวงจร</h4>
                        <p>มีระบบจัดการหลังบ้าน สต๊อกสินค้า และดูสถิติการขายฟรีไม่มีค่าธรรมเนียม</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="onboarding-interaction">
            <?php if ($is_pending): ?>
                
                <div class="pending-review-state">
                    <div class="pending-anim-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <h2 class="pending-review-title">คำขออยู่ระหว่างพิจารณา</h2>
                    <p class="pending-review-desc">
                        เราได้รับข้อมูลการขอเปิดร้านค้าของคุณแล้ว ขณะนี้ผู้ดูแลระบบกำลังตรวจสอบความถูกต้อง โปรดรอประมาณ 1-2 วันทำการ
                    </p>
                    
                    <div class="pending-shop-preview">
                        <div class="preview-shop-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: var(--seller-text-muted); font-weight: 700; text-transform: uppercase;">ชื่อร้านค้าที่เสนอ</div>
                            <div style="font-size: 1.2rem; font-weight: 800; color: var(--seller-text-main);">
                                <?= htmlspecialchars($existing_shop['shop_name']) ?>
                            </div>
                        </div>
                    </div>
                    
                    <a href="../pages/profile.php" class="action-submit-btn" style="text-decoration: none; background: var(--seller-surface-alt); color: var(--seller-text-main); border: 2px solid var(--seller-border-color); box-shadow: none;">
                        <i class="fas fa-arrow-left"></i> กลับไปหน้าโปรไฟล์
                    </a>
                </div>

            <?php else: ?>
                
                <div class="interaction-header">
                    <h2>ข้อมูลร้านค้าเบื้องต้น</h2>
                    <p>กรอกรายละเอียดเพื่อส่งให้ผู้ดูแลระบบพิจารณาอนุมัติเปิดร้าน</p>
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
                    
                    <div class="input-field-group">
                        <div class="input-field-label">
                            <label for="inputShopName">ชื่อร้านค้า <span class="input-required-mark">*</span></label>
                            <span class="input-character-count" id="counterShopName">0 / 50</span>
                        </div>
                        <input type="text" name="shop_name" id="inputShopName" class="form-input-element" 
                               placeholder="เช่น BNCC Stationery" 
                               value="<?= htmlspecialchars($_POST['shop_name'] ?? '') ?>" 
                               maxlength="50" autocomplete="off">
                    </div>

                    <div class="input-field-group">
                        <div class="input-field-label">
                            <label for="inputShopDesc">รายละเอียดและจุดเด่นของร้าน <span class="input-required-mark">*</span></label>
                            <span class="input-character-count" id="counterShopDesc">0 / 500</span>
                        </div>
                        <textarea name="shop_description" id="inputShopDesc" class="form-input-element" rows="4" 
                                  placeholder="อธิบายสั้นๆ ว่าร้านของคุณขายอะไร มีจุดเด่นหรือบริการอะไรบ้าง..." 
                                  maxlength="500"><?= htmlspecialchars($_POST['shop_description'] ?? '') ?></textarea>
                    </div>

                    <div class="terms-agreement-wrapper">
                        <input type="checkbox" name="accept_terms" id="inputAcceptTerms" class="custom-check-element" 
                               <?= isset($_POST['accept_terms']) ? 'checked' : '' ?>>
                        <label for="inputAcceptTerms" class="custom-check-label">
                            ข้าพเจ้ายืนยันว่าข้อมูลถูกต้อง และยอมรับ <strong>กฎระเบียบการซื้อขาย</strong> ภายในวิทยาลัย หากฝ่าฝืนยินยอมให้ระงับบัญชี
                        </label>
                    </div>

                    <button type="submit" class="action-submit-btn" id="btnSubmitRequest" disabled>
                        ส่งคำขอเปิดร้านค้า <i class="fas fa-arrow-right"></i>
                    </button>
                    
                </form>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
/**
 * Real-time Form Validation System
 * Handles character counting and enabling/disabling the submit button dynamically.
 */
document.addEventListener('DOMContentLoaded', function() {
    const elShopName = document.getElementById('inputShopName');
    const elShopDesc = document.getElementById('inputShopDesc');
    const elTerms = document.getElementById('inputAcceptTerms');
    const elBtnSubmit = document.getElementById('btnSubmitRequest');
    const cntName = document.getElementById('counterShopName');
    const cntDesc = document.getElementById('counterShopDesc');
    const regForm = document.getElementById('sellerRegistrationForm');

    // Prevent execution if form doesn't exist (e.g., in pending state)
    if (!regForm) return;

    // Logic to evaluate form readiness
    function evaluateFormValidity() {
        const valName = elShopName.value.trim();
        const valDesc = elShopDesc.value.trim();
        
        const isNameValid = valName.length >= 3 && valName.length <= 50;
        const isDescValid = valDesc.length >= 10 && valDesc.length <= 500;
        const isTermsChecked = elTerms.checked;

        // Visual feedback for valid inputs
        isNameValid ? elShopName.classList.add('is-valid') : elShopName.classList.remove('is-valid');
        isDescValid ? elShopDesc.classList.add('is-valid') : elShopDesc.classList.remove('is-valid');

        // Enable button only if all criteria meet
        if (isNameValid && isDescValid && isTermsChecked) {
            elBtnSubmit.removeAttribute('disabled');
            // Adding a pulse effect when it becomes active
            elBtnSubmit.style.animation = 'none';
            setTimeout(() => elBtnSubmit.style.animation = 'checkBounce 0.4s ease', 10);
        } else {
            elBtnSubmit.setAttribute('disabled', 'true');
        }
    }

    // Logic to update character counters
    function updateCharCounters() {
        const lenName = elShopName.value.length;
        const lenDesc = elShopDesc.value.length;

        cntName.textContent = `${lenName} / 50`;
        cntDesc.textContent = `${lenDesc} / 500`;

        // Toggle warning colors if limit reached
        cntName.classList.toggle('limit-reached', lenName >= 50);
        cntDesc.classList.toggle('limit-reached', lenDesc >= 500);
    }

    // Attach Event Listeners
    ['input', 'keyup', 'change', 'paste'].forEach(evt => {
        elShopName.addEventListener(evt, () => {
            updateCharCounters();
            evaluateFormValidity();
        });
        
        elShopDesc.addEventListener(evt, () => {
            updateCharCounters();
            evaluateFormValidity();
        });
    });

    elTerms.addEventListener('change', evaluateFormValidity);

    // Form Submission Loader
    regForm.addEventListener('submit', function(e) {
        if (!elBtnSubmit.disabled) {
            elBtnSubmit.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> กำลังประมวลผล...';
            elBtnSubmit.style.opacity = '0.8';
            elBtnSubmit.style.cursor = 'wait';
            // We do not strictly disable it here to ensure the form submits properly across all browsers, 
            // but the visual state changes.
        }
    });

    // Initial check on load (useful if browser preserves input values on back navigation)
    updateCharCounters();
    evaluateFormValidity();
});
</script>

<?php require_once '../includes/footer.php'; ?>