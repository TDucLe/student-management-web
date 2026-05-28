```sql
CREATE DATABASE IF NOT EXISTS student_management;
USE student_management;

-- ==========================================
-- USERS
-- ==========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires DATETIME NULL,

    role ENUM(
        'admin',
        'teacher',
        'student'
    ) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    deleted_at TIMESTAMP NULL
);

-- ==========================================
-- SEMESTERS
-- ==========================================
CREATE TABLE semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
);

-- ==========================================
-- ROOMS
-- ==========================================
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,

    room_number VARCHAR(20) NOT NULL,
    building VARCHAR(100),
    capacity INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- STUDENTS
-- ==========================================
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNIQUE,

    student_code VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,

    dob DATE,
    major VARCHAR(100),
    contact VARCHAR(50),
    address VARCHAR(255),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE
);

-- ==========================================
-- TEACHERS
-- ==========================================
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNIQUE,

    teacher_code VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,

    department VARCHAR(100),
    contact VARCHAR(50),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE
);

-- ==========================================
-- COURSES
-- ==========================================
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,

    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,

    credits INT NOT NULL,
    department VARCHAR(100),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
);

-- ==========================================
-- CLASSES
-- ==========================================
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    class_name VARCHAR(100) NOT NULL,

    course_id INT,
    semester_id INT,
    teacher_id INT,
    room_id INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (course_id)
    REFERENCES courses(id)
    ON DELETE CASCADE,

    FOREIGN KEY (semester_id)
    REFERENCES semesters(id)
    ON DELETE CASCADE,

    FOREIGN KEY (teacher_id)
    REFERENCES teachers(id)
    ON DELETE SET NULL,

    FOREIGN KEY (room_id)
    REFERENCES rooms(id)
    ON DELETE SET NULL
);

-- ==========================================
-- SCHEDULES
-- ==========================================
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,

    class_id INT,
    room_id INT NULL,

    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    FOREIGN KEY (class_id)
    REFERENCES classes(id)
    ON DELETE CASCADE,

    FOREIGN KEY (room_id)
    REFERENCES rooms(id)
    ON DELETE SET NULL
);

-- ==========================================
-- ENROLLMENTS
-- ==========================================
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    student_id INT,
    class_id INT,

    status ENUM(
        'active',
        'completed',
        'dropped'
    ) DEFAULT 'active',

    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(student_id, class_id),

    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE,

    FOREIGN KEY (class_id)
    REFERENCES classes(id)
    ON DELETE CASCADE
);

-- ==========================================
-- ATTENDANCE
-- ==========================================
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,

    enrollment_id INT,
    schedule_id INT,

    attendance_date DATE NOT NULL,

    status ENUM(
        'present',
        'absent',
        'late'
    ) NOT NULL,

    teacher_comment TEXT,

    UNIQUE(
        enrollment_id,
        schedule_id,
        attendance_date
    ),

    FOREIGN KEY (enrollment_id)
    REFERENCES enrollments(id)
    ON DELETE CASCADE,

    FOREIGN KEY (schedule_id)
    REFERENCES schedules(id)
    ON DELETE CASCADE
);

-- ==========================================
-- ASSIGNMENTS
-- ==========================================
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    class_id INT,
    teacher_id INT,

    title VARCHAR(200) NOT NULL,
    description TEXT,

    deadline DATETIME NOT NULL,
    max_score DECIMAL(5,2),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (class_id)
    REFERENCES classes(id)
    ON DELETE CASCADE,

    FOREIGN KEY (teacher_id)
    REFERENCES teachers(id)
    ON DELETE SET NULL
);

-- ==========================================
-- SUBMISSIONS
-- ==========================================
CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    assignment_id INT,
    student_id INT,

    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    score DECIMAL(5,2),

    UNIQUE(assignment_id, student_id),

    FOREIGN KEY (assignment_id)
    REFERENCES assignments(id)
    ON DELETE CASCADE,

    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
);

-- ==========================================
-- SUBMISSION FILES
-- ==========================================
CREATE TABLE submission_files (
    id INT AUTO_INCREMENT PRIMARY KEY,

    submission_id INT NOT NULL,

    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(100),
    file_size INT,

    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (submission_id)
    REFERENCES submissions(id)
    ON DELETE CASCADE
);

-- ==========================================
-- EXAMS
-- ==========================================
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,

    class_id INT,

    exam_name VARCHAR(100),
    exam_date DATE,

    max_score DECIMAL(5,2),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (class_id)
    REFERENCES classes(id)
    ON DELETE CASCADE
);

-- ==========================================
-- EXAM RESULTS
-- ==========================================
CREATE TABLE exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,

    exam_id INT,
    student_id INT,

    score DECIMAL(5,2),

    UNIQUE(exam_id, student_id),

    FOREIGN KEY (exam_id)
    REFERENCES exams(id)
    ON DELETE CASCADE,

    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE
);

-- ==========================================
-- GRADE CATEGORIES
-- ==========================================
CREATE TABLE grade_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,

    class_id INT NOT NULL,

    category_name VARCHAR(100) NOT NULL,

    percentage DECIMAL(5,2) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (class_id)
    REFERENCES classes(id)
    ON DELETE CASCADE
);

-- ==========================================
-- STUDENT GRADES
-- ==========================================
CREATE TABLE student_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,

    enrollment_id INT NOT NULL,

    grade_category_id INT NOT NULL,

    score DECIMAL(5,2) NOT NULL,

    teacher_comment TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(enrollment_id, grade_category_id),

    FOREIGN KEY (enrollment_id)
    REFERENCES enrollments(id)
    ON DELETE CASCADE,

    FOREIGN KEY (grade_category_id)
    REFERENCES grade_categories(id)
    ON DELETE CASCADE
);

-- ==========================================
-- GPA RECORDS
-- ==========================================
CREATE TABLE gpa_records (
    id INT AUTO_INCREMENT PRIMARY KEY,

    student_id INT NOT NULL,

    semester_id INT NOT NULL,

    gpa DECIMAL(3,2) NOT NULL,

    ranking VARCHAR(50),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(student_id, semester_id),

    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE,

    FOREIGN KEY (semester_id)
    REFERENCES semesters(id)
    ON DELETE CASCADE
);

-- ==========================================
-- NOTIFICATIONS
-- ==========================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    type ENUM(
        'assignment',
        'attendance',
        'exam',
        'general'
    ),

    message TEXT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE
);

-- ==========================================
-- LEAVE REQUESTS
-- ==========================================
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,

    student_id INT,
    class_id INT,
    teacher_id INT,

    reason TEXT NOT NULL,

    leave_date DATE NOT NULL,

    status ENUM(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id)
    REFERENCES students(id)
    ON DELETE CASCADE,

    FOREIGN KEY (class_id)
    REFERENCES classes(id)
    ON DELETE CASCADE,

    FOREIGN KEY (teacher_id)
    REFERENCES teachers(id)
    ON DELETE SET NULL
);
```
