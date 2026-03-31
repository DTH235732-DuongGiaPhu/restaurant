<?php
$pageTitle = 'Lịch đặt bàn của tôi';
require_once __DIR__ . '/includes/header.php';
if (!isLoggedIn()) {
    setFlash('error', 'Vui lòng đăng nhập để xem lịch đặt bàn');
    header('Location: ' . SITE_URL . '/login.php'); exit;
}
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT r.*, tp.name as pos_name, tp.icon as pos_icon, dt.name as decor_name, dt.color_hint as decor_color
    FROM reservations r
    LEFT JOIN table_positions tp ON r.position_id = tp.id
    LEFT JOIN decor_types dt ON r.decor_id = dt.id
    WHERE r.user_id = ?
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
");
$stmt->execute([$userId]);
$list = $stmt->fetchAll();

$statusMap = [
    'pending'   => ['Chờ xác nhận','warning','hourglass-split'],
    'confirmed' => ['Đã xác nhận','info','check-circle-fill'],
    'seated'    => ['Đang ngồi','success','person-fill'],
    'done'      => ['Hoàn thành','success','check2-circle'],
    'cancelled' => ['Đã huỷ','danger','x-circle-fill'],
];
?>
<div class="page-hero">
    <span class="page-hero-label">Tài khoản</span>
    <h1>Lịch <em>đặt bàn</em></h1>
    <div class="breadcrumb-nav"><a href="<?= SITE_URL ?>/">Trang chủ</a> <span>›</span> <span class="current">Đặt bàn của tôi</span></div>
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
                    <div style="color:var(--text-muted);font-size:12px;"><?= clean($_SESSION['user_email'] ?? '') ?></div>
                </div>
                <ul style="list-style:none;">
                    <li style="border-bottom:1px solid var(--border);">
                        <a href="<?= SITE_URL ?>/orders.php" style="display:flex;align-items:center;gap:10px;padding:12px 0;color:var(--text-muted);font-size:13px;font-weight:500;transition:var(--transition);"
                           onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--text-muted)'">
                            <i class="bi bi-bag-check"></i> Đơn hàng của tôi
                        </a>
                    </li>
                    <li style="border-bottom:1px solid var(--border);">
                        <a href="<?= SITE_URL ?>/my-reservations.php" style="display:flex;align-items:center;gap:10px;padding:12px 0;color:var(--gold);font-size:13px;font-weight:600;">
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

        <!-- Content -->
        <div class="col-lg-9">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
                <div>
                    <h2 style="font-family:var(--font-display);font-size:1.8rem;color:var(--cream);margin-bottom:4px">Lịch đặt bàn</h2>
                    <p style="color:var(--text-muted);font-size:13px">Tổng cộng <?= count($list) ?> lần đặt bàn</p>
                </div>
                <a href="<?= SITE_URL ?>/reservation.php" class="btn-gold" style="display:inline-flex;align-items:center;gap:8px"><i class="bi bi-plus-lg"></i> Đặt bàn mới</a>
            </div>

<?php if (empty($list)): ?>
            <div style="text-align:center;padding:80px 20px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius)">
                <div style="width:80px;height:80px;border-radius:50%;background:rgba(201,169,110,.08);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2.2rem;color:var(--text-muted)"><i class="bi bi-calendar-x"></i></div>
                <h3 style="font-family:var(--font-display);font-size:1.6rem;color:var(--cream);margin-bottom:8px">Chưa có lịch đặt bàn nào</h3>
                <p style="color:var(--text-muted);margin-bottom:24px">Hãy đặt bàn để có trải nghiệm hoàn hảo nhất!</p>
                <a href="<?= SITE_URL ?>/reservation.php" class="btn-gold"><i class="bi bi-calendar-plus"></i> Đặt bàn ngay</a>
            </div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:20px">
<?php foreach ($list as $r):
    [$label,$cls,$ico] = $statusMap[$r['status']] ?? ['Không xác định','secondary','question'];
    $isPast = strtotime($r['reservation_date']) < strtotime('today');
    $items = [];
    if ($r['has_preorder']) {
        $si = $pdo->prepare("SELECT * FROM reservation_items WHERE reservation_id=?");
        $si->execute([$r['id']]); $items = $si->fetchAll();
    }
?>
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:var(--transition)">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;background:var(--bg-secondary);border-bottom:1px solid var(--border);flex-wrap:wrap;gap:10px">
        <div>
            <span style="color:var(--gold);font-weight:700;font-size:15px;margin-right:10px">#<?= str_pad($r['id'],5,'0',STR_PAD_LEFT) ?></span>
            <span style="color:var(--text-muted);font-size:13px"><?= date('d/m/Y',strtotime($r['reservation_date'])) ?> lúc <?= substr($r['reservation_time'],0,5) ?></span>
        </div>
        <span style="font-size:12px;font-weight:700;padding:5px 12px;border-radius:100px;display:flex;align-items:center;gap:6px;background:rgba(<?= $cls==='warning'?'224,164,82':($cls==='success'?'82,183,136':($cls==='info'?'100,162,224':'224,82,82')) ?>,.12);color:<?= $cls==='warning'?'#e0a452':($cls==='success'?'var(--green)':($cls==='info'?'#64a2e0':'var(--red)')) ?>">
            <i class="bi bi-<?= $ico ?>"></i> <?= $label ?>
        </span>
    </div>
    <div style="padding:20px">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:16px">
            <div style="display:flex;align-items:flex-start;gap:10px">
                <i class="bi bi-<?= clean($r['pos_icon']??'geo-alt') ?>" style="color:var(--gold);font-size:1.2rem;margin-top:2px"></i>
                <div><div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Vị trí bàn</div><div style="color:var(--cream);font-weight:500;font-size:13px"><?= clean($r['pos_name']??'—') ?></div></div>
            </div>
            <div style="display:flex;align-items:flex-start;gap:10px">
                <i class="bi bi-people-fill" style="color:var(--gold);font-size:1.2rem;margin-top:2px"></i>
                <div><div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Số khách</div><div style="color:var(--cream);font-weight:500;font-size:13px"><?= $r['num_guests'] ?> khách</div></div>
            </div>
            <div style="display:flex;align-items:flex-start;gap:10px">
                <i class="bi bi-<?= $r['has_preorder']?'bag-check-fill':'calendar2-check' ?>" style="color:var(--gold);font-size:1.2rem;margin-top:2px"></i>
                <div><div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Loại đặt</div><div style="color:var(--cream);font-weight:500;font-size:13px"><?= $r['has_preorder']?'Đặt bàn + Món trước':'Chỉ đặt bàn' ?></div></div>
            </div>
            <?php if ($r['has_preorder']): ?>
            <div style="display:flex;align-items:flex-start;gap:10px">
                <i class="bi bi-stars" style="color:<?= $r['decor_name']?clean($r['decor_color']):'var(--text-muted)' ?>;font-size:1.2rem;margin-top:2px"></i>
                <div><div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">Decor</div><div style="color:var(--cream);font-weight:500;font-size:13px"><?= $r['decor_name']?clean($r['decor_name']):'Không có' ?></div></div>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($items)): ?>
        <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:12px">
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;display:flex;align-items:center;gap:6px"><i class="bi bi-egg-fried" style="color:var(--gold)"></i> Món đặt trước</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                <?php foreach ($items as $it): ?>
                <span style="background:rgba(201,169,110,.1);border:1px solid rgba(201,169,110,.2);border-radius:100px;padding:3px 10px;font-size:12px;color:var(--cream)"><?= clean($it['dish_name']) ?> × <?= $it['quantity'] ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($r['has_preorder'] && $r['total'] > 0): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;padding:10px 0;border-top:1px solid var(--border)">
            <span style="color:var(--text-muted);font-size:13px">
                Tiền món: <?= formatPrice($r['food_total']) ?>
                <?php if ($r['decor_fee'] > 0): ?> · Phí decor: <?= formatPrice($r['decor_fee']) ?>
                <?php elseif ($r['decor_name']): ?> · Decor: <span style="color:var(--green)">Miễn phí</span>
                <?php endif; ?>
            </span>
            <span style="color:var(--gold);font-weight:700;font-size:15px">Tổng: <?= formatPrice($r['total']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($r['note']): ?>
        <div style="font-size:13px;color:var(--text-muted);background:var(--bg-secondary);border-radius:var(--radius-sm);padding:10px 14px;margin-top:12px">
            <i class="bi bi-chat-left-text" style="color:var(--gold);margin-right:6px"></i><?= clean($r['note']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($r['status'] === 'pending' && !$isPast): ?>
    <div style="padding:14px 20px;border-top:1px solid var(--border);background:var(--bg-secondary)">
        <form method="POST" action="<?= SITE_URL ?>/reservation-cancel.php" onsubmit="return confirm('Bạn chắc chắn muốn huỷ đặt bàn này?')">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button type="submit" style="background:rgba(224,82,82,.1);border:1px solid rgba(224,82,82,.3);color:var(--red);border-radius:var(--radius-sm);padding:8px 18px;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px;transition:var(--transition)">
                <i class="bi bi-x-circle"></i> Huỷ đặt bàn
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
        </div><!-- /.col-lg-9 -->
    </div><!-- /.row -->
</div><!-- /.container -->
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>