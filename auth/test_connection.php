<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 📍 แก้ข้อมูล 4 บรรทัดนี้ให้ตรงกับหน้าจัดการโฮสต์ของมึงเป๊ะๆ
$host = 'localhost';
$db   = 's673190104_db';   // 🎯 เช็ก Prefix ในหน้า Control Panel
$user = 's673190104_user'; // 🎯 เช็ก Prefix ในหน้า Control Panel
$pass = 'รหัสผ่านที่มึงตั้งไว้';

echo "<h3>🚀 กำลังทดสอบการเชื่อมต่อ...</h3>";

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<h4 style='color:green;'>✅ เชื่อมต่อสำเร็จ! มึงก๊อปค่าด้านบนไปใส่ใน database.php ได้เลย</h4>";
} catch (PDOException $e) {
    echo "<h4 style='color:red;'>❌ เชื่อมต่อล้มเหลว!</h4>";
    echo "<b>สาเหตุจาก Server:</b> " . $e->getMessage();
    
    echo "<hr><h4>💡 คำแนะนำสำหรับสาย IT Support:</h4>";
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "1. มึงใส่ <b>User</b> หรือ <b>Password</b> ผิด<br>";
        echo "2. มึงยังไม่ได้ <b>Add User to Database</b> ในหน้าโฮสต์<br>";
        echo "3. มึงลืมใส่ <b>Prefix</b> ที่โฮสต์บังคับ (เช่น u673190104_)";
    }
}
?>