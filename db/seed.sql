USE student_management;

INSERT INTO users (username, email, password_hash, role)
VALUES (
    'admin',
    'admin@school.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
)
ON DUPLICATE KEY UPDATE username = username;
-- Default password: password
