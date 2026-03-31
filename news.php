<?php
$pageTitle = 'Tin tức';
require_once __DIR__ . '/includes/header.php';
$news = $pdo->query("SELECT * FROM news ORDER BY created_at DESC")->fetchAll();
?>

<div class="page-hero">
    <span class="page-hero-label">Saigon Bistro</span>
    <h1>Tin tức & <em>Sự kiện</em></h1>
    <p>Cập nhật những tin tức mới nhất từ Saigon Bistro</p>
    <div class="breadcrumb-nav"><a href="<?= SITE_URL ?>/">Trang chủ</a> <span>›</span> <span class="current">Tin tức</span></div>
</div>

<section class="section-py">
    <div class="container">
        <?php if (empty($news)): ?>
        <div class="empty-state"><i class="bi bi-newspaper"></i><h5>Chưa có tin tức nào</h5></div>
        <?php else: ?>
        <div class="row g-4">
            <!-- Featured -->
            <div class="col-12" data-aos="fadeInUp">
                <a href="<?= SITE_URL ?>/news-detail.php?id=<?= $news[0]['id'] ?>" style="text-decoration:none;">
                    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;display:flex;flex-wrap:wrap;transition:var(--transition);" class="featured-news-card">
                        <div style="flex:0 0 50%;min-width:280px;aspect-ratio:16/7;overflow:hidden;">
                            <img src="<?= getNewsImage($news[0]['image']) ?>" alt="<?= clean($news[0]['title']) ?>"
                                style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                                onerror="this.src='https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&h=300&fit=crop'">
                        </div>
                        <div style="flex:1;padding:36px;display:flex;flex-direction:column;justify-content:center;">
                            <div style="background:var(--gold);color:#000;font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;padding:3px 12px;border-radius:3px;display:inline-block;margin-bottom:14px;align-self:flex-start;">Nổi bật</div>
                            <div style="color:var(--gold);font-size:12px;margin-bottom:10px;"><i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($news[0]['created_at'])) ?></div>
                            <h2 style="font-family:var(--font-display);font-size:1.8rem;color:var(--cream);margin-bottom:12px;line-height:1.3;"><?= clean($news[0]['title']) ?></h2>
                            <p style="color:var(--text-muted);font-size:14px;line-height:1.7;margin-bottom:20px;"><?= truncate($news[0]['excerpt'], 150) ?></p>
                            <span class="news-link">Đọc tiếp <i class="bi bi-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Rest -->
            <?php for ($i=1; $i < count($news); $i++): $n = $news[$i]; ?>
            <div class="col-md-4" data-aos="fadeInUp" style="animation-delay:<?= ($i-1)*0.1 ?>s">
                <div class="news-card">
                    <div class="news-card-img">
                        <img src="<?= getNewsImage($n['image']) ?>" alt="<?= clean($n['title']) ?>"
                            onerror="this.src='https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400&h=225&fit=crop&q=<?= $n['id'] ?>'">
                    </div>
                    <div class="news-card-body">
                        <div class="news-date"><i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($n['created_at'])) ?></div>
                        <div class="news-title"><?= clean($n['title']) ?></div>
                        <div class="news-excerpt"><?= truncate($n['excerpt'], 90) ?></div>
                        <a href="<?= SITE_URL ?>/news-detail.php?id=<?= $n['id'] ?>" class="news-link">Đọc thêm <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.featured-news-card:hover { border-color:var(--gold-dark); box-shadow:var(--shadow); }
.featured-news-card:hover img { transform:scale(1.04); }
</style>

<script>var SITE_URL = '<?= SITE_URL ?>';</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
