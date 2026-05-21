<?php
require_once 'config.php';
requireLogin();
$stmt_t = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?"); $stmt_t->execute([$_SESSION['user_id']]); $teacher_id = $stmt_t->fetchColumn() ?: 1;

// AUTO-SYNC: Tự đưa user học sinh vào bảng students và Enroll vào các lớp của giáo viên này
$users = $pdo->query("SELECT id, username FROM users WHERE role='student'")->fetchAll();
$cls_ids = $pdo->prepare("SELECT id FROM classes WHERE teacher_id = ?"); $cls_ids->execute([$teacher_id]); $my_cls = $cls_ids->fetchAll(PDO::FETCH_COLUMN);
foreach ($users as $u) {
    $chk = $pdo->prepare("SELECT id FROM students WHERE user_id = ?"); $chk->execute([$u['id']]); $stu = $chk->fetch();
    if (!$stu) { $pdo->prepare("INSERT INTO students (user_id, student_code, full_name) VALUES (?, ?, ?)")->execute([$u['id'], 'STU'.$u['id'], $u['username']]); $s_id = $pdo->lastInsertId(); } else { $s_id = $stu['id']; }
    foreach($my_cls as $cid) { try { $pdo->prepare("INSERT IGNORE INTO enrollments (student_id, class_id) VALUES (?, ?)")->execute([$s_id, $cid]); } catch(Exception $e){} }
}

$message = "";
if (isset($_POST['mark_att'])) {
    try {
        $pdo->prepare("INSERT INTO attendance (enrollment_id, schedule_id, attendance_date, status, note) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status), note=VALUES(note)")
            ->execute([$_POST['enroll_id'], $_POST['sched_id'], $_POST['date'], $_POST['status'], $_POST['note']]);
        $message = "<div class='success'>✅ Attendance saved!</div>";
    } catch (PDOException $e) { $message = "<div class='error'>❌ Error. Did you select a valid Enrollment & Schedule?</div>"; }
}
if (isset($_POST['del_att'])) { $pdo->prepare("DELETE FROM attendance WHERE id = ?")->execute([$_POST['att_id']]); }

$enrolls = $pdo->prepare("SELECT e.id, s.full_name, c.class_name FROM enrollments e JOIN students s ON e.student_id = s.id JOIN classes c ON e.class_id = c.id WHERE c.teacher_id = ?"); $enrolls->execute([$teacher_id]); $enrolls = $enrolls->fetchAll();
$schedules = $pdo->prepare("SELECT s.id, c.class_name, s.day_of_week FROM schedules s JOIN classes c ON s.class_id = c.id WHERE c.teacher_id = ?"); $schedules->execute([$teacher_id]); $schedules = $schedules->fetchAll();
$history = $pdo->prepare("SELECT a.id, a.attendance_date, a.status, a.note, s.full_name, c.class_name FROM attendance a JOIN enrollments e ON a.enrollment_id = e.id JOIN students s ON e.student_id = s.id JOIN classes c ON e.class_id = c.id WHERE c.teacher_id = ? ORDER BY a.attendance_date DESC"); $history->execute([$teacher_id]); $history = $history->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Attendance</title>
<style>body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;padding:20px;margin:0} .container{max-width:1000px;margin:auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1)} h2{color:#333;border-bottom:2px solid #667eea;padding-bottom:10px} table{width:100%;border-collapse:collapse;margin-top:20px} th,td{border-bottom:1px solid #eee;padding:12px;text-align:left} th{background:#f9f9f9} input,select{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;box-sizing:border-box} .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px} .btn-add{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;padding:12px;border-radius:5px;cursor:pointer;font-weight:bold;width:100%} .btn-del{background:#fee2e2;color:#ef4444;border:none;padding:6px 12px;border-radius:4px;cursor:pointer} .btn-back{display:inline-block;margin-bottom:20px;text-decoration:none;color:#667eea;font-weight:bold} .success{background:#dcfce7;color:#166534;padding:12px;border-radius:5px;margin-bottom:15px} .error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:5px;margin-bottom:15px} .badge{padding:4px 10px;border-radius:12px;font-size:12px;font-weight:bold;color:white;text-transform:uppercase} .bg-present{background:#10b981} .bg-absent{background:#ef4444} .bg-late{background:#f59e0b}</style>
</head><body><div class="container"><a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
<h2>✅ Mark Attendance</h2><?= $message ?>
<form method="POST"><div class="form-grid">
    <div><label>Enrolled Student:</label><select name="enroll_id" required><option value="">-- Select --</option><?php foreach($enrolls as $e) echo "<option value='{$e['id']}'>{$e['full_name']} ({$e['class_name']})</option>"; ?></select></div>
    <div><label>Schedule Slot:</label><select name="sched_id" required><option value="">-- Select --</option><?php foreach($schedules as $s) echo "<option value='{$s['id']}'>{$s['class_name']} - {$s['day_of_week']}</option>"; ?></select></div>
    <div><label>Date & Status:</label><div style="display:flex;gap:10px;"><input type="date" name="date" required value="<?= date('Y-m-d') ?>"><select name="status" required><option value="present">Present</option><option value="late">Late</option><option value="absent">Absent</option></select></div></div>
    <div><label>Note:</label><input type="text" name="note" placeholder="Optional notes..."></div>
</div><button type="submit" name="mark_att" class="btn-add">Save Attendance</button></form>
<h2 style="margin-top: 40px;">📋 History</h2>
<?php if(count($history)>0): ?><table><tr><th>Class</th><th>Student</th><th>Date</th><th>Status</th><th>Note</th><th>Action</th></tr>
<?php foreach ($history as $r): ?><tr><td><b><?= htmlspecialchars($r['class_name']) ?></b></td><td><?= htmlspecialchars($r['full_name']) ?></td><td><?= date('d/m/Y', strtotime($r['attendance_date'])) ?></td><td><span class="badge bg-<?= $r['status'] ?>"><?= $r['status'] ?></span></td><td><?= htmlspecialchars($r['note']) ?></td><td><form method="POST"><input type="hidden" name="att_id" value="<?= $r['id'] ?>"><button type="submit" name="del_att" class="btn-del">Del</button></form></td></tr><?php endforeach; ?></table><?php endif; ?>
</div></body></html>
