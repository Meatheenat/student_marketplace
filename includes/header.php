<?php
/**
 * BNCC Market - Master Header (Refined UX/UI)
 * สำหรับโปรเจกต์ Student Marketplace โดยเฉพาะ
 */

// 🛠️ อัปเกรด: ใช้ __DIR__ เพื่อให้ Path ถูกต้องเสมอ ไม่ว่าจะเรียกจากโฟลเดอร์ไหน
require_once __DIR__ . '/functions.php';

// 1. ตรวจสอบชื่อไฟล์ปัจจุบัน
$current_page = basename($_SERVER['PHP_SELF']);

// 2. การตั้งค่าความปลอดภัยเบื้องต้น
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. รายการหน้าที่ต้องซ่อนเมนูเฉพาะจุด
$hide_home_list = ['login.php', 'register.php', 'register_google.php'];
$hide_auth_list = ['index.php', 'register_seller.php', 'product_detail.php'];

// 4. ฟังก์ชันจัดการรูปโปรไฟล์
// 🎯 ชี้เป้าไปที่โฟลเดอร์เดียวกันให้หมด จะได้ไม่แตก
$user_avatar = isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img']) 
                ? "../assets/images/profiles/" . $_SESSION['profile_img'] 
                : "../assets/images/profiles/default_profile.png";

// 5. เช็กจำนวนข้อความแชทที่ยังไม่ได้อ่าน (ถ้าล็อกอินแล้ว)
$unread_msg_count = 0;
if (isLoggedIn()) {
    $db = getDB();
    $msg_stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $msg_stmt->execute([$_SESSION['user_id']]);
    $unread_msg_count = $msg_stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'BNCC Market'; ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/student_marketplace/assets/css/style.css">

    <style>
        :root {
            --nav-height: 70px;
            --glass-bg: rgba(var(--bg-card-rgb), 0.8);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: var(--nav-height);
            display: flex;
            align-items: center;
            transition: var(--transition);
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin: 0;
            padding: 0;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-main);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 10px;
            transition: var(--transition);
        }

        .nav-link:hover {
            background: rgba(var(--primary-rgb), 0.1);
            color: var(--primary);
        }

        .user-nav-box {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #1e293b; 
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            position: relative;
        }

        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .user-info {
            text-align: left;
            line-height: 1.2;
            color: #ffffff;
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .role-badge-nav {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-top: 2px;
        }

        .badge-teacher { color: #ef4444; }
        .badge-admin { color: #f87171; }
        .badge-seller { color: #34d399; }

        .logout-icon {
            color: #ef4444;
            font-size: 1.1rem;
            margin-left: 8px;
            transition: var(--transition);
        }

        .logout-icon:hover {
            transform: scale(1.1);
            filter: brightness(1.2);
        }

        .btn-icon {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            width: 40px;
            height: 40px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .btn-icon:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .badge-count {
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 50px;
            margin-left: 5px;
        }

        .chat-icon-container {
            position: relative;
            color: #3b82f6; 
            font-size: 1.1rem;
            margin-left: 5px;
            text-decoration: none;
            transition: var(--transition);
        }
        .chat-icon-container:hover {
            transform: scale(1.1);
            filter: brightness(1.2);
        }
        .chat-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background: #ef4444;
            color: white;
            font-size: 0.6rem;
            font-weight: 800;
            padding: 2px 5px;
            border-radius: 50px;
            border: 2px solid #1e293b;
        }
        
        .toolbar-icon {
            color: #94a3b8;
            font-size: 1.1rem;
            margin-left: 5px;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
        }
        .toolbar-icon:hover {
            color: white;
            transform: scale(1.1);
        }

        /* 🎯 🛠️ Style สำหรับระบบแจ้งเตือน Dropdown */
        .notif-dropdown {
            position: absolute; top: 55px; right: 80px; width: 320px; background: var(--bg-card);
            border: 1px solid var(--border-color); border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            display: none; flex-direction: column; overflow: hidden; z-index: 1001; opacity: 0;
            transform: translateY(-10px); transition: all 0.2s ease;
        }
        .notif-dropdown.show { display: flex; opacity: 1; transform: translateY(0); }
        .notif-header { padding: 15px; border-bottom: 1px solid var(--border-color); font-weight: 700; display: flex; justify-content: space-between; color: var(--text-main); background: var(--bg-card); }
        .notif-body { max-height: 350px; overflow-y: auto; background: var(--bg-body); }
        .notif-item { padding: 12px 15px; border-bottom: 1px solid var(--border-color); display: flex; gap: 12px; text-decoration: none; color: var(--text-main); transition: 0.2s; align-items: flex-start; }
        .notif-item:hover { background: rgba(99, 102, 241, 0.05); }
        .notif-unread { background: rgba(99, 102, 241, 0.1); }
        .notif-text { font-size: 0.85rem; line-height: 1.4; margin-bottom: 5px; }
        .notif-time { font-size: 0.7rem; color: var(--text-muted); }
    </style>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark-theme');
            }
        })();
    </script>
</head>
<body>

<nav class="navbar">
    <div class="container nav-content">
        <a href="../pages/index.php" class="nav-brand">
            <i class="fas fa-shopping-basket"></i>
            <span>BNCC Market</span>
        </a>

        <ul class="nav-links">
            <li>
                <button id="theme-toggle" class="btn-icon" title="สลับโหมด">
                    <i class="fas fa-sun" id="theme-icon"></i>
                </button>
            </li>

            <?php if (!in_array($current_page, $hide_home_list)): ?>
                <li>
                    <a href="../pages/index.php" class="nav-link">
                        <i class="fas fa-house"></i> <span>หน้าแรก</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
                
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher'): ?>
                    <li><a href="../admin/admin_dashboard.php" class="nav-link text-danger"><i class="fas fa-shield-halved"></i> <?php echo $_SESSION['role'] === 'teacher' ? 'Master Admin' : 'Admin'; ?></a></li>
                    <li>
                        <a href="../admin/approve_product.php" class="nav-link">
                            <i class="fas fa-clipboard-check"></i> อนุมัติสินค้า 
                            <?php 
                                $db = getDB();
                                $count_stmt = $db->query("SELECT COUNT(*) FROM products WHERE status = 'pending'");
                                $pending_count = $count_stmt->fetchColumn();
                                if ($pending_count > 0) echo "<span class='badge-count'>$pending_count</span>";
                            ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'seller'): ?>
                    <li><a href="../seller/dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard ผู้ขาย</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'buyer'): ?>
                    <li><a href="../auth/register_seller.php" class="nav-link text-primary"><i class="fas fa-store"></i> สมัครเป็นผู้ขาย</a></li>
                <?php endif; ?>

                <li class="user-nav-box">
                    <a href="../pages/profile.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                        <img src="<?= $user_avatar ?>" class="avatar-circle" alt="Avatar">
                        <div class="user-info">
                            <div class="user-name"><?= e($_SESSION['fullname']) ?></div>
                            <?php if (in_array($_SESSION['role'], ['admin', 'seller', 'teacher'])): ?>
                                <small class="role-badge-nav <?= $_SESSION['role'] === 'teacher' ? 'badge-teacher' : ($_SESSION['role'] === 'admin' ? 'badge-admin' : 'badge-seller') ?>">
                                    <?= strtoupper(e($_SESSION['role'])) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </a>

                    <div style="position: relative; display: flex; align-items: center;">
                        <a href="javascript:void(0)" id="notif-bell" class="toolbar-icon" style="color: #f59e0b;">
                            <i class="fas fa-bell"></i>
                            <span id="notif-badge" class="chat-badge" style="display:none;">0</span>
                        </a>
                        <div id="notif-dropdown" class="notif-dropdown">
                            <div class="notif-header">
                                <span>การแจ้งเตือน</span>
                                <button onclick="markNotifAsRead()" style="background:none; border:none; color:var(--primary); font-size:0.75rem; cursor:pointer; font-weight:700;">อ่านทั้งหมด</button>
                            </div>
                            <div class="notif-body" id="notif-list">
                                <div style="padding:20px; text-align:center; color:var(--text-muted); font-size:0.85rem;">กำลังโหลด...</div>
                            </div>
                        </div>
                    </div>

                    <a href="../pages/chat.php" title="กล่องข้อความแชท" class="chat-icon-container">
                        <i class="fas fa-comment-dots"></i>
                        <?php if($unread_msg_count > 0): ?>
                            <span class="chat-badge"><?= $unread_msg_count > 99 ? '99+' : $unread_msg_count ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if ($_SESSION['role'] === 'buyer' || $_SESSION['role'] === 'seller'): ?>
                    <a href="../pages/my_orders.php" title="ประวัติการสั่งซื้อของฉัน" class="toolbar-icon" style="color: #10b981;">
                        <i class="fas fa-shopping-bag"></i>
                    </a>
                    <?php endif; ?>

                    <a href="../pages/wishlist.php" title="รายการที่ชอบ" class="toolbar-icon" style="color: #ef4444;">
                        <i class="fas fa-heart"></i>
                    </a>
                    
                    <a href="../auth/logout.php" class="logout-icon" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>
                </li>

            <?php else: ?>
                <?php if (!in_array($current_page, $hide_auth_list)): ?>
                    <li><a href="../auth/login.php" class="btn btn-outline">เข้าสู่ระบบ</a></li>
                    <li><a href="../auth/register.php" class="btn btn-primary">สมัครสมาชิก</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<?php if(isLoggedIn()): ?>
<script>
    const notifBell = document.getElementById('notif-bell');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifBadge = document.getElementById('notif-badge');
    const notifList = document.getElementById('notif-list');

    notifBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('show');
    });

    document.addEventListener('click', function(e) {
        if (!notifDropdown.contains(e.target) && e.target !== notifBell) {
            notifDropdown.classList.remove('show');
        }
    });

    function fetchNotifications() {
        fetch('../ajax/notifications_api.php?action=fetch')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.unread_count > 0) {
                    notifBadge.style.display = 'block';
                    notifBadge.innerText = data.unread_count > 99 ? '99+' : data.unread_count;
                } else {
                    notifBadge.style.display = 'none';
                }
                if(data.notifications.length > 0) {
                    let html = '';
                    data.notifications.forEach(n => {
                        const readClass = n.is_read == 0 ? 'notif-unread' : '';
                        html += `
                            <a href="${n.link}" class="notif-item ${readClass}">
                                <div style="font-size: 1.2rem; margin-top: 3px;">${n.icon}</div>
                                <div>
                                    <div class="notif-text">${n.message}</div>
                                    <div class="notif-time">${n.time}</div>
                                </div>
                            </a>
                        `;
                    });
                    notifList.innerHTML = html;
                } else {
                    notifList.innerHTML = '<div style="padding:20px; text-align:center; color:var(--text-muted); font-size:0.85rem;">ไม่มีการแจ้งเตือน</div>';
                }
            }
        });
    }

    function markNotifAsRead() {
        fetch('../ajax/notifications_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=mark_read'
        }).then(() => fetchNotifications());
    }

    fetchNotifications();
    setInterval(fetchNotifications, 10000);
</script>
<?php endif; ?>

<main class="container" style="padding-top: 2rem; min-height: calc(100vh - var(--nav-height));">