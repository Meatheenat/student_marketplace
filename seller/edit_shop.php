<?php
/**
 * Student Marketplace - Edit Shop Settings
 */
require_once '../includes/functions.php';

checkRole('seller');

$db = getDB();
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->execute([$user_id]);
$shop = $stmt->fetch();

$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name        = trim($_POST['shop_name']        ?? '');
    $description      = trim($_POST['description']      ?? '');
    $contact_line     = trim($_POST['contact_line']     ?? '');
    $contact_ig       = trim($_POST['contact_ig']       ?? '');
    $contact_facebook = trim($_POST['contact_facebook'] ?? '');
    $contact_phone    = trim($_POST['contact_phone']    ?? '');

    if (empty($shop_name)) {
        $form_errors[] = "กรุณาระบุชื่อร้านค้าของคุณ";
    } elseif (mb_strlen($shop_name) > 50) {
        $form_errors[] = "ชื่อร้านค้าต้องมีความยาวไม่เกิน 50 ตัวอักษร";
    }

    if (mb_strlen($description) > 500) {
        $form_errors[] = "คำอธิบายร้านค้าไม่ควรเกิน 500 ตัวอักษร";
    }

    if (empty($form_errors)) {
        if ($shop) {
            $sql    = "UPDATE shops SET shop_name = ?, description = ?, contact_line = ?, contact_ig = ?, contact_facebook = ?, contact_phone = ? WHERE user_id = ?";
            $params = [$shop_name, $description, $contact_line ?: null, $contact_ig ?: null, $contact_facebook ?: null, $contact_phone ?: null, $user_id];
            $msg    = "อัปเดตข้อมูลร้านค้าเรียบร้อยแล้ว";
        } else {
            $sql    = "INSERT INTO shops (shop_name, description, contact_line, contact_ig, contact_facebook, contact_phone, user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $params = [$shop_name, $description, $contact_line ?: null, $contact_ig ?: null, $contact_facebook ?: null, $contact_phone ?: null, $user_id];
            $msg    = "สร้างร้านค้าสำเร็จ! กรุณารอครูอนุมัติร้านค้าของคุณ";
        }

        $stmt_save = $db->prepare($sql);
        if ($stmt_save->execute($params)) {
            $_SESSION['flash_message'] = $msg;
            $_SESSION['flash_type']    = "success";
            redirect('dashboard.php');
        }
    }
}

// ค่าที่จะแสดงในฟอร์ม — ถ้า POST มาให้ใช้ POST, ไม่งั้นใช้ DB
$val_name     = $_POST['shop_name']        ?? $shop['shop_name']        ?? '';
$val_desc     = $_POST['description']      ?? $shop['description']      ?? '';
$val_line     = $_POST['contact_line']     ?? $shop['contact_line']     ?? '';
$val_ig       = $_POST['contact_ig']       ?? $shop['contact_ig']       ?? '';
$val_facebook = $_POST['contact_facebook'] ?? $shop['contact_facebook'] ?? '';
$val_phone    = $_POST['contact_phone']    ?? $shop['contact_phone']    ?? '';

$pageTitle = "ตั้งค่าร้านค้า - BNCC Market";
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

        --seller-shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.05);
        --seller-shadow-md: 0 10px 15px -3px rgba(0,0,0,0.1);
        --seller-shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.05);
        --seller-shadow-glow: 0 0 20px rgba(79,70,229,0.2);

        --seller-transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        --seller-bounce: all 0.5s cubic-bezier(0.68,-0.55,0.265,1.55);
    }

    .dark-theme {
        --seller-surface-bg: #111827;
        --seller-surface-alt: #0b0f19;
        --seller-border-color: #1f2937;
        --seller-text-main: #f8fafc;
        --seller-text-muted: #94a3b8;
        --seller-text-light: #475569;
        --seller-color-primary-light: rgba(79,70,229,0.15);
        --seller-color-primary-soft: rgba(79,70,229,0.1);
        --seller-shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.3);
    }

    .onboarding-container {
        max-width: 1100px;
        margin: 60px auto;
        padding: 0 20px;
        min-height: calc(100vh - 250px);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: scaleFadeIn 0.6s cubic-bezier(0.16,1,0.3,1) forwards;
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

    /* ── Left Presentation ── */
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
        font-size: 1.05rem;
        color: var(--seller-text-muted);
        line-height: 1.6;
        margin-bottom: 45px;
        font-weight: 500;
    }

    .feature-list-group { display: flex; flex-direction: column; gap: 24px; }

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
        display: flex; justify-content: center; align-items: center;
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

    .feature-text-content h4 { font-size: 1.05rem; font-weight: 800; color: var(--seller-text-main); margin: 0 0 5px 0; }
    .feature-text-content p  { font-size: 0.9rem; color: var(--seller-text-muted); margin: 0; line-height: 1.5; }

    /* Back link */
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--seller-text-muted);
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 700;
        margin-bottom: 22px;
        transition: var(--seller-transition);
    }
    .back-link:hover { color: var(--seller-color-primary); }

    /* ── Right Interaction ── */
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
        background: rgba(239,68,68,0.08);
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

    .input-field-group { margin-bottom: 20px; }

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
        font-size: 0.78rem; font-weight: 600;
        color: var(--seller-text-light);
        background: var(--seller-surface-alt);
        padding: 2px 8px;
        border-radius: var(--seller-radius-pill);
        transition: color 0.3s;
    }

    .input-character-count.limit-reached { color: #fff; background: var(--seller-color-danger); }

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
    .form-input-element.is-valid { border-color: var(--seller-color-success); }

    /* Contact section */
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

    /* Submit button */
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

    .action-submit-btn:hover { transform: translateY(-4px); box-shadow: var(--seller-shadow-glow); }
    .action-submit-btn:hover::before { left: 100%; }
    .action-submit-btn:active { transform: translateY(0) scale(0.98); }

    /* KEYFRAMES */
    @keyframes scaleFadeIn  { 0% { opacity:0; transform: scale(0.96) translateY(20px); } 100% { opacity:1; transform: scale(1) translateY(0); } }
    @keyframes slowDrift    { 0% { transform: rotate(0deg) translate(0,0); } 100% { transform: rotate(360deg) translate(-50px,-50px); } }
    @keyframes floatUpDown  { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    @keyframes shakeError   { 10%,90% { transform: translate3d(-1px,0,0); } 20%,80% { transform: translate3d(2px,0,0); } 30%,50%,70% { transform: translate3d(-4px,0,0); } 40%,60% { transform: translate3d(4px,0,0); } }

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
        .contact-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="onboarding-container">
    <div class="onboarding-layout">

        <!-- ── Left: Presentation ── -->
        <div class="onboarding-presentation">
            <div class="presentation-content-wrapper">
                <div class="presentation-badge">
                    <i class="fas fa-store"></i> Seller Center
                </div>
                <h1 class="presentation-headline">ปรับแต่งร้านให้โดดเด่น</h1>
                <p class="presentation-subheadline">
                    ข้อมูลร้านที่ครบถ้วนและน่าสนใจช่วยให้ลูกค้าตัดสินใจซื้อได้เร็วขึ้น และสร้างความน่าเชื่อถือในระบบ
                </p>

                <div class="feature-list-group">
                    <div class="feature-list-item">
                        <div class="feature-icon-wrapper"><i class="fas fa-pen-nib"></i></div>
                        <div class="feature-text-content">
                            <h4>ชื่อร้านที่จำง่าย</h4>
                            <p>ตั้งชื่อให้สื่อถึงสิ่งที่ขาย จำง่าย และดึงดูดสายตาผู้ซื้อ</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-icon-wrapper"><i class="fas fa-address-card"></i></div>
                        <div class="feature-text-content">
                            <h4>ช่องทางติดต่อครบ</h4>
                            <p>ใส่ LINE, IG, Facebook, เบอร์โทร ให้ลูกค้าทักได้ทุกช่องทาง</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-icon-wrapper"><i class="fas fa-star"></i></div>
                        <div class="feature-text-content">
                            <h4>คำอธิบายที่ชัดเจน</h4>
                            <p>บอกจุดเด่นและประเภทสินค้าให้ครบ เพื่อสร้างความประทับใจแรก</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Right: Form ── -->
        <div class="onboarding-interaction">

            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> กลับไปยัง Dashboard
            </a>

            <div class="interaction-header">
                <h2>ตั้งค่าหน้าร้านค้า</h2>
                <p>ข้อมูลส่วนนี้จะปรากฏบนหน้าโปรไฟล์ร้านค้าของคุณให้นักเรียนคนอื่นเห็น</p>
            </div>

            <?php if (!empty($form_errors)): ?>
                <div class="validation-alert-box">
                    <ul class="validation-alert-list">
                        <?php foreach ($form_errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="edit_shop.php" method="POST" id="editShopForm">

                <!-- ชื่อร้านค้า -->
                <div class="input-field-group">
                    <div class="input-field-label">
                        <label for="inputShopName">ชื่อร้านค้า <span class="input-required-mark">*</span></label>
                        <span class="input-character-count" id="counterShopName">0 / 50</span>
                    </div>
                    <input type="text" name="shop_name" id="inputShopName"
                           class="form-input-element"
                           placeholder="เช่น BNCC Stationery"
                           value="<?= htmlspecialchars($val_name) ?>"
                           maxlength="50" autocomplete="off" autofocus>
                </div>

                <!-- คำอธิบายร้าน -->
                <div class="input-field-group">
                    <div class="input-field-label">
                        <label for="inputShopDesc">คำอธิบายร้านค้า</label>
                        <span class="input-character-count" id="counterShopDesc">0 / 500</span>
                    </div>
                    <textarea name="description" id="inputShopDesc"
                              class="form-input-element" rows="3"
                              placeholder="เล่าเรื่องราวของร้าน หรือรายละเอียดการรับสินค้า..."
                              maxlength="500"><?= htmlspecialchars($val_desc) ?></textarea>
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
                                   value="<?= htmlspecialchars($val_ig) ?>"
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
                                   value="<?= htmlspecialchars($val_line) ?>"
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
                                   value="<?= htmlspecialchars($val_facebook) ?>"
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
                                   value="<?= htmlspecialchars($val_phone) ?>"
                                   maxlength="20">
                        </div>
                    </div>

                </div><!-- /.contact-grid -->

                <button type="submit" class="action-submit-btn" id="btnSave">
                    <i class="fas fa-save"></i>
                    <span>บันทึกข้อมูลร้านค้า</span>
                </button>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const elName  = document.getElementById('inputShopName');
    const elDesc  = document.getElementById('inputShopDesc');
    const cntName = document.getElementById('counterShopName');
    const cntDesc = document.getElementById('counterShopDesc');
    const btnSave = document.getElementById('btnSave');

    function updateCounters() {
        const lenName = elName.value.length;
        const lenDesc = elDesc.value.length;
        cntName.textContent = `${lenName} / 50`;
        cntDesc.textContent = `${lenDesc} / 500`;
        cntName.classList.toggle('limit-reached', lenName >= 50);
        cntDesc.classList.toggle('limit-reached', lenDesc >= 500);

        // is-valid border
        lenName >= 1 ? elName.classList.add('is-valid') : elName.classList.remove('is-valid');
        lenDesc >= 1 ? elDesc.classList.add('is-valid') : elDesc.classList.remove('is-valid');
    }

    ['input','keyup','change','paste','cut'].forEach(evt => {
        elName?.addEventListener(evt, updateCounters);
        elDesc?.addEventListener(evt, updateCounters);
    });

    document.getElementById('editShopForm')?.addEventListener('submit', function() {
        if (btnSave) {
            btnSave.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i><span>กำลังบันทึก...</span>';
            btnSave.style.opacity = '0.9';
            btnSave.style.cursor  = 'wait';
        }
    });

    updateCounters();
});
</script>

<?php require_once '../includes/footer.php'; ?>