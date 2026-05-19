<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if ($user['role'] !== 'teacher') die("Access denied.");

$teacher_id = $user['id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_class'])) {
    $course_id = $_POST['course_id'];
    $schedule = trim($_POST['schedule']);
    $room = trim($_POST['room']);

    try {
        $check = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
        $check->execute([$course_id, $teacher_id]);
        
        if ($check->fetch()) {
            $stmt_in = $pdo->prepare("INSERT INTO classes (course_id, schedule, room) VALUES (?, ?, ?)");
            $stmt_in->execute([$course_id, $schedule, $room]);
            $message = "<div class='success'>✅ Class scheduled successfully!</div>";
        }
    } catch (PDOException $e) {}
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_class'])) {
    try {
        $stmt_del = $pdo->prepare("DELETE classes FROM classes INNER JOIN courses ON classes.course_id = courses.id WHERE classes.id = ? AND courses.teacher_id = ?");
        $stmt_del->execute([$_POST['class_id'], $teacher_id]);
        $message = "<div class='success'>🗑️ Class deleted successfully!</div>";
    } catch (PDOException $e) {}
}

$stmt_courses = $pdo->prepare("SELECT id, name FROM courses WHERE teacher_id = ?");
$stmt_courses->execute([$teacher_id]);
$my_courses = $stmt_courses->fetchAll();

$stmt_classes = $pdo->prepare("SELECT classes.id, courses.name AS course_name, classes.schedule, classes.room 
                               FROM classes JOIN courses ON classes.course_id = courses.id 
                               WHERE courses.teacher_id = ? ORDER BY classes.id DESC");
$stmt_classes->execute([$teacher_id]);
$my_classes = $stmt_classes->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>My Classes</title>
    <style> /* Tái sử dụng CSS từ file courses */
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
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
        <h2>🏫 Schedule New Class</h2>
        <?= $message ?>
        <form method="POST">
            <div class="form-group"><label>Course:</label><select name="course_id" required><option value="">-- Choose Course --</option><?php foreach ($my_courses as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Schedule:</label><input type="text" name="schedule" required placeholder="Monday 08:00-10:00"></div>
            <div class="form-group"><label>Room:</label><input type="text" name="room" required placeholder="Room A101"></div>
            <button type="submit" name="add_class" class="btn-add">Schedule Class</button>
        </form>

        <h2 style="margin-top: 40px;">📋 Classes Scheduled</h2>
        <?php if (count($my_classes) > 0): ?>
            <table>
                <tr><th style="width: 50px; text-align: center;">No.</th><th>Course</th><th>Schedule</th><th>Room</th><th style="text-align: center;">Action</th></tr>
                <?php $stt = 1; foreach ($my_classes as $row): ?>
                    <tr>
                        <td style="text-align: center;"><strong><?= $stt++ ?></strong></td>
                        <td><strong><?= htmlspecialchars($row['course_name']) ?></strong></td>
                        <td><?= htmlspecialchars($row['schedule']) ?></td>
                        <td><?= htmlspecialchars($row['room']) ?></td>
                        <td style="text-align: center;"><form method="POST" onsubmit="return confirm('Delete this class?');"><input type="hidden" name="class_id" value="<?= $row['id'] ?>"><button type="submit" name="delete_class" class="btn-delete">Delete</button></form></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
