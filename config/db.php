<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
// ============================================================
// Cấu hình kết nối database
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurant_db');
define('DB_CHARSET', 'utf8mb4');

// Cấu hình Claude AI Chatbot
define('CLAUDE_API_KEY', 'YOUR_CLAUDE_API_KEY_HERE'); // Thay bằng API key thực

// Cấu hình website
define('SITE_NAME', 'Saigon Bistro');
define('SITE_URL', 'http://localhost/restaurant');
define('SITE_PHONE', '1800 6868');
define('SITE_EMAIL', 'info@saigonbistro.com');
define('SITE_ADDRESS', '168 Hai Bà Trưng, Quận 1, TP.HCM');

// Kết nối database (PDO)
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="background:#1a0000;color:#ff6b6b;padding:30px;font-family:monospace;text-align:center;">
        <h2>⚠ Lỗi Kết Nối Database</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p>Vui lòng kiểm tra lại cấu hình trong <code>config/db.php</code></p>
    </div>');
}
?>
