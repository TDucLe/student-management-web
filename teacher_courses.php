<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if ($user['role'] !== 'teacher') {
    die("Access denied. Teachers only.");
}

$teacher_id = $user['id'];
$message = "";

// Bảo trì ngầm: Đảm bảo giáo viên có trong bảng teachers
try {
    $stmt_check = $pdo->prepare("SELECT id FROM teachers WHERE id = ?");
    $stmt_check->execute([$teacher_id]);
    if (!$stmt_check->fetch()) {
        $stmt_ins = $pdo->prepare("INSERT INTO teachers (id, name, department, contact) VALUES (?, ?, 'IT Department', '')");
        $stmt_ins->execute([$teacher_id, $user['username']]);
    }
} catch (PDOException $e) {}

// Thêm môn học
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $title = trim($_POST['title']);
    $credits = (int)$_POST['credits']; 
    try {
        $stmt = $pdo->prepare("INSERT INTO courses (name, credits, teacher_id) VALUES (?, ?, ?)");
        $stmt->execute([$title, $credits, $teacher_id]);
        $message = "<div class='success'>✅ Course added successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Xóa môn học
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_course'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$_POST['course_id'], $teacher_id]);
        $message = "<div class='success'>🗑️ Course deleted!</div>";
    } catch (PDOException $e) {
        $message = "<div class='error'>❌ Cannot delete this course.</div>";
    }
}

$stmt_sel = $pdo->prepare("SELECT id, name, credits FROM courses WHERE teacher_id = ? ORDER BY id DESC");
$stmt_sel->execute([$teacher_id]);
$courses = $stmt_sel->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>My Courses</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f5f5f5; padding: 20px; margin: 0; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-top: 0; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; } th, td { border-bottom: 1px solid #eee; padding: 12px; } th { background: #f9f9f9; color: #333; }
        .form-group { margin-bottom: 15px; } label { display: block; margin-bottom: 8px; font-weight: 500; color: #555;}
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box; }
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
        <h2>📚 Manage Courses</h2>
        <?= $message ?>
        <form method="POST">
            <div class="form-group"><label>Course Title:</label><input type="text" name="title" required placeholder="e.g. Database Systems"></div>
            <div class="form-group"><label>Credits:</label><input type="number" name="credits" required min="1" max="10" placeholder="e.g. 3"></div>
            <button type="submit" name="add_course" class="btn-add">+ Add Course</button>
        </form>

        <h2 style="margin-top: 40px;">📋 Course List</h2>
        <?php if (count($courses) > 0): ?>
            <table>
                <tr><th style="width: 50px; text-align: center;">No.</th><th>Course Name</th><th style="text-align: center;">Credits</th><th style="text-align: center;">Action</th></tr>
                <?php $stt = 1; foreach ($courses as $row): ?>
                    <tr>
                        <td style="text-align: center;"><strong><?= $stt++ ?></strong></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td style="text-align: center; font-weight: bold;"><?= htmlspecialchars($row['credits']) ?></td>
                        <td style="text-align: center;">
                            <form method="POST" onsubmit="return confirm('Delete this course?');"><input type="hidden" name="course_id" value="<?= $row['id'] ?>"><button type="submit" name="delete_course" class="btn-delete">Delete</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
