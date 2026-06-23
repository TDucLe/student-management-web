<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('student');

$user = getCurrentUser();
$student_id = getStudentId($pdo, $user['id'], $user['username']);
$pageTitle = 'My Courses';

$stmt = $pdo->prepare("
    SELECT DISTINCT c.course_code, c.course_name, c.credits, c.department, cl.class_name
    FROM enrollments e
    JOIN classes cl ON e.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    WHERE e.student_id = ? AND e.status = 'active'
");
$stmt->execute([$student_id]);
$courses = $stmt->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Code</th><th>Course</th><th>Class</th><th>Credits</th><th>Department</th></tr></thead>
            <tbody>
            <?php if (empty($courses)): ?>
            <tr><td colspan="5">No enrolled courses yet.</td></tr>
            <?php else: foreach ($courses as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['course_code']) ?></td>
                <td><?= htmlspecialchars($c['course_name']) ?></td>
                <td><?= htmlspecialchars($c['class_name']) ?></td>
                <td><?= (int) $c['credits'] ?></td>
                <td><?= htmlspecialchars($c['department']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
