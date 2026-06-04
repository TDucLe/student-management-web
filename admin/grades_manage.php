<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = t('nav.grades');

$rows = $pdo->query("
    SELECT g.*, s.full_name, s.student_code, c.course_name, cl.class_name, sem.name AS semester_name
    FROM student_grades g
    JOIN enrollments e ON g.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN classes cl ON e.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    LEFT JOIN semesters sem ON cl.semester_id = sem.id
    ORDER BY sem.start_date DESC, cl.class_name, s.full_name
")->fetchAll();

renderHeader($pageTitle, $user);
?>
<p class="alert alert-info"><?= htmlspecialchars(t('fail_note')) ?></p>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars(t('student')) ?></th>
                    <th><?= lang() === 'vi' ? 'Lớp / Môn' : 'Class / Course' ?></th>
                    <th><?= lang() === 'vi' ? 'Kỳ' : 'Semester' ?></th>
                    <th>10%</th><th>30%</th><th>60%</th>
                    <th><?= htmlspecialchars(t('total')) ?></th>
                    <th><?= htmlspecialchars(t('letter')) ?></th>
                    <th><?= htmlspecialchars(t('gpa')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $fail = ($r['letter_grade'] === 'F' || ($r['gpa'] !== null && (float) $r['gpa'] < 4));
            ?>
            <tr>
                <td><?= htmlspecialchars($r['full_name']) ?> (<?= htmlspecialchars($r['student_code']) ?>)</td>
                <td><?= htmlspecialchars($r['class_name'] . ' — ' . $r['course_name']) ?></td>
                <td><?= htmlspecialchars($r['semester_name'] ?? '—') ?></td>
                <td><?= $r['regular_score'] ?? '—' ?></td>
                <td><?= $r['midterm_score'] ?? '—' ?></td>
                <td><?= $r['final_score'] ?? '—' ?></td>
                <td class="<?= $fail ? 'grade-f' : 'grade-pass' ?>"><?= $r['total_score'] ?? '—' ?></td>
                <td class="<?= $fail ? 'grade-f' : 'grade-pass' ?>"><?= htmlspecialchars($r['letter_grade'] ?? '—') ?></td>
                <td class="<?= $fail ? 'grade-f' : 'grade-pass' ?>"><?= $r['gpa'] ?? '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
