<?php
/**
 * BNCC Market - Members & Shops Management Dashboard (Hierarchy Edition)
 * [SOLID HIGH-CONTRAST REDESIGN]
 */

// 🛠️ 1. ต้องเรียก functions.php เป็นอันดับแรกสุด! (เพื่อเปิดระบบ Session และกันชน ob_start ก่อนที่ HTML จะหลุดออกไป)
require_once '../includes/functions.php';

// 🛠️ 2. ตรวจสอบสิทธิ์ให้เสร็จเรียบร้อยก่อน
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    redirect('../pages/index.php');
}

// 🛠️ 3. ตรวจสอบผ่านแล้ว ค่อยอนุญาตให้โหลดส่วนหัว (HTML) มาแสดงผล
$pageTitle = "จัดการสมาชิกและร้านค้า - BNCC Market";
require_once '../includes/header.php';

$db = getDB();

// 2. ดึงข้อมูลสมาชิกทั้งหมด 
$user_stmt = $db->query("SELECT id, fullname, email, role, is_banned, created_at FROM users ORDER BY created_at DESC");
$users = $user_stmt->fetchAll();

// 3. ดึงข้อมูลร้านค้าทั้งหมด JOIN กับชื่อเจ้าของ 
$shop_stmt = $db->query("
    SELECT s.*, u.fullname as owner_name 
    FROM shops s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.status != 'pending' 
    ORDER BY s.created_at DESC
");
$shops = $shop_stmt->fetchAll();
?>

<style>
    /* ============================================================
       🛠️ SOLID DESIGN SYSTEM - MEMBER MANAGEMENT
       ============================================================ */
    :root {
        --solid-bg: #f8fafc;
        --solid-card: #ffffff;
        --solid-text: #0f172a;
        --solid-border: #cbd5e1;
        --solid-primary: #4f46e5;
        --solid-danger: #ef4444;
        --solid-success: #10b981;
    }

    .dark-theme {
        --solid-bg: #0f172a;
        --solid-card: #1e293b;
        --solid-text: #ffffff;
        --solid-border: #334155;
    }

    body { background-color: var(--solid-bg) !important; color: var(--solid-text); }

    .admin-wrapper { max-width: 1200px; margin: 40px auto 80px; padding: 0 20px; }

    /* 🏰 Header */
    .mgmt-header { 
        margin-bottom: 40px; 
        border-left: 6px solid var(--solid-primary); 
        padding-left: 25px; 
        animation: dropIn 0.5s ease forwards;
    }
    .mgmt-header h1 { font-size: 2.2rem; font-weight: 900; margin: 0 0 5px; color: var(--solid-text); letter-spacing: -1px; }

    /* 🧱 Section Cards */
    .mgmt-card { 
        background: var(--solid-card); 
        border-radius: 24px; 
        border: 2px solid var(--solid-border); 
        padding: 30px; 
        margin-bottom: 50px; 
        box-shadow: 0 20px 40px rgba(0,0,0,0.05); 
        overflow: hidden;
        opacity: 0;
        transform: translateY(20px);
        animation: dropIn 0.8s ease forwards;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 900;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--solid-text);
        border-bottom: 3px solid var(--solid-border);
        padding-bottom: 15px;
    }

    /* 📋 Solid Tables */
    .table-custom { width: 100%; border-collapse: collapse; }
    .table-custom th { 
        background: var(--solid-bg); 
        color: var(--text-muted); 
        font-size: 0.85rem; 
        text-transform: uppercase; 
        letter-spacing: 1px; 
        padding: 20px; 
        border-bottom: 2px solid var(--solid-border); 
        text-align: left;
    }
    .table-custom td { 
        padding: 20px; 
        border-bottom: 1px solid var(--solid-border); 
        vertical-align: middle;
    }
    .table-custom tr:last-child td { border-bottom: none; }
    .table-custom tr:hover td { background: rgba(79, 70, 229, 0.03); }

    /* 🏷️ Badges */
    .status-badge { padding: 6px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; border: 2px solid transparent; }
    .status-active { background: rgba(16, 185, 129, 0.1); color: var(--solid-success); border-color: rgba(16, 185, 129, 0.3); }
    .status-banned { background: rgba(239, 68, 68, 0.1); color: var(--solid-danger); border-color: rgba(239, 68, 68, 0.3); }
    
    .role-badge { padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .role-teacher { background: var(--solid-danger); color: white; }
    .role-admin { background: var(--solid-primary); color: white; }
    .role-seller { background: var(--solid-success); color: white; }

    /* 🔘 Buttons */
    .btn-solid-action {
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 800;
        font-size: 0.85rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
        transition: 0.2s;
        text-decoration: none;
    }
    .btn-solid-danger { background: var(--solid-danger); color: white; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }
    .btn-solid-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(239, 68, 68, 0.4); }
    
    .btn-solid-success { background: var(--solid-success); color: white; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); }
    .btn-solid-success:hover { transform: translateY(-2px); filter: brightness(1.1); }

    .btn-solid-warning { background: #f59e0b; color: white; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3); }
    .btn-solid-warning:hover { transform: translateY(-2px); filter: brightness(1.1); }

    .btn-solid-primary { background: var(--solid-primary); color: white; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); }
    .btn-solid-primary:hover { transform: translateY(-2px); filter: brightness(1.1); }

    /* 🎯 Select Dropdown (แก้ปัญหามองไม่เห็นตัวอักษร) */
    .select-solid {
        padding: 10px 15px;
        border-radius: 12px;
        border: 2px solid var(--solid-border);
        font-size: 0.9rem;
        outline: none;
        background: var(--solid-bg); /* พื้นหลังทึบ */
        color: var(--solid-text); /* สีข้อความชัดเจน */
        cursor: pointer;
        font-weight: 700;
        transition: 0.2s;
        min-width: 200px;
    }
    
    .select-solid:focus {
        border-color: var(--solid-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }

    /* บังคับสี option ด้านในให้อ่านง่าย */
    .select-solid option {
        background: var(--solid-card);
        color: var(--solid-text);
        font-weight: 600;
        padding: 10px;
    }

    @keyframes dropIn { to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .table-custom th { display: none; }
        .table-custom tr { display: block; border-bottom: 3px solid var(--solid-border); }
        .table-custom td { display: block; text-align: right; padding: 15px; border-bottom: 1px dashed var(--solid-border); }
        .table-custom td::before { content: attr(data-label); float: left; font-weight: 800; color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; }
    }
</style>

<div class="admin-wrapper">
    <div class="mgmt-header">
        <h1><i class="fas fa-users-cog text-primary"></i> ศูนย์จัดการสมาชิกและร้านค้า</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;">จัดการสิทธิ์การเข้าใช้งานและสถานะร้านค้าแยกจากกัน (ระดับ: <?= strtoupper($_SESSION['role']) ?>)</p>
    </div>

    <?php echo displayFlashMessage(); ?>

    <div class="mgmt-card" style="animation-delay: 0.1s;">
        <h2 class="section-title"><i class="fas fa-user-friends text-primary"></i> บัญชีผู้ใช้งานทั้งหมด</h2>
        <div style="overflow-x: auto;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>วันที่สมัคร</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>สิทธิ์</th>
                        <th>สถานะ</th>
                        <th style="text-align: right;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td data-label="วันที่สมัคร" style="font-weight: 700; color: var(--solid-text);">
                            <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                        </td>
                        <td data-label="ชื่อ-นามสกุล">
                            <div style="font-weight: 800; font-size: 1.1rem; color: var(--solid-text);"><?= e($u['fullname']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;"><i class="fas fa-envelope"></i> <?= e($u['email']) ?></div>
                        </td>
                        <td data-label="สิทธิ์">
                            <span class="role-badge <?= $u['role'] === 'teacher' ? 'role-teacher' : ($u['role'] === 'admin' ? 'role-admin' : ($u['role'] === 'seller' ? 'role-seller' : '')) ?>" style="background: <?= $u['role'] === 'buyer' ? '#94a3b8' : '' ?>;">
                                <?= strtoupper($u['role']) ?>
                            </span>
                        </td>
                        <td data-label="สถานะ">
                            <?php if ($u['is_banned']): ?>
                                <span class="status-badge status-banned"><i class="fas fa-ban"></i> ถูกระงับ</span>
                            <?php else: ?>
                                <span class="status-badge status-active"><i class="fas fa-check-circle"></i> ปกติ</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="การจัดการ" style="text-align: right;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <?php if ($u['id'] != $_SESSION['user_id'] && $u['role'] !== 'teacher'): ?>
                                    <form action="manage_actions.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="type" value="user">
                                        <?php if ($u['is_banned']): ?>
                                            <button type="submit" name="action" value="unban" class="btn-solid-action btn-solid-success" onclick="return confirm('ยืนยันปลดแบนผู้ใช้นี้?')"><i class="fas fa-unlock"></i> ปลดแบน</button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="ban" class="btn-solid-action btn-solid-danger" onclick="return confirm('ยืนยันแบนผู้ใช้นี้?')"><i class="fas fa-ban"></i> แบน</button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mgmt-card" style="border-color: var(--solid-success); animation-delay: 0.2s;">
        <h2 class="section-title"><i class="fas fa-store text-success"></i> รายการร้านค้าในระบบ</h2>
        <div style="overflow-x: auto;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>ชื่อร้านค้า</th>
                        <th>เจ้าของร้าน</th>
                        <th>สถานะร้าน</th>
                        <th style="text-align: right;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shops as $s): ?>
                    <tr>
                        <td data-label="ชื่อร้านค้า">
                            <div style="font-weight: 800; font-size: 1.1rem; color: var(--solid-text);"><?= e($s['shop_name']) ?></div>
                        </td>
                        <td data-label="เจ้าของร้าน" style="font-weight: 600; color: var(--text-muted);">
                            <i class="fas fa-user-circle"></i> <?= e($s['owner_name']) ?>
                        </td>
                        <td data-label="สถานะร้าน">
                            <?php if ($s['status'] === 'approved'): ?>
                                <span class="status-badge status-active"><i class="fas fa-door-open"></i> เปิดบริการ</span>
                            <?php elseif ($s['status'] === 'blocked'): ?>
                                <span class="status-badge status-banned"><i class="fas fa-door-closed"></i> ถูกสั่งปิด</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="การจัดการ" style="text-align: right;">
                            <form action="manage_actions.php" method="POST" style="display: flex; gap: 8px; justify-content: flex-end;">
                                <input type="hidden" name="target_id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="type" value="shop">
                                <?php if ($s['status'] === 'approved'): ?>
                                    <button type="submit" name="action" value="block" class="btn-solid-action btn-solid-warning" onclick="return confirm('ยืนยันสั่งปิดร้านค้านี้?')"><i class="fas fa-store-slash"></i> สั่งปิดร้าน</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="unblock" class="btn-solid-action btn-solid-primary" onclick="return confirm('ยืนยันอนุญาตให้เปิดร้านใหม่?')"><i class="fas fa-store"></i> เปิดร้านใหม่</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($_SESSION['role'] === 'teacher'): ?>
    <div class="mgmt-card" style="border-color: var(--solid-danger); background: rgba(239, 68, 68, 0.02); animation-delay: 0.3s;">
        <h2 class="section-title" style="color: var(--solid-danger); border-bottom-color: rgba(239, 68, 68, 0.3);">
            <i class="fas fa-crown"></i> อัปเกรดระดับสิทธิ์ (Master Role Manager)
        </h2>
        <p style="font-size: 1rem; font-weight: 600; color: var(--text-muted); margin-bottom: 30px;">
            * คุณครูสามารถปรับเปลี่ยนยศของสมาชิกทุกคนในระบบได้แบบอิสระ
        </p>
        
        <div style="overflow-x: auto;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>ชื่อผู้ใช้งาน</th>
                        <th>อีเมล</th>
                        <th>ยศปัจจุบัน</th>
                        <th style="text-align: right;">ปรับแต่งสิทธิ์</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): if ($u['id'] == $_SESSION['user_id']) continue; ?>
                    <tr>
                        <td data-label="ชื่อผู้ใช้งาน" style="font-weight: 800; color: var(--solid-text);"><?= e($u['fullname']) ?></td>
                        <td data-label="อีเมล" style="color: var(--text-muted); font-weight: 600;"><?= e($u['email']) ?></td>
                        <td data-label="ยศปัจจุบัน">
                            <span class="role-badge <?= $u['role'] === 'teacher' ? 'role-teacher' : ($u['role'] === 'admin' ? 'role-admin' : ($u['role'] === 'seller' ? 'role-seller' : '')) ?>" style="background: <?= $u['role'] === 'buyer' ? '#94a3b8' : '' ?>;">
                                <?= strtoupper($u['role']) ?>
                            </span>
                        </td>
                        <td data-label="ปรับแต่งสิทธิ์" style="text-align: right;">
                            <form action="manage_actions.php" method="POST" style="display: inline-flex; align-items: center; gap: 15px; justify-content: flex-end;">
                                <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="type" value="change_role">
                                
                                <select name="new_role" class="select-solid">
                                    <option value="buyer" <?= $u['role'] === 'buyer' ? 'selected' : '' ?>>👤 ผู้ใช้ปกติ (Buyer)</option>
                                    <option value="seller" <?= $u['role'] === 'seller' ? 'selected' : '' ?>>🏪 คนขาย (Seller)</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>🛡️ นักเรียน (Admin)</option>
                                    <option value="teacher" <?= $u['role'] === 'teacher' ? 'selected' : '' ?>>👑 ครู (Teacher)</option>
                                </select>
                                
                                <button type="submit" class="btn-solid-action btn-solid-danger" onclick="return confirm('ยืนยันเปลี่ยนสิทธิ์ผู้ใช้นี้?')">
                                    <i class="fas fa-save"></i> บันทึก
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>