<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if ($user['role'] !== 'teacher') die("Access denied.");

$teacher_id = $user['id'];
$message = "";

// Bảo trì ngầm sinh viên
try {
    $stmt_all = $pdo->query("SELECT id, username FROM users WHERE role = 'student'");
    foreach ($stmt_all->fetchAll() as $stu) {
        $check = $pdo->prepare("SELECT id FROM students WHERE id = ?");
        $check->execute([$stu['id']]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO students (id, name) VALUES (?, ?)")->execute([$stu['id'], $stu['username']]);
        }
    }
} catch (PDOException $e) {}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_attendance'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO attendance (class_id, student_id, date, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['class_id'], $_POST['student_id'], $_POST['date'], $_POST['status']]);
        $message = "<div class='success'>✅ Attendance recorded!</div>";
    } catch (PDOException $e) {
        $message = "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_attendance'])) {
    try {
        $pdo->prepare("DELETE FROM attendance WHERE id = ?")->execute([$_POST['attendance_id']]);
        $message = "<div class='success'>🗑️ Record deleted!</div>";
    } catch (PDOException $e) {}
}

$stmt_classes = $pdo->prepare("SELECT classes.id, courses.name AS course_name, classes.schedule FROM classes JOIN courses ON classes.course_id = courses.id WHERE courses.teacher_id = ?");
$stmt_classes->execute([$teacher_id]);
$my_classes = $stmt_classes->fetchAll();

$students_res = $pdo->query("SELECT id, name FROM students ORDER BY name ASC")->fetchAll();

$stmt_history = $pdo->prepare("SELECT a.id, c.name AS course_name, cls.schedule, s.name AS student_name, a.date, a.status 
                               FROM attendance a JOIN classes cls ON a.class_id = cls.id 
                               JOIN courses c ON cls.course_id = c.id JOIN students s ON a.student_id = s.id 
                               WHERE c.teacher_id = ? ORDER BY a.date DESC, a.id DESC");
$stmt_history->execute([$teacher_id]);
$history_res = $stmt_history->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Attendance</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f5f5f5; padding: 20px; margin: 0; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-top: 0; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; } th, td { border-bottom: 1px solid #eee; padding: 12px; } th { background: #f9f9f9; color: #333; }
        .form-group { margin-bottom: 15px; } label { display: block; margin-bottom: 8px; font-weight: 500; color: #555;}
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box; }
        .btn-add { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; width: 100%; }
        .btn-delete { background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; }
        .btn-back { display: inline-block; margin-bottom: 20px; padding: 8px 15px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; display: inline-block; min-width: 60px; text-align: center; }
        .bg-present { background-color: #10b981; } .bg-absent { background-color: #ef4444; } .bg-late { background-color: #f59e0b; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
        <h2>✅ Mark Attendance</h2>
        <?= $message ?>
        <form method="POST">
            <div class="form-group"><label>Class:</label><select name="class_id" required><option value="">-- Choose Class --</option><?php foreach($my_classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name'] . ' - ' . $c['schedule']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Student:</label><select name="student_id" required><option value="">-- Choose Student --</option><?php foreach($students_res as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Date:</label><input type="date" name="date" required value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>Status:</label><select name="status" required><option value="present">🟢 Present</option><option value="late">🟠 Late</option><option value="absent">🔴 Absent</option></select></div>
            <button type="submit" name="mark_attendance" class="btn-add">Save Attendance</button>
        </form>

        <h2 style="margin-top: 40px;">📋 Attendance History</h2>
        <?php if (count($history_res) > 0): ?>
            <table>
                <tr><th style="width: 50px; text-align: center;">No.</th><th>Class</th><th>Student</th><th style="text-align: center;">Date</th><th style="text-align: center;">Status</th><th style="text-align: center;">Action</th></tr>
                <?php $stt = 1; foreach ($history_res as $row): ?>
                    <tr>
                        <td style="text-align: center;"><strong><?= $stt++ ?></strong></td>
                        <td><strong><?= htmlspecialchars($row['course_name']) ?></strong><br><small style="color: #888;"><?= htmlspecialchars($row['schedule']) ?></small></td>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td style="text-align: center;"><?= htmlspecialchars(date('d/m/Y', strtotime($row['date']))) ?></td>
                        <td style="text-align: center;"><span class="badge bg-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td style="text-align: center;"><form method="POST" onsubmit="return confirm('Delete?');"><input type="hidden" name="attendance_id" value="<?= $row['id'] ?>"><button type="submit" name="delete_attendance" class="btn-delete">Delete</button></form></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>