# 🎓 Student Management System
## Hệ thống Quản lý Sinh viên — Trường Quốc tế, ĐHQGHN

---

## 📋 Giới thiệu

Hệ thống quản lý sinh viên toàn diện với 3 vai trò: **Admin**, **Teacher**, **Student**. Hỗ trợ quản lý môn học, lớp học, điểm danh, điểm số, lịch học, bài tập, đơn xin nghỉ, thông báo và thống kê.

**Công nghệ**: PHP 8+ · MySQL · HTML/CSS/JS · Chart.js · XAMPP

---

## 📂 Cấu trúc dự án

```
student-management-web/
│
├── config.php                 # Bootstrap: DB, session, i18n, security, auth
│
├── db/
│   ├── db.php                 # Kết nối MySQL (PDO + mysqli)
│   └── init.sql               # Script tạo bảng
│
├── auth/
│   ├── login.php              # Đăng nhập (CSRF, rate-limit, session regeneration)
│   ├── register.php           # Đăng ký (chỉ student, CSRF)
│   ├── forgot_password.php    # Thông tin liên hệ phòng QL
│   ├── reset_password.php     # Đặt lại mật khẩu (token-based)
│   ├── logout.php             # Đăng xuất (xóa session)
│   └── auth_helper.php        # Hàm tiện ích: role check, nav, render
│
├── includes/
│   ├── i18n.php               # Hệ thống đa ngôn ngữ (VI/EN)
│   ├── security_helper.php    # CSRF token, brute-force rate limiting
│   ├── grades_helper.php      # Tính điểm GPA
│   ├── notification_helper.php # Gửi thông báo tự động
│   └── notif_seen.php         # AJAX endpoint đánh dấu đã đọc thông báo
│
├── admin/
│   ├── dashboard.php          # Tổng quan: thống kê, biểu đồ
│   ├── users_manage.php       # Quản lý tài khoản (CRUD, phân role)
│   ├── students_manage.php    # Quản lý hồ sơ sinh viên
│   ├── teachers_manage.php    # Quản lý hồ sơ giảng viên
│   ├── courses_manage.php     # Quản lý môn học
│   ├── classes_manage.php     # Quản lý lớp học
│   ├── class_detail.php       # Chi tiết lớp: TKB, sinh viên, điểm
│   ├── semesters_manage.php   # Quản lý kỳ học
│   ├── rooms_manage.php       # Quản lý phòng học
│   ├── attendance_manage.php  # Xem/xóa điểm danh (lọc kỳ, lớp, ngày)
│   ├── grades_manage.php      # Xem/xóa điểm (lọc kỳ, lớp)
│   ├── assignments_manage.php # Quản lý bài tập
│   ├── leave_manage.php       # Duyệt đơn xin nghỉ
│   └── stats_manage.php       # Thống kê toàn hệ thống
│
├── teacher/
│   ├── courses.php            # Môn học giảng viên phụ trách
│   ├── classes.php            # Lớp học đang dạy
│   ├── class_students.php     # Danh sách SV trong lớp
│   ├── attendance.php         # Điểm danh (lọc ngày)
│   ├── grades.php             # Nhập điểm TX/GK/CK
│   ├── schedule.php           # Lịch dạy cá nhân
│   ├── assignments.php        # Giao bài tập, chấm điểm
│   └── leave_approval.php     # Duyệt đơn xin nghỉ
│
├── student/
│   ├── profile.php            # Hồ sơ cá nhân
│   ├── courses_view.php       # Môn học đã đăng ký
│   ├── classes_view.php       # Lớp học tham gia
│   ├── attendance_view.php    # Thống kê điểm danh (theo lớp, biểu đồ)
│   ├── grades_view.php        # Bảng điểm cá nhân
│   ├── schedule_view.php      # Lịch học
│   ├── assignments_view.php   # Xem & nộp bài tập
│   ├── leave_request.php      # Gửi đơn xin nghỉ
│   └── leave_status.php       # Theo dõi trạng thái đơn
│
├── lang/
│   ├── vi.php                 # Bản dịch tiếng Việt
│   └── en.php                 # Bản dịch tiếng Anh
│
├── frontend/
│   ├── css/style.css          # Giao diện chính (dark theme, responsive)
│   ├── js/
│   │   ├── ui.js              # Toggle thông báo, animation, mobile sidebar
│   │   ├── validation.js      # Validate form
│   │   ├── ajax.js            # AJAX calls
│   │   └── chart.js           # Biểu đồ Chart.js
│   └── templates/
│       ├── header.php         # Header: sidebar, topbar, CSRF, i18n
│       └── footer.php         # Footer + notification dropdown
│
├── index.php                  # Router: redirect theo role
├── README.md                  # File này
└── README_AUTH.md             # Chi tiết hệ thống xác thực & bảo mật
```

---

## 🔐 Bảo mật

| Tính năng | Chi tiết |
|-----------|----------|
| **Mã hóa mật khẩu** | `PASSWORD_BCRYPT` (bcrypt hash) |
| **Chống CSRF** | Token tự động inject vào mọi form POST |
| **Chống Brute-force** | Khóa 15 phút sau 5 lần đăng nhập sai |
| **Session Fixation** | `session_regenerate_id(true)` sau đăng nhập |
| **SQL Injection** | PDO Prepared Statements toàn bộ |
| **XSS** | `htmlspecialchars()` cho mọi output |
| **Phân quyền** | Đăng ký mặc định student, chỉ admin gán role |
| **Quên mật khẩu** | Hiện thông tin liên hệ Phòng QL Đào tạo |

---

## 🔔 Hệ thống thông báo

Thông báo **tự động** khi:
- GV giao bài tập mới → thông báo SV trong lớp
- GV nhập/cập nhật điểm → thông báo từng SV
- GV điểm danh (vắng/muộn) → thông báo SV
- SV nộp bài → thông báo GV
- GV chấm bài nộp → thông báo SV

Badge đếm thông báo chưa đọc, tự ẩn khi đã xem (cookie-based).

---

## 🌐 Đa ngôn ngữ

- Hỗ trợ **Tiếng Việt** và **English**
- 2 nút chuyển đổi VI / EN trên topbar
- Ngôn ngữ lưu trong `$_SESSION['lang']`, không bị reset khi thao tác
- URL switch giữ nguyên tất cả query params

---

## 🗂 Database (13 bảng)

| # | Bảng | Chức năng |
|---|------|-----------|
| 1 | `users` | Tài khoản: username, email, password_hash, role |
| 2 | `students` | Hồ sơ sinh viên: mã SV, họ tên, ngày sinh |
| 3 | `teachers` | Hồ sơ giảng viên: mã GV, họ tên, khoa |
| 4 | `courses` | Môn học: mã môn, tên, số tín chỉ |
| 5 | `semesters` | Kỳ học: tên, ngày bắt đầu/kết thúc |
| 6 | `classes` | Lớp học: môn, GV, kỳ, mã lớp |
| 7 | `enrollments` | Đăng ký lớp: SV ↔ Lớp |
| 8 | `schedules` | Thời khóa biểu: ngày, giờ, phòng |
| 9 | `rooms` | Phòng học: tên, sức chứa |
| 10 | `attendance` | Điểm danh: trạng thái, ngày, nhận xét |
| 11 | `student_grades` | Điểm: TX, giữa kỳ, cuối kỳ |
| 12 | `assignments` | Bài tập: tiêu đề, deadline, điểm tối đa |
| 13 | `submissions` | Bài nộp: file, điểm, thời gian |
| 14 | `notifications` | Thông báo: loại, nội dung, thời gian |
| 15 | `leave_requests` | Đơn xin nghỉ: lý do, trạng thái |

---

## ⚙️ Cài đặt

### Yêu cầu
- XAMPP (PHP 8.0+, MySQL 5.7+)
- Trình duyệt hiện đại

### Bước 1: Clone/copy dự án
```bash
# Copy vào thư mục htdocs của XAMPP
cp -r student-management-web/ C:/xampp/htdocs/Student_management/
```

### Bước 2: Tạo database
```sql
-- Mở phpMyAdmin hoặc MySQL CLI
CREATE DATABASE student_management;
-- Import file init.sql
mysql -u root student_management < db/init.sql
```

### Bước 3: Cấu hình kết nối
Sửa `db/db.php` nếu cần thay đổi thông tin kết nối.

### Bước 4: Truy cập
```
http://localhost/Student_management/student-management-web/
```

---

## 👥 Phân công

| Thành viên | Nhiệm vụ |
|-----------|-----------|
| Đạt đen | Database, CRUD đơn xin nghỉ |
| Đạt đỏ | Auth, Admin module, bảo mật |
| Đạt m9 | Teacher module |
| Yến | Student module |
| Đức | Frontend/UI, tổng hợp, report |

---
