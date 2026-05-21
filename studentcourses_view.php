<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$sql = "SELECT DISTINCT c.course_code, c.course_name, c.credits, c.department
        FROM enrollments e
        JOIN classes cl ON e.class_id = cl.id
        JOIN courses c ON cl.course_id = c.id
        WHERE e.student_id = (SELECT id FROM students WHERE user_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head><title>My Courses</title></head>
<body>
<h2>My Courses</h2>
<table border="1"><tr><th>Code</th><th>Name</th><th>Credits</th><th>Department</th></tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr><td><?= htmlspecialchars($row['course_code']) ?></td><td><?= htmlspecialchars($row['course_name']) ?></td><td><?= $row['credits'] ?></td><td><?= htmlspecialchars($row['department']) ?></td></tr>
<?php endwhile; ?>
</table>
<a href="../index.php">Back</a>
</body>
</html>
