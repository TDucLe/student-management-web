<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$sql = "SELECT sc.day_of_week, sc.start_time, sc.end_time, cl.class_name, c.course_name, r.room_number
        FROM schedules sc
        JOIN classes cl ON sc.class_id = cl.id
        JOIN courses c ON cl.course_id = c.id
        JOIN rooms r ON cl.room_id = r.id
        JOIN enrollments e ON e.class_id = cl.id
        WHERE e.student_id = (SELECT id FROM students WHERE user_id = ?)
        ORDER BY FIELD(sc.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), sc.start_time";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head><title>Schedule</title></head>
<body>
<h2>Weekly Schedule</h2>
<table border="1"><tr><th>Day</th><th>Time</th><th>Class</th><th>Course</th><th>Room</th></tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['day_of_week']) ?></td>
<td><?= date('H:i', strtotime($row['start_time'])) ?> - <?= date('H:i', strtotime($row['end_time'])) ?></td>
<td><?= htmlspecialchars($row['class_name']) ?></td>
<td><?= htmlspecialchars($row['course_name']) ?></td>
<td><?= htmlspecialchars($row['room_number']) ?></td>
</tr>
<?php endwhile; ?>
</table>
<a href="../index.php">Back</a>
</body>
</html>
