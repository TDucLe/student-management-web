-- Run once on existing databases (ignore errors if columns already exist)
USE student_management;

ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE AFTER username;
ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN password_reset_expires DATETIME NULL;
