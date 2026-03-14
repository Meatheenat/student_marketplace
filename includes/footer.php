</main> <?php
// 🛠️ ตรวจสอบ Path พื้นฐานเพื่อให้ลิงก์ไม่พัง
$basePath = defined('BASE_URL') ? BASE_URL : '/s673190104/student_marketplace/';
?>

<style>
    /* ============================================================
       💎 MODERN SOLID FOOTER DESIGN SYSTEM
       ============================================================ */
    .site-footer {
        background: var(--solid-card, #ffffff);
        border-top: 2px solid var(--solid-border, #e2e8f0);
        padding: 80px 0 30px;
        margin-top: 100px;
        color: var(--solid-text, #0f172a);
        position: relative;
        z-index: 50;
        width: 100%;
        clear: both;
    }

    .dark-theme .site-footer {
        background: #0f172a;
        border-top: 2px solid #1e293b;
    }

    .footer-container {
        max-width: 1300px;
        margin: 0 auto;
        padding: 0 25px;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1fr;
        gap: 50px;
        margin-bottom: 60px;
    }

    /* --- ส่วน Brand & Social --- */
    .footer-brand h3 { 
        font-size: 2rem; 
        font-weight: 900; 
        margin-bottom: 20px; 
        color: var(--solid-primary, #4f46e5); 
        letter-spacing: -1.5px; 
    }
    
    .footer-brand p { 
        color: var(--text-muted, #64748b); 
        font-size: 1rem; 
        line-height: 1.8; 
        margin-bottom: 25px; 
        max-width: 350px;
    }

    .footer-contact-item {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        color: var(--text-muted, #64748b);
        font-weight: 600;
        font-size: 0.95rem;
    }

    .footer-contact-item i {
        width: 35px;
        height: 35px;
        background: var(--solid-bg, #f1f5f9);
        color: var(--solid-primary, #4f46e5);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1rem;
    }

    .footer-social { display: flex; gap: 12px; margin-top: 30px; }
    .footer-social a { 
        width: 45px; height: 45px; border-radius: 12px; 
        background: var(--solid-primary, #4f46e5); color: #fff; 
        display: flex; justify-content: center; align-items: center; 
        font-size: 1.2rem; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
    }
    .footer-social a:hover { transform: translateY(-8px) rotate(8deg); box-shadow: 0 15px 25px rgba(79, 70, 229, 0.4); }

    /* --- ส่วน Links --- */
    .footer-links h4 { 
        font-weight: 900; 
        font-size: 1.2rem; 
        margin-bottom: 25px; 
        color: var(--solid-text, #0f172a); 
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .footer-links ul { list-style: none; }
    .footer-links ul li { margin-bottom: 15px; }
    .footer-links ul li a {
        color: var(--text-muted, #64748b);
        text-decoration: none;
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .footer-links ul li a::before {
        content: ''; width: 0; height: 2px; background: var(--solid-primary, #4f46e5);
        transition: width 0.3s ease;
    }

    .footer-links ul li a:hover { color: var(--solid-primary, #4f46e5); }
    .footer-links ul li a:hover::before { width: 15px; }

    /* --- ส่วน Bottom --- */
    .footer-bottom {
        padding: 30px 0;
        border-top: 1px solid var(--solid-border, #e2e8f0);
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--text-muted, #64748b);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .footer-bottom-links { display: flex; gap: 25px; }
    .footer-bottom-links a:hover { color: var(--solid-primary); }

    /* --- Responsive --- */
    @media (max-width: 1024px) {
        .footer-grid { grid-template-columns: 1fr 1fr; gap: 40px; }
    }
    @media (max-width: 640px) {
        .footer-grid { grid-template-columns: 1fr; }
        .footer-bottom { flex-direction: column; gap: 20px; text-align: center; }
        .site-footer { padding: 60px 0 30px; }
    }
</style>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>BNCC Market</h3>
                <p>แหล่งรวมสินค้าและบริการที่ดีที่สุดสำหรับชาวพณิชยการบางนา ซื้อขายปลอดภัย นัดรับง่ายในรั้ววิทยาลัย</p>
                
                <div class="footer-contact">
                    <div class="footer-contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>วิทยาลัยพณิชยการบางนา, กรุงเทพฯ</span>
                    </div>
                    <div class="footer-contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>support@bnccmarket.com</span>
                    </div>
                </div>

                <div style="margin: 25px 0;">
                    <a href="<?= $basePath ?>pages/about.php" style="display: inline-flex; align-items: center; gap: 10px; background: var(--solid-primary, #4f46e5); color: #fff; padding: 12px 24px; border-radius: 14px; text-decoration: none; font-weight: 800; font-size: 0.95rem; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 8px 15px rgba(79, 70, 229, 0.25);">
                        <i class="fas fa-info-circle"></i> เกี่ยวกับเรา (About Us)
                    </a>
                </div>

                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="LINE"><i class="fab fa-line"></i></a>
                    <a href="https://www.instagram.com/__r._.wang_/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <div class="footer-links">
                <h4>สำรวจตลาด</h4>
                <ul>
                    <li><a href="<?= $basePath ?>pages/index.php">สินค้าทั้งหมด</a></li>
                    <li><a href="<?= $basePath ?>pages/shops.php">ร้านค้านักศึกษา</a></li>
                    <li><a href="<?= $basePath ?>pages/wtb_board.php">กระดานตามหาของ</a></li>
                    <li><a href="<?= $basePath ?>pages/barter_board.php">ระบบแลกเปลี่ยน</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>จัดการบัญชี</h4>
                <ul>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="<?= $basePath ?>pages/profile.php">ข้อมูลส่วนตัว</a></li>
                        <li><a href="<?= $basePath ?>pages/my_orders.php">ประวัติการสั่งซื้อ</a></li>
                        <li><a href="<?= $basePath ?>seller/dashboard.php">ศูนย์ผู้ขาย (Seller)</a></li>
                        <li><a href="<?= $basePath ?>auth/logout.php" style="color: var(--adm-danger, #ef4444);">ออกจากระบบ</a></li>
                    <?php else: ?>
                        <li><a href="<?= $basePath ?>auth/login.php">เข้าสู่ระบบ</a></li>
                        <li><a href="<?= $basePath ?>auth/register.php">สมัครสมาชิกใหม่</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-links">
                <h4>การสนับสนุน</h4>
                <ul>
                    <li><a href="<?= $basePath ?>pages/how-to.php">คู่มือการซื้อขาย</a></li>
                    <li><a href="<?= $basePath ?>pages/terms.php">ข้อตกลงและเงื่อนไข</a></li>
                    <li><a href="<?= $basePath ?>pages/privacy.php">นโยบายข้อมูลส่วนบุคคล</a></li>
                    <li><a href="<?= $basePath ?>auth/submit_report.php" style="color: var(--adm-warning, #f59e0b);">แจ้งปัญหา/รายงานโกง</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="copyright">
                &copy; <?= date('Y') ?> BNCC Market - Project by Student 673190104.
            </div>
            <div class="footer-bottom-links">
                <span>Enterprise v3.5.0</span>
            </div>
        </div>
    </div>
</footer>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Custom Swal2 for Solid Identity */
    .swal2-popup {
        background: var(--solid-card, #ffffff) !important;
        border: 2px solid var(--solid-border, #cbd5e1) !important;
        border-radius: 30px !important;
        padding: 2rem !important;
        font-family: 'Prompt', sans-serif !important;
    }
    .swal2-title { font-weight: 900 !important; color: var(--solid-text, #0f172a) !important; }
    .swal2-confirm { background: var(--solid-primary, #4f46e5) !important; border-radius: 15px !important; font-weight: 800 !important; padding: 12px 35px !important; }
    .swal2-cancel { background: #f1f5f9 !important; color: #64748b !important; border-radius: 15px !important; font-weight: 800 !important; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // 🎯 1. Auto-convert native confirm() to SweetAlert2
        const confirmElements = document.querySelectorAll('[onclick*="return confirm"]');
        confirmElements.forEach(el => {
            const originalOnclick = el.getAttribute('onclick');
            const match = originalOnclick.match(/confirm\(['"](.*?)['"]\)/);
            const msg = match ? match[1] : 'คุณต้องการดำเนินการนี้ใช่หรือไม่?';
            
            el.removeAttribute('onclick');
            el.addEventListener('click', function(e) {
                e.preventDefault(); 
                Swal.fire({
                    title: 'ยืนยันการทำรายการ',
                    text: msg,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'ใช่, ดำเนินการเลย',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true 
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (el.tagName === 'A') {
                            window.location.href = el.href;
                        } else if (el.type === 'submit' || el.closest('form')) {
                            el.closest('form').submit();
                        }
                    }
                });
            });
        });

        // 🎯 2. Flash Message Handler (PHP Session)
        <?php if(isset($_SESSION['flash_message'])): ?>
            Swal.fire({
                title: 'ผลการทำงาน',
                text: '<?= $_SESSION['flash_message'] ?>',
                icon: '<?= $_SESSION['flash_type'] ?? 'success' ?>',
                confirmButtonText: 'ตกลง',
                timer: 4000,
                timerProgressBar: true
            });
            <?php 
            unset($_SESSION['flash_message']); 
            unset($_SESSION['flash_type']); 
            ?>
        <?php endif; ?>
    });
</script>

</body>
</html>