<?php
$pageTitle = 'Thực đơn';
require_once __DIR__ . '/includes/header.php';

// Lấy danh mục
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();

// Lọc theo category
$catFilter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search    = isset($_GET['q']) ? clean($_GET['q']) : '';

// Lấy món ăn
$sql = "SELECT d.*, c.name as cat_name FROM dishes d JOIN categories c ON d.category_id = c.id WHERE d.status = 'active'";
$params = [];
if ($catFilter > 0) {
    $sql .= " AND d.category_id = ?";
    $params[] = $catFilter;
}
if ($search) {
    $sql .= " AND (d.name LIKE ? OR d.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY d.is_featured DESC, d.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dishes = $stmt->fetchAll();
?>

<div class="page-hero">
    <span class="page-hero-label">Saigon Bistro</span>
    <h1>Thực <em>đơn</em></h1>
    <p>Khám phá hơn <?= $pdo->query("SELECT COUNT(*) FROM dishes WHERE status='active'")->fetchColumn() ?> món ăn đặc sắc — từ khai vị đến tráng miệng</p>
    <div class="breadcrumb-nav"><a href="<?= SITE_URL ?>/">Trang chủ</a> <span>›</span> <span class="current">Thực đơn</span></div>
</div>

<section class="section-py">
    <div class="container">
        <!-- Search -->
        <div class="search-bar-wrap" data-aos="fadeInUp">
            <i class="bi bi-search"></i>
            <input type="text" class="search-input" id="searchInput" value="<?= $search ?>" placeholder="Tìm kiếm món ăn..." autocomplete="off">
        </div>

        <!-- Category Filter -->
        <div class="category-filter" data-aos="fadeInUp">
            <button class="cat-btn <?= !$catFilter ? 'active' : '' ?>" data-cat="all" onclick="filterCat(0, this)">
                <i class="bi bi-grid"></i> Tất cả
            </button>
            <?php foreach ($categories as $cat): ?>
            <button class="cat-btn <?= $catFilter == $cat['id'] ? 'active' : '' ?>"
                data-cat="<?= $cat['id'] ?>"
                onclick="filterCat(<?= $cat['id'] ?>, this)">
                <i class="bi bi-<?= $cat['icon'] ?>"></i> <?= clean($cat['name']) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Results count -->
        <div style="text-align:center;margin-bottom:24px;">
            <span style="color:var(--text-muted);font-size:13px;" id="resultCount">
                <?= count($dishes) ?> món ăn
            </span>
        </div>

        <!-- Dishes Grid -->
        <div class="row g-4" id="dishesGrid">
            <?php if (empty($dishes)): ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="bi bi-search"></i>
                    <h5>Không tìm thấy món ăn</h5>
                    <p>Hãy thử tìm kiếm với từ khóa khác</p>
                    <a href="<?= SITE_URL ?>/menu.php" class="btn-outline-gold mt-3" style="display:inline-block;">Xem tất cả</a>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($dishes as $i => $dish): ?>
            <div class="col-sm-6 col-lg-4 dish-col" data-cat="<?= $dish['category_id'] ?>">
                <div class="dish-card dish-item"
                    data-cat="<?= $dish['category_id'] ?>"
                    data-name="<?= htmlspecialchars($dish['name']) ?>"
                    data-aos="fadeInUp" style="animation-delay:<?= ($i % 6) * 0.07 ?>s">
                    <div class="dish-card-img">
                        <img src="<?= getDishImage($dish['image']) ?>"
                            alt="<?= clean($dish['name']) ?>" loading="lazy"
                            onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&h=300&fit=crop'">
                        <?php if ($dish['is_featured']): ?>
                        <span class="dish-badge">⭐ Nổi bật</span>
                        <?php endif; ?>
                    </div>
                    <div class="dish-card-body">
                        <div class="dish-card-cat"><?= clean($dish['cat_name']) ?></div>
                        <div class="dish-card-name"><?= clean($dish['name']) ?></div>
                        <div class="dish-card-desc"><?= clean($dish['description']) ?></div>
                        <div class="dish-card-footer">
                            <span class="dish-price"><?= formatPrice($dish['price']) ?></span>
                            <button class="btn-add-cart"
                                data-id="<?= $dish['id'] ?>"
                                data-name="<?= htmlspecialchars($dish['name']) ?>"
                                data-price="<?= $dish['price'] ?>"
                                title="Thêm vào giỏ hàng">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
var SITE_URL = '<?= SITE_URL ?>';
function filterCat(id, btn) {
    $('.cat-btn').removeClass('active');
    $(btn).addClass('active');
    if (id === 0) {
        $('.dish-col').show();
    } else {
        $('.dish-col').each(function() {
            $(this).data('cat') == id ? $(this).fadeIn(200) : $(this).hide();
        });
    }
    var visible = $('.dish-col:visible').length;
    $('#resultCount').text(visible + ' món ăn');
}
$('#searchInput').on('input', function() {
    var q = $(this).val().toLowerCase().trim();
    $('.dish-col').each(function() {
        var name = $(this).find('.dish-item').data('name').toLowerCase();
        !q || name.includes(q) ? $(this).fadeIn(200) : $(this).hide();
    });
    var visible = $('.dish-col:visible').length;
    $('#resultCount').text(visible + ' món ăn');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
