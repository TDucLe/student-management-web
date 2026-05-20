CREATE DATABASE IF NOT EXISTS student_management;
USE student_management;

-- ==========================================
-- USERS
-- ==========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
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
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (teacher_id)
    REFERENCES teachers(id)
    ON DELETE SET NULL
);

-- ==========================================
-- CLASSES
-- ==========================================
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    course_id INT,
    room VARCHAR(50),
    schedule VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (course_id)
    REFERENCES courses(id)
    ON DELETE CASCADE
);

-- ==========================================
-- ENROLLMENTS
-- ==========================================
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    class_id INT,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',

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
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,
    note VARCHAR(255),

    UNIQUE(enrollment_id, attendance_date),

    FOREIGN KEY (enrollment_id)
    REFERENCES enrollments(id)
    ON DELETE CASCADE
);

-- ==========================================
-- GRADES
-- ==========================================
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT,
    assignment_type VARCHAR(50),
    score DECIMAL(5,2),
    max_score DECIMAL(5,2),
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note VARCHAR(255),

    FOREIGN KEY (enrollment_id)
    REFERENCES enrollments(id)
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
    file_url VARCHAR(255),
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
-- NOTIFICATIONS
-- ==========================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(50),
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
    status ENUM('pending', 'approved', 'rejected')
    DEFAULT 'pending',
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

-- ==========================================
-- SEED DATA
-- ==========================================

-- USERS
INSERT INTO users (username, password_hash, role) VALUES
('admin', 'admin123', 'admin'),
('teacher1', 'teacher123', 'teacher'),
('teacher2', 'teacher123', 'teacher'),
('student1', 'student123', 'student'),
('student2', 'student123', 'student'),
('student3', 'student123', 'student');

-- STUDENTS
INSERT INTO students
(user_id, student_code, full_name, dob, major, contact, address)
VALUES
(4, 'SV001', 'Nguyen Van A', '2000-01-15',
'Information Technology', '0123456789', 'Ha Noi'),

(5, 'SV002', 'Tran Thi B', '2001-05-20',
'Business Administration', '0987654321', 'Hai Phong'),

(6, 'SV003', 'Le Van C', '2002-10-10',
'Computer Science', '0112233445', 'Da Nang');

-- TEACHERS
INSERT INTO teachers
(user_id, teacher_code, full_name, department, contact)
VALUES
(2, 'GV001', 'Dr. Pham D', 'Computer Science', '0999888777'),
(3, 'GV002', 'Prof. Hoang E', 'Mathematics', '0888777666');

-- COURSES
INSERT INTO courses
(course_code, course_name, credits, teacher_id)
VALUES
('DB101', 'Database Systems', 3, 1),
('MATH201', 'Calculus', 4, 2);

-- CLASSES
INSERT INTO classes
(class_name, course_id, room, schedule)
VALUES
('DBS_A1', 1, 'A101', 'Monday 08:00-10:00'),
('CAL_B1', 2, 'B202', 'Wednesday 13:00-15:00');

-- ENROLLMENTS
INSERT INTO enrollments
(student_id, class_id)
VALUES
(1, 1),
(2, 1),
(3, 2);

-- ATTENDANCE
INSERT INTO attendance
(enrollment_id, attendance_date, status, note)
VALUES
(1, '2026-05-01', 'present', 'On time'),
(2, '2026-05-01', 'absent', 'Sick'),
(3, '2026-05-03', 'late', 'Traffic');

-- GRADES
INSERT INTO grades
(enrollment_id, assignment_type, score, max_score, note)
VALUES
(1, 'Midterm', 8.5, 10, 'Good'),
(2, 'Midterm', 7.0, 10, 'Average'),
(3, 'Midterm', 9.0, 10, 'Excellent');

-- ASSIGNMENTS
INSERT INTO assignments
(class_id, teacher_id, title, description, deadline, max_score)
VALUES
(1, 1,
'SQL Query Practice',
'Write SQL queries for the database schema',
'2026-05-30 23:59:59',
10),

(2, 2,
'Calculus Exercises',
'Solve exercises 1-10',
'2026-06-05 23:59:59',
10);

-- SUBMISSIONS
INSERT INTO submissions
(assignment_id, student_id, file_url, score)
VALUES
(1, 1, '/uploads/student1_hw.pdf', 9.0),
(1, 2, '/uploads/student2_hw.pdf', 8.0);

-- NOTIFICATIONS
INSERT INTO notifications
(user_id, type, message)
VALUES
(4, 'assignment',
'Reminder: SQL assignment deadline tomorrow'),

(5, 'attendance',
'You were absent from Database Systems');

-- LEAVE REQUESTS
INSERT INTO leave_requests
(student_id, class_id, teacher_id, reason, leave_date, status)
VALUES
(1, 1, 1, 'Sick leave', '2026-05-10', 'approved'),

(2, 1, 1, 'Family emergency', '2026-05-12', 'pending');