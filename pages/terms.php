<?php
/**
 * Student Marketplace - Public Terms of Service
 */
$pageTitle = "ระเบียบการซื้อขาย";
require_once '../includes/header.php';
?>

<div style="max-width: 800px; margin: 40px auto; background: var(--bg-card); padding: 40px; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border-color); color: var(--text-color); line-height: 1.6;">
    
    <h2 style="color: var(--primary-color); text-align: center; margin-bottom: 30px;">ระเบียบการซื้อขาย (Terms of Service)</h2>
    
    <p style="text-align: center; color: var(--text-muted); margin-bottom: 30px;">
        เพื่อให้ตลาดนัดออนไลน์ของพวกเราน่าอยู่และปลอดภัย โปรดอ่านและปฏิบัติตามกฎดังนี้ครับ
    </p>

    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 25px 0;">

    <section>
        <h3 style="color: var(--primary-color); margin-bottom: 15px;">1. คุณสมบัติของผู้ใช้งาน</h3>
        <ul style="margin-left: 20px; margin-bottom: 15px;">
            <li>ต้องเป็นนักเรียนหรือบุคลากรของ <strong>วิทยาลัยพณิชยการบางนา</strong> เท่านั้น</li>
            <li>ต้องใช้บัญชีอีเมล <strong>@bncc.ac.th</strong> ในการเข้าสู่ระบบเพื่อยืนยันตัวตน</li>
            <li>ห้ามแชร์บัญชีผู้ใช้งานให้ผู้อื่น หรือนำบัญชีไปใช้ในทางที่ผิด</li>
        </ul>
    </section>

    <section>
        <h3 style="color: var(--primary-color); margin-bottom: 15px;">2. กฎการลงขายสินค้า</h3>
        <ul style="margin-left: 20px; margin-bottom: 15px;">
            <li>ห้ามลงขายสินค้าที่ผิดกฎหมาย หรือผิดระเบียบของทางวิทยาลัย</li>
            <li>ห้ามลงขายสินค้าที่ลามก อนาจาร หรือสื่อไปในทางที่เสื่อมเสีย</li>
            <li>ข้อมูลสินค้าและรูปภาพต้องเป็นความจริง ไม่หลอกลวงผู้ซื้อ</li>
        </ul>
    </section>

    <section>
        <h3 style="color: var(--primary-color); margin-bottom: 15px;">3. การทำธุรกรรมและการนัดรับ</h3>
        <ul style="margin-left: 20px; margin-bottom: 15px;">
            <li>ระบบนี้เป็นเพียงสื่อกลางในการพบปะระหว่างผู้ซื้อและผู้ขายเท่านั้น</li>
            <li>การตกลงราคาและการชำระเงิน ให้กระทำผ่านการพูดคุยกันโดยตรง</li>
            <li>แนะนำให้ <strong>นัดรับสินค้าภายในบริเวณวิทยาลัย</strong> ในจุดที่ปลอดภัยเพื่อความมั่นใจทั้งสองฝ่าย</li>
        </ul>
    </section>

    <section>
        <h3 style="color: var(--primary-color); margin-bottom: 15px;">4. การระงับการใช้งาน</h3>
        <p>หากตรวจพบการกระทำที่ฝ่าฝืนกฎระเบียบ หรือมีการร้องเรียนว่ามีการโกงเกิดขึ้น ผู้ดูแลระบบมีสิทธิ์ระงับบัญชีผู้ใช้งานทันทีโดยไม่ต้องแจ้งให้ทราบล่วงหน้า 
            และจะมีการสืบสวนภายในโรงเรียน หากพบมีความผิดจะดำเนินการลงโทษตามกฏของโรงเรียน
        </p>
    </section>

    <div style="margin-top: 40px; padding: 20px; background: rgba(var(--primary-rgb), 0.1); border-radius: 8px; border-left: 4px solid var(--primary-color);">
        <p style="font-weight: 500; color: var(--primary-color);">ข้อควรระวัง:</p>
        <p style="font-size: 0.95rem;">โปรดระมัดระวังมิจฉาชีพและตรวจสอบสภาพสินค้าทุกครั้งก่อนทำการชำระเงิน ทางระบบจะไม่รับผิดชอบต่อความเสียหายจากการซื้อขายที่เกิดขึ้นระหว่างบุคคล</p>
    </div>

    <div style="margin-top: 40px; text-align: center;">
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 15px;">
            การสมัครสมาชิกถือว่าคุณยอมรับระเบียบการข้างต้นทั้งหมด
        </p>
        <a href="index.php" class="btn btn-primary" style="padding: 10px 25px;">ยอมรับ</a>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>