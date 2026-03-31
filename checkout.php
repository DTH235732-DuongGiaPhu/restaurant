<?php
$pageTitle = 'Đặt hàng';
require_once __DIR__ . '/includes/header.php';

$cart = getCart();
if (empty($cart)) {
    setFlash('error', 'Giỏ hàng trống! Vui lòng chọn món trước.');
    header('Location: ' . SITE_URL . '/menu.php');
    exit;
}

$subtotal   = getCartTotal();
$discount   = 0;
$couponCode = '';
$couponMsg  = '';
$couponData = null;
$total      = $subtotal;
$errors     = [];
$success    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = clean($_POST['name'] ?? '');
    $phone      = clean($_POST['phone'] ?? '');
    $address    = clean($_POST['address'] ?? '');
    $note       = clean($_POST['note'] ?? '');
    $payment    = in_array($_POST['payment'] ?? '', ['cash','transfer']) ? $_POST['payment'] : 'cash';
    $couponCode = strtoupper(clean($_POST['coupon_code'] ?? ''));

    if (!$name)    $errors[] = 'Vui lòng nhập họ tên';
    if (!$phone)   $errors[] = 'Vui lòng nhập số điện thoại';
    if (!$address) $errors[] = 'Vui lòng nhập địa chỉ giao hàng';
    if (!preg_match('/^[0-9]{9,11}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ';

    // Xử lý coupon khi submit
    if ($couponCode) {
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code=? AND is_active=1 AND (expires_at IS NULL OR expires_at >= CURDATE()) AND (max_uses IS NULL OR used_count < max_uses)");
        $stmt->execute([$couponCode]);
        $couponData = $stmt->fetch();
        if (!$couponData) {
            $errors[] = 'Mã giảm giá không hợp lệ hoặc đã hết hạn';
        } elseif ($subtotal < $couponData['min_order']) {
            $errors[] = 'Đơn hàng tối thiểu ' . formatPrice($couponData['min_order']) . ' để dùng mã này';
            $couponData = null;
        } else {
            $discount = $couponData['type']==='percent' ? round($subtotal * $couponData['value'] / 100) : min($couponData['value'], $subtotal);
            $total    = $subtotal - $discount;
        }
    }

    if (empty($errors)) {
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, note, total, payment_method) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$userId, $name, $phone, $address, $note, $total, $payment]);
        $orderId = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, dish_id, dish_name, dish_price, quantity, subtotal) VALUES (?,?,?,?,?,?)");
        foreach ($cart as $item) {
            $stmtItem->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['quantity'], $item['price'] * $item['quantity']]);
        }

        // Tăng used_count coupon
        if ($couponData) {
            $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id=?")->execute([$couponData['id']]);
        }

        $_SESSION['cart'] = [];
        $success = true;
        $successOrderId = $orderId;
    }
}
?>

<div class="page-hero">
    <span class="page-hero-label">Saigon Bistro</span>
    <h1>Xác nhận <em>đặt hàng</em></h1>
    <div class="breadcrumb-nav"><a href="<?= SITE_URL ?>/">Trang chủ</a> <span>›</span> <a href="<?= SITE_URL ?>/cart.php">Giỏ hàng</a> <span>›</span> <span class="current">Đặt hàng</span></div>
</div>

<section class="form-section">
    <div class="container">
        <?php if ($success): ?>
        <div style="text-align:center;padding:60px 20px;" data-aos="zoomIn">
            <div style="width:80px;height:80px;border-radius:50%;background:rgba(82,183,136,0.1);border:2px solid var(--green);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem;color:var(--green);">
                <i class="bi bi-check-lg"></i>
            </div>
            <h2 style="font-family:var(--font-display);color:var(--cream);font-size:2.2rem;margin-bottom:10px;">Đặt hàng thành công!</h2>
            <p style="color:var(--text-muted);margin-bottom:6px;">Mã đơn hàng: <strong style="color:var(--gold);">#<?= str_pad($successOrderId, 5, '0', STR_PAD_LEFT) ?></strong></p>
            <p style="color:var(--text-muted);margin-bottom:30px;">Chúng tôi sẽ liên hệ xác nhận đơn hàng trong vòng 5-10 phút.</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="<?= SITE_URL ?>/orders.php" class="btn-gold">Xem đơn hàng</a>
                <a href="<?= SITE_URL ?>/menu.php" class="btn-outline-gold">Đặt thêm</a>
            </div>
        </div>
        <?php else: ?>

        <?php if (!empty($errors)): ?>
        <div style="background:rgba(224,82,82,0.1);border:1px solid rgba(224,82,82,0.3);border-radius:var(--radius-sm);padding:16px;margin-bottom:24px;">
            <?php foreach ($errors as $e): ?>
            <div style="color:var(--red);font-size:13px;"><i class="bi bi-exclamation-circle"></i> <?= $e ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
        <div class="row g-4">
            <!-- Info form -->
            <div class="col-lg-7">
                <div class="form-card">
                    <div class="form-card-title"><i class="bi bi-person-fill" style="color:var(--gold)"></i> Thông tin giao hàng</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Họ và tên *</label>
                                <input type="text" name="name" class="form-control-custom"
                                    value="<?= isset($_POST['name']) ? clean($_POST['name']) : (isLoggedIn() ? clean($_SESSION['user_name']) : '') ?>"
                                    placeholder="Nguyễn Văn A" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Số điện thoại *</label>
                                <input type="tel" name="phone" class="form-control-custom"
                                    value="<?= isset($_POST['phone']) ? clean($_POST['phone']) : '' ?>"
                                    placeholder="0901234567" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Địa chỉ giao hàng *</label>
                        <input type="text" name="address" class="form-control-custom"
                            value="<?= isset($_POST['address']) ? clean($_POST['address']) : '' ?>"
                            placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ghi chú đơn hàng</label>
                        <textarea name="note" class="form-control-custom" placeholder="Yêu cầu đặc biệt, thời gian giao hàng..."><?= isset($_POST['note']) ? clean($_POST['note']) : '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phương thức thanh toán</label>
                        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                            <label class="payment-option">
                                <input type="radio" name="payment" value="cash" id="paymentCash" <?= (!isset($_POST['payment']) || $_POST['payment'] === 'cash') ? 'checked' : '' ?>>
                                <span><i class="bi bi-cash-coin"></i> Tiền mặt khi nhận</span>
                            </label>
                            <label class="payment-option" onclick="showQR()">
                                <input type="radio" name="payment" value="transfer" id="paymentTransfer" <?= (isset($_POST['payment']) && $_POST['payment'] === 'transfer') ? 'checked' : '' ?>>
                                <span><i class="bi bi-qr-code"></i> Chuyển khoản QR</span>
                            </label>
                        </div>

                        <!-- QR Info Box (hiện khi chọn chuyển khoản) -->
                        <div id="qrInfoBox" style="display:none;margin-top:16px;background:rgba(201,169,110,0.06);border:1px solid rgba(201,169,110,0.25);border-radius:var(--radius-sm);padding:14px 16px;">
                            <div style="color:var(--gold);font-size:12px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:10px;">
                                <i class="bi bi-qr-code-scan"></i> Thông tin chuyển khoản
                            </div>
                            <div style="font-size:13px;color:var(--text-muted);line-height:2;">
                                <div>🏦 <span style="color:var(--cream)">Ngân hàng:</span> <strong style="color:var(--gold)">Vietcombank</strong></div>
                                <div>💳 <span style="color:var(--cream)">Số tài khoản:</span> <strong style="color:var(--gold)">1234 5678 9012</strong></div>
                                <div>👤 <span style="color:var(--cream)">Chủ tài khoản:</span> <strong style="color:var(--gold)">SAIGON BISTRO</strong></div>
                                <div>💰 <span style="color:var(--cream)">Số tiền:</span> <strong style="color:var(--gold)"><?= formatPrice($total) ?></strong></div>
                                <div>📝 <span style="color:var(--cream)">Nội dung:</span> <strong style="color:var(--gold)">THANHTOAN [SĐT của bạn]</strong></div>
                            </div>
                            <button type="button" onclick="document.getElementById('qrModal').style.display='flex'" style="margin-top:12px;background:var(--gold);color:#000;border:none;border-radius:6px;padding:9px 18px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;">
                                <i class="bi bi-qr-code"></i> Xem mã QR
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order summary -->
            <div class="col-lg-5">
                <div class="cart-summary">
                    <div class="form-card-title">Đơn hàng của bạn</div>
                    <?php foreach ($cart as $item): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);">
                        <div>
                            <div style="color:var(--cream);font-size:13px;font-weight:500;"><?= clean($item['name']) ?></div>
                            <div style="color:var(--text-muted);font-size:12px;">x<?= $item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
                        </div>
                        <span style="color:var(--gold);font-weight:600;"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <!-- Coupon input -->
                    <div style="margin:14px 0;padding:14px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
                        <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;font-weight:600;letter-spacing:.05em;text-transform:uppercase"><i class="bi bi-ticket-perforated" style="color:var(--gold)"></i> Mã giảm giá</div>
                        <div style="display:flex;gap:8px">
                            <input type="text" id="couponInput" name="coupon_code" placeholder="Nhập mã giảm giá" value="<?= clean($couponCode) ?>"
                                style="flex:1;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px;color:var(--cream);font-size:13px;text-transform:uppercase;font-family:var(--font-body)"
                                oninput="this.value=this.value.toUpperCase()">
                            <button type="button" onclick="applyCoupon()" id="couponBtn"
                                style="background:rgba(201,169,110,.15);border:1px solid rgba(201,169,110,.3);border-radius:var(--radius-sm);padding:9px 14px;color:var(--gold);font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;transition:var(--transition)">
                                Áp dụng
                            </button>
                        </div>
                        <div id="couponMsg" style="margin-top:8px;font-size:12px;display:none"></div>
                    </div>

                    <div class="summary-row" style="margin-top:4px;">
                        <span>Tạm tính</span>
                        <span style="color:var(--cream)"><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div class="summary-row" id="discountRow" style="<?= $discount > 0 ? '' : 'display:none' ?>">
                        <span style="color:var(--green)"><i class="bi bi-tag-fill"></i> Giảm giá</span>
                        <span style="color:var(--green);font-weight:600" id="discountAmt">-<?= $discount > 0 ? formatPrice($discount) : '0đ' ?></span>
                    </div>
                    <div class="summary-row" style="margin-top:4px;">
                        <span>Phí giao hàng</span>
                        <span style="color:var(--green)">Miễn phí</span>
                    </div>
                    <div class="summary-row" style="border-bottom:none;">
                        <span style="color:var(--cream);font-weight:700;font-size:1rem;">Tổng cộng</span>
                        <span class="summary-total" id="grandTotal"><?= formatPrice($total) ?></span>
                    </div>
                    <button type="submit" class="btn-gold w-100 mt-4" style="width:100%;padding:14px;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <i class="bi bi-bag-check"></i> Xác nhận đặt hàng
                    </button>
                </div>
            </div>
        </div>
        </form>
        <?php endif; ?>
    </div>
</section>

<!-- QR MODAL -->
<div id="qrModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(6px);">
    <div style="background:var(--bg-card);border:1px solid var(--gold);border-radius:var(--radius);padding:32px;max-width:380px;width:90%;text-align:center;position:relative;animation:modalIn 0.3s ease;">

        <!-- Close button -->
        <button onclick="document.getElementById('qrModal').style.display='none'"
            style="position:absolute;top:14px;right:16px;background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1;">×</button>

        <!-- Header -->
        <div style="margin-bottom:20px;">
            <div style="color:var(--gold);font-size:1rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:4px;">
                <i class="bi bi-qr-code-scan"></i> Quét mã QR để thanh toán
            </div>
            <div style="color:var(--text-muted);font-size:12px;">Dùng app ngân hàng quét mã bên dưới</div>
        </div>

        <!-- QR Code (tạo bằng API miễn phí) -->
        <div style="background:#fff;border-radius:12px;padding:16px;display:inline-block;margin-bottom:20px;box-shadow:0 4px 20px rgba(201,169,110,0.3);">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=00020101021238570010A000000727012700069704220113FT20241234567890208QRIBFTTA5303704540<?= $total ?>5802VN5912SAIGON BISTRO6008HO CHI M62270823THANHTOAN<?= date('YmdHis') ?>6304ABCD"
                width="200" height="200" alt="QR thanh toán"
                style="display:block;"
                onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=SAIGONBISTRO-THANHTOAN-<?= $total ?>'">
        </div>

        <!-- Bank info -->
        <div style="background:rgba(201,169,110,0.06);border:1px solid rgba(201,169,110,0.2);border-radius:8px;padding:14px;text-align:left;font-size:13px;line-height:2;">
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted);">Ngân hàng</span>
                <strong style="color:var(--cream);">Vietcombank</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted);">Số tài khoản</span>
                <strong style="color:var(--gold);">1234 5678 9012</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted);">Chủ tài khoản</span>
                <strong style="color:var(--cream);">SAIGON BISTRO</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:8px;margin-top:6px;">
                <span style="color:var(--text-muted);">Số tiền</span>
                <strong style="color:var(--gold);font-size:1.1rem;"><?= formatPrice($total) ?></strong>
            </div>
        </div>

        <!-- Note -->
        <div style="margin-top:14px;font-size:12px;color:var(--text-muted);line-height:1.6;">
            <i class="bi bi-info-circle" style="color:var(--gold)"></i>
            Nội dung chuyển khoản: <strong style="color:var(--cream);">THANHTOAN [SĐT]</strong><br>
            Đơn hàng xác nhận trong vòng <strong style="color:var(--gold);">5–10 phút</strong> sau khi nhận tiền
        </div>

        <button onclick="document.getElementById('qrModal').style.display='none'"
            style="margin-top:18px;background:var(--gold);color:#000;border:none;border-radius:6px;padding:11px 28px;font-size:13px;font-weight:700;cursor:pointer;width:100%;">
            Đã chuyển khoản xong ✓
        </button>
    </div>
</div>

<style>
.payment-option {
    display:flex; align-items:center; gap:10px; cursor:pointer;
    background:var(--bg-secondary); border:1px solid var(--border);
    border-radius:var(--radius-sm); padding:12px 18px;
    transition:var(--transition); flex:1; min-width:140px;
}
.payment-option:has(input:checked) { border-color:var(--gold); background:rgba(201,169,110,0.05); }
.payment-option input { accent-color:var(--gold); }
.payment-option span { color:var(--text); font-size:13px; font-weight:500; }
.payment-option i { color:var(--gold); }
@keyframes modalIn { from { transform:scale(0.92);opacity:0; } to { transform:scale(1);opacity:1; } }
</style>

<script>
var SITE_URL = '<?= SITE_URL ?>';
var SUBTOTAL = <?= $subtotal ?>;
var FMT = v => new Intl.NumberFormat('vi-VN').format(v) + 'đ';

function showQR() {
    document.getElementById('paymentTransfer').checked = true;
    document.getElementById('qrInfoBox').style.display = 'block';
}

document.getElementById('paymentCash').addEventListener('change', function() {
    document.getElementById('qrInfoBox').style.display = 'none';
});

window.addEventListener('load', function() {
    if (document.getElementById('paymentTransfer').checked) {
        document.getElementById('qrInfoBox').style.display = 'block';
    }
    <?php if ($discount > 0): ?>
    showDiscountRow(<?= $discount ?>);
    <?php endif; ?>
});

document.getElementById('qrModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

// ── Coupon ──────────────────────────────────────────────────
function applyCoupon() {
    const code  = document.getElementById('couponInput').value.trim();
    const msgEl = document.getElementById('couponMsg');
    const btn   = document.getElementById('couponBtn');

    if (!code) { showCouponMsg('Vui lòng nhập mã giảm giá', 'warn'); return; }

    btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    btn.disabled  = true;

    fetch(`${SITE_URL}/coupon-check.php?code=${encodeURIComponent(code)}&amount=${SUBTOTAL}`)
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            btn.disabled  = false;
            btn.innerHTML = 'Áp dụng';
            if (data.ok) {
                showCouponMsg('✓ ' + data.msg + (data.remaining !== null ? ' · Còn ' + data.remaining + ' lượt' : ''), 'ok');
                showDiscountRow(data.discount);
            } else {
                showCouponMsg(data.msg, 'err');
                clearDiscount();
            }
        })
        .catch(err => {
            btn.disabled  = false;
            btn.innerHTML = 'Áp dụng';
            showCouponMsg('Lỗi kết nối, vui lòng thử lại', 'err');
        });
}

function showCouponMsg(msg, type) {
    const el = document.getElementById('couponMsg');
    el.style.display = 'flex';
    el.style.alignItems = 'center';
    el.style.gap = '6px';
    el.style.padding = '8px 10px';
    el.style.borderRadius = '6px';
    el.style.fontSize = '12px';
    el.style.fontWeight = '500';
    if (type === 'ok') {
        el.style.background = 'rgba(82,183,136,.1)';
        el.style.border = '1px solid rgba(82,183,136,.25)';
        el.style.color = 'var(--green)';
    } else if (type === 'err') {
        el.style.background = 'rgba(224,82,82,.1)';
        el.style.border = '1px solid rgba(224,82,82,.25)';
        el.style.color = 'var(--red)';
    } else {
        el.style.background = 'rgba(201,169,110,.1)';
        el.style.border = '1px solid rgba(201,169,110,.25)';
        el.style.color = 'var(--gold)';
    }
    el.textContent = msg;
}

function showDiscountRow(disc) {
    document.getElementById('discountRow').style.display  = 'flex';
    document.getElementById('discountAmt').textContent    = '-' + FMT(disc);
    document.getElementById('grandTotal').textContent     = FMT(Math.max(0, SUBTOTAL - disc));
}

function clearDiscount() {
    document.getElementById('discountRow').style.display = 'none';
    document.getElementById('grandTotal').textContent    = FMT(SUBTOTAL);
}

document.getElementById('couponInput').addEventListener('input', function() {
    document.getElementById('couponMsg').style.display = 'none';
    clearDiscount();
});
document.getElementById('couponInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>