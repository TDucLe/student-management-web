# 🔐 Authentication & Security — Student Management System

## Tổng quan

Hệ thống xác thực và bảo mật đa lớp cho ứng dụng quản lý sinh viên, bao gồm đăng nhập, đăng ký, phân quyền, chống tấn công, và quản lý phiên.

---

## ✅ Tính năng bảo mật

| # | Tính năng | Trạng thái | Mô tả |
|---|-----------|:----------:|-------|
| 1 | Mã hóa mật khẩu | ✅ | `password_hash()` với `PASSWORD_BCRYPT` |
| 2 | CSRF Protection | ✅ | Token tự động inject vào mọi form POST |
| 3 | Brute-force Protection | ✅ | Khóa tài khoản 15 phút sau 5 lần đăng nhập sai |
| 4 | Session Fixation | ✅ | `session_regenerate_id(true)` sau đăng nhập |
| 5 | SQL Injection | ✅ | PDO Prepared Statements toàn bộ |
| 6 | XSS Protection | ✅ | `htmlspecialchars()` cho mọi output |
| 7 | Phân quyền đăng ký | ✅ | Mặc định student, chỉ admin thay đổi role |
| 8 | Quên mật khẩu | ✅ | Hiển thị thông tin liên hệ Phòng QL Đào tạo |

---

## 📁 File liên quan

```
auth/
├── login.php              # Đăng nhập (CSRF + rate-limit + session regen)
├── register.php           # Đăng ký (chỉ student, CSRF, validate username)
├── forgot_password.php    # Thông tin liên hệ Phòng QL Đào tạo
├── reset_password.php     # Đặt lại mật khẩu (token 1h)
├── logout.php             # Xóa session
└── auth_helper.php        # Core functions: role check, nav, render

includes/
├── security_helper.php    # CSRF token + brute-force rate limiting
└── ...
```

---

## 🔑 Luồng đăng nhập

```
Người dùng → login.php
    ├── Kiểm tra CSRF token
    ├── Kiểm tra rate-limit (đã bị khóa?)
    ├── Truy vấn DB (username hoặc email)
    ├── password_verify()
    │
    ├── ❌ Sai → loginAttemptFailed() → hiện số lần còn lại
    │          → 5 lần sai → khóa 15 phút
    │
    └── ✅ Đúng → session_regenerate_id(true)
               → loginAttemptClear()
               → Lưu session (user_id, role, username, email)
               → Redirect → index.php → dashboard theo role
```

---

## 📝 Luồng đăng ký

```
Người dùng → register.php
    ├── Kiểm tra CSRF token
    ├── Validate: username (3-30 ký tự, a-z0-9_), email, password (≥6)
    ├── Kiểm tra trùng username/email
    │
    └── ✅ Hợp lệ → password_hash(PASSWORD_BCRYPT)
               → INSERT users (role = 'student')    ← luôn là student
               → Tạo record students
               → Thông báo thành công
```

> ⚠️ **Bảo mật**: Người dùng **không thể** tự chọn role admin/teacher khi đăng ký. Chỉ admin có quyền thay đổi role qua `admin/users_manage.php`.

---

## 🔒 Quên mật khẩu

Thay vì gửi email (không có SMTP trên localhost), trang `forgot_password.php` hiển thị **thông tin liên hệ Phòng Quản lý Đào tạo**:

- 🏢 Phòng Quản lý Đào tạo
- 📍 144 Xuân Thủy, Cầu Giấy, Hà Nội
- 📞 (024) 3754 7461
- ✉️ info@is.vnu.edu.vn
- 🕐 T2 – T6, 8:00 – 17:00

Kèm hướng dẫn chuẩn bị: mã SV/GV, CMND/CCCD, email đã đăng ký.

---

## 🛡️ CSRF Protection

### Cách hoạt động

1. **Server** tạo token random (`bin2hex(random_bytes(32))`) lưu trong `$_SESSION['csrf_token']`
2. **Header template** xuất `<meta name="csrf-token">` + script JS auto-inject
3. **JavaScript** tự động thêm `<input hidden name="csrf_token">` vào mọi form `method="POST"`
4. **Server** validate token khi nhận POST → từ chối nếu không khớp

### Functions (security_helper.php)

```php
csrfToken()      // Tạo/lấy token hiện tại
csrfField()      // Xuất <input hidden> cho form
csrfValidate()   // Validate token từ $_POST, regenerate sau validate
```

---

## 🚫 Brute-force Protection

### Cơ chế

- Đếm số lần đăng nhập sai theo identifier (username/email)
- **5 lần sai** → khóa tài khoản **15 phút**
- Hiển thị số lần thử còn lại cho người dùng
- Tự động xóa counter khi đăng nhập thành công

### Functions (security_helper.php)

```php
loginRateLimitCheck($id)   // Kiểm tra có bị khóa không
loginAttemptFailed($id)    // Ghi nhận lần sai, trả về số lần đã thử
loginLockRemaining($id)    // Số giây còn khóa (0 = không khóa)
loginAttemptClear($id)     // Xóa counter sau login thành công
```

---

## 🔄 Session Management

### Bảo vệ Session

| Kỹ thuật | Mô tả |
|----------|-------|
| `session_regenerate_id(true)` | Tạo session ID mới sau login, chống fixation |
| `session_start()` trước i18n | Đảm bảo ngôn ngữ không bị reset |
| Server-side storage | Session data lưu trên server, chỉ cookie ID |
| `session_destroy()` | Logout xóa toàn bộ data |

### Session Variables

```php
$_SESSION['user_id']        // ID người dùng
$_SESSION['username']       // Tên đăng nhập
$_SESSION['email']          // Email
$_SESSION['role']           // admin | teacher | student
$_SESSION['lang']           // vi | en
$_SESSION['csrf_token']     // Token CSRF hiện tại
```

---

## 👤 Phân quyền (Role-Based Access)

### 3 vai trò

| Role | Đăng ký | Quyền |
|------|---------|-------|
| `student` | ✅ Tự đăng ký | Xem thông tin cá nhân, điểm, lịch, nộp bài |
| `teacher` | ❌ Admin tạo | Quản lý lớp, điểm danh, nhập điểm, giao bài |
| `admin` | ❌ Admin tạo | Toàn quyền quản lý hệ thống |

### Hàm kiểm tra quyền

```php
requireRole('admin')       // Chỉ admin được truy cập
requireRole('teacher')     // Chỉ teacher
requireRole('student')     // Chỉ student
requireLogin()             // Phải đăng nhập
requireLogout()            // Phải chưa đăng nhập (trang auth)
```

---

## ⚙️ Cấu hình

### Database (db/db.php)

```php
$host = 'localhost';
$dbname = 'student_management';
$username = 'root';
$password = '';
```

### Bootstrap order (config.php)

```
1. Define APP_ROOT
2. Require db.php        → kết nối DB
3. session_start()       → khởi tạo session
4. security_helper.php   → CSRF, rate limit
5. i18n.php + init()     → đa ngôn ngữ
6. grades_helper.php     → tính điểm
7. notification_helper   → thông báo
8. auth_helper.php       → role check, render
```

---

## 🐛 Xử lý lỗi thường gặp

| Lỗi | Nguyên nhân | Giải pháp |
|-----|-------------|-----------|
| "Session expired" | CSRF token hết hạn | Tải lại trang, thử lại |
| "Account locked" | 5 lần đăng nhập sai | Đợi 15 phút |
| Ngôn ngữ bị reset | `session_start()` chạy sau i18n | Đã sửa trong config.php |
| Thông báo không hiện | `$notifications` không có trong footer | Dùng `$GLOBALS['__notifications']` |
| Dropdown bị che | `backdrop-filter` tạo stacking context | Dropdown render ngoài body |

---
