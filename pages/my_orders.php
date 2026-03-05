<?php
/**
 * BNCC Market - My Orders (Purchase History)
 * หน้าประวัติการสั่งซื้อสำหรับผู้ซื้อ
 */
require_once '../includes/functions.php';

// ต้องล็อกอินก่อนถึงจะดูได้
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "กรุณาเข้าสู่ระบบเพื่อดูประวัติการสั่งซื้อ";
    $_SESSION['flash_type'] = "warning";
    redirect('../auth/login.php');
}

$pageTitle = "ประวัติการสั่งซื้อของฉัน - BNCC Market";
require_once '../includes/header.php';

$db = getDB();
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลคำสั่งซื้อของตัวเอง
$stmt = $db->prepare("
    SELECT o.*, p.title as product_name, p.image_url, p.price, s.shop_name, s.user_id as seller_id
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN shops s ON o.shop_id = s.id
    WHERE o.buyer_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$my_orders = $stmt->fetchAll();
?>

<div class="container" style="max-width: 1000px; margin: 40px auto;">
    <h2 style="margin-bottom: 30px; display: flex; align-items: center; gap: 12px; font-weight: 800;">
        <i class="fas fa-shopping-bag text-primary"></i> ประวัติการสั่งซื้อของฉัน
    </h2>

    <?php echo displayFlashMessage(); ?>

    <div class="card" style="background: var(--bg-card); border-radius: 20px; overflow: hidden; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: var(--bg-body); border-bottom: 2px solid var(--border-color);">
                    <tr>
                        <th style="padding: 18px 20px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">รหัสคำสั่งซื้อ</th>
                        <th style="padding: 18px 20px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">สินค้า</th>
                        <th style="padding: 18px 20px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">ร้านค้า</th>
                        <th style="padding: 18px 20px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">สถานะ</th>
                        <th style="padding: 18px 20px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">ติดต่อผู้ขาย</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($my_orders) > 0): ?>
                        <?php foreach($my_orders as $o): 
                            // จัดการสีของ Badge สถานะให้ตรงกับที่คนขายเห็น
                            $badge_bg = '#e2e8f0'; $badge_color = '#475569'; $status_text = 'รอยืนยัน'; $icon = 'fa-clock';
                            if($o['status'] == 'pending') { $badge_bg = '#fef3c7'; $badge_color = '#d97706'; $status_text = 'รอยืนยัน'; $icon = 'fa-hourglass-half'; }
                            elseif($o['status'] == 'preparing') { $badge_bg = '#dbeafe'; $badge_color = '#2563eb'; $status_text = 'กำลังเตรียมของ'; $icon = 'fa-box-open'; }
                            elseif($o['status'] == 'completed') { $badge_bg = '#d1fae5'; $badge_color = '#059669'; $status_text = 'สำเร็จแล้ว'; $icon = 'fa-check-circle'; }
                            elseif($o['status'] == 'cancelled') { $badge_bg = '#fee2e2'; $badge_color = '#dc2626'; $status_text = 'ยกเลิก'; $icon = 'fa-times-circle'; }
                        ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: 0.2s;">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 700; color: var(--primary);">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></div>
                            </td>
                            <td style="padding: 15px 20px; display: flex; align-items: center; gap: 15px;">
                                <img src="<?= !empty($o['image_url']) ? '../assets/images/products/'.$o['image_url'] : 'https://via.placeholder.com/50' ?>" style="width: 50px; height: 50px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border-color);">
                                <div>
                                    <a href="product_detail.php?id=<?= $o['product_id'] ?>" style="font-weight: 600; color: var(--text-main); text-decoration: none; display: block; margin-bottom: 3px;"><?= e($o['product_name']) ?></a>
                                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--primary);">฿<?= number_format($o['price'], 2) ?></div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <a href="view_profile.php?id=<?= $o['seller_id'] ?>" style="color: var(--text-main); text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-store" style="color: #94a3b8;"></i> <?= e($o['shop_name']) ?>
                                </a>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="background: <?= $badge_bg ?>; color: <?= $badge_color ?>; padding: 6px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fas <?= $icon ?>"></i> <?= $status_text ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <a href="chat.php?user=<?= $o['seller_id'] ?>" class="btn btn-sm" style="background: #6366f1; color: white; border-radius: 10px; padding: 8px 15px;">
                                    <i class="fas fa-comment-dots"></i> ทักแชท
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 60px 20px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-shopping-basket" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                                <h3 style="color: var(--text-main);">คุณยังไม่มีประวัติการสั่งซื้อ</h3>
                                <p style="margin-bottom: 20px;">ลองไปเลือกดูสินค้าที่น่าสนใจในตลาดนัดดูสิ!</p>
                                <a href="index.php" class="btn btn-primary" style="border-radius: 12px;">ไปช้อปปิ้งกันเลย</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>