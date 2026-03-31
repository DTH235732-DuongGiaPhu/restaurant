<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Quản lý Đặt bàn';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id     = (int)$_POST['res_id'];
    $status = $_POST['status'];
    $allowed = ['pending','confirmed','seated','done','cancelled'];
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE reservations SET status=? WHERE id=?")->execute([$status, $id]);
        header('Location: reservations.php?updated=1'); exit;
    }
}

// View single
$viewId = (int)($_GET['view'] ?? 0);
$viewRes = null; $viewItems = [];
if ($viewId) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.email as user_email, tp.name as pos_name, tp.icon as pos_icon,
               dt.name as decor_name, dt.color_hint as decor_color, dt.icon as decor_icon
        FROM reservations r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN table_positions tp ON r.position_id = tp.id
        LEFT JOIN decor_types dt ON r.decor_id = dt.id
        WHERE r.id = ?
    ");
    $stmt->execute([$viewId]);
    $viewRes = $stmt->fetch();
    if ($viewRes && $viewRes['has_preorder']) {
        $stmtI = $pdo->prepare("SELECT * FROM reservation_items WHERE reservation_id=?");
        $stmtI->execute([$viewId]);
        $viewItems = $stmtI->fetchAll();
    }
}

// Pagination & filter
$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 20; $offset = ($page-1)*$limit;
$filter = $_GET['status'] ?? 'all';
$search = clean($_GET['q'] ?? '');

$where = "WHERE 1=1";
if ($filter !== 'all') $where .= " AND r.status='" . $filter . "'";
if ($search) $where .= " AND (r.customer_name LIKE '%" . addslashes($search) . "%' OR r.customer_phone LIKE '%" . addslashes($search) . "%')";

$total = $pdo->query("SELECT COUNT(*) FROM reservations r $where")->fetchColumn();
$pages = max(1, ceil($total/$limit));

$reservations = $pdo->query("
    SELECT r.*, tp.name as pos_name, tp.icon as pos_icon, dt.name as decor_name, dt.color_hint as decor_color
    FROM reservations r
    LEFT JOIN table_positions tp ON r.position_id = tp.id
    LEFT JOIN decor_types dt ON r.decor_id = dt.id
    $where
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
")->fetchAll();

$statusMap = [
    'pending'   => ['Chờ xác nhận', 'warning'],
    'confirmed' => ['Đã xác nhận',  'info'],
    'seated'    => ['Đang ngồi',    'success'],
    'done'      => ['Hoàn thành',   'green'],
    'cancelled' => ['Đã huỷ',       'red'],
];

// Stats
$stats = [
    'today'     => $pdo->query("SELECT COUNT(*) FROM reservations WHERE DATE(reservation_date)=CURDATE()")->fetchColumn(),
    'pending'   => $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn(),
    'confirmed' => $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='confirmed'")->fetchColumn(),
    'preorder'  => $pdo->query("SELECT COUNT(*) FROM reservations WHERE has_preorder=1 AND DATE(reservation_date)>=CURDATE()")->fetchColumn(),
];
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
    <div>
        <h2 class="content-title">Đặt bàn</h2>
        <p class="content-subtitle">Quản lý và xác nhận lịch đặt bàn của khách hàng</p>
    </div>
</div>

<?php if (isset($_GET['updated'])): ?>
<div class="admin-flash" style="background:rgba(82,183,136,.1);border:1px solid rgba(82,183,136,.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#52b788">
    <i class="bi bi-check-circle-fill"></i> Cập nhật trạng thái thành công!
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Hôm nay', $stats['today'], 'calendar-check', '#c9a96e'],
        ['Chờ xác nhận', $stats['pending'], 'hourglass-split', '#e0a452'],
        ['Đã xác nhận', $stats['confirmed'], 'check-circle', '#64a2e0'],
        ['Có đặt món trước', $stats['preorder'], 'bag-check', '#52b788'],
    ];
    foreach ($cards as [$lbl,$val,$ico,$clr]):
    ?>
    <div class="col-6 col-lg-3">
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:18px 16px;display:flex;align-items:center;gap:14px">
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(<?= implode(',',sscanf($clr,'#%02x%02x%02x')) ?>,.12);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:<?= $clr ?>">
                <i class="bi bi-<?= $ico ?>"></i>
            </div>
            <div>
                <div style="font-size:1.5rem;font-weight:700;color:var(--cream)"><?= $val ?></div>
                <div style="font-size:12px;color:var(--text-muted)"><?= $lbl ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter & Search -->
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px;align-items:center">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex:1;min-width:200px">
        <input type="hidden" name="status" value="<?= clean($filter) ?>">
        <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Tìm tên / SĐT..." style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:8px;padding:9px 14px;color:var(--cream);font-size:13px;flex:1">
        <button type="submit" style="background:var(--gold);border:none;border-radius:8px;padding:9px 16px;color:#000;font-size:13px;font-weight:700;cursor:pointer"><i class="bi bi-search"></i></button>
    </form>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php foreach (['all'=>'Tất cả'] + array_map(fn($v)=>$v[0],$statusMap) as $k=>$v):
            $cnt = $k==='all' ? $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn()
                              : $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='$k'")->fetchColumn();
        ?>
        <a href="?status=<?= $k ?>" style="background:var(--bg-card);border:1px solid var(--border);border-radius:100px;padding:6px 14px;font-size:12px;color:var(--text-muted);transition:.3s;<?= $filter===$k?'border-color:var(--gold);color:var(--gold);':'' ?>">
            <?= $v ?> <span style="background:var(--bg-secondary);padding:1px 6px;border-radius:100px;margin-left:4px"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Table -->
<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>Mã / Ngày tạo</th>
    <th>Khách hàng</th>
    <th>Ngày & Giờ đặt</th>
    <th>Vị trí</th>
    <th>Loại</th>
    <th>Tổng tiền</th>
    <th>Trạng thái</th>
    <th>Thao tác</th>
</tr>
</thead>
<tbody>
<?php if (empty($reservations)): ?>
<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)"><i class="bi bi-calendar-x" style="font-size:1.5rem;display:block;margin-bottom:8px"></i> Không có đặt bàn nào</td></tr>
<?php endif; ?>
<?php foreach ($reservations as $r):
    [$label,$cls] = $statusMap[$r['status']] ?? ['—','secondary'];
    $clrMap = ['warning'=>'#e0a452','info'=>'#64a2e0','success'=>'#52b788','green'=>'#52b788','red'=>'#e05252','secondary'=>'#888'];
    $bgMap  = ['warning'=>'rgba(224,164,82,.1)','info'=>'rgba(100,162,224,.1)','success'=>'rgba(82,183,136,.1)','green'=>'rgba(82,183,136,.1)','red'=>'rgba(224,82,82,.1)','secondary'=>'rgba(136,136,128,.1)'];
?>
<tr>
    <td>
        <div style="color:var(--gold);font-weight:700;font-size:13px">#<?= str_pad($r['id'],5,'0',STR_PAD_LEFT) ?></div>
        <div style="color:var(--text-muted);font-size:11px"><?= date('d/m/Y H:i',strtotime($r['created_at'])) ?></div>
    </td>
    <td>
        <div style="color:var(--cream);font-weight:500;font-size:13px"><?= clean($r['customer_name']) ?></div>
        <div style="color:var(--text-muted);font-size:12px"><i class="bi bi-telephone"></i> <?= clean($r['customer_phone']) ?></div>
    </td>
    <td>
        <div style="color:var(--cream);font-size:13px;font-weight:600"><?= date('d/m/Y',strtotime($r['reservation_date'])) ?></div>
        <div style="color:var(--gold);font-size:12px"><i class="bi bi-clock"></i> <?= substr($r['reservation_time'],0,5) ?></div>
        <div style="color:var(--text-muted);font-size:11px"><i class="bi bi-people"></i> <?= $r['num_guests'] ?> khách</div>
    </td>
    <td>
        <div style="font-size:13px;color:var(--cream);display:flex;align-items:center;gap:6px">
            <i class="bi bi-<?= clean($r['pos_icon']??'geo-alt') ?>" style="color:var(--gold)"></i>
            <?= clean($r['pos_name'] ?? '—') ?>
        </div>
    </td>
    <td>
        <?php if ($r['has_preorder']): ?>
        <span style="background:rgba(201,169,110,.1);border:1px solid rgba(201,169,110,.2);border-radius:100px;padding:3px 10px;font-size:11px;color:var(--gold)">
            <i class="bi bi-bag-check"></i> Đặt món
        </span>
        <?php if ($r['decor_name']): ?>
        <div style="margin-top:4px;font-size:11px;color:<?= clean($r['decor_color']) ?>"><i class="bi bi-stars"></i> <?= clean($r['decor_name']) ?></div>
        <?php endif; ?>
        <?php else: ?>
        <span style="background:rgba(82,183,136,.08);border:1px solid rgba(82,183,136,.2);border-radius:100px;padding:3px 10px;font-size:11px;color:var(--green)">
            <i class="bi bi-calendar2-check"></i> Chỉ bàn
        </span>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($r['total'] > 0): ?>
        <div style="color:var(--gold);font-weight:700;font-size:13px"><?= formatPrice($r['total']) ?></div>
        <?php if ($r['decor_fee'] == 0 && $r['decor_name']): ?>
        <div style="font-size:11px;color:var(--green)">Decor miễn phí</div>
        <?php endif; ?>
        <?php else: ?>
        <span style="color:var(--text-muted);font-size:12px">—</span>
        <?php endif; ?>
    </td>
    <td>
        <span style="font-size:12px;font-weight:600;padding:4px 10px;border-radius:100px;background:<?= $bgMap[$cls] ?>;color:<?= $clrMap[$cls] ?>">
            <?= $label ?>
        </span>
    </td>
    <td>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="?view=<?= $r['id'] ?>" style="width:32px;height:32px;border-radius:8px;background:rgba(201,169,110,.1);border:1px solid rgba(201,169,110,.2);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:.85rem" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
            <?php if ($r['status'] !== 'done' && $r['status'] !== 'cancelled'): ?>
            <button onclick="openStatusModal(<?= $r['id'] ?>,'<?= $r['status'] ?>')" style="width:32px;height:32px;border-radius:8px;background:rgba(82,183,136,.1);border:1px solid rgba(82,183,136,.2);display:flex;align-items:center;justify-content:center;color:var(--green);font-size:.85rem;cursor:pointer" title="Đổi trạng thái"><i class="bi bi-arrow-repeat"></i></button>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div style="display:flex;justify-content:center;gap:6px;margin-top:20px;flex-wrap:wrap">
    <?php for ($i=1;$i<=$pages;$i++): ?>
    <a href="?status=<?= $filter ?>&page=<?= $i ?>" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:<?= $i===$page?'var(--gold)':'var(--bg-card)' ?>;border:1px solid <?= $i===$page?'var(--gold)':'var(--border)' ?>;color:<?= $i===$page?'#000':'var(--text-muted)' ?>;font-size:13px;font-weight:<?= $i===$page?'700':'400' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

</div><!-- /.admin-content -->
</div><!-- /.admin-main -->

<!-- ════ VIEW DETAIL MODAL ════ -->
<?php if ($viewRes): ?>
<div id="viewModal" style="position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9000;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(6px)">
    <div style="background:#141414;border:1px solid var(--gold);border-radius:12px;width:100%;max-width:640px;max-height:90vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid var(--border)">
            <div>
                <h4 style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;color:var(--cream);margin:0">
                    Đặt bàn #<?= str_pad($viewRes['id'],5,'0',STR_PAD_LEFT) ?>
                </h4>
                <p style="font-size:12px;color:var(--text-muted);margin:2px 0 0">
                    <?= $viewRes['has_preorder'] ? '🍽️ Đặt bàn + Đặt món trước' : '🪑 Chỉ đặt bàn' ?>
                </p>
            </div>
            <a href="reservations.php" style="color:var(--text-muted);font-size:1.5rem;line-height:1">×</a>
        </div>
        <div style="padding:24px">
            <!-- Thông tin khách -->
            <div style="margin-bottom:20px">
                <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">Khách hàng</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div style="font-size:13px"><span style="color:var(--text-muted)">Họ tên:</span> <span style="color:var(--cream)"><?= clean($viewRes['customer_name']) ?></span></div>
                    <div style="font-size:13px"><span style="color:var(--text-muted)">SĐT:</span> <span style="color:var(--cream)"><?= clean($viewRes['customer_phone']) ?></span></div>
                    <?php if ($viewRes['customer_email']): ?>
                    <div style="font-size:13px"><span style="color:var(--text-muted)">Email:</span> <span style="color:var(--cream)"><?= clean($viewRes['customer_email']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($viewRes['user_email']): ?>
                    <div style="font-size:13px"><span style="color:var(--text-muted)">Tài khoản:</span> <span style="color:var(--cream)"><?= clean($viewRes['user_email']) ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Thông tin bàn -->
            <div style="margin-bottom:20px">
                <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">Thông tin bàn</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div style="font-size:13px"><span style="color:var(--text-muted)">Ngày:</span> <span style="color:var(--cream)"><?= date('d/m/Y',strtotime($viewRes['reservation_date'])) ?></span></div>
                    <div style="font-size:13px"><span style="color:var(--text-muted)">Giờ:</span> <span style="color:var(--gold);font-weight:600"><?= substr($viewRes['reservation_time'],0,5) ?></span></div>
                    <div style="font-size:13px"><span style="color:var(--text-muted)">Số khách:</span> <span style="color:var(--cream)"><?= $viewRes['num_guests'] ?> người</span></div>
                    <div style="font-size:13px"><span style="color:var(--text-muted)">Vị trí:</span> <span style="color:var(--cream)"><?= clean($viewRes['pos_name']??'—') ?></span></div>
                    <?php if ($viewRes['decor_name']): ?>
                    <div style="font-size:13px"><span style="color:var(--text-muted)">Decor:</span> <span style="color:<?= clean($viewRes['decor_color']) ?>"><?= clean($viewRes['decor_name']) ?></span></div>
                    <div style="font-size:13px"><span style="color:var(--text-muted)">Phí decor:</span> <span style="color:<?= $viewRes['decor_fee']>0?'var(--gold)':'var(--green)' ?>"><?= $viewRes['decor_fee']>0?formatPrice($viewRes['decor_fee']):'Miễn phí' ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Món đặt trước -->
            <?php if (!empty($viewItems)): ?>
            <div style="margin-bottom:20px">
                <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">Món đặt trước</div>
                <?php foreach ($viewItems as $it): ?>
                <div style="display:flex;justify-content:space-between;font-size:13px;padding:5px 0;border-bottom:1px solid var(--border)">
                    <span style="color:var(--cream)"><?= clean($it['dish_name']) ?> × <?= $it['quantity'] ?></span>
                    <span style="color:var(--gold)"><?= formatPrice($it['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>
                <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;padding:10px 0;color:var(--cream)">
                    <span>Tiền món</span><span style="color:var(--gold)"><?= formatPrice($viewRes['food_total']) ?></span>
                </div>
            </div>
            <?php endif; ?>
            <!-- Tổng -->
            <?php if ($viewRes['total'] > 0): ?>
            <div style="background:rgba(201,169,110,.06);border:1px solid rgba(201,169,110,.2);border-radius:8px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <span style="color:var(--cream);font-weight:700">Tổng hóa đơn</span>
                <span style="color:var(--gold);font-size:1.3rem;font-weight:700"><?= formatPrice($viewRes['total']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($viewRes['note']): ?>
            <div style="background:var(--bg-secondary);border-radius:8px;padding:12px 14px;font-size:13px;color:var(--text-muted);margin-bottom:16px">
                <i class="bi bi-chat-left-text" style="color:var(--gold);margin-right:6px"></i><?= clean($viewRes['note']) ?>
            </div>
            <?php endif; ?>
            <!-- Update status -->
            <?php if ($viewRes['status'] !== 'done' && $viewRes['status'] !== 'cancelled'): ?>
            <form method="POST" style="display:flex;gap:10px;align-items:center">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="res_id" value="<?= $viewRes['id'] ?>">
                <select name="status" style="flex:1;background:var(--bg-secondary);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--cream);font-size:13px">
                    <?php foreach ($statusMap as $k=>[$lbl,$c]): ?>
                    <option value="<?= $k ?>" <?= $k===$viewRes['status']?'selected':'' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" style="background:var(--gold);border:none;border-radius:8px;padding:9px 20px;color:#000;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap">Cập nhật</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ════ STATUS MODAL ════ -->
<div id="statusModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:8000;align-items:center;justify-content:center;backdrop-filter:blur(4px)">
    <div style="background:#141414;border:1px solid var(--gold);border-radius:12px;padding:28px;width:90%;max-width:400px">
        <h5 style="font-family:'Cormorant Garamond',serif;color:var(--cream);font-size:1.3rem;margin:0 0 20px">Cập nhật trạng thái</h5>
        <form method="POST">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="res_id" id="modalResId">
            <select name="status" id="modalStatus" style="width:100%;background:var(--bg-secondary);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--cream);font-size:14px;margin-bottom:16px">
                <?php foreach ($statusMap as $k=>[$lbl,$c]): ?>
                <option value="<?= $k ?>"><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex;gap:10px">
                <button type="button" onclick="closeStatusModal()" style="flex:1;background:var(--bg-secondary);border:1px solid var(--border);border-radius:8px;padding:11px;color:var(--text-muted);font-size:13px;cursor:pointer">Huỷ</button>
                <button type="submit" style="flex:1;background:var(--gold);border:none;border-radius:8px;padding:11px;color:#000;font-size:13px;font-weight:700;cursor:pointer">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/admin/assets/js/admin.js"></script>
<script>
function openStatusModal(id,status){
    document.getElementById('modalResId').value=id;
    document.getElementById('modalStatus').value=status;
    document.getElementById('statusModal').style.display='flex';
}
function closeStatusModal(){document.getElementById('statusModal').style.display='none';}
document.getElementById('statusModal').addEventListener('click',function(e){if(e.target===this)closeStatusModal();});
</script>
</body></html>
