<?php
/**
 * BNCC Market - Search Suggestions API
 */
require_once '../includes/functions.php';
header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

// ถ้าพิมพ์น้อยกว่า 2 ตัวอักษร ไม่ต้องค้นหาให้เปลือง Resource
if (mb_strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$db = getDB();
// ค้นหาเฉพาะสินค้าที่ผ่านการอนุมัติ (approved) และยังไม่ถูกลบ (is_deleted = 0)
$stmt = $db->prepare("SELECT id, title, image_url, price FROM products 
                      WHERE title LIKE ? AND status = 'approved' AND is_deleted = 0 
                      LIMIT 6");
$stmt->execute(['%' . $query . '%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);