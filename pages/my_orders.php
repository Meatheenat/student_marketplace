<?php
/**
 * BNCC Market - My Orders (Purchase History)
 * [SOLID HIGH-CONTRAST REDESIGN]
 * Project: BNCC Student Marketplace [Cite: User Summary]
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

<style>
    /* ============================================================
       🛠️ SOLID DESIGN SYSTEM - HIGH CONTRAST (ORDER HISTORY)
       ============================================================ */
    :root {
        --solid-bg: #f8fafc;
        --solid-card: #ffffff;
        --solid-text: #0f172a;
        --solid-border: #cbd5e1;
        --solid-primary: #4f46e5;
    }

    .dark-theme {
        --solid-bg: #0b0e14;
        --solid-card: #161b26;
        --solid-text: #ffffff;
        --solid-border: #2d3748;
        --solid-primary: #6366f1;
    }

    body {
        background-color: var(--solid-bg) !important;
        color: var(--solid-text);
        transition: background 0.3s ease;
    }

    .orders-wrapper {
        max-width: 1000px;
        margin: 40px auto 80px;
        padding: 0 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 3px solid var(--solid-border);
        opacity: 0;
        transform: translateY(-20px);
        animation: dropIn 0.6s ease forwards;
    }

    .page-header h2 {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--solid-text);
        letter-spacing: -1px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .page-header h2 i { color: var(--solid-primary); }

    .order-count-badge {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        padding: 8px 20px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 1rem;
        color: var(--solid-text);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    /* 🧱 Order List Style (Table) */
    .table-container {
        background: var(--solid-card);
        border: 2px solid var(--solid-border);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        opacity: 0;
        transform: translateY(30px);
        animation: dropIn 0.8s ease 0.2s forwards;
    }

    .solid-table { width: 100%; border-collapse: collapse; }
    .solid-table th {
        background: var(--solid-bg);
        padding: 20px;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        border-bottom: 2px solid var(--solid-border);
    }
    .solid-table td { padding: 20px; border-bottom: 1px solid var(--solid-border); font-weight: 600; vertical-align: middle; transition: background 0.2s; }
    .solid-table tr:last-child td { border-bottom: none; }
    .solid-table tr:hover td { background: rgba(79, 70, 229, 0.03); }

    /* Product Section in Table */
    .order-product-info {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .order-img {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid var(--solid-border);
        background: var(--solid-bg);
    }

    .order-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--solid-text);
        text-decoration: none;
        margin-bottom: 5px;
        display: block;
        transition: 0.2s;
    }
    .order-title:hover { color: var(--solid-primary); text-decoration: underline; }

    .order-price {
        font-size: 1.1rem;
        font-weight: 900;
        color: var(--solid-text);
    }

    /* 🚦 Status Badge */
    .status-badge {
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 2px solid transparent;
    }

    /* 💬 Chat Button */
    .btn-chat {
        background: var(--solid-primary);
        color: #fff !important;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 0.9rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: 0.2s;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
    }
    .btn-chat:hover { filter: brightness(1.15); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3); }

    /* 📱 Responsive Adjustment */
    @media (max-width: 768px) {
        .page-header { flex-direction: column; gap: 15px; align-items: flex-start; }
        .solid-table th { display: none; }
        .solid-table tr { display: block; border-bottom: 2px solid var(--solid-border); margin-bottom: 20px; }
        .solid-table td { display: block; text-align: right; padding: 15px; border-bottom: 1px dotted var(--solid-border); }
        .solid-table td::before { content: attr(data-label); float: left; font-weight: 800; color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; }
        .order-product-info { flex-direction: column; align-items: flex-end; text-align: right; }
    }

    @keyframes dropIn { to { opacity: 1; transform: translateY(0); } }
</style>

<div class="orders-wrapper">
    <div class="page-header">
        <h2><i class="fas fa-shopping-bag"></i> ประวัติการสั่งซื้อ</h2>
        <div class="order-count-badge">ทั้งหมด <?= count($my_orders) ?> รายการ</div>
    </div>

    <?php echo displayFlashMessage(); ?>

    <div class="table-container">
        <table class="solid-table">
            <thead>
                <tr>
                    <th>รหัสคำสั่งซื้อ</th>
                    <th>สินค้า</th>
                    <th>ร้านค้า</th>
                    <th>สถานะ</th>
                    <th>ติดต่อผู้ขาย</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($my_orders) > 0): ?>
                    <?php foreach($my_orders as $index => $o): 
                        // จัดการสีของ Badge สถานะ (Solid Style)
                        $badge_bg = 'rgba(203, 213, 225, 0.2)'; $badge_color = 'var(--text-muted)'; $badge_border = 'var(--solid-border)'; $status_text = 'รอยืนยัน'; $icon = 'fa-clock';
                        
                        if($o['status'] == 'pending') { 
                            $badge_bg = 'rgba(217, 119, 6, 0.1)'; $badge_color = '#d97706'; $badge_border = 'rgba(217, 119, 6, 0.3)'; $status_text = 'รอยืนยัน'; $icon = 'fa-hourglass-half'; 
                        }
                        elseif($o['status'] == 'preparing') { 
                            $badge_bg = 'rgba(37, 99, 235, 0.1)'; $badge_color = '#2563eb'; $badge_border = 'rgba(37, 99, 235, 0.3)'; $status_text = 'กำลังเตรียมของ'; $icon = 'fa-box-open'; 
                        }
                        elseif($o['status'] == 'completed') { 
                            $badge_bg = 'rgba(5, 150, 105, 0.1)'; $badge_color = '#059669'; $badge_border = 'rgba(5, 150, 105, 0.3)'; $status_text = 'สำเร็จแล้ว'; $icon = 'fa-check-circle'; 
                        }
                        elseif($o['status'] == 'cancelled') { 
                            $badge_bg = 'rgba(220, 38, 38, 0.1)'; $badge_color = '#dc2626'; $badge_border = 'rgba(220, 38, 38, 0.3)'; $status_text = 'ยกเลิก'; $icon = 'fa-times-circle'; 
                        }
                    ?>
                        <tr>
                            <td data-label="รหัสคำสั่งซื้อ">
                                <div style="font-weight: 800; color: var(--solid-primary); font-size: 1.1rem;">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;"><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></div>
                            </td>
                            <td data-label="สินค้า">
                                <div class="order-product-info">
                                    <img src="<?= !empty($o['image_url']) ? '../assets/images/products/'.$o['image_url'] : 'https://via.placeholder.com/60' ?>" class="order-img">
                                    <div>
                                        <a href="product_detail.php?id=<?= $o['product_id'] ?>" class="order-title">
                                            <?= e($o['product_name']) ?> <i class="fas fa-external-link-alt" style="font-size: 0.7rem; color: var(--text-muted);"></i>
                                        </a>
                                        <div class="order-price">฿<?= number_format($o['price'], 2) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="ร้านค้า">
                                <a href="view_profile.php?id=<?= $o['seller_id'] ?>" style="color: var(--solid-text); text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; border-bottom: 1px dashed var(--solid-border); padding-bottom: 2px;">
                                    <i class="fas fa-store" style="color: var(--text-muted);"></i> <?= e($o['shop_name']) ?>
                                </a>
                            </td>
                            <td data-label="สถานะ">
                                <span class="status-badge" style="background: <?= $badge_bg ?>; color: <?= $badge_color ?>; border-color: <?= $badge_border ?>;">
                                    <i class="fas <?= $icon ?>"></i> <?= $status_text ?>
                                </span>
                            </td>
                            <td data-label="ติดต่อผู้ขาย">
                                <?php if($o['status'] != 'cancelled' && $o['status'] != 'completed'): ?>
                                    <a href="chat.php?user=<?= $o['seller_id'] ?>" class="btn-chat">
                                        <i class="fas fa-comment-dots"></i> ทักแชทผู้ขาย
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;"><i class="fas fa-ban"></i> ไม่สามารถติดต่อได้</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding: 100px 20px; text-align: center; border-bottom: none;">
                            <i class="fas fa-shopping-basket" style="font-size: 4rem; color: var(--solid-border); margin-bottom: 20px;"></i>
                            <h3 style="font-weight: 900; font-size: 1.8rem; margin-bottom: 10px; color: var(--solid-text);">คุณยังไม่มีประวัติการสั่งซื้อ</h3>
                            <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 500; margin-bottom: 30px;">ลองไปเลือกดูสินค้าที่น่าสนใจในตลาดนัดดูสิ!</p>
                            <a href="index.php" class="btn-chat" style="padding: 15px 40px; font-size: 1.1rem;">เริ่มช้อปปิ้งกันเลย</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>