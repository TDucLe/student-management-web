<?php
require_once 'config.php';
requireLogin();
$stmt_t = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?"); $stmt_t->execute([$_SESSION['user_id']]); $teacher_id = $stmt_t->fetchColumn() ?: 1;

// FUNCTION CỦA ĐẠT ĐEN
function getLeaveRequestsByTeacher($pdo, $teacher_id) {
    try {
        $stmt = $pdo->prepare("SELECT lr.*, s.full_name AS student_name, c.course_name, cl.class_name FROM leave_requests lr JOIN students s ON lr.student_id = s.id JOIN classes cl ON lr.class_id = cl.id JOIN courses c ON cl.course_id = c.id WHERE cl.teacher_id = :teacher_id ORDER BY lr.leave_date DESC");
        $stmt->execute([':teacher_id' => $teacher_id]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return false; }
}

if (isset($_POST['action'])) {
    $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?")->execute([$_POST['status'], $_POST['req_id']]);
}
$requests = getLeaveRequestsByTeacher($pdo, $teacher_id) ?: [];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Leave Approval</title>
<style>body{font-family:sans-serif;background:#f5f5f5;padding:20px;margin:0} .container{max-width:1000px;margin:auto;background:white;padding:30px;border-radius:10px} table{width:100%;border-collapse:collapse;margin-top:20px} th,td{border-bottom:1px solid #eee;padding:12px;text-align:left} .btn-app{background:#10b981;color:white;border:none;padding:5px 10px;cursor:pointer;border-radius:3px} .btn-rej{background:#ef4444;color:white;border:none;padding:5px 10px;cursor:pointer;border-radius:3px} .badge{padding:4px 10px;border-radius:12px;font-size:12px;font-weight:bold;color:white;text-transform:uppercase} .st-pending{background:#f59e0b} .st-approved{background:#10b981} .st-rejected{background:#ef4444} .btn-back{display:inline-block;margin-bottom:20px;text-decoration:none;color:#667eea;font-weight:bold}</style>
</head><body><div class="container"><a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
<h2>✉️ Leave Requests Approval</h2>
<?php if(count($requests)>0): ?><table><tr><th>Student</th><th>Class / Course</th><th>Reason</th><th>Date</th><th>Status</th><th>Action</th></tr>
<?php foreach ($requests as $r): ?><tr><td><b><?= htmlspecialchars($r['student_name']) ?></b></td><td><?= htmlspecialchars($r['class_name']) ?><br><small style="color:#777;"><?= htmlspecialchars($r['course_name']) ?></small></td><td><i>"<?= htmlspecialchars($r['reason']) ?>"</i></td><td><?= htmlspecialchars(date('d/m/Y', strtotime($r['leave_date']))) ?></td><td><span class="badge st-<?= $r['status'] ?>"><?= $r['status'] ?></span></td><td><?php if($r['status']==='pending'): ?><form method="POST" style="display:inline;"><input type="hidden" name="req_id" value="<?= $r['id'] ?>"><button type="submit" name="action" value="approved" class="btn-app">Approve</button></form><form method="POST" style="display:inline;"><input type="hidden" name="req_id" value="<?= $r['id'] ?>"><button type="submit" name="action" value="rejected" class="btn-rej">Reject</button></form><?php else: ?><span style="color:#999;font-size:13px;">Processed</span><?php endif; ?></td></tr><?php endforeach; ?></table><?php else: ?><p>No pending requests.</p><?php endif; ?>
</div></body></html>
