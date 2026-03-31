<?php
$pageTitle = 'Giỏ hàng';
require_once __DIR__ . '/includes/header.php';
$cart = getCart();
$total = getCartTotal();
?>

<div class="page-hero">
    <span class="page-hero-label">Saigon Bistro</span>
    <h1>Giỏ <em>hàng</em></h1>
    <div class="breadcrumb-nav"><a href="<?= SITE_URL ?>/">Trang chủ</a> <span>›</span> <span class="current">Giỏ hàng</span></div>
</div>

<section class="form-section">
    <div class="container">
        <?php if (empty($cart)): ?>
        <div class="empty-state" data-aos="fadeIn">
            <i class="bi bi-bag-x"></i>
            <h5>Giỏ hàng của bạn đang trống</h5>
            <p>Hãy thêm món ăn vào giỏ hàng để tiếp tục đặt</p>
            <a href="<?= SITE_URL ?>/menu.php" class="btn-gold mt-3" style="display:inline-block;">Xem thực đơn</a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="form-card">
                    <div class="form-card-title">
                        <i class="bi bi-bag" style="color:var(--gold)"></i>
                        Giỏ hàng (<?= getCartCount() ?> món)
                    </div>
                    <div class="table-responsive">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Món ăn</th>
                                    <th>Đơn giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $item): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:12px;">
                                            <img src="<?= $item['img'] ? getDishImage($item['img']) : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=80&h=80&fit=crop' ?>"
                                                class="cart-item-img" alt="<?= clean($item['name']) ?>">
                                            <span class="cart-item-name"><?= clean($item['name']) ?></span>
                                        </div>
                                    </td>
                                    <td style="color:var(--text-muted)"><?= formatPrice($item['price']) ?></td>
                                    <td>
                                        <div class="qty-control">
                                            <button class="qty-btn" data-id="<?= $item['id'] ?>" data-action="decrease">−</button>
                                            <span class="qty-num"><?= $item['quantity'] ?></span>
                                            <button class="qty-btn" data-id="<?= $item['id'] ?>" data-action="increase">+</button>
                                        </div>
                                    </td>
                                    <td><span class="item-total"><?= formatPrice($item['price'] * $item['quantity']) ?></span></td>
                                    <td>
                                        <button class="remove-item" data-id="<?= $item['id'] ?>" title="Xóa">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                        <a href="<?= SITE_URL ?>/menu.php" style="color:var(--text-muted);font-size:13px;">
                            <i class="bi bi-arrow-left"></i> Tiếp tục mua sắm
                        </a>
                        <button class="btn-danger-custom" onclick="clearCart()">
                            <i class="bi bi-trash3"></i> Xóa tất cả
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <div class="form-card-title">Tóm tắt đơn hàng</div>
                    <div class="summary-row">
                        <span>Tạm tính (<?= getCartCount() ?> món)</span>
                        <span><?= formatPrice($total) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Phí giao hàng</span>
                        <span style="color:var(--green)">Miễn phí</span>
                    </div>
                    <div class="summary-row" style="border-bottom:none;padding-top:16px;">
                        <span style="color:var(--cream);font-weight:600;">Tổng cộng</span>
                        <span class="summary-total"><?= formatPrice($total) ?></span>
                    </div>
                    <a href="<?= SITE_URL ?>/checkout.php" class="btn-gold w-100 text-center mt-4" style="display:block;padding:14px;">
                        <i class="bi bi-credit-card"></i> Tiến hành đặt hàng
                    </a>
                    <div style="margin-top:16px;text-align:center;">
                        <small style="color:var(--text-muted);font-size:12px;">
                            <i class="bi bi-shield-check" style="color:var(--gold)"></i>
                            Thanh toán an toàn & bảo mật
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
var SITE_URL = '<?= SITE_URL ?>';
function clearCart() {
    if (!confirm('Xóa toàn bộ giỏ hàng?')) return;
    $.post(SITE_URL + '/cart-action.php', {action: 'clear'}, function() {
        location.reload();
    }, 'json');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
