<?php
/**
 * Student Marketplace - Privacy Policy Page
 */
$pageTitle = "นโยบายความเป็นส่วนตัว";
require_once '../includes/header.php';
?>

<div style="max-width: 800px; margin: 40px auto; background: var(--bg-card); padding: 40px; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border-color); color: var(--text-color); line-height: 1.6;">
    
    <h2 style="color: var(--primary-color); text-align: center; margin-bottom: 30px;">นโยบายความเป็นส่วนตัว (Privacy Policy)</h2>
    
    <p style="margin-bottom: 20px;">
        ยินดีต้อนรับสู่ <strong>Student Marketplace</strong> เราให้ความสำคัญกับความเป็นส่วนตัวของคุณ ข้อมูลนี้จะอธิบายถึงวิธีการที่เราเก็บรวบรวม ใช้ และป้องกันข้อมูลส่วนบุคคลของนักเรียนที่ใช้งานระบบของเรา
    </p>

    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 25px 0;">

    <section>
        <h3 style="color: var(--primary-color); margin-bottom: 15px;">1. ข้อมูลที่เราจัดเก็บ</h3>
        <p>เราจัดเก็บข้อมูลที่จำเป็นสำหรับการระบุตัวตนในการซื้อขายภายในวิทยาลัยเท่านั้น ได้แก่:</p>
        <ul style="margin-left: 20px; margin-bottom: 15px;">
            <li>ชื่อ-นามสกุล</li>
            <li>รหัสนักเรียน (ตัวอย่าง: 65xxxxxxxx)</li>
            <li>แผนกวิชา และระดับชั้น (ปวช. / ปวส.)</li>
            <li>อีเมลวิทยาลัย (ลงท้ายด้วย <strong>@bncc.ac.th</strong> เท่านั้น)</li>
        </ul>
    </section>

    <section>
        <h3 style="color: var(--primary-color); margin-bottom: 15px;">2. การใช้งานข้อมูล</h3>
        <p>ข้อมูลของคุณจะถูกนำไปใช้เพื่อวัตถุประสงค์ดังต่อไปนี้:</p>
        <ul style="margin-left: 20px; margin-bottom: 15px;">
            <li>เพื่อยืนยันตัวตนสมาชิกว่าเป็นนักเรียนของวิทยาลัยจริง</li>
            <li>เพื่อใช้ในการติดต่อสื่อสารระหว่างผู้ซื้อและผู้ขายในตลาดนัดออนไลน์</li>
            <li>เพื่อรักษาความปลอดภัยและป้องกันการแอบอ้างตัวตน</li>
        </ul>
    </section>

    <section>
        <h3 style="color: var(--primary-color); margin-bottom: 15px;">3. การเข้าสู่ระบบด้วย Google (OAuth 2.0)</h3>
        <p>เมื่อคุณใช้บริการ <strong>Login with Google</strong> ระบบจะดึงข้อมูลชื่อและอีเมลของคุณมาเพื่อสร้างบัญชีโดยอัตโนมัติ ทั้งนี้เราจะอนุญาตให้เข้าใช้งานได้เฉพาะบัญชีที่ใช้โดเมนของวิทยาลัยเท่านั้น</p>
    </section>

    <section>
        <h3 style="color: var(--primary-color); margin-bottom: 15px;">4. การรักษาความปลอดภัยของข้อมูล</h3>
        <p>เราใช้มาตรการทางเทคนิคที่เหมาะสมเพื่อป้องกันการเข้าถึงข้อมูลโดยไม่ได้รับอนุญาต รวมถึงการเข้ารหัสรหัสผ่าน (Password Hashing) ก่อนบันทึกลงฐานข้อมูลทุกครั้ง</p>
    </section>

    <section>
        <h3 style="color: var(--primary-color); margin-bottom: 15px;">5. สิทธิ์ของผู้ใช้งาน</h3>
        <p>คุณมีสิทธิ์ในการเข้าถึง แก้ไข หรือขอลบข้อมูลส่วนบุคคลของคุณออกจากระบบได้ โดยการติดต่อผู้ดูแลระบบผ่านช่องทางที่กำหนด</p>
    </section>

    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center; font-size: 0.9rem; color: var(--text-muted);">
        <p>อัปเดตล่าสุดเมื่อ: <?php echo date('d/m/Y'); ?></p>
        <a href="index.php" class="btn btn-primary" style="display: inline-block; margin-top: 15px; text-decoration: none;">กลับสู่หน้าแรก</a>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>