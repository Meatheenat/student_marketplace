<?php
/**
 * ============================================================================================
 * 🛡️ BNCC MARKETPLACE - ABOUT US (THE DEVELOPER TITAN)
 * ============================================================================================
 */
require_once '../includes/functions.php';
$pageTitle = "เกี่ยวกับเรา - ทีมผู้พัฒนา BNCC Market";
require_once '../includes/header.php';
?>

<style>
    /* ============================================================
       💎 ABOUT US PREMIUM DESIGN
       ============================================================ */
    .about-wrapper {
        max-width: 1100px;
        margin: 60px auto;
        padding: 0 25px;
        animation: aboutReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes aboutReveal { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

    .about-header {
        text-align: center;
        margin-bottom: 80px;
    }
    .about-header h1 { 
        font-size: 3.5rem; 
        font-weight: 900; 
        letter-spacing: -2px; 
        background: linear-gradient(135deg, #4f46e5 0%, #8b5cf6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 15px;
    }
    .about-header p {
        font-size: 1.2rem;
        color: var(--text-muted, #64748b);
        font-weight: 600;
    }

    /* Team Grid Layout */
    .team-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }

    .dev-card {
        background: var(--bg-card, #ffffff);
        border: 2px solid var(--border-color, #e2e8f0);
        border-radius: 40px;
        padding: 50px 30px;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
    }
    .dev-card:hover {
        transform: translateY(-15px);
        border-color: #4f46e5;
        box-shadow: 0 30px 60px -12px rgba(79, 70, 229, 0.2);
    }

    /* Avatar System */
    .dev-avatar-wrap {
        width: 180px;
        height: 180px;
        margin: 0 auto 25px;
        border-radius: 50%;
        padding: 8px;
        background: linear-gradient(135deg, #4f46e5, #8b5cf6);
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
    }
    .dev-avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #fff;
    }

    .dev-name { font-size: 1.8rem; font-weight: 900; color: var(--text-main, #0f172a); margin-bottom: 5px; }
    .dev-role { 
        display: inline-block;
        font-size: 0.85rem; 
        font-weight: 800; 
        text-transform: uppercase; 
        color: #4f46e5; 
        background: rgba(79, 70, 229, 0.1);
        padding: 5px 15px;
        border-radius: 10px;
        letter-spacing: 1px;
        margin-bottom: 20px;
    }

    .dev-bio {
        color: var(--text-muted, #64748b);
        line-height: 1.7;
        font-weight: 500;
        margin-bottom: 25px;
    }

    /* Social Links in Card */
    .dev-socials {
        display: flex;
        justify-content: center;
        gap: 15px;
    }
    .dev-socials a {
        width: 45px;
        height: 45px;
        border-radius: 14px;
        background: var(--bg-main, #f1f5f9);
        color: var(--text-main, #0f172a);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: 0.3s;
    }
    .dev-socials a:hover {
        background: #4f46e5;
        color: #fff;
        transform: scale(1.1);
    }

    /* BNCC Brand Badge */
    .brand-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        margin-top: 100px;
        padding: 30px;
        background: rgba(15, 23, 42, 0.03);
        border-radius: 25px;
        border: 1px dashed var(--border-color, #cbd5e1);
    }

    @media (max-width: 850px) {
        .team-grid { grid-template-columns: 1fr; }
        .about-header h1 { font-size: 2.5rem; }
    }
</style>

<div class="about-wrapper">
    <div class="about-header">
        <h1>Our Creative Team</h1>
        <p>ผู้อยู่เบื้องหลังระบบตลาดนัดออนไลน์ BNCC Market</p>
    </div>

    <div class="team-grid">
        <div class="dev-card">
            <div class="dev-avatar-wrap">
                <img src="../assets/images/dev1.jpg" alt="Developer 1" class="dev-avatar">
            </div>
            <h2 class="dev-name">ชื่อ-นามสกุล คนที่ 1</h2>
            <span class="dev-role">Lead Developer / Designer</span>
            <p class="dev-bio">
                รับผิดชอบในการออกแบบโครงสร้างฐานข้อมูล (Database Architect) 
                และออกแบบ UI/UX ของระบบทั้งหมด เพื่อให้การใช้งานลื่นไหลและพรีเมียมที่สุด
            </p>
            <div class="dev-socials">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-github"></i></a>
            </div>
        </div>

        <div class="dev-card">
            <div class="dev-avatar-wrap">
                <img src="../assets/images/dev2.jpg" alt="Developer 2" class="dev-avatar">
            </div>
            <h2 class="dev-name">ชื่อ-นามสกุล คนที่ 2</h2>
            <span class="dev-role">System Engineer / QA</span>
            <p class="dev-bio">
                รับผิดชอบในการพัฒนาระบบ Backend การจัดการคำสั่งซื้อ (Orders System) 
                และการตรวจสอบความปลอดภัยของระบบ (Security Verification)
            </p>
            <div class="dev-socials">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-github"></i></a>
            </div>
        </div>
    </div>

    <div class="brand-badge">
        <i class="fas fa-university fa-2x" style="color: #4f46e5;"></i>
        <div style="text-align: left;">
            <h4 style="margin: 0; font-weight: 900;">Project BNCC Market v3.5</h4>
            <p style="margin: 0; color: #64748b; font-weight: 600; font-size: 0.9rem;">วิทยาลัยพณิชยการบางนา (Bangna Commercial College)</p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>