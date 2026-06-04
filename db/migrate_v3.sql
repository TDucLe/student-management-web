USE student_management;

CREATE TABLE IF NOT EXISTS student_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL UNIQUE,
    regular_score DECIMAL(4,2) NULL,
    midterm_score DECIMAL(4,2) NULL,
    final_score DECIMAL(4,2) NULL,
    total_score DECIMAL(4,2) NULL,
    letter_grade VARCHAR(2) NULL,
    gpa DECIMAL(4,2) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
);

ALTER TABLE semesters ADD COLUMN term_type ENUM('semester1', 'semester2', 'summer') NOT NULL DEFAULT 'semester1';
ALTER TABLE semesters ADD COLUMN school_year VARCHAR(20) NOT NULL DEFAULT '2025-2026';
