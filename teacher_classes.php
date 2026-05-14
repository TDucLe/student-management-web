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
        .container { max-width: 800px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #17a2b8; color: white; }
        .form-group { margin-bottom: 15px; }
        input[type="text"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #28a745; color: white; border: none; cursor: pointer; }
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
                <label>Class Name (e.g., PHP101 - Spring 2026):</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Schedule (e.g., Mon/Wed 08:00 AM - 10:00 AM):</label>
                <input type="text" name="schedule" required>
            </div>
            <button type="submit" name="add_class">Create Class</button>
        </form>

        <hr style="margin: 30px 0;">

        <h2>My Classes</h2>
        <?php if ($classes->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Class ID</th>
                    <th>Class Name</th>
                    <th>Schedule</th>
                </tr>
                <?php while ($row = $classes->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['schedule']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>You have not created any classes yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>