<?php
/**
 * 🛡️ BNCC Market - 403 Forbidden Error Page
 */
require_once '../includes/functions.php';
$pageTitle = "403 Access Denied - BNCC Market";
require_once '../includes/header.php';
?>

<style>
    .error-page-wrapper {
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        background-color: var(--solid-bg, #f8fafc);
        font-family: 'Prompt', sans-serif;
    }

    .error-card {
        background: var(--solid-card, #ffffff);
        border: 2px solid #fca5a5;
        border-radius: 32px;
        padding: 60px 40px;
        text-align: center;
        max-width: 550px;
        width: 100%;
        box-shadow: 0 20px 40px rgba(239, 68, 68, 0.08);
        animation: slideUpFade 0.6s ease-out forwards;
    }

    .dark-theme .error-card {
        background: #161b26;
        border-color: #7f1d1d;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .error-code {
        font-size: 6rem;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: pulseDanger 2s infinite;
    }

    .error-icon {
        font-size: 4rem;
        color: #ef4444;
        margin-bottom: 20px;
        display: block;
    }

    .error-title {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--solid-text, #0f172a);
        margin-bottom: 15px;
        letter-spacing: -0.5px;
    }

    .error-desc {
        font-size: 1.05rem;
        color: var(--text-muted, #64748b);
        line-height: 1.6;
        margin-bottom: 35px;
        font-weight: 500;
    }

    .btn-back-home {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #ef4444;
        color: #ffffff;
        padding: 16px 35px;
        border-radius: 16px;
        font-size: 1.05rem;
        font-weight: 800;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
    }

    .btn-back-home:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(239, 68, 68, 0.4);
        color: #ffffff;
    }

    @keyframes slideUpFade {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulseDanger {
        0%, 100% { filter: drop-shadow(0 0 15px rgba(239,68,68,0.2)); }
        50% { filter: drop-shadow(0 0 25px rgba(239,68,68,0.5)); }
    }
</style>

<div class="error-page-wrapper">
    <div class="error-card">
        <i class="fas fa-user-shield error-icon"></i>
        <div class="error-code">403</div>
        <h1 class="error-title">Access Denied!</h1>
        <p class="error-desc">
            หยุดนะ! 🛑 พื้นที่นี้ถูกจำกัดสิทธิ์การเข้าถึงเฉพาะผู้ดูแลระบบ หรือคุณอาจไม่มีสิทธิ์เข้าดูเนื้อหาในหน้านี้
        </p>
        <a href="../pages/index.php" class="btn-back-home">
            <i class="fas fa-home"></i> กลับสู่หน้าหลัก
        </a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>