<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$sql = "SELECT cl.class_name, c.course_name, s.name AS semester_name, r.room_number,
               sc.day_of_week, sc.start_time, sc.end_time
        FROM enrollments e
        JOIN classes cl ON e.class_id = cl.id
        JOIN courses c ON cl.course_id = c.id
        LEFT JOIN semesters s ON cl.semester_id = s.id
        LEFT JOIN rooms r ON cl.room_id = r.id
        LEFT JOIN schedules sc ON sc.class_id = cl.id
        WHERE e.student_id = (SELECT id FROM students WHERE user_id = ?)
        ORDER BY sc.day_of_week, sc.start_time";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head><title>My Classes</title></head>
<body>
<h2>My Classes & Schedule</h2>
<table border="1"><tr><th>Class</th><th>Course</th><th>Semester</th><th>Room</th><th>Day</th><th>Time</th></tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['class_name']) ?></td>
<td><?= htmlspecialchars($row['course_name']) ?></td>
<td><?= htmlspecialchars($row['semester_name']) ?></td>
<td><?= htmlspecialchars($row['room_number']) ?></td>
<td><?= htmlspecialchars($row['day_of_week']) ?></td>
<td><?= date('H:i', strtotime($row['start_time'])) ?> - <?= date('H:i', strtotime($row['end_time'])) ?></td>
</tr>
<?php endwhile; ?>
</table>
<a href="../index.php">Back</a>
</body>
</html>
