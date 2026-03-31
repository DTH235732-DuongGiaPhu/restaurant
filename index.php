<?php
$pageTitle = 'Trang chủ';
require_once __DIR__ . '/includes/header.php';

// Lấy món nổi bật
$stmt = $pdo->query("SELECT d.*, c.name as cat_name FROM dishes d JOIN categories c ON d.category_id = c.id WHERE d.is_featured = 1 AND d.status = 'active' LIMIT 6");
$featured = $stmt->fetchAll();

// Lấy tất cả categories
$cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();

// Lấy tin tức mới nhất
$news = $pdo->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 3")->fetchAll();

// Stats
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='done'")->fetchColumn();
$totalDishes  = $pdo->query("SELECT COUNT(*) FROM dishes WHERE status='active'")->fetchColumn();
?>

<!-- ====================================================
     HERO SECTION
===================================================== -->
<section class="hero-section">
    <div class="hero-bg"></div>
    <div class="hero-pattern"></div>
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="hero-content" data-aos="fadeInLeft">
                    <div class="hero-eyebrow">Fine Dining</div>
                    <h1 class="hero-title">
                        Nghệ thuật
                        <span class="line-italic">ẩm thực</span>
                        Việt Nam
                    </h1>
                    <p class="hero-subtitle">Trải nghiệm hương vị độc đáo kết hợp ẩm thực truyền thống và kỹ thuật nấu nướng hiện đại — nơi mỗi bữa ăn là một hành trình khám phá.</p>
                    <div class="hero-actions">
                        <a href="<?= SITE_URL ?>/menu.php" class="btn-gold">Xem thực đơn</a>
                        <a href="<?= SITE_URL ?>/reservation.php" class="btn-outline-gold"><i class="bi bi-calendar2-check"></i> Đặt bàn trước</a>
                    </div>
                    <div class="hero-stats">
                        <div>
                            <span class="hero-stat-num counter-num" data-target="<?= $totalOrders ?>" data-suffix="+"><?= $totalOrders ?>+</span>
                            <span class="hero-stat-label">Đơn hoàn thành</span>
                        </div>
                        <div>
                            <span class="hero-stat-num counter-num" data-target="<?= $totalDishes ?>" data-suffix="+"><?= $totalDishes ?>+</span>
                            <span class="hero-stat-label">Món đặc sắc</span>
                        </div>
                        <div>
                            <span class="hero-stat-num">4.9<span style="font-size:1.2rem">★</span></span>
                            <span class="hero-stat-label">Đánh giá</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-side" data-aos="fadeInRight">
                    <div class="hero-img-main">
                        <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&h=750&fit=crop" alt="Saigon Bistro Food">
                    </div>
                    <div class="hero-img-badge">
                        <div class="badge-icon">⭐</div>
                        <div class="badge-val">Top 10</div>
                        <div class="badge-text">Nhà hàng Sài Gòn 2025</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================
     ABOUT STRIP
===================================================== -->
<section class="about-strip">
    <div class="container">
        <div class="row g-0">
            <div class="col-6 col-md-3">
                <div class="strip-item" data-aos="fadeInUp">
                    <div class="strip-icon"><i class="bi bi-truck"></i></div>
                    <div class="strip-info">
                        <h6>Giao hàng nhanh</h6>
                        <span>Trong vòng 45 phút</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="strip-item" data-aos="fadeInUp" style="animation-delay:.1s">
                    <div class="strip-icon"><i class="bi bi-award"></i></div>
                    <div class="strip-info">
                        <h6>Nguyên liệu tươi</h6>
                        <span>Nhập mỗi ngày</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="strip-item" data-aos="fadeInUp" style="animation-delay:.2s">
                    <div class="strip-icon"><i class="bi bi-headset"></i></div>
                    <div class="strip-info">
                        <h6>Hỗ trợ 24/7</h6>
                        <span>Tư vấn tận tình</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="strip-item" data-aos="fadeInUp" style="animation-delay:.3s">
                    <div class="strip-icon"><i class="bi bi-shield-check"></i></div>
                    <div class="strip-info">
                        <h6>An toàn vệ sinh</h6>
                        <span>Đạt chuẩn ATTP</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================
     FEATURED DISHES
===================================================== -->
<section class="featured-section" id="featured">
    <div class="container">
        <div class="text-center mb-5" data-aos="fadeInUp">
            <span class="section-label">Thực đơn nổi bật</span>
            <h2 class="section-title">Món <em>đặc sắc</em> nhà hàng</h2>
            <div class="section-divider"><span class="divider-icon">✦</span></div>
            <p class="text-muted-custom" style="max-width:520px;margin:0 auto">Những món ăn được lựa chọn kỹ lưỡng từ đội ngũ đầu bếp giàu kinh nghiệm — mang đến trải nghiệm ẩm thực đỉnh cao.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($featured as $i => $dish): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="dish-card" data-aos="fadeInUp" style="animation-delay:<?= $i * 0.08 ?>s">
                    <div class="dish-card-img">
                        <img src="<?= getDishImage($dish['image']) ?>"
                            alt="<?= clean($dish['name']) ?>" 
                            loading="lazy"
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
                                title="Thêm vào giỏ">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?= SITE_URL ?>/menu.php" class="btn-outline-gold">Xem toàn bộ thực đơn →</a>
        </div>
    </div>
</section>

<!-- ====================================================
     CATEGORIES SHOWCASE
===================================================== -->
<section style="padding:60px 0; background: var(--bg-secondary); border-top:1px solid var(--border)">
    <div class="container">
        <div class="text-center mb-5" data-aos="fadeInUp">
            <span class="section-label">Danh mục</span>
            <h2 class="section-title">Khám phá <em>thực đơn</em></h2>
        </div>
        <div class="row g-3 justify-content-center">
            <?php foreach ($cats as $i => $cat): ?>
            <div class="col-4 col-md-2" data-aos="zoomIn" style="animation-delay:<?= $i * 0.07 ?>s">
                <a href="<?= SITE_URL ?>/menu.php?cat=<?= $cat['id'] ?>" class="cat-showcase-item">
                    <div class="cat-icon-box"><i class="bi bi-<?= $cat['icon'] ?>"></i></div>
                    <span><?= clean($cat['name']) ?></span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====================================================
     ABOUT BANNER
===================================================== -->
<section style="padding:100px 0; overflow:hidden;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fadeInLeft">
                <div style="position:relative;">
                    <div style="border-radius:var(--radius); overflow:hidden; aspect-ratio:4/3;">
                        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&h=450&fit=crop" alt="Restaurant" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <div style="position:absolute;bottom:-20px;right:-20px;background:var(--gold);color:#000;border-radius:var(--radius);padding:20px 24px;min-width:140px;text-align:center;">
                        <div style="font-family:var(--font-display);font-size:2.5rem;font-weight:700;line-height:1;">10+</div>
                        <div style="font-size:11px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;">Năm kinh nghiệm</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fadeInRight">
                <span class="section-label">Về chúng tôi</span>
                <h2 class="section-title">Nơi <em>hội tụ</em> hương vị</h2>
                <div class="section-divider" style="justify-content:flex-start;"><span class="divider-icon">✦</span></div>
                <p style="color:var(--text-muted);line-height:1.9;margin-bottom:20px;">Saigon Bistro được thành lập năm 2014 với sứ mệnh mang đến những trải nghiệm ẩm thực đặc sắc, nơi kỹ thuật nấu nướng hiện đại gặp gỡ hương vị truyền thống Việt Nam.</p>
                <p style="color:var(--text-muted);line-height:1.9;margin-bottom:30px;">Đội ngũ đầu bếp của chúng tôi được đào tạo tại các trường ẩm thực danh tiếng trong và ngoài nước, cam kết mang đến những món ăn không chỉ ngon mà còn đẹp mắt.</p>
                <div style="display:flex;gap:32px;margin-bottom:32px;">
                    <div>
                        <div style="font-family:var(--font-display);font-size:2rem;color:var(--gold);font-weight:700;">50+</div>
                        <div style="font-size:12px;color:var(--text-muted);">Đầu bếp chuyên nghiệp</div>
                    </div>
                    <div>
                        <div style="font-family:var(--font-display);font-size:2rem;color:var(--gold);font-weight:700;">3</div>
                        <div style="font-size:12px;color:var(--text-muted);">Chi nhánh TP.HCM</div>
                    </div>
                    <div>
                        <div style="font-family:var(--font-display);font-size:2rem;color:var(--gold);font-weight:700;">15+</div>
                        <div style="font-size:12px;color:var(--text-muted);">Giải thưởng uy tín</div>
                    </div>
                </div>
                <a href="<?= SITE_URL ?>/contact.php" class="btn-gold">Liên hệ với chúng tôi</a>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================
     TESTIMONIALS
===================================================== -->
<section class="testimonial-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fadeInUp">
            <span class="section-label">Đánh giá khách hàng</span>
            <h2 class="section-title">Khách hàng <em>nói gì?</em></h2>
        </div>
        <div class="row g-4">
            <?php
            $testimonials = [
                ['name'=>'Nguyễn Minh Tuấn','role'=>'Doanh nhân','text'=>'Thức ăn ngon tuyệt, phục vụ chuyên nghiệp. Lần nào đến cũng cảm thấy như đang ở một không gian riêng biệt. Sẽ quay lại nhiều lần!','init'=>'T'],
                ['name'=>'Trần Thanh Huyền','role'=>'Food Blogger','text'=>'Bò bít tết ở đây là ngon nhất mình từng ăn. Sốt tiêu đen đặc biệt, thịt bò Úc mềm tan. Không gian sang trọng, đặt bàn online rất tiện.','init'=>'H'],
                ['name'=>'Lê Văn Khang','role'=>'Kỹ sư','text'=>'Lẩu Thái hải sản đậm đà đúng vị, hải sản tươi ngon. Đặt hàng qua app nhanh, giao hàng đúng giờ. Chatbot AI hỗ trợ tư vấn món rất hay!','init'=>'K'],
            ];
            foreach ($testimonials as $i => $t): ?>
            <div class="col-lg-4" data-aos="fadeInUp" style="animation-delay:<?= $i * 0.1 ?>s">
                <div class="testimonial-card">
                    <div class="testimonial-stars">★★★★★</div>
                    <p class="testimonial-text">"<?= $t['text'] ?>"</p>
                    <div class="testimonial-author">
                        <div class="t-avatar"><?= $t['init'] ?></div>
                        <div>
                            <div class="t-name"><?= $t['name'] ?></div>
                            <div class="t-title"><?= $t['role'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====================================================
     LATEST NEWS
===================================================== -->
<?php if (!empty($news)): ?>
<section style="padding:90px 0;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fadeInUp">
            <span class="section-label">Tin tức & Sự kiện</span>
            <h2 class="section-title">Tin tức <em>mới nhất</em></h2>
        </div>
        <div class="row g-4">
            <?php foreach ($news as $i => $n): ?>
            <div class="col-md-4" data-aos="fadeInUp" style="animation-delay:<?= $i * 0.1 ?>s">
                <div class="news-card">
                    <div class="news-card-img">
                        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400&h=225&fit=crop&sig=<?= $n['id'] ?>" alt="<?= clean($n['title']) ?>">
                    </div>
                    <div class="news-card-body">
                        <div class="news-date"><i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($n['created_at'])) ?></div>
                        <div class="news-title"><?= clean($n['title']) ?></div>
                        <div class="news-excerpt"><?= truncate($n['excerpt'], 90) ?></div>
                        <a href="<?= SITE_URL ?>/news-detail.php?id=<?= $n['id'] ?>" class="news-link">Đọc thêm <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
.cat-showcase-item {
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  padding: 20px 10px; border-radius: var(--radius);
  background: var(--bg-card); border: 1px solid var(--border);
  color: var(--text-muted); font-size: 12px; font-weight: 500; text-align: center;
  transition: var(--transition); text-decoration: none;
}
.cat-showcase-item:hover { border-color: var(--gold); color: var(--gold); transform: translateY(-4px); }
.cat-icon-box { font-size: 1.6rem; color: var(--gold); }
</style>

<script>
var SITE_URL = '<?= SITE_URL ?>';
</script>

<!-- ── RESERVATION CTA SECTION ── -->
<section style="padding:80px 0;background:linear-gradient(135deg,rgba(201,169,110,.06) 0%,transparent 60%);border-top:1px solid var(--border)">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:900px;margin:0 auto" class="res-cta-grid">
            <!-- Card 1: Chỉ đặt bàn -->
            <div data-aos="fadeInLeft" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:36px 32px;text-align:center;transition:var(--transition);position:relative;overflow:hidden">
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--border),var(--border))"></div>
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(82,183,136,.1);border:2px solid rgba(82,183,136,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.8rem;color:var(--green)">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
                <h3 style="font-family:var(--font-display);font-size:1.6rem;color:var(--cream);margin-bottom:10px">Đặt bàn trước</h3>
                <p style="color:var(--text-muted);font-size:14px;line-height:1.7;margin-bottom:8px">Chọn vị trí yêu thích, giữ chỗ đảm bảo — đến nhà hàng gọi món thoải mái.</p>
                <p style="color:var(--green);font-size:13px;font-weight:700;margin-bottom:24px"><i class="bi bi-check-circle"></i> Hoàn toàn miễn phí</p>
                <a href="<?= SITE_URL ?>/reservation.php" class="btn-outline-gold" style="display:inline-flex;align-items:center;gap:8px">
                    <i class="bi bi-arrow-right"></i> Đặt bàn ngay
                </a>
            </div>
            <!-- Card 2: VIP -->
            <div data-aos="fadeInRight" style="background:var(--bg-card);border:1px solid var(--gold);border-radius:var(--radius);padding:36px 32px;text-align:center;transition:var(--transition);position:relative;overflow:hidden;box-shadow:0 4px 32px rgba(201,169,110,.12)">
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--gold-dark),var(--gold-light))"></div>
                <div style="position:absolute;top:16px;right:16px;background:rgba(201,169,110,.15);border:1px solid var(--gold);border-radius:100px;padding:3px 10px;font-size:10px;font-weight:700;color:var(--gold);letter-spacing:.08em">VIP</div>
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(201,169,110,.1);border:2px solid rgba(201,169,110,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.8rem;color:var(--gold)">
                    <i class="bi bi-stars"></i>
                </div>
                <h3 style="font-family:var(--font-display);font-size:1.6rem;color:var(--cream);margin-bottom:10px">Trải nghiệm VIP</h3>
                <p style="color:var(--text-muted);font-size:14px;line-height:1.7;margin-bottom:8px">Đặt sẵn món ăn, chọn phong cách trang trí bàn — đến là tận hưởng ngay.</p>
                <p style="color:var(--gold);font-size:13px;font-weight:700;margin-bottom:24px"><i class="bi bi-gift"></i> Decor miễn phí cho hóa đơn trên 1.000.000đ</p>
                <a href="<?= SITE_URL ?>/reservation.php" class="btn-gold" style="display:inline-flex;align-items:center;gap:8px">
                    <i class="bi bi-bag-heart"></i> Trải nghiệm VIP
                </a>
            </div>
        </div>
    </div>
</section>
<style>
@media(max-width:600px){.res-cta-grid{grid-template-columns:1fr!important}}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
