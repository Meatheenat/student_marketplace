<?php
/**
 * BNCC Market - Members & Shops Management Dashboard (Hierarchy Edition)
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

// ... โค้ดตารางด้านล่าง ปล่อยไว้เหมือนเดิมเป๊ะๆ เลยครับ ...

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
    .mgmt-header { margin-bottom: 30px; border-left: 6px solid var(--primary); padding-left: 20px; }
    .mgmt-card { background: var(--bg-card); border-radius: 24px; padding: 30px; border: 1px solid var(--border-color); margin-bottom: 40px; box-shadow: var(--shadow-md); }
    .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
    .table-custom tr { background: var(--bg-body); transition: 0.2s; }
    .table-custom td, .table-custom th { padding: 15px; }
    .table-custom th { color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
    .status-badge { padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
    .status-active { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .status-banned { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .role-badge { padding: 2px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; }
    .role-teacher { background: #ef4444; color: white; }
    .role-admin { background: #6366f1; color: white; }
    .role-seller { background: #10b981; color: white; }
</style>

<div class="mgmt-header">
    <h1 style="font-size: 2rem; font-weight: 800;">ศูนย์จัดการสมาชิกและร้านค้า</h1>
    <p style="color: var(--text-muted);">จัดการสิทธิ์การเข้าใช้งานและสถานะร้านค้าแยกจากกัน (ระดับ: <?= strtoupper($_SESSION['role']) ?>)</p>
</div>

<?php echo displayFlashMessage(); ?>

<div class="mgmt-card">
    <h2 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 25px;"><i class="fas fa-users-cog text-primary"></i> บัญชีผู้ใช้งานทั้งหมด</h2>
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
                    <td style="border-radius: 12px 0 0 12px; font-size: 0.85rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div style="font-weight: 600;"><?= e($u['fullname']) ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($u['email']) ?></div>
                    </td>
                    <td>
                        <span class="role-badge <?= $u['role'] === 'teacher' ? 'role-teacher' : ($u['role'] === 'admin' ? 'role-admin' : ($u['role'] === 'seller' ? 'role-seller' : '')) ?>" style="background: <?= $u['role'] === 'buyer' ? '#94a3b8' : '' ?>;">
                            <?= strtoupper($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['is_banned']): ?>
                            <span class="status-badge status-banned">ถูกระงับ</span>
                        <?php else: ?>
                            <span class="status-badge status-active">ปกติ</span>
                        <?php endif; ?>
                    </td>
                    <td style="border-radius: 0 12px 12px 0; text-align: right;">
                        <div style="display: flex; gap: 5px; justify-content: flex-end;">
                            <?php if ($u['id'] != $_SESSION['user_id'] && $u['role'] !== 'teacher'): ?>
                                <form action="manage_actions.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="type" value="user">
                                    <?php if ($u['is_banned']): ?>
                                        <button type="submit" name="action" value="unban" class="btn btn-sm btn-success">ปลดแบน</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="ban" class="btn btn-sm btn-danger">แบน</button>
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

<div class="mgmt-card" style="border-color: #10b981;">
    <h2 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 25px;"><i class="fas fa-store text-success"></i> รายการร้านค้าในระบบ</h2>
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
                    <td style="border-radius: 12px 0 0 12px; font-weight: 600;"><?= e($s['shop_name']) ?></td>
                    <td><?= e($s['owner_name']) ?></td>
                    <td>
                        <?php if ($s['status'] === 'approved'): ?>
                            <span class="status-badge status-active">เปิดบริการ</span>
                        <?php elseif ($s['status'] === 'blocked'): ?>
                            <span class="status-badge status-banned">ถูกสั่งปิด</span>
                        <?php endif; ?>
                    </td>
                    <td style="border-radius: 0 12px 12px 0; text-align: right;">
                        <form action="manage_actions.php" method="POST" style="display: inline;">
                            <input type="hidden" name="target_id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="type" value="shop">
                            <?php if ($s['status'] === 'approved'): ?>
                                <button type="submit" name="action" value="block" class="btn btn-sm btn-warning">สั่งปิดร้าน</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="unblock" class="btn btn-sm btn-primary">เปิดร้านใหม่</button>
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
<div class="mgmt-card" style="border-color: #ef4444; background: rgba(239, 68, 68, 0.02);">
    <h2 style="font-size: 1.4rem; font-weight: 700; color: #ef4444; margin-bottom: 10px;">
        <i class="fas fa-crown"></i> อัปเกรดระดับสิทธิ์ (Master Role Manager)
    </h2>
    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">
        * คุณครูสามารถปรับเปลี่ยนยศของสมาชิกทุกคนในระบบได้แบบอิสระ (เปลี่ยนนักเรียนเป็นแอดมิน หรือเพิ่มครูคนอื่น)
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
                    <td style="font-weight: 600; border-radius: 12px 0 0 12px;"><?= e($u['fullname']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <span class="role-badge <?= $u['role'] === 'teacher' ? 'role-teacher' : ($u['role'] === 'admin' ? 'role-admin' : ($u['role'] === 'seller' ? 'role-seller' : '')) ?>" style="background: <?= $u['role'] === 'buyer' ? '#94a3b8' : '' ?>;">
                            <?= strtoupper($u['role']) ?>
                        </span>
                    </td>
                    <td style="border-radius: 0 12px 12px 0; text-align: right;">
                        
                        <form action="manage_actions.php" method="POST" style="display: inline-flex; align-items: center; gap: 8px; justify-content: flex-end;">
                            <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="type" value="change_role">
                            
                            <select name="new_role" class="form-control" style="padding: 6px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 0.85rem; outline: none; background: white; cursor: pointer;">
                                <option value="buyer" <?= $u['role'] === 'buyer' ? 'selected' : '' ?>>👤 ผู้ใช้ปกติ (Buyer)</option>
                                <option value="seller" <?= $u['role'] === 'seller' ? 'selected' : '' ?>>🏪 คนขาย (Seller)</option>
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>🛡️ นักเรียน (Admin)</option>
                                <option value="teacher" <?= $u['role'] === 'teacher' ? 'selected' : '' ?>>👑 ครู (Teacher)</option>
                            </select>
                            
                            <button type="submit" class="btn btn-sm btn-danger" style="padding: 6px 15px; border-radius: 8px; font-weight: 700;">
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

<?php require_once '../includes/footer.php'; ?>