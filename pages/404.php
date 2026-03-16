<?php
/**
 * 🔍 BNCC Market - 404 Not Found Error Page
 */
require_once '../includes/functions.php';
$pageTitle = "404 Page Not Found - BNCC Market";
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
        border: 2px solid #e2e8f0;
        border-radius: 32px;
        padding: 60px 40px;
        text-align: center;
        max-width: 550px;
        width: 100%;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        animation: slideUpFade 0.6s ease-out forwards;
        position: relative;
        overflow: hidden;
    }

    .dark-theme .error-card {
        background: #161b26;
        border-color: #334155;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }

    .error-code {
        font-size: 7rem;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 5px;
        background: linear-gradient(135deg, #4f46e5 0%, #a855f7 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: -5px;
        position: relative;
        z-index: 2;
        animation: floating 3s ease-in-out infinite;
    }

    .error-icon {
        font-size: 3.5rem;
        color: #a855f7;
        margin-bottom: 15px;
        opacity: 0.8;
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
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    .btn-back-home {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #4f46e5;
        color: #ffffff;
        padding: 16px 35px;
        border-radius: 16px;
        font-size: 1.05rem;
        font-weight: 800;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
    }

    .btn-back-home:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(79, 70, 229, 0.4);
        background: #4338ca;
        color: #ffffff;
    }

    @keyframes slideUpFade {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes floating {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
</style>

<div class="error-page-wrapper">
    <div class="error-card">
        <i class="fas fa-ghost error-icon"></i>
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-desc">
            อ๊ะ! ดูเหมือนคุณจะหลงทาง หน้าเว็บที่คุณกำลังตามหาอาจถูกลบไปแล้ว หรือคุณพิมพ์ URL ผิด ลองตรวจสอบใหม่อีกครั้งนะ
        </p>
        <a href="../pages/index.php" class="btn-back-home">
            <i class="fas fa-rocket"></i> พากลับยานแม่ (หน้าหลัก)
        </a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>