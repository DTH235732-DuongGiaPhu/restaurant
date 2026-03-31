<?php
$pageTitle = 'Đơn hàng của tôi';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders->execute([$_SESSION['user_id']]);
$orders = $orders->fetchAll();

$statusMap = [
    'pending'    => ['label'=>'Chờ xác nhận', 'class'=>'status-pending'],
    'confirmed'  => ['label'=>'Đã xác nhận',  'class'=>'status-confirmed'],
    'cooking'    => ['label'=>'Đang nấu',      'class'=>'status-cooking'],
    'delivering' => ['label'=>'Đang giao',     'class'=>'status-delivering'],
    'done'       => ['label'=>'Hoàn thành',    'class'=>'status-done'],
    'cancelled'  => ['label'=>'Đã hủy',        'class'=>'status-cancelled'],
];
?>

<div class="page-hero">
    <span class="page-hero-label">Tài khoản</span>
    <h1>Đơn hàng <em>của tôi</em></h1>
    <div class="breadcrumb-nav"><a href="<?= SITE_URL ?>/">Trang chủ</a> <span>›</span> <span class="current">Đơn hàng</span></div>
</div>

<section class="form-section">
    <div class="container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="form-card">
                    <div style="text-align:center;margin-bottom:20px;">
                        <div style="width:64px;height:64px;border-radius:50%;background:var(--gold);color:#000;font-size:1.5rem;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <?= strtoupper(mb_substr($_SESSION['user_name'], 0, 1)) ?>
                        </div>
                        <div style="color:var(--cream);font-weight:600;"><?= clean($_SESSION['user_name']) ?></div>
                        <div style="color:var(--text-muted);font-size:12px;"><?= clean($_SESSION['user_email']) ?></div>
                    </div>
                    <ul style="list-style:none;">
                        <li style="border-bottom:1px solid var(--border);">
                            <a href="<?= SITE_URL ?>/orders.php" style="display:flex;align-items:center;gap:10px;padding:12px 0;color:var(--gold);font-size:13px;font-weight:600;">
                                <i class="bi bi-bag-check"></i> Đơn hàng của tôi
                            </a>
                        </li>
                        <li style="border-bottom:1px solid var(--border);">
                            <a href="<?= SITE_URL ?>/my-reservations.php" style="display:flex;align-items:center;gap:10px;padding:12px 0;color:var(--text-muted);font-size:13px;font-weight:500;transition:var(--transition);"
                               onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--text-muted)'">
                                <i class="bi bi-calendar2-check"></i> Đặt bàn của tôi
                            </a>
                        </li>
                        <li>
                            <a href="<?= SITE_URL ?>/logout.php" style="display:flex;align-items:center;gap:10px;padding:12px 0;color:var(--text-muted);font-size:13px;transition:var(--transition);"
                               onmouseover="this.style.color='var(--red)'" onmouseout="this.style.color='var(--text-muted)'">
                                <i class="bi bi-box-arrow-right"></i> Đăng xuất
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Orders List -->
            <div class="col-lg-9">
                <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="bi bi-bag-x"></i>
                    <h5>Chưa có đơn hàng nào</h5>
                    <p>Hãy đặt món ngay hôm nay!</p>
                    <a href="<?= SITE_URL ?>/menu.php" class="btn-gold mt-3" style="display:inline-block;">Xem thực đơn</a>
                </div>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <?php
                    $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    $items->execute([$order['id']]);
                    $items = $items->fetchAll();
                    $st = $statusMap[$order['status']] ?? ['label'=>$order['status'],'class'=>'status-pending'];
                ?>
                <div class="form-card mb-4">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border);">
                        <div>
                            <div style="color:var(--cream);font-weight:600;">
                                Đơn #<?= str_pad($order['id'],5,'0',STR_PAD_LEFT) ?>
                            </div>
                            <div style="color:var(--text-muted);font-size:12px;margin-top:3px;">
                                <i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </div>
                        </div>
                        <span class="status-badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
                    </div>

                    <?php foreach ($items as $item): ?>
                    <div style="display:flex;justify-content:space-between;font-size:13px;padding:5px 0;">
                        <span style="color:var(--text-muted);"><?= clean($item['dish_name']) ?> × <?= $item['quantity'] ?></span>
                        <span style="color:var(--cream);"><?= formatPrice($item['subtotal']) ?></span>
                    </div>
                    <?php endforeach; ?>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:14px;border-top:1px solid var(--border);flex-wrap:wrap;gap:10px;">
                        <div>
                            <div style="color:var(--text-muted);font-size:12px;">Địa chỉ: <?= clean($order['customer_address']) ?></div>
                            <div style="color:var(--text-muted);font-size:12px;">Thanh toán: <?= $order['payment_method'] === 'cash' ? 'Tiền mặt' : 'Chuyển khoản' ?></div>
                        </div>
                        <div style="text-align:right;">
                            <div style="color:var(--text-muted);font-size:12px;">Tổng tiền</div>
                            <div style="font-family:var(--font-display);font-size:1.3rem;color:var(--gold);font-weight:700;"><?= formatPrice($order['total']) ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>var SITE_URL = '<?= SITE_URL ?>';</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>