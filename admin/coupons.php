<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Mã giảm giá';
$msg = '';

// ── XỬ LÝ HÀNH ĐỘNG ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Thêm / Sửa
    if ($action === 'save') {
        $id      = (int)($_POST['id'] ?? 0);
        $code    = strtoupper(clean($_POST['code'] ?? ''));
        $type    = in_array($_POST['type'], ['percent','fixed']) ? $_POST['type'] : 'percent';
        $value   = (float)($_POST['value'] ?? 0);
        $minOrd  = (int)($_POST['min_order'] ?? 0);
        $maxUses = $_POST['max_uses'] !== '' ? (int)$_POST['max_uses'] : null;
        $expires = $_POST['expires_at'] ?: null;
        $active  = isset($_POST['is_active']) ? 1 : 0;

        if (!$code || $value <= 0) {
            $msg = 'error|Vui lòng nhập đầy đủ mã và giá trị giảm.';
        } else {
            try {
                if ($id) {
                    $pdo->prepare("UPDATE coupons SET code=?,type=?,value=?,min_order=?,max_uses=?,expires_at=?,is_active=? WHERE id=?")
                        ->execute([$code,$type,$value,$minOrd,$maxUses,$expires,$active,$id]);
                    $msg = 'success|Cập nhật mã giảm giá thành công!';
                } else {
                    $pdo->prepare("INSERT INTO coupons (code,type,value,min_order,max_uses,expires_at,is_active) VALUES (?,?,?,?,?,?,?)")
                        ->execute([$code,$type,$value,$minOrd,$maxUses,$expires,$active]);
                    $msg = 'success|Thêm mã giảm giá thành công!';
                }
            } catch (Exception $e) {
                $msg = 'error|Mã đã tồn tại hoặc có lỗi xảy ra.';
            }
        }
    }

    // Xoá
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM coupons WHERE id=?")->execute([$id]);
        $msg = 'success|Đã xoá mã giảm giá.';
    }

    // Đổi trạng thái nhanh
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE coupons SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
        header('Location: coupons.php');
        exit;
    }

    header('Location: coupons.php?msg=' . urlencode($msg));
    exit;
}

// Flash message từ redirect
$flashMsg = $_GET['msg'] ?? '';
if (!$msg && $flashMsg) $msg = urldecode($flashMsg);
[$msgType, $msgText] = $msg ? explode('|', $msg, 2) : ['', ''];

// Lấy danh sách
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();

// Edit mode
$editId   = (int)($_GET['edit'] ?? 0);
$editData = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id=?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
}
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
                <h2 class="content-title">Mã giảm giá</h2>
                <p class="content-subtitle">Quản lý voucher & khuyến mãi</p>
            </div>
            <button onclick="openModal()" class="btn-admin-gold">
                <i class="bi bi-plus-lg"></i> Thêm mã mới
            </button>
        </div>

        <?php if ($msgText): ?>
        <div class="alert-admin alert-<?= $msgType ?>" style="margin-bottom:20px;">
            <i class="bi bi-<?= $msgType==='success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $msgText ?>
        </div>
        <?php endif; ?>

        <!-- Thống kê nhanh -->
        <div class="row g-3 mb-4">
            <?php
            $totalCoupons  = count($coupons);
            $activeCoupons = count(array_filter($coupons, fn($c) => $c['is_active']));
            $totalUsed     = array_sum(array_column($coupons, 'used_count'));
            ?>
            <div class="col-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(201,169,110,0.1);color:var(--gold)"><i class="bi bi-ticket-perforated"></i></div>
                    <div class="stat-info"><div class="stat-val"><?= $totalCoupons ?></div><div class="stat-label">Tổng mã</div></div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(82,183,136,0.1);color:var(--green)"><i class="bi bi-check-circle"></i></div>
                    <div class="stat-info"><div class="stat-val"><?= $activeCoupons ?></div><div class="stat-label">Đang hoạt động</div></div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(100,160,255,0.1);color:#64a0ff"><i class="bi bi-lightning"></i></div>
                    <div class="stat-info"><div class="stat-val"><?= $totalUsed ?></div><div class="stat-label">Lượt dùng</div></div>
                </div>
            </div>
        </div>

        <!-- Bảng danh sách -->
        <div class="table-card">
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã</th><th>Loại</th><th>Giá trị</th><th>Đơn tối thiểu</th>
                            <th>Đã dùng</th><th>Hết hạn</th><th>Trạng thái</th><th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($coupons as $c):
                        $expired  = $c['expires_at'] && strtotime($c['expires_at']) < strtotime('today');
                        $maxed    = $c['max_uses'] !== null && $c['used_count'] >= $c['max_uses'];
                        $isActive = $c['is_active'] && !$expired && !$maxed;
                    ?>
                    <tr>
                        <td>
                            <code style="background:rgba(201,169,110,0.1);color:var(--gold);padding:4px 10px;border-radius:4px;font-weight:700;letter-spacing:0.05em;">
                                <?= clean($c['code']) ?>
                            </code>
                        </td>
                        <td>
                            <?php if ($c['type'] === 'percent'): ?>
                            <span style="color:#64a0ff;font-size:12px;"><i class="bi bi-percent"></i> Phần trăm</span>
                            <?php else: ?>
                            <span style="color:var(--green);font-size:12px;"><i class="bi bi-cash"></i> Cố định</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--gold);font-weight:600;">
                            <?= $c['type'] === 'percent' ? intval($c['value']) . '%' : number_format($c['value'], 0, ',', '.') . 'đ' ?>
                        </td>
                        <td style="color:var(--text-muted);">
                            <?= $c['min_order'] > 0 ? number_format($c['min_order'], 0, ',', '.') . 'đ' : '—' ?>
                        </td>
                        <td>
                            <span style="color:var(--cream);"><?= $c['used_count'] ?></span>
                            <?php if ($c['max_uses']): ?>
                            <span style="color:var(--text-muted);font-size:11px;"> / <?= $c['max_uses'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;color:<?= $expired ? 'var(--red)' : 'var(--text-muted)' ?>;">
                            <?= $c['expires_at'] ? date('d/m/Y', strtotime($c['expires_at'])) : '∞' ?>
                            <?php if ($expired): ?><br><span style="color:var(--red);font-size:10px;">Đã hết hạn</span><?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit"
                                    style="border:none;cursor:pointer;border-radius:20px;padding:4px 12px;font-size:11px;font-weight:600;
                                    background:<?= $isActive ? 'rgba(82,183,136,0.15)' : 'rgba(224,82,82,0.12)' ?>;
                                    color:<?= $isActive ? 'var(--green)' : 'var(--red)' ?>;">
                                    <?= $isActive ? '● Hoạt động' : '○ Tắt' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <a href="coupons.php?edit=<?= $c['id'] ?>" class="btn-icon" title="Sửa"
                                style="color:var(--gold);margin-right:4px;"><i class="bi bi-pencil"></i></a>
                            <form method="POST" style="display:inline;"
                                onsubmit="return confirm('Xoá mã <?= clean($c['code']) ?>?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn-icon" style="color:var(--red);background:none;border:none;cursor:pointer;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($coupons)): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px;">Chưa có mã giảm giá nào</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL THÊM / SỬA -->
<div id="couponModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:32px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto;position:relative;animation:modalIn .25s ease;">
        <button onclick="closeModal()" style="position:absolute;top:16px;right:20px;background:none;border:none;color:var(--text-muted);font-size:1.5rem;cursor:pointer;">×</button>
        <h3 style="font-family:var(--font-display);color:var(--cream);margin-bottom:24px;" id="modalTitle">Thêm mã giảm giá</h3>
        <form method="POST" id="couponForm">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="editId" value="0">

            <div class="form-group-admin">
                <label>Mã giảm giá *</label>
                <input type="text" name="code" id="fCode" class="input-admin" placeholder="VD: SAIGON10" required
                    style="text-transform:uppercase;">
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <div class="form-group-admin">
                        <label>Loại giảm *</label>
                        <select name="type" id="fType" class="input-admin">
                            <option value="percent">% Phần trăm</option>
                            <option value="fixed">Cố định (VNĐ)</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group-admin">
                        <label>Giá trị *</label>
                        <input type="number" name="value" id="fValue" class="input-admin" placeholder="10" min="1" required>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <div class="form-group-admin">
                        <label>Đơn tối thiểu (đ)</label>
                        <input type="number" name="min_order" id="fMinOrder" class="input-admin" placeholder="0" min="0">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group-admin">
                        <label>Giới hạn dùng</label>
                        <input type="number" name="max_uses" id="fMaxUses" class="input-admin" placeholder="Để trống = không giới hạn" min="1">
                    </div>
                </div>
            </div>
            <div class="form-group-admin">
                <label>Ngày hết hạn</label>
                <input type="date" name="expires_at" id="fExpires" class="input-admin">
            </div>
            <div class="form-group-admin" style="display:flex;align-items:center;gap:10px;">
                <input type="checkbox" name="is_active" id="fActive" checked style="accent-color:var(--gold);width:16px;height:16px;">
                <label for="fActive" style="cursor:pointer;">Kích hoạt ngay</label>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="submit" class="btn-admin-gold" style="flex:1;">
                    <i class="bi bi-check-lg"></i> Lưu mã giảm giá
                </button>
                <button type="button" onclick="closeModal()" class="btn-admin-outline" style="flex:1;">Huỷ</button>
            </div>
        </form>
    </div>
</div>

<style>
.form-group-admin { margin-bottom:16px; }
.form-group-admin label { display:block;font-size:12px;color:var(--text-muted);font-weight:600;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:6px; }
.input-admin { width:100%;background:var(--bg-secondary);border:1px solid var(--border);color:var(--cream);padding:10px 12px;border-radius:var(--radius);font-size:13px;font-family:var(--font-body);transition:var(--transition); }
.input-admin:focus { outline:none;border-color:var(--gold); }
.btn-admin-outline { background:transparent;border:1px solid var(--border);color:var(--text);padding:10px 20px;border-radius:var(--radius);font-size:13px;font-weight:600;cursor:pointer;transition:var(--transition);font-family:var(--font-body); }
.btn-admin-outline:hover { border-color:var(--gold);color:var(--gold); }
.alert-admin { padding:14px 18px;border-radius:var(--radius);font-size:13px;display:flex;align-items:center;gap:8px; }
.alert-success { background:rgba(82,183,136,0.1);border:1px solid rgba(82,183,136,0.3);color:var(--green); }
.alert-error   { background:rgba(224,82,82,0.1);border:1px solid rgba(224,82,82,0.3);color:var(--red); }
@keyframes modalIn { from{transform:scale(0.94);opacity:0} to{transform:scale(1);opacity:1} }
</style>

<script>
var editData = <?= $editData ? json_encode($editData) : 'null' ?>;

function openModal(data) {
    document.getElementById('couponModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = data ? 'Sửa mã giảm giá' : 'Thêm mã giảm giá';
    if (data) {
        document.getElementById('editId').value      = data.id;
        document.getElementById('fCode').value       = data.code;
        document.getElementById('fType').value       = data.type;
        document.getElementById('fValue').value      = data.value;
        document.getElementById('fMinOrder').value   = data.min_order;
        document.getElementById('fMaxUses').value    = data.max_uses || '';
        document.getElementById('fExpires').value    = data.expires_at || '';
        document.getElementById('fActive').checked   = data.is_active == 1;
    } else {
        document.getElementById('couponForm').reset();
        document.getElementById('editId').value = '0';
        document.getElementById('fActive').checked = true;
    }
}
function closeModal() { document.getElementById('couponModal').style.display = 'none'; }

// Auto open nếu có edit
if (editData) openModal(editData);

document.getElementById('couponModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
