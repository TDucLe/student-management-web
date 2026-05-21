<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$sql = "SELECT a.attendance_date, a.status, a.note, cl.class_name, c.course_name
        FROM attendance a
        JOIN enrollments e ON a.enrollment_id = e.id
        JOIN classes cl ON e.class_id = cl.id
        JOIN courses c ON cl.course_id = c.id
        WHERE e.student_id = (SELECT id FROM students WHERE user_id = ?)
        ORDER BY a.attendance_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head><title>Attendance</title><style>.present{color:green}.absent{color:red}.late{color:orange}</style></head>
<body>
<h2>Attendance History</h2>
<table border="1"><tr><th>Course</th><th>Class</th><th>Date</th><th>Status</th><th>Note</th></tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['course_name']) ?></td>
<td><?= htmlspecialchars($row['class_name']) ?></td>
<td><?= htmlspecialchars($row['attendance_date']) ?></td>
<td class="<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
<td><?= htmlspecialchars($row['note']) ?></td>
</tr>
<?php endwhile; ?>
</table>
<a href="../index.php">Back</a>
</body>
</html>
