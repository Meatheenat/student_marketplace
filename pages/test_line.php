<?php
require_once '../includes/functions.php';

echo "<h2>🧪 กำลังทดสอบระบบแจ้งเตือน LINE (Audit Log)</h2>";

$message = "📢 ทดสอบจากระบบ BNCC Market\n"
         . "ผู้ทดสอบ: " . ($_SESSION['fullname'] ?? 'Guest') . "\n"
         . "เวลา: " . date('H:i:s');

$result = notifyAllAdmins($message);

echo "<b>ผลลัพธ์จากระบบ:</b><br>";
echo "<pre>";
var_dump($result); 
echo "</pre>";

if (str_contains($result, '"status":200')) {
    echo "<h3 style='color:green;'>✅ ส่งสำเร็จ! เช็คในกลุ่ม LINE ได้เลย</h3>";
} else {
    echo "<h3 style='color:red;'>❌ ส่งไม่สำเร็จ!</h3>";
    echo "<b>วิธีแก้:</b><br>";
    echo "1. เช็คว่า Token ถูกไหม (ต้องไม่มีช่องว่าง)<br>";
    echo "2. เช็คว่าดึง Bot เข้ากลุ่มหรือยัง?<br>";
}
?>