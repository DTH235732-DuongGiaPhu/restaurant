-- ============================================================
-- CẬP NHẬT DATABASE: Hệ thống Đặt bàn (Table Reservation)
-- Chạy file này để thêm tính năng đặt bàn vào restaurant_db
-- ============================================================

USE restaurant_db;

-- ============================================================
-- BẢNG: table_locations — Vị trí bàn trong nhà hàng
-- ============================================================
CREATE TABLE IF NOT EXISTS table_locations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    description  VARCHAR(255),
    icon         VARCHAR(50)  DEFAULT 'geo-alt',
    capacity_min INT          DEFAULT 1,
    capacity_max INT          DEFAULT 10,
    is_active    TINYINT(1)   DEFAULT 1,
    sort_order   INT          DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO table_locations (name, description, icon, capacity_min, capacity_max, sort_order) VALUES
('Gần cửa sổ',       'View thành phố lung linh về đêm, ánh đèn phố đẹp mắt',   'window',          2, 6,  1),
('Sân vườn ngoài',   'Không gian mở, cây xanh tươi mát, gió nhẹ thư giãn',      'tree',            2, 8,  2),
('Phòng VIP riêng',  'Phòng riêng tư sang trọng, âm nhạc nhẹ, dịch vụ cao cấp','door-closed',     2, 12, 3),
('Khu vực trung tâm','Không khí sôi động, gần sân khấu âm nhạc sống',            'music-note-beamed',2, 8,  4),
('Tầng 2 toàn cảnh', 'Tầm nhìn panorama toàn nhà hàng, bàn cao, ánh sáng đẹp', 'layers',          2, 6,  5);

-- ============================================================
-- BẢNG: decor_options — Kiểu trang trí
-- ============================================================
CREATE TABLE IF NOT EXISTS decor_options (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    icon        VARCHAR(10)  DEFAULT '🌿',
    tag         VARCHAR(50)  DEFAULT NULL,
    is_active   TINYINT(1)   DEFAULT 1,
    sort_order  INT          DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO decor_options (name, description, icon, tag, sort_order) VALUES
('Lãng mạn',      'Nến lung linh, hoa hồng đỏ, dải voan trắng trải bàn',  '🌹', 'romantic',    1),
('Sinh nhật',     'Bóng bay sặc sỡ, banner chúc mừng, pháo giấy kim tuyến','🎂', 'birthday',    2),
('Kỷ niệm',       'Hoa tươi tinh tế, nến trắng, ruy băng vàng thanh lịch',  '💍', 'anniversary', 3),
('Tiệc gia đình', 'Trang trí ấm cúng, màu sắc vui tươi, không khí đoàn tụ','🎊', 'family',      4),
('Trang nhã',     'Hoa tươi xanh nhẹ, bình gốm sứ, tối giản sang trọng',   '🌿', 'elegant',     5);

-- ============================================================
-- BẢNG: reservations — Đặt bàn
-- ============================================================
CREATE TABLE IF NOT EXISTS reservations (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT          DEFAULT NULL,
    -- Thông tin khách
    customer_name    VARCHAR(100) NOT NULL,
    customer_phone   VARCHAR(20)  NOT NULL,
    customer_email   VARCHAR(100) DEFAULT NULL,
    -- Thông tin đặt bàn
    reservation_date DATE         NOT NULL,
    reservation_time TIME         NOT NULL,
    guest_count      INT          NOT NULL DEFAULT 2,
    location_id      INT          DEFAULT NULL,
    special_request  TEXT         DEFAULT NULL,
    -- Đặt món trước
    has_preorder     TINYINT(1)   DEFAULT 0,
    food_total       DECIMAL(12,0) DEFAULT 0,
    -- Decor
    decor_id         INT          DEFAULT NULL,
    decor_fee        DECIMAL(10,0) DEFAULT 0,
    decor_note       VARCHAR(255)  DEFAULT NULL,
    -- Tổng hóa đơn (chỉ tính khi có đặt món)
    total_amount     DECIMAL(12,0) DEFAULT 0,
    -- Trạng thái
    status           ENUM('pending','confirmed','seated','done','cancelled') DEFAULT 'pending',
    note_admin       TEXT         DEFAULT NULL,
    created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)           ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES table_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (decor_id)   REFERENCES decor_options(id)   ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- BẢNG: reservation_items — Món ăn đặt trước kèm bàn (tuỳ chọn)
-- ============================================================
CREATE TABLE IF NOT EXISTS reservation_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    dish_id      INT  NOT NULL,
    dish_name    VARCHAR(150) NOT NULL,
    dish_price   DECIMAL(10,0) NOT NULL,
    quantity     INT  NOT NULL DEFAULT 1,
    subtotal     DECIMAL(12,0) NOT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (dish_id)        REFERENCES dishes(id)        ON DELETE CASCADE
) ENGINE=InnoDB;
