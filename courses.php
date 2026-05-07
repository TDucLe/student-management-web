<?php
session_start();
include '../config.php';
include '../auth.php';

checkLogin();

if($_SESSION['user_role'] !== 'teacher'){
    die("Access denied");
}

$teacher_id = $_SESSION['user_id'];

// CREATE
if(isset($_POST['add'])){

    $stmt = $conn->prepare("
        INSERT INTO courses(title, description, instructor_id)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param(
        "ssi",
        $_POST['title'],
        $_POST['description'],
        $teacher_id
    );

    $stmt->execute();

    header("Location: courses.php");
    exit();
}

// DELETE
if(isset($_GET['delete'])){

    $stmt = $conn->prepare("
        DELETE FROM courses
        WHERE id=? AND instructor_id=?
    ");

    $stmt->bind_param(
        "ii",
        $_GET['delete'],
        $teacher_id
    );

    $stmt->execute();

    header("Location: courses.php");
    exit();
}

// GET EDIT
$edit = null;

if(isset($_GET['edit'])){

    $stmt = $conn->prepare("
        SELECT * FROM courses
        WHERE id=? AND instructor_id=?
    ");

    $stmt->bind_param(
        "ii",
        $_GET['edit'],
        $teacher_id
    );

    $stmt->execute();

    $edit = $stmt->get_result()->fetch_assoc();
}

// UPDATE
if(isset($_POST['update'])){

    $stmt = $conn->prepare("
        UPDATE courses
        SET title=?, description=?
        WHERE id=? AND instructor_id=?
    ");

    $stmt->bind_param(
        "ssii",
        $_POST['title'],
        $_POST['description'],
        $_POST['id'],
        $teacher_id
    );

    $stmt->execute();

    header("Location: courses.php");
    exit();
}

// READ
$stmt = $conn->prepare("
    SELECT * FROM courses
    WHERE instructor_id=?
");

$stmt->bind_param("i", $teacher_id);

$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Courses</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container mt-4">

<h2>Course Management</h2>

<form method="POST" class="mb-3">

    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">

    <input
        type="text"
        name="title"
        class="form-control mb-2"
        placeholder="Course title"
        value="<?= $edit['title'] ?? '' ?>"
        required
    >

    <textarea
        name="description"
        class="form-control mb-2"
        placeholder="Description"
    ><?= $edit['description'] ?? '' ?></textarea>

    <?php if($edit): ?>

        <button class="btn btn-warning" name="update">
            Update
        </button>

        <a href="courses.php" class="btn btn-secondary">
            Cancel
        </a>

    <?php else: ?>

        <button class="btn btn-primary" name="add">
            Add Course
        </button>

    <?php endif; ?>

</form>

<table class="table table-bordered">

<tr>
    <th>ID</th>
    <th>Title</th>
    <th>Description</th>
    <th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>

<tr>

    <td><?= $row['id'] ?></td>

    <td><?= htmlspecialchars($row['title']) ?></td>

    <td><?= htmlspecialchars($row['description']) ?></td>

    <td>

        <a
            href="?edit=<?= $row['id'] ?>"
            class="btn btn-warning btn-sm"
        >
            Edit
        </a>

        <a
            href="?delete=<?= $row['id'] ?>"
            class="btn btn-danger btn-sm"
            onclick="return confirm('Delete this course?')"
        >
            Delete
        </a>

    </td>

</tr>

<?php endwhile; ?>

</table>

</body>
</html>