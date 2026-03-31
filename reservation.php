<?php
$pageTitle = 'Đặt bàn trước';
require_once __DIR__ . '/includes/header.php';

$positions  = $pdo->query("SELECT * FROM table_positions WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$decors     = $pdo->query("SELECT * FROM decor_types WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();
$dishes     = $pdo->query("SELECT d.*, c.name as cat_name FROM dishes d JOIN categories c ON d.category_id=c.id WHERE d.status='active' ORDER BY c.sort_order, d.name")->fetchAll();

define('DECOR_FEE', 100000);
define('DECOR_FREE_THRESHOLD', 1000000);

$errors = []; $success = false; $successId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type       = $_POST['reservation_type'] ?? 'table_only';
    $date       = $_POST['reservation_date'] ?? '';
    $time       = $_POST['reservation_time'] ?? '';
    $guests     = (int)($_POST['num_guests'] ?? 2);
    $positionId = (int)($_POST['position_id'] ?? 0);
    $decorId    = ($type === 'preorder') ? (int)($_POST['decor_id'] ?? 0) : 0;
    $name       = clean($_POST['customer_name'] ?? '');
    $phone      = clean($_POST['customer_phone'] ?? '');
    $email      = clean($_POST['customer_email'] ?? '');
    $note       = clean($_POST['note'] ?? '');
    $payment    = in_array($_POST['payment_method'] ?? '', ['cash','transfer','none']) ? $_POST['payment_method'] : 'none';

    if (!$name)  $errors[] = 'Vui lòng nhập họ tên';
    if (!$phone) $errors[] = 'Vui lòng nhập số điện thoại';
    if (!preg_match('/^[0-9]{9,11}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ';
    if (!$date)  $errors[] = 'Vui lòng chọn ngày đặt bàn';
    if (!$time)  $errors[] = 'Vui lòng chọn giờ đặt bàn';
    if ($date && strtotime($date) < strtotime('today')) $errors[] = 'Ngày đặt bàn phải từ hôm nay trở đi';
    if ($guests < 1 || $guests > 50) $errors[] = 'Số khách phải từ 1 đến 50';
    if (!$positionId) $errors[] = 'Vui lòng chọn vị trí bàn';
    if ($type === 'table_only') $payment = 'none';

    $foodTotal = 0; $preOrderItems = [];
    if ($type === 'preorder') {
        $dishIds  = $_POST['dish_id'] ?? [];
        $dishQtys = $_POST['dish_qty'] ?? [];
        foreach ($dishIds as $i => $dId) {
            $qty  = max(1, (int)($dishQtys[$i] ?? 1));
            $stmt = $pdo->prepare("SELECT * FROM dishes WHERE id=? AND status='active'");
            $stmt->execute([$dId]);
            $d = $stmt->fetch();
            if ($d) {
                $sub = $d['price'] * $qty;
                $foodTotal += $sub;
                $preOrderItems[] = ['id'=>$d['id'],'name'=>$d['name'],'price'=>$d['price'],'qty'=>$qty,'sub'=>$sub];
            }
        }
        if (empty($preOrderItems)) $errors[] = 'Bạn chọn "Đặt bàn & Đặt món" nhưng chưa chọn món nào';
    }

    $decorFee = ($type === 'preorder' && $decorId > 0)
                ? ($foodTotal >= DECOR_FREE_THRESHOLD ? 0 : DECOR_FEE)
                : 0;

    // Xử lý coupon (chỉ áp dụng khi preorder có tiền món)
    $couponCode = strtoupper(clean($_POST['coupon_code'] ?? ''));
    $couponDiscount = 0;
    $couponData = null;
    if ($type === 'preorder' && $couponCode && $foodTotal > 0) {
        $stmtC = $pdo->prepare("SELECT * FROM coupons WHERE code=? AND is_active=1 AND (expires_at IS NULL OR expires_at >= CURDATE()) AND (max_uses IS NULL OR used_count < max_uses)");
        $stmtC->execute([$couponCode]);
        $couponData = $stmtC->fetch();
        if (!$couponData) {
            $errors[] = 'Mã giảm giá không hợp lệ hoặc đã hết hạn';
        } elseif ($foodTotal < $couponData['min_order']) {
            $errors[] = 'Đơn hàng tối thiểu ' . number_format($couponData['min_order'],0,',','.') . 'đ để dùng mã này';
            $couponData = null;
        } else {
            $couponDiscount = $couponData['type']==='percent'
                ? round($foodTotal * $couponData['value'] / 100)
                : min($couponData['value'], $foodTotal);
        }
    }
    $total = max(0, $foodTotal - $couponDiscount) + $decorFee;

    if (empty($errors)) {
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        $hpre   = ($type === 'preorder') ? 1 : 0;
        $dId    = ($decorId > 0 && $hpre) ? $decorId : null;
        $pm     = $hpre ? $payment : 'none';

        $stmt = $pdo->prepare("INSERT INTO reservations
            (user_id,customer_name,customer_phone,customer_email,reservation_date,reservation_time,
             num_guests,position_id,has_preorder,decor_id,food_total,decor_fee,total,note,payment_method)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$userId,$name,$phone,$email?:null,$date,$time,
                        $guests,$positionId,$hpre,$dId,$foodTotal,$decorFee,$total,$note?:null,$pm]);
        $resId = $pdo->lastInsertId();

        if (!empty($preOrderItems)) {
            $si = $pdo->prepare("INSERT INTO reservation_items (reservation_id,dish_id,dish_name,dish_price,quantity,subtotal) VALUES (?,?,?,?,?,?)");
            foreach ($preOrderItems as $it) $si->execute([$resId,$it['id'],$it['name'],$it['price'],$it['qty'],$it['sub']]);
        }

        // Tăng used_count coupon
        if ($couponData) {
            $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id=?")->execute([$couponData['id']]);
        }
        $success = true; $successId = $resId;
    }
}
?>

<div class="page-hero">
    <span class="page-hero-label">Saigon Bistro</span>
    <h1>Đặt <em>bàn trước</em></h1>
    <div class="breadcrumb-nav">
        <a href="<?= SITE_URL ?>/">Trang chủ</a> <span>›</span>
        <span class="current">Đặt bàn</span>
    </div>
</div>

<section class="reservation-section">
<div class="container">

<?php if ($success): ?>
<div class="reservation-success" data-aos="zoomIn">
    <div class="success-icon-wrap"><i class="bi bi-calendar2-check-fill"></i></div>
    <h2>Đặt bàn thành công!</h2>
    <p class="success-code">Mã đặt bàn: <strong>#<?= str_pad($successId, 5, '0', STR_PAD_LEFT) ?></strong></p>
    <p class="success-note">Chúng tôi sẽ gọi điện xác nhận trong vòng <strong>15 phút</strong>. Cảm ơn quý khách!</p>
    <div class="success-actions">
        <a href="<?= SITE_URL ?>/my-reservations.php" class="btn-gold"><i class="bi bi-calendar3"></i> Xem lịch đặt bàn</a>
        <a href="<?= SITE_URL ?>/" class="btn-outline-gold">Về trang chủ</a>
    </div>
</div>
<?php else: ?>

<?php if (!empty($errors)): ?>
<div class="res-error-box" id="errorBox">
    <?php foreach ($errors as $e): ?><div><i class="bi bi-exclamation-circle-fill"></i> <?= $e ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="res-wizard">
    <!-- Progress -->
    <div class="wizard-progress">
        <div class="wp-step active" data-step="1"><div class="wp-dot"><i class="bi bi-grid-1x2"></i></div><span>Loại đặt</span></div>
        <div class="wp-line"></div>
        <div class="wp-step" data-step="2"><div class="wp-dot"><i class="bi bi-calendar3"></i></div><span>Bàn &amp; Giờ</span></div>
        <div class="wp-line"></div>
        <div class="wp-step" data-step="3"><div class="wp-dot"><i class="bi bi-stars"></i></div><span>Decor</span></div>
        <div class="wp-line"></div>
        <div class="wp-step" data-step="4"><div class="wp-dot"><i class="bi bi-egg-fried"></i></div><span>Chọn món</span></div>
        <div class="wp-line"></div>
        <div class="wp-step" data-step="5"><div class="wp-dot"><i class="bi bi-person-fill"></i></div><span>Thông tin</span></div>
        <div class="wp-line"></div>
        <div class="wp-step" data-step="6"><div class="wp-dot"><i class="bi bi-check-lg"></i></div><span>Xác nhận</span></div>
    </div>

    <form method="POST" id="resForm">
    <input type="hidden" name="reservation_type" id="resType" value="table_only">

    <!-- ── STEP 1 ── -->
    <div class="wizard-step active" data-step="1">
        <div class="step-header">
            <h3>Bạn muốn đặt theo hình thức nào?</h3>
            <p>Lựa chọn hình thức phù hợp sẽ tạo nên trải nghiệm hoàn hảo nhất cho bạn</p>
        </div>
        <div class="type-cards">
            <label class="type-card active" id="cardTableOnly" onclick="selectType('table_only')">
                <div class="tc-icon"><i class="bi bi-calendar2-date"></i></div>
                <div class="tc-check"><i class="bi bi-check-circle-fill"></i></div>
                <h4>Chỉ đặt bàn trước</h4>
                <p>Giữ chỗ tại bàn yêu thích, đến nhà hàng gọi món trực tiếp với đầy đủ thực đơn.</p>
                <ul class="tc-features">
                    <li><i class="bi bi-check2 text-green"></i> Giữ chỗ đảm bảo</li>
                    <li><i class="bi bi-check2 text-green"></i> Gọi món tại bàn theo ý thích</li>
                    <li><i class="bi bi-x2 text-red"></i> Không có decor trang trí</li>
                    <li><i class="bi bi-x2 text-red"></i> Không cần thanh toán trước</li>
                </ul>
                <div class="tc-badge free">Hoàn toàn miễn phí</div>
            </label>
            <label class="type-card" id="cardPreorder" onclick="selectType('preorder')">
                <div class="tc-icon golden"><i class="bi bi-bag-heart-fill"></i></div>
                <div class="tc-check"><i class="bi bi-check-circle-fill"></i></div>
                <h4>Đặt bàn &amp; Đặt món trước</h4>
                <p>Đặt sẵn món ăn và phong cách trang trí bàn. Đến là tận hưởng ngay — không chờ đợi.</p>
                <ul class="tc-features">
                    <li><i class="bi bi-check2 text-green"></i> Giữ chỗ đảm bảo</li>
                    <li><i class="bi bi-check2 text-green"></i> Món ăn sẵn sàng khi bạn đến</li>
                    <li><i class="bi bi-check2 text-green"></i> Trang trí bàn theo yêu cầu</li>
                    <li><i class="bi bi-check2 text-green"></i> Trải nghiệm VIP trọn vẹn</li>
                </ul>
                <div class="tc-badge vip">✦ Trải nghiệm VIP</div>
            </label>
        </div>
        <div class="step-actions"><div></div>
            <button type="button" class="btn-gold" onclick="goStep(2)">Tiếp theo <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>

    <!-- ── STEP 2 ── -->
    <div class="wizard-step" data-step="2">
        <div class="step-header">
            <h3>Chọn thời gian &amp; vị trí bàn</h3>
            <p>Nhà hàng mở cửa từ 10:00 đến 22:00, tất cả các ngày trong tuần</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="form-card">
                    <div class="form-card-title"><i class="bi bi-clock-fill" style="color:var(--gold)"></i> Thời gian &amp; Số khách</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Ngày đặt bàn *</label>
                                <input type="date" name="reservation_date" id="resDate" class="form-control-custom" min="<?= date('Y-m-d') ?>" value="<?= clean($_POST['reservation_date'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Giờ đặt bàn *</label>
                                <select name="reservation_time" id="resTime" class="form-control-custom" required>
                                    <option value="">-- Chọn giờ --</option>
                                    <?php
                                    $selT = $_POST['reservation_time'] ?? '';
                                    for ($h = 10; $h <= 21; $h++) {
                                        foreach (['00','30'] as $m) {
                                            $v = sprintf('%02d:%s:00',$h,$m);
                                            $l = sprintf('%02d:%s',$h,$m);
                                            echo "<option value=\"$v\"".($selT===$v?' selected':'').">$l</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Số khách *</label>
                                <div class="guest-counter">
                                    <button type="button" class="gc-btn" onclick="changeGuests(-1)"><i class="bi bi-dash"></i></button>
                                    <input type="number" name="num_guests" id="numGuests" class="gc-input" value="<?= (int)($_POST['num_guests'] ?? 2) ?>" min="1" max="50" readonly>
                                    <button type="button" class="gc-btn" onclick="changeGuests(1)"><i class="bi bi-plus"></i></button>
                                    <span class="gc-label">khách</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="form-card">
                    <div class="form-card-title"><i class="bi bi-geo-alt-fill" style="color:var(--gold)"></i> Vị trí bàn *</div>
                    <div class="position-grid">
                        <?php foreach ($positions as $pos):
                            $selP = (int)($_POST['position_id'] ?? 0);
                        ?>
                        <label class="pos-card <?= $selP==$pos['id']?'active':'' ?>">
                            <input type="radio" name="position_id" value="<?= $pos['id'] ?>" <?= $selP==$pos['id']?'checked':'' ?> required>
                            <div class="pos-icon"><i class="bi bi-<?= clean($pos['icon']) ?>"></i></div>
                            <div class="pos-info">
                                <h5><?= clean($pos['name']) ?></h5>
                                <p><?= clean($pos['description']) ?></p>
                                <span class="pos-cap"><i class="bi bi-people"></i> Tối đa <?= $pos['max_guests'] ?> khách</span>
                            </div>
                            <div class="pos-check"><i class="bi bi-check-circle-fill"></i></div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="step-actions">
            <button type="button" class="btn-outline-gold" onclick="goStep(1)"><i class="bi bi-arrow-left"></i> Quay lại</button>
            <button type="button" class="btn-gold" onclick="validateStep2()">Tiếp theo <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>

    <!-- ── STEP 3: Decor ── -->
    <div class="wizard-step" data-step="3">
        <div class="step-header">
            <h3>Chọn phong cách trang trí</h3>
            <p>Biến bữa ăn thành khoảnh khắc đáng nhớ suốt đời</p>
        </div>
        <div class="decor-pricing-note">
            <div class="dpn-icon"><i class="bi bi-info-circle-fill"></i></div>
            <div>
                <strong>Chính sách phí Decor:</strong>
                Miễn phí khi tổng tiền món đặt trước đạt <strong>1.000.000đ</strong> trở lên.
                Dưới ngưỡng đó, phí trang trí là <strong>100.000đ</strong> và sẽ được cộng vào hóa đơn.
            </div>
        </div>
        <div class="decor-grid">
            <label class="decor-card active" id="decorNone">
                <input type="radio" name="decor_id" value="0" checked>
                <div class="dc-icon" style="color:var(--text-muted)"><i class="bi bi-x-circle"></i></div>
                <div class="dc-check"><i class="bi bi-check-circle-fill"></i></div>
                <h5>Không cần decor</h5>
                <p>Để nguyên bàn tiêu chuẩn sang trọng của nhà hàng</p>
                <div class="dc-price" style="color:var(--green)">Miễn phí</div>
            </label>
            <?php foreach ($decors as $dc): ?>
            <label class="decor-card">
                <input type="radio" name="decor_id" value="<?= $dc['id'] ?>">
                <div class="dc-icon" style="color:<?= clean($dc['color_hint']) ?>"><i class="bi bi-<?= clean($dc['icon']) ?>"></i></div>
                <div class="dc-check"><i class="bi bi-check-circle-fill"></i></div>
                <h5><?= clean($dc['name']) ?></h5>
                <p><?= clean($dc['description']) ?></p>
                <div class="dc-price" id="dcPrice<?= $dc['id'] ?>">100.000đ</div>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="step-actions">
            <button type="button" class="btn-outline-gold" onclick="goStep(2)"><i class="bi bi-arrow-left"></i> Quay lại</button>
            <button type="button" class="btn-gold" onclick="goStep(4)">Tiếp theo <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>

    <!-- ── STEP 4: Chọn món ── -->
    <div class="wizard-step" data-step="4">
        <div class="step-header">
            <h3>Đặt món ăn trước</h3>
            <p>Món được chuẩn bị sẵn khi bạn đến — tươi ngon, nóng hổi, đúng giờ</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="dish-cat-filter">
                    <button type="button" class="dcf-btn active" onclick="filterDishes('all',this)">Tất cả</button>
                    <?php foreach ($categories as $cat): ?>
                    <button type="button" class="dcf-btn" onclick="filterDishes(<?= $cat['id'] ?>,this)"><?= clean($cat['name']) ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="dish-search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="dishSearch" placeholder="Tìm tên món ăn..." oninput="searchDishes(this.value)">
                </div>
                <div class="order-dish-grid" id="dishGrid">
                    <?php foreach ($dishes as $dish): ?>
                    <div class="odg-card" data-cat="<?= $dish['category_id'] ?>" data-name="<?= mb_strtolower($dish['name'], 'UTF-8') ?>">
                        <div class="odg-img">
                            <img src="<?= SITE_URL ?>/uploads/dishes/<?= clean($dish['image'] ?? '') ?>" alt="<?= clean($dish['name']) ?>"
                                 onerror="this.src='<?= SITE_URL ?>/assets/images/dish-default.jpg'">
                        </div>
                        <div class="odg-info">
                            <div class="odg-name"><?= clean($dish['name']) ?></div>
                            <div class="odg-price"><?= formatPrice($dish['price']) ?></div>
                        </div>
                        <button type="button" class="odg-add" title="Thêm" onclick="addToPreOrder(<?= $dish['id'] ?>,'<?= addslashes(clean($dish['name'])) ?>',<?= $dish['price'] ?>)">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="preorder-cart" id="preorderCart">
                    <div class="form-card-title"><i class="bi bi-bag-check" style="color:var(--gold)"></i> Món đã chọn</div>
                    <div id="preorderItems">
                        <div class="poc-empty" id="pocEmpty"><i class="bi bi-bag-x"></i><p>Chưa có món nào</p></div>
                    </div>
                    <div id="preorderFooter" style="display:none">
                        <div class="poc-row"><span>Tổng tiền món</span><span class="poc-total" id="pocFoodTotal">0đ</span></div>
                        <div class="poc-row" id="pocDecorRow" style="display:none">
                            <span id="pocDecorLabel">Phí decor</span>
                            <span id="pocDecorAmt"></span>
                        </div>
                        <!-- Coupon input -->
                        <div id="pocCouponWrap" style="padding:10px 0;border-top:1px solid var(--border);margin-top:4px">
                            <div style="font-size:11px;color:var(--text-muted);margin-bottom:6px;font-weight:600;letter-spacing:.05em;text-transform:uppercase"><i class="bi bi-ticket-perforated" style="color:var(--gold)"></i> Mã giảm giá</div>
                            <div style="display:flex;gap:6px">
                                <input type="text" id="resCouponInput" name="coupon_code" placeholder="Nhập mã..." 
                                    style="flex:1;background:var(--bg-secondary);border:1px solid var(--border);border-radius:6px;padding:7px 10px;color:var(--cream);font-size:12px;text-transform:uppercase;font-family:var(--font-body)"
                                    oninput="this.value=this.value.toUpperCase();clearResDiscount()">
                                <button type="button" onclick="applyResCoupon()" id="resCouponBtn"
                                    style="background:rgba(201,169,110,.15);border:1px solid rgba(201,169,110,.3);border-radius:6px;padding:7px 10px;color:var(--gold);font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;transition:var(--transition)">
                                    Áp dụng
                                </button>
                            </div>
                            <div id="resCouponMsg" style="margin-top:6px;font-size:11px;display:none"></div>
                        </div>
                        <div class="poc-row" id="pocDiscountRow" style="display:none">
                            <span style="color:var(--green)"><i class="bi bi-tag-fill"></i> Giảm giá</span>
                            <span style="color:var(--green);font-weight:600" id="pocDiscountAmt">-0đ</span>
                        </div>
                        <div class="poc-row grand"><span>Tạm tính</span><span class="poc-grand" id="pocGrand">0đ</span></div>
                        <div class="poc-decor-note" id="pocDecorNote" style="display:none"></div>
                    </div>
                </div>
                <div id="hiddenPreorderInputs"></div>
            </div>
        </div>
        <div class="step-actions">
            <button type="button" class="btn-outline-gold" onclick="goStep(3)"><i class="bi bi-arrow-left"></i> Quay lại</button>
            <button type="button" class="btn-gold" onclick="validateStep4()">Tiếp theo <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>

    <!-- ── STEP 5: Thông tin ── -->
    <div class="wizard-step" data-step="5">
        <div class="step-header">
            <h3>Thông tin liên hệ</h3>
            <p>Chúng tôi sẽ dùng số điện thoại này để xác nhận đặt bàn với bạn</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="form-card">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Họ và tên *</label>
                                <input type="text" name="customer_name" class="form-control-custom"
                                    value="<?= isset($_POST['customer_name']) ? clean($_POST['customer_name']) : (isLoggedIn() ? clean($_SESSION['user_name']) : '') ?>"
                                    placeholder="Nguyễn Văn A" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Số điện thoại *</label>
                                <input type="tel" name="customer_phone" class="form-control-custom"
                                    value="<?= clean($_POST['customer_phone'] ?? '') ?>" placeholder="0901234567" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Email <span style="color:var(--text-muted);font-weight:400">(không bắt buộc)</span></label>
                                <input type="email" name="customer_email" class="form-control-custom"
                                    value="<?= clean($_POST['customer_email'] ?? '') ?>" placeholder="email@example.com">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Yêu cầu đặc biệt</label>
                                <textarea name="note" class="form-control-custom" rows="3"
                                    placeholder="Dị ứng thực phẩm, yêu cầu về chỗ ngồi, trang trí thêm..."><?= clean($_POST['note'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="paymentSection" style="display:none; margin-top:20px">
                    <div class="form-card">
                        <div class="form-card-title"><i class="bi bi-credit-card" style="color:var(--gold)"></i> Phương thức thanh toán</div>
                        <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">Thanh toán khi đến nhà hàng hoặc chuyển khoản trước</p>
                        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:0" id="payRadioWrap">
                            <label class="payment-option" id="payOptCash">
                                <input type="radio" name="payment_radio" value="cash" checked onchange="setPayment('cash');showQRBox(false)">
                                <span><i class="bi bi-cash-coin"></i> Tiền mặt khi đến</span>
                            </label>
                            <label class="payment-option" id="payOptTransfer">
                                <input type="radio" name="payment_radio" value="transfer" onchange="setPayment('transfer');showQRBox(true)">
                                <span><i class="bi bi-qr-code"></i> Chuyển khoản trước</span>
                            </label>
                        </div>

                        <!-- QR Box — hiện khi chọn chuyển khoản -->
                        <div id="qrTransferBox" style="display:none;margin-top:20px;animation:fadeInUp .3s ease">
                            <div style="background:rgba(201,169,110,.04);border:1px solid rgba(201,169,110,.25);border-radius:var(--radius);padding:24px">
                                <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:18px;display:flex;align-items:center;gap:8px">
                                    <i class="bi bi-qr-code-scan"></i> Thông tin chuyển khoản
                                </div>
                                <div style="display:flex;gap:28px;align-items:flex-start;flex-wrap:wrap">
                                    <!-- QR code -->
                                    <div style="flex-shrink:0;text-align:center">
                                        <div style="background:#fff;border-radius:12px;padding:14px;display:inline-block;box-shadow:0 4px 24px rgba(201,169,110,.25)">
                                            <img id="qrImg"
                                                src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=00020101021238570010A000000727012700069704220113FT20241234567890208QRIBFTTA5303704540005802VN5912SAIGON+BISTRO6008HO+CHI+M62270823DATBAN<?= date('YmdHis') ?>6304ABCD"
                                                width="180" height="180" alt="QR chuyển khoản"
                                                style="display:block;border-radius:6px"
                                                onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=SAIGONBISTRO-VCB-1234567890'">
                                        </div>
                                        <p style="font-size:11px;color:var(--text-muted);margin-top:10px;line-height:1.5">Quét bằng app<br>ngân hàng bất kỳ</p>
                                    </div>
                                    <!-- Bank info -->
                                    <div style="flex:1;min-width:200px">
                                        <div style="display:flex;flex-direction:column;gap:10px">
                                            <div style="background:var(--bg-secondary);border-radius:8px;padding:12px 14px">
                                                <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">Ngân hàng</div>
                                                <div style="color:var(--cream);font-weight:700;font-size:14px;display:flex;align-items:center;gap:8px">
                                                    <span style="background:#006a4e;color:#fff;font-size:10px;font-weight:800;padding:2px 8px;border-radius:4px;letter-spacing:.05em">VCB</span>
                                                    Vietcombank — Chi nhánh TP.HCM
                                                </div>
                                            </div>
                                            <div style="background:var(--bg-secondary);border-radius:8px;padding:12px 14px">
                                                <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">Số tài khoản</div>
                                                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                                                    <span id="stk" style="color:var(--gold);font-weight:700;font-size:1.1rem;letter-spacing:.05em">1234 5678 9012</span>
                                                    <button type="button" onclick="copySTK()" title="Sao chép" style="background:rgba(201,169,110,.12);border:1px solid rgba(201,169,110,.3);border-radius:6px;padding:5px 10px;color:var(--gold);font-size:11px;cursor:pointer;transition:var(--transition);display:flex;align-items:center;gap:4px" id="copyBtn">
                                                        <i class="bi bi-copy"></i> Copy
                                                    </button>
                                                </div>
                                            </div>
                                            <div style="background:var(--bg-secondary);border-radius:8px;padding:12px 14px">
                                                <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">Chủ tài khoản</div>
                                                <div style="color:var(--cream);font-weight:600;font-size:14px">SAIGON BISTRO</div>
                                            </div>
                                            <div style="background:var(--bg-secondary);border-radius:8px;padding:12px 14px">
                                                <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">Số tiền</div>
                                                <div id="qrAmount" style="color:var(--gold);font-weight:700;font-size:1.1rem">— (tính sau khi xác nhận)</div>
                                            </div>
                                            <div style="background:rgba(201,169,110,.06);border:1px solid rgba(201,169,110,.2);border-radius:8px;padding:12px 14px">
                                                <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">Nội dung chuyển khoản</div>
                                                <div style="color:var(--cream);font-weight:700;font-size:13px" id="qrContent">DATBAN [SĐT của bạn]</div>
                                            </div>
                                        </div>
                                        <div style="margin-top:14px;background:rgba(82,183,136,.06);border:1px solid rgba(82,183,136,.2);border-radius:8px;padding:10px 12px;font-size:12px;color:var(--green);line-height:1.6">
                                            <i class="bi bi-shield-check"></i>
                                            Chúng tôi xác nhận đặt bàn trong vòng <strong>15 phút</strong> sau khi nhận được chuyển khoản.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="payment_method" id="paymentMethodHidden" value="none">
            </div>
        </div>
        <div class="step-actions">
            <button type="button" class="btn-outline-gold" id="step5Back" onclick="goStep(4)"><i class="bi bi-arrow-left"></i> Quay lại</button>
            <button type="button" class="btn-gold" onclick="validateStep5()">Xem xác nhận <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>

    <!-- ── STEP 6: Xác nhận ── -->
    <div class="wizard-step" data-step="6">
        <div class="step-header">
            <h3>Xác nhận đặt bàn</h3>
            <p>Kiểm tra lại toàn bộ thông tin trước khi gửi</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-lg-8">
                <div class="confirm-card" id="confirmSummary"></div>
                <div style="text-align:center;margin-top:32px">
                    <p style="font-size:13px;color:var(--text-muted);margin-bottom:24px">
                        <i class="bi bi-shield-check" style="color:var(--green)"></i>
                        Thông tin của bạn được bảo mật tuyệt đối. Chúng tôi sẽ liên hệ xác nhận trong 15 phút.
                    </p>
                    <div class="step-actions" style="justify-content:center;gap:16px;border-top:none;margin-top:0;padding-top:0">
                        <button type="button" class="btn-outline-gold" onclick="goStep(5)"><i class="bi bi-arrow-left"></i> Chỉnh sửa</button>
                        <button type="submit" class="btn-gold" id="submitBtn" style="padding:14px 36px">
                            <i class="bi bi-calendar2-check"></i> Xác nhận đặt bàn
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </form>
</div>
<?php endif; ?>
</div>
</section>

<style>
.reservation-section{padding:60px 0 100px}
.wizard-progress{display:flex;align-items:center;justify-content:center;margin-bottom:48px;flex-wrap:nowrap;overflow-x:auto;padding-bottom:12px;gap:0}
.wp-step{display:flex;flex-direction:column;align-items:center;gap:8px;min-width:72px}
.wp-dot{width:44px;height:44px;border-radius:50%;border:2px solid var(--border);background:var(--bg-card);display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--text-muted);transition:var(--transition)}
.wp-step span{font-size:10px;color:var(--text-muted);text-align:center;white-space:nowrap}
.wp-step.active .wp-dot{border-color:var(--gold);background:rgba(201,169,110,0.12);color:var(--gold)}
.wp-step.active span{color:var(--gold);font-weight:600}
.wp-step.done .wp-dot{border-color:var(--green);background:rgba(82,183,136,0.12);color:var(--green)}
.wp-step.done span{color:var(--green)}
.wp-line{flex:1;height:2px;background:var(--border);min-width:16px;max-width:60px;margin-bottom:24px;transition:background .4s}
.wp-line.done{background:var(--green)}
.wizard-step{display:none;animation:fadeInUp .35s ease}
.wizard-step.active{display:block}
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.step-header{text-align:center;margin-bottom:36px}
.step-header h3{font-family:var(--font-display);font-size:2rem;color:var(--cream);margin-bottom:8px}
.step-header p{color:var(--text-muted);font-size:14px}
.type-cards{display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:760px;margin:0 auto}
@media(max-width:600px){.type-cards{grid-template-columns:1fr}}
.type-card{position:relative;cursor:pointer;background:var(--bg-card);border:2px solid var(--border);border-radius:var(--radius);padding:28px 24px 24px;transition:var(--transition)}
.type-card:hover{border-color:rgba(201,169,110,0.35);background:var(--bg-card-hover)}
.type-card.active{border-color:var(--gold);background:rgba(201,169,110,0.04);box-shadow:0 0 0 1px rgba(201,169,110,.15)}
.tc-icon{font-size:2.8rem;color:var(--text-muted);margin-bottom:16px}
.tc-icon.golden{color:var(--gold)}
.tc-check{position:absolute;top:16px;right:16px;font-size:1.3rem;color:var(--gold);opacity:0;transition:var(--transition)}
.type-card.active .tc-check{opacity:1}
.type-card h4{font-family:var(--font-display);font-size:1.4rem;color:var(--cream);margin-bottom:8px}
.type-card p{font-size:13px;color:var(--text-muted);margin-bottom:16px}
.tc-features{list-style:none;padding:0;margin:0}
.tc-features li{font-size:12px;color:var(--text-muted);padding:3px 0;display:flex;align-items:center;gap:6px}
.tc-features .text-green{color:var(--green)}
.tc-features .text-red{color:var(--red);opacity:.7}
.tc-badge{display:inline-block;margin-top:16px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 12px;border-radius:100px}
.tc-badge.free{background:rgba(82,183,136,.15);color:var(--green)}
.tc-badge.vip{background:rgba(201,169,110,.15);color:var(--gold)}
.guest-counter{display:flex;align-items:center;gap:10px}
.gc-btn{width:36px;height:36px;border-radius:50%;background:var(--bg-secondary);border:1px solid var(--border);color:var(--gold);font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:var(--transition)}
.gc-btn:hover{border-color:var(--gold);background:rgba(201,169,110,.1)}
.gc-input{width:60px;text-align:center;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px;color:var(--cream);font-size:1rem;font-weight:700}
.gc-label{color:var(--text-muted);font-size:13px}
.position-grid{display:flex;flex-direction:column;gap:12px}
.pos-card{display:flex;align-items:center;gap:14px;cursor:pointer;background:var(--bg-secondary);border:2px solid var(--border);border-radius:var(--radius-sm);padding:14px 16px;transition:var(--transition);position:relative}
.pos-card:hover{border-color:rgba(201,169,110,.3)}
.pos-card:has(input:checked){border-color:var(--gold);background:rgba(201,169,110,.04)}
.pos-card input{display:none}
.pos-icon{font-size:1.6rem;color:var(--gold);min-width:36px;text-align:center}
.pos-info{flex:1}
.pos-info h5{color:var(--cream);font-size:14px;font-weight:600;margin:0 0 3px}
.pos-info p{color:var(--text-muted);font-size:12px;margin:0 0 4px}
.pos-cap{font-size:11px;color:var(--text-muted)}
.pos-cap i{color:var(--gold)}
.pos-check{color:var(--gold);opacity:0;transition:var(--transition);font-size:1.1rem}
.pos-card:has(input:checked) .pos-check{opacity:1}
.decor-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:16px;margin-bottom:32px}
.decor-card{cursor:pointer;background:var(--bg-card);border:2px solid var(--border);border-radius:var(--radius);padding:22px 18px;text-align:center;transition:var(--transition);position:relative}
.decor-card:hover{border-color:rgba(201,169,110,.3);transform:translateY(-2px)}
.decor-card:has(input:checked){border-color:var(--gold);background:rgba(201,169,110,.05);transform:translateY(-3px);box-shadow:var(--shadow-gold)}
.decor-card input{display:none}
.dc-icon{font-size:2.2rem;margin-bottom:12px}
.dc-check{position:absolute;top:10px;right:10px;color:var(--gold);font-size:1.1rem;opacity:0;transition:var(--transition)}
.decor-card:has(input:checked) .dc-check{opacity:1}
.decor-card h5{color:var(--cream);font-size:14px;font-weight:600;margin:0 0 6px}
.decor-card p{color:var(--text-muted);font-size:11px;line-height:1.5;margin:0 0 12px}
.dc-price{font-size:12px;font-weight:700;color:var(--gold)}
.decor-pricing-note{display:flex;gap:12px;align-items:flex-start;background:rgba(201,169,110,.06);border:1px solid rgba(201,169,110,.2);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:24px;font-size:13px;color:var(--text-muted);line-height:1.6}
.dpn-icon{color:var(--gold);font-size:1.1rem;margin-top:2px;flex-shrink:0}
.decor-pricing-note strong{color:var(--cream)}
.dish-cat-filter{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.dcf-btn{background:var(--bg-card);border:1px solid var(--border);border-radius:100px;padding:6px 16px;font-size:12px;color:var(--text-muted);cursor:pointer;transition:var(--transition)}
.dcf-btn:hover,.dcf-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,169,110,.08)}
.dish-search-wrap{position:relative;margin-bottom:16px}
.dish-search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted)}
.dish-search-wrap input{width:100%;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px 10px 38px;color:var(--cream);font-size:13px;font-family:var(--font-body)}
.dish-search-wrap input:focus{outline:none;border-color:var(--gold)}
.order-dish-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:12px;max-height:480px;overflow-y:auto;padding-right:4px}
.order-dish-grid::-webkit-scrollbar{width:4px}
.order-dish-grid::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
.odg-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;position:relative;transition:var(--transition)}
.odg-card:hover{border-color:rgba(201,169,110,.3)}
.odg-card.hidden{display:none}
.odg-img{height:110px;overflow:hidden}
.odg-img img{width:100%;height:100%;object-fit:cover;transition:var(--transition)}
.odg-card:hover .odg-img img{transform:scale(1.06)}
.odg-info{padding:8px 10px 36px}
.odg-name{font-size:12px;color:var(--cream);font-weight:600;margin-bottom:3px;line-height:1.3}
.odg-price{font-size:11px;color:var(--gold);font-weight:700}
.odg-add{position:absolute;bottom:8px;right:8px;width:28px;height:28px;border-radius:50%;background:var(--gold);border:none;color:#000;font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:var(--transition)}
.odg-add:hover{background:var(--gold-light);transform:scale(1.1)}
.preorder-cart{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;position:sticky;top:100px}
.poc-empty{text-align:center;padding:28px 0;color:var(--text-muted)}
.poc-empty i{font-size:2rem;display:block;margin-bottom:8px}
.poc-empty p{font-size:13px}
.poc-item{display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid var(--border)}
.poc-item-name{flex:1;font-size:12px;color:var(--cream);line-height:1.3}
.poc-item-sub{font-size:12px;color:var(--gold);font-weight:600;white-space:nowrap}
.poc-qty{display:flex;align-items:center;gap:4px}
.poc-qty-btn{width:22px;height:22px;border-radius:50%;background:var(--bg-secondary);border:1px solid var(--border);color:var(--text-muted);font-size:.75rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:var(--transition);padding:0}
.poc-qty-btn:hover{border-color:var(--gold);color:var(--gold)}
.poc-qty-num{width:24px;text-align:center;font-size:13px;color:var(--cream);font-weight:600}
.poc-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-top:1px solid var(--border);font-size:13px;color:var(--text-muted);margin-top:4px}
.poc-row.grand{border-top:1px solid var(--gold)}
.poc-total,.poc-grand{color:var(--gold);font-weight:700}
.poc-grand{font-size:1rem}
.poc-decor-note{border-radius:6px;padding:8px 12px;font-size:12px;margin-top:8px}
.confirm-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.confirm-header{background:linear-gradient(135deg,rgba(201,169,110,.15),rgba(201,169,110,.05));border-bottom:1px solid var(--border);padding:20px 24px;display:flex;align-items:center;gap:14px}
.confirm-header i{font-size:2rem;color:var(--gold)}
.confirm-header h4{font-family:var(--font-display);font-size:1.5rem;color:var(--cream);margin:0}
.confirm-header p{font-size:12px;color:var(--text-muted);margin:2px 0 0}
.confirm-body{padding:24px}
.confirm-section{margin-bottom:20px}
.confirm-section-title{font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)}
.confirm-row{display:flex;justify-content:space-between;padding:5px 0;font-size:13px}
.confirm-row span:first-child{color:var(--text-muted)}
.confirm-row span:last-child{color:var(--cream);font-weight:500;text-align:right;max-width:60%}
.confirm-total{background:rgba(201,169,110,.06);border:1px solid rgba(201,169,110,.2);border-radius:var(--radius-sm);padding:16px;display:flex;justify-content:space-between;align-items:center}
.confirm-total span:first-child{color:var(--cream);font-weight:700;font-size:1rem}
.confirm-total-amt{color:var(--gold);font-size:1.4rem;font-weight:700}
.confirm-dish-list{list-style:none;padding:0;margin:0}
.confirm-dish-list li{display:flex;justify-content:space-between;font-size:13px;padding:5px 0;border-bottom:1px solid var(--border)}
.confirm-dish-list li:last-child{border-bottom:none}
.confirm-dish-list .cn{color:var(--text-muted)}
.confirm-dish-list .cp{color:var(--gold)}
.step-actions{display:flex;justify-content:space-between;align-items:center;margin-top:32px;padding-top:24px;border-top:1px solid var(--border)}
.reservation-success{text-align:center;padding:60px 20px;max-width:500px;margin:0 auto}
.success-icon-wrap{width:90px;height:90px;border-radius:50%;background:rgba(82,183,136,.1);border:2px solid var(--green);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:2.4rem;color:var(--green)}
.reservation-success h2{font-family:var(--font-display);font-size:2.4rem;color:var(--cream);margin-bottom:10px}
.success-code{color:var(--text-muted);margin-bottom:6px}
.success-code strong{color:var(--gold);font-size:1.1rem}
.success-note{color:var(--text-muted);font-size:14px;margin-bottom:30px}
.success-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.res-error-box{background:rgba(224,82,82,.08);border:1px solid rgba(224,82,82,.3);border-radius:var(--radius-sm);padding:14px 18px;margin-bottom:28px}
.res-error-box div{color:var(--red);font-size:13px;padding:2px 0}
.payment-option{display:flex;align-items:center;gap:10px;cursor:pointer;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 18px;transition:var(--transition);flex:1;min-width:140px}
.payment-option:has(input:checked){border-color:var(--gold);background:rgba(201,169,110,.05)}
.payment-option input{accent-color:var(--gold)}
.payment-option span{color:var(--text);font-size:13px;font-weight:500}
.payment-option i{color:var(--gold)}
</style>

<script>
const DECOR_FEE=100000,DECOR_THRESHOLD=1000000;
const FMT=v=>new Intl.NumberFormat('vi-VN').format(v)+'đ';
let currentStep=1,resType='table_only',preOrder={},selDecorId=0,selDecorName='';
let resDiscount=0; // giảm giá từ coupon

function goStep(n){
    document.querySelectorAll('.wizard-step').forEach(s=>s.classList.remove('active'));
    document.querySelector('.wizard-step[data-step="'+n+'"]').classList.add('active');
    document.querySelectorAll('.wp-step').forEach(s=>{
        const sn=parseInt(s.dataset.step);
        s.classList.remove('active','done');
        if(sn===n)s.classList.add('active');
        else if(sn<n)s.classList.add('done');
    });
    document.querySelectorAll('.wp-line').forEach((l,i)=>l.classList.toggle('done',i<n-1));
    currentStep=n;
    window.scrollTo({top:0,behavior:'smooth'});
    if(n===6)buildConfirm();
}

function selectType(t){
    resType=t;
    document.getElementById('resType').value=t;
    document.getElementById('cardTableOnly').classList.toggle('active',t==='table_only');
    document.getElementById('cardPreorder').classList.toggle('active',t==='preorder');
    [3,4].forEach(n=>{
        const el=document.querySelector('.wp-step[data-step="'+n+'"]');
        el.style.opacity=t==='preorder'?'1':'0.35';
    });
    document.getElementById('paymentSection').style.display=t==='preorder'?'block':'none';
    document.getElementById('step5Back').onclick=()=>goStep(t==='preorder'?4:2);
}

function changeGuests(d){
    const el=document.getElementById('numGuests');
    el.value=Math.min(50,Math.max(1,parseInt(el.value)+d));
}

function validateStep2(){
    if(!document.getElementById('resDate').value){alert('Vui lòng chọn ngày đặt bàn');return;}
    if(!document.getElementById('resTime').value){alert('Vui lòng chọn giờ đặt bàn');return;}
    if(!document.querySelector('input[name="position_id"]:checked')){alert('Vui lòng chọn vị trí bàn');return;}
    goStep(resType==='preorder'?3:5);
}

function validateStep4(){
    if(Object.keys(preOrder).length===0){
        alert('Bạn chưa chọn món nào.\nVui lòng thêm ít nhất 1 món hoặc quay lại chọn "Chỉ đặt bàn".');return;
    }
    goStep(5);
}

function validateStep5(){
    const n=document.querySelector('input[name="customer_name"]').value.trim();
    const p=document.querySelector('input[name="customer_phone"]').value.trim();
    if(!n){alert('Vui lòng nhập họ tên');return;}
    if(!/^[0-9]{9,11}$/.test(p)){alert('Số điện thoại không hợp lệ (9-11 chữ số)');return;}
    goStep(6);
}

function setPayment(v){document.getElementById('paymentMethodHidden').value=v;}

function showQRBox(show){
    const box=document.getElementById('qrTransferBox');
    box.style.display=show?'block':'none';
    if(show) updateQRInfo();
}

function updateQRInfo(){
    const phone=document.querySelector('input[name="customer_phone"]')?.value||'';
    const contentEl=document.getElementById('qrContent');
    if(contentEl) contentEl.textContent='DATBAN '+(phone||'[SĐT của bạn]');
    let ft=0;Object.values(preOrder).forEach(it=>ft+=it.price*it.qty);
    const df=(selDecorId>0)?(ft>=DECOR_THRESHOLD?0:DECOR_FEE):0;
    const grand=Math.max(0,ft-resDiscount)+df;
    const amtEl=document.getElementById('qrAmount');
    if(amtEl) amtEl.textContent=grand>0?FMT(grand):'Tính theo hóa đơn thực tế';
}

function copySTK(){
    navigator.clipboard.writeText('1234567890 12').then(()=>{
        const btn=document.getElementById('copyBtn');
        btn.innerHTML='<i class="bi bi-check2"></i> Đã copy';
        btn.style.color='var(--green)';
        btn.style.borderColor='rgba(82,183,136,.4)';
        setTimeout(()=>{btn.innerHTML='<i class="bi bi-copy"></i> Copy';btn.style.color='var(--gold)';btn.style.borderColor='rgba(201,169,110,.3)';},2000);
    });
}

function filterDishes(cat,btn){
    document.querySelectorAll('.dcf-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.odg-card').forEach(c=>c.classList.toggle('hidden',cat!=='all'&&parseInt(c.dataset.cat)!==cat));
}
function searchDishes(q){
    const low=q.toLowerCase();
    document.querySelectorAll('.odg-card').forEach(c=>c.classList.toggle('hidden',!c.dataset.name.includes(low)));
}

function addToPreOrder(id,name,price){
    if(preOrder[id])preOrder[id].qty++;
    else preOrder[id]={name,price,qty:1};
    renderPreOrder();
}
function changePreOrderQty(id,d){
    if(!preOrder[id])return;
    preOrder[id].qty+=d;
    if(preOrder[id].qty<=0)delete preOrder[id];
    renderPreOrder();
}

function renderPreOrder(){
    const iEl=document.getElementById('preorderItems'),fEl=document.getElementById('preorderFooter'),hEl=document.getElementById('hiddenPreorderInputs');
    const keys=Object.keys(preOrder);
    hEl.innerHTML='';
    let ft=0;
    keys.forEach((id,i)=>{
        const it=preOrder[id];ft+=it.price*it.qty;
        hEl.innerHTML+=`<input type="hidden" name="dish_id[]" value="${id}"><input type="hidden" name="dish_qty[]" value="${it.qty}">`;
    });
    if(!keys.length){
        iEl.innerHTML='<div class="poc-empty" id="pocEmpty"><i class="bi bi-bag-x"></i><p>Chưa có món nào</p></div>';
        fEl.style.display='none';
        return;
    }
    let html='';
    keys.forEach(id=>{
        const it=preOrder[id];
        html+=`<div class="poc-item">
            <div class="poc-item-name">${it.name}</div>
            <div class="poc-qty">
                <button type="button" class="poc-qty-btn" onclick="changePreOrderQty(${id},-1)"><i class="bi bi-dash"></i></button>
                <span class="poc-qty-num">${it.qty}</span>
                <button type="button" class="poc-qty-btn" onclick="changePreOrderQty(${id},1)"><i class="bi bi-plus"></i></button>
            </div>
            <div class="poc-item-sub">${FMT(it.price*it.qty)}</div>
        </div>`;
    });
    iEl.innerHTML=html;fEl.style.display='block';
    const df=(selDecorId>0)?(ft>=DECOR_THRESHOLD?0:DECOR_FEE):0;
    document.getElementById('pocFoodTotal').textContent=FMT(ft);
    document.getElementById('pocGrand').textContent=FMT(ft+df-resDiscount);
    // Cập nhật QR nếu đang hiện
    if(document.getElementById('qrTransferBox').style.display!=='none') updateQRInfo();
    const dr=document.getElementById('pocDecorRow'),dn=document.getElementById('pocDecorNote');
    if(selDecorId>0){
        dr.style.display='flex';
        document.getElementById('pocDecorLabel').textContent='Phí decor: '+selDecorName;
        document.getElementById('pocDecorAmt').innerHTML=df===0?'<span style="color:var(--green)">Miễn phí ✓</span>':FMT(DECOR_FEE);
        if(df===0){dn.style.cssText='display:block;background:rgba(82,183,136,.08);border:1px solid rgba(82,183,136,.2);color:var(--green)';dn.textContent='✓ Hóa đơn trên 1.000.000đ — Decor miễn phí!';}
        else{const need=DECOR_THRESHOLD-ft;dn.style.cssText='display:block;background:rgba(201,169,110,.06);border:1px solid rgba(201,169,110,.2);color:var(--text-muted)';dn.textContent='Thêm '+FMT(need)+' để được miễn phí decor';}
    }else{dr.style.display='none';dn.style.display='none';}
    updateDecorPrices(ft);
}

function updateDecorPrices(ft){
    document.querySelectorAll('[id^="dcPrice"]').forEach(el=>{
        if(ft>=DECOR_THRESHOLD){el.textContent='Miễn phí ✓';el.style.color='var(--green)';}
        else{el.textContent='100.000đ';el.style.color='var(--gold)';}
    });
}

document.querySelectorAll('input[name="decor_id"]').forEach(inp=>inp.addEventListener('change',function(){
    selDecorId=parseInt(this.value);
    const card=this.closest('.decor-card');
    selDecorName=card?card.querySelector('h5').textContent:'';
    renderPreOrder();
}));

function buildConfirm(){
    const name=document.querySelector('input[name="customer_name"]').value;
    const phone=document.querySelector('input[name="customer_phone"]').value;
    const email=document.querySelector('input[name="customer_email"]').value;
    const date=document.getElementById('resDate').value;
    const time=document.getElementById('resTime').value.substring(0,5);
    const guests=document.getElementById('numGuests').value;
    const posEl=document.querySelector('input[name="position_id"]:checked');
    const posName=posEl?posEl.closest('.pos-card').querySelector('h5').textContent:'—';
    const note=document.querySelector('textarea[name="note"]').value;
    let ft=0;Object.values(preOrder).forEach(it=>ft+=it.price*it.qty);
    const df=(selDecorId>0)?(ft>=DECOR_THRESHOLD?0:DECOR_FEE):0;
    const grand=Math.max(0,ft-resDiscount)+df;
    const pmEl=document.querySelector('input[name="payment_radio"]:checked');
    const payLabel=pmEl?.value==='cash'?'Tiền mặt khi đến':(pmEl?.value==='transfer'?'Chuyển khoản trước':'—');
    const decLabel=selDecorId>0?selDecorName+(df===0?' (Miễn phí)':' ('+FMT(DECOR_FEE)+')'):'Không có decor';
    const couponCode=document.getElementById('resCouponInput')?.value||'';
    let dishHTML='';
    if(resType==='preorder'&&Object.keys(preOrder).length>0){
        dishHTML='<ul class="confirm-dish-list">';
        Object.values(preOrder).forEach(it=>{dishHTML+=`<li><span class="cn">${it.name} × ${it.qty}</span><span class="cp">${FMT(it.price*it.qty)}</span></li>`;});
        dishHTML+='</ul>';
    }
    const fmtDate=d=>{if(!d)return'—';const[y,m,dd]=d.split('-');return`${dd}/${m}/${y}`};
    const typeLabel=resType==='table_only'?'🪑 Chỉ đặt bàn':'🍽️ Đặt bàn &amp; Đặt món trước';
    document.getElementById('confirmSummary').innerHTML=`
    <div class="confirm-header">
        <i class="bi bi-calendar2-check-fill"></i>
        <div><h4>Thông tin đặt bàn</h4><p>${typeLabel}</p></div>
    </div>
    <div class="confirm-body">
        <div class="confirm-section">
            <div class="confirm-section-title">Thời gian &amp; Vị trí</div>
            <div class="confirm-row"><span>Ngày</span><span>${fmtDate(date)}</span></div>
            <div class="confirm-row"><span>Giờ</span><span>${time}</span></div>
            <div class="confirm-row"><span>Số khách</span><span>${guests} khách</span></div>
            <div class="confirm-row"><span>Vị trí bàn</span><span>${posName}</span></div>
        </div>
        <div class="confirm-section">
            <div class="confirm-section-title">Thông tin liên hệ</div>
            <div class="confirm-row"><span>Họ tên</span><span>${name}</span></div>
            <div class="confirm-row"><span>Điện thoại</span><span>${phone}</span></div>
            ${email?`<div class="confirm-row"><span>Email</span><span>${email}</span></div>`:''}
            ${note?`<div class="confirm-row"><span>Ghi chú</span><span>${note}</span></div>`:''}
        </div>
        ${resType==='preorder'?`
        <div class="confirm-section">
            <div class="confirm-section-title">Món đặt trước</div>
            ${dishHTML||'<p style="color:var(--text-muted);font-size:13px">Chưa có món</p>'}
        </div>
        <div class="confirm-section">
            <div class="confirm-section-title">Trang trí &amp; Thanh toán</div>
            <div class="confirm-row"><span>Decor</span><span>${decLabel}</span></div>
            ${resDiscount>0?`<div class="confirm-row"><span style="color:var(--green)"><i class="bi bi-tag-fill"></i> Mã giảm (${couponCode})</span><span style="color:var(--green)">-${FMT(resDiscount)}</span></div>`:''}
            <div class="confirm-row"><span>Thanh toán</span><span>${payLabel}</span></div>
        </div>
        <div class="confirm-total"><span>Tổng hóa đơn</span><span class="confirm-total-amt">${grand>0?FMT(grand):'Thanh toán tại bàn'}</span></div>`
        :`<div class="confirm-total"><span>Chi phí đặt bàn</span><span class="confirm-total-amt" style="color:var(--green)">Miễn phí ✓</span></div>`}
    </div>`;
}

// ── Coupon ──────────────────────────────────────────────────
function applyResCoupon(){
    const code = document.getElementById('resCouponInput').value.trim();
    const btn  = document.getElementById('resCouponBtn');
    if(!code){ showResCouponMsg('Vui lòng nhập mã giảm giá','warn'); return; }
    let ft=0; Object.values(preOrder).forEach(it=>ft+=it.price*it.qty);
    if(ft===0){ showResCouponMsg('Vui lòng thêm món trước khi áp mã','warn'); return; }

    btn.innerHTML='<i class="bi bi-hourglass-split"></i>';
    btn.disabled=true;

    fetch(`<?= SITE_URL ?>/coupon-check.php?code=${encodeURIComponent(code)}&amount=${ft}`)
        .then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(data=>{
            btn.disabled=false; btn.innerHTML='Áp dụng';
            if(data.ok){
                resDiscount=data.discount;
                const note = data.remaining!==null ? ' · Còn '+data.remaining+' lượt' : '';
                showResCouponMsg('✓ '+data.msg+note, 'ok');
                document.getElementById('pocDiscountRow').style.display='flex';
                document.getElementById('pocDiscountAmt').textContent='-'+FMT(data.discount);
                renderPreOrder();
            } else {
                resDiscount=0;
                showResCouponMsg(data.msg,'err');
                clearResDiscount();
            }
        })
        .catch(()=>{ btn.disabled=false; btn.innerHTML='Áp dụng'; showResCouponMsg('Lỗi kết nối, thử lại','err'); });
}

function showResCouponMsg(msg, type){
    const el=document.getElementById('resCouponMsg');
    el.style.display='flex';
    el.style.alignItems='center';
    el.style.gap='6px';
    el.style.padding='7px 10px';
    el.style.borderRadius='6px';
    el.style.fontSize='11px';
    el.style.fontWeight='500';
    if(type==='ok'){
        el.style.background='rgba(82,183,136,.1)';el.style.border='1px solid rgba(82,183,136,.25)';el.style.color='var(--green)';
    } else if(type==='err'){
        el.style.background='rgba(224,82,82,.1)';el.style.border='1px solid rgba(224,82,82,.25)';el.style.color='var(--red)';
    } else {
        el.style.background='rgba(201,169,110,.1)';el.style.border='1px solid rgba(201,169,110,.25)';el.style.color='var(--gold)';
    }
    el.textContent=msg;
}

function clearResDiscount(){
    resDiscount=0;
    document.getElementById('pocDiscountRow').style.display='none';
    renderPreOrder();
}

document.getElementById('resCouponInput')?.addEventListener('input',function(){
    document.getElementById('resCouponMsg').style.display='none';
    clearResDiscount();
});
document.getElementById('resCouponInput')?.addEventListener('keydown',function(e){
    if(e.key==='Enter'){e.preventDefault();applyResCoupon();}
});

// Init
selectType('table_only');
<?php if(!empty($errors)&&!empty($_POST)):?>goStep(5);<?php endif;?>

document.getElementById('resForm').addEventListener('submit',function(){
    const btn=document.getElementById('submitBtn');
    btn.disabled=true;btn.innerHTML='<i class="bi bi-hourglass-split"></i> Đang gửi...';
    const pmEl=document.querySelector('input[name="payment_radio"]:checked');
    if(pmEl)document.getElementById('paymentMethodHidden').value=pmEl.value;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>