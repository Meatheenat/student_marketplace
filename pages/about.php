<?php
/**
 * ============================================================================================
 * 🛡️ BNCC MARKETPLACE - ABOUT US (ULTIMATE TITAN EDITION)
 * ============================================================================================
 */
require_once '../includes/functions.php';
$pageTitle = "ทีมผู้พัฒนา - BNCC Market";
require_once '../includes/header.php';
?>

<style>
    :root {
        --dev-primary: #4f46e5;
        --dev-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    }

    .about-wrapper {
        max-width: 1200px;
        margin: 80px auto;
        padding: 0 25px;
        font-family: 'Prompt', sans-serif;
    }

    /* --- Hero Section --- */
    .about-header {
        text-align: center;
        margin-bottom: 100px;
        animation: revealDown 0.8s ease-out;
    }

    @keyframes revealDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .about-header h1 {
        font-size: 4rem;
        font-weight: 900;
        letter-spacing: -2px;
        background: var(--dev-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 20px;
    }

    .about-header p {
        font-size: 1.25rem;
        color: var(--text-muted, #64748b);
        font-weight: 500;
    }

    /* --- Team Grid --- */
    .team-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
    }

    .dev-card {
        background: var(--bg-card, #ffffff);
        border: 2px solid var(--border-color, #e2e8f0);
        border-radius: 45px;
        padding: 60px 40px;
        text-align: center;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
    }

    .dark-theme .dev-card {
        background: #1e293b;
        border-color: #334155;
    }

    .dev-card:hover {
        transform: translateY(-20px);
        border-color: var(--dev-primary);
        box-shadow: 0 40px 80px -15px rgba(79, 70, 229, 0.25);
    }

    /* --- Avatar System --- */
    .avatar-container {
        position: relative;
        width: 200px;
        height: 200px;
        margin: 0 auto 35px;
    }

    .avatar-ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        padding: 5px;
        background: var(--dev-gradient);
        animation: rotateRing 10s linear infinite;
    }

    @keyframes rotateRing {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .dev-avatar {
        position: relative;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 6px solid var(--bg-card, #fff);
        z-index: 2;
    }

    .dev-name {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--text-main, #0f172a);
        margin-bottom: 8px;
    }

    .dev-role-badge {
        display: inline-block;
        padding: 8px 20px;
        background: rgba(79, 70, 229, 0.1);
        color: var(--dev-primary);
        border-radius: 15px;
        font-weight: 800;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 25px;
    }

    .dev-bio {
        font-size: 1.05rem;
        color: var(--text-muted, #64748b);
        line-height: 1.8;
        margin-bottom: 35px;
        min-height: 80px;
    }

    /* --- Social Icons (SVG Based) --- */
    .dev-social-stack {
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    .social-btn {
        width: 55px;
        height: 55px;
        border-radius: 18px;
        background: var(--bg-main, #f1f5f9);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
        color: var(--text-main, #1e293b);
    }

    .dark-theme .social-btn { background: #334155; color: #f8fafc; }

    .social-btn:hover {
        background: var(--dev-primary);
        color: #fff !important;
        transform: scale(1.15) rotate(5deg);
    }

    .social-btn svg {
        width: 24px;
        height: 24px;
        stroke-width: 2.5px;
    }

    /* --- Footer Badge --- */
    .project-status-bar {
        margin-top: 100px;
        padding: 40px;
        background: var(--bg-card, #fff);
        border-radius: 30px;
        border: 2px solid var(--border-color, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        text-align: left;
    }

    .status-icon {
        width: 60px;
        height: 60px;
        background: var(--dev-gradient);
        color: #fff;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 900px) {
        .team-grid { grid-template-columns: 1fr; }
        .about-header h1 { font-size: 2.8rem; }
    }
</style>

<div class="about-wrapper">
    <header class="about-header">
        <h1>Creative Minds</h1>
        <p>ทีมผู้พัฒนาผู้ขับเคลื่อน BNCC Market สู่การเป็นอันดับ 1 ของวิทยาลัย</p>
    </header>

    <div class="team-grid">
        <div class="dev-card">
            <div class="avatar-container">
                <div class="avatar-ring"></div>
                <img src="../assets/images/dev1.jpg" alt="Dev 1" class="dev-avatar">
            </div>
            <h2 class="dev-name">ชื่อ-นามสกุล คนที่ 1</h2>
            <div class="dev-role-badge">Lead Architect / UX Expert</div>
            <p class="dev-bio">
                ดูแลภาพรวมของโครงสร้างระบบฐานข้อมูล และเน้นการสร้างประสบการณ์ผู้ใช้งาน (UX) 
                ที่ลื่นไหลระดับสากล เพื่อให้ทุกคนใน BNCC ใช้งานได้อย่างง่ายดาย
            </p>
            <div class="dev-social-stack">
                <a href="#" class="social-btn" title="Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                </a>
                <a href="#" class="social-btn" title="Instagram">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                </a>
                <a href="#" class="social-btn" title="GitHub">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                </a>
            </div>
        </div>

        <div class="dev-card">
            <div class="avatar-container">
                <div class="avatar-ring"></div>
                <img src="../assets/images/dev2.jpg" alt="Dev 2" class="dev-avatar">
            </div>
            <h2 class="dev-name">ชื่อ-นามสกุล คนที่ 2</h2>
            <div class="dev-role-badge">System Engine / Logic</div>
            <p class="dev-bio">
                รับผิดชอบความปลอดภัยหลังบ้าน และการประมวลผลคำสั่งซื้อสินค้า 
                เพื่อให้ทุกการทำรายการภายในระบบ BNCC Market มีความแม่นยำและปลอดภัย 100%
            </p>
            <div class="dev-social-stack">
                <a href="#" class="social-btn" title="Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                </a>
                <a href="#" class="social-btn" title="Instagram">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                </a>
                <a href="#" class="social-btn" title="GitHub">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                </a>
            </div>
        </div>
    </div>

    <div class="project-status-bar">
        <div class="status-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
        </div>
        <div>
            <h4 style="margin: 0; font-weight: 900; font-size: 1.2rem;">BNCC Student Marketplace Project</h4>
            <p style="margin: 0; color: #64748b; font-weight: 600;">Bangna Commercial College • Professional Development Edition</p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>