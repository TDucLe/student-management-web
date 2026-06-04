<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'Leave Requests';

if (isset($_POST['req_id'], $_POST['status'])) {
    $status = $_POST['status'];
    if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $pdo->prepare('UPDATE leave_requests SET status = ? WHERE id = ?')->execute([$status, (int) $_POST['req_id']]);
        flash('success', 'Status updated.');
    }
    header('Location: leave_manage.php');
    exit;
}

$rows = $pdo->query("
    SELECT lr.*, s.full_name, c.class_name, co.course_name
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    JOIN classes c ON lr.class_id = c.id
    JOIN courses co ON c.course_id = co.id
    ORDER BY lr.created_at DESC
")->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Student</th><th>Class</th><th>Date</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['class_name']) ?> (<?= htmlspecialchars($r['course_name']) ?>)</td>
                <td><?= htmlspecialchars($r['leave_date']) ?></td>
                <td><?= htmlspecialchars($r['reason']) ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                <td>
                    <form method="POST" style="display:inline-flex;gap:6px;">
                        <input type="hidden" name="req_id" value="<?= (int) $r['id'] ?>">
                        <button name="status" value="approved" class="btn btn-primary btn-sm">Approve</button>
                        <button name="status" value="rejected" class="btn btn-danger btn-sm">Reject</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
