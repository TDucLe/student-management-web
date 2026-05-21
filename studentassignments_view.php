<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$sid_q = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$sid_q->bind_param("i", $_SESSION['user_id']);
$sid_q->execute();
$stu = $sid_q->get_result()->fetch_assoc();
$student_id = $stu['id'];

$message = $error = '';

if(isset($_POST['submit'])){
    $assignment_id = $_POST['assignment_id'];
    // check deadline
    $dl = $conn->prepare("SELECT deadline FROM assignments WHERE id = ?");
    $dl->bind_param("i", $assignment_id);
    $dl->execute();
    $deadline = $dl->get_result()->fetch_assoc()['deadline'];
    if(date('Y-m-d H:i:s') > $deadline) $error = "Deadline passed.";
    else {
        $check = $conn->prepare("SELECT id FROM submissions WHERE assignment_id = ? AND student_id = ?");
        $check->bind_param("ii", $assignment_id, $student_id);
        $check->execute();
        if($check->get_result()->num_rows > 0) $error = "Already submitted.";
        else {
            if(isset($_FILES['file']) && $_FILES['file']['error']==0){
                $target_dir = "../uploads/";
                if(!is_dir($target_dir)) mkdir($target_dir,0777,true);
                $file_name = time()."_".basename($_FILES['file']['name']);
                if(move_uploaded_file($_FILES['file']['tmp_name'], $target_dir.$file_name)){
                    $url = "uploads/".$file_name;
                    $ins = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, file_url, submitted_at) VALUES (?,?,?,NOW())");
                    $ins->bind_param("iis", $assignment_id, $student_id, $url);
                    $ins->execute();
                    $message = "Submitted!";
                } else $error = "Upload failed.";
            } else $error = "No file.";
        }
    }
}

// assignments
$asm = $conn->prepare("SELECT a.*, c.course_name, cl.class_name,
                       (SELECT id FROM submissions WHERE assignment_id = a.id AND student_id = ?) as sub
                       FROM assignments a
                       JOIN classes cl ON a.class_id = cl.id
                       JOIN courses c ON cl.course_id = c.id
                       JOIN enrollments e ON e.class_id = cl.id
                       WHERE e.student_id = ?
                       ORDER BY a.deadline");
$asm->bind_param("ii", $student_id, $student_id);
$asm->execute();
$assignList = $asm->get_result();

$subm = $conn->prepare("SELECT sub.*, a.title, c.course_name, a.max_score
                        FROM submissions sub
                        JOIN assignments a ON sub.assignment_id = a.id
                        JOIN classes cl ON a.class_id = cl.id
                        JOIN courses c ON cl.course_id = c.id
                        WHERE sub.student_id = ?
                        ORDER BY sub.submitted_at DESC");
$subm->bind_param("i", $student_id);
$subm->execute();
$subList = $subm->get_result();
?>
<!DOCTYPE html>
<html>
<head><title>Assignments</title></head>
<body>
<h2>Assignments</h2>
<?php if($message) echo "<p style='color:green'>$message</p>";
      if($error) echo "<p style='color:red'>$error</p>"; ?>
<table border="1"><tr><th>Course</th><th>Class</th><th>Title</th><th>Deadline</th><th>Status</th><th>Action</th></tr>
<?php while($a = $assignList->fetch_assoc()): 
    $status = $a['sub'] ? 'Submitted' : (date('Y-m-d H:i:s') > $a['deadline'] ? 'Overdue' : 'Pending');
?>
<tr>
<td><?= htmlspecialchars($a['course_name']) ?></td>
<td><?= htmlspecialchars($a['class_name']) ?></td>
<td><?= htmlspecialchars($a['title']) ?></td>
<td><?= $a['deadline'] ?></td>
<td><?= $status ?></td>
<td><?php if(!$a['sub'] && date('Y-m-d H:i:s') <= $a['deadline']): ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
        <input type="file" name="file" required>
        <button type="submit" name="submit">Upload</button>
    </form>
    <?php elseif($a['sub']): ?>✓<?php else: ?>❌<?php endif; ?>
</td></tr>
<?php endwhile; ?>
</table>

<h3>Submitted</h3>
<table border="1"><tr><th>Course</th><th>Assignment</th><th>File</th><th>Submitted At</th><th>Score</th></tr>
<?php while($s = $subList->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($s['course_name']) ?></td>
<td><?= htmlspecialchars($s['title']) ?></td>
<td><a href="../<?= $s['file_url'] ?>" target="_blank">View</a></td>
<td><?= $s['submitted_at'] ?></td>
<td><?= $s['score'] !== null ? $s['score'].' / '.$s['max_score'] : 'Not graded' ?></td>
</tr>
<?php endwhile; ?>
</table>
<a href="../index.php">Back</a>
</body>
</html>
