<?php
/**
 * Student Marketplace - Database Connection
 * Author: Senior Full-Stack Developer
 */

// ตั้งค่าพารามิเตอร์การเชื่อมต่อ
$host = 'localhost';
$db   = 's673190104';
$user = 's673190104'; // เปลี่ยนตามความเหมาะสมของ Server
$pass = 's673190104';     // เปลี่ยนตามความเหมาะสมของ Server
$charset = 'utf8mb4';

// ตั้งค่า Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// กำหนด Options สำหรับ PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // แจ้งเตือนข้อผิดพลาดเป็น Exception
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // ดึงข้อมูลออกมาเป็น Array แบบ Assoc
    PDO::ATTR_EMULATE_PREPARES   => false,                  // ใช้ Prepared Statements จริง ไม่ใช่การเลียนแบบ
];

try {
    // สร้าง Instance ของ PDO
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // กรณีเชื่อมต่อไม่ได้ ให้แสดงข้อผิดพลาดและหยุดการทำงาน (ใน Production ควรเปลี่ยนเป็น Log แทน)
    die("Database Connection Failed: " . $e->getMessage());
}

// ฟังก์ชันสำหรับเรียกใช้งาน $pdo ได้ทั่วถึง (Global)
function getDB() {
    global $pdo;
    return $pdo;
}
?>