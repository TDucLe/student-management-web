<?php
require_once 'config.php';
requireLogin();
$user = getCurrentUser();
if ($user['role'] !== 'teacher') die("Access denied.");

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    try {
        $pdo->prepare("INSERT INTO courses (course_code, course_name, credits, department) VALUES (?, ?, ?, ?)")
            ->execute([strtoupper(trim($_POST['course_code'])), trim($_POST['course_name']), $_POST['credits'], trim($_POST['department'])]);
        $message = "<div class='success'>✅ Course added to Global Catalog!</div>";
    } catch (PDOException $e) { $message = "<div class='error'>❌ Error: Course code might already exist.</div>"; }
}

if (isset($_POST['delete_course'])) {
    try { $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$_POST['course_id']]); } catch(PDOException $e) { $message = "<div class='error'>❌ Cannot delete. Linked to active classes.</div>"; }
}

$courses = $pdo->query("SELECT * FROM courses ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Courses Catalog</title>
<style>body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;padding:20px;margin:0} .container{max-width:900px;margin:auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1)} h2{color:#333;border-bottom:2px solid #667eea;padding-bottom:10px} table{width:100%;border-collapse:collapse;margin-top:20px} th,td{border-bottom:1px solid #eee;padding:12px;text-align:left} th{background:#f9f9f9} input{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;box-sizing:border-box} .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px} .btn-add{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;padding:12px;border-radius:5px;cursor:pointer;font-weight:bold;width:100%} .btn-del{background:#fee2e2;color:#ef4444;border:none;padding:6px 12px;border-radius:4px;cursor:pointer} .btn-back{display:inline-block;margin-bottom:20px;text-decoration:none;color:#667eea;font-weight:bold} .success{background:#dcfce7;color:#166534;padding:12px;border-radius:5px;margin-bottom:15px} .error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:5px;margin-bottom:15px}</style>
</head><body><div class="container"><a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
<h2>📚 Global Course Catalog</h2><?= $message ?>
<form method="POST"><div class="form-grid">
    <div><label>Course Code:</label><input type="text" name="course_code" required placeholder="e.g. IT101"></div>
    <div><label>Course Name:</label><input type="text" name="course_name" required placeholder="e.g. Database"></div>
    <div><label>Credits:</label><input type="number" name="credits" required min="1" max="10"></div>
    <div><label>Department:</label><input type="text" name="department" required placeholder="e.g. IT"></div>
</div><button type="submit" name="add_course" class="btn-add">Add to Catalog</button></form>
<h2 style="margin-top: 40px;">📋 Available Courses</h2>
<?php if(count($courses)>0): ?><table><tr><th>Code</th><th>Name</th><th>Credits</th><th>Department</th><th>Action</th></tr>
<?php foreach($courses as $r): ?><tr><td><b><?= htmlspecialchars($r['course_code']) ?></b></td><td><?= htmlspecialchars($r['course_name']) ?></td><td><?= $r['credits'] ?></td><td><?= htmlspecialchars($r['department']) ?></td><td><form method="POST" onsubmit="return confirm('Delete?');"><input type="hidden" name="course_id" value="<?= $r['id'] ?>"><button type="submit" name="delete_course" class="btn-del">Delete</button></form></td></tr><?php endforeach; ?></table><?php endif; ?>
</div></body></html>
