<?php
require_once '../includes/functions.php';
$pageTitle = "กระดานแลกเปลี่ยนสินค้า (Barter System)";
require_once '../includes/header.php';

$db = getDB();
// ดึงรายการแลกเปลี่ยนล่าสุด
$stmt = $db->prepare("SELECT b.*, u.fullname, u.profile_img FROM barter_posts b JOIN users u ON b.user_id = u.id WHERE b.status = 'open' ORDER BY b.created_at DESC");
$stmt->execute();
$barters = $stmt->fetchAll();
?>

<style>
    .barter-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; margin-top: 30px; }
    
    .barter-card {
        background: var(--theme-surface);
        border: 2px solid var(--theme-border);
        border-radius: 24px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .barter-card:hover { transform: translateY(-10px); box-shadow: var(--bncc-shadow-xl); border-color: var(--bncc-primary-500); }

    .barter-img-box { width: 100%; height: 220px; overflow: hidden; position: relative; background: #eee; }
    .barter-img-box img { width: 100%; height: 100%; object-fit: cover; }

    .barter-content { padding: 25px; }
    
    /* 🔄 Barter Logic UI */
    .exchange-flow { display: flex; flex-direction: column; gap: 12px; margin: 15px 0; }
    .flow-item { display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 12px; font-weight: 800; font-size: 0.9rem; }
    .item-have { background: var(--bncc-success-50); color: var(--bncc-success-600); border: 1px solid var(--bncc-success-100); }
    .item-want { background: var(--bncc-primary-50); color: var(--bncc-primary-600); border: 1px solid var(--bncc-primary-100); }
    
    .divider-icon { text-align: center; color: var(--theme-text-tertiary); font-size: 0.8rem; margin: -5px 0; }

    .btn-pro-action {
        width: 100%; padding: 12px; border-radius: 12px; background: var(--bncc-primary-500);
        color: #fff; font-weight: 800; text-align: center; text-decoration: none; display: block;
        transition: 0.3s;
    }
    .btn-pro-action:hover { background: var(--bncc-primary-600); box-shadow: 0 5px 15px var(--bncc-glow-primary-md); }
</style>

<div class="ui-container ui-py-10">
    <div class="ui-flex ui-justify-between ui-items-center ui-mb-8">
        <div>
            <h1 class="ui-font-black ui-text-4xl">Barter <span class="ui-text-primary">System</span></h1>
            <p class="ui-text-sub ui-font-bold">แลกเปลี่ยนสิ่งของภายในวิทยาลัย ไม่ต้องใช้เงิน</p>
        </div>
        <?php if(isLoggedIn()): ?>
            <a href="post_barter.php" class="ui-btn ui-btn-primary" style="padding:15px 30px; border-radius:15px;">
                <i class="fas fa-plus-circle"></i> ลงประกาศแลกเปลี่ยน
            </a>
        <?php endif; ?>
    </div>

    <?php if(count($barters) > 0): ?>
        <div class="barter-grid">
            <?php foreach($barters as $b): 
                $img = !empty($b['image_url']) ? "../assets/images/barter/".$b['image_url'] : "../assets/images/products/default.png";
            ?>
            <div class="barter-card stagger-reveal">
                <div class="barter-img-box">
                    <img src="<?= $img ?>" alt="Barter Item">
                    <div style="position:absolute; top:15px; left:15px; background:rgba(0,0,0,0.7); color:#fff; padding:5px 12px; border-radius:10px; font-size:0.7rem; font-weight:800;">
                        <i class="far fa-clock"></i> <?= date('d M Y', strtotime($b['created_at'])) ?>
                    </div>
                </div>
                
                <div class="barter-content">
                    <h3 class="ui-font-black ui-text-xl ui-mb-4"><?= e($b['title']) ?></h3>
                    
                    <div class="exchange-flow">
                        <div class="flow-item item-have">
                            <i class="fas fa-box-open"></i> มี: <?= e($b['item_have']) ?>
                        </div>
                        <div class="divider-icon"><i class="fas fa-chevron-down"></i> แลกกับ <i class="fas fa-chevron-down"></i></div>
                        <div class="flow-item item-want">
                            <i class="fas fa-heart"></i> อยากได้: <?= e($b['item_want']) ?>
                        </div>
                    </div>

                    <p class="ui-text-sub ui-text-sm ui-mb-6 ui-truncate"><?= e($b['description']) ?></p>

                    <div class="ui-flex ui-items-center ui-justify-between ui-pt-4" style="border-top:1px solid var(--theme-border);">
                        <div class="ui-flex ui-items-center ui-gap-2">
                            <img src="<?= $base_path ?>assets/images/profiles/<?= $b['profile_img'] ?: 'default_profile.png' ?>" style="width:30px;height:30px;border-radius:50%;">
                            <span class="ui-text-xs ui-font-bold"><?= e($b['fullname']) ?></span>
                        </div>
                        <a href="barter_detail.php?id=<?= $b['id'] ?>" class="ui-text-primary ui-font-black ui-text-sm">สนใจแลกเปลี่ยน <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding:100px; border:3px dashed var(--theme-border); border-radius:40px;">
            <i class="fas fa-exchange-alt fa-4x ui-text-muted ui-mb-4"></i>
            <h2 class="ui-font-black ui-text-muted">ยังไม่มีรายการแลกเปลี่ยนในขณะนี้</h2>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>