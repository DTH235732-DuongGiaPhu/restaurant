<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Quản lý Đơn hàng';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id     = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowed = ['pending','confirmed','cooking','delivering','done','cancelled'];
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $id]);
        header('Location: orders.php?updated=1');
        exit;
    }
}

// View single order
$viewId = (int)($_GET['view'] ?? 0);
$viewOrder = null; $viewItems = [];
if ($viewId) {
    $stmt = $pdo->prepare("SELECT o.*, u.email FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?");
    $stmt->execute([$viewId]);
    $viewOrder = $stmt->fetch();
    if ($viewOrder) {
        $stmtI = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
        $stmtI->execute([$viewId]);
        $viewItems = $stmtI->fetchAll();
    }
}

// Pagination & filter
$page   = max(1,(int)($_GET['page']??1));
$limit  = 15; $offset = ($page-1)*$limit;
$filter = $_GET['status'] ?? 'all';

$where = $filter !== 'all' ? "WHERE o.status='$filter'" : '';
$total = $pdo->query("SELECT COUNT(*) FROM orders o $where")->fetchColumn();
$pages = ceil($total/$limit);

$orders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id=u.id $where ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();

$statusMap = ['pending'=>['Chờ xác nhận','status-pending'],'confirmed'=>['Đã xác nhận','status-confirmed'],'cooking'=>['Đang nấu','status-cooking'],'delivering'=>['Đang giao','status-delivering'],'done'=>['Hoàn thành','status-done'],'cancelled'=>['Đã hủy','status-cancelled']];
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
    <div><h2 class="content-title">Đơn hàng</h2><p class="content-subtitle">Quản lý và cập nhật trạng thái đơn</p></div>
</div>

<?php if (isset($_GET['updated'])): ?>
<div class="admin-flash" style="background:rgba(82,183,136,0.1);border:1px solid rgba(82,183,136,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#52b788;">
    <i class="bi bi-check-circle-fill"></i> Cập nhật trạng thái thành công!
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;">
    <?php foreach (['all'=>'Tất cả']+array_map(fn($v)=>$v[0],$statusMap) as $k=>$v):
        $cnt = $k==='all'?$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn():$pdo->query("SELECT COUNT(*) FROM orders WHERE status='$k'")->fetchColumn();
    ?>
    <a href="?status=<?= $k ?>" class="cat-filter-btn <?= $filter===$k?'active':'' ?>" style="background:var(--bg-card);border:1px solid var(--border);border-radius:100px;padding:6px 16px;font-size:12px;color:var(--text-muted);transition:var(--transition);<?= $filter===$k?'border-color:var(--gold);color:var(--gold);':'' ?>">
        <?= $v ?> <span style="background:var(--bg-secondary);padding:1px 7px;border-radius:100px;margin-left:4px;"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<div class="admin-card">
<div class="admin-card-body p-0">
<div class="table-responsive">
<table class="admin-table">
<thead><tr><th>#</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th>T.Toán</th><th>Thời gian</th><th></th></tr></thead>
<tbody>
<?php foreach ($orders as $o): $st = $statusMap[$o['status']]??[$o['status'],'status-pending']; ?>
<tr>
    <td><span style="color:var(--gold);font-weight:600;">#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></span></td>
    <td>
        <div style="font-weight:500;"><?= clean($o['customer_name']) ?></div>
        <div style="color:var(--text-muted);font-size:11px;"><?= clean($o['customer_phone']) ?></div>
    </td>
    <td style="color:var(--gold);font-weight:700;"><?= formatPrice($o['total']) ?></td>
    <td>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
            <select name="status" onchange="this.form.submit()" style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:5px 10px;cursor:pointer;outline:none;">
                <?php foreach ($statusMap as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $o['status']===$k?'selected':'' ?>><?= $v[0] ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="update_status" value="1">
        </form>
    </td>
    <td style="font-size:12px;"><?= $o['payment_method']==='cash'?'Tiền mặt':'Chuyển khoản' ?></td>
    <td style="color:var(--text-muted);font-size:12px;"><?= date('d/m H:i',strtotime($o['created_at'])) ?></td>
    <td><a href="?view=<?= $o['id'] ?>" class="admin-btn-sm"><i class="bi bi-eye"></i></a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="admin-pagination mt-3">
<?php for ($i=1;$i<=$pages;$i++): ?>
<a href="?status=<?= $filter ?>&page=<?= $i ?>" class="page-link-custom <?= $i===$page?'active':'' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>
<?php endif; ?>

</div></div>

<!-- View Order Modal -->
<?php if ($viewOrder): ?>
<div style="position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:999;display:flex;align-items:center;justify-content:center;padding:20px;">
<div class="admin-modal-content" style="width:100%;max-width:600px;">
<div class="admin-modal-header">
    <h6>Chi tiết đơn #<?= str_pad($viewOrder['id'],5,'0',STR_PAD_LEFT) ?></h6>
    <a href="orders.php" class="admin-modal-close"><i class="bi bi-x-lg"></i></a>
</div>
<div class="admin-modal-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
        <div>
            <div style="color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Khách hàng</div>
            <div style="color:var(--cream);font-weight:500;"><?= clean($viewOrder['customer_name']) ?></div>
            <div style="color:var(--text-muted);font-size:13px;"><?= clean($viewOrder['customer_phone']) ?></div>
            <?php if ($viewOrder['email']): ?><div style="color:var(--text-muted);font-size:12px;"><?= clean($viewOrder['email']) ?></div><?php endif; ?>
        </div>
        <div>
            <div style="color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Thông tin đơn</div>
            <div style="color:var(--text-muted);font-size:13px;">Ngày: <?= date('d/m/Y H:i',strtotime($viewOrder['created_at'])) ?></div>
            <div style="color:var(--text-muted);font-size:13px;">Thanh toán: <?= $viewOrder['payment_method']==='cash'?'Tiền mặt':'Chuyển khoản' ?></div>
        </div>
    </div>
    <div style="margin-bottom:16px;">
        <div style="color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Địa chỉ</div>
        <div style="color:var(--cream);font-size:13px;"><?= clean($viewOrder['customer_address']) ?></div>
    </div>
    <?php if ($viewOrder['note']): ?>
    <div style="margin-bottom:16px;padding:12px;background:rgba(201,169,110,0.06);border-radius:6px;font-size:13px;color:var(--text-muted);">
        <i class="bi bi-chat-text" style="color:var(--gold);margin-right:6px;"></i><?= clean($viewOrder['note']) ?>
    </div>
    <?php endif; ?>
    <div style="border-top:1px solid var(--border);padding-top:16px;">
        <div style="color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px;">Món đã đặt</div>
        <?php foreach ($viewItems as $item): ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.04);font-size:13px;">
            <span style="color:var(--text);"><?= clean($item['dish_name']) ?> × <?= $item['quantity'] ?></span>
            <span style="color:var(--gold);font-weight:600;"><?= formatPrice($item['subtotal']) ?></span>
        </div>
        <?php endforeach; ?>
        <div style="display:flex;justify-content:space-between;padding-top:14px;font-size:1.1rem;">
            <span style="color:var(--cream);font-weight:700;">Tổng cộng</span>
            <span style="color:var(--gold);font-family:var(--font-display);font-size:1.4rem;font-weight:700;"><?= formatPrice($viewOrder['total']) ?></span>
        </div>
    </div>
</div>
</div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/admin/assets/js/admin.js"></script>
<script>var SITE_URL='<?= SITE_URL ?>';</script>
</body></html>
