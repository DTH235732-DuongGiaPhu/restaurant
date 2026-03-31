# 🍽️ SAIGON BISTRO — Website Đặt Món Nhà Hàng
## Đồ án Lập trình Web | HTML · CSS · JavaScript · PHP · MySQL

---

## 📋 GIỚI THIỆU

**Saigon Bistro** là website đặt món nhà hàng cao cấp, xây dựng bằng:
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5, jQuery
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **AI Chatbot:** Claude API (Anthropic) — có fallback offline

---

## 🚀 CÀI ĐẶT

### Bước 1 — Yêu cầu hệ thống
- XAMPP / WAMP / Laragon (PHP 7.4+, MySQL 5.7+)
- Trình duyệt hiện đại (Chrome, Firefox, Edge)

### Bước 2 — Copy source code
```
Giải nén → Copy thư mục `restaurant` → dán vào `htdocs` (XAMPP) hoặc `www` (WAMP)
```

### Bước 3 — Import Database
1. Mở **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Tạo database mới tên `restaurant_db` (UTF8mb4)
3. Import file `database.sql`

### Bước 4 — Cấu hình
Mở file `config/db.php`, kiểm tra:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');    // username MySQL
define('DB_PASS', '');         // password MySQL (mặc định trống với XAMPP)
define('DB_NAME', 'restaurant_db');
define('SITE_URL', 'http://localhost/restaurant'); // URL website của bạn
```

### Bước 5 — Truy cập website
- **Website:** `http://localhost/restaurant/`
- **Admin:** `http://localhost/restaurant/admin/`

---

## 🔑 TÀI KHOẢN DEMO

| Loại | Email | Mật khẩu |
|------|-------|-----------|
| Admin | admin@saigonbistro.com | password |
| User | user@gmail.com | password |

---

## 🤖 TÍCH HỢP AI CHATBOT

### Sử dụng Claude API (khuyến nghị):
1. Đăng ký tại [console.anthropic.com](https://console.anthropic.com)
2. Tạo API Key
3. Mở `config/db.php`, thay:
```php
define('CLAUDE_API_KEY', 'sk-ant-xxxxx'); // Dán API key vào đây
```

### Chế độ Offline (không cần API key):
Chatbot tự động chuyển sang **chế độ offline** với các câu trả lời được lập trình sẵn cho:
- Tư vấn món ăn, giá cả
- Giờ mở cửa, địa chỉ
- Hướng dẫn đặt hàng, giao hàng
- Các câu hỏi thường gặp

---

## 🗂️ CẤU TRÚC DỰ ÁN

```
restaurant/
├── config/
│   └── db.php              # Cấu hình DB & API
├── includes/
│   ├── header.php          # Header chung
│   ├── footer.php          # Footer + AI Chatbot
│   └── functions.php       # Hàm tiện ích
├── assets/
│   ├── css/style.css       # CSS chính (Dark Luxury theme)
│   ├── js/main.js          # JavaScript chính
│   └── images/             # Ảnh mặc định
├── admin/
│   ├── index.php           # Dashboard
│   ├── orders.php          # Quản lý đơn hàng
│   ├── dishes.php          # Quản lý món ăn
│   ├── categories.php      # Quản lý danh mục
│   ├── news.php            # Quản lý tin tức
│   ├── users.php           # Quản lý người dùng
│   ├── includes/           # Sidebar, topbar
│   └── assets/             # CSS/JS admin
├── uploads/
│   ├── dishes/             # Ảnh món ăn upload
│   └── news/               # Ảnh tin tức upload
├── index.php               # Trang chủ
├── menu.php                # Thực đơn
├── cart.php                # Giỏ hàng
├── checkout.php            # Đặt hàng
├── orders.php              # Lịch sử đơn hàng
├── news.php                # Tin tức
├── news-detail.php         # Chi tiết tin
├── contact.php             # Liên hệ
├── login.php               # Đăng nhập
├── register.php            # Đăng ký
├── logout.php              # Đăng xuất
├── cart-action.php         # AJAX giỏ hàng
├── chatbot.php             # AI Chatbot API
└── database.sql            # Script tạo CSDL
```

---

## ✨ TÍNH NĂNG

### Người dùng:
- ✅ Trang chủ đẹp với Hero section, Featured dishes, Testimonials
- ✅ Thực đơn với bộ lọc danh mục và tìm kiếm real-time
- ✅ Giỏ hàng (Session) — thêm/sửa/xóa không reload
- ✅ Đặt hàng với form validation
- ✅ Đăng ký / Đăng nhập / Đăng xuất (Session + Cookie)
- ✅ Lịch sử đơn hàng
- ✅ Trang tin tức & chi tiết tin
- ✅ Trang liên hệ
- ✅ AI Chatbot tư vấn (Claude API + Offline fallback)

### Admin Panel:
- ✅ Dashboard với biểu đồ doanh thu (Chart.js)
- ✅ Quản lý đơn hàng — cập nhật trạng thái, xem chi tiết
- ✅ Quản lý món ăn — CRUD, upload ảnh, đánh dấu nổi bật
- ✅ Quản lý danh mục
- ✅ Quản lý tin tức
- ✅ Quản lý người dùng — phân quyền, khóa tài khoản

### Kỹ thuật:
- ✅ **Sessions** — giỏ hàng, đăng nhập
- ✅ **Cookies** — ghi nhớ đăng nhập
- ✅ **AJAX** — thêm giỏ hàng, cập nhật số lượng, chatbot
- ✅ **PDO** — kết nối database an toàn (prepared statements)
- ✅ **Password hashing** — bcrypt
- ✅ **XSS Protection** — htmlspecialchars
- ✅ **Responsive** — Bootstrap 5

---

## 👥 PHÂN CÔNG NHÓM (gợi ý)

| Thành viên | Phần việc |
|------------|-----------|
| Thành viên 1 | Trang chủ, Menu, CSS/Design |
| Thành viên 2 | Giỏ hàng, Checkout, Cart AJAX |
| Thành viên 3 | Đăng nhập, Đăng ký, Session/Cookie |
| Thành viên 4 | Admin Panel (Dashboard, Orders) |
| Thành viên 5 | Admin (Dishes, Categories, News) + Chatbot AI |

---

## 📞 HỖ TRỢ

Email: info@saigonbistro.com | Hotline: 1800 6868

---
Chatbot tích hợp Claude AI — khi có API Key thật sẽ trả lời thông minh hoàn toàn. Hiện demo bằng Offline Mode với logic xử lý từ khóa.##
---