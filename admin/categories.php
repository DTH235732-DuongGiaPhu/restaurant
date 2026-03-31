<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = 'Quản lý Danh mục';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = (int)($_POST['id']??0);
        $name = clean($_POST['name']??'');
        $icon = clean($_POST['icon']??'utensils');
        $sort = (int)($_POST['sort_order']??0);
        if ($id) $pdo->prepare("UPDATE categories SET name=?,icon=?,sort_order=? WHERE id=?")->execute([$name,$icon,$sort,$id]);
        else $pdo->prepare("INSERT INTO categories (name,icon,sort_order) VALUES (?,?,?)")->execute([$name,$icon,$sort]);
        header('Location: categories.php?msg=saved'); exit;
    }
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['id']]);
        header('Location: categories.php?msg=deleted'); exit;
    }
}
$editCat = null;
if (isset($_GET['edit'])) { $s=$pdo->prepare("SELECT * FROM categories WHERE id=?");$s->execute([(int)$_GET['edit']]);$editCat=$s->fetch(); }
$categories = $pdo->query("SELECT c.*,(SELECT COUNT(*) FROM dishes WHERE category_id=c.id) as dish_count FROM categories c ORDER BY c.sort_order")->fetchAll();
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
    <div><h2 class="content-title">Danh mục</h2><p class="content-subtitle"><?= count($categories) ?> danh mục</p></div>
    <a href="?edit=new" class="admin-btn"><i class="bi bi-plus-lg"></i> Thêm danh mục</a>
</div>
<?php if(isset($_GET['msg'])): ?>
<div class="admin-flash" style="background:rgba(82,183,136,0.1);border:1px solid rgba(82,183,136,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#52b788;"><i class="bi bi-check-circle-fill"></i> Thao tác thành công!</div>
<?php endif; ?>
<div class="admin-card"><div class="admin-card-body p-0"><div class="table-responsive">
<table class="admin-table">
<thead><tr><th>Icon</th><th>Tên danh mục</th><th>Thứ tự</th><th>Số món</th><th></th></tr></thead>
<tbody>
<?php foreach($categories as $c): ?>
<tr>
    <td><div style="width:38px;height:38px;border-radius:8px;background:rgba(201,169,110,0.1);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:1.1rem;"><i class="bi bi-<?= $c['icon'] ?>"></i></div></td>
    <td style="color:var(--cream);font-weight:500;"><?= clean($c['name']) ?></td>
    <td style="color:var(--text-muted);"><?= $c['sort_order'] ?></td>
    <td><span style="background:rgba(201,169,110,0.1);color:var(--gold);padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600;"><?= $c['dish_count'] ?> món</span></td>
    <td style="white-space:nowrap;">
        <a href="?edit=<?= $c['id'] ?>" class="admin-btn-sm"><i class="bi bi-pencil"></i></a>
        <form method="POST" style="display:inline;"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button type="submit" class="admin-btn-danger" data-confirm="Xóa '<?= clean($c['name']) ?>'? Tất cả món ăn trong danh mục cũng bị xóa!"><i class="bi bi-trash3"></i></button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
</div></div>

<!-- Modal -->
<?php if($editCat !== null || isset($_GET['edit'])): ?>
<div class="admin-modal show">
<div class="admin-modal-content">
<div class="admin-modal-header">
    <h6><?= $editCat?'Sửa danh mục':'Thêm danh mục' ?></h6>
    <a href="categories.php" class="admin-modal-close"><i class="bi bi-x-lg"></i></a>
</div>
<form method="POST">
<input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="<?= $editCat?$editCat['id']:0 ?>">
<div class="admin-modal-body">
    <div class="admin-form-group"><label class="admin-label">Tên danh mục *</label>
    <input type="text" name="name" class="admin-input" value="<?= $editCat?clean($editCat['name']):'' ?>" required></div>
    <div class="admin-form-group"><label class="admin-label">Bootstrap Icon (vd: utensils, fish, coffee)</label>
    <input type="text" name="icon" class="admin-input" value="<?= $editCat?clean($editCat['icon']):'utensils' ?>">
    <small style="color:var(--text-muted);font-size:11px;">Xem icon tại <a href="https://icons.getbootstrap.com" target="_blank" style="color:var(--gold);">icons.getbootstrap.com</a></small></div>
    <div class="admin-form-group"><label class="admin-label">Thứ tự hiển thị</label>
    <input type="number" name="sort_order" class="admin-input" value="<?= $editCat?$editCat['sort_order']:0 ?>"></div>
</div>
<div class="admin-modal-footer">
    <a href="categories.php" class="admin-btn-sm">Hủy</a>
    <button type="submit" class="admin-btn"><i class="bi bi-save"></i> Lưu</button>
</div>
</form>
</div></div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/admin/assets/js/admin.js"></script>
<script>var SITE_URL='<?= SITE_URL ?>';</script>
</body></html>
