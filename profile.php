<?php
$pageTitle = 'Tài khoản của tôi';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Lấy thông tin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$errors   = [];
$success  = '';
$tab      = $_GET['tab'] ?? 'info';

// ── XỬ LÝ ĐỔI MẬT KHẨU ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPw = $_POST['current_password'] ?? '';
    $newPw     = $_POST['new_password'] ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';

    if (!$currentPw) {
        $errors[] = 'Vui lòng nhập mật khẩu hiện tại.';
    } elseif (!password_verify($currentPw, $user['password'])) {
        $errors[] = 'Mật khẩu hiện tại không đúng.';
    }

    if (!$newPw) {
        $errors[] = 'Vui lòng nhập mật khẩu mới.';
    } elseif (strlen($newPw) < 6) {
        $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } elseif ($newPw !== $confirmPw) {
        $errors[] = 'Xác nhận mật khẩu không khớp.';
    } elseif ($newPw === $currentPw) {
        $errors[] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
    }

    if (empty($errors)) {
        $hash = password_hash($newPw, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $userId]);
        $success = 'Đổi mật khẩu thành công! Vui lòng dùng mật khẩu mới cho lần đăng nhập tiếp theo.';
    }
    $tab = 'password';
}

// ── CẬP NHẬT THÔNG TIN ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $name  = clean($_POST['name'] ?? '');
    $phone = clean($_POST['phone'] ?? '');

    if (!$name) $errors[] = 'Vui lòng nhập họ tên.';
    if ($phone && !preg_match('/^[0-9]{9,11}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE users SET name=?, phone=? WHERE id=?")->execute([$name, $phone, $userId]);
        $_SESSION['user_name'] = $name;
        // Reload user data
        $stmt->execute([$userId]);
        $user    = $stmt->fetch();
        $success = 'Cập nhật thông tin thành công!';
    }
    $tab = 'info';
}

// Lịch sử đơn hàng
$ordersStmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id ORDER BY o.created_at DESC LIMIT 10
");
$ordersStmt->execute([$userId]);
$recentOrders = $ordersStmt->fetchAll();

$statusMap = [
    'pending'    => ['label' => 'Chờ xác nhận', 'color' => '#c9a96e'],
    'confirmed'  => ['label' => 'Đã xác nhận',  'color' => '#64a0ff'],
    'cooking'    => ['label' => 'Đang nấu',      'color' => '#f4a261'],
    'delivering' => ['label' => 'Đang giao',     'color' => '#52b788'],
    'done'       => ['label' => 'Hoàn thành',    'color' => '#2d9e5f'],
    'cancelled'  => ['label' => 'Đã huỷ',        'color' => '#e05252'],
];
?>

<div class="page-hero">
    <span class="page-hero-label">Saigon Bistro</span>
    <h1>Tài khoản <em>của tôi</em></h1>
    <div class="breadcrumb-nav">
        <a href="<?= SITE_URL ?>/">Trang chủ</a> <span>›</span>
        <span class="current">Tài khoản</span>
    </div>
</div>

<section class="form-section">
    <div class="container">
        <div class="row g-4">

            <!-- Sidebar tài khoản -->
            <div class="col-lg-3">
                <div class="form-card" style="padding:0;overflow:hidden;">
                    <!-- Avatar chữ cái -->
                    <div style="background:linear-gradient(135deg,rgba(201,169,110,0.2),rgba(201,169,110,0.05));padding:28px;text-align:center;border-bottom:1px solid var(--border);">
                        <div style="width:72px;height:72px;border-radius:50%;background:rgba(201,169,110,0.15);border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-family:var(--font-display);font-size:2rem;color:var(--gold);">
                            <?= strtoupper(mb_substr($user['name'], 0, 1, 'UTF-8')) ?>
                        </div>
                        <div style="color:var(--cream);font-weight:600;font-size:1rem;"><?= clean($user['name']) ?></div>
                        <div style="color:var(--text-muted);font-size:12px;margin-top:4px;"><?= clean($user['email']) ?></div>
                        <div style="margin-top:10px;">
                            <span style="background:rgba(82,183,136,0.1);color:var(--green);border:1px solid rgba(82,183,136,0.3);border-radius:20px;padding:4px 14px;font-size:11px;font-weight:600;">
                                ✓ Thành viên
                            </span>
                        </div>
                    </div>

                    <!-- Menu -->
                    <nav style="padding:8px;">
                        <a href="?tab=info" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:8px;font-size:13px;color:<?= $tab==='info' ? 'var(--gold)' : 'var(--text-muted)' ?>;background:<?= $tab==='info' ? 'rgba(201,169,110,0.08)' : 'transparent' ?>;transition:all .2s;">
                            <i class="bi bi-person"></i> Thông tin cá nhân
                        </a>
                        <a href="?tab=password" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:8px;font-size:13px;color:<?= $tab==='password' ? 'var(--gold)' : 'var(--text-muted)' ?>;background:<?= $tab==='password' ? 'rgba(201,169,110,0.08)' : 'transparent' ?>;transition:all .2s;">
                            <i class="bi bi-shield-lock"></i> Đổi mật khẩu
                        </a>
                        <a href="?tab=orders" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:8px;font-size:13px;color:<?= $tab==='orders' ? 'var(--gold)' : 'var(--text-muted)' ?>;background:<?= $tab==='orders' ? 'rgba(201,169,110,0.08)' : 'transparent' ?>;transition:all .2s;">
                            <i class="bi bi-bag"></i> Đơn hàng của tôi
                        </a>
                        <div style="border-top:1px solid var(--border);margin:8px 0;"></div>
                        <a href="<?= SITE_URL ?>/logout.php" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:8px;font-size:13px;color:var(--red);transition:all .2s;">
                            <i class="bi bi-box-arrow-left"></i> Đăng xuất
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Nội dung chính -->
            <div class="col-lg-9">

                <?php if (!empty($errors)): ?>
                <div style="background:rgba(224,82,82,0.1);border:1px solid rgba(224,82,82,0.3);border-radius:var(--radius-sm);padding:16px;margin-bottom:20px;">
                    <?php foreach ($errors as $e): ?>
                    <div style="color:var(--red);font-size:13px;"><i class="bi bi-exclamation-circle"></i> <?= $e ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div style="background:rgba(82,183,136,0.1);border:1px solid rgba(82,183,136,0.3);border-radius:var(--radius-sm);padding:16px;margin-bottom:20px;color:var(--green);">
                    <i class="bi bi-check-circle-fill"></i> <?= $success ?>
                </div>
                <?php endif; ?>

                <!-- TAB: Thông tin cá nhân -->
                <?php if ($tab === 'info'): ?>
                <div class="form-card">
                    <div class="form-card-title"><i class="bi bi-person-fill" style="color:var(--gold)"></i> Thông tin cá nhân</div>
                    <form method="POST">
                        <input type="hidden" name="update_info" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Họ và tên *</label>
                                    <input type="text" name="name" class="form-control-custom"
                                        value="<?= clean($user['name']) ?>" placeholder="Nguyễn Văn A" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Số điện thoại</label>
                                    <input type="tel" name="phone" class="form-control-custom"
                                        value="<?= clean($user['phone'] ?? '') ?>" placeholder="0901234567">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email (không thể thay đổi)</label>
                            <input type="email" class="form-control-custom" value="<?= clean($user['email']) ?>"
                                disabled style="opacity:0.5;cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ngày tham gia</label>
                            <input type="text" class="form-control-custom"
                                value="<?= date('d/m/Y', strtotime($user['created_at'])) ?>"
                                disabled style="opacity:0.5;cursor:not-allowed;">
                        </div>
                        <button type="submit" class="btn-gold" style="padding:12px 32px;">
                            <i class="bi bi-check-lg"></i> Lưu thông tin
                        </button>
                    </form>
                </div>

                <!-- TAB: Đổi mật khẩu -->
                <?php elseif ($tab === 'password'): ?>
                <div class="form-card">
                    <div class="form-card-title"><i class="bi bi-shield-lock-fill" style="color:var(--gold)"></i> Đổi mật khẩu</div>
                    <form method="POST" id="pwForm">
                        <input type="hidden" name="change_password" value="1">

                        <div class="form-group">
                            <label class="form-label">Mật khẩu hiện tại *</label>
                            <div style="position:relative;">
                                <input type="password" name="current_password" id="curPw"
                                    class="form-control-custom" placeholder="Nhập mật khẩu hiện tại" required
                                    style="padding-right:44px;">
                                <button type="button" onclick="togglePw('curPw','eyeCur')"
                                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1rem;">
                                    <i class="bi bi-eye" id="eyeCur"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mật khẩu mới *</label>
                            <div style="position:relative;">
                                <input type="password" name="new_password" id="newPw"
                                    class="form-control-custom" placeholder="Tối thiểu 6 ký tự" required
                                    style="padding-right:44px;" oninput="checkStrength(this.value)">
                                <button type="button" onclick="togglePw('newPw','eyeNew')"
                                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1rem;">
                                    <i class="bi bi-eye" id="eyeNew"></i>
                                </button>
                            </div>
                            <!-- Thanh độ mạnh mật khẩu -->
                            <div style="margin-top:8px;">
                                <div style="height:3px;border-radius:2px;background:var(--border);overflow:hidden;">
                                    <div id="strengthBar" style="height:100%;width:0;transition:all .3s;border-radius:2px;"></div>
                                </div>
                                <div id="strengthLabel" style="font-size:11px;color:var(--text-muted);margin-top:4px;"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Xác nhận mật khẩu mới *</label>
                            <div style="position:relative;">
                                <input type="password" name="confirm_password" id="cfPw"
                                    class="form-control-custom" placeholder="Nhập lại mật khẩu mới" required
                                    style="padding-right:44px;" oninput="checkMatch()">
                                <button type="button" onclick="togglePw('cfPw','eyeCf')"
                                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1rem;">
                                    <i class="bi bi-eye" id="eyeCf"></i>
                                </button>
                            </div>
                            <div id="matchMsg" style="font-size:11px;margin-top:4px;display:none;"></div>
                        </div>

                        <!-- Yêu cầu mật khẩu -->
                        <div style="background:rgba(201,169,110,0.05);border:1px solid rgba(201,169,110,0.15);border-radius:var(--radius-sm);padding:14px;margin-bottom:20px;font-size:12px;">
                            <div style="color:var(--gold);font-weight:600;margin-bottom:8px;"><i class="bi bi-info-circle"></i> Yêu cầu mật khẩu</div>
                            <div id="req1" style="color:var(--text-muted);margin-bottom:4px;"><i class="bi bi-x-circle"></i> Ít nhất 6 ký tự</div>
                            <div id="req2" style="color:var(--text-muted);margin-bottom:4px;"><i class="bi bi-x-circle"></i> Có chữ hoa và chữ thường</div>
                            <div id="req3" style="color:var(--text-muted);"><i class="bi bi-x-circle"></i> Có ít nhất 1 chữ số</div>
                        </div>

                        <button type="submit" class="btn-gold" style="padding:12px 32px;" id="submitBtn">
                            <i class="bi bi-shield-check"></i> Đổi mật khẩu
                        </button>
                    </form>
                </div>

                <!-- TAB: Đơn hàng -->
                <?php elseif ($tab === 'orders'): ?>
                <div class="form-card">
                    <div class="form-card-title"><i class="bi bi-bag-fill" style="color:var(--gold)"></i> Lịch sử đơn hàng</div>
                    <?php if (empty($recentOrders)): ?>
                    <div style="text-align:center;padding:40px 20px;color:var(--text-muted);">
                        <i class="bi bi-bag-x" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>
                        Bạn chưa có đơn hàng nào
                        <br><a href="<?= SITE_URL ?>/menu.php" class="btn-gold" style="display:inline-block;margin-top:16px;padding:10px 24px;">Đặt món ngay</a>
                    </div>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:1px solid var(--border);">
                                    <th style="padding:10px 8px;text-align:left;font-size:11px;color:var(--text-muted);font-weight:600;letter-spacing:0.08em;text-transform:uppercase;">Mã ĐH</th>
                                    <th style="padding:10px 8px;font-size:11px;color:var(--text-muted);font-weight:600;letter-spacing:0.08em;text-transform:uppercase;">Ngày đặt</th>
                                    <th style="padding:10px 8px;font-size:11px;color:var(--text-muted);font-weight:600;letter-spacing:0.08em;text-transform:uppercase;">Số món</th>
                                    <th style="padding:10px 8px;font-size:11px;color:var(--text-muted);font-weight:600;letter-spacing:0.08em;text-transform:uppercase;">Tổng tiền</th>
                                    <th style="padding:10px 8px;font-size:11px;color:var(--text-muted);font-weight:600;letter-spacing:0.08em;text-transform:uppercase;">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentOrders as $o):
                                $st = $statusMap[$o['status']] ?? ['label'=>$o['status'],'color'=>'#888'];
                            ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                                <td style="padding:12px 8px;color:var(--gold);font-weight:600;font-size:13px;">
                                    #<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td style="padding:12px 8px;color:var(--text-muted);font-size:12px;">
                                    <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?>
                                </td>
                                <td style="padding:12px 8px;color:var(--cream);font-size:13px;text-align:center;">
                                    <?= $o['item_count'] ?> món
                                </td>
                                <td style="padding:12px 8px;color:var(--cream);font-weight:600;font-size:13px;">
                                    <?= formatPrice($o['total']) ?>
                                    <?php if ($o['discount'] > 0): ?>
                                    <br><span style="color:var(--green);font-size:11px;font-weight:400;">-<?= formatPrice($o['discount']) ?> voucher</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 8px;">
                                    <span style="background:<?= $st['color'] ?>20;color:<?= $st['color'] ?>;border:1px solid <?= $st['color'] ?>40;border-radius:20px;padding:4px 12px;font-size:11px;font-weight:600;white-space:nowrap;">
                                        <?= $st['label'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top:16px;text-align:center;">
                        <a href="<?= SITE_URL ?>/orders.php" style="color:var(--gold);font-size:13px;">
                            Xem tất cả đơn hàng <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<script>
function togglePw(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function checkStrength(pw) {
    var bar   = document.getElementById('strengthBar');
    var label = document.getElementById('strengthLabel');
    var r1    = document.getElementById('req1');
    var r2    = document.getElementById('req2');
    var r3    = document.getElementById('req3');

    var hasLen   = pw.length >= 6;
    var hasCase  = /[a-z]/.test(pw) && /[A-Z]/.test(pw);
    var hasDigit = /[0-9]/.test(pw);

    r1.innerHTML = (hasLen   ? '<i class="bi bi-check-circle" style="color:var(--green)"></i>' : '<i class="bi bi-x-circle"></i>') + ' Ít nhất 6 ký tự';
    r2.innerHTML = (hasCase  ? '<i class="bi bi-check-circle" style="color:var(--green)"></i>' : '<i class="bi bi-x-circle"></i>') + ' Có chữ hoa và chữ thường';
    r3.innerHTML = (hasDigit ? '<i class="bi bi-check-circle" style="color:var(--green)"></i>' : '<i class="bi bi-x-circle"></i>') + ' Có ít nhất 1 chữ số';

    r1.style.color = hasLen   ? 'var(--green)' : 'var(--text-muted)';
    r2.style.color = hasCase  ? 'var(--green)' : 'var(--text-muted)';
    r3.style.color = hasDigit ? 'var(--green)' : 'var(--text-muted)';

    var score = [hasLen, hasCase, hasDigit].filter(Boolean).length;
    var colors = ['#e05252','#f4a261','#52b788'];
    var labels = ['Yếu', 'Trung bình', 'Mạnh'];
    if (pw.length === 0) { bar.style.width = '0'; label.textContent = ''; return; }
    bar.style.width      = (score * 33) + '%';
    bar.style.background = colors[score-1] || '#e05252';
    label.textContent    = 'Độ mạnh: ' + (labels[score-1] || 'Yếu');
    label.style.color    = colors[score-1] || '#e05252';
}

function checkMatch() {
    var np  = document.getElementById('newPw').value;
    var cfp = document.getElementById('cfPw').value;
    var msg = document.getElementById('matchMsg');
    if (!cfp) { msg.style.display = 'none'; return; }
    msg.style.display = 'block';
    if (np === cfp) {
        msg.innerHTML = '<i class="bi bi-check-circle" style="color:var(--green)"></i> Mật khẩu khớp';
        msg.style.color = 'var(--green)';
    } else {
        msg.innerHTML = '<i class="bi bi-x-circle"></i> Mật khẩu không khớp';
        msg.style.color = 'var(--red)';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
