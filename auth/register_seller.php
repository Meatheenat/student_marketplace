<?php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบก่อนสมัครเป็นผู้ขาย";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] === 'seller') {
    $_SESSION['flash_message'] = "คุณเป็นร้านค้าอยู่แล้ว";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_pending) {
    $shop_name = trim($_POST['shop_name'] ?? '');
    $shop_desc = trim($_POST['shop_description'] ?? '');
    $accept_terms = isset($_POST['accept_terms']) ? true : false;

    $errors = [];

    if (empty($shop_name)) {
        $errors[] = "กรุณาระบุชื่อร้านค้า";
    } elseif (mb_strlen($shop_name) < 3 || mb_strlen($shop_name) > 50) {
        $errors[] = "ชื่อร้านค้าต้องมีความยาว 3-50 ตัวอักษร";
    }

    if (empty($shop_desc)) {
        $errors[] = "กรุณาระบุรายละเอียดร้านค้า";
    }

    if (!$accept_terms) {
        $errors[] = "คุณต้องยอมรับเงื่อนไขการให้บริการ";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO shops (user_id, shop_name, shop_description, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $shop_name, $shop_desc]);
            $db->commit();
            
            $_SESSION['flash_message'] = "ส่งคำขอเปิดร้านค้าสำเร็จ กรุณารอผู้ดูแลระบบตรวจสอบ";
            $_SESSION['flash_type'] = "success";
            header("Location: register_seller.php");
            exit();
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "เกิดข้อผิดพลาดของระบบ: " . $e->getMessage();
        }
    }
}

$pageTitle = "ลงทะเบียนเปิดร้านค้า - BNCC Market";
require_once '../includes/header.php';
?>

<style>
    :root {
        --seller-primary: #4f46e5;
        --seller-primary-dark: #3730a3;
        --seller-secondary: #10b981;
        --seller-bg: #f8fafc;
        --seller-surface: #ffffff;
        --seller-border: #e2e8f0;
        --seller-text: #0f172a;
        --seller-text-sub: #64748b;
        --seller-danger: #ef4444;
        --seller-radius-lg: 24px;
        --seller-radius-md: 16px;
        --seller-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
    }

    .dark-theme {
        --seller-bg: #0b0f19;
        --seller-surface: #111827;
        --seller-border: #1f2937;
        --seller-text: #f8fafc;
        --seller-text-sub: #94a3b8;
        --seller-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
    }

    .onboard-wrapper {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
        animation: fadeIn 0.6s ease-out;
    }

    .onboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        background: var(--seller-surface);
        border-radius: var(--seller-radius-lg);
        border: 1px solid var(--seller-border);
        box-shadow: var(--seller-shadow);
        overflow: hidden;
    }

    .onboard-info {
        padding: 60px 40px;
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
        border-right: 1px solid var(--seller-border);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .onboard-badge {
        display: inline-block;
        padding: 8px 16px;
        background: var(--seller-primary);
        color: white;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 800;
        margin-bottom: 20px;
        width: fit-content;
        letter-spacing: 1px;
    }

    .onboard-title {
        font-size: 2.5rem;
        font-weight: 900;
        color: var(--seller-text);
        line-height: 1.2;
        margin-bottom: 20px;
    }

    .onboard-subtitle {
        font-size: 1.1rem;
        color: var(--seller-text-sub);
        line-height: 1.6;
        margin-bottom: 40px;
    }

    .benefit-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .benefit-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }

    .benefit-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        background: var(--seller-surface);
        border: 1px solid var(--seller-border);
        display: flex;
        justify-content: center;
        align-items: center;
        color: var(--seller-primary);
        font-size: 1.2rem;
        flex-shrink: 0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }

    .benefit-text h4 {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--seller-text);
        margin: 0 0 5px 0;
    }

    .benefit-text p {
        font-size: 0.9rem;
        color: var(--seller-text-sub);
        margin: 0;
        line-height: 1.5;
    }

    .onboard-form-area {
        padding: 60px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .form-group {
        margin-bottom: 25px;
        position: relative;
    }

    .form-label {
        display: block;
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--seller-text);
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        padding: 16px 20px;
        border-radius: var(--seller-radius-md);
        border: 2px solid var(--seller-border);
        background: transparent;
        color: var(--seller-text);
        font-size: 1rem;
        font-family: inherit;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--seller-primary);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        background: var(--seller-surface);
    }

    .form-control::placeholder {
        color: var(--seller-text-sub);
        opacity: 0.6;
    }

    .char-counter {
        position: absolute;
        bottom: -22px;
        right: 0;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--seller-text-sub);
    }

    .checkbox-wrapper {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 30px;
        padding: 15px;
        background: rgba(79, 70, 229, 0.05);
        border-radius: var(--seller-radius-md);
        border: 1px dashed rgba(79, 70, 229, 0.2);
    }

    .custom-checkbox {
        width: 22px;
        height: 22px;
        border-radius: 6px;
        border: 2px solid var(--seller-primary);
        appearance: none;
        cursor: pointer;
        position: relative;
        transition: 0.2s;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .custom-checkbox:checked {
        background: var(--seller-primary);
    }

    .custom-checkbox:checked::after {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        color: white;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 0.7rem;
    }

    .checkbox-label {
        font-size: 0.9rem;
        color: var(--seller-text-sub);
        line-height: 1.5;
        cursor: pointer;
    }

    .checkbox-label a {
        color: var(--seller-primary);
        font-weight: 800;
        text-decoration: underline;
    }

    .submit-btn {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, var(--seller-primary), #6366f1);
        color: white;
        border: none;
        border-radius: var(--seller-radius-md);
        font-size: 1.1rem;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
    }

    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.5);
    }

    .submit-btn:disabled {
        background: var(--seller-border);
        color: var(--seller-text-sub);
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    .error-box {
        background: rgba(239, 68, 68, 0.1);
        border-left: 4px solid var(--seller-danger);
        padding: 15px 20px;
        border-radius: 0 12px 12px 0;
        margin-bottom: 25px;
    }

    .error-list {
        margin: 0;
        padding-left: 20px;
        color: var(--seller-danger);
        font-size: 0.9rem;
        font-weight: 600;
    }

    .pending-state {
        text-align: center;
        padding: 60px 40px;
    }

    .pending-icon-wrap {
        width: 120px;
        height: 120px;
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 4rem;
        margin: 0 auto 30px auto;
        position: relative;
    }

    .pending-icon-wrap::after {
        content: '';
        position: absolute;
        top: -10px; left: -10px; right: -10px; bottom: -10px;
        border: 3px dashed #f59e0b;
        border-radius: 50%;
        animation: spin 10s linear infinite;
        opacity: 0.3;
    }

    .pending-title {
        font-size: 2rem;
        font-weight: 900;
        color: var(--seller-text);
        margin-bottom: 15px;
    }

    .pending-desc {
        color: var(--seller-text-sub);
        font-size: 1.1rem;
        line-height: 1.6;
        max-width: 400px;
        margin: 0 auto 40px auto;
    }

    .shop-preview-card {
        background: var(--seller-bg);
        border: 1px solid var(--seller-border);
        border-radius: var(--seller-radius-md);
        padding: 20px;
        text-align: left;
        margin-bottom: 30px;
    }

    @keyframes spin { 100% { transform: rotate(360deg); } }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 992px) {
        .onboard-grid { grid-template-columns: 1fr; }
        .onboard-info { border-right: none; border-bottom: 1px solid var(--seller-border); padding: 40px 30px; }
        .onboard-form-area { padding: 40px 30px; }
    }

    @media (max-width: 576px) {
        .onboard-info, .onboard-form-area { padding: 30px 20px; }
        .onboard-title { font-size: 2rem; }
    }
</style>

<div class="onboard-wrapper">
    <div class="onboard-grid w-100">
        
        <div class="onboard-info">
            <span class="onboard-badge"><i class="fas fa-rocket me-2"></i>SELLER PROGRAM</span>
            <h1 class="onboard-title">เริ่มธุรกิจของคุณบนพื้นที่ของวิทยาลัย</h1>
            <p class="onboard-subtitle">เปลี่ยนสิ่งของที่ไม่ได้ใช้ หรือสินค้าที่คุณสร้างสรรค์ ให้เป็นรายได้เสริมที่ยั่งยืนผ่านแพลตฟอร์มของเรา</p>
            
            <div class="benefit-list">
                <div class="benefit-item">
                    <div class="benefit-icon"><i class="fas fa-users"></i></div>
                    <div class="benefit-text">
                        <h4>เข้าถึงกลุ่มลูกค้าโดยตรง</h4>
                        <p>สินค้าของคุณจะถูกมองเห็นโดยนักศึกษาและบุคลากรภายในวิทยาลัยกว่าพันคน</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="benefit-text">
                        <h4>ปลอดภัยและน่าเชื่อถือ</h4>
                        <p>ระบบตรวจสอบยืนยันตัวตน ทำให้การซื้อขายโปร่งใสไร้ความกังวล</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="benefit-text">
                        <h4>ระบบจัดการร้านค้าครบวงจร</h4>
                        <p>จัดการคำสั่งซื้อ สต๊อกสินค้า และดูสถิติการขายได้ฟรีผ่านแดชบอร์ด</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="onboard-form-area">
            <?php if ($is_pending): ?>
                
                <div class="pending-state">
                    <div class="pending-icon-wrap">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <h2 class="pending-title">คำขออยู่ระหว่างพิจารณา</h2>
                    <p class="pending-desc">ระบบได้รับข้อมูลการขอเปิดร้านค้าของคุณแล้ว ขณะนี้แอดมินกำลังตรวจสอบความถูกต้อง กรุณารอ 1-2 วันทำการ</p>
                    
                    <div class="shop-preview-card">
                        <div class="text-muted small fw-bold mb-1">ชื่อร้านค้าที่เสนอ:</div>
                        <div class="fw-bold" style="font-size: 1.2rem; color: var(--seller-primary); margin-bottom: 10px;">
                            <i class="fas fa-store me-2"></i><?= htmlspecialchars($existing_shop['shop_name']) ?>
                        </div>
                    </div>
                    
                    <a href="../pages/index.php" class="submit-btn" style="text-decoration: none; background: var(--seller-bg); color: var(--seller-text); border: 2px solid var(--seller-border); box-shadow: none;">
                        กลับสู่หน้าหลัก
                    </a>
                </div>

            <?php else: ?>
                
                <div style="margin-bottom: 30px;">
                    <h2 style="font-weight: 900; color: var(--seller-text); margin-bottom: 5px;">ข้อมูลร้านค้าของคุณ</h2>
                    <p style="color: var(--seller-text-sub); font-size: 0.95rem;">กรอกข้อมูลให้ครบถ้วนเพื่อส่งให้ผู้ดูแลระบบพิจารณา</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <ul class="error-list">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" id="sellerForm">
                    <div class="form-group">
                        <label class="form-label">ชื่อร้านค้า <span class="text-danger">*</span></label>
                        <input type="text" name="shop_name" id="shopName" class="form-control" placeholder="เช่น BNCC Stationery" value="<?= htmlspecialchars($_POST['shop_name'] ?? '') ?>" required maxlength="50">
                        <div class="char-counter" id="nameCounter">0 / 50</div>
                    </div>

                    <div class="form-group" style="margin-bottom: 40px;">
                        <label class="form-label">รายละเอียดร้านค้า <span class="text-danger">*</span></label>
                        <textarea name="shop_description" id="shopDesc" class="form-control" rows="5" placeholder="อธิบายเกี่ยวกับสินค้า บริการ หรือจุดเด่นของร้านคุณให้ลูกค้ารู้จัก..." required maxlength="500"><?= htmlspecialchars($_POST['shop_description'] ?? '') ?></textarea>
                        <div class="char-counter" id="descCounter">0 / 500</div>
                    </div>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="accept_terms" id="acceptTerms" class="custom-checkbox" <?= isset($_POST['accept_terms']) ? 'checked' : '' ?>>
                        <label for="acceptTerms" class="checkbox-label">
                            ข้าพเจ้าขอยืนยันว่าข้อมูลข้างต้นเป็นความจริง และยอมรับ <a href="/pages/terms.php" target="_blank">เงื่อนไขการให้บริการ</a> รวมถึงการปฏิบัติตามกฎระเบียบของวิทยาลัยอย่างเคร่งครัด
                        </label>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn" disabled>
                        ส่งคำขอเปิดร้านค้า <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                </form>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const shopName = document.getElementById('shopName');
    const shopDesc = document.getElementById('shopDesc');
    const nameCounter = document.getElementById('nameCounter');
    const descCounter = document.getElementById('descCounter');
    const acceptTerms = document.getElementById('acceptTerms');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('sellerForm');

    if (!form) return;

    function updateCounters() {
        if (shopName) nameCounter.textContent = `${shopName.value.length} / 50`;
        if (shopDesc) descCounter.textContent = `${shopDesc.value.length} / 500`;
    }

    function checkFormValidity() {
        const isNameValid = shopName.value.trim().length >= 3;
        const isDescValid = shopDesc.value.trim().length > 0;
        const isTermsAccepted = acceptTerms.checked;
        
        submitBtn.disabled = !(isNameValid && isDescValid && isTermsAccepted);
    }

    if (shopName) {
        shopName.addEventListener('input', () => {
            updateCounters();
            checkFormValidity();
        });
    }

    if (shopDesc) {
        shopDesc.addEventListener('input', () => {
            updateCounters();
            checkFormValidity();
        });
    }

    if (acceptTerms) {
        acceptTerms.addEventListener('change', checkFormValidity);
    }

    form.addEventListener('submit', function(e) {
        if (!submitBtn.disabled) {
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> กำลังดำเนินการ...';
            submitBtn.disabled = true;
        }
    });

    updateCounters();
    checkFormValidity();
});
</script>

<?php require_once '../includes/footer.php'; ?>