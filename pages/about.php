<?php
/**
 * ============================================================================================
 * 🛡️ BNCC MARKETPLACE - ABOUT US (ULTIMATE TITAN EDITION)
 * ============================================================================================
 */
require_once '../includes/functions.php';
$pageTitle = "ทีมผู้พัฒนา - BNCC Market";
require_once '../includes/header.php';

// ============================================================================================
// 👇 แก้ไขข้อมูลผู้พัฒนาที่นี่
// ============================================================================================
$developers = [
    [
        'avatar'      => '../assets/images/dev1.gif',        // ← ใช้รูปที่มีในเซิร์ฟเวอร์
        'banner_img'  => '../assets/images/dev2_banner.jpg',        // ← ใช้รูปเดียวกันไปก่อน หรืออัปโหลด dev1_banner.jpg แล้วเปลี่ยน
        'name'        => 'Meatheenat khaowketwaranisa',
        'nickname'    => 'Ping',
        'age'         => '20',
        'education'   => 'ปวส.2 สาขาเทคโนโลยีสารสนเทศ วิทยาลัยพณิชยการบางนา',
        'role'        => 'Lead Developer / Full-Stack / System Engine / Back-End / UX UI',
        'student_id'  => '67319010004',
        'bio'         => '👨‍💻 IT Support | 💻 Coding | 🎬 Multimedia | 🎮 Gaming | 🎌 Anime | 🐺 Silver Wolf Main | 😴 Professional Sleeper',
        'skills'      => ['PHP', 'MySQL', 'UI/UX', 'JavaScript', 'Security', 'API'],
        'facebook'    => 'https://www.facebook.com/meatheenat.khaowaranisa',
        'instagram'   => 'https://www.instagram.com/__r._.wang_/',
        'github'      => 'https://github.com/Meatheenat',
    ],
    [
        'avatar'      => '../assets/images/dev2.jpg',
        'banner_img'  => '../assets/images/dev2_banner.jpg', // path รูป banner ด้านบน card
        'name'        => 'Kittipat Tunkhan',
        'nickname'    => 'Oven',
        'age'         => '20',
        'education'   => 'ปวส.2 สาขาเทคโนโลยีสารสนเทศ วิทยาลัยพณิชยการบางนา',
        'role'        => 'System Engine / IT Support / Forex / Binary Options',
        'student_id'  => '67319010023',
        'bio'         => 'นักศึกษาด้าน IT ที่สนใจการพัฒนาโปรแกรม การวิเคราะห์ข้อมูล และ FinTech โดยเฉพาะการสร้างระบบวิเคราะห์การเทรดและเครื่องมืออัตโนมัติสำหรับตลาด Forex และ Binary Options',
        'skills'      => ['Trader'],
        'facebook'    => 'https://www.facebook.com/kittipat.oven',
        'instagram'   => 'https://www.instagram.com/_esp_32_/',
        'github'      => 'https://github.com/kittipatoven',
    ],
];
// ============================================================================================
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

    .about-header {
        text-align: center;
        margin-bottom: 80px;
        animation: revealDown 0.8s ease-out;
    }

    @keyframes revealDown {
        from { opacity: 0; transform: translateY(-30px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .about-header h1 {
        font-size: 4rem;
        font-weight: 900;
        letter-spacing: -2px;
        background: var(--dev-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 15px;
    }

    .about-header p {
        font-size: 1.2rem;
        color: var(--text-muted, #64748b);
        font-weight: 500;
    }

    .team-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }

    /* ── Card ── */
    .dev-card {
        background: var(--bg-card, #ffffff);
        border: 2px solid var(--border-color, #e2e8f0);
        border-radius: 40px;
        overflow: hidden;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    }

    .dark-theme .dev-card { background: #1e293b; border-color: #334155; }

    .dev-card:hover {
        transform: translateY(-12px);
        border-color: var(--dev-primary);
        box-shadow: 0 40px 80px -15px rgba(79,70,229,0.22);
    }

    /* ── Banner ── */
    .dev-card-banner {
        height: 140px;
        position: relative;
        overflow: hidden;
        background: var(--dev-gradient); /* fallback ถ้าไม่มีรูป */
    }

    /* รูป banner จริง */
    .dev-card-banner .banner-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
        transition: transform 0.6s ease;
    }

    .dev-card:hover .banner-bg {
        transform: scale(1.08);
    }

    /* gradient overlay ทับรูป */
    .dev-card-banner::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(
            to bottom,
            rgba(79, 70, 229, 0.3) 0%,
            rgba(0, 0, 0, 0.6) 100%
        );
        z-index: 1;
    }

    /* pattern texture */
    .dev-card-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        background: repeating-linear-gradient(
            45deg,
            rgba(255,255,255,0.04) 0px,
            rgba(255,255,255,0.04) 2px,
            transparent 2px,
            transparent 14px
        );
        z-index: 2;
        pointer-events: none;
    }

    /* ── Avatar ── */
    .dev-card-body { padding: 0 35px 35px; }

    .avatar-container {
        position: relative;
        width: 110px;
        height: 110px;
        margin: -55px auto 20px;
        z-index: 10;
    }

    .avatar-ring {
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        background: var(--dev-gradient);
        animation: rotateRing 10s linear infinite;
        z-index: 0;
    }

    @keyframes rotateRing {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }

    .dev-avatar {
        position: relative;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid var(--bg-card, #fff);
        z-index: 2;
        display: block;
    }

    .dark-theme .dev-avatar { border-color: #1e293b; }

    /* ── Name & Role ── */
    .dev-name {
        font-size: 1.7rem;
        font-weight: 900;
        color: var(--text-main, #0f172a);
        text-align: center;
        margin-bottom: 4px;
        letter-spacing: -0.5px;
    }

    .dev-nickname {
        text-align: center;
        font-size: 1rem;
        color: var(--text-muted, #64748b);
        font-weight: 600;
        margin-bottom: 10px;
    }

    .dev-role-badge {
        display: block;
        width: fit-content;
        margin: 0 auto 22px;
        padding: 6px 18px;
        background: rgba(79,70,229,0.1);
        color: var(--dev-primary);
        border-radius: 12px;
        font-weight: 800;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* ── Info Grid ── */
    .dev-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 20px;
    }

    .dev-info-item {
        background: var(--bg-main, #f8fafc);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
        padding: 12px 14px;
    }

    .dark-theme .dev-info-item { background: #0f172a; border-color: #334155; }

    .dev-info-label {
        font-size: 0.68rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--text-muted, #94a3b8);
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .dev-info-value {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-main, #0f172a);
        line-height: 1.3;
    }

    .dev-info-item.full { grid-column: 1 / -1; }

    /* ── Bio ── */
    .dev-bio {
        font-size: 0.93rem;
        color: var(--text-muted, #64748b);
        line-height: 1.75;
        margin-bottom: 18px;
        padding: 14px 16px;
        background: rgba(79,70,229,0.04);
        border-left: 3px solid var(--dev-primary);
        border-radius: 0 12px 12px 0;
    }

    /* ── Skills ── */
    .dev-skills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 22px;
    }

    .skill-tag {
        padding: 4px 12px;
        background: var(--bg-main, #f1f5f9);
        border: 1.5px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--text-muted, #475569);
        transition: 0.2s;
    }

    .skill-tag:hover {
        background: var(--dev-primary);
        color: #fff;
        border-color: var(--dev-primary);
    }

    /* ── Social ── */
    .dev-divider {
        height: 1px;
        background: var(--border-color, #e2e8f0);
        margin-bottom: 20px;
    }

    .dev-social-stack {
        display: flex;
        justify-content: center;
        gap: 14px;
    }

    .social-btn {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: var(--bg-main, #f1f5f9);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
        color: var(--text-main, #1e293b);
        text-decoration: none;
    }

    .dark-theme .social-btn { background: #334155; color: #f8fafc; }

    .social-btn:hover {
        background: var(--dev-primary);
        color: #fff !important;
        transform: scale(1.15) rotate(5deg);
    }

    .social-btn svg { width: 22px; height: 22px; stroke-width: 2.5px; }

    /* ── Project Bar ── */
    .project-status-bar {
        margin-top: 80px;
        padding: 35px 40px;
        background: var(--bg-card, #fff);
        border-radius: 28px;
        border: 2px solid var(--border-color, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
    }

    .dark-theme .project-status-bar { background: #1e293b; border-color: #334155; }

    .status-icon {
        width: 60px; height: 60px;
        background: var(--dev-gradient);
        color: #fff;
        border-radius: 18px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    @media (max-width: 900px) {
        .team-grid { grid-template-columns: 1fr; }
        .about-header h1 { font-size: 2.8rem; }
        .dev-card-body { padding: 0 22px 28px; }
    }

    @media (max-width: 480px) {
        .dev-info-grid { grid-template-columns: 1fr; }
        .dev-info-item.full { grid-column: 1; }
    }
</style>

<div class="about-wrapper">

    <header class="about-header">
        <h1>Creative Minds</h1>
        <p>ทีมผู้พัฒนาผู้ขับเคลื่อน BNCC Market สู่การเป็นอันดับ 1 ของวิทยาลัย</p>
    </header>

    <div class="team-grid">
        <?php foreach ($developers as $dev): ?>
        <div class="dev-card">

            <!-- Banner: รูปจริงของแต่ละคน + gradient overlay -->
            <div class="dev-card-banner">
                <?php if (!empty($dev['banner_img'])): ?>
                <img class="banner-bg"
                     src="<?= htmlspecialchars($dev['banner_img']) ?>"
                     alt=""
                     onerror="this.style.display='none'">
                <?php endif; ?>
            </div>

            <div class="dev-card-body">

                <!-- Avatar -->
                <div class="avatar-container">
                    <div class="avatar-ring"></div>
                    <img src="<?= htmlspecialchars($dev['avatar']) ?>"
                         alt="<?= htmlspecialchars($dev['name']) ?>"
                         class="dev-avatar"
                         onerror="this.src='../assets/images/profiles/default_profile.png'">
                </div>

                <!-- Name & Role -->
                <h2 class="dev-name"><?= htmlspecialchars($dev['name']) ?></h2>
                <div class="dev-nickname">"<?= htmlspecialchars($dev['nickname']) ?>"</div>
                <span class="dev-role-badge">
                    <i class="fas fa-code" style="margin-right:5px;"></i>
                    <?= htmlspecialchars($dev['role']) ?>
                </span>

                <!-- Info Grid -->
                <div class="dev-info-grid">
                    <div class="dev-info-item">
                        <div class="dev-info-label"><i class="fas fa-user"></i> อายุ</div>
                        <div class="dev-info-value"><?= htmlspecialchars($dev['age']) ?> ปี</div>
                    </div>
                    <div class="dev-info-item">
                        <div class="dev-info-label"><i class="fas fa-id-card"></i> รหัสนักศึกษา</div>
                        <div class="dev-info-value"><?= htmlspecialchars($dev['student_id']) ?></div>
                    </div>
                    <div class="dev-info-item full">
                        <div class="dev-info-label"><i class="fas fa-graduation-cap"></i> การศึกษา</div>
                        <div class="dev-info-value"><?= htmlspecialchars($dev['education']) ?></div>
                    </div>
                </div>

                <!-- Bio -->
                <p class="dev-bio"><?= htmlspecialchars($dev['bio']) ?></p>

                <!-- Skills -->
                <div class="dev-skills">
                    <?php foreach ($dev['skills'] as $skill): ?>
                    <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="dev-divider"></div>

                <!-- Social -->
                <div class="dev-social-stack">
                    <a href="<?= htmlspecialchars($dev['facebook']) ?>" target="_blank" class="social-btn" title="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                        </svg>
                    </a>
                    <a href="<?= htmlspecialchars($dev['instagram']) ?>" target="_blank" class="social-btn" title="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="<?= htmlspecialchars($dev['github']) ?>" target="_blank" class="social-btn" title="GitHub">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>
                        </svg>
                    </a>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Project Bar -->
    <div class="project-status-bar">
        <div class="status-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
            </svg>
        </div>
        <div>
            <h4 style="margin:0; font-weight:900; font-size:1.2rem; color:var(--text-main,#0f172a);">BNCC Student Marketplace Project</h4>
            <p style="margin:0; color:#64748b; font-weight:600;">Bangna Commercial College • Professional Development Edition</p>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>