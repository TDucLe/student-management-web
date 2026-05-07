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
SELECT id,name
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
if(isset($_POST['mark'])){

    foreach($_POST['status'] as $student_id => $status){

        $stmt = $conn->prepare("
        INSERT INTO attendance(student_id,class_id,date,status)
        VALUES(?,?,?,?)
        ");

        $date = date('Y-m-d');

        $stmt->bind_param(
            "iiss",
            $student_id,
            $_POST['class_id'],
            $date,
            $status
        );

        $stmt->execute();
    }

    header("Location: attendance.php");
    exit();
}

// READ
$stmt = $conn->prepare("
SELECT a.*, u.name as student, c.name as class
FROM attendance a
JOIN users u ON a.student_id=u.id
JOIN classes c ON a.class_id=c.id
WHERE c.teacher_id=?
ORDER BY a.date DESC
");

$stmt->bind_param("i", $teacher_id);

$stmt->execute();

$data = $stmt->get_result();
?>