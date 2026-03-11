<?php
session_start();
require_once '../includes/functions.php';

// 🛡️ เช็คสิทธิ์ Admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $post_id = (int)$_POST['post_id'];
    $action = $_POST['action'];

    // ดึงข้อมูลโพสต์เพื่อเอา User ID มาส่งแจ้งเตือน
    $stmt = $db->prepare("SELECT user_id, title FROM wtb_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post) {
        if ($action === 'approve') {
            // ✅ อนุมัติ เปลี่ยนเป็น active
            $update = $db->prepare("UPDATE wtb_posts SET status = 'active' WHERE id = ?");
            if ($update->execute([$post_id])) {
                sendNotification($post['user_id'], 'system', "✅ โพสต์ตามหาของ '{$post['title']}' ได้รับการอนุมัติแล้ว", "../pages/wtb_board.php");
                $_SESSION['flash_message'] = "อนุมัติโพสต์เรียบร้อยแล้ว";
                $_SESSION['flash_type'] = "success";
            }
        } elseif ($action === 'reject') {
            // ❌ ไม่อนุมัติ เปลี่ยนเป็น rejected
            $update = $db->prepare("UPDATE wtb_posts SET status = 'rejected' WHERE id = ?");
            if ($update->execute([$post_id])) {
                sendNotification($post['user_id'], 'system', "❌ โพสต์ตามหาของ '{$post['title']}' ไม่ผ่านการตรวจสอบ", "#");
                $_SESSION['flash_message'] = "ไม่อนุมัติโพสต์เรียบร้อยแล้ว";
                $_SESSION['flash_type'] = "warning";
            }
        }
    }
    
    redirect('approve_wtb.php');
}