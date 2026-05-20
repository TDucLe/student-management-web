<?php
session_start();
include 'config.php';
include 'auth.php';
checkLogin();

$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .back-btn { background-color: #f0f0f0; padding: 10px; text-decoration: none; color: black; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <a href="index.php" class="back-btn">Back</a>
    <h2>Welcome, <?php echo $user_name; ?> (<?php echo $user_role; ?>)</h2>
    <a href="logout.php">Logout</a><br><br>

    <?php if ($user_role == 'admin'): ?>
        <h3>Admin Panel</h3>
        <p>Manage users, courses, etc.</p>
        <!-- Add admin-specific links or forms here -->
    <?php elseif ($user_role == 'teacher'): ?>
        <h3>Teacher Panel</h3>
        <p>View classes, grades, attendance.</p>
        <!-- Add teacher-specific links or forms here -->
    <?php elseif ($user_role == 'student'): ?>
        <h3>Student Panel</h3>
        <p>View enrolled courses, grades, attendance.</p>
        <!-- Add student-specific links or forms here -->
    <?php endif; ?>
</body>
</html>

<?php $conn->close(); ?>