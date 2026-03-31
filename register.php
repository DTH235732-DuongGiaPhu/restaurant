<?php
$pageTitle = 'Đăng ký';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) { header('Location: ' . SITE_URL . '/'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = clean($_POST['name'] ?? '');
    $email    = clean($_POST['email'] ?? '');
    $phone    = clean($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$name)   $errors[] = 'Vui lòng nhập họ tên';
    if (!$email)  $errors[] = 'Vui lòng nhập email';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    if (strlen($password) < 6) $errors[] = 'Mật khẩu tối thiểu 6 ký tự';
    if ($password !== $confirm)  $errors[] = 'Mật khẩu xác nhận không khớp';

    if (empty($errors)) {
        // Check email exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'Email này đã được sử dụng';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?,?,?,?)");
            $stmt->execute([$name, $email, $phone, $hashed]);

            $userId = $pdo->lastInsertId();
            $_SESSION['user_id']    = $userId;
            $_SESSION['user_name']  = $name;
            $_SESSION['user_role']  = 'user';
            $_SESSION['user_email'] = $email;

            setFlash('success', 'Đăng ký thành công! Chào mừng ' . $name . '!');
            header('Location: ' . SITE_URL . '/');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div id="preloader"><div class="preloader-inner"><div class="preloader-logo">SB</div><div class="preloader-bar"><div class="preloader-fill"></div></div></div></div>

<div class="auth-section">
    <div class="auth-card" style="max-width:520px;" data-aos="fadeInUp">
        <div class="auth-logo">
            <a href="<?= SITE_URL ?>/" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;">
                <span style="color:var(--gold);font-size:1.2rem;">✦</span>
                <span style="font-family:var(--font-display);font-size:1.8rem;color:var(--cream);">Saigon<em style="color:var(--gold);font-style:normal;">Bistro</em></span>
            </a>
        </div>

        <h2 class="auth-title">Tạo tài khoản</h2>
        <p class="auth-subtitle">Đăng ký để theo dõi đơn hàng và nhận ưu đãi!</p>

        <?php if (!empty($errors)): ?>
        <div style="background:rgba(224,82,82,0.1);border:1px solid rgba(224,82,82,0.3);border-radius:8px;padding:14px 16px;margin-bottom:20px;">
            <?php foreach ($errors as $e): ?>
            <div style="color:var(--red);font-size:13px;margin-bottom:3px;"><i class="bi bi-exclamation-circle"></i> <?= $e ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="regForm">
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">Họ và tên *</label>
                        <input type="text" name="name" class="form-control-custom"
                            value="<?= clean($_POST['name'] ?? '') ?>" placeholder="Nguyễn Văn A" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control-custom"
                            value="<?= clean($_POST['email'] ?? '') ?>" placeholder="your@email.com" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control-custom"
                            value="<?= clean($_POST['phone'] ?? '') ?>" placeholder="0901234567">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Mật khẩu *</label>
                        <input type="password" name="password" class="form-control-custom" id="pwd" placeholder="Tối thiểu 6 ký tự" required>
                        <div id="pwdStrength" style="margin-top:6px;"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Xác nhận mật khẩu *</label>
                        <input type="password" name="confirm" class="form-control-custom" id="confirm" placeholder="Nhập lại mật khẩu" required>
                    </div>
                </div>
            </div>

            <div style="margin:20px 0;font-size:13px;color:var(--text-muted);">
                Bằng cách đăng ký, bạn đồng ý với
                <a href="#" style="color:var(--gold);">Điều khoản sử dụng</a> và
                <a href="#" style="color:var(--gold);">Chính sách bảo mật</a>
            </div>

            <button type="submit" class="btn-gold" style="width:100%;padding:13px;">
                <i class="bi bi-person-check"></i> Tạo tài khoản
            </button>
        </form>

        <div style="text-align:center;margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
            <p style="color:var(--text-muted);font-size:13px;">
                Đã có tài khoản? <a href="<?= SITE_URL ?>/login.php" style="color:var(--gold);font-weight:600;">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
var SITE_URL = '<?= SITE_URL ?>';
setTimeout(function(){$('#preloader').addClass('hidden')},800);
$('#pwd').on('input',function(){
    var v=$(this).val(),len=v.length;
    var strength=len<6?'Yếu':len<10?'Trung bình':'Mạnh';
    var color=len<6?'var(--red)':len<10?'orange':'var(--green)';
    $('#pwdStrength').html('<small style="color:'+color+'">Độ mạnh: '+strength+'</small>');
});
$('#confirm').on('input',function(){
    var match=$('#pwd').val()===$(this).val();
    $(this).css('border-color',match?'var(--green)':'var(--red)');
});
</script>
</body></html>
