-- ============================================================
-- CẬP NHẬT DATABASE — Saigon Bistro
-- Chạy file này sau khi đã có database_update.sql gốc
-- ============================================================

USE restaurant_db;

-- ── 1. BẢNG COUPONS (Mã giảm giá) ───────────────────────────
CREATE TABLE IF NOT EXISTS coupons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(50) NOT NULL UNIQUE,
    type        ENUM('percent','fixed') DEFAULT 'percent',
    value       DECIMAL(10,2) NOT NULL,
    min_order   DECIMAL(12,0) DEFAULT 0,
    max_uses    INT DEFAULT NULL COMMENT 'NULL = không giới hạn',
    used_count  INT DEFAULT 0,
    expires_at  DATE DEFAULT NULL COMMENT 'NULL = không hết hạn',
    is_active   TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 2. THÊM CỘT VÀO BẢNG ORDERS ────────────────────────────
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(50) DEFAULT NULL AFTER payment_method,
    ADD COLUMN IF NOT EXISTS discount    DECIMAL(12,0) DEFAULT 0  AFTER coupon_code;

-- ── 3. DỮ LIỆU MẪU COUPON ───────────────────────────────────
INSERT INTO coupons (code, type, value, min_order, max_uses, expires_at) VALUES
('SAIGON10',  'percent', 10,  100000, 100,  '2026-12-31'),
('WELCOME50K','fixed',   50000, 200000, 50, '2026-12-31'),
('VIP20',     'percent', 20,  300000, NULL, '2026-12-31'),
('FREESHIP',  'fixed',   30000, 150000, 200, '2026-06-30')
ON DUPLICATE KEY UPDATE id = id;
