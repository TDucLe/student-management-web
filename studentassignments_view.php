<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Xử lý nộp bài tập
if(isset($_POST['submit'])){
    $assignment_id = intval($_POST['assignment_id']);
    $file_url = '';

    // Kiểm tra assignment có tồn tại và deadline
    $check_assign = $conn->prepare("SELECT deadline FROM assignments WHERE id = ?");
    $check_assign->bind_param("i", $assignment_id);
    $check_assign->execute();
    $assign_res = $check_assign->get_result();
    if($assign_res->num_rows == 0){
        $error = "Invalid assignment.";
    } else {
        $deadline = $assign_res->fetch_assoc()['deadline'];
        $today = date('Y-m-d');
        if($today > $deadline){
            $error = "Cannot submit after deadline ($deadline).";
        } else {
            // Kiểm tra xem đã nộp chưa
            $check_sub = $conn->prepare("SELECT id FROM submissions WHERE assignment_id = ? AND student_id = ?");
            $check_sub->bind_param("ii", $assignment_id, $student_id);
            $check_sub->execute();
            if($check_sub->get_result()->num_rows > 0){
                $error = "You have already submitted this assignment.";
            } else {
                // Xử lý upload file
                if(isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == 0){
                    $target_dir = "../uploads/";
                    if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                    $file_name = time() . "_" . basename($_FILES['assignment_file']['name']);
                    $target_file = $target_dir . $file_name;
                    if(move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_file)){
                        $file_url = "uploads/" . $file_name;
                    } else {
                        $error = "Failed to upload file.";
                    }
                } else {
                    $error = "Please select a file to upload.";
                }
                
                if(empty($error)){
                    $insert = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, file_url, submitted_at) VALUES (?, ?, ?, NOW())");
                    $insert->bind_param("iis", $assignment_id, $student_id, $file_url);
                    if($insert->execute()){
                        $message = "Assignment submitted successfully.";
                    } else {
                        $error = "Database error: " . $conn->error;
                    }
                }
            }
        }
    }
}

// Lấy danh sách assignment của student (chưa nộp và đã nộp)
$sql_assignments = "SELECT a.*, co.name AS course_name,
                    (SELECT id FROM submissions WHERE assignment_id = a.id AND student_id = ?) as submitted_id
                    FROM assignments a
                    JOIN enrollments e ON e.course_id = a.course_id
                    JOIN courses co ON a.course_id = co.id
                    WHERE e.student_id = ?
                    ORDER BY a.deadline ASC";
$stmt = $conn->prepare($sql_assignments);
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$assignments = $stmt->get_result();

// Lấy danh sách bài đã nộp (kèm điểm nếu có)
$sql_submitted = "SELECT s.*, a.title, a.deadline, co.name as course_name
                  FROM submissions s
                  JOIN assignments a ON s.assignment_id = a.id
                  JOIN courses co ON a.course_id = co.id
                  WHERE s.student_id = ?
                  ORDER BY s.submitted_at DESC";
$stmt_sub = $conn->prepare($sql_submitted);
$stmt_sub->bind_param("i", $student_id);
$stmt_sub->execute();
$submitted = $stmt_sub->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Assignments</title>
<link rel="stylesheet" href="../frontend/css/style.css">
<style>
body{ font-family:Arial; padding:20px; }
table{ width:100%; border-collapse:collapse; margin-bottom:20px; }
th,td{ border:1px solid #ccc; padding:10px; }
.success{ color:green; }
.error{ color:red; }
</style>
</head>
<body>

<h2>My Assignments</h2>

<?php if($message): ?>
<p class="success"><?= htmlspecialchars($message) ?></p>
<?php elseif($error): ?>
<p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h3>Pending Assignments</h3>
<table>
<tr><th>Course</th><th>Title</th><th>Deadline</th><th>Status</th><th>Action</th></tr>
<?php 
$has_pending = false;
while($row = $assignments->fetch_assoc()): 
    $has_pending = true;
    $status = $row['submitted_id'] ? "Submitted" : (date('Y-m-d') > $row['deadline'] ? "Overdue" : "Pending");
    $status_class = ($status == "Submitted") ? "success" : (($status == "Overdue") ? "error" : "");
?>
<tr>
<td><?= htmlspecialchars($row['course_name']) ?></td>
<td><?= htmlspecialchars($row['title']) ?></td>
<td><?= htmlspecialchars($row['deadline']) ?></td>
<td class="<?= $status_class ?>"><?= $status ?></td>
<td>
<?php if(!$row['submitted_id'] && date('Y-m-d') <= $row['deadline']): ?>
<form method="POST" enctype="multipart/form-data" style="display:inline;">
    <input type="hidden" name="assignment_id" value="<?= $row['id'] ?>">
    <input type="file" name="assignment_file" required>
    <button type="submit" name="submit">Upload & Submit</button>
</form>
<?php elseif($row['submitted_id']): ?>
Already submitted
<?php else: ?>
Expired
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
<?php if(!$has_pending): ?>
<tr><td colspan="5">No assignments found.</td></tr>
<?php endif; ?>
</table>

<h3>Submitted Assignments</h3>
<table>
<tr><th>Course</th><th>Title</th><th>Submitted File</th><th>Submission Date</th><th>Grade</th></tr>
<?php 
$has_submitted = false;
while($sub = $submitted->fetch_assoc()): 
    $has_submitted = true;
?>
<tr>
<td><?= htmlspecialchars($sub['course_name']) ?></td>
<td><?= htmlspecialchars($sub['title']) ?></td>
<td><a href="../<?= htmlspecialchars($sub['file_url']) ?>" target="_blank">View File</a></td>
<td><?= htmlspecialchars($sub['submitted_at']) ?></td>
<td><?= isset($sub['grade']) ? htmlspecialchars($sub['grade']) : 'Not graded yet' ?></td>
</tr>
<?php endwhile; ?>
<?php if(!$has_submitted): ?>
<tr><td colspan="5">No submissions yet.</td></tr>
<?php endif; ?>
</table>

<a href="../index.php">Back to Dashboard</a>
</body>
</html>