# ORBIT v2 — Hướng dẫn cài đặt trên XAMPP

> Ứng dụng lên lịch đăng video đa kênh cho Facebook, TikTok, YouTube.  
> Chạy 24/7 tự động · Tích hợp AI · Hệ thống tương tác & auto-reply.

---

## Yêu cầu hệ thống

| Thành phần | Phiên bản |
|---|---|
| XAMPP (Apache + MySQL) | 8.0 trở lên |
| PHP | 8.0+ |
| MySQL | 5.7+ |
| Trình duyệt | Chrome, Firefox, Edge mới nhất |

> Node.js **không cần thiết** — frontend đã được build sẵn trong gói này.

---

## Bước 1 — Giải nén và copy vào XAMPP

Giải nén file tải về, bạn sẽ có thư mục **`orbit/`**.

Copy thư mục `orbit/` vào thư mục `htdocs` của XAMPP:

| Hệ điều hành | Đường dẫn htdocs |
|---|---|
| Windows | `C:\xampp\htdocs\` |
| macOS | `/Applications/XAMPP/htdocs/` |
| Linux | `/opt/lampp/htdocs/` |

Kết quả: `C:\xampp\htdocs\orbit\`

---

## Bước 2 — Khởi động XAMPP

1. Mở **XAMPP Control Panel**
2. Nhấn **Start** cho **Apache**
3. Nhấn **Start** cho **MySQL**

---

## Bước 3 — Tạo database

1. Mở trình duyệt vào: **`http://localhost/phpmyadmin`**
2. Chọn tab **Import** (hoặc nhấn **Nhập** nếu giao diện tiếng Việt)
3. Nhấn **Chọn tệp** → chọn file **`orbit/orbit.sql`**
4. Cuộn xuống nhấn **Thực hiện (Go)**

Database `orbit` và tất cả bảng sẽ được tạo tự động.

---

## Bước 4 — Cấu hình database (nếu cần)

Mở file `orbit/api/config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'orbit');
define('DB_USER', 'root');   // Tên user MySQL
define('DB_PASS', '');       // XAMPP mặc định để trống
```

> Thường không cần sửa nếu dùng XAMPP mặc định.

---

## Bước 5 — Bật mod_rewrite (Apache)

1. Mở **XAMPP Control Panel** → Apache → **Config** → `httpd.conf`
2. Tìm dòng: `#LoadModule rewrite_module modules/mod_rewrite.so`
3. Xóa dấu `#` ở đầu → `LoadModule rewrite_module modules/mod_rewrite.so`
4. Tìm đoạn `<Directory "C:/xampp/htdocs">` và sửa `AllowOverride None` → `AllowOverride All`
5. Lưu file → **Restart Apache**

---

## Bước 6 — Truy cập ứng dụng

Mở trình duyệt vào:

```
http://localhost/orbit/
```

---

## Lần đầu sử dụng

1. Trang **Thiết lập ban đầu** sẽ hiện ra tự động
2. Nhập **tên đăng nhập** và **mật khẩu** cho tài khoản Admin
3. Nhấn **Hoàn tất thiết lập**
4. Đăng nhập và bắt đầu sử dụng!

---

## Tính năng chính

| Tính năng | Mô tả |
|---|---|
| Lịch đăng bài | Đặt lịch đăng video lên Facebook / TikTok / YouTube |
| Tương tác | Xem bình luận, trả lời thủ công hoặc tự động |
| Auto-reply | Tạo quy tắc tự động trả lời theo từ khóa |
| Tải TikTok | Tải video TikTok/Douyin không logo |
| AI Chatbot | Hỏi đáp với AI (cần OpenAI API Key) |
| Thống kê | Dashboard tổng quan hoạt động |

---

## Cài đặt AI (tùy chọn)

1. Vào **Cài đặt** trong ứng dụng
2. Nhập **OpenAI API Key**
3. Nhấn lưu

---

## Cấu trúc thư mục

```
orbit/
├── index.html              ← React App (đã build sẵn)
├── assets/                 ← JS + CSS đã minify
├── orbit.sql               ← Schema MySQL (import 1 lần)
├── .htaccess               ← Apache routing
└── api/
    ├── index.php           ← Router chính
    ├── helpers.php         ← Tiện ích chung
    ├── config/
    │   └── database.php    ← Cấu hình MySQL
    └── routes/
        ├── auth.php        ← Đăng nhập / đăng xuất
        ├── videos.php      ← Quản lý video
        ├── platforms.php   ← Nền tảng kết nối
        ├── schedules.php   ← Lịch đăng bài
        ├── engagement.php  ← Bình luận & Auto-reply
        ├── activity.php    ← Nhật ký hoạt động
        ├── settings.php    ← Cài đặt hệ thống
        ├── stats.php       ← Thống kê dashboard
        ├── tiktok.php      ← Tải TikTok/Douyin
        ├── ai.php          ← AI Chatbot
        └── health.php      ← Ping / health check
```

---

## Khắc phục sự cố

| Lỗi | Giải pháp |
|---|---|
| Trang trắng khi vào `/orbit/` | Kiểm tra Apache đã chạy; `.htaccess` cần `mod_rewrite` |
| Lỗi 500 | Xem `C:\xampp\apache\logs\error.log` |
| Lỗi kết nối database | MySQL chưa chạy hoặc sai thông tin trong `database.php` |
| API trả về 404 | `mod_rewrite` chưa bật hoặc `AllowOverride All` chưa set |
| Không tải được TikTok | PHP extension `curl` cần bật (XAMPP thường đã bật sẵn) |
| Trang bị redirect lạ | Xóa cache trình duyệt (Ctrl+Shift+R) |

---

**ORBIT v2** · PHP + MySQL · Chạy cục bộ với XAMPP
