<?php
session_start();

include '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['user_role'] !== 'student') {
    die("Access denied");
}

$student_id = $_SESSION['user_id'];

$sql = "SELECT * FROM students WHERE id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $student_id);

$stmt->execute();

$result = $stmt->get_result();

$student = $result->fetch_assoc();

if (!$student) {
    die("Student not found");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
</head>
<body>

<h2>My Profile</h2>

<p>
Name:
<?= htmlspecialchars($student['name']) ?>
</p>

<p>
Date of Birth:
<?= htmlspecialchars($student['dob']) ?>
</p>

<p>
Major:
<?= htmlspecialchars($student['major']) ?>
</p>

<p>
Contact:
<?= htmlspecialchars($student['contact']) ?>
</p>

</body>
</html>