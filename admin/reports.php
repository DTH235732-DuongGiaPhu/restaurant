<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// ── BỘ LỌC ───────────────────────────────────────────────────
$dateFrom = $_GET['from'] ?? date('Y-m-01');          // Đầu tháng
$dateTo   = $_GET['to']   ?? date('Y-m-d');           // Hôm nay
$groupBy  = in_array($_GET['group'] ?? '', ['day','month']) ? $_GET['group'] : 'day';

// ── XUẤT CSV ─────────────────────────────────────────────────
if (($_GET['export'] ?? '') === 'csv') {
    $rows = $pdo->prepare("
        SELECT o.id, o.created_at, o.customer_name, o.customer_phone,
               o.customer_address, o.total, o.discount, o.coupon_code,
               o.payment_method, o.status,
               GROUP_CONCAT(CONCAT(oi.dish_name,' x',oi.quantity) SEPARATOR ' | ') AS items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY o.id ORDER BY o.created_at DESC
    ");
    $rows->execute([$dateFrom, $dateTo]);
    $data = $rows->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bao-cao-' . $dateFrom . '_' . $dateTo . '.csv"');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM cho Excel
    fputcsv($out, ['Mã ĐH','Ngày','Khách hàng','SĐT','Địa chỉ','Tổng tiền','Giảm giá','Mã voucher','Thanh toán','Trạng thái','Món đặt']);
    foreach ($data as $r) {
        $statusMap = ['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','cooking'=>'Đang nấu','delivering'=>'Đang giao','done'=>'Hoàn thành','cancelled'=>'Đã huỷ'];
        $payMap    = ['cash'=>'Tiền mặt','transfer'=>'Chuyển khoản'];
        fputcsv($out, [
            '#'.str_pad($r['id'],5,'0',STR_PAD_LEFT),
            date('d/m/Y H:i', strtotime($r['created_at'])),
            $r['customer_name'], $r['customer_phone'], $r['customer_address'],
            number_format($r['total'], 0, ',', '.'),
            $r['discount'] > 0 ? number_format($r['discount'], 0, ',', '.') : '',
            $r['coupon_code'] ?? '',
            $payMap[$r['payment_method']] ?? $r['payment_method'],
            $statusMap[$r['status']] ?? $r['status'],
            $r['items'],
        ]);
    }
    fclose($out);
    exit;
}

$pageTitle = 'Báo cáo doanh thu';

// ── TRUY VẤN THỐNG KÊ ────────────────────────────────────────

// Tổng quan kỳ
$summary = $pdo->prepare("
    SELECT
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status='done' THEN 1 END) as done_orders,
        COUNT(CASE WHEN status='cancelled' THEN 1 END) as cancelled_orders,
        COALESCE(SUM(CASE WHEN status='done' THEN total END),0) as revenue,
        COALESCE(SUM(CASE WHEN status='done' THEN discount END),0) as total_discount,
        COALESCE(AVG(CASE WHEN status='done' THEN total END),0) as avg_order
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$summary->execute([$dateFrom, $dateTo]);
$s = $summary->fetch();

// Biểu đồ theo ngày/tháng
$groupFmt = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';
$labelFmt = $groupBy === 'month' ? '%m/%Y' : '%d/%m';
$chartRows = $pdo->prepare("
    SELECT
        DATE_FORMAT(created_at, '$groupFmt') as period,
        DATE_FORMAT(created_at, '$labelFmt') as label,
        COUNT(*) as orders,
        COALESCE(SUM(CASE WHEN status='done' THEN total END),0) as revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY period, label ORDER BY period
");
$chartRows->execute([$dateFrom, $dateTo]);
$chartData = $chartRows->fetchAll();

$chartLabels  = array_column($chartData, 'label');
$chartRevenue = array_column($chartData, 'revenue');
$chartOrders  = array_column($chartData, 'orders');

// Top món ăn bán chạy
$topDishes = $pdo->prepare("
    SELECT oi.dish_name, SUM(oi.quantity) as qty, SUM(oi.subtotal) as total
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status = 'done'
    GROUP BY oi.dish_name ORDER BY qty DESC LIMIT 10
");
$topDishes->execute([$dateFrom, $dateTo]);
$topDishes = $topDishes->fetchAll();

// Phân bổ trạng thái
$statusData = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status");
$statusData->execute([$dateFrom, $dateTo]);
$statusMap = ['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','cooking'=>'Đang nấu','delivering'=>'Đang giao','done'=>'Hoàn thành','cancelled'=>'Đã huỷ'];
$statusColors = ['pending'=>'#c9a96e','confirmed'=>'#64a0ff','cooking'=>'#f4a261','delivering'=>'#52b788','done'=>'#2d9e5f','cancelled'=>'#e05252'];
$statusRows = $statusData->fetchAll(PDO::FETCH_KEY_PAIR);

// Phương thức thanh toán
$payData = $pdo->prepare("SELECT payment_method, COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status='done' GROUP BY payment_method");
$payData->execute([$dateFrom, $dateTo]);
$payRows = $payData->fetchAll();

// Doanh thu so sánh kỳ trước
$days = (strtotime($dateTo) - strtotime($dateFrom)) / 86400 + 1;
$prevFrom = date('Y-m-d', strtotime($dateFrom) - $days * 86400);
$prevTo   = date('Y-m-d', strtotime($dateTo)   - $days * 86400);
$prevRev  = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status='done'");
$prevRev->execute([$prevFrom, $prevTo]);
$prevRevenue = (float)$prevRev->fetchColumn();
$growthPct = $prevRevenue > 0 ? round(($s['revenue'] - $prevRevenue) / $prevRevenue * 100, 1) : ($s['revenue'] > 0 ? 100 : 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | Admin Saigon Bistro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/admin/assets/css/admin.css" rel="stylesheet">
    <style>
    .filter-bar { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;margin-bottom:24px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end; }
    .filter-group { display:flex;flex-direction:column;gap:5px; }
    .filter-group label { font-size:10px;color:var(--text-muted);font-weight:700;letter-spacing:0.1em;text-transform:uppercase; }
    .filter-input { background:var(--bg-secondary);border:1px solid var(--border);color:var(--cream);padding:8px 12px;border-radius:8px;font-size:13px;font-family:var(--font-body); }
    .filter-input:focus { outline:none;border-color:var(--gold); }
    .kpi-card { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px; }
    .kpi-label { font-size:11px;color:var(--text-muted);font-weight:700;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:6px; }
    .kpi-val { font-size:1.7rem;font-weight:700;color:var(--cream);font-family:var(--font-display);line-height:1.1; }
    .kpi-sub { font-size:12px;margin-top:4px; }
    .chart-card { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px; }
    .chart-title { font-size:13px;font-weight:600;color:var(--cream);margin-bottom:16px;display:flex;align-items:center;gap:8px; }
    .dish-row { display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px; }
    .dish-row:last-child { border-bottom:none; }
    .dish-bar { height:4px;border-radius:2px;background:var(--gold);opacity:0.7; }

    /* Print styles */
    @media print {
        .admin-sidebar, .admin-topbar, .filter-bar, .no-print { display:none !important; }
        .admin-main { margin-left:0 !important; }
        .admin-content { padding:0 !important; }
        body { background:#fff !important; color:#000 !important; }
        .kpi-card, .chart-card, .table-card { border:1px solid #ddd !important;background:#fff !important;break-inside:avoid; }
        .kpi-val, .chart-title, .kpi-label { color:#000 !important; }
        .kpi-sub, .dish-row { color:#555 !important; }
    }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="admin-content">
        <div class="content-header">
            <div>
                <h2 class="content-title">Báo cáo doanh thu</h2>
                <p class="content-subtitle">Phân tích chi tiết theo kỳ</p>
            </div>
            <div style="display:flex;gap:8px;" class="no-print">
                <a href="?from=<?= $dateFrom ?>&to=<?= $dateTo ?>&group=<?= $groupBy ?>&export=csv"
                    class="btn-admin-gold" style="font-size:12px;padding:9px 16px;">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Xuất Excel/CSV
                </a>
                <button onclick="window.print()" class="btn-admin-outline" style="font-size:12px;padding:9px 16px;">
                    <i class="bi bi-printer"></i> In / PDF
                </button>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="filter-bar no-print">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;width:100%;">
                <div class="filter-group">
                    <label>Từ ngày</label>
                    <input type="date" name="from" class="filter-input" value="<?= $dateFrom ?>">
                </div>
                <div class="filter-group">
                    <label>Đến ngày</label>
                    <input type="date" name="to" class="filter-input" value="<?= $dateTo ?>">
                </div>
                <div class="filter-group">
                    <label>Nhóm theo</label>
                    <select name="group" class="filter-input">
                        <option value="day"   <?= $groupBy==='day'?'selected':'' ?>>Theo ngày</option>
                        <option value="month" <?= $groupBy==='month'?'selected':'' ?>>Theo tháng</option>
                    </select>
                </div>
                <div style="display:flex;gap:6px;">
                    <button type="submit" class="btn-admin-gold" style="font-size:12px;padding:9px 16px;">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                    <button type="button" onclick="setPreset('thismonth')" class="btn-admin-outline" style="font-size:12px;padding:9px 12px;">Tháng này</button>
                    <button type="button" onclick="setPreset('lastmonth')" class="btn-admin-outline" style="font-size:12px;padding:9px 12px;">Tháng trước</button>
                    <button type="button" onclick="setPreset('thisyear')" class="btn-admin-outline" style="font-size:12px;padding:9px 12px;">Năm này</button>
                </div>
            </form>
        </div>

        <!-- KPI -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-label"><i class="bi bi-currency-exchange" style="color:var(--gold)"></i> Doanh thu</div>
                    <div class="kpi-val" style="color:var(--gold);"><?= number_format($s['revenue']/1000000, 1, ',', '.') ?>tr đ</div>
                    <div class="kpi-sub" style="color:<?= $growthPct >= 0 ? 'var(--green)' : 'var(--red)' ?>">
                        <i class="bi bi-arrow-<?= $growthPct >= 0 ? 'up' : 'down' ?>"></i>
                        <?= abs($growthPct) ?>% so với kỳ trước
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-label"><i class="bi bi-bag-check" style="color:#64a0ff"></i> Đơn hàng</div>
                    <div class="kpi-val"><?= $s['total_orders'] ?></div>
                    <div class="kpi-sub" style="color:var(--text-muted);">
                        <?= $s['done_orders'] ?> hoàn thành · <?= $s['cancelled_orders'] ?> huỷ
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-label"><i class="bi bi-receipt" style="color:var(--green)"></i> Đơn TB</div>
                    <div class="kpi-val" style="font-size:1.4rem;"><?= number_format($s['avg_order']/1000, 0, ',', '.') ?>K</div>
                    <div class="kpi-sub" style="color:var(--text-muted);">Giá trị trung bình / đơn</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-label"><i class="bi bi-ticket-perforated" style="color:#f4a261"></i> Giảm giá</div>
                    <div class="kpi-val" style="font-size:1.3rem;color:#f4a261;"><?= number_format($s['total_discount']/1000, 0, ',', '.') ?>K</div>
                    <div class="kpi-sub" style="color:var(--text-muted);">Tổng chiết khấu từ voucher</div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ doanh thu -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="chart-card">
                    <div class="chart-title"><i class="bi bi-bar-chart" style="color:var(--gold)"></i> Doanh thu theo <?= $groupBy === 'month' ? 'tháng' : 'ngày' ?></div>
                    <canvas id="revenueChart" height="200"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card" style="height:100%">
                    <div class="chart-title"><i class="bi bi-pie-chart" style="color:#64a0ff"></i> Trạng thái đơn hàng</div>
                    <canvas id="statusChart" height="200"></canvas>
                    <div style="margin-top:12px;font-size:12px;">
                        <?php foreach ($statusRows as $st => $cnt): ?>
                        <div style="display:flex;justify-content:space-between;padding:3px 0;color:var(--text-muted);">
                            <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $statusColors[$st] ?? '#888' ?>;margin-right:6px;"></span><?= $statusMap[$st] ?? $st ?></span>
                            <strong style="color:var(--cream);"><?= $cnt ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top món + Thanh toán -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="chart-card">
                    <div class="chart-title"><i class="bi bi-trophy" style="color:var(--gold)"></i> Top 10 món bán chạy</div>
                    <?php if ($topDishes): ?>
                    <?php $maxQty = max(array_column($topDishes, 'qty')); ?>
                    <?php foreach ($topDishes as $i => $d): ?>
                    <div class="dish-row">
                        <span style="color:var(--text-muted);font-size:11px;min-width:18px;"><?= $i+1 ?>.</span>
                        <div style="flex:1;">
                            <div style="color:var(--cream);margin-bottom:4px;"><?= clean($d['dish_name']) ?></div>
                            <div class="dish-bar" style="width:<?= round($d['qty']/$maxQty*100) ?>%"></div>
                        </div>
                        <span style="color:var(--gold);font-weight:600;min-width:40px;text-align:right;"><?= $d['qty'] ?> món</span>
                        <span style="color:var(--text-muted);font-size:12px;min-width:80px;text-align:right;"><?= number_format($d['total']/1000, 0) ?>K</span>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p style="color:var(--text-muted);text-align:center;padding:30px;">Không có dữ liệu</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card">
                    <div class="chart-title"><i class="bi bi-credit-card" style="color:var(--green)"></i> Phương thức thanh toán</div>
                    <?php foreach ($payRows as $p): ?>
                    <div style="padding:12px 0;border-bottom:1px solid var(--border);">
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                            <span style="color:var(--cream);font-size:13px;">
                                <?= $p['payment_method'] === 'cash' ? '💵 Tiền mặt' : '📱 Chuyển khoản' ?>
                            </span>
                            <strong style="color:var(--gold);"><?= $p['cnt'] ?> đơn</strong>
                        </div>
                        <div style="color:var(--text-muted);font-size:12px;"><?= number_format($p['rev']/1000, 0) ?>K đ doanh thu</div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($payRows)): ?>
                    <p style="color:var(--text-muted);text-align:center;padding:30px;">Không có dữ liệu</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kỳ so sánh -->
        <div class="chart-card" style="margin-bottom:24px;font-size:12px;color:var(--text-muted);">
            <i class="bi bi-info-circle" style="color:var(--gold)"></i>
            So sánh với kỳ trước: <strong style="color:var(--cream);"><?= date('d/m/Y', strtotime($prevFrom)) ?> → <?= date('d/m/Y', strtotime($prevTo)) ?></strong>
            · Doanh thu kỳ trước: <strong style="color:var(--gold);"><?= number_format($prevRevenue/1000, 0) ?>K đ</strong>
        </div>
    </div>
</div>

<style>
.btn-admin-outline { background:transparent;border:1px solid var(--border);color:var(--text);border-radius:var(--radius);font-size:13px;font-weight:600;cursor:pointer;transition:var(--transition);font-family:var(--font-body); }
.btn-admin-outline:hover { border-color:var(--gold);color:var(--gold); }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
var labels  = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
var revenue = <?= json_encode(array_map('floatval', $chartRevenue)) ?>;
var orders  = <?= json_encode(array_map('intval',   $chartOrders)) ?>;

// Revenue chart
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Doanh thu (đ)',
                data: revenue,
                backgroundColor: 'rgba(201,169,110,0.25)',
                borderColor: '#c9a96e',
                borderWidth: 1.5,
                borderRadius: 4,
                yAxisID: 'y',
            },
            {
                label: 'Đơn hàng',
                data: orders,
                type: 'line',
                borderColor: '#52b788',
                backgroundColor: 'rgba(82,183,136,0.1)',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0.4,
                yAxisID: 'y1',
                fill: true,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#777', font: { size: 11 } } } },
        scales: {
            x: { ticks: { color: '#666', font: { size: 11 } }, grid: { color: '#1a1a1a' } },
            y: {
                ticks: { color: '#666', font: { size: 11 }, callback: v => (v/1000000).toFixed(1) + 'M' },
                grid: { color: '#1a1a1a' }, position: 'left',
            },
            y1: {
                ticks: { color: '#52b788', font: { size: 11 } },
                grid: { display: false }, position: 'right',
            }
        }
    }
});

// Status chart
var statusLabels = <?= json_encode(array_map(fn($k) => $statusMap[$k] ?? $k, array_keys($statusRows)), JSON_UNESCAPED_UNICODE) ?>;
var statusCounts = <?= json_encode(array_values($statusRows)) ?>;
var statusClrs   = <?= json_encode(array_map(fn($k) => $statusColors[$k] ?? '#888', array_keys($statusRows))) ?>;
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: { labels: statusLabels, datasets: [{ data: statusCounts, backgroundColor: statusClrs, borderWidth: 0 }] },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        cutout: '65%',
    }
});

// Preset date ranges
function setPreset(p) {
    var now = new Date();
    var from, to = now.toISOString().slice(0,10);
    if (p === 'thismonth') {
        from = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-01';
    } else if (p === 'lastmonth') {
        var d = new Date(now.getFullYear(), now.getMonth(), 0);
        to   = d.toISOString().slice(0,10);
        from = new Date(now.getFullYear(), now.getMonth()-1, 1).toISOString().slice(0,10);
    } else if (p === 'thisyear') {
        from = now.getFullYear() + '-01-01';
    }
    document.querySelector('[name=from]').value  = from;
    document.querySelector('[name=to]').value    = to;
    document.querySelector('[name=group]').value = p === 'thisyear' ? 'month' : 'day';
    document.querySelector('form').submit();
}
</script>
</body>
</html>
