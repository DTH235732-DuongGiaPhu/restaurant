<?php
$pageTitle = 'Liên hệ';
require_once __DIR__ . '/includes/header.php';

$success = false;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = clean($_POST['name'] ?? '');
    $email   = clean($_POST['email'] ?? '');
    $subject = clean($_POST['subject'] ?? '');
    $message = clean($_POST['message'] ?? '');

    if (!$name)    $errors[] = 'Vui lòng nhập họ tên';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    if (!$message) $errors[] = 'Vui lòng nhập nội dung';

    if (empty($errors)) {
        // Trong thực tế: gửi email hoặc lưu vào DB
        $success = true;
    }
}
?>

<div class="page-hero">
    <span class="page-hero-label">Saigon Bistro</span>
    <h1>Liên <em>hệ</em></h1>
    <p>Chúng tôi luôn sẵn sàng lắng nghe bạn</p>
    <div class="breadcrumb-nav"><a href="<?= SITE_URL ?>/">Trang chủ</a> <span>›</span> <span class="current">Liên hệ</span></div>
</div>

<section class="section-py">
    <div class="container">
        <div class="row g-4">
            <!-- Contact Info -->
            <div class="col-lg-4" data-aos="fadeInLeft">
                <div class="contact-info-card">
                    <div class="form-card-title" style="margin-bottom:20px;">
                        <i class="bi bi-info-circle" style="color:var(--gold)"></i> Thông tin liên hệ
                    </div>
                    <div class="contact-info-item">
                        <div class="contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <div class="contact-label">Địa chỉ</div>
                            <div class="contact-value">168 Hai Bà Trưng, Phường Đakao, Quận 1, TP.HCM</div>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <div class="contact-label">Điện thoại</div>
                            <div class="contact-value">1800 6868 (miễn phí)</div>
                            <div class="contact-value">+84 28 3822 6868</div>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <div class="contact-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <div class="contact-label">Email</div>
                            <div class="contact-value">info@saigonbistro.com</div>
                            <div class="contact-value">order@saigonbistro.com</div>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <div class="contact-icon"><i class="bi bi-clock-fill"></i></div>
                        <div>
                            <div class="contact-label">Giờ mở cửa</div>
                            <div class="contact-value">Thứ 2 – Chủ nhật</div>
                            <div class="contact-value" style="color:var(--gold);">10:00 – 22:00</div>
                        </div>
                    </div>

                    <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
                        <div style="color:var(--text-muted);font-size:12px;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.1em;font-weight:600;">Theo dõi chúng tôi</div>
                        <div class="footer-socials">
                            <a href="#"><i class="bi bi-facebook"></i></a>
                            <a href="#"><i class="bi bi-instagram"></i></a>
                            <a href="#"><i class="bi bi-youtube"></i></a>
                            <a href="#"><i class="bi bi-tiktok"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="col-lg-8" data-aos="fadeInRight">
                <div class="form-card">
                    <div class="form-card-title">
                        <i class="bi bi-envelope" style="color:var(--gold)"></i> Gửi tin nhắn
                    </div>

                    <?php if ($success): ?>
                    <div style="text-align:center;padding:40px 20px;">
                        <div style="font-size:3rem;color:var(--green);margin-bottom:16px;"><i class="bi bi-check-circle-fill"></i></div>
                        <h5 style="color:var(--cream);margin-bottom:8px;">Đã gửi thành công!</h5>
                        <p style="color:var(--text-muted);font-size:13px;">Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi trong vòng 24 giờ.</p>
                        <button onclick="location.reload()" class="btn-outline-gold mt-3">Gửi tin nhắn khác</button>
                    </div>
                    <?php else: ?>

                    <?php if (!empty($errors)): ?>
                    <div style="background:rgba(224,82,82,0.1);border:1px solid rgba(224,82,82,0.3);border-radius:8px;padding:14px;margin-bottom:20px;">
                        <?php foreach ($errors as $e): ?>
                        <div style="color:var(--red);font-size:13px;"><i class="bi bi-exclamation-circle"></i> <?= $e ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Họ và tên *</label>
                                    <input type="text" name="name" class="form-control-custom"
                                        value="<?= clean($_POST['name'] ?? '') ?>"
                                        placeholder="Nguyễn Văn A" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control-custom"
                                        value="<?= clean($_POST['email'] ?? '') ?>"
                                        placeholder="your@email.com" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Chủ đề</label>
                                    <select name="subject" class="form-control-custom">
                                        <option value="order">Thắc mắc về đơn hàng</option>
                                        <option value="menu">Hỏi về thực đơn</option>
                                        <option value="booking">Đặt bàn</option>
                                        <option value="feedback">Phản hồi & góp ý</option>
                                        <option value="other">Khác</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Nội dung *</label>
                                    <textarea name="message" class="form-control-custom" style="min-height:150px;"
                                        placeholder="Nhập nội dung tin nhắn..." required><?= clean($_POST['message'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn-gold mt-2" style="padding:12px 32px;">
                            <i class="bi bi-send"></i> Gửi tin nhắn
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Google Map (embedded placeholder) -->
        <div style="margin-top:40px;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);" data-aos="fadeInUp">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.394857781893!2d106.6955!3d10.7849!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTDCsDQ3JzA1LjYiTiAxMDbCsDQxJzQ0LjIiRQ!5e0!3m2!1svi!2svn!4v1620000000000"
                width="100%" height="350" style="border:0;display:block;filter:grayscale(1) invert(0.9) contrast(0.85);"
                allowfullscreen="" loading="lazy">
            </iframe>
        </div>
    </div>
</section>

<script>var SITE_URL = '<?= SITE_URL ?>';</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
