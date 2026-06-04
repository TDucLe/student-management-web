<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'Attendance Management';

if (isset($_POST['delete_id'])) {
    $pdo->prepare('DELETE FROM attendance WHERE id = ?')->execute([(int) $_POST['delete_id']]);
    flash('success', 'Record deleted.');
    header('Location: attendance_manage.php');
    exit;
}

$rows = $pdo->query("
    SELECT a.id, a.attendance_date, a.status, a.teacher_comment, s.full_name, c.class_name
    FROM attendance a
    JOIN enrollments e ON a.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN classes c ON e.class_id = c.id
    ORDER BY a.attendance_date DESC
    LIMIT 200
")->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Date</th><th>Student</th><th>Class</th><th>Status</th><th>Teacher comment</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['attendance_date']) ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['class_name']) ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                <td><?= htmlspecialchars($r['teacher_comment'] ?? '') ?></td>
                <td>
                    <form method="POST"><input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button></form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
