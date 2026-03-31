<?php
$currentAdmin = basename($_SERVER['PHP_SELF']);
$adminPath    = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-logo">
        <span style="color:var(--gold)">✦</span>
        <span>Saigon<em>Bistro</em></span>
    </div>
    <div class="sidebar-user">
        <div class="su-avatar"><?= strtoupper(mb_substr($_SESSION['user_name']??'A',0,1)) ?></div>
        <div>
            <div class="su-name"><?= clean($_SESSION['user_name']??'Admin') ?></div>
            <div class="su-role"><i class="bi bi-shield-fill"></i> Administrator</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group-label">Tổng quan</div>
        <a href="<?= SITE_URL ?>/admin/" class="nav-item <?= $currentAdmin==='index.php' && $adminPath==='admin' ? 'active':'' ?>">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>

        <div class="nav-group-label">Quản lý</div>
        <a href="<?= SITE_URL ?>/admin/orders.php" class="nav-item <?= $currentAdmin==='orders.php'?'active':'' ?>">
            <i class="bi bi-bag-check"></i><span>Đơn hàng</span>
            <?php
            $pending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
            if ($pending > 0): ?>
            <span class="nav-badge"><?= $pending ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= SITE_URL ?>/admin/reservations.php" class="nav-item <?= $currentAdmin==='reservations.php'?'active':'' ?>">
            <i class="bi bi-calendar2-check"></i><span>Đặt bàn</span>
            <?php
            $pendingRes = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn();
            if ($pendingRes > 0): ?>
            <span class="nav-badge" style="background:rgba(100,162,224,.2);color:#64a2e0"><?= $pendingRes ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= SITE_URL ?>/admin/dishes.php" class="nav-item <?= $currentAdmin==='dishes.php'?'active':'' ?>">
            <i class="bi bi-egg-fried"></i><span>Món ăn</span>
        </a>
        <a href="<?= SITE_URL ?>/admin/categories.php" class="nav-item <?= $currentAdmin==='categories.php'?'active':'' ?>">
            <i class="bi bi-tags"></i><span>Danh mục</span>
        </a>
        <a href="<?= SITE_URL ?>/admin/news.php" class="nav-item <?= $currentAdmin==='news.php'?'active':'' ?>">
            <i class="bi bi-newspaper"></i><span>Tin tức</span>
        </a>
        <a href="<?= SITE_URL ?>/admin/users.php" class="nav-item <?= $currentAdmin==='users.php'?'active':'' ?>">
            <i class="bi bi-people"></i><span>Người dùng</span>
        </a>

        <div class="nav-group-label">Marketing</div>
        <a href="<?= SITE_URL ?>/admin/coupons.php" class="nav-item <?= $currentAdmin==='coupons.php'?'active':'' ?>">
            <i class="bi bi-ticket-perforated"></i><span>Mã giảm giá</span>
            <?php
            $activeCoupons = $pdo->query("SELECT COUNT(*) FROM coupons WHERE is_active=1 AND (expires_at IS NULL OR expires_at >= CURDATE())")->fetchColumn();
            if ($activeCoupons > 0): ?>
            <span class="nav-badge" style="background:rgba(201,169,110,0.2);color:var(--gold);"><?= $activeCoupons ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-group-label">Phân tích</div>
        <a href="<?= SITE_URL ?>/admin/reports.php" class="nav-item <?= $currentAdmin==='reports.php'?'active':'' ?>">
            <i class="bi bi-bar-chart-line"></i><span>Báo cáo doanh thu</span>
        </a>

        <div class="nav-group-label">Hệ thống</div>
        <a href="<?= SITE_URL ?>/" target="_blank" class="nav-item">
            <i class="bi bi-box-arrow-up-right"></i><span>Xem website</span>
        </a>
        <a href="<?= SITE_URL ?>/logout.php" class="nav-item" style="color:var(--red-soft)">
            <i class="bi bi-box-arrow-left"></i><span>Đăng xuất</span>
        </a>
    </nav>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
