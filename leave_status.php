<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

$sql = "SELECT lr.*, c.room, co.name as course_name
        FROM leave_requests lr
        JOIN classes cl ON lr.class_id = cl.id
        JOIN courses co ON cl.course_id = co.id
        LEFT JOIN classes c ON lr.class_id = c.id
        WHERE lr.student_id = ?
        ORDER BY lr.date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Leave Status</title>
<link rel="stylesheet" href="../frontend/css/style.css">
<style>
.pending{ color:orange; font-weight:bold; }
.approved{ color:green; font-weight:bold; }
.rejected{ color:red; font-weight:bold; }
</style>
</head>
<body>
<div class="container">
<h2>My Leave Requests</h2>
<table border="1">
<tr><th>Course</th><th>Reason</th><th>Date</th><th>Status</th></tr>
<?php if($result->num_rows == 0): ?>
<tr><td colspan="4">No leave requests found.</td></tr>
<?php else: ?>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['course_name']) ?></td>
<td><?= htmlspecialchars($row['reason']) ?></td>
<td><?= htmlspecialchars($row['date']) ?></td>
<td class="<?= $row['status'] ?>"><?= strtoupper($row['status']) ?></td>
</tr>
<?php endwhile; ?>
<?php endif; ?>
</table>
<a href="leave_request.php">New Request</a> | <a href="../index.php">Back</a>
</div>
</body>
</html>