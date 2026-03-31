-- ============================================================
-- MIGRATION: Hệ thống Đặt bàn - Saigon Bistro
-- Chạy file này sau khi đã có database restaurant_db
-- ============================================================

USE restaurant_db;

-- ============================================================
-- BẢNG: reservations (Đặt bàn)
-- ============================================================
CREATE TABLE IF NOT EXISTS reservations (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT DEFAULT NULL,
    customer_name     VARCHAR(100) NOT NULL,
    customer_phone    VARCHAR(20)  NOT NULL,
    customer_email    VARCHAR(100) DEFAULT NULL,
    reservation_date  DATE         NOT NULL,
    reservation_time  TIME         NOT NULL,
    guests            INT          NOT NULL DEFAULT 2,
    table_area        ENUM('window','terrace','indoor','private','garden') NOT NULL DEFAULT 'indoor',
    has_preorder      TINYINT(1)   DEFAULT 0,
    decor_option      ENUM('none','birthday','romantic','baby','business','simple') DEFAULT 'none',
    decor_fee         DECIMAL(10,0) DEFAULT 0,
    food_total        DECIMAL(12,0) DEFAULT 0,
    total_amount      DECIMAL(12,0) DEFAULT 0,
    note              TEXT DEFAULT NULL,
    status            ENUM('pending','confirmed','seated','completed','cancelled') DEFAULT 'pending',
    payment_method    ENUM('cash','transfer','none') DEFAULT 'none',
    payment_status    ENUM('unpaid','paid','not_required') DEFAULT 'not_required',
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG: reservation_items (Món đặt trước)
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
    FOREIGN KEY (dish_id)        REFERENCES dishes(id)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for performance
CREATE INDEX idx_reservations_date   ON reservations(reservation_date);
CREATE INDEX idx_reservations_status ON reservations(status);
CREATE INDEX idx_reservations_user   ON reservations(user_id);
