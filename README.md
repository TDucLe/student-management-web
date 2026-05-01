# Student Management System

## 📂 Project Structure

```bash
student-management-system/
│
├── db/                  # Kết nối và script database
│   ├── db.php
│   └── init.sql
│
├── auth/                # Authentication (login, register, logout)
│   ├── register.php
│   ├── login.php
│   ├── logout.php
│   └── auth_helper.php
│
├── teacher/             # Module Teacher
│   ├── courses.php
│   ├── classes.php
│   ├── attendance.php
│   └── grades.php
│
├── student/             # Module Student
│   ├── profile.php
│   ├── courses_view.php
│   ├── classes_view.php
│   ├── attendance_view.php
│   └── grades_view.php
│
├── frontend/            # UI + JavaScript + CSS
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── validation.js
│   │   ├── ajax.js
│   │   └── chart.js
│   └── templates/
│       ├── header.php
│       ├── footer.php
│       └── dashboard.php
│
├── index.php            # Trang chính, điều hướng theo role
├── README.md            # Hướng dẫn setup
└── .gitignore


1. Branch Naming
backend-db → Người 1 (Database & Backend Core)

auth → Người 2 (Authentication & Role-based Access)

teacher → Người 3 (Module Teacher)

student → Người 4 (Module Student)

frontend → Người 5 (UI/Frontend + JavaScript)
