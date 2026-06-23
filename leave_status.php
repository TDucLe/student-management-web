<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('student');

$user = getCurrentUser();
$student_id = getStudentId($pdo, $user['id'], $user['username']);
$pageTitle = 'Leave Status';

$stmt = $pdo->prepare("
    SELECT lr.*, cl.class_name, c.course_name
    FROM leave_requests lr
    JOIN classes cl ON lr.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    WHERE lr.student_id = ?
    ORDER BY lr.created_at DESC
");
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card" style="margin-bottom:16px">
    <a href="leave_request.php" class="btn btn-primary">New request</a>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Class</th><th>Date</th><th>Reason</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="4">No leave requests yet.</td></tr>
            <?php else: foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['class_name']) ?> (<?= htmlspecialchars($r['course_name']) ?>)</td>
                <td><?= htmlspecialchars($r['leave_date']) ?></td>
                <td><?= htmlspecialchars($r['reason']) ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
