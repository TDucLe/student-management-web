<?php
session_start();
include 'config.php';
include 'auth.php';

checkLogin();
// Chỉ cho phép giáo viên truy cập
if ($_SESSION['user_role'] !== 'teacher') {
    die("Access denied. This page is for Teachers only.");
}

$teacher_id = $_SESSION['user_id'];
$message = "";

// 1. XỬ LÝ THÊM MÔN HỌC (CREATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];

    $sql_insert = "INSERT INTO courses (title, description, instructor_id) VALUES (?, ?, ?)";
    $stmt_in = $conn->prepare($sql_insert);
    $stmt_in->bind_param("ssi", $title, $description, $teacher_id);
    
    if ($stmt_in->execute()) {
        $message = "<p style='color: green;'>Course added successfully!</p>";
    } else {
        $message = "<p style='color: red;'>Error adding course: " . $stmt_in->error . "</p>";
    }
}

// 2. XỬ LÝ XÓA MÔN HỌC (DELETE) - Phần mới thêm
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_course'])) {
    $course_id = $_POST['course_id'];

    // Bảo mật: Chỉ cho phép xóa nếu môn học này do chính giáo viên này phụ trách
    $sql_delete = "DELETE FROM courses WHERE id = ? AND instructor_id = ?";
    $stmt_del = $conn->prepare($sql_delete);
    $stmt_del->bind_param("ii", $course_id, $teacher_id);
    
    if ($stmt_del->execute()) {
        $message = "<p style='color: orange;'>Course deleted successfully!</p>";
    } else {
        $message = "<p style='color: red;'>Error deleting course: " . $stmt_del->error . "</p>";
    }
}

// 3. TRUY VẤN LẤY DANH SÁCH MÔN HỌC (READ)
$sql_select = "SELECT id, title, description FROM courses WHERE instructor_id = ?";
$stmt_sel = $conn->prepare($sql_select);
$stmt_sel->bind_param("i", $teacher_id);
$stmt_sel->execute();
$courses = $stmt_sel->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; }
        .container { max-width: 900px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #007bff; color: white; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .btn-add { padding: 10px 20px; background-color: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .btn-delete { background-color: #dc3545; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 4px; }
        .btn-delete:hover { background-color: #c82333; }
        .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #007bff; font-weight: bold; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn-back">&larr; Back to Dashboard</a>
        
        <h2>Add New Course</h2>
        <?= $message ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Course Title:</label>
                <input type="text" name="title" placeholder="e.g. Introduction to Web Design" required>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="3" placeholder="Briefly describe the course content..." required></textarea>
            </div>
            <button type="submit" name="add_course" class="btn-add">Add Course</button>
        </form>

        <hr style="margin: 40px 0; border: 0; border-top: 1px solid #eee;">

        <h2>My Courses</h2>
        <?php if ($courses->num_rows > 0): ?>
            <table>
                <tr>
                    <th style="width: 50px; text-align: center;">STT</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th style="width: 100px; text-align: center;">Action</th>
                </tr>
                <?php 
                $stt = 1; 
                while ($row = $courses->fetch_assoc()): 
                ?>
                    <tr>
                        <td style="text-align: center;"><strong><?= $stt++ ?></strong></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td style="text-align: center;">
                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this course? This action cannot be undone.');" style="display:inline;">
                                <input type="hidden" name="course_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_course" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>You have not created any courses yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
