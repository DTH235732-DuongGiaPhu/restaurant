-- ============================================================
-- DATABASE: restaurant_db
-- Mô tả: Hệ thống đặt món nhà hàng
-- ============================================================

CREATE DATABASE IF NOT EXISTS restaurant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_db;

-- ============================================================
-- BẢNG: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- BẢNG: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'utensils',
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- BẢNG: dishes
-- ============================================================
CREATE TABLE IF NOT EXISTS dishes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,0) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- BẢNG: orders
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    note TEXT DEFAULT NULL,
    total DECIMAL(12,0) NOT NULL,
    status ENUM('pending','confirmed','cooking','delivering','done','cancelled') DEFAULT 'pending',
    payment_method ENUM('cash','transfer') DEFAULT 'cash',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- BẢNG: order_items
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    dish_id INT NOT NULL,
    dish_name VARCHAR(150) NOT NULL,
    dish_price DECIMAL(10,0) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(12,0) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- BẢNG: news
-- ============================================================
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    image VARCHAR(255) DEFAULT NULL,
    views INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- BẢNG: reviews
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dish_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT(1) NOT NULL DEFAULT 5,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DỮ LIỆU MẪU
-- ============================================================

-- Admin account (password: admin123)
INSERT INTO users (name, email, phone, password, role) VALUES
('Administrator', 'admin@saigonbistro.com', '0901234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Nguyễn Văn An', 'user@gmail.com', '0912345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Trần Thị Bình', 'binh@gmail.com', '0923456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Categories
INSERT INTO categories (name, icon, sort_order) VALUES
('Khai vị', 'leaf', 1),
('Món chính', 'fire', 2),
('Lẩu & Nướng', 'flame', 3),
('Hải sản', 'fish', 4),
('Tráng miệng', 'star', 5),
('Đồ uống', 'coffee', 6);

-- Dishes
INSERT INTO dishes (category_id, name, description, price, is_featured, status) VALUES
-- Khai vị
(1, 'Gỏi cuốn tôm thịt', 'Gỏi cuốn truyền thống với tôm tươi, thịt luộc, rau sống, bún, chấm tương hoisin', 65000, 1, 'active'),
(1, 'Chả giò hải sản', 'Chả giò giòn rụm nhân tôm mực, cà rốt, bún tàu, hành lá thơm ngon', 75000, 0, 'active'),
(1, 'Súp bào ngư vi cá', 'Súp bào ngư cao cấp kết hợp vi cá thanh đạm, nấu theo phong cách Á Đông', 185000, 1, 'active'),
(1, 'Salad ức gà nướng', 'Ức gà nướng thảo mộc trên nền rau xanh tươi, sốt chanh dây đặc biệt', 95000, 0, 'active'),

-- Món chính
(2, 'Bò bít tết sốt tiêu đen', 'Thăn bò Úc 200g áp chảo medium, sốt tiêu đen đặc biệt, kèm khoai tây chiên', 285000, 1, 'active'),
(2, 'Cơm chiên Dương Châu', 'Cơm chiên đặc trưng với tôm, thịt, trứng, hành lá, dứa - công thức bí truyền', 115000, 0, 'active'),
(2, 'Mì xào hải sản', 'Mì xào thập cẩm tôm mực sò điệp với rau củ tươi, nước sốt đậm đà', 135000, 1, 'active'),
(2, 'Cá lóc hấp bần', 'Cá lóc tươi hấp với mắm bần, gừng, hành lá - đặc sản miền Tây', 195000, 0, 'active'),
(2, 'Sườn non nướng BBQ', 'Sườn non heo nướng BBQ kiểu Mỹ, ướp 24 giờ, mềm tan, sốt ngọt cay', 245000, 1, 'active'),
(2, 'Gà nướng ngũ vị', 'Gà ta nướng ngũ vị hương, da giòn vàng, thịt ngọt, kèm cơm trắng', 175000, 0, 'active'),

-- Lẩu & Nướng
(3, 'Lẩu Thái hải sản', 'Lẩu Thái chua cay với tôm, mực, ngao, nấm linh chi và rau tươi (2 người)', 320000, 1, 'active'),
(3, 'Lẩu bò nhúng dấm', 'Bò Mỹ thái mỏng nhúng giấm thanh ngọt, kèm bún, rau thơm đầy đủ', 285000, 0, 'active'),
(3, 'Nướng than hoa tổng hợp', 'Set nướng than hoa: bò, tôm, mực, rau củ - trải nghiệm tự nướng (2 người)', 395000, 1, 'active'),

-- Hải sản
(4, 'Tôm hùm nướng bơ tỏi', 'Tôm hùm Canada 500g nướng bơ tỏi thơm béo, hấp dẫn', 650000, 1, 'active'),
(4, 'Cua rang me', 'Cua biển tươi 700g rang me chua ngọt, thơm đặc trưng', 420000, 0, 'active'),
(4, 'Mực nhồi thịt hấp', 'Mực ống lớn nhồi thịt tôm, hấp chín, chấm mắm gừng', 195000, 0, 'active'),

-- Tráng miệng
(5, 'Chè thập cẩm', 'Chè đặc biệt với đậu xanh, hạt sen, long nhãn, sương sa, nước cốt dừa', 55000, 0, 'active'),
(5, 'Bánh flan caramel', 'Bánh flan mềm mịn tự làm, nước caramel đắng ngọt hài hòa', 45000, 1, 'active'),
(5, 'Kem dừa Thái', 'Kem dừa Thái tươi nguyên trái, phủ đậu phộng và xi rô ngọt', 65000, 0, 'active'),

-- Đồ uống
(6, 'Nước ép dưa hấu', 'Dưa hấu tươi ép lạnh, không đường, thanh mát', 45000, 0, 'active'),
(6, 'Cà phê sữa đá', 'Cà phê phin truyền thống pha sữa đặc, đá viên', 35000, 0, 'active'),
(6, 'Sinh tố bơ', 'Bơ chín pha sữa tươi và đường, béo ngậy', 55000, 1, 'active'),
(6, 'Trà đào cam sả', 'Trà đào hương cam sả tươi, đá lạnh, thơm dịu', 55000, 0, 'active');

-- News
INSERT INTO news (title, excerpt, content, views) VALUES
('Khai trương chi nhánh Quận 7 - Ưu đãi đặc biệt 50%', 'Saigon Bistro hân hạnh thông báo khai trương chi nhánh mới tại Quận 7 với hàng loạt ưu đãi hấp dẫn lên đến 50%.', '<p>Saigon Bistro hân hạnh thông báo khai trương chi nhánh mới nhất tại <strong>123 Nguyễn Thị Thập, Quận 7, TP.HCM</strong>.</p><p>Để chào mừng sự kiện này, chúng tôi áp dụng <strong>ưu đãi giảm 50%</strong> cho toàn bộ thực đơn trong 3 ngày khai trương (20-22/03/2026).</p><p>Ngoài ra, khách hàng đặt bàn trước sẽ nhận được một phần tráng miệng miễn phí và thẻ thành viên VIP với nhiều đặc quyền hấp dẫn.</p>', 1250),
('Thực đơn mùa xuân 2026 - Hương vị mới lạ', 'Chúng tôi vừa cập nhật thực đơn mùa xuân với hơn 20 món mới đặc sắc, kết hợp ẩm thực truyền thống và hiện đại.', '<p>Mùa xuân 2026, Saigon Bistro trân trọng giới thiệu <strong>thực đơn mùa xuân</strong> với hơn 20 món mới đặc sắc.</p><p>Các món mới được lấy cảm hứng từ ẩm thực miền Tây Nam Bộ kết hợp kỹ thuật nấu nướng hiện đại của Pháp và Nhật Bản.</p><p>Điểm nhấn là <em>Bộ set thưởng xuân</em> dành cho nhóm 4-6 người với đầy đủ khai vị, món chính và tráng miệng.</p>', 876),
('Saigon Bistro đạt giải "Nhà hàng xuất sắc 2025"', 'Chúng tôi tự hào được Hiệp hội Ẩm thực Việt Nam trao tặng giải thưởng Nhà hàng Xuất sắc năm 2025.', '<p>Saigon Bistro vừa vinh dự nhận giải thưởng <strong>"Nhà hàng Xuất sắc 2025"</strong> do Hiệp hội Ẩm thực Việt Nam trao tặng.</p><p>Giải thưởng ghi nhận nỗ lực không ngừng của đội ngũ đầu bếp và nhân viên trong việc duy trì chất lượng ẩm thực và dịch vụ khách hàng xuất sắc.</p><p>Chúng tôi xin chân thành cảm ơn quý khách hàng đã tin tưởng và đồng hành cùng chúng tôi suốt thời gian qua.</p>', 2341),
('Hướng dẫn đặt bàn trực tuyến và nhận ưu đãi', 'Đặt bàn trực tuyến qua website nhận ngay voucher giảm 10% cho lần đến đầu tiên và tích điểm thành viên.', '<p>Saigon Bistro ra mắt tính năng <strong>đặt bàn trực tuyến</strong> giúp quý khách đặt bàn dễ dàng mọi lúc, mọi nơi.</p><p>Khách hàng đặt bàn qua website sẽ nhận ngay <strong>voucher giảm 10%</strong> cho lần dùng bữa đầu tiên.</p><p>Hệ thống tích điểm thành viên cũng được nâng cấp: cứ 100.000đ chi tiêu = 1 điểm, tích đủ 50 điểm đổi được voucher 100.000đ.</p>', 654);
