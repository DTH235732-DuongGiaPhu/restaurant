-- ============================================================
-- DATABASE UPDATE: Tính năng Đặt bàn trước
-- Import file này sau khi đã import database.sql
-- ============================================================

USE restaurant_db;

-- ============================================================
-- BẢNG: table_positions — Vị trí bàn
-- ============================================================
CREATE TABLE IF NOT EXISTS table_positions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    icon        VARCHAR(60) DEFAULT 'geo-alt',
    max_guests  INT DEFAULT 10,
    sort_order  INT DEFAULT 0,
    is_active   TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO table_positions (name, description, icon, max_guests, sort_order) VALUES
('Cửa sổ nhìn ra phố',  'View đường phố Sài Gòn, ánh sáng tự nhiên, không gian mở',       'window',           8,  1),
('Trong nhà – Sang trọng','Không gian điều hòa, yên tĩnh, phù hợp buổi tối lãng mạn',   'house-door',       10, 2),
('Sân vườn ngoài trời', 'Không gian xanh mát, cây cảnh, phù hợp tiệc nhóm ban ngày',     'tree',             12, 3),
('Phòng VIP riêng',     'Phòng riêng biệt, riêng tư tuyệt đối, phù hợp họp mặt quan trọng','door-closed',   20, 4);

-- ============================================================
-- BẢNG: decor_types — Kiểu trang trí
-- ============================================================
CREATE TABLE IF NOT EXISTS decor_types (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    icon        VARCHAR(60) DEFAULT 'stars',
    color_hint  VARCHAR(20) DEFAULT '#c9a96e',
    sort_order  INT DEFAULT 0,
    is_active   TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO decor_types (name, description, icon, color_hint, sort_order) VALUES
('Lãng mạn',   'Nến lung linh, hoa hồng đỏ, dải voan trắng – Lý tưởng cho hẹn hò',         'heart-fill',          '#e05252', 1),
('Sinh nhật',  'Bóng bay rực rỡ, banner chúc mừng, hoa giấy rơi – Vui tươi, ấm áp',        'cake2-fill',          '#e0a652', 2),
('Kỷ niệm',    'Champagne tower, vòng hoa, nến bạc – Tôn vinh những khoảnh khắc đặc biệt',  'trophy-fill',         '#c9a96e', 3),
('Hội nghị',   'Setup bàn họp, namecard, máy chiếu mini – Chuyên nghiệp & Hiện đại',         'briefcase-fill',      '#52b788', 4),
('Gia đình',   'Decor ấm cúng, bóng bay pastel, banner Welcome – Quây quần sum họp',        'people-fill',         '#6ea8c9', 5);

-- ============================================================
-- BẢNG: reservations — Đặt bàn
-- ============================================================
CREATE TABLE IF NOT EXISTS reservations (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT DEFAULT NULL,
    customer_name       VARCHAR(100) NOT NULL,
    customer_phone      VARCHAR(20) NOT NULL,
    customer_email      VARCHAR(100) DEFAULT NULL,
    reservation_date    DATE NOT NULL,
    reservation_time    TIME NOT NULL,
    num_guests          INT NOT NULL DEFAULT 2,
    position_id         INT DEFAULT NULL,
    has_preorder        TINYINT(1) DEFAULT 0,  -- 0: chỉ đặt bàn | 1: có đặt món
    decor_id            INT DEFAULT NULL,
    food_total          DECIMAL(12,0) DEFAULT 0,
    decor_fee           DECIMAL(10,0) DEFAULT 0,   -- 0 hoặc 100000
    total               DECIMAL(12,0) DEFAULT 0,
    note                TEXT DEFAULT NULL,
    status              ENUM('pending','confirmed','seated','done','cancelled') DEFAULT 'pending',
    payment_method      ENUM('cash','transfer','none') DEFAULT 'none',
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)      REFERENCES users(id)           ON DELETE SET NULL,
    FOREIGN KEY (position_id)  REFERENCES table_positions(id) ON DELETE SET NULL,
    FOREIGN KEY (decor_id)     REFERENCES decor_types(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- BẢNG: reservation_items — Món đặt trước (nếu has_preorder=1)
-- ============================================================
CREATE TABLE IF NOT EXISTS reservation_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id  INT NOT NULL,
    dish_id         INT NOT NULL,
    dish_name       VARCHAR(150) NOT NULL,
    dish_price      DECIMAL(10,0) NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    subtotal        DECIMAL(12,0) NOT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (dish_id)        REFERENCES dishes(id)       ON DELETE CASCADE
) ENGINE=InnoDB;
