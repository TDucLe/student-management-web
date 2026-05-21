<?php
require_once 'config.php';
requireLogin();
$stmt_t = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?"); $stmt_t->execute([$_SESSION['user_id']]); $teacher_id = $stmt_t->fetchColumn();

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sched'])) {
    try {
        $pdo->prepare("INSERT INTO schedules (class_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)")
            ->execute([$_POST['class_id'], $_POST['day'], $_POST['start'], $_POST['end']]);
        $message = "<div class='success'>✅ Schedule added!</div>";
    } catch (PDOException $e) { $message = "<div class='error'>❌ Error adding schedule.</div>"; }
}
if (isset($_POST['delete_sched'])) { $pdo->prepare("DELETE FROM schedules WHERE id = ?")->execute([$_POST['sched_id']]); }

$my_classes = $pdo->prepare("SELECT id, class_name FROM classes WHERE teacher_id = ?"); $my_classes->execute([$teacher_id]); $classes = $my_classes->fetchAll();
$schedules = $pdo->prepare("SELECT s.id, cl.class_name, c.course_name, s.day_of_week, s.start_time, s.end_time FROM schedules s JOIN classes cl ON s.class_id = cl.id JOIN courses c ON cl.course_id = c.id WHERE cl.teacher_id = ? ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time");
$schedules->execute([$teacher_id]); $scheds = $schedules->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>My Schedule</title>
<style>body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;padding:20px;margin:0} .container{max-width:900px;margin:auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1)} h2{color:#333;border-bottom:2px solid #667eea;padding-bottom:10px} table{width:100%;border-collapse:collapse;margin-top:20px} th,td{border-bottom:1px solid #eee;padding:12px;text-align:left} th{background:#f9f9f9} input,select{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;box-sizing:border-box} .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px} .btn-add{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;padding:12px;border-radius:5px;cursor:pointer;font-weight:bold;width:100%} .btn-del{background:#fee2e2;color:#ef4444;border:none;padding:6px 12px;border-radius:4px;cursor:pointer} .btn-back{display:inline-block;margin-bottom:20px;text-decoration:none;color:#667eea;font-weight:bold} .success{background:#dcfce7;color:#166534;padding:12px;border-radius:5px;margin-bottom:15px}</style>
</head><body><div class="container"><a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
<h2>🗓️ Add Timetable</h2><?= $message ?>
<form method="POST"><div class="form-grid">
    <div><label>Class:</label><select name="class_id" required><option value="">-- Select Class --</option><?php foreach($classes as $c) echo "<option value='{$c['id']}'>{$c['class_name']}</option>"; ?></select></div>
    <div><label>Day of Week:</label><select name="day" required><option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option></select></div>
    <div><label>Start Time:</label><input type="time" name="start" required></div>
    <div><label>End Time:</label><input type="time" name="end" required></div>
</div><button type="submit" name="add_sched" class="btn-add">Add Schedule</button></form>
<h2 style="margin-top: 40px;">📋 Weekly Routine</h2>
<?php if(count($scheds)>0): ?><table><tr><th>Class</th><th>Day</th><th>Time</th><th>Action</th></tr>
<?php foreach ($scheds as $row): ?><tr><td><strong><?= htmlspecialchars($row['class_name']) ?></strong></td><td><span style="background:#e0e7ff;color:#4f46e5;padding:4px 8px;border-radius:4px;"><?= $row['day_of_week'] ?></span></td><td><?= date('H:i', strtotime($row['start_time'])) ?> - <?= date('H:i', strtotime($row['end_time'])) ?></td><td><form method="POST"><input type="hidden" name="sched_id" value="<?= $row['id'] ?>"><button type="submit" name="delete_sched" class="btn-del">Del</button></form></td></tr><?php endforeach; ?></table><?php endif; ?>
</div></body></html>
