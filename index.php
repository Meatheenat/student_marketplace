<?php
/**
 * Student Marketplace - Root Index
 * ทำหน้าที่เป็นประตูหน้าบ้านเพื่อตรวจสอบการล็อกอิน
 */
require_once 'includes/functions.php';

// ตรวจสอบสถานะการล็อกอิน
if (!isLoggedIn()) {
    // ถ้ายังไม่ได้ล็อกอิน ให้ส่งไปหน้า Login
    header('Location: auth/login.php');
    exit();
} else {
    // ถ้าล็อกอินแล้ว ให้ส่งไปหน้าแรกของระบบ (เช่น pages/index.php)
    header('Location: pages/index.php');
    exit();
}