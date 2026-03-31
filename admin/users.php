<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = 'Quản lý Người dùng';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_active') {
        $id = (int)$_POST['id'];
        if ($id !== (int)$_SESSION['user_id']) { // Can't deactivate self
            $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id=?")->execute([$id]);
        }
        header('Location: users.php'); exit;
    }
    if ($action === 'change_role') {
        $id = (int)$_POST['id']; $role = $_POST['role'];
        if ($id !== (int)$_SESSION['user_id'] && in_array($role,['user','admin'])) {
            $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$id]);
        }
        header('Location: users.php?msg=saved'); exit;
    }
}

$page   = max(1,(int)($_GET['page']??1)); $limit=15; $offset=($page-1)*$limit;
$total  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pages  = ceil($total/$limit);
$users  = $pdo->query("SELECT u.*,(SELECT COUNT(*) FROM orders WHERE user_id=u.id) as order_count FROM users u ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html><html lang="vi">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> | Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= SITE_URL ?>/admin/assets/css/admin.css" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-main"><?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="admin-content">
<div class="content-header">
    <div><h2 class="content-title">Người dùng</h2><p class="content-subtitle"><?= $total ?> tài khoản</p></div>
</div>
<?php if(isset($_GET['msg'])): ?><div class="admin-flash" style="background:rgba(82,183,136,0.1);border:1px solid rgba(82,183,136,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#52b788;"><i class="bi bi-check-circle-fill"></i> Đã cập nhật!</div><?php endif; ?>
<div class="admin-card"><div class="admin-card-body p-0"><div class="table-responsive">
<table class="admin-table">
<thead><tr><th>Người dùng</th><th>Email</th><th>SĐT</th><th>Đơn hàng</th><th>Quyền</th><th>Trạng thái</th><th></th></tr></thead>
<tbody>
<?php foreach($users as $u): ?>
<tr>
    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:50%;background:<?= $u['role']==='admin'?'var(--gold)':'var(--bg-secondary)' ?>;color:<?= $u['role']==='admin'?'#000':'var(--text-muted)' ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;"><?= strtoupper(mb_substr($u['name'],0,1)) ?></div>
            <div style="font-weight:500;color:var(--cream);"><?= clean($u['name']) ?></div>
        </div>
    </td>
    <td style="color:var(--text-muted);font-size:13px;"><?= clean($u['email']) ?></td>
    <td style="color:var(--text-muted);font-size:13px;"><?= clean($u['phone']??'—') ?></td>
    <td><span style="background:rgba(201,169,110,0.1);color:var(--gold);padding:3px 10px;border-radius:100px;font-size:11px;"><?= $u['order_count'] ?></span></td>
    <td>
        <?php if($u['id'] != $_SESSION['user_id']): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="change_role"><input type="hidden" name="id" value="<?= $u['id'] ?>">
            <select name="role" onchange="this.form.submit()" style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:4px 8px;cursor:pointer;outline:none;">
                <option value="user" <?= $u['role']==='user'?'selected':'' ?>>User</option>
                <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
            </select>
        </form>
        <?php else: ?>
        <span style="color:var(--gold);font-size:12px;"><i class="bi bi-shield-fill"></i> Admin (bạn)</span>
        <?php endif; ?>
    </td>
    <td>
        <span class="status-badge <?= $u['is_active']?'status-done':'status-cancelled' ?>">
            <?= $u['is_active']?'Hoạt động':'Đã khóa' ?>
        </span>
    </td>
    <td>
        <?php if($u['id'] != $_SESSION['user_id']): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="toggle_active"><input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button type="submit" class="<?= $u['is_active']?'admin-btn-danger':'admin-btn-sm' ?>" title="<?= $u['is_active']?'Khóa':'Mở khóa' ?>">
                <i class="bi bi-<?= $u['is_active']?'lock':'unlock' ?>"></i>
            </button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php if($pages>1): ?><div class="admin-pagination mt-3"><?php for($i=1;$i<=$pages;$i++): ?><a href="?page=<?= $i ?>" class="page-link-custom <?= $i===$page?'active':'' ?>"><?= $i ?></a><?php endfor; ?></div><?php endif; ?>
</div></div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/admin/assets/js/admin.js"></script>
<script>var SITE_URL='<?= SITE_URL ?>';</script>
</body></html>
