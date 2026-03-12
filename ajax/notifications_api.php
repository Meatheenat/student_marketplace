<?php
/**
 * BNCC Market - Notifications API (V 4.0.0 - STABLE PATH)
 */
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'fetch') {

    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unread_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->execute([$user_id]);
    $unread_count = (int)$unread_stmt->fetchColumn();

    foreach ($notifs as &$n) {

        $n['time'] = date('d/m H:i', strtotime($n['created_at']));

        if (!empty($n['link']) && $n['link'] !== '#') {

            if (stripos($n['link'], 'http') !== 0) {

                $clean_link = trim($n['link']);

                $clean_link = str_replace('\\', '/', $clean_link);

                $remove = [
                    '../',
                    './',
                    '/s673190104/student_marketplace/',
                    's673190104/student_marketplace/',
                    '/student_marketplace/',
                    'student_marketplace/',
                    '/s673190104/',
                    's673190104/'
                ];

                $clean_link = str_ireplace($remove, '', $clean_link);

                $clean_link = ltrim($clean_link, '/');

                if (defined('BASE_URL')) {
                    $base = rtrim(BASE_URL, '/') . '/';
                } else {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                    $base = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/s673190104/student_marketplace/";
                }

                $n['link'] = $base . $clean_link;
            }
        }

        if ($n['type'] == 'order') {
            $n['icon'] = '<i class="fas fa-shopping-bag" style="color:#10b981;"></i>';
        } elseif ($n['type'] == 'review') {
            $n['icon'] = '<i class="fas fa-star" style="color:#f59e0b;"></i>';
        } elseif ($n['type'] == 'system') {
            $n['icon'] = '<i class="fas fa-cog" style="color:#6366f1;"></i>';
        } elseif ($n['type'] == 'danger') {
            $n['icon'] = '<i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i>';
        } else {
            $n['icon'] = '<i class="fas fa-bell" style="color:#94a3b8;"></i>';
        }
    }

    echo json_encode([
        'status' => 'success',
        'notifications' => $notifs,
        'unread_count' => $unread_count
    ]);
}

elseif ($action === 'check_new') {

    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $new_notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($new_notifs) > 0) {
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);
    }

    echo json_encode([
        'status' => 'success',
        'notifications' => $new_notifs
    ]);
}

elseif ($action === 'mark_read') {

    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);

    echo json_encode([
        'status' => 'success'
    ]);
}

else {

    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid action'
    ]);
}
