<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$sid_q = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$sid_q->bind_param("i", $_SESSION['user_id']);
$sid_q->execute();
$stu = $sid_q->get_result()->fetch_assoc();
$student_id = $stu['id'];

$sql = "SELECT lr.*, cl.class_name, c.course_name
        FROM leave_requests lr
        JOIN classes cl ON lr.class_id = cl.id
        JOIN courses c ON cl.course_id = c.id
        WHERE lr.student_id = ?
        ORDER BY lr.leave_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head><title>Leave Status</title>
<style>.pending{color:orange}.approved{color:green}.rejected{color:red}</style>
</head>
<body>
<h2>Leave Requests</h2>
<table border="1"><tr><th>Class</th><th>Course</th><th>Reason</th><th>Date</th><th>Status</th></tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['class_name']) ?></td>
<td><?= htmlspecialchars($row['course_name']) ?></td>
<td><?= htmlspecialchars($row['reason']) ?></td>
<td><?= htmlspecialchars($row['leave_date']) ?></td>
<td class="<?= $row['status'] ?>"><?= strtoupper($row['status']) ?></td>
</tr>
<?php endwhile; ?>
</table>
<a href="leave_request.php">New Request</a> | <a href="../index.php">Back</a>
</body>
</html>
