<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = 'Leave Approval';

if (isset($_POST['req_id'], $_POST['status'])) {
    $status = $_POST['status'];
    if (in_array($status, ['approved', 'rejected'], true)) {
        $pdo->prepare('UPDATE leave_requests SET status = ?, teacher_id = ? WHERE id = ?')
            ->execute([$status, $teacher_id, (int) $_POST['req_id']]);
        flash('success', 'Request updated.');
    }
    header('Location: leave_approval.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT lr.*, s.full_name AS student_name, c.course_name, cl.class_name
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    JOIN classes cl ON lr.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    WHERE cl.teacher_id = ?
    ORDER BY lr.leave_date DESC
");
$stmt->execute([$teacher_id]);
$requests = $stmt->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Student</th><th>Class</th><th>Date</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['student_name']) ?></td>
                <td><?= htmlspecialchars($r['class_name']) ?> <small>(<?= htmlspecialchars($r['course_name']) ?>)</small></td>
                <td><?= htmlspecialchars($r['leave_date']) ?></td>
                <td><?= htmlspecialchars($r['reason']) ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                    <form method="POST" style="display:inline-flex;gap:6px">
                        <input type="hidden" name="req_id" value="<?= (int) $r['id'] ?>">
                        <button name="status" value="approved" class="btn btn-primary btn-sm">Approve</button>
                        <button name="status" value="rejected" class="btn btn-danger btn-sm">Reject</button>
                    </form>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
