# Student Management System

## 📂 Project Structure

```bash
student-management-system/
# Student Management System

Hệ thống quản lý học sinh, giáo viên, môn học, lớp học, điểm danh, điểm số, lịch học/lịch dạy và thống kê học tập.

---
# Student Management System

## 📂 Project Structure (Tree + Notes)

```bash
student-management-system/
│
├── db/                  
│   ├── db.php              # File PHP kết nối MySQL
│   └── init.sql            # Script tạo bảng: users, students, teachers, courses, classes,
│                           # attendance, grades, schedules, assignments, submissions,
│                           # notifications, leave_requests, stats
│
├── auth/                
│   ├── register.php        # Form đăng ký tài khoản mới (student/teacher/admin)
│   ├── login.php           # Form đăng nhập
│   ├── logout.php          # Đăng xuất
│   └── auth_helper.php     # Hàm tiện ích: kiểm tra role, session, redirect
│
├── admin/                  # Module Admin
│   ├── dashboard.php       # Trang tổng quan admin: thống kê toàn hệ thống
│   ├── users_manage.php    # Quản lý tài khoản (CRUD student, teacher, admin)
│   ├── courses_manage.php  # Quản lý môn học
│   ├── classes_manage.php  # Quản lý lớp học
│   ├── attendance_manage.php # Quản lý dữ liệu điểm danh
│   ├── grades_manage.php   # Quản lý bảng điểm toàn hệ thống
│   ├── assignments_manage.php # Quản lý bài tập (xem, xóa, sửa)
│   ├── leave_manage.php    # Quản lý đơn xin nghỉ học (CRUD)
│   └── stats_manage.php    # Thống kê tổng hợp toàn hệ thống
│
├── teacher/             
│   ├── courses.php         # Quản lý môn học do giáo viên phụ trách
│   ├── classes.php         # Quản lý lớp học
│   ├── attendance.php      # Điểm danh sinh viên trong lớp
│   ├── grades.php          # Nhập điểm cho sinh viên
│   ├── schedule.php        # Lịch dạy cá nhân
│   ├── assignments.php     # Giao bài tập
│   └── leave_approval.php  # Giáo viên duyệt đơn xin nghỉ học
│
├── student/             
│   ├── profile.php         # Hồ sơ cá nhân sinh viên
│   ├── courses_view.php    # Xem môn học đã đăng ký
│   ├── classes_view.php    # Xem lớp học đã tham gia
│   ├── attendance_view.php # Xem lịch sử điểm danh
│   ├── grades_view.php     # Xem bảng điểm cá nhân
│   ├── schedule_view.php   # Lịch học cá nhân
│   ├── assignments_view.php# Xem và nộp bài tập
│   ├── leave_request.php   # Sinh viên gửi đơn xin nghỉ học
│   └── leave_status.php    # Sinh viên xem trạng thái đơn nghỉ học
│
├── stats/               
│   ├── student_stats.php   # Thống kê cá nhân: GPA, chuyên cần, số môn hoàn thành
│   └── teacher_stats.php   # Thống kê lớp học: GPA trung bình, chuyên cần, số lượng sinh viên
│
├── notifications/       
│   ├── notify_absence.php  # Nhắc nhở sinh viên nghỉ học nhiều buổi
│   └── notify_assignment.php # Nhắc nhở hạn nộp bài tập
│
├── frontend/            
│   ├── css/
│   │   └── style.css       # Giao diện chính
│   ├── js/
│   │   ├── validation.js   # Kiểm tra form
│   │   ├── ajax.js         # Xử lý AJAX call
│   │   └── chart.js        # Biểu đồ thống kê (Chart.js)
│   └── templates/
│       ├── header.php      # Header chung
│       ├── footer.php      # Footer chung
│       └── dashboard.php   # Dashboard hiển thị thống kê, lịch, thông báo
│
├── index.php               # Trang chính, điều hướng theo role (admin/teacher/student)
├── README.md               # Hướng dẫn setup + phân công công việc
└── .gitignore
    

---------------------------------------------------------------------------------
- **Admin**: Quản lý người dùng, môn học, lớp học, xem thống kê toàn hệ thống.  
- **Teacher**: Quản lý môn học, lớp học, điểm danh, nhập điểm, lịch dạy, giao bài tập, xem thống kê lớp.  
- **Student**: Xem hồ sơ, môn học, lớp học, điểm danh, điểm số, lịch học, nộp bài tập, nhận thông báo.  
- **Frontend**: Hiển thị dashboard, biểu đồ thống kê, lịch học/lịch dạy, thông báo nhắc nhở.  
+---------------------------------------------------+
|         Admin                                     |
+---------------------------------------------------+
| Menu: Users | Courses | Classes | Statistics      |
+---------------------------------------------------+
| Tổng quan:                                        |
| - Số lượng sinh viên:                             |
| - Số lượng giáo viên:                             |
| - Số môn học:                                     |
| - GPA trung bình toàn hệ thống:                   |
+---------------------------------------------------+
| Biểu đồ:                                          |
| - Pie chart: Tỷ lệ chuyên cần toàn hệ thống       |
| - Bar chart: GPA trung bình theo khoa             |
+---------------------------------------------------+
| Calendar: Lịch học/lịch dạy toàn hệ thống         |
+---------------------------------------------------+
| Notifications:                                    |
| - Danh sách sinh viên nghỉ học nhiều              |
| - Thống kê bài tập chưa nộp                       |
+---------------------------------------------------+

-----

+---------------------------------------------------+
|         Teacher Dashboard                         |
+---------------------------------------------------+
| Menu: My Courses | My Classes | Attendance | Stats|
+---------------------------------------------------+
| Thông tin cá nhân:                                |
| - Tên: Nguyễn Văn A                               |
| - Bộ môn: CNTT                                    |
+---------------------------------------------------+
| Lịch dạy:                                         |
| - Calendar hiển thị lịch dạy theo tuần/tháng      |
+---------------------------------------------------+
| Thống kê lớp học:                                 |
| - GPA trung bình lớp                              |
| - Tỷ lệ chuyên cần                                |
| - Số lượng sinh viên tham gia                     |
+---------------------------------------------------+
| Bài tập:                                          |
| - Danh sách bài tập đã giao                       |
| - Deadline, số lượng sinh viên đã nộp             |
+---------------------------------------------------+
| Notifications:                                    |
| - Nhắc nhở sinh viên chưa nộp bài                 |
+---------------------------------------------------+

-----------

+---------------------------------------------------+
|         Student Dashboard                         |
+---------------------------------------------------+
| Menu: Profile | Courses | Classes | Grades | Stats|
+---------------------------------------------------+
| Hồ sơ cá nhân:                                    |
| - Tên: Trần Thị B                                 |
| - Ngành: Kinh tế                                  |
+---------------------------------------------------+
| Lịch học:                                         |
| - Calendar hiển thị lịch học theo tuần/tháng      |
+---------------------------------------------------+
| Thống kê cá nhân:                                 |
| - GPA: 3.2                                        |
| - Tỷ lệ chuyên cần: 85%                           |
| - Số môn đã hoàn thành: 25                        |
+---------------------------------------------------+
| Bài tập:                                          |
| - Danh sách bài tập được giao                     |
| - Deadline, trạng thái nộp                        |
+---------------------------------------------------+
| Notifications:                                    |
| - Popup/email nhắc nhở nghỉ học                   |
| - Nhắc nhở hạn nộp bài tập                        |
+---------------------------------------------------+

--------------------------------------------------------------------
## 🗂 Các bảng trong Database & Chức năng

### 1. **users**
- **Chức năng**: Quản lý tài khoản đăng nhập hệ thống.
- **Cột chính**: `id`, `username`, `password`, `role`

---

### 2. **students**
- **Chức năng**: Lưu thông tin hồ sơ sinh viên.
- **Cột chính**: `id`, `name`, `dob`, `major`, `contact`

---

### 3. **teachers**
- **Chức năng**: Lưu thông tin hồ sơ giáo viên.
- **Cột chính**: `id`, `name`, `department`, `contact`

---

### 4. **courses**
- **Chức năng**: Quản lý môn học.
- **Cột chính**: `id`, `name`, `credits`, `teacher_id`

---

### 5. **classes**
- **Chức năng**: Quản lý lớp học (gắn với môn học).
- **Cột chính**: `id`, `course_id`, `schedule`, `room`

---

### 6. **attendance**
- **Chức năng**: Quản lý điểm danh sinh viên.
- **Cột chính**: `id`, `class_id`, `student_id`, `status`, `date`

---

### 7. **grades**
- **Chức năng**: Quản lý bảng điểm sinh viên.
- **Cột chính**: `id`, `student_id`, `course_id`, `grade`

---

### 8. **schedules**
- **Chức năng**: Quản lý lịch học/lịch dạy.
- **Cột chính**: `id`, `user_id`, `role`, `date`, `time`, `course_id`, `room`

---

### 9. **assignments**
- **Chức năng**: Quản lý bài tập được giao.
- **Cột chính**: `id`, `course_id`, `teacher_id`, `title`, `description`, `deadline`

---

### 10. **submissions**
- **Chức năng**: Quản lý bài nộp của sinh viên.
- **Cột chính**: `id`, `assignment_id`, `student_id`, `file_url`, `submitted_at`, `grade`

---

### 11. **notifications**
- **Chức năng**: Quản lý thông báo/nhắc nhở.
- **Cột chính**: `id`, `user_id`, `type`, `message`, `date`

---

### 12. **leave_requests** (Mới)
- **Chức năng**: Quản lý đơn xin nghỉ học.
- **Cột chính**:
  - `id`: Khóa chính
  - `student_id`: Sinh viên gửi đơn
  - `class_id`: Lớp học liên quan
  - `reason`: Lý do nghỉ
  - `date`: Ngày xin nghỉ
  - `status`: Trạng thái (pending/approved/rejected)
  - `teacher_id`: Giáo viên duyệt đơn

---

### 13. **stats**
- **Chức năng**: Lưu dữ liệu thống 

------------------------------------------------------------------

- Đạt đen (DB)
  - Thiết kế database 
  - CRUD dữ liệu cho đơn xin nghỉ học  

- Đạt đỏ (Auth & Admin) 
  - Quản lý đăng nhập, đăng ký, phân quyền  
  - Admin dashboard có thêm mục quản lý đơn xin nghỉ học  

- Đạt m9 (Teacher Module) 
  - Quản lý môn học, lớp học  
  - Điểm danh, nhập điểm  
  - Lịch dạy cá nhân  
  - Giao và quản lý bài tập  
  - Duyệt đơn xin nghỉ học  

- Yến (Student Module) 
  - Hồ sơ cá nhân, xem môn học, lớp học  
  - Xem điểm danh, điểm số  
  - Lịch học cá nhân  
  - Nộp bài tập, xem bài tập  
  - Gửi đơn xin nghỉ học, xem trạng thái  
  - Nhận thông báo nghỉ học, hạn nộp bài  

- Đức (Frontend/UI)  
  - Thiết kế giao diện cho form xin nghỉ, duyệt nghỉ  
  - Hiển thị biểu đồ thống kê  
  - Hiển thị lịch học/lịch dạy dạng calendar  
  - Hiển thị popup/email reminder cho nghỉ học, hạn nộp bài  
  - Tổng hợp code, viết report 

---
