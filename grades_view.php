<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$student_id_q = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$student_id_q->bind_param("i", $_SESSION['user_id']);
$student_id_q->execute();
$stu = $student_id_q->get_result()->fetch_assoc();
$sid = $stu['id'];

// Điểm thi
$exam = $conn->prepare("SELECT er.score, e.exam_name, e.max_score, c.course_name
                        FROM exam_results er
                        JOIN exams e ON er.exam_id = e.id
                        JOIN classes cl ON e.class_id = cl.id
                        JOIN courses c ON cl.course_id = c.id
                        WHERE er.student_id = ?");
$exam->bind_param("i", $sid);
$exam->execute();
$exams = $exam->get_result();

// Điểm bài tập
$sub = $conn->prepare("SELECT sub.score, a.title AS exam_name, a.max_score, c.course_name
                       FROM submissions sub
                       JOIN assignments a ON sub.assignment_id = a.id
                       JOIN classes cl ON a.class_id = cl.id
                       JOIN courses c ON cl.course_id = c.id
                       WHERE sub.student_id = ?");
$sub->bind_param("i", $sid);
$sub->execute();
$subs = $sub->get_result();

$all = [];
while($r = $exams->fetch_assoc()) $all[] = $r;
while($r = $subs->fetch_assoc()) $all[] = $r;
?>
<!DOCTYPE html>
<html>
<head><title>Grades</title></head>
<body>
<h2>My Grades</h2>
<table border="1"><tr><th>Course</th><th>Assessment</th><th>Score / Max</th><th>%</th></tr>
<?php foreach($all as $g): 
    $p = ($g['score']/$g['max_score'])*100;
?>
<tr>
<td><?= htmlspecialchars($g['course_name']) ?></td>
<td><?= htmlspecialchars($g['exam_name']) ?></td>
<td><?= $g['score'] ?> / <?= $g['max_score'] ?></td>
<td><?= round($p,2) ?>%</td>
</tr>
<?php endforeach; ?>
</table>
<a href="../index.php">Back</a>
</body>
</html>
