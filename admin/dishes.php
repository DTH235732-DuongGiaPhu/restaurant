<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = 'Quản lý Món ăn';

// CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $dish = $pdo->prepare("SELECT image FROM dishes WHERE id=?"); $dish->execute([$id]); $d=$dish->fetch();
        if ($d && $d['image']) @unlink(__DIR__.'/../uploads/dishes/'.$d['image']);
        $pdo->prepare("DELETE FROM dishes WHERE id=?")->execute([$id]);
        header('Location: dishes.php?msg=deleted'); exit;
    }

    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $cat         = (int)$_POST['category_id'];
        $name        = clean($_POST['name'] ?? '');
        $desc        = clean($_POST['description'] ?? '');
        $price       = (int)$_POST['price'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $status      = $_POST['status'] === 'active' ? 'active' : 'inactive';

        $imgFile = $_FILES['image'] ?? null;
        $imgName = $_POST['old_image'] ?? null;
        if ($imgFile && $imgFile['size'] > 0) {
            $r = uploadImage($imgFile);
            if (!isset($r['error'])) $imgName = $r['filename'];
        }

        if ($id > 0) {
            $pdo->prepare("UPDATE dishes SET category_id=?,name=?,description=?,price=?,is_featured=?,status=?,image=? WHERE id=?")
                ->execute([$cat,$name,$desc,$price,$is_featured,$status,$imgName,$id]);
        } else {
            $pdo->prepare("INSERT INTO dishes (category_id,name,description,price,is_featured,status,image) VALUES (?,?,?,?,?,?,?)")
                ->execute([$cat,$name,$desc,$price,$is_featured,$status,$imgName]);
        }
        header('Location: dishes.php?msg=saved'); exit;
    }
}

// Edit
$editDish = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM dishes WHERE id=?"); $s->execute([(int)$_GET['edit']]);
    $editDish = $s->fetch();
}

$cats    = $pdo->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();
$catFilt = (int)($_GET['cat']??0);
$where   = $catFilt ? "WHERE d.category_id=$catFilt" : '';
$dishes  = $pdo->query("SELECT d.*,c.name as cat_name FROM dishes d JOIN categories c ON d.category_id=c.id $where ORDER BY c.sort_order,d.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> | Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,400&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= SITE_URL ?>/admin/assets/css/admin.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="admin-content">
<div class="content-header">
    <div><h2 class="content-title">Món ăn</h2><p class="content-subtitle"><?= count($dishes) ?> món trong hệ thống</p></div>
    <button class="admin-btn" onclick="openModal()"><i class="bi bi-plus-lg"></i> Thêm món</button>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="admin-flash" style="background:rgba(82,183,136,0.1);border:1px solid rgba(82,183,136,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#52b788;">
    <i class="bi bi-check-circle-fill"></i> <?= $_GET['msg']==='saved'?'Đã lưu thành công!':'Đã xóa thành công!' ?>
</div>
<?php endif; ?>

<!-- Category Filter -->
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;">
    <a href="dishes.php" style="background:var(--bg-card);border:1px solid <?= !$catFilt?'var(--gold)':'var(--border)' ?>;border-radius:100px;padding:6px 16px;font-size:12px;color:<?= !$catFilt?'var(--gold)':'var(--text-muted)' ?>;">Tất cả</a>
    <?php foreach ($cats as $c): ?>
    <a href="?cat=<?= $c['id'] ?>" style="background:var(--bg-card);border:1px solid <?= $catFilt==$c['id']?'var(--gold)':'var(--border)' ?>;border-radius:100px;padding:6px 16px;font-size:12px;color:<?= $catFilt==$c['id']?'var(--gold)':'var(--text-muted)' ?>;"><?= clean($c['name']) ?></a>
    <?php endforeach; ?>
</div>

<div class="admin-card">
<div class="admin-card-body p-0">
<div class="table-responsive">
<table class="admin-table">
<thead><tr><th>Ảnh</th><th>Tên món</th><th>Danh mục</th><th>Giá</th><th>Nổi bật</th><th>Trạng thái</th><th></th></tr></thead>
<tbody>
<?php foreach ($dishes as $d): ?>
<tr>
    <td><img src="<?= getDishImage($d['image']) ?>" style="width:52px;height:52px;border-radius:8px;object-fit:cover;" onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=60&h=60&fit=crop'"></td>
    <td style="font-weight:500;color:var(--cream);"><?= clean($d['name']) ?></td>
    <td style="color:var(--text-muted);font-size:12px;"><?= clean($d['cat_name']) ?></td>
    <td style="color:var(--gold);font-weight:700;"><?= formatPrice($d['price']) ?></td>
    <td><?= $d['is_featured']?'<span style="color:var(--gold)">⭐ Có</span>':'<span style="color:var(--text-muted)">—</span>' ?></td>
    <td><span class="status-badge <?= $d['status']==='active'?'status-done':'status-cancelled' ?>"><?= $d['status']==='active'?'Hiện':'Ẩn' ?></span></td>
    <td style="white-space:nowrap;">
        <a href="?edit=<?= $d['id'] ?>" class="admin-btn-sm" onclick="openEditModal(<?= $d['id'] ?>)"><i class="bi bi-pencil"></i></a>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $d['id'] ?>">
            <button type="submit" class="admin-btn-danger" data-confirm="Xóa món '<?= clean($d['name']) ?>'?"><i class="bi bi-trash3"></i></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div></div>

<!-- Add/Edit Modal -->
<div class="admin-modal <?= $editDish?'show':'' ?>" id="dishModal">
<div class="admin-modal-content">
<div class="admin-modal-header">
    <h6><?= $editDish?'Chỉnh sửa món ăn':'Thêm món mới' ?></h6>
    <a href="dishes.php" class="admin-modal-close"><i class="bi bi-x-lg"></i></a>
</div>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="<?= $editDish?$editDish['id']:0 ?>">
<input type="hidden" name="old_image" value="<?= $editDish?$editDish['image']:'' ?>">
<div class="admin-modal-body">
<div class="row g-3">
<div class="col-12">
    <div class="admin-form-group">
        <label class="admin-label">Tên món *</label>
        <input type="text" name="name" class="admin-input" value="<?= $editDish?clean($editDish['name']):'' ?>" required>
    </div>
</div>
<div class="col-md-6">
    <div class="admin-form-group">
        <label class="admin-label">Danh mục *</label>
        <select name="category_id" class="admin-input" required>
            <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $editDish&&$editDish['category_id']==$c['id']?'selected':'' ?>><?= clean($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="col-md-6">
    <div class="admin-form-group">
        <label class="admin-label">Giá (VNĐ) *</label>
        <input type="number" name="price" class="admin-input" value="<?= $editDish?$editDish['price']:'' ?>" min="0" step="1000" required>
    </div>
</div>
<div class="col-12">
    <div class="admin-form-group">
        <label class="admin-label">Mô tả</label>
        <textarea name="description" class="admin-input"><?= $editDish?clean($editDish['description']):'' ?></textarea>
    </div>
</div>
<div class="col-md-6">
    <div class="admin-form-group">
        <label class="admin-label">Trạng thái</label>
        <select name="status" class="admin-input">
            <option value="active" <?= !$editDish||$editDish['status']==='active'?'selected':'' ?>>Hiển thị</option>
            <option value="inactive" <?= $editDish&&$editDish['status']==='inactive'?'selected':'' ?>>Ẩn</option>
        </select>
    </div>
</div>
<div class="col-md-6">
    <div class="admin-form-group">
        <label class="admin-label" style="display:flex;align-items:center;gap:8px;">
            <input type="checkbox" name="is_featured" <?= $editDish&&$editDish['is_featured']?'checked':'' ?> style="accent-color:var(--gold);">
            Đánh dấu nổi bật
        </label>
    </div>
</div>
<div class="col-12">
    <div class="admin-form-group">
        <label class="admin-label">Ảnh món ăn</label>
        <input type="file" name="image" class="admin-input" accept="image/*">
        <?php if ($editDish&&$editDish['image']): ?>
        <div style="margin-top:8px;"><img src="<?= getDishImage($editDish['image']) ?>" style="height:60px;border-radius:6px;"></div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<div class="admin-modal-footer">
    <a href="dishes.php" class="admin-btn-sm">Hủy</a>
    <button type="submit" class="admin-btn"><i class="bi bi-save"></i> Lưu</button>
</div>
</form>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/admin/assets/js/admin.js"></script>
<script>
var SITE_URL='<?= SITE_URL ?>';
function openModal(){ document.getElementById('dishModal').classList.add('show'); }
</script>
</body></html>
