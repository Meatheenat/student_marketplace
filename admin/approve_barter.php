<?php
/**
 * ============================================================================================
 * 🛡️ BNCC ADMIN COMMAND CENTER - BARTER APPROVAL ENGINE (V 1.0.0)
 * ============================================================================================
 * Role: Admin / Teacher Only
 * Function: Review pending barter posts, approve to 'open', or reject to 'rejected'
 * UI/UX: High-Speed Review Grid, Tactical Action Buttons, Real-time Notifications
 * ============================================================================================
 */
require_once '../includes/functions.php';

// 🛑 GUARD: จำกัดสิทธิ์เข้าถึงเฉพาะ Admin หรือ Teacher
checkRole(['admin', 'teacher']);

$db = getDB();

// --------------------------------------------------------------------------------------------
// [ACTION HANDLER] จัดการเมื่อกดปุ่ม อนุมัติ / ปฏิเสธ
// --------------------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $post_id = (int)$_POST['post_id'];
    $owner_id = (int)$_POST['owner_id'];
    $item_have = trim($_POST['item_have']);

    if ($post_id > 0) {
        if ($action === 'approve') {
            // อนุมัติ -> เปลี่ยนสถานะเป็น open
            $stmt = $db->prepare("UPDATE barter_posts SET status = 'open' WHERE id = ?");
            if ($stmt->execute([$post_id])) {
                // ส่งแจ้งเตือนหา User ว่าผ่านแล้ว
                sendNotification($owner_id, 'system', "✅ ยินดีด้วย! ประกาศแลกเปลี่ยน [{$item_have}] ของคุณได้รับการอนุมัติแล้ว", "../pages/barter_detail.php?id={$post_id}");
                $_SESSION['flash_message'] = "อนุมัติรายการ [{$item_have}] เรียบร้อยแล้ว";
                $_SESSION['flash_type'] = "success";
            }
        } elseif ($action === 'reject') {
            // ปฏิเสธ -> เปลี่ยนสถานะเป็น rejected (หรือจะใช้ DELETE ก็ได้ แต่เปลี่ยนสถานะปลอดภัยกว่า)
            $stmt = $db->prepare("UPDATE barter_posts SET status = 'rejected' WHERE id = ?");
            if ($stmt->execute([$post_id])) {
                // ส่งแจ้งเตือนหา User ว่าไม่ผ่าน
                sendNotification($owner_id, 'system', "❌ ขออภัย ประกาศแลกเปลี่ยน [{$item_have}] ของคุณไม่ผ่านการตรวจสอบ", "../pages/barter_board.php");
                $_SESSION['flash_message'] = "ปฏิเสธรายการ [{$item_have}] แล้ว";
                $_SESSION['flash_type'] = "danger";
            }
        }
    }
    redirect('approve_barter.php'); // รีเฟรชหน้าเคลียร์ POST
}

// --------------------------------------------------------------------------------------------
// [DATA FETCH] ดึงข้อมูลที่รอการอนุมัติ (status = 'pending')
// --------------------------------------------------------------------------------------------
$query = "SELECT b.*, u.fullname, u.profile_img, u.role as user_role 
          FROM barter_posts b 
          JOIN users u ON b.user_id = u.id 
          WHERE b.status = 'pending' 
          ORDER BY b.created_at ASC"; // ดึงอันเก่าสุดขึ้นมาก่อน (First in, First out)
$stmt = $db->prepare($query);
$stmt->execute();
$pending_posts = $stmt->fetchAll();

$pageTitle = "อนุมัติกระดานแลกเปลี่ยน | Admin Center";
require_once '../includes/header.php';
?>

<style>
/* ==========================================================================
   [CSS CORE] ADMIN TACTICAL UI
   ========================================================================== */
.admin-container {
    max-width: 1400px;
    margin: 40px auto;
    padding: 0 20px;
    animation: adminReveal 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes adminReveal {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* 📊 Header Dashboard */
.admin-header-panel {
    background: var(--theme-surface);
    border: 2px solid var(--theme-border);
    border-radius: 24px;
    padding: 30px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.stat-badge {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
    border: 2px solid rgba(245, 158, 11, 0.3);
    padding: 10px 20px;
    border-radius: 15px;
    font-size: 1.2rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* 📦 Review Grid System */
.review-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 30px;
}

.review-card {
    background: var(--theme-surface);
    border: 2px solid var(--theme-border);
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 20px rgba(0,0,0,0.03);
    transition: 0.3s;
}
.review-card:hover { border-color: #4f46e5; box-shadow: 0 15px 35px rgba(79, 70, 229, 0.1); }

/* Card Image Area */
.review-img-box {
    width: 100%;
    height: 250px;
    background: var(--theme-bg);
    position: relative;
}
.review-img-box img {
    width: 100%; height: 100%; object-fit: cover;
}

.poster-tag {
    position: absolute;
    bottom: 15px; left: 15px;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(5px);
    color: white;
    padding: 8px 15px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85rem;
    font-weight: 700;
}
.poster-tag img { width: 25px; height: 25px; border-radius: 50%; border: 1px solid white; }

/* Card Info Area */
.review-info { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }

.item-have-badge {
    display: inline-block;
    background: #4f46e5;
    color: white;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 900;
    margin-bottom: 10px;
}

.review-title {
    font-size: 1.4rem;
    font-weight: 900;
    color: var(--theme-text-primary);
    margin-bottom: 15px;
    line-height: 1.3;
}

.review-desc {
    font-size: 0.95rem;
    color: var(--theme-text-secondary);
    line-height: 1.6;
    margin-bottom: 25px;
    background: var(--theme-bg);
    padding: 15px;
    border-radius: 12px;
    border: 1px solid var(--theme-border);
    flex-grow: 1;
}

/* Tactical Action Buttons */
.action-dock {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: auto;
}

.btn-tactical {
    padding: 15px;
    border-radius: 15px;
    font-size: 1rem;
    font-weight: 900;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: 0.3s;
}

.btn-approve {
    background: #10b981; color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}
.btn-approve:hover { background: #059669; transform: translateY(-3px); }

.btn-reject {
    background: transparent; color: #ef4444;
    border: 2px solid #ef4444;
}
.btn-reject:hover { background: #ef4444; color: white; transform: translateY(-3px); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }

/* Empty State */
.all-clear-state {
    text-align: center;
    padding: 100px 20px;
    background: var(--theme-surface);
    border: 3px dashed var(--theme-border);
    border-radius: 30px;
}

@media (max-width: 768px) {
    .admin-header-panel { flex-direction: column; gap: 20px; text-align: center; padding: 25px; }
    .review-grid { grid-template-columns: 1fr; }
}
</style>

<div class="admin-container">

    <div class="admin-header-panel">
        <div>
            <h1 style="font-size: 2.2rem; font-weight: 900; margin-bottom: 5px; color: var(--theme-text-primary);">
                <i class="fas fa-clipboard-check" style="color: #4f46e5;"></i> อนุมัติประกาศแลกเปลี่ยน
            </h1>
            <p style="color: var(--theme-text-secondary); font-weight: 600;">
                ตรวจสอบความเหมาะสมของประกาศก่อนเผยแพร่สู่กระดานสาธารณะ
            </p>
        </div>
        <div class="stat-badge">
            <i class="fas fa-hourglass-half fa-spin" style="animation-duration: 3s;"></i>
            รอตรวจสอบ <?= count($pending_posts) ?> รายการ
        </div>
    </div>

    <?php if (count($pending_posts) > 0): ?>
        <div class="review-grid">
            <?php foreach ($pending_posts as $post): 
                $img = !empty($post['image_url']) ? "../assets/images/barter/".$post['image_url'] : "../assets/images/products/default.png";
                $pfp = !empty($post['profile_img']) ? "../assets/images/profiles/".$post['profile_img'] : "../assets/images/profiles/default_profile.png";
            ?>
                <div class="review-card">
                    <div class="review-img-box">
                        <img src="<?= $img ?>" alt="Item Image">
                        <div class="poster-tag">
                            <img src="<?= $pfp ?>" alt="Avatar">
                            <span><?= e($post['fullname']) ?></span>
                        </div>
                    </div>

                    <div class="review-info">
                        <span class="item-have-badge">สิ่งที่มี: <?= e($post['item_have']) ?></span>
                        <h2 class="review-title"><?= e($post['title']) ?></h2>
                        
                        <div class="review-desc">
                            <strong style="color: var(--theme-text-primary); display:block; margin-bottom:5px;">รายละเอียด:</strong>
                            <?= nl2br(e($post['description'])) ?>
                        </div>

                        <div class="action-dock">
                            <form method="POST" style="width: 100%;" onsubmit="return confirm('แน่ใจหรือไม่ที่จะปฏิเสธและซ่อนประกาศนี้?');">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <input type="hidden" name="owner_id" value="<?= $post['user_id'] ?>">
                                <input type="hidden" name="item_name" value="<?= e($post['item_have']) ?>">
                                <button type="submit" class="btn-tactical btn-reject" style="width: 100%;">
                                    <i class="fas fa-times"></i> ไม่อนุมัติ
                                </button>
                            </form>

                            <form method="POST" style="width: 100%;" onsubmit="return confirm('ตรวจสอบแน่ใจแล้วใช่ไหมที่จะเผยแพร่ประกาศนี้?');">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <input type="hidden" name="owner_id" value="<?= $post['user_id'] ?>">
                                <input type="hidden" name="item_name" value="<?= e($post['item_have']) ?>">
                                <button type="submit" class="btn-tactical btn-approve" style="width: 100%;">
                                    <i class="fas fa-check"></i> อนุมัติเผยแพร่
                                </button>
                            </form>
                        </div>
                        
                        <div style="text-align: center; margin-top: 15px; font-size: 0.75rem; color: var(--theme-text-tertiary); font-weight: 700;">
                            โพสต์เมื่อ: <?= date('d M Y, H:i', strtotime($post['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="all-clear-state">
            <i class="fas fa-shield-check" style="font-size: 6rem; color: #10b981; margin-bottom: 20px;"></i>
            <h2 style="font-size: 2.5rem; font-weight: 900; color: var(--theme-text-primary); margin-bottom: 10px;">เคลียร์หมดแล้ว!</h2>
            <p style="font-size: 1.1rem; color: var(--theme-text-secondary); font-weight: 600;">
                ไม่มีประกาศแลกเปลี่ยนที่รอการตรวจสอบในขณะนี้ แอดมินไปพักผ่อนได้เลยครับ ☕
            </p>
            <a href="admin_dashboard.php" class="btn-tactical" style="background: #4f46e5; color: white; display: inline-flex; width: auto; padding: 15px 30px; margin-top: 30px;">
                กลับหน้าหลัก Admin
            </a>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>