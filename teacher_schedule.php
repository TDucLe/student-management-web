<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if ($user['role'] !== 'teacher') {
    die("Access denied. Teachers only.");
}

$teacher_id = $user['id'];
$message = "";

// 1. HANDLE ADD SCHEDULE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_schedule'])) {
    $course_id = $_POST['course_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $room = trim($_POST['room']);

    try {
        $stmt = $pdo->prepare("INSERT INTO schedules (user_id, role, date, time, course_id, room) VALUES (?, 'teacher', ?, ?, ?, ?)");
        $stmt->execute([$teacher_id, $date, $time, $course_id, $room]);
        $message = "<div class='success'>✅ New schedule added successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// 2. HANDLE DELETE SCHEDULE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_schedule'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['schedule_id'], $teacher_id]);
        $message = "<div class='success'>🗑️ Schedule removed!</div>";
    } catch (PDOException $e) {}
}

// 3. FETCH TEACHER'S COURSES FOR DROPDOWN
$stmt_courses = $pdo->prepare("SELECT id, name FROM courses WHERE teacher_id = ?");
$stmt_courses->execute([$teacher_id]);
$my_courses = $stmt_courses->fetchAll();

// 4. FETCH SCHEDULES
$stmt_sched = $pdo->prepare("SELECT s.id, c.name AS course_name, s.date, s.time, s.room 
                             FROM schedules s 
                             JOIN courses c ON s.course_id = c.id 
                             WHERE s.user_id = ? AND s.role = 'teacher' 
                             ORDER BY s.date ASC, s.time ASC");
$stmt_sched->execute([$teacher_id]);
$schedules = $stmt_sched->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Schedule - EduManager</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; margin: 0; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-top: 0; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; } th, td { border-bottom: 1px solid #eee; padding: 12px; text-align: left; } th { background: #f9f9f9; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .btn-add { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
        .btn-delete { background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #667eea; font-weight: bold; }
        .success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
        <h2>🗓️ My Teaching Schedule</h2>
        <?= $message ?>

        <form method="POST">
            <div class="form-grid">
                <div>
                    <label>Course:</label>
                    <select name="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($my_courses as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Room:</label>
                    <input type="text" name="room" required placeholder="e.g. Room A101">
                </div>
                <div>
                    <label>Date:</label>
                    <input type="date" name="date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label>Time:</label>
                    <input type="time" name="time" required value="08:00">
                </div>
            </div>
            <button type="submit" name="add_schedule" class="btn-add">+ Add to Schedule</button>
        </form>

        <h2 style="margin-top: 40px;">📋 Weekly/Monthly Schedule List</h2>
        <?php if (count($schedules) > 0): ?>
            <table>
                <tr><th>No.</th><th>Course Name</th><th>Date</th><th>Time</th><th>Room</th><th>Action</th></tr>
                <?php $stt = 1; foreach ($schedules as $row): ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td><strong><?= htmlspecialchars($row['course_name']) ?></strong></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['date']))) ?></td>
                        <td><?= htmlspecialchars(date('H:i', strtotime($row['time']))) ?></td>
                        <td><span style="background: #e0e7ff; color: #4f46e5; padding: 4px 8px; border-radius: 4px; font-weight: 500;"><?= htmlspecialchars($row['room']) ?></span></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Remove this schedule?');">
                                <input type="hidden" name="schedule_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_schedule" class="btn-delete">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p style="color: #666; text-align: center; padding: 20px;">No schedules set yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>