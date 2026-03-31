<?php
ob_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store');
header('X-Content-Type-Options: nosniff');

$code = strtoupper(trim($_GET['code'] ?? ''));
$amt  = (float)($_GET['amount'] ?? 0);

function respond($ok, $msg, $extra = []) {
    echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $extra));
    exit;
}

if (!$code) respond(false, 'Vui lòng nhập mã giảm giá');
if ($amt <= 0) respond(false, 'Không có giá trị đơn hàng để áp dụng');

$stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
$stmt->execute([$code]);
$c = $stmt->fetch();

if (!$c) {
    respond(false, 'Mã giảm giá không tồn tại hoặc đã bị vô hiệu hóa');
}

if (!empty($c['expires_at'])) {
    $expTs = strtotime($c['expires_at']);
    if ($expTs !== false && $expTs < strtotime('today')) {
        $expDate = date('d/m/Y', $expTs);
        respond(false, 'Mã đã hết hạn sử dụng (hết hạn ngày ' . $expDate . ')');
    }
}

if ($c['max_uses'] !== null && (int)$c['used_count'] >= (int)$c['max_uses']) {
    respond(false, 'Mã đã hết lượt sử dụng (' . $c['used_count'] . '/' . $c['max_uses'] . ' lượt)');
}

if ((float)$amt < (float)$c['min_order']) {
    $need = $c['min_order'] - $amt;
    respond(false, 'Đơn hàng tối thiểu ' . number_format($c['min_order'], 0, ',', '.') . 'đ (cần thêm ' . number_format($need, 0, ',', '.') . 'đ nữa)');
}

if ($c['type'] === 'percent') {
    $discount = (int)round($amt * (float)$c['value'] / 100);
    $label    = 'Giảm ' . $c['value'] . '%';
} else {
    $discount = (int)min((float)$c['value'], $amt);
    $label    = 'Giảm ' . number_format($c['value'], 0, ',', '.') . 'đ';
}

$remaining = ($c['max_uses'] !== null)
    ? max(0, (int)$c['max_uses'] - (int)$c['used_count'] - 1)
    : null;

respond(true, $label, [
    'discount'  => $discount,
    'type'      => $c['type'],
    'value'     => (float)$c['value'],
    'remaining' => $remaining,
]);