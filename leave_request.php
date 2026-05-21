<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$sid_q = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$sid_q->bind_param("i", $_SESSION['user_id']);
$sid_q->execute();
$stu = $sid_q->get_result()->fetch_assoc();
$student_id = $stu['id'];

$classes = $conn->prepare("SELECT cl.id, cl.class_name, c.course_name
                           FROM enrollments e
                           JOIN classes cl ON e.class_id = cl.id
                           JOIN courses c ON cl.course_id = c.id
                           WHERE e.student_id = ?");
$classes->bind_param("i", $student_id);
$classes->execute();
$myClasses = $classes->get_result();

if(isset($_POST['submit'])){
    $class_id = $_POST['class_id'];
    $reason = trim($_POST['reason']);
    $leave_date = $_POST['leave_date'];
    if(empty($reason)) $error = "Reason required.";
    else {
        $chk = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND class_id = ?");
        $chk->bind_param("ii", $student_id, $class_id);
        $chk->execute();
        if($chk->get_result()->num_rows==0) $error = "Invalid class.";
        else {
            $ins = $conn->prepare("INSERT INTO leave_requests (student_id, class_id, reason, leave_date, status) VALUES (?,?,?,?,'pending')");
            $ins->bind_param("iiss", $student_id, $class_id, $reason, $leave_date);
            $ins->execute();
            header("Location: leave_status.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Leave Request</title></head>
<body>
<h2>Leave Request</h2>
<?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
<form method="POST">
<label>Class:</label>
<select name="class_id" required>
<?php while($c = $myClasses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?> (<?= htmlspecialchars($c['course_name']) ?>)</option>
<?php endwhile; ?>
</select><br>
<label>Reason:</label><textarea name="reason" required></textarea><br>
<label>Date:</label><input type="date" name="leave_date" required><br>
<button type="submit" name="submit">Submit</button>
</form>
<a href="leave_status.php">View requests</a> | <a href="../index.php">Back</a>
</body>
</html>
