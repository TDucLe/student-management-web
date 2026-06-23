<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('student');

$user = getCurrentUser();
$student_id = getStudentId($pdo, $user['id'], $user['username']);
$pageTitle = 'My Classes';

$stmt = $pdo->prepare("
    SELECT cl.class_name, c.course_name, c.course_code, e.status, e.enrolled_at, t.full_name AS teacher_name
    FROM enrollments e
    JOIN classes cl ON e.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    LEFT JOIN teachers t ON cl.teacher_id = t.id
    WHERE e.student_id = ?
    ORDER BY e.enrolled_at DESC
");
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Class</th><th>Course</th><th>Teacher</th><th>Status</th><th>Enrolled</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['class_name']) ?></td>
                <td><?= htmlspecialchars($r['course_code'] . ' — ' . $r['course_name']) ?></td>
                <td><?= htmlspecialchars($r['teacher_name'] ?? '—') ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                <td><?= htmlspecialchars($r['enrolled_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
