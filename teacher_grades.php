<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if ($user['role'] !== 'teacher') die("Access denied.");

$teacher_id = $user['id'];
$message = "";

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_grade'])) {
    $course_id = $_POST['course_id'];
    $student_id = $_POST['student_id'];
    $grade = filter_var($_POST['grade'], FILTER_VALIDATE_FLOAT);

    if ($grade === false || $grade < 0 || $grade > 10) {
        $message = "<div class='error'>❌ Error: Grade must be between 0.00 and 10.00.</div>";
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND course_id = ?");
            $check->execute([$student_id, $course_id]);
            
            if ($check->fetch()) {
                $pdo->prepare("UPDATE grades SET grade = ? WHERE student_id = ? AND course_id = ?")->execute([$grade, $student_id, $course_id]);
                $message = "<div class='success'>✅ Grade updated successfully!</div>";
            } else {
                $pdo->prepare("INSERT INTO grades (student_id, course_id, grade) VALUES (?, ?, ?)")->execute([$student_id, $course_id, $grade]);
                $message = "<div class='success'>✅ Grade recorded successfully!</div>";
            }
        } catch (PDOException $e) {}
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_grade'])) {
    try {
        $pdo->prepare("DELETE FROM grades WHERE id = ?")->execute([$_POST['grade_id']]);
        $message = "<div class='success'>🗑️ Grade deleted!</div>";
    } catch (PDOException $e) {}
}

$stmt_courses = $pdo->prepare("SELECT id, name FROM courses WHERE teacher_id = ?");
$stmt_courses->execute([$teacher_id]);
$my_courses = $stmt_courses->fetchAll();

$students_res = $pdo->query("SELECT id, name FROM students ORDER BY name ASC")->fetchAll();

$stmt_history = $pdo->prepare("SELECT g.id, c.name AS course_name, s.name AS student_name, g.grade 
                                FROM grades g JOIN courses c ON g.course_id = c.id JOIN students s ON g.student_id = s.id 
                                WHERE c.teacher_id = ? ORDER BY c.name ASC, g.grade DESC");
$stmt_history->execute([$teacher_id]);
$history_res = $stmt_history->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Manage Grades</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f5f5f5; padding: 20px; margin: 0; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-top: 0; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; } th, td { border-bottom: 1px solid #eee; padding: 12px; text-align: left; } th { background: #f9f9f9; color: #333; }
        .form-group { margin-bottom: 15px; } label { display: block; margin-bottom: 8px; font-weight: 500; color: #555;}
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box; }
        .btn-add { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; width: 100%; }
        .btn-delete { background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; }
        .btn-back { display: inline-block; margin-bottom: 20px; padding: 8px 15px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
        <h2>📊 Input & Manage Grades</h2>
        <?= $message ?>
        <form method="POST">
            <div class="form-group"><label>Course:</label><select name="course_id" required><option value="">-- Choose Course --</option><?php foreach($my_courses as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Student:</label><select name="student_id" required><option value="">-- Choose Student --</option><?php foreach($students_res as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Grade (0.00 - 10.00):</label><input type="text" name="grade" required placeholder="e.g. 8.5"></div>
            <button type="submit" name="submit_grade" class="btn-add">Save Grade</button>
        </form>

        <h2 style="margin-top: 40px;">📋 Grades Record</h2>
        <?php if (count($history_res) > 0): ?>
            <table>
                <tr><th style="width: 50px; text-align: center;">No.</th><th>Course</th><th>Student</th><th style="text-align: center;">Grade</th><th style="text-align: center;">Action</th></tr>
                <?php $stt = 1; foreach ($history_res as $row): ?>
                    <tr>
                        <td style="text-align: center;"><strong><?= $stt++ ?></strong></td>
                        <td><?= htmlspecialchars($row['course_name']) ?></td>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td style="text-align: center;"><strong style="color: #667eea; font-size: 18px;"><?= htmlspecialchars($row['grade']) ?></strong></td>
                        <td style="text-align: center;"><form method="POST" onsubmit="return confirm('Delete?');"><input type="hidden" name="grade_id" value="<?= $row['id'] ?>"><button type="submit" name="delete_grade" class="btn-delete">Delete</button></form></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>