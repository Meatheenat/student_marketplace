</main> <style>
    /* 🛠️ สไตล์ Footer 7 องค์ประกอบ (Responsive & High-Contrast) */
    .site-footer {
        background: var(--solid-card, #ffffff);
        border-top: 2px solid var(--solid-border, #cbd5e1);
        padding: 60px 0 20px;
        margin-top: 80px;
        color: var(--solid-text, #0f172a);
        position: relative;
        z-index: 10;
        width: 100%;
        clear: both;
    }
    .dark-theme .site-footer {
        background: var(--solid-card, #1e293b);
        border-top: 2px solid var(--solid-border, #334155);
    }
    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 40px;
        margin-bottom: 40px;
    }
    
    /* 1. ข้อมูลเว็บ & 5. ติดต่อ & 6. Social */
    .footer-brand h3 { font-size: 1.8rem; font-weight: 900; margin-bottom: 15px; color: var(--solid-primary, #4f46e5); letter-spacing: -1px; }
    .footer-brand p { color: var(--text-muted, #64748b); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; }
    .footer-contact { font-size: 0.9rem; color: var(--text-muted, #64748b); margin-bottom: 20px; }
    .footer-contact i { color: var(--solid-primary, #4f46e5); width: 20px; }
    .footer-social a { 
        display: inline-flex; justify-content: center; align-items: center; 
        width: 40px; height: 40px; border-radius: 10px; 
        background: var(--solid-bg, #f1f5f9); color: var(--solid-text, #0f172a); 
        margin-right: 10px; font-size: 1.2rem; transition: 0.3s; 
    }
    .footer-social a:hover { background: var(--solid-primary, #4f46e5); color: #fff; transform: translateY(-3px); }
    .dark-theme .footer-social a { background: var(--solid-border, #334155); }

    /* 2, 3, 4. เมนูต่างๆ */
    .footer-links h4 { font-weight: 800; font-size: 1.1rem; margin-bottom: 20px; color: var(--solid-text, #0f172a); }
    .footer-links ul { list-style: none; padding: 0; margin: 0; }
    .footer-links ul li { margin-bottom: 12px; }
    .footer-links ul li a {
        color: var(--text-muted, #64748b); text-decoration: none; font-size: 0.95rem; font-weight: 600;
        transition: color 0.2s, padding-left 0.2s; display: inline-block;
    }
    .footer-links ul li a:hover { color: var(--solid-primary, #4f46e5); padding-left: 5px; }

    /* 7. Copyright */
    .footer-bottom {
        text-align: center; padding-top: 25px; border-top: 1px solid var(--solid-border, #cbd5e1);
        color: var(--text-muted, #64748b); font-size: 0.85rem; font-weight: 600;
    }

    @media (max-width: 992px) {
        .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 576px) {
        .footer-grid { grid-template-columns: 1fr; gap: 30px; }
    }
</style>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>BNCC Market</h3>
                <p>ตลาดนัดออนไลน์สำหรับนักศึกษา พื้นที่ซื้อ-ขาย แลกเปลี่ยนสินค้า สะดวก ปลอดภัย ภายในวิทยาลัย</p>
                <div class="footer-contact">
                    <div style="margin-bottom: 8px;"><i class="fas fa-map-marker-alt"></i> วิทยาลัยพณิชยการบางนา</div>
                    <div style="margin-bottom: 8px;"><i class="fas fa-envelope"></i> support@bncc.ac.th</div>
                </div>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-line"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <div class="footer-links">
                <h4>สำรวจตลาด</h4>
                <ul>
                    <li><a href="<?= defined('BASE_URL') ? BASE_URL : '../' ?>pages/index.php"><i class="fas fa-angle-right"></i> หน้าแรก</a></li>
                    <li><a href="<?= defined('BASE_URL') ? BASE_URL : '../' ?>pages/index.php"><i class="fas fa-angle-right"></i> สินค้าทั้งหมด</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> ร้านค้านักศึกษา</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> กระดานตามหาของ (WTB)</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>บัญชีของฉัน</h4>
                <ul>
                    <?php if(isLoggedIn()): ?>
                        <li><a href="<?= defined('BASE_URL') ? BASE_URL : '../' ?>pages/profile.php"><i class="fas fa-angle-right"></i> โปรไฟล์ส่วนตัว</a></li>
                        <li><a href="<?= defined('BASE_URL') ? BASE_URL : '../' ?>pages/my_orders.php"><i class="fas fa-angle-right"></i> การสั่งซื้อของฉัน</a></li>
                        <li><a href="<?= defined('BASE_URL') ? BASE_URL : '../' ?>pages/my_shop.php"><i class="fas fa-angle-right"></i> จัดการร้านค้า</a></li>
                    <?php else: ?>
                        <li><a href="<?= defined('BASE_URL') ? BASE_URL : '../' ?>auth/login.php"><i class="fas fa-angle-right"></i> เข้าสู่ระบบ / สมัครสมาชิก</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-links">
                <h4>ช่วยเหลือ & นโยบาย</h4>
                <ul>
                    <li><a href="#"><i class="fas fa-angle-right"></i> วิธีการสั่งซื้อและนัดรับ</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> ข้อตกลงการใช้งาน (Terms)</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> นโยบายความเป็นส่วนตัว</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> แจ้งปัญหาสินค้า / รายงานการโกง</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> BNCC Market. All rights reserved.
        </div>
    </div>
</footer>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .swal2-popup {
        background: var(--solid-card, var(--bg-card, #ffffff)) !important;
        color: var(--solid-text, var(--text-main, #0f172a)) !important;
        border: 2px solid var(--solid-border, var(--border-color, #cbd5e1)) !important;
        border-radius: 24px !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        font-family: 'Prompt', sans-serif !important;
    }
    .swal2-title { font-weight: 900 !important; font-size: 1.6rem !important; }
    .swal2-html-container { font-weight: 500 !important; font-size: 1.05rem !important; color: var(--text-muted, #64748b) !important; }
    .swal2-confirm, .swal2-cancel { border-radius: 14px !important; font-weight: 800 !important; padding: 12px 30px !important; font-size: 1rem !important; transition: transform 0.2s ease, box-shadow 0.2s ease !important; }
    .swal2-confirm:hover, .swal2-cancel:hover { transform: translateY(-3px) !important; }
    .swal2-confirm { background: var(--solid-primary, var(--primary, #4f46e5)) !important; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4) !important; }
    .swal2-cancel { background: var(--solid-bg, var(--bg-body, #f1f5f9)) !important; border: 2px solid var(--solid-border, var(--border-color, #cbd5e1)) !important; color: var(--solid-text, var(--text-main, #0f172a)) !important; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const confirmElements = document.querySelectorAll('[onclick*="return confirm"]');
        confirmElements.forEach(el => {
            const onclickText = el.getAttribute('onclick');
            const match = onclickText.match(/confirm\(['"](.*?)['"]\)/);
            const msg = match ? match[1] : 'คุณต้องการดำเนินการนี้ใช่หรือไม่?';
            el.removeAttribute('onclick');
            el.addEventListener('click', function(e) {
                e.preventDefault(); 
                Swal.fire({
                    title: 'ยืนยันการทำรายการ',
                    text: msg,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check-circle"></i> ยืนยัน',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true 
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (el.tagName.toUpperCase() === 'A') {
                            window.location.href = el.href;
                        } 
                        else if (el.closest('form')) {
                            const form = el.closest('form');
                            if (el.name) {
                                const hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = el.name;
                                hiddenInput.value = el.value || '1';
                                form.appendChild(hiddenInput);
                            }
                            form.submit();
                        }
                    }
                });
            });
        });

        const alertElements = document.querySelectorAll('[onclick*="alert("]');
        alertElements.forEach(el => {
            const onclickText = el.getAttribute('onclick');
            if(!onclickText.includes('Swal')) {
                const match = onclickText.match(/alert\(['"](.*?)['"]\)/);
                const msg = match ? match[1] : 'มีการแจ้งเตือนจากระบบ';
                el.removeAttribute('onclick');
                el.addEventListener('click', function(e) {
                    if(el.tagName.toUpperCase() === 'A') e.preventDefault();
                    Swal.fire({
                        title: 'แจ้งเตือน',
                        text: msg,
                        icon: 'warning',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        if (el.tagName.toUpperCase() === 'A' && el.href && !el.href.includes('#')) {
                            window.location.href = el.href;
                        }
                    });
                });
            }
        });
    });
</script>

</body>
</html>