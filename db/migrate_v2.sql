-- Chạy nếu KHÔNG xóa DB (nâng cấp từ bản cũ)
USE student_management;

ALTER TABLE schedules ADD COLUMN room_id INT NULL;
ALTER TABLE schedules ADD CONSTRAINT fk_sched_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL;

ALTER TABLE attendance CHANGE COLUMN note teacher_comment TEXT;
