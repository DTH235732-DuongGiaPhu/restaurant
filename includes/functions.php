<?php
// ============================================================
// Các hàm tiện ích chung
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Format tiền tệ VNĐ ---
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

// --- Sanitize input ---
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// --- Tạo slug ---
function makeSlug($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $from = ['à','á','â','ã','ä','å','ă','ắ','ặ','ẳ','ẵ','ấ','ầ','ẩ','ẫ','è','é','ê','ề','ế','ệ','ể','ễ','ì','í','î','ï','ò','ó','ô','õ','ö','ø','ố','ồ','ộ','ổ','ỗ','ú','ù','û','ü','ứ','ừ','ự','ử','ữ','ý','ỳ','ỹ','ỵ','ỷ','ñ','ç','đ',' '];
    $to   = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','y','y','y','y','y','n','c','d','-'];
    return str_replace($from, $to, $str);
}

// --- Kiểm tra đăng nhập ---
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// --- Kiểm tra admin ---
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// --- Yêu cầu đăng nhập ---
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// --- Yêu cầu admin ---
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

// --- Lấy giỏ hàng ---
function getCart() {
    return $_SESSION['cart'] ?? [];
}

// --- Số lượng giỏ hàng ---
function getCartCount() {
    $cart = getCart();
    return array_sum(array_column($cart, 'quantity'));
}

// --- Tổng tiền giỏ hàng ---
function getCartTotal() {
    $cart = getCart();
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// --- Flash message ---
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// --- Hiển thị flash ---
function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $icon = $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'error' ? '✕' : 'ℹ');
        echo '<div class="flash-message flash-' . $flash['type'] . '">' . $icon . ' ' . $flash['message'] . '</div>';
    }
}

// --- Lấy ảnh món ăn ---
function getDishImage($image) {
    if ($image && file_exists(__DIR__ . '/../uploads/dishes/' . $image)) {
        return BASE_URL . '/uploads/dishes/' . $image;
    }
    return BASE_URL . '/assets/images/dish-default.jpg';
}

// --- Lấy ảnh tin tức ---
function getNewsImage($image) {
    if ($image && file_exists(__DIR__ . '/../uploads/news/' . $image)) {
        return BASE_URL . '/uploads/news/' . $image;
    }
    return BASE_URL . '/assets/images/news-default.jpg';
}

// --- Truncate text ---
function truncate($text, $length = 100) {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}

// --- Upload ảnh ---
function uploadImage($file, $folder = 'dishes') {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return ['error' => 'Chỉ chấp nhận file JPG, PNG, WEBP'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'File không được vượt quá 5MB'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $uploadPath = __DIR__ . '/../uploads/' . $folder . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $filename];
    }
    return ['error' => 'Upload thất bại'];
}

// Định nghĩa BASE_URL
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Tự động detect base URL
    define('BASE_URL', SITE_URL);
}
?>
