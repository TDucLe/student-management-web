CREATE DATABASE IF NOT EXISTS student_management;
USE student_management;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL
);

CREATE TABLE students (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dob DATE,
    major VARCHAR(100),
    contact VARCHAR(50),
    FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE teachers (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    contact VARCHAR(50),
    FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    credits INT NOT NULL,
    teacher_id INT,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    schedule VARCHAR(100),
    room VARCHAR(50),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    student_id INT,
    status ENUM('present', 'absent', 'late') NOT NULL,
    date DATE NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_id INT,
    grade DECIMAL(5,2),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    course_id INT,
    room VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    teacher_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    deadline DATETIME NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT,
    student_id INT,
    file_url VARCHAR(255) NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    grade DECIMAL(5,2),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(50),
    message TEXT NOT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    class_id INT,
    reason TEXT NOT NULL,
    date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    teacher_id INT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(50),
    value TEXT,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- INSERT SEED DATA
-- ==========================================

-- users (1 admin, 2 teachers, 3 students)
INSERT INTO users (username, password, role) VALUES
('admin', 'password123', 'admin'),
('teacher1', 'password123', 'teacher'),
('teacher2', 'password123', 'teacher'),
('student1', 'password123', 'student'),
('student2', 'password123', 'student'),
('student3', 'password123', 'student');

-- students (IDs 4, 5, 6)
INSERT INTO students (id, name, dob, major, contact) VALUES
(4, 'Nguyen Van A', '2000-01-15', 'Information Technology', '0123456789'),
(5, 'Tran Thi B', '2001-05-20', 'Business Administration', '0987654321'),
(6, 'Le Van C', '2002-10-10', 'Computer Science', '0112233445');

-- teachers (IDs 2, 3)
INSERT INTO teachers (id, name, department, contact) VALUES
(2, 'Dr. Pham D', 'Computer Science', '0999888777'),
(3, 'Prof. Hoang E', 'Mathematics', '0888777666');

-- courses
INSERT INTO courses (name, credits, teacher_id) VALUES
('Database Systems', 3, 2),
('Calculus', 4, 3);

-- classes
INSERT INTO classes (course_id, schedule, room) VALUES
(1, 'Monday 08:00-10:00', 'Room A101'),
(2, 'Wednesday 13:00-15:00', 'Room B202');

-- attendance
INSERT INTO attendance (class_id, student_id, status, date) VALUES
(1, 4, 'present', '2023-09-01'),
(1, 5, 'absent', '2023-09-01'),
(2, 6, 'late', '2023-09-03');

-- grades
INSERT INTO grades (student_id, course_id, grade) VALUES
(4, 1, 8.5),
(5, 1, 7.0),
(6, 2, 9.0);

-- schedules
INSERT INTO schedules (user_id, role, date, time, course_id, room) VALUES
(2, 'teacher', '2023-09-01', '08:00:00', 1, 'Room A101'),
(4, 'student', '2023-09-01', '08:00:00', 1, 'Room A101');

-- assignments
INSERT INTO assignments (course_id, teacher_id, title, description, deadline) VALUES
(1, 2, 'SQL Query Practice', 'Write queries for given database schema', '2023-09-10 23:59:59'),
(2, 3, 'Limits and Derivatives', 'Solve exercises 1 to 10 on page 42', '2023-09-15 23:59:59');

-- submissions
INSERT INTO submissions (assignment_id, student_id, file_url, submitted_at, grade) VALUES
(1, 4, '/uploads/hw1_student1.pdf', '2023-09-09 10:00:00', 9.0),
(1, 5, '/uploads/hw1_student2.pdf', '2023-09-10 15:30:00', 8.0);

-- notifications
INSERT INTO notifications (user_id, type, message) VALUES
(4, 'assignment_reminder', 'Reminder: SQL Query Practice is due tomorrow.'),
(5, 'absence_warning', 'Warning: You have been absent from Database Systems.');

-- leave_requests
INSERT INTO leave_requests (student_id, class_id, reason, date, status, teacher_id) VALUES
(4, 1, 'Sick leave', '2023-09-08', 'approved', 2),
(5, 2, 'Family emergency', '2023-09-10', 'pending', NULL);

-- stats
INSERT INTO stats (user_id, type, value) VALUES
(4, 'gpa', '8.5'),
(5, 'gpa', '7.0');
