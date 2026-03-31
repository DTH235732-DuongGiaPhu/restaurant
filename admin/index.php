<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// Dashboard stats
$stats = [
    'orders_today'   => $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
    'orders_total'   => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue_today'  => $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status='done'")->fetchColumn(),
    'revenue_month'  => $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) AND status='done'")->fetchColumn(),
    'total_users'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'total_dishes'   => $pdo->query("SELECT COUNT(*) FROM dishes WHERE status='active'")->fetchColumn(),
    'pending_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),
];

// Recent orders
$recent = $pdo->query("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();

// Revenue chart data (last 7 days)
$chartLabels = []; $chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($date));
    $rev = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=? AND status='done'");
    $rev->execute([$date]);
    $chartData[] = (int)$rev->fetchColumn();
}

$pageTitle = 'Dashboard';
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
</head>
<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="admin-content">
        <div class="content-header">
            <div>
                <h2 class="content-title">Dashboard</h2>
                <p class="content-subtitle">Tổng quan hoạt động nhà hàng</p>
            </div>
            <div style="color:var(--text-muted);font-size:13px;"><i class="bi bi-calendar3"></i> <?= date('d/m/Y') ?></div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(201,169,110,0.1);color:var(--gold)"><i class="bi bi-bag-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-val"><?= $stats['orders_today'] ?></div>
                        <div class="stat-label">Đơn hôm nay</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(82,183,136,0.1);color:#52b788"><i class="bi bi-currency-exchange"></i></div>
                    <div class="stat-info">
                        <div class="stat-val"><?= number_format($stats['revenue_today']/1000) ?>K</div>
                        <div class="stat-label">Doanh thu hôm nay</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(100,160,255,0.1);color:#64a0ff"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-info">
                        <div class="stat-val"><?= $stats['total_users'] ?></div>
                        <div class="stat-label">Thành viên</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(255,100,100,0.1);color:#ff6464"><i class="bi bi-clock-history"></i></div>
                    <div class="stat-info">
                        <div class="stat-val"><?= $stats['pending_orders'] ?></div>
                        <div class="stat-label">Chờ xử lý</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Revenue Chart -->
            <div class="col-lg-7">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6>Doanh thu 7 ngày gần nhất</h6>
                    </div>
                    <div class="admin-card-body">
                        <canvas id="revenueChart" height="240"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="col-lg-5">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6>Thống kê nhanh</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="quick-stat-row">
                            <span>Tổng đơn hàng</span>
                            <span class="qs-val"><?= number_format($stats['orders_total']) ?></span>
                        </div>
                        <div class="quick-stat-row">
                            <span>Doanh thu tháng này</span>
                            <span class="qs-val gold"><?= number_format($stats['revenue_month']/1000) ?>K đ</span>
                        </div>
                        <div class="quick-stat-row">
                            <span>Số món trong menu</span>
                            <span class="qs-val"><?= $stats['total_dishes'] ?></span>
                        </div>
                        <div class="quick-stat-row" style="border:none">
                            <span>Đơn đang chờ</span>
                            <span class="qs-val" style="color:#ff6464"><?= $stats['pending_orders'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="col-12">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6>Đơn hàng gần đây</h6>
                        <a href="<?= SITE_URL ?>/admin/orders.php" class="card-link">Xem tất cả →</a>
                    </div>
                    <div class="admin-card-body p-0">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr><th>#</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th>Thời gian</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent as $o):
                                        $statusMap = ['pending'=>['Chờ xác nhận','status-pending'],'confirmed'=>['Đã xác nhận','status-confirmed'],'cooking'=>['Đang nấu','status-cooking'],'delivering'=>['Đang giao','status-delivering'],'done'=>['Hoàn thành','status-done'],'cancelled'=>['Đã hủy','status-cancelled']];
                                        $st = $statusMap[$o['status']] ?? [$o['status'],'status-pending'];
                                    ?>
                                    <tr>
                                        <td><span style="color:var(--gold);font-weight:600;">#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></span></td>
                                        <td>
                                            <div style="font-weight:500;"><?= clean($o['customer_name']) ?></div>
                                            <div style="color:var(--text-muted);font-size:11px;"><?= clean($o['customer_phone']) ?></div>
                                        </td>
                                        <td style="color:var(--gold);font-weight:600;"><?= formatPrice($o['total']) ?></td>
                                        <td><span class="status-badge <?= $st[1] ?>"><?= $st[0] ?></span></td>
                                        <td style="color:var(--text-muted);font-size:12px;"><?= date('d/m H:i', strtotime($o['created_at'])) ?></td>
                                        <td>
                                            <a href="<?= SITE_URL ?>/admin/orders.php?view=<?= $o['id'] ?>" class="admin-btn-sm">Chi tiết</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= SITE_URL ?>/admin/assets/js/admin.js"></script>
<script>
var SITE_URL = '<?= SITE_URL ?>';
// Revenue Chart
var ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Doanh thu (đ)',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(201,169,110,0.2)',
            borderColor: '#c9a96e',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { color:'#888', callback: v => (v/1000)+'K' }, grid: { color:'rgba(255,255,255,0.05)' }, border:{color:'transparent'} },
            x: { ticks: { color:'#888' }, grid: { display: false }, border:{color:'transparent'} }
        }
    }
});
</script>
</body></html>
