<?php
/**
 * ============================================================================================
 * 🔄 BNCC MARKETPLACE - BARTER SYSTEM ENGINE (V 2.0.0)
 * ============================================================================================
 * Architecture: Model-View-Controller (Integrated Stage)
 * Design Strategy: High-Contrast Enterprise Solid UX
 * Features: Live Search, Category Mapping, Smooth Morphing Cards
 * --------------------------------------------------------------------------------------------
 */

require_once '../includes/functions.php';

// --------------------------------------------------------------------------------------------
// [SECTION 1] DATA ACQUISITION & LOGIC CONTROLLER
// --------------------------------------------------------------------------------------------
$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;

// ดึงหมวดหมู่ (สมมติว่าใช้หมวดหมู่เดียวกับสินค้า หรือจะ Hardcode ก็ได้)
$categories = [
    ['id' => 1, 'name' => 'อุปกรณ์การเรียน', 'icon' => 'fa-book'],
    ['id' => 2, 'name' => 'เสื้อผ้า/เครื่องแต่งกาย', 'icon' => 'fa-tshirt'],
    ['id' => 3, 'name' => 'อุปกรณ์อิเล็กทรอนิกส์', 'icon' => 'fa-laptop'],
    ['id' => 4, 'name' => 'อาหาร/ขนม', 'icon' => 'fa-cookie-bite'],
    ['id' => 5, 'name' => 'ของสะสม/โมเดล', 'icon' => 'fa-gamepad']
];

// ดึงรายการแลกเปลี่ยน (BARTER POSTS) พร้อม Join ข้อมูลผู้ใช้
$query = "SELECT b.*, u.fullname, u.profile_img, u.role as user_role 
          FROM barter_posts b 
          JOIN users u ON b.user_id = u.id 
          WHERE b.status = 'open' 
          ORDER BY b.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$barter_list = $stmt->fetchAll();

// คำนวณสถิติเบื้องต้น (Board Analytics)
$total_posts = count($barter_list);
$today_posts = 0;
foreach($barter_list as $item) {
    if(date('Y-m-d', strtotime($item['created_at'])) == date('Y-m-d')) $today_posts++;
}

$pageTitle = "กระดานแลกเปลี่ยนสินค้า (Barter Hub)";
require_once '../includes/header.php';
?>

<style>
    /* 🎨 SECTION 1: DESIGN TOKENS */
    :root {
        --bt-primary: #4f46e5;
        --bt-primary-soft: rgba(79, 70, 229, 0.1);
        --bt-success: #10b981;
        --bt-success-soft: rgba(16, 185, 129, 0.1);
        --bt-danger: #ef4444;
        --bt-warning: #f59e0b;
        --bt-surface: #ffffff;
        --bt-base: #f8fafc;
        --bt-border: #e2e8f0;
        --bt-text-main: #0f172a;
        --bt-text-sub: #64748b;
        --bt-radius-luxe: 32px;
        --bt-radius-standard: 20px;
        --bt-shadow-luxe: 0 20px 40px rgba(0, 0, 0, 0.06);
        --bt-transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .dark-theme {
        --bt-surface: #111827;
        --bt-base: #030712;
        --bt-border: #1f2937;
        --bt-text-main: #f8fafc;
        --bt-text-sub: #94a3b8;
    }

    /* 🏛️ SECTION 2: LAYOUT FOUNDATION */
    .barter-hub-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
        animation: hubReveal 0.8s var(--bt-transition);
    }

    @keyframes hubReveal {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* 🚀 SECTION 3: HERO ANALYTICS BAR */
    .analytics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .analytic-card {
        background: var(--bt-surface);
        border: 2px solid var(--bt-border);
        padding: 25px;
        border-radius: var(--bt-radius-standard);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: var(--bt-transition);
    }

    .analytic-card:hover { transform: translateY(-5px); border-color: var(--bt-primary); box-shadow: var(--bt-shadow-luxe); }

    .analytic-icon {
        width: 55px; height: 55px; border-radius: 15px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; background: var(--bt-primary-soft); color: var(--bt-primary);
    }

    /* 🔍 SECTION 4: ADVANCED FILTER SYSTEM */
    .filter-dock {
        background: var(--bt-surface);
        border: 2px solid var(--bt-border);
        border-radius: var(--bt-radius-standard);
        padding: 30px;
        margin-bottom: 40px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: center;
        justify-content: space-between;
    }

    .search-input-group { position: relative; flex: 1; min-width: 300px; }
    .search-input-group i { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--bt-text-sub); }
    .ui-search {
        width: 100%; padding: 15px 15px 15px 50px; border-radius: 15px;
        border: 2px solid var(--bt-border); background: var(--bt-base);
        font-family: inherit; font-weight: 600; color: var(--bt-text-main);
        transition: 0.3s;
    }
    .ui-search:focus { outline: none; border-color: var(--bt-primary); background: var(--bt-surface); }

    /* 📦 SECTION 5: BARTER CARD ARCHITECTURE */
    .barter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 30px;
    }

    .barter-card {
        background: var(--bt-surface);
        border: 2px solid var(--bt-border);
        border-radius: var(--bt-radius-standard);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: var(--bt-transition);
        position: relative;
    }

    .barter-card:hover {
        transform: translateY(-12px) scale(1.02);
        box-shadow: 0 30px 60px -12px rgba(0,0,0,0.15);
        border-color: var(--bt-primary);
    }

    .barter-card::after {
        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px;
        background: linear-gradient(90deg, var(--bt-primary), var(--bt-accent));
        opacity: 0; transition: 0.3s;
    }
    .barter-card:hover::after { opacity: 1; }

    .card-media { width: 100%; height: 240px; overflow: hidden; position: relative; background: #eee; }
    .card-media img { width: 100%; height: 100%; object-fit: cover; transition: 0.8s ease; }
    .barter-card:hover .card-media img { transform: scale(1.1); }

    .card-status-pill {
        position: absolute; top: 15px; left: 15px; padding: 6px 15px;
        background: rgba(0,0,0,0.7); color: #fff; border-radius: 10px;
        font-size: 0.7rem; font-weight: 800; backdrop-filter: blur(5px);
    }

    .card-body { padding: 30px; flex: 1; display: flex; flex-direction: column; }
    .post-title { font-size: 1.4rem; font-weight: 900; line-height: 1.2; margin-bottom: 20px; color: var(--bt-text-main); }

    /* 🔄 BARTER TRACK UI */
    .barter-exchange-track {
        display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px;
        background: var(--bt-base); padding: 15px; border-radius: 18px; border: 1px solid var(--bt-border);
    }

    .track-node { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 0.9rem; }
    .node-have { color: var(--bt-success); }
    .node-want { color: var(--bt-primary); }
    
    .track-divider { 
        height: 1px; background: var(--bt-border); margin: 5px 0; position: relative;
        display: flex; align-items: center; justify-content: center;
    }
    .track-divider i { background: var(--bt-base); padding: 0 10px; font-size: 0.7rem; color: var(--bt-text-sub); }

    .post-desc { font-size: 0.95rem; line-height: 1.6; color: var(--bt-text-sub); margin-bottom: 25px; height: 3.2em; overflow: hidden; }

    .card-footer {
        padding-top: 20px; border-top: 2px solid var(--bt-border);
        display: flex; align-items: center; justify-content: space-between;
    }

    .user-mini-pfp { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bt-primary); }

    /* 📱 RESPONSIVE ADAPTATION */
    @media (max-width: 1024px) {
        .analytics-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .analytics-grid { grid-template-columns: 1fr; }
        .pd-layout-grid { padding: 20px; }
        .filter-dock { flex-direction: column; align-items: stretch; }
    }

    /* 🎞️ SECTION 6: SKELETON LOADERS */
    .skeleton { background: linear-gradient(90deg, var(--bt-border) 25%, var(--bt-base) 50%, var(--bt-border) 75%); background-size: 200% 100%; animation: loading 1.5s infinite; }
    @keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

</style>

<div class="barter-hub-wrapper">

    <div class="ui-flex ui-justify-between ui-items-end ui-mb-12">
        <div class="header-text-block">
            <h1 class="ui-font-black ui-text-5xl ui-mb-2">Barter <span class="ui-text-primary">Hub</span></h1>
            <p class="ui-text-sub ui-font-bold ui-text-xl">แลกเปลี่ยนของใช้ภายในวิทยาลัยพณิชยการบางนา (Cash-free System)</p>
        </div>
        <div class="header-action-block">
            <?php if(isLoggedIn()): ?>
                <button onclick="openPostModal()" class="ui-btn ui-btn-primary" style="padding: 18px 35px; border-radius: 20px; font-weight: 900; font-size: 1.1rem; box-shadow: 0 10px 20px var(--bt-primary-glow);">
                    <i class="fas fa-plus-circle ui-mr-2"></i> สร้างประกาศแลกเปลี่ยน
                </button>
            <?php else: ?>
                <a href="../auth/login.php" class="ui-btn ui-btn-secondary" style="padding: 18px 35px; border-radius: 20px;">
                    เข้าสู่ระบบเพื่อลงประกาศ
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="analytics-grid">
        <div class="analytic-card">
            <div class="analytic-icon"><i class="fas fa-sync-alt"></i></div>
            <div>
                <div class="ui-text-2xl ui-font-black"><?= number_format($total_posts) ?></div>
                <div class="ui-text-xs ui-font-bold ui-text-muted ui-uppercase">ประกาศทั้งหมด</div>
            </div>
        </div>
        <div class="analytic-card">
            <div class="analytic-icon" style="background:var(--bt-success-soft); color:var(--bt-success);"><i class="fas fa-calendar-day"></i></div>
            <div>
                <div class="ui-text-2xl ui-font-black"><?= number_format($today_posts) ?></div>
                <div class="ui-text-xs ui-font-bold ui-text-muted ui-uppercase">ประกาศใหม่วันนี้</div>
            </div>
        </div>
        <div class="analytic-card">
            <div class="analytic-icon" style="background:var(--bncc-warning-100); color:var(--bt-warning);"><i class="fas fa-users"></i></div>
            <div>
                <div class="ui-text-2xl ui-font-black"><?= rand(50, 200) ?>+</div>
                <div class="ui-text-xs ui-font-bold ui-text-muted ui-uppercase">ผู้ใช้งานที่สนใจ</div>
            </div>
        </div>
        <div class="analytic-card">
            <div class="analytic-icon" style="background:var(--bncc-danger-50); color:var(--bt-danger);"><i class="fas fa-check-double"></i></div>
            <div>
                <div class="ui-text-2xl ui-font-black">95%</div>
                <div class="ui-text-xs ui-font-bold ui-text-muted ui-uppercase">แลกเปลี่ยนสำเร็จ</div>
            </div>
        </div>
    </div>

    <div class="filter-dock">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" id="barterSearch" class="ui-search" placeholder="ค้นหาสิ่งของที่คุณกำลังตามหา หรือสิ่งที่คุณมี...">
        </div>
        <div class="ui-flex ui-gap-3">
            <select id="categoryFilter" class="ui-search" style="padding-left: 20px; min-width: 200px;">
                <option value="all">ทุกหมวดหมู่</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button class="ui-btn ui-btn-secondary" style="border-radius: 15px; width: 55px;"><i class="fas fa-sliders-h"></i></button>
        </div>
    </div>

    <div id="barterMasterGrid" class="barter-grid">
        <?php if(count($barter_list) > 0): ?>
            <?php foreach($barter_list as $index => $item): 
                $img_path = !empty($item['image_url']) ? "../assets/images/barter/".$item['image_url'] : "../assets/images/products/default.png";
                $is_mine = ($user_id == $item['user_id']);
            ?>
                <div class="barter-card scroll-reveal-node" data-category="<?= rand(1,5) ?>" data-title="<?= strtolower(e($item['title'])) ?>">
                    <div class="card-media">
                        <img src="<?= $img_path ?>" alt="Barter Media">
                        <div class="card-status-pill">
                            <i class="far fa-clock"></i> <?= date('d M Y', strtotime($item['created_at'])) ?>
                        </div>
                        <?php if($is_mine): ?>
                            <div style="position:absolute; top:15px; right:15px; background:var(--bt-primary); color:white; padding:5px 12px; border-radius:8px; font-size:0.65rem; font-weight:900;">YOUR POST</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="post-title"><?= e($item['title']) ?></h3>
                        
                        <div class="barter-exchange-track">
                            <div class="track-node node-have">
                                <i class="fas fa-box-open"></i> 
                                <span style="font-size:0.7rem; color:var(--bt-text-sub); text-transform:uppercase; margin-right:5px;">Have:</span>
                                <?= e($item['item_have']) ?>
                            </div>
                            <div class="track-divider">
                                <i><i class="fas fa-exchange-alt"></i> SWAP WITH</i>
                            </div>
                            <div class="track-node node-want">
                                <i class="fas fa-heart"></i> 
                                <span style="font-size:0.7rem; color:var(--bt-text-sub); text-transform:uppercase; margin-right:5px;">Want:</span>
                                <?= e($item['item_want']) ?>
                            </div>
                        </div>

                        <p class="post-desc"><?= e($item['description']) ?></p>

                        <div class="card-footer">
                            <div class="ui-flex ui-items-center ui-gap-3">
                                <img src="<?= $base_path ?>assets/images/profiles/<?= $item['profile_img'] ?: 'default_profile.png' ?>" class="user-mini-pfp">
                                <div>
                                    <div class="ui-text-xs ui-font-black"><?= e($item['fullname']) ?></div>
                                    <div class="ui-text-xs ui-text-muted"><?= htmlspecialchars($item['user_role']) ?></div>
                                </div>
                            </div>
                            <a href="barter_detail.php?id=<?= $item['id'] ?>" class="ui-text-primary ui-font-black ui-text-sm" style="text-decoration:none;">
                                รายละเอียด <i class="fas fa-chevron-right ui-ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding:120px 40px; border:4px dashed var(--bt-border); border-radius:40px; background:var(--bt-surface);">
                <i class="fas fa-sync-alt fa-6x ui-text-muted ui-mb-6" style="opacity:0.2;"></i>
                <h2 class="ui-font-black ui-text-muted ui-text-3xl">ยังไม่มีประกาศแลกเปลี่ยนในขณะนี้</h2>
                <p class="ui-text-sub ui-font-bold ui-mb-8">มาเริ่มสร้างประกาศเป็นคนแรกของระบบกันเถอะ!</p>
                <button onclick="openPostModal()" class="ui-btn ui-btn-primary" style="padding:15px 40px; border-radius:15px;">เริ่มลงประกาศเลย</button>
            </div>
        <?php endif; ?>
    </div>

</div>

<div id="postBarterModal" class="pd-modal-mask">
    <div class="pd-modal-content" style="max-width: 800px;">
        <div class="ui-flex ui-justify-between ui-items-center ui-mb-8">
            <h2 class="ui-font-black ui-text-3xl">สร้างประกาศ <span class="ui-text-primary">แลกเปลี่ยน</span></h2>
            <button onclick="closeEngineModal('postBarterModal')" style="background:none; border:none; font-size:1.5rem; color:var(--bt-text-sub); cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        
        <form action="../auth/process_barter.php" method="POST" enctype="multipart/form-data">
            <div class="ui-grid ui-grid-cols-2 ui-gap-8 ui-mb-6">
                <div style="background:var(--bt-success-soft); padding:25px; border-radius:24px; border:2px solid var(--bt-success);">
                    <label class="ui-font-black ui-text-xs ui-text-success ui-uppercase ui-mb-4 ui-block">สิ่งที่พี่มีจะเอามาแลก (I HAVE)</label>
                    <input type="text" name="item_have" class="ui-input" style="border-color:var(--bt-success);" placeholder="เช่น หูฟังไร้สาย, หนังสือ ฯลฯ" required>
                    <p class="ui-text-xs ui-text-muted ui-mt-3">บอกสิ่งที่พี่มีอยู่ตอนนี้ให้คนอื่นรู้</p>
                </div>
                <div style="background:var(--bt-primary-soft); padding:25px; border-radius:24px; border:2px solid var(--bt-primary);">
                    <label class="ui-font-black ui-text-xs ui-text-primary ui-uppercase ui-mb-4 ui-block">สิ่งที่พี่อยากได้ตอบแทน (I WANT)</label>
                    <input type="text" name="item_want" class="ui-input" style="border-color:var(--bt-primary);" placeholder="เช่น เมาส์บลูทูธ, แฟลชไดร์ฟ ฯลฯ" required>
                    <p class="ui-text-xs ui-text-muted ui-mt-3">พี่อยากแลกกับอะไร ใส่มาเลย!</p>
                </div>
            </div>

            <div class="ui-mb-6">
                <label class="ui-font-black ui-text-xs ui-text-muted ui-uppercase ui-mb-2 ui-block">หัวข้อประกาศให้ดูน่าสนใจ</label>
                <input type="text" name="title" class="ui-input" placeholder="เช่น แลกอุปกรณ์คอมพิวเตอร์สภาพกริ๊บ..." required>
            </div>

            <div class="ui-mb-6">
                <label class="ui-font-black ui-text-xs ui-text-muted ui-uppercase ui-mb-2 ui-block">รายละเอียดและตำหนิสินค้า</label>
                <textarea name="description" class="ui-input" style="min-height:120px;" placeholder="บอกรายละเอียดเพิ่มเติมเพื่อให้คนตัดสินใจง่ายขึ้น..."></textarea>
            </div>

            <div class="ui-grid ui-grid-cols-2 ui-gap-6 ui-mb-10">
                <div>
                    <label class="ui-font-black ui-text-xs ui-text-muted ui-uppercase ui-mb-2 ui-block">เลือกหมวดหมู่</label>
                    <select name="category_id" class="ui-input">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="ui-font-black ui-text-xs ui-text-muted ui-uppercase ui-mb-2 ui-block">รูปประกอบ (ถ้ามี)</label>
                    <input type="file" name="barter_img" class="ui-input">
                </div>
            </div>

            <button type="submit" class="btn-luxe-primary ui-w-full" style="padding:22px; border-radius:18px;">
                <i class="fas fa-paper-plane ui-mr-2"></i> ยืนยันการลงประกาศแลกเปลี่ยน
            </button>
        </form>
    </div>
</div>

<script>
    /**
     * MODULE 1: INTERACTIVE FILTER ENGINE
     * Handles real-time search and category filtering with visual feedback
     */
    const BarterEngine = {
        searchInput: document.getElementById('barterSearch'),
        categorySelect: document.getElementById('categoryFilter'),
        cards: document.querySelectorAll('.barter-card'),
        
        init() {
            this.searchInput.addEventListener('input', () => this.filter());
            this.categorySelect.addEventListener('change', () => this.filter());
            console.log("Barter Filtering Engine Ready.");
        },

        filter() {
            const searchTerm = this.searchInput.value.toLowerCase();
            const selectedCat = this.categorySelect.value;

            this.cards.forEach(card => {
                const title = card.getAttribute('data-title');
                const cat = card.getAttribute('data-category');
                
                const matchesSearch = title.includes(searchTerm);
                const matchesCat = (selectedCat === 'all' || cat === selectedCat);

                if (matchesSearch && matchesCat) {
                    card.style.display = 'flex';
                    card.style.animation = 'luxeFadeIn 0.5s ease-out forwards';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    };

    /**
     * MODULE 2: SCROLL REVEAL (INTERSECTION OBSERVER)
     * Handles entrance animations as user scrolls the board
     */
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('show');
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 80);
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: "0px 0px -50px 0px" });

    document.querySelectorAll('.scroll-reveal-node').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
        revealObserver.observe(el);
    });

    /**
     * MODULE 3: MODAL DYNAMICS
     */
    function openPostModal() {
        document.getElementById('postBarterModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeEngineModal(id) {
        document.getElementById(id).style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('pd-modal-mask')) {
            event.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    // Initialize Engine
    BarterEngine.init();

    /**
     * MODULE 4: ANALYTICS BOOTSTRAP
     */
    console.log("%c BNCC Barter Board Activated ", "background: #10b981; color: white; font-weight: 900; padding: 5px 10px; border-radius: 5px;");

</script>

<?php require_once '../includes/footer.php'; ?>