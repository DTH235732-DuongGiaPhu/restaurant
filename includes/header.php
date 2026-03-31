<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
$cartCount = getCartCount();
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME . ' — Fine Dining' ?></title>
    <meta name="description" content="Saigon Bistro - Nhà hàng cao cấp với ẩm thực Việt Nam sáng tạo tại TP.HCM">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- PRELOADER -->
<div id="preloader">
    <div class="preloader-inner">
        <div class="preloader-logo">SB</div>
        <div class="preloader-bar"><div class="preloader-fill"></div></div>
    </div>
</div>

<!-- HEADER -->
<header class="site-header" id="siteHeader">
    <div class="header-top">
        <div class="container">
            <div class="header-top-inner">
                <div class="header-contact">
                    <span><i class="bi bi-telephone-fill"></i> <?= SITE_PHONE ?></span>
                    <span><i class="bi bi-geo-alt-fill"></i> <?= SITE_ADDRESS ?></span>
                </div>
                <div class="header-top-right">
                    <span><i class="bi bi-clock-fill"></i> 10:00 - 22:00 (T2 - CN)</span>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= SITE_URL ?>/orders.php" class="header-user-link"><i class="bi bi-person-fill"></i> <?= clean($_SESSION['user_name']) ?></a>
                        <?php if (isAdmin()): ?>
                            <a href="<?= SITE_URL ?>/admin/" class="header-user-link gold"><i class="bi bi-shield-fill"></i> Admin</a>
                        <?php endif; ?>
                        <a href="<?= SITE_URL ?>/logout.php" class="header-user-link"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/login.php" class="header-user-link"><i class="bi bi-person"></i> Đăng nhập</a>
                        <a href="<?= SITE_URL ?>/register.php" class="header-user-link gold"><i class="bi bi-person-plus"></i> Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="<?= SITE_URL ?>/">
                <span class="brand-icon">✦</span>
                <span class="brand-name">Saigon<em>Bistro</em></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'menu.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/menu.php">Thực đơn</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'news.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/news.php">Tin tức</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= in_array($currentPage, ['reservation.php','my-reservations.php']) ? 'active' : '' ?>" href="<?= SITE_URL ?>/reservation.php" style="display:flex;align-items:center;gap:5px">
                            <i class="bi bi-calendar2-check" style="color:var(--gold)"></i> Đặt bàn
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/contact.php">Liên hệ</a>
                    </li>
                </ul>

                <div class="navbar-actions">
                    <a href="<?= SITE_URL ?>/cart.php" class="cart-btn <?= $currentPage === 'cart.php' ? 'active' : '' ?>">
                        <i class="bi bi-bag"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= SITE_URL ?>/menu.php" class="btn-order-now">Đặt món</a>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- FLASH MESSAGE -->
<?php if ($flash): ?>
<div class="flash-wrap">
    <div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>-fill"></i>
        <?= clean($flash['message']) ?>
        <button class="flash-close" onclick="document.getElementById('flashMsg').remove()">×</button>
    </div>
</div>
<?php endif; ?>
