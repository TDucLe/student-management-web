<?php
require_once 'config.php';
requireLogin();
$user = getCurrentUser();
if ($user['role'] !== 'teacher') die("Access denied.");

// BẢO TRÌ NGẦM: Tìm hoặc Tạo Teacher ID từ User ID
$stmt_t = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?"); $stmt_t->execute([$user['id']]); $teacher = $stmt_t->fetch();
if (!$teacher) {
    $pdo->prepare("INSERT INTO teachers (user_id, teacher_code, full_name, department) VALUES (?, ?, ?, 'IT')")->execute([$user['id'], 'TCH'.time(), $user['username']]);
    $teacher_id = $pdo->lastInsertId();
} else { $teacher_id = $teacher['id']; }

// Tự tạo Semester và Room mẫu nếu DB đang trống
$sem_id = $pdo->query("SELECT id FROM semesters LIMIT 1")->fetchColumn() ?: ($pdo->exec("INSERT INTO semesters (name, start_date, end_date) VALUES ('Spring 2026', '2026-01-01', '2026-06-01')") ? $pdo->lastInsertId() : 1);
$room_id = $pdo->query("SELECT id FROM rooms LIMIT 1")->fetchColumn() ?: ($pdo->exec("INSERT INTO rooms (room_number, building) VALUES ('A101', 'Main')") ? $pdo->lastInsertId() : 1);

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_class'])) {
    try {
        $pdo->prepare("INSERT INTO classes (class_name, course_id, semester_id, teacher_id, room_id) VALUES (?, ?, ?, ?, ?)")
            ->execute([trim($_POST['class_name']), $_POST['course_id'], $_POST['semester_id'], $teacher_id, $_POST['room_id']]);
        $message = "<div class='success'>✅ Class created successfully!</div>";
    } catch (PDOException $e) { $message = "<div class='error'>❌ Error creating class.</div>"; }
}

if (isset($_POST['delete_class'])) { $pdo->prepare("DELETE FROM classes WHERE id = ? AND teacher_id = ?")->execute([$_POST['class_id'], $teacher_id]); }

$courses = $pdo->query("SELECT id, course_code, course_name FROM courses")->fetchAll();
$semesters = $pdo->query("SELECT id, name FROM semesters")->fetchAll();
$rooms = $pdo->query("SELECT id, room_number FROM rooms")->fetchAll();

$my_classes = $pdo->prepare("SELECT cl.id, cl.class_name, c.course_name, s.name as sem_name, r.room_number FROM classes cl JOIN courses c ON cl.course_id = c.id LEFT JOIN semesters s ON cl.semester_id = s.id LEFT JOIN rooms r ON cl.room_id = r.id WHERE cl.teacher_id = ? ORDER BY cl.id DESC");
$my_classes->execute([$teacher_id]); $classes_data = $my_classes->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>My Classes</title>
<style>/* Copy lại Style của file Courses để dùng chung nhé */ body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;padding:20px;margin:0} .container{max-width:900px;margin:auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1)} h2{color:#333;border-bottom:2px solid #667eea;padding-bottom:10px} table{width:100%;border-collapse:collapse;margin-top:20px} th,td{border-bottom:1px solid #eee;padding:12px;text-align:left} th{background:#f9f9f9} input,select{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;box-sizing:border-box} .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px} .btn-add{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;padding:12px;border-radius:5px;cursor:pointer;font-weight:bold;width:100%} .btn-del{background:#fee2e2;color:#ef4444;border:none;padding:6px 12px;border-radius:4px;cursor:pointer} .btn-back{display:inline-block;margin-bottom:20px;text-decoration:none;color:#667eea;font-weight:bold} .success{background:#dcfce7;color:#166534;padding:12px;border-radius:5px;margin-bottom:15px} .error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:5px;margin-bottom:15px}</style>
</head><body><div class="container"><a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
<h2>🏫 Open New Class</h2><?= $message ?>
<form method="POST"><div class="form-grid">
    <div><label>Class Name:</label><input type="text" name="class_name" required placeholder="e.g. IT-K64-A"></div>
    <div><label>Course:</label><select name="course_id" required><option value="">-- Select Course --</option><?php foreach($courses as $c) echo "<option value='{$c['id']}'>{$c['course_code']} - {$c['course_name']}</option>"; ?></select></div>
    <div><label>Semester:</label><select name="semester_id" required><?php foreach($semesters as $s) echo "<option value='{$s['id']}'>{$s['name']}</option>"; ?></select></div>
    <div><label>Room:</label><select name="room_id" required><?php foreach($rooms as $r) echo "<option value='{$r['id']}'>{$r['room_number']}</option>"; ?></select></div>
</div><button type="submit" name="add_class" class="btn-add">Create Class</button></form>
<h2 style="margin-top: 40px;">📋 My Teaching Classes</h2>
<?php if(count($classes_data)>0): ?><table><tr><th>Class Name</th><th>Course</th><th>Semester</th><th>Room</th><th>Action</th></tr>
<?php foreach ($classes_data as $row): ?><tr><td><strong style="color:#667eea;"><?= htmlspecialchars($row['class_name']) ?></strong></td><td><?= htmlspecialchars($row['course_name']) ?></td><td><?= htmlspecialchars($row['sem_name']) ?></td><td><?= htmlspecialchars($row['room_number']) ?></td><td><form method="POST"><input type="hidden" name="class_id" value="<?= $row['id'] ?>"><button type="submit" name="delete_class" class="btn-del">Delete</button></form></td></tr><?php endforeach; ?></table><?php endif; ?>
</div></body></html>
