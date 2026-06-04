<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'Assignments Management';

if (isset($_POST['delete_id'])) {
    $pdo->prepare('DELETE FROM assignments WHERE id = ?')->execute([(int) $_POST['delete_id']]);
    flash('success', 'Assignment deleted.');
    header('Location: assignments_manage.php');
    exit;
}

$rows = $pdo->query("
    SELECT a.id, a.title, a.deadline, a.max_score, c.class_name, t.full_name AS teacher_name,
           (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS sub_count
    FROM assignments a
    LEFT JOIN classes c ON a.class_id = c.id
    LEFT JOIN teachers t ON a.teacher_id = t.id
    ORDER BY a.deadline DESC
")->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Title</th><th>Class</th><th>Teacher</th><th>Deadline</th><th>Submissions</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['class_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['teacher_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['deadline']) ?></td>
                <td><?= (int) $r['sub_count'] ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete assignment?');">
                        <input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
