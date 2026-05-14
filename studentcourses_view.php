<?php
session_start();

include '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (strtolower($_SESSION['user_role']) !== 'student') {
    die("Access denied");
}

$student_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET REGISTERED COURSES ONLY
|--------------------------------------------------------------------------
*/

$sql = "SELECT courses.id,
               courses.name,
               courses.credits,
               teachers.name AS teacher_name
        FROM student_courses

        JOIN courses
        ON student_courses.course_id = courses.id

        JOIN teachers
        ON courses.teacher_id = teachers.id

        WHERE student_courses.student_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $student_id);

$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>My Courses</title>

</head>

<body>

<h2>My Courses</h2>

<table border="1">

<tr>
    <th>Course</th>
    <th>Credits</th>
    <th>Teacher</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>

<tr>

<td>
<?= htmlspecialchars($row['name']) ?>
</td>

<td>
<?= htmlspecialchars($row['credits']) ?>
</td>

<td>
<?= htmlspecialchars($row['teacher_name']) ?>
</td>

</tr>

<?php endwhile; ?>

</table>

</body>
</html>