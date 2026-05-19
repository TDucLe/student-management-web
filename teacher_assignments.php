<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if ($user['role'] !== 'teacher') die("Access denied.");

$teacher_id = $user['id'];
$message = "";

// 1. HANDLE CREATE ASSIGNMENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_assignment'])) {
    $course_id = $_POST['course_id'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $deadline = $_POST['deadline'];

    try {
        $stmt = $pdo->prepare("INSERT INTO assignments (course_id, teacher_id, title, description, deadline) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$course_id, $teacher_id, $title, $desc, $deadline]);
        $message = "<div class='success'>✅ Assignment published successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// 2. HANDLE GRADE SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grade_submission'])) {
    $sub_id = $_POST['submission_id'];
    $score = filter_var($_POST['score'], FILTER_VALIDATE_FLOAT);

    if ($score !== false && $score >= 0 && $score <= 10) {
        $stmt = $pdo->prepare("UPDATE submissions SET grade = ? WHERE id = ?");
        $stmt->execute([$score, $sub_id]);
        $message = "<div class='success'>✅ Submission graded successfully!</div>";
    } else {
        $message = "<div class='error'>❌ Invalid score. Must be between 0 and 10.</div>";
    }
}

// FETCH DATA FOR FORM
$stmt_courses = $pdo->prepare("SELECT id, name FROM courses WHERE teacher_id = ?");
$stmt_courses->execute([$teacher_id]);
$my_courses = $stmt_courses->fetchAll();

// FETCH SUBMISSIONS TO GRADE
$stmt_subs = $pdo->prepare("SELECT sub.id, a.title AS asm_title, s.name AS student_name, sub.file_url, sub.submitted_at, sub.grade 
                            FROM submissions sub 
                            JOIN assignments a ON sub.assignment_id = a.id 
                            JOIN students s ON sub.student_id = s.id 
                            WHERE a.teacher_id = ? ORDER BY sub.submitted_at DESC");
$stmt_subs->execute([$teacher_id]);
$submissions = $stmt_subs->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Manage Assignments</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; margin: 0; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-top: 0; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; } th, td { border-bottom: 1px solid #eee; padding: 12px; text-align: left; } th { background: #f9f9f9; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; box-sizing: border-box; }
        .btn-add { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #667eea; font-weight: bold; }
        .success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .score-input { width: 70px; padding: 5px; margin: 0; display: inline-block; }
        .btn-score { padding: 5px 10px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
        <h2>📋 Publish New Assignment</h2>
        <?= $message ?>
        
        <form method="POST">
            <label>Select Course:</label>
            <select name="course_id" required>
                <option value="">-- Choose Course --</option>
                <?php foreach ($my_courses as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Assignment Title:</label>
            <input type="text" name="title" required placeholder="e.g. Midterm Project Paper">
            <label>Instructions / Description:</label>
            <textarea name="description" rows="3" placeholder="Enter instructions for students..."></textarea>
            <label>Deadline:</label>
            <input type="datetime-local" name="deadline" required value="<?= date('Y-m-d\HT:i', strtotime('+7 days')) ?>">
            <button type="submit" name="add_assignment" class="btn-add">Publish Assignment</button>
        </form>

        <h2 style="margin-top: 40px;">📥 Student Submissions & Grading</h2>
        <?php if (count($submissions) > 0): ?>
            <table>
                <tr><th>Assignment</th><th>Student</th><th>Submitted At</th><th>File</th><th>Grade (Điểm)</th></tr>
                <?php foreach ($submissions as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['asm_title']) ?></td>
                        <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['submitted_at']))) ?></td>
                        <td><a href="<?= htmlspecialchars($row['file_url']) ?>" target="_blank" style="color: #667eea; font-weight: 600;">📄 View File</a></td>
                        <td>
                            <form method="POST" style="display: flex; gap: 5px; align-items: center;">
                                <input type="hidden" name="submission_id" value="<?= $row['id'] ?>">
                                <input type="text" name="score" class="score-input" placeholder="0-10" value="<?= $row['grade'] !== null ? htmlspecialchars($row['grade']) : '' ?>" required>
                                <button type="submit" name="grade_submission" class="btn-score">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p style="color: #666; text-align: center; padding: 20px;">No student submissions received yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>