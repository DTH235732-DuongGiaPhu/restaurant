<div class="admin-topbar">
    <button class="topbar-toggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>
    <div class="topbar-title"><?= $pageTitle ?? 'Admin' ?></div>
    <div class="topbar-right">
        <?php
        $pending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
        if ($pending > 0):
        ?>
        <a href="<?= SITE_URL ?>/admin/orders.php" class="topbar-notif" title="<?= $pending ?> đơn chờ xử lý">
            <i class="bi bi-bell"></i>
            <span class="notif-badge"><?= $pending ?></span>
        </a>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>/" target="_blank" style="color:var(--text-muted);font-size:13px;">
            <i class="bi bi-box-arrow-up-right"></i> Website
        </a>
    </div>
</div>
