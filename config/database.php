<?php
/**
 * Student Marketplace - Database Connection
 * Author: Senior Full-Stack Developer
 * Updated: Timezone Fix for Thailand (GMT+7)
 */

// 🎯 1. ตั้งค่า Timezone ให้ PHP (ใช้สำหรับฟังก์ชัน date() ต่างๆ)
date_default_timezone_set('Asia/Bangkok');

// แปะบรรทัดนี้เพื่อเปิดดู Error จริงๆ บนหน้าเว็บ
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตั้งค่าพารามิเตอร์การเชื่อมต่อ
$host = 'localhost';
$db   = 's673190104';
$user = 's673190104'; // เปลี่ยนตามความเหมาะสมของ Server
$pass = 's673190104'; // เปลี่ยนตามความเหมาะสมของ Server
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
    
    // 🎯 2. ตั้งค่า Timezone ให้ MySQL Session (ใช้สำหรับ CURRENT_TIMESTAMP ใน Database)
    // แก้ปัญหาเวลาในตาราง notifications และ admin_logs ที่บันทึกช้าไป 7 ชั่วโมง
    $pdo->exec("SET time_zone = '+07:00'");

} catch (\PDOException $e) {
    // กรณีเชื่อมต่อไม่ได้ ให้แสดงข้อผิดพลาดและหยุดการทำงาน
    die("Database Connection Failed: " . $e->getMessage());
}

// ฟังก์ชันสำหรับเรียกใช้งาน $pdo ได้ทั่วถึง (Global)
function getDB() {
    global $pdo;
    return $pdo;
}
?>