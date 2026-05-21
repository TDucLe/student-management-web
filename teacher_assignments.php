<?php
require_once 'config.php';
requireLogin();
$stmt_t = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?"); $stmt_t->execute([$_SESSION['user_id']]); $teacher_id = $stmt_t->fetchColumn() ?: 1;

$message = "";
if (isset($_POST['add_asm'])) {
    try {
        $pdo->prepare("INSERT INTO assignments (class_id, teacher_id, title, description, deadline, max_score) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$_POST['class_id'], $teacher_id, $_POST['title'], $_POST['desc'], $_POST['deadline'], $_POST['max_score']]);
        $message = "<div class='success'>✅ Assignment Published!</div>";
    } catch (Exception $e) {}
}

if (isset($_POST['grade_sub'])) {
    $pdo->prepare("UPDATE submissions SET score = ? WHERE id = ?")->execute([$_POST['score'], $_POST['sub_id']]);
    $message = "<div class='success'>✅ Graded!</div>";
}

$classes = $pdo->prepare("SELECT id, class_name FROM classes WHERE teacher_id = ?"); $classes->execute([$teacher_id]); $classes = $classes->fetchAll();
$subs = $pdo->prepare("SELECT sub.id, a.title, s.full_name, sub.file_url, sub.score, a.max_score FROM submissions sub JOIN assignments a ON sub.assignment_id = a.id JOIN students s ON sub.student_id = s.id WHERE a.teacher_id = ?"); $subs->execute([$teacher_id]); $subs = $subs->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Assignments</title>
<style>body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;padding:20px;margin:0} .container{max-width:900px;margin:auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1)} h2{color:#333;border-bottom:2px solid #667eea;padding-bottom:10px} table{width:100%;border-collapse:collapse;margin-top:20px} th,td{border-bottom:1px solid #eee;padding:12px;text-align:left} th{background:#f9f9f9} input,select,textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;box-sizing:border-box;margin-bottom:15px;} .btn-add{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;padding:12px 20px;border-radius:5px;cursor:pointer;font-weight:bold;width:100%} .btn-back{display:inline-block;margin-bottom:20px;text-decoration:none;color:#667eea;font-weight:bold} .success{background:#dcfce7;color:#166534;padding:12px;border-radius:5px;margin-bottom:15px}</style>
</head><body><div class="container"><a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
<h2>📋 Publish Assignment</h2><?= $message ?>
<form method="POST" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
    <div><label>Class:</label><select name="class_id" required><option value="">-- Class --</option><?php foreach($classes as $c) echo "<option value='{$c['id']}'>{$c['class_name']}</option>"; ?></select></div>
    <div><label>Title:</label><input type="text" name="title" required></div>
    <div style="grid-column: span 2;"><label>Description:</label><textarea name="desc" rows="3"></textarea></div>
    <div><label>Deadline:</label><input type="datetime-local" name="deadline" required value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>"></div>
    <div><label>Max Score:</label><input type="number" step="0.1" name="max_score" required value="10"></div>
    <div style="grid-column: span 2;"><button type="submit" name="add_asm" class="btn-add">Publish</button></div>
</form>
<h2 style="margin-top: 40px;">📥 Submissions & Grading</h2>
<?php if(count($subs)>0): ?><table><tr><th>Assignment</th><th>Student</th><th>File</th><th>Grade</th></tr>
<?php foreach($subs as $s): ?><tr><td><?= htmlspecialchars($s['title']) ?></td><td><b><?= htmlspecialchars($s['full_name']) ?></b></td><td><a href="<?= $s['file_url'] ?>" style="color:#667eea;">View</a></td><td><form method="POST" style="display:flex;gap:5px;"><input type="hidden" name="sub_id" value="<?= $s['id'] ?>"><input type="number" step="0.1" name="score" value="<?= $s['score'] ?>" style="width:70px;margin:0;" required><button type="submit" name="grade_sub" style="background:#10b981;color:white;border:none;border-radius:4px;cursor:pointer;">Save</button></form></td></tr><?php endforeach; ?></table><?php endif; ?>
</div></body></html>
