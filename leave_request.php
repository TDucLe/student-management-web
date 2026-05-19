<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Lấy danh sách các lớp sinh viên đã đăng ký (để hiển thị dropdown)
$classes_sql = "SELECT cl.id, co.name as course_name
                FROM classes cl
                JOIN courses co ON cl.course_id = co.id
                JOIN enrollments e ON e.course_id = co.id
                WHERE e.student_id = ?";
$stmt = $conn->prepare($classes_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$my_classes = $stmt->get_result();

if(isset($_POST['submit'])){
    $class_id = intval($_POST['class_id']);
    $reason = trim($_POST['reason']);
    $date = $_POST['date'];
    
    if(empty($reason)){
        $error = "Reason is required.";
    } else {
        // Kiểm tra class_id có thuộc sinh viên này không
        $check_class = $conn->prepare("SELECT id FROM classes cl
                                       JOIN enrollments e ON e.course_id = cl.course_id
                                       WHERE cl.id = ? AND e.student_id = ?");
        $check_class->bind_param("ii", $class_id, $student_id);
        $check_class->execute();
        if($check_class->get_result()->num_rows == 0){
            $error = "Invalid class selected.";
        } else {
            $insert = $conn->prepare("INSERT INTO leave_requests (student_id, class_id, reason, date, status) VALUES (?, ?, ?, ?, 'pending')");
            $insert->bind_param("iiss", $student_id, $class_id, $reason, $date);
            if($insert->execute()){
                $success = "Leave request submitted successfully.";
                // Reset form hoặc chuyển hướng sau 2 giây
                header("refresh:2;url=leave_status.php");
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Leave Request</title>
<link rel="stylesheet" href="../frontend/css/style.css">
<style>
form{ width:500px; margin-top:20px; }
input, textarea, select{ width:100%; padding:8px; margin:5px 0 15px; }
</style>
</head>
<body>
<div class="container">
<h2>Submit Leave Request</h2>
<?php if($error): ?>
<p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php elseif($success): ?>
<p style="color:green;"><?= htmlspecialchars($success) ?> Redirecting to status...</p>
<?php endif; ?>

<form method="POST">
<label>Class:</label>
<select name="class_id" required>
<option value="">-- Select Class --</option>
<?php while($row = $my_classes->fetch_assoc()): ?>
<option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['course_name']) ?></option>
<?php endwhile; ?>
</select>

<label>Reason:</label>
<textarea name="reason" rows="4" required></textarea>

<label>Date of absence:</label>
<input type="date" name="date" required>

<button type="submit" name="submit">Submit Request</button>
</form>
<a href="leave_status.php">View my leave requests</a> | <a href="../index.php">Back</a>
</div>
</body>
</html>