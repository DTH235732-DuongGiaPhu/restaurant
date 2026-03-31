<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$id]);
$news = $stmt->fetch();
if (!$news) { header('Location: ' . SITE_URL . '/news.php'); exit; }

// Tăng lượt xem
$pdo->prepare("UPDATE news SET views = views + 1 WHERE id = ?")->execute([$id]);

$pageTitle = $news['title'];
require_once __DIR__ . '/includes/header.php';

// Tin liên quan
$related = $pdo->prepare("SELECT * FROM news WHERE id != ? ORDER BY RAND() LIMIT 3");
$related->execute([$id]);
$related = $related->fetchAll();
?>

<div class="page-hero">
    <span class="page-hero-label">Tin tức</span>
    <h1 style="font-size:clamp(1.6rem,3.5vw,2.8rem);"><?= clean($news['title']) ?></h1>
    <div class="breadcrumb-nav">
        <a href="<?= SITE_URL ?>/">Trang chủ</a> <span>›</span>
        <a href="<?= SITE_URL ?>/news.php">Tin tức</a> <span>›</span>
        <span class="current">Chi tiết</span>
    </div>
</div>

<section class="section-py">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-8">
                <article class="form-card">
                    <!-- Meta -->
                    <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;flex-wrap:wrap;">
                        <span style="color:var(--gold);font-size:13px;"><i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($news['created_at'])) ?></span>
                        <span style="color:var(--text-muted);font-size:13px;"><i class="bi bi-eye"></i> <?= number_format($news['views']) ?> lượt xem</span>
                    </div>

                    <!-- Image -->
                    <?php if ($news['image']): ?>
                    <div style="border-radius:var(--radius);overflow:hidden;margin-bottom:28px;aspect-ratio:16/9;">
                        <img src="<?= getNewsImage($news['image']) ?>" alt="<?= clean($news['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <?php else: ?>
                    <div style="border-radius:var(--radius);overflow:hidden;margin-bottom:28px;aspect-ratio:16/9;">
                        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=450&fit=crop" alt="News" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <?php endif; ?>

                    <!-- Content -->
                    <div class="news-content">
                        <?= $news['content'] ?>
                    </div>

                    <!-- Share -->
                    <div style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <span style="color:var(--text-muted);font-size:13px;">Chia sẻ:</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL.'/news-detail.php?id='.$id) ?>" target="_blank" style="background:rgba(24,119,242,0.1);color:#1877f2;border:1px solid rgba(24,119,242,0.3);padding:6px 14px;border-radius:100px;font-size:12px;"><i class="bi bi-facebook"></i> Facebook</a>
                    </div>
                </article>
            </div>

            <!-- Related -->
            <?php if (!empty($related)): ?>
            <div class="col-lg-8 mt-2">
                <h5 style="font-family:var(--font-display);color:var(--cream);margin-bottom:20px;">Tin tức liên quan</h5>
                <div class="row g-3">
                    <?php foreach ($related as $r): ?>
                    <div class="col-md-4">
                        <div class="news-card" style="font-size:0.9rem;">
                            <div class="news-card-img" style="aspect-ratio:16/9;">
                                <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=300&h=170&fit=crop&q=<?= $r['id'] ?>" alt="<?= clean($r['title']) ?>">
                            </div>
                            <div class="news-card-body" style="padding:14px;">
                                <div class="news-date" style="font-size:10px;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></div>
                                <div class="news-title" style="font-size:1rem;"><?= clean($r['title']) ?></div>
                                <a href="<?= SITE_URL ?>/news-detail.php?id=<?= $r['id'] ?>" class="news-link" style="font-size:12px;">Đọc thêm <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.news-content { color: var(--text); line-height: 1.9; font-size: 15px; }
.news-content p { margin-bottom: 16px; }
.news-content h2, .news-content h3 { font-family: var(--font-display); color: var(--cream); margin: 24px 0 12px; }
.news-content strong { color: var(--cream); }
.news-content em { color: var(--gold); }
.news-content ul, .news-content ol { padding-left: 24px; margin-bottom: 16px; color: var(--text-muted); }
</style>

<script>var SITE_URL = '<?= SITE_URL ?>';</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
