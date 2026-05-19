<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

$sql = "SELECT co.name, co.credits, g.grade
        FROM grades g
        JOIN courses co ON g.course_id = co.id
        WHERE g.student_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = []; // Khởi tạo mảng
$total = 0;
$count = 0;

while($r = $result->fetch_assoc()){
    $rows[] = $r;
    $total += $r['grade'];
    $count++;
}

$gpa = $count > 0 ? round($total/$count, 2) : 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Grades</title>
<link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body>
<div class="container">
<h2>Grades</h2>
<p><b>GPA:</b> <?= $gpa ?></p>
<table border="1">
<tr><th>Course</th><th>Credits</th><th>Grade</th></tr>
<?php foreach($rows as $row): ?>
<tr>
<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= htmlspecialchars($row['credits']) ?></td>
<td><?= htmlspecialchars($row['grade']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<a href="../index.php">Back</a>
</div>
</body>
</html>