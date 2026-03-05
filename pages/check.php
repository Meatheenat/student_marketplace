<?php
// 🎯 ใส่ค่าที่มึงมั่นใจที่สุดลงไปตรงนี้
$u = 's673190104'; 
$p = 's673190104'; 
$d = 's673190104'; // เช็กดีๆ ว่าชื่อ DB กับชื่อ User คือตัวเดียวกันไหม

try {
    $c = new PDO("mysql:host=localhost;dbname=$d", $u, $p);
    echo "<h1 style='color:green'>✅ รอดแล้วไอ้เหี้ย! ข้อมูลนี้ถูกชัวร์</h1>";
} catch (PDOException $e) {
    echo "<h1 style='color:red'>❌ ยังพังอยู่!</h1>";
    echo "<b>คอมมันด่าว่า:</b> " . $e->getMessage();
    echo "<br><br><b>คำแนะนำ:</b> ถ้ามันยัง Access Denied แสดงว่า 'รหัสผิด' หรือ 'ยังไม่ผูก User เข้ากับ DB' ครับพลอย";
}