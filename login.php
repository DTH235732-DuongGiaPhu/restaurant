<?php
$pageTitle = 'Đăng nhập';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email']= $user['email'];

            // Remember me
            if (!empty($_POST['remember'])) {
                setcookie('sb_remember', base64_encode($user['email']), time() + 30*24*3600, '/');
            }

            setFlash('success', 'Chào mừng ' . $user['name'] . '!');
            $redirect = $_SESSION['redirect_after_login'] ?? SITE_URL . '/';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không đúng';
        }
    }
}

// Pre-fill from cookie
$rememberedEmail = '';
if (!empty($_COOKIE['sb_remember'])) {
    $rememberedEmail = base64_decode($_COOKIE['sb_remember']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div id="preloader"><div class="preloader-inner"><div class="preloader-logo">SB</div><div class="preloader-bar"><div class="preloader-fill"></div></div></div></div>

<div class="auth-section">
    <div class="auth-card" data-aos="fadeInUp">
        <div class="auth-logo">
            <a href="<?= SITE_URL ?>/" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;">
                <span style="color:var(--gold);font-size:1.2rem;">✦</span>
                <span style="font-family:var(--font-display);font-size:1.8rem;color:var(--cream);">Saigon<em style="color:var(--gold);font-style:normal;">Bistro</em></span>
            </a>
        </div>

        <h2 class="auth-title">Đăng nhập</h2>
        <p class="auth-subtitle">Chào mừng bạn trở lại!</p>

        <?php if ($error): ?>
        <div style="background:rgba(224,82,82,0.1);border:1px solid rgba(224,82,82,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;color:var(--red);font-size:13px;">
            <i class="bi bi-exclamation-circle-fill"></i> <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control-custom"
                    value="<?= $rememberedEmail ?: clean($_POST['email'] ?? '') ?>"
                    placeholder="your@email.com" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Mật khẩu</label>
                <div style="position:relative;">
                    <input type="password" name="password" class="form-control-custom" id="pwdInput"
                        placeholder="••••••••" required style="padding-right:44px;">
                    <button type="button" onclick="togglePwd()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
                        <i class="bi bi-eye" id="pwdIcon"></i>
                    </button>
                </div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:10px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text-muted);">
                    <input type="checkbox" name="remember" style="accent-color:var(--gold);" <?= $rememberedEmail ? 'checked' : '' ?>>
                    Ghi nhớ đăng nhập
                </label>
            </div>
            <button type="submit" class="btn-gold w-100" style="width:100%;padding:13px;">
                <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
            </button>
        </form>

        <div style="text-align:center;margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
            <p style="color:var(--text-muted);font-size:13px;">
                Chưa có tài khoản?
                <a href="<?= SITE_URL ?>/register.php" style="color:var(--gold);font-weight:600;">Đăng ký ngay</a>
            </p>
            <p style="margin-top:8px;"><a href="<?= SITE_URL ?>/" style="color:var(--text-muted);font-size:12px;"><i class="bi bi-arrow-left"></i> Về trang chủ</a></p>
        </div>

        <div style="background:rgba(201,169,110,0.06);border:1px solid rgba(201,169,110,0.2);border-radius:8px;padding:14px;margin-top:20px;font-size:12px;color:var(--text-muted);">
            <div style="color:var(--gold);font-weight:600;margin-bottom:6px;font-size:11px;letter-spacing:0.1em;text-transform:uppercase;">Demo Accounts</div>
            <div>Admin: <code style="color:var(--gold);">admin@saigonbistro.com</code> / <code>password</code></div>
            <div style="margin-top:3px;">User: <code style="color:var(--gold);">user@gmail.com</code> / <code>password</code></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
var SITE_URL = '<?= SITE_URL ?>';
setTimeout(function(){$('#preloader').addClass('hidden')},800);
function togglePwd(){
    var i=$('#pwdInput');
    if(i.attr('type')==='password'){i.attr('type','text');$('#pwdIcon').removeClass('bi-eye').addClass('bi-eye-slash');}
    else{i.attr('type','password');$('#pwdIcon').removeClass('bi-eye-slash').addClass('bi-eye');}
}
</script>
</body></html>
