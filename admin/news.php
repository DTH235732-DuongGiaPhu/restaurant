<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = 'Quản lý Tin tức';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id      = (int)($_POST['id']??0);
        $title   = clean($_POST['title']??'');
        $excerpt = clean($_POST['excerpt']??'');
        $content = $_POST['content']??'';
        $imgName = $_POST['old_image']??null;
        if (isset($_FILES['image']) && $_FILES['image']['size']>0) {
            $r = uploadImage($_FILES['image'],'news');
            if (!isset($r['error'])) $imgName = $r['filename'];
        }
        if ($id) $pdo->prepare("UPDATE news SET title=?,excerpt=?,content=?,image=? WHERE id=?")->execute([$title,$excerpt,$content,$imgName,$id]);
        else $pdo->prepare("INSERT INTO news (title,excerpt,content,image) VALUES (?,?,?,?)")->execute([$title,$excerpt,$content,$imgName]);
        header('Location: news.php?msg=saved'); exit;
    }
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM news WHERE id=?")->execute([(int)$_POST['id']]);
        header('Location: news.php?msg=deleted'); exit;
    }
}
$editNews = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) { $s=$pdo->prepare("SELECT * FROM news WHERE id=?");$s->execute([(int)$_GET['edit']]);$editNews=$s->fetch(); }
$allNews = $pdo->query("SELECT * FROM news ORDER BY created_at DESC")->fetchAll();
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
    <div><h2 class="content-title">Tin tức</h2><p class="content-subtitle"><?= count($allNews) ?> bài viết</p></div>
    <a href="?edit=new" class="admin-btn"><i class="bi bi-plus-lg"></i> Thêm bài viết</a>
</div>
<?php if(isset($_GET['msg'])): ?><div class="admin-flash" style="background:rgba(82,183,136,0.1);border:1px solid rgba(82,183,136,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#52b788;"><i class="bi bi-check-circle-fill"></i> <?= $_GET['msg']==='saved'?'Đã lưu!':'Đã xóa!' ?></div><?php endif; ?>

<?php if($editNews !== null || isset($_GET['edit'])): ?>
<!-- Edit Form (fullpage) -->
<div class="admin-card">
<div class="admin-card-header"><h6><?= $editNews?'Sửa bài viết':'Thêm bài viết mới' ?></h6><a href="news.php" style="color:var(--text-muted);font-size:12px;">← Quay lại</a></div>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="<?= $editNews?$editNews['id']:0 ?>">
<input type="hidden" name="old_image" value="<?= $editNews?$editNews['image']:'' ?>">
<div class="admin-card-body">
<div class="row g-3">
<div class="col-12"><div class="admin-form-group"><label class="admin-label">Tiêu đề *</label><input type="text" name="title" class="admin-input" value="<?= $editNews?clean($editNews['title']):'' ?>" required></div></div>
<div class="col-12"><div class="admin-form-group"><label class="admin-label">Tóm tắt</label><textarea name="excerpt" class="admin-input" rows="2"><?= $editNews?clean($editNews['excerpt']):'' ?></textarea></div></div>
<div class="col-12"><div class="admin-form-group"><label class="admin-label">Nội dung (hỗ trợ HTML)</label><textarea name="content" class="admin-input" rows="10"><?= $editNews?htmlspecialchars($editNews['content']):'' ?></textarea></div></div>
<div class="col-md-6"><div class="admin-form-group"><label class="admin-label">Ảnh bìa</label><input type="file" name="image" class="admin-input" accept="image/*"><?php if($editNews&&$editNews['image']): ?><div style="margin-top:8px;"><img src="<?= getNewsImage($editNews['image']) ?>" style="height:60px;border-radius:6px;"></div><?php endif; ?></div></div>
<div class="col-12"><button type="submit" class="admin-btn"><i class="bi bi-save"></i> Lưu bài viết</button></div>
</div>
</div>
</form>
</div>
<?php else: ?>
<div class="admin-card"><div class="admin-card-body p-0"><div class="table-responsive">
<table class="admin-table">
<thead><tr><th>Tiêu đề</th><th>Lượt xem</th><th>Ngày đăng</th><th></th></tr></thead>
<tbody>
<?php foreach($allNews as $n): ?>
<tr>
    <td><div style="color:var(--cream);font-weight:500;max-width:400px;"><?= clean($n['title']) ?></div><div style="color:var(--text-muted);font-size:12px;"><?= truncate($n['excerpt'],80) ?></div></td>
    <td><span style="color:var(--text-muted);font-size:13px;"><i class="bi bi-eye"></i> <?= number_format($n['views']) ?></span></td>
    <td style="color:var(--text-muted);font-size:12px;"><?= date('d/m/Y',strtotime($n['created_at'])) ?></td>
    <td style="white-space:nowrap;">
        <a href="?edit=<?= $n['id'] ?>" class="admin-btn-sm"><i class="bi bi-pencil"></i></a>
        <form method="POST" style="display:inline;"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $n['id'] ?>"><button type="submit" class="admin-btn-danger" data-confirm="Xóa bài viết này?"><i class="bi bi-trash3"></i></button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php endif; ?>
</div></div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/admin/assets/js/admin.js"></script>
<script>var SITE_URL='<?= SITE_URL ?>';</script>
</body></html>
