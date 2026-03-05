</main> <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* 🛠️ ปรับแต่ง SweetAlert2 ให้เข้ากับธีม Solid High-Contrast ของเรา */
    .swal2-popup {
        background: var(--solid-card, var(--bg-card, #ffffff)) !important;
        color: var(--solid-text, var(--text-main, #0f172a)) !important;
        border: 2px solid var(--solid-border, var(--border-color, #cbd5e1)) !important;
        border-radius: 24px !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        font-family: 'Prompt', sans-serif !important;
    }

    .swal2-title {
        font-weight: 900 !important;
        font-size: 1.6rem !important;
    }

    .swal2-html-container {
        font-weight: 500 !important;
        font-size: 1.05rem !important;
        color: var(--text-muted, #64748b) !important;
    }

    .swal2-confirm, .swal2-cancel {
        border-radius: 14px !important;
        font-weight: 800 !important;
        padding: 12px 30px !important;
        font-size: 1rem !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }

    .swal2-confirm:hover, .swal2-cancel:hover {
        transform: translateY(-3px) !important;
    }

    /* ปุ่มยืนยัน */
    .swal2-confirm {
        background: var(--solid-primary, var(--primary, #4f46e5)) !important;
        box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4) !important;
    }

    /* ปุ่มยกเลิก */
    .swal2-cancel {
        background: var(--solid-bg, var(--bg-body, #f1f5f9)) !important;
        border: 2px solid var(--solid-border, var(--border-color, #cbd5e1)) !important;
        color: var(--solid-text, var(--text-main, #0f172a)) !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        /**
         * 🪄 SCRIPT เวทมนตร์: แปลง confirm() ธรรมดา ให้เป็น SweetAlert2 อัตโนมัติ
         * ไม่ต้องไปแก้ PHP ทุกหน้า สคริปต์นี้จัดการให้หมด!
         */
        const confirmElements = document.querySelectorAll('[onclick*="return confirm"]');
        
        confirmElements.forEach(el => {
            // 1. ดึงข้อความเดิมออกมาจาก onclick
            const onclickText = el.getAttribute('onclick');
            const match = onclickText.match(/confirm\(['"](.*?)['"]\)/);
            const msg = match ? match[1] : 'คุณต้องการดำเนินการนี้ใช่หรือไม่?';

            // 2. ลบ onclick เดิมที่เป็นกากหมาทิ้งไป
            el.removeAttribute('onclick');

            // 3. ใส่ Event Listener ใหม่แบบพรีเมียมเข้าไปแทน
            el.addEventListener('click', function(e) {
                e.preventDefault(); // หยุดการทำงานปกติไว้ก่อน
                
                Swal.fire({
                    title: 'ยืนยันการทำรายการ',
                    text: msg,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check-circle"></i> ยืนยัน',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true // สลับปุ่มยกเลิกไว้ซ้าย ยืนยันไว้ขวา (ตามหลัก UX)
                }).then((result) => {
                    if (result.isConfirmed) {
                        // ถ้าเป็นแท็ก <a> (ลิงก์)
                        if (el.tagName.toUpperCase() === 'A') {
                            window.location.href = el.href;
                        } 
                        // ถ้าเป็นปุ่ม <button> ใน <form>
                        else if (el.closest('form')) {
                            const form = el.closest('form');
                            // 💡 ทริคสำคัญ: ถ้าปุ่มมี name (เช่น name="place_order") ต้องสร้าง input ซ่อนส่งไปด้วย ไม่งั้น PHP จับไม่ได้
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

        /**
         * 🪄 แปลง alert() ธรรมดา (เช่นในหน้า product_detail ที่ให้ล็อกอินก่อนซื้อ) ให้เป็น SweetAlert2
         */
        const alertElements = document.querySelectorAll('[onclick*="alert("]');
        alertElements.forEach(el => {
            const onclickText = el.getAttribute('onclick');
            // เช็กว่าไม่ใช่ SweetAlert นะ
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
                        // ถ้ามีลิงก์ค่อยให้ไปต่อหลังจากกดตกลง
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