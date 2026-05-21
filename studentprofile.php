<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$sql = "SELECT s.full_name, s.student_code, s.dob, s.major, s.contact, s.address, u.username
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE s.user_id = ? AND s.deleted_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
if (!$student) die("Student not found.");
?>
<!DOCTYPE html>
<html>
<head><title>Profile</title></head>
<body>
<h2>Student Profile</h2>
<p><strong>Username:</strong> <?= htmlspecialchars($student['username']) ?></p>
<p><strong>Student Code:</strong> <?= htmlspecialchars($student['student_code']) ?></p>
<p><strong>Full Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
<p><strong>Date of Birth:</strong> <?= htmlspecialchars($student['dob']) ?></p>
<p><strong>Major:</strong> <?= htmlspecialchars($student['major']) ?></p>
<p><strong>Contact:</strong> <?= htmlspecialchars($student['contact']) ?></p>
<p><strong>Address:</strong> <?= htmlspecialchars($student['address']) ?></p>
<a href="../index.php">Back</a>
</body>
</html>
