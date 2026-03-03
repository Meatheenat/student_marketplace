/**
 * Student Marketplace - Main Script
 * Features: Theme Toggling, Mobile Menu, Image Preview, Form Validation
 * Author: Senior Full-Stack Developer
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Theme Manager (จัดการระบบสลับธีม มืด/สว่าง)
    const themeToggle = document.getElementById('theme-toggle');
    const html = document.documentElement;
    const themeIcon = document.getElementById('theme-icon');

    // ฟังก์ชันอัปเดตไอคอน
    function updateThemeIcon(isDark) {
        if (!themeIcon) return;
        if (isDark) {
            themeIcon.className = 'fas fa-sun'; // เปลี่ยนทั้ง class เพื่อความชัวร์ ป้องกันไอคอนซ้อน
        } else {
            themeIcon.className = 'fas fa-moon';
        }
    }

    // ตรวจสอบธีมปัจจุบัน (ทำซ้ำอีกครั้งเพื่อเซ็ตไอคอนให้ถูกต้องตอนโหลดหน้าเว็บ)
    const isDark = html.classList.contains('dark-theme');
    updateThemeIcon(isDark);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            // สลับคลาสที่ <html> และรับค่า boolean กลับมา
            const willBeDark = html.classList.toggle('dark-theme');
            
            // บันทึกลง LocalStorage
            localStorage.setItem('theme', willBeDark ? 'dark' : 'light');
            
            // อัปเดตไอคอน
            updateThemeIcon(willBeDark);
        });
    }

    // 2. Mobile Menu Toggle (เมนูสำหรับมือถือ)
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    if (menuBtn && navLinks) {
        menuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            // เพิ่ม Animation เล็กน้อยให้กับปุ่ม
            menuBtn.classList.toggle('open');
        });
    }

    // 3. Image Preview (แสดงตัวอย่างรูปภาพในหน้า Add Product)
    const imageInput = document.getElementById('product_image');
    const imagePreview = document.getElementById('image_preview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                imagePreview.style.display = 'block';
                
                reader.addEventListener('load', function() {
                    imagePreview.setAttribute('src', this.result);
                });
                
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
                imagePreview.setAttribute('src', '');
            }
        });
    }

    // 4. Delete Confirmation (ระบบยืนยันก่อนลบสินค้า)
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const confirmAction = confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?');
            if (!confirmAction) {
                e.preventDefault(); // ยกเลิกการลิงก์หรือการ Submit
            }
        });
    });

    // 5. Basic Form Validation (การตรวจสอบฟอร์มเบื้องต้น)
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const inputs = form.querySelectorAll('input[required], textarea[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid');
                    
                    // ลบ Class แดงๆ เมื่อมีการพิมพ์ใหม่
                    input.addEventListener('input', () => {
                        input.classList.remove('is-invalid');
                    }, { once: true });
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลในช่องที่จำเป็นให้ครบถ้วน');
            }
        });
    });

    // 6. Price Formatter (จัดรูปแบบราคาให้อัตโนมัติ)
    const priceInputs = document.querySelectorAll('.price-input');
    priceInputs.forEach(input => {
        input.addEventListener('blur', (e) => {
            let value = parseFloat(e.target.value);
            if (!isNaN(value)) {
                e.target.value = value.toFixed(2);
            }
        });
    });
});