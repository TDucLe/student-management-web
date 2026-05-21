<?php
require_once 'config.php';
requireLogin();
$stmt_t = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?"); $stmt_t->execute([$_SESSION['user_id']]); $teacher_id = $stmt_t->fetchColumn() ?: 1;

$message = "";
if (isset($_POST['add_exam'])) {
    try { $pdo->prepare("INSERT INTO exams (class_id, exam_name, exam_date, max_score) VALUES (?, ?, ?, ?)")->execute([$_POST['class_id'], $_POST['name'], $_POST['date'], $_POST['max']]); $message = "<div class='success'>✅ Exam Created!</div>"; } catch (Exception $e) {}
}
if (isset($_POST['add_res'])) {
    try { $pdo->prepare("INSERT INTO exam_results (exam_id, student_id, score) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE score=?")->execute([$_POST['exam_id'], $_POST['stu_id'], $_POST['score'], $_POST['score']]); $message = "<div class='success'>✅ Score Saved!</div>"; } catch (Exception $e) {}
}

$classes = $pdo->prepare("SELECT id, class_name FROM classes WHERE teacher_id = ?"); $classes->execute([$teacher_id]); $classes = $classes->fetchAll();
$exams = $pdo->prepare("SELECT e.id, e.exam_name, c.class_name FROM exams e JOIN classes c ON e.class_id = c.id WHERE c.teacher_id = ?"); $exams->execute([$teacher_id]); $exams = $exams->fetchAll();
$students = $pdo->query("SELECT id, full_name FROM students")->fetchAll();
$results = $pdo->prepare("SELECT er.id, e.exam_name, s.full_name, er.score, e.max_score FROM exam_results er JOIN exams e ON er.exam_id = e.id JOIN students s ON er.student_id = s.id JOIN classes c ON e.class_id = c.id WHERE c.teacher_id = ? ORDER BY e.exam_date DESC"); $results->execute([$teacher_id]); $results = $results->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Exams & Grades</title>
<style>body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;padding:20px;margin:0} .container{max-width:900px;margin:auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1)} h2{color:#333;border-bottom:2px solid #667eea;padding-bottom:10px} table{width:100%;border-collapse:collapse;margin-top:20px} th,td{border-bottom:1px solid #eee;padding:12px;text-align:left} th{background:#f9f9f9} input,select{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;box-sizing:border-box} .btn-add{background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;border:none;padding:12px 20px;border-radius:5px;cursor:pointer;font-weight:bold;width:100%} .btn-back{display:inline-block;margin-bottom:20px;text-decoration:none;color:#10b981;font-weight:bold} .success{background:#dcfce7;color:#166534;padding:12px;border-radius:5px;margin-bottom:15px}</style>
</head><body><div class="container"><a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
<h2>📝 1. Create Exam Event</h2><?= $message ?>
<form method="POST" style="display:grid; grid-template-columns:1fr 1fr; gap:15px; background:#f8fafc; padding:20px; border-radius:8px; margin-bottom:40px;">
    <div><label>Class:</label><select name="class_id" required><option value="">-- Class --</option><?php foreach($classes as $c) echo "<option value='{$c['id']}'>{$c['class_name']}</option>"; ?></select></div>
    <div><label>Exam Name:</label><input type="text" name="name" required placeholder="e.g. Midterm"></div>
    <div><label>Date:</label><input type="date" name="date" required value="<?= date('Y-m-d') ?>"></div>
    <div><label>Max Score:</label><input type="number" step="0.1" name="max" required value="10"></div>
    <div style="grid-column:span 2;"><button type="submit" name="add_exam" class="btn-add" style="background:#3b82f6;">Create Exam</button></div>
</form>
<h2>📊 2. Input Student Scores</h2>
<form method="POST" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px;">
    <div><label>Exam:</label><select name="exam_id" required><option value="">-- Exam --</option><?php foreach($exams as $e) echo "<option value='{$e['id']}'>{$e['exam_name']} ({$e['class_name']})</option>"; ?></select></div>
    <div><label>Student:</label><select name="stu_id" required><option value="">-- Student --</option><?php foreach($students as $s) echo "<option value='{$s['id']}'>{$s['full_name']}</option>"; ?></select></div>
    <div><label>Score:</label><div style="display:flex;gap:10px;"><input type="number" step="0.01" name="score" required><button type="submit" name="add_res" class="btn-add" style="margin:0;">Save</button></div></div>
</form>
<h2 style="margin-top: 40px;">📋 Score Records</h2>
<?php if(count($results)>0): ?><table><tr><th>Exam</th><th>Student</th><th>Score</th></tr>
<?php foreach($results as $r): ?><tr><td><?= htmlspecialchars($r['exam_name']) ?></td><td><?= htmlspecialchars($r['full_name']) ?></td><td><strong style="color:#10b981;font-size:18px;"><?= $r['score'] ?> / <?= $r['max_score'] ?></strong></td></tr><?php endforeach; ?></table><?php endif; ?>
</div></body></html>
