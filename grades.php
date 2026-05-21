<?php
session_start();
include '../config.php';
include '../auth.php';

checkLogin();

if($_SESSION['user_role'] !== 'teacher'){
    die("Access denied");
}

$teacher_id = $_SESSION['user_id'];

// STUDENTS
$students = $conn->query("
SELECT id, name
FROM users
WHERE role='student'
");

// CLASSES
$stmt = $conn->prepare("
SELECT *
FROM classes
WHERE teacher_id=?
");

$stmt->bind_param("i", $teacher_id);

$stmt->execute();

$classes = $stmt->get_result();

// SAVE
if(isset($_POST['save'])){

    $student = $_POST['student_id'];
    $class = $_POST['class_id'];
    $grade = $_POST['grade'];

    $check = $conn->prepare("
    SELECT id
    FROM grades
    WHERE student_id=? AND class_id=?
    ");

    $check->bind_param("ii", $student, $class);

    $check->execute();

    $exists = $check->get_result();

    if($exists->num_rows > 0){

        $update = $conn->prepare("
        UPDATE grades
        SET grade=?
        WHERE student_id=? AND class_id=?
        ");

        $update->bind_param(
            "sii",
            $grade,
            $student,
            $class
        );

        $update->execute();

    } else {

        $insert = $conn->prepare("
        INSERT INTO grades(student_id,class_id,grade)
        VALUES(?,?,?)
        ");

        $insert->bind_param(
            "iis",
            $student,
            $class,
            $grade
        );

        $insert->execute();
    }

    header("Location: grades.php");
    exit();
}

// READ
$stmt = $conn->prepare("
SELECT g.*, u.name as student, c.name as class
FROM grades g
JOIN users u ON g.student_id=u.id
JOIN classes c ON g.class_id=c.id
WHERE c.teacher_id=?
");

$stmt->bind_param("i", $teacher_id);

$stmt->execute();

$data = $stmt->get_result();
?>