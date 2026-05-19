<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

$sql = "SELECT schedules.date, schedules.time, schedules.room, courses.name as course_name
        FROM schedules
        JOIN courses ON schedules.course_id = courses.id
        WHERE schedules.user_id = ? AND schedules.role = 'student'
        ORDER BY schedules.date ASC, schedules.time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Schedule</title>
<link rel="stylesheet" href="../frontend/css/style.css">
<style>
.calendar-table { width:100%; border-collapse:collapse; }
.calendar-table th, .calendar-table td { border:1px solid #ccc; padding:8px; text-align:left; }
</style>
</head>
<body>
<div class="container">
<h2>My Schedule</h2>
<table class="calendar-table">
<tr><th>Date</th><th>Time</th><th>Course</th><th>Room</th></tr>
<?php if($result->num_rows == 0): ?>
<tr><td colspan="4">No schedule found.</td></tr>
<?php else: ?>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['date']) ?></td>
<td><?= htmlspecialchars($row['time']) ?></td>
<td><?= htmlspecialchars($row['course_name']) ?></td>
<td><?= htmlspecialchars($row['room']) ?></td>
</tr>
<?php endwhile; ?>
<?php endif; ?>
</table>
<a href="../index.php">Back</a>
</div>
</body>
</html>