<!-- FOOTER -->
<footer class="site-footer">
    <div class="footer-wave">
        <svg viewBox="0 0 1440 80" preserveAspectRatio="none"><path d="M0,40 C360,80 1080,0 1440,40 L1440,80 L0,80 Z" fill="#0d0d0d"/></svg>
    </div>

    <div class="footer-main">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <span class="brand-icon">✦</span>
                        <span class="brand-name">Saigon<em>Bistro</em></span>
                    </div>
                    <p class="footer-desc">Trải nghiệm ẩm thực đỉnh cao tại trái tim Sài Gòn. Nơi hội tụ hương vị truyền thống và nghệ thuật đương đại.</p>
                    <div class="footer-socials">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-youtube"></i></a>
                        <a href="#"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>

                <div class="col-12 col-sm-4 col-lg-2">
                    <h6 class="footer-title">Khám phá</h6>
                    <ul class="footer-links">
                        <li><a href="<?= SITE_URL ?>/">Trang chủ</a></li>
                        <li><a href="<?= SITE_URL ?>/menu.php">Thực đơn</a></li>
                        <li><a href="<?= SITE_URL ?>/reservation.php">Đặt bàn</a></li>
                        <li><a href="<?= SITE_URL ?>/news.php">Tin tức</a></li>
                        <li><a href="<?= SITE_URL ?>/contact.php">Liên hệ</a></li>
                    </ul>
                </div>

                <div class="col-12 col-sm-4 col-lg-3">
                    <h6 class="footer-title">Tài khoản</h6>
                    <ul class="footer-links">
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?= SITE_URL ?>/orders.php">Đơn hàng của tôi</a></li>
                            <li><a href="<?= SITE_URL ?>/my-reservations.php">Đặt bàn của tôi</a></li>
                            <li><a href="<?= SITE_URL ?>/logout.php">Đăng xuất</a></li>
                        <?php else: ?>
                            <li><a href="<?= SITE_URL ?>/login.php">Đăng nhập</a></li>
                            <li><a href="<?= SITE_URL ?>/register.php">Đăng ký</a></li>
                        <?php endif; ?>
                        <li><a href="<?= SITE_URL ?>/cart.php">Giỏ hàng</a></li>
                    </ul>
                </div>

                <div class="col-12 col-sm-4 col-lg-3">
                    <h6 class="footer-title">Liên hệ</h6>
                    <ul class="footer-contact">
                        <li><i class="bi bi-geo-alt-fill"></i> 168 Hai Bà Trưng, Q.1, TP.HCM</li>
                        <li><i class="bi bi-telephone-fill"></i> 1800 6868</li>
                        <li><i class="bi bi-envelope-fill"></i> info@saigonbistro.com</li>
                        <li><i class="bi bi-clock-fill"></i> 10:00 - 22:00 hàng ngày</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-inner">
                <p>© 2026 Saigon Bistro. Mọi quyền được bảo lưu.</p>
                <p class="footer-tech">Xây dựng bằng <span class="gold">♥</span> PHP · MySQL · Bootstrap</p>
            </div>
        </div>
    </div>
</footer>

<!-- BACK TO TOP -->
<button class="back-to-top" id="backToTop" title="Lên đầu trang">
    <i class="bi bi-chevron-up"></i>
</button>

<!-- AI CHATBOT -->
<div class="chatbot-widget" id="chatbotWidget">
    <button class="chatbot-toggle" id="chatbotToggle" title="Trợ lý AI">
        <i class="bi bi-robot"></i>
        <span class="chatbot-pulse"></span>
    </button>

    <div class="chatbot-panel" id="chatbotPanel">
        <div class="chatbot-header">
            <div class="chatbot-avatar"><i class="bi bi-robot"></i></div>
            <div class="chatbot-info">
                <h6>Trợ lý Saigon Bistro</h6>
                <span class="chatbot-status"><span class="dot"></span> Trực tuyến</span>
            </div>
            <button class="chatbot-close" id="chatbotClose"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="chatbot-messages" id="chatbotMessages">
            <div class="msg bot">
                <div class="msg-avatar"><i class="bi bi-robot"></i></div>
                <div class="msg-bubble">
                    Xin chào! Tôi là trợ lý AI của Saigon Bistro 🍜<br>
                    Tôi có thể giúp bạn tư vấn món ăn, kiểm tra giờ mở cửa, hoặc trả lời mọi thắc mắc!
                </div>
            </div>
            <div class="chatbot-suggestions">
                <button class="suggest-btn" onclick="sendSuggestion(this)">🍽️ Gợi ý món ngon</button>
                <button class="suggest-btn" onclick="sendSuggestion(this)">⏰ Giờ mở cửa?</button>
                <button class="suggest-btn" onclick="sendSuggestion(this)">📍 Địa chỉ nhà hàng</button>
                <button class="suggest-btn" onclick="sendSuggestion(this)">💰 Món ăn giá rẻ</button>
            </div>
        </div>

        <div class="chatbot-input-area">
            <input type="text" id="chatbotInput" placeholder="Nhập câu hỏi của bạn..." autocomplete="off">
            <button id="chatbotSend"><i class="bi bi-send-fill"></i></button>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>