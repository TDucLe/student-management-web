-- Bước 1: Xóa database cũ
DROP DATABASE IF EXISTS student_management;

-- Bước 2: Import file db/init.sql (tạo lại toàn bộ bảng)
-- Bước 3: (Tùy chọn) Import db/seed.sql — admin / password
--
-- Nếu KHÔNG xóa DB, chạy lần lượt: migrate_auth.sql, migrate_v2.sql, migrate_v3.sql
