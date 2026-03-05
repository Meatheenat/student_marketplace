<?php
/**
 * Student Marketplace - Seller Dashboard (Solid Modern Edition)
 * Project: BNCC Student Marketplace [Cite: User Summary]
 * Developed by Gemini AI Collaboration
 */
require_once '../includes/functions.php';

// ตรวจสอบสิทธิ์ว่าเป็นผู้ขายหรือไม่
checkRole('seller');

$db = getDB();
$user_id = $_SESSION['user_id'];

// ตรวจสอบว่านักเรียนคนนี้มีร้านค้าหรือยัง
$shop_stmt = $db->prepare("SELECT * FROM shops WHERE user_id = ?");
$shop_stmt->execute([$user_id]);
$shop = $shop_stmt->fetch();

if ($shop) {
    $shop_id = $shop['id'];

    // --- 1. จัดการอัปเดตสถานะออเดอร์ (OMS) ---
    if (isset($_POST['update_order_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        
        $db->prepare("UPDATE orders SET status = ? WHERE id = ? AND shop_id = ?")->execute([$new_status, $order_id, $shop_id]);
        $_SESSION['flash_message'] = "อัปเดตสถานะออเดอร์เรียบร้อยแล้ว";
        $_SESSION['flash_type'] = "success";
        redirect('dashboard.php');
    }

    // --- 2. จัดการการลบสินค้า (Soft Delete) ---
    if (isset($_GET['delete_id'])) {
        $del_id = (int)$_GET['delete_id'];
        $check_del = $db->prepare("UPDATE products SET is_deleted = 1 WHERE id = ? AND shop_id = ?");
        if ($check_del->execute([$del_id, $shop_id])) {
            $_SESSION['flash_message'] = "ลบสินค้าออกจากหน้าร้านเรียบร้อยแล้ว (Soft Delete)";
            $_SESSION['flash_type'] = "success";
            redirect('dashboard.php');
        }
    }

    // --- 3. ดึงข้อมูลสถิติภาพรวมร้านค้า ---
    $total_views = $db->query("SELECT SUM(views) FROM products WHERE shop_id = $shop_id AND is_deleted = 0")->fetchColumn() ?? 0;
    $total_wishlist = $db->query("SELECT COUNT(*) FROM wishlist w JOIN products p ON w.product_id = p.id WHERE p.shop_id = $shop_id AND p.is_deleted = 0")->fetchColumn();
    $total_sales = $db->query("SELECT COUNT(*) FROM orders WHERE shop_id = $shop_id AND status = 'completed'")->fetchColumn();

    // --- 4. ดึงสินค้าทั้งหมดของร้าน ---
    $prod_stmt = $db->prepare("
        SELECT p.*, c.category_name, 
        (SELECT COUNT(*) FROM wishlist WHERE product_id = p.id) as wish_count 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.shop_id = ? AND p.is_deleted = 0 
        ORDER BY p.created_at DESC
    ");
    $prod_stmt->execute([$shop_id]);
    $products = $prod_stmt->fetchAll();

    // --- 5. ดึงคำสั่งซื้อ (Orders) ---
    $order_stmt = $db->prepare("
        SELECT o.*, p.title as product_name, p.image_url, p.price, u.fullname as buyer_name, u.id as buyer_id 
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        JOIN users u ON o.buyer_id = u.id 
        WHERE o.shop_id = ? 
        ORDER BY o.created_at DESC
    ");
    $order_stmt->execute([$shop_id]);
    $orders = $order_stmt->fetchAll();
}

$pageTitle = "แผงควบคุมผู้ขาย - BNCC Market";
require_once '../includes/header.php';

// ถ้ายังไม่มีร้านค้า
if (!$shop) {
    echo "<div style='text-align:center; padding:100px 20px; background:var(--bg-card); border-radius:32px; border:3px dashed var(--border-color); margin-top:40px;'>
            <i class='fas fa-store-slash' style='font-size:5rem; color:var(--text-muted); opacity:0.3;'></i>
            <h2 style='margin-top:30px; font-weight:800;'>คุณยังไม่มีหน้าร้านค้าในระบบ</h2>
            <p style='color:var(--text-muted); margin-bottom:30px;'>เริ่มสร้างร้านค้าของคุณวันนี้ เพื่อสร้างรายได้ในวิทยาลัย!</p>
            <a href='edit_shop.php' class='btn btn-primary' style='padding:15px 40px; border-radius:16px; font-weight:800;'>
                <i class='fas fa-plus-circle'></i> สร้างร้านค้าของฉันทันที
            </a>
          </div>";
    require_once '../includes/footer.php';
    exit;
}
?>

<style>
    /* ============================================================
       🛠️ SOLID DASHBOARD UI SYSTEM
       ============================================================ */
    :root {
        --dash-bg: #f1f5f9;
        --dash-card: #ffffff;
        --dash-border: #cbd5e1;
        --dash-text: #0f172a;
        --dash-accent: #4f46e5;
    }

    .dark-theme {
        --dash-bg: #0f172a;
        --dash-card: #1e293b;
        --dash-border: #334155;
        --dash-text: #ffffff;
        --dash-accent: #6366f1;
    }

    body { background-color: var(--dash-bg) !important; color: var(--dash-text); }

    .stat-card {
        background: var(--dash-card);
        border: 2px solid var(--dash-border);
        border-radius: 24px;
        padding: 30px;
        display: flex;
        align-items: center;
        gap: 25px;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        opacity: 0;
        transform: translateY(20px);
    }
    .stat-card.visible { opacity: 1; transform: translateY(0); }
    .stat-card:hover { transform: translateY(-8px); border-color: var(--dash-accent); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }

    .stat-icon {
        width: 65px; height: 65px;
        border-radius: 18px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem;
        flex-shrink: 0;
    }

    .table-container {
        background: var(--dash-card);
        border: 2px solid var(--dash-border);
        border-radius: 24px;
        overflow: hidden;
        margin-bottom: 50px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }

    .solid-table { width: 100%; border-collapse: collapse; }
    .solid-table th {
        background: var(--dash-bg);
        padding: 20px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        border-bottom: 2px solid var(--dash-border);
    }
    .solid-table td { padding: 20px; border-bottom: 1px solid var(--dash-border); font-weight: 600; vertical-align: middle; }
    .solid-table tr:hover td { background: rgba(79, 70, 229, 0.03); }

    .status-pill {
        padding: 6px 14px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        border: 2px solid transparent;
    }

    /* 🎯 🛠️ สไตล์สำหรับลิงก์สินค้า (ให้ดูออกว่ากดได้) */
    .product-link {
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.2s ease;
        padding: 5px;
        border-radius: 12px;
    }
    .product-link:hover {
        transform: translateX(8px);
        color: var(--dash-accent);
    }

    .popularity-bar {
        width: 100%; height: 10px;
        background: var(--dash-bg);
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid var(--dash-border);
    }
    .bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--dash-accent), #a855f7);
        width: 0;
        transition: width 1.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .fade-up { opacity: 0; transform: translateY(20px); transition: 0.5s ease-out; }
    .fade-up.visible { opacity: 1; transform: translateY(0); }
</style>

<div class="container" style="padding-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;" class="fade-up">
        <div>
            <h1 style="font-size: 2.5rem; font-weight: 900; letter-spacing: -1.5px;">Dashboard: <?= e($shop['shop_name']) ?></h1>
            <p style="color: var(--text-muted); font-weight: 600; font-size: 1.1rem;">จัดการธุรกิจของคุณและตรวจสอบผลงานได้ที่นี่</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <a href="edit_shop.php" class="btn btn-outline" style="border-radius: 14px; font-weight: 800;">
                <i class="fas fa-cog"></i> ตั้งค่าร้านค้า
            </a>
            <a href="add_product.php" class="btn btn-primary" style="border-radius: 14px; font-weight: 800; padding: 12px 30px;">
                <i class="fas fa-plus-circle"></i> ลงขายสินค้าใหม่
            </a>
        </div>
    </div>

    <?php echo displayFlashMessage(); ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 50px;">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(79, 70, 229, 0.15); color: var(--dash-accent); border: 2px solid var(--dash-accent);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: 900; line-height: 1;"><?= number_format($total_views) ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 800; text-transform: uppercase; margin-top: 5px;">Total Views</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 2px solid #ef4444;">
                <i class="fas fa-heart"></i>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: 900; line-height: 1;"><?= number_format($total_wishlist) ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 800; text-transform: uppercase; margin-top: 5px;">Total Hearts</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 2px solid #10b981;">
                <i class="fas fa-wallet"></i>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: 900; line-height: 1;"><?= number_format($total_sales) ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 800; text-transform: uppercase; margin-top: 5px;">Sales Volume</div>
            </div>
        </div>
    </div>

    <h2 style="font-weight: 900; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;" class="fade-up">
        <i class="fas fa-truck-loading" style="color: var(--dash-accent);"></i> Recent Customer Orders
    </h2>
    <div class="table-container fade-up">
        <table class="solid-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product Item</th>
                    <th>Customer Name</th>
                    <th>Order Status</th>
                    <th style="text-align: right;">Action Control</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($orders) > 0): foreach($orders as $o): 
                    $status_cfg = [
                        'pending'   => ['bg'=>'rgba(217, 119, 6, 0.1)', 'color'=>'#d97706', 'border'=>'#d97706'],
                        'preparing' => ['bg'=>'rgba(37, 99, 235, 0.1)', 'color'=>'#2563eb', 'border'=>'#2563eb'],
                        'completed' => ['bg'=>'rgba(5, 150, 105, 0.1)', 'color'=>'#059669', 'border'=>'#059669'],
                        'cancelled' => ['bg'=>'rgba(220, 38, 38, 0.1)', 'color'=>'#dc2626', 'border'=>'#dc2626']
                    ];
                    $st = $status_cfg[$o['status']];
                ?>
                <tr>
                    <td style="font-family: monospace; font-size: 0.9rem;">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td><?= e($o['product_name']) ?></td>
                    <td>
                        <a href="../pages/chat.php?user=<?= $o['buyer_id'] ?>" style="color: var(--dash-accent); text-decoration: none; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-comment-alt"></i> <?= e($o['buyer_name']) ?>
                        </a>
                    </td>
                    <td>
                        <span class="status-pill" style="background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>; border-color: <?= $st['border'] ?>;">
                            <?= $o['status'] ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <form method="POST" style="display: inline-flex; gap: 10px;">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <select name="new_status" style="padding: 8px; border-radius: 10px; border: 2px solid var(--dash-border); background: var(--dash-bg); color: var(--dash-text); font-weight: 700;">
                                <option value="pending" <?= $o['status']=='pending' ? 'selected':'' ?>>รอยืนยัน</option>
                                <option value="preparing" <?= $o['status']=='preparing' ? 'selected':'' ?>>เตรียมของ</option>
                                <option value="completed" <?= $o['status']=='completed' ? 'selected':'' ?>>สำเร็จ</option>
                                <option value="cancelled" <?= $o['status']=='cancelled' ? 'selected':'' ?>>ยกเลิก</option>
                            </select>
                            <button type="submit" name="update_order_status" class="btn btn-primary" style="padding: 8px 15px; border-radius: 10px;">OK</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="padding: 60px; text-align: center; color: var(--text-muted); font-weight: 700;">📭 ยังไม่มีออเดอร์เข้ามา</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2 style="font-weight: 900; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;" class="fade-up">
        <i class="fas fa-box-open" style="color: #10b981;"></i> Inventory & Performance Analytics
    </h2>
    <div class="table-container fade-up">
        <table class="solid-table">
            <thead>
                <tr>
                    <th>Product Item (Click to view)</th>
                    <th style="text-align: center;">Views</th>
                    <th style="text-align: center;">Wishlists</th>
                    <th>Engagement Score</th>
                    <th>Management</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($products) > 0): foreach($products as $p): 
                    $pop_score = $p['views'] + ($p['wish_count'] * 5);
                ?>
                <tr>
                    <td>
                        <a href="../pages/product_detail.php?id=<?= $p['id'] ?>" class="product-link" title="ดูรายละเอียดสินค้าบนหน้าเว็บ">
                            <img src="<?= !empty($p['image_url']) ? '../assets/images/products/'.$p['image_url'] : 'https://via.placeholder.com/60' ?>" 
                                 style="width: 60px; height: 60px; border-radius: 12px; object-fit: cover; border: 2px solid var(--dash-border);">
                            <div>
                                <div style="font-weight: 800; font-size: 1.1rem;"><?= e($p['title']) ?> <i class="fas fa-external-link-alt" style="font-size: 0.7rem; opacity: 0.5;"></i></div>
                                <div style="color: var(--dash-accent); font-weight: 800;">฿<?= number_format($p['price'], 2) ?></div>
                            </div>
                        </a>
                    </td>
                    <td style="text-align: center; font-size: 1.2rem; font-weight: 800; color: var(--dash-accent);"><?= number_format($p['views']) ?></td>
                    <td style="text-align: center; font-size: 1.2rem; font-weight: 800; color: #ef4444;"><?= number_format($p['wish_count']) ?></td>
                    <td style="width: 200px;">
                        <div class="popularity-bar">
                            <div class="bar-fill" data-width="<?= min(($pop_score / 200) * 100, 100) ?>%"></div>
                        </div>
                        <div style="font-size: 0.75rem; margin-top: 5px; font-weight: 700;">Score: <?= $pop_score ?></div>
                    </td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <a href="add_product.php?id=<?= $p['id']; ?>" class="btn" style="background: var(--dash-bg); border: 2px solid var(--dash-border); color: var(--dash-text); font-weight: 800; border-radius: 10px;">EDIT</a>
                            <a href="dashboard.php?delete_id=<?= $p['id']; ?>" class="btn" style="background: rgba(239, 68, 68, 0.1); border: 2px solid #ef4444; color: #ef4444; font-weight: 800; border-radius: 10px;" onclick="return confirm('⚠️ ยืนยันการลบสินค้าชิ้นนี้?');">DELETE</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="padding: 60px; text-align: center; color: var(--text-muted); font-weight: 700;">📦 ยังไม่มีสินค้าวางจำหน่าย</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('visible');
                    const bars = entry.target.querySelectorAll('.bar-fill');
                    bars.forEach(bar => {
                        bar.style.width = bar.getAttribute('data-width');
                    });
                }, index * 100);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.stat-card, .fade-up, .table-container').forEach(el => observer.observe(el));
</script>

<?php require_once '../includes/footer.php'; ?>