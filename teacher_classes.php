<?php
session_start();
include 'config.php';
include 'auth.php';

checkLogin();
if ($_SESSION['user_role'] !== 'teacher') {
    die("Access denied. This page is for Teachers only.");
}

$teacher_id = $_SESSION['user_id'];
$message = "";

// 1. XỬ LÝ THÊM LỚP HỌC (Giữ nguyên)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_class'])) {
    $name = $_POST['name'];
    $schedule = $_POST['schedule'];

    $sql_insert = "INSERT INTO classes (name, teacher_id, schedule) VALUES (?, ?, ?)";
    $stmt_in = $conn->prepare($sql_insert);
    $stmt_in->bind_param("sis", $name, $teacher_id, $schedule);
    
    if ($stmt_in->execute()) {
        $message = "<p style='color: green;'>Class created successfully!</p>";
    } else {
        $message = "<p style='color: red;'>Error creating class: " . $stmt_in->error . "</p>";
    }
}

// 2. XỬ LÝ XÓA LỚP HỌC (Phần mới thêm)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_class'])) {
    $class_id = $_POST['class_id'];

    // Bảo mật: Chỉ cho phép xóa nếu lớp đó thuộc về giáo viên đang đăng nhập
    $sql_delete = "DELETE FROM classes WHERE id = ? AND teacher_id = ?";
    $stmt_del = $conn->prepare($sql_delete);
    $stmt_del->bind_param("ii", $class_id, $teacher_id);
    
    if ($stmt_del->execute()) {
        $message = "<p style='color: orange;'>Class deleted successfully!</p>";
    } else {
        $message = "<p style='color: red;'>Error deleting class: " . $stmt_del->error . "</p>";
    }
}

// 3. TRUY VẤN LẤY DANH SÁCH LỚP HỌC (Giữ nguyên)
$sql_select = "SELECT id, name, schedule FROM classes WHERE teacher_id = ?";
$stmt_sel = $conn->prepare($sql_select);
$stmt_sel->bind_param("i", $teacher_id);
$stmt_sel->execute();
$classes = $stmt_sel->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Classes</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #17a2b8; color: white; }
        .form-group { margin-bottom: 15px; }
        input[type="text"] { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn-add { padding: 10px 15px; background-color: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px; }
        .btn-delete { background-color: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }
        .btn-delete:hover { background-color: #c82333; }
        .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn-back">&larr; Back to Dashboard</a>
        
        <h2>Create New Class</h2>
        <?= $message ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Class Name:</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Schedule:</label>
                <input type="text" name="schedule" required>
            </div>
            <button type="submit" name="add_class" class="btn-add">Create Class</button>
        </form>

        <hr style="margin: 30px 0;">

        <h2>My Classes</h2>
        <?php if ($classes->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Class ID</th>
                    <th>Class Name</th>
                    <th>Schedule</th>
                    <th>Action</th> </tr>
                <?php while ($row = $classes->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['schedule']) ?></td>
                        <td>
                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this class?');" style="display:inline;">
                                <input type="hidden" name="class_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_class" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>You have not created any classes yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
