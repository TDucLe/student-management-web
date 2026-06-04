<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('student');

$user = getCurrentUser();
$student_id = getStudentId($pdo, $user['id'], $user['username']);
$pageTitle = t('nav.grades');

$stmt = $pdo->prepare("
    SELECT g.*, c.course_name, c.course_code, cl.class_name, sem.name AS semester_name, sem.term_type, sem.school_year
    FROM enrollments e
    JOIN classes cl ON e.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    LEFT JOIN semesters sem ON cl.semester_id = sem.id
    LEFT JOIN student_grades g ON g.enrollment_id = e.id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY sem.start_date DESC, c.course_name
");
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll();

$chartLabels = [];
$chartGpa = [];
foreach ($rows as $r) {
    if ($r['gpa'] !== null) {
        $chartLabels[] = $r['course_code'] . ' (' . ($r['semester_name'] ?? '') . ')';
        $chartGpa[] = (float) $r['gpa'];
    }
}

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2><?= htmlspecialchars(t('gpa_chart_title')) ?></h2>
    <p class="alert alert-info" style="margin-top:0"><?= htmlspecialchars(t('fail_note')) ?></p>
    <?php if (!empty($chartGpa)): ?>
    <div class="chart-box">
        <canvas id="gpaChart" data-labels='<?= json_encode($chartLabels) ?>' data-values='<?= json_encode($chartGpa) ?>'></canvas>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('gpaChart');
        if (!el || typeof Chart === 'undefined') return;
        new Chart(el, {
            type: 'bar',
            data: {
                labels: JSON.parse(el.dataset.labels),
                datasets: [{
                    label: 'GPA',
                    data: JSON.parse(el.dataset.values),
                    backgroundColor: '#06254d99',
                    borderColor: '#06254d',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { min: 0, max: 10 } },
                plugins: { legend: { display: false } }
            }
        });
    });
    </script>
    <?php else: ?>
    <p><?= lang() === 'vi' ? 'Chưa có điểm GPA.' : 'No GPA data yet.' ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= lang() === 'vi' ? 'Môn' : 'Course' ?></th>
                    <th><?= lang() === 'vi' ? 'Lớp' : 'Class' ?></th>
                    <th><?= lang() === 'vi' ? 'Kỳ' : 'Semester' ?></th>
                    <th><?= htmlspecialchars(t('regular')) ?></th>
                    <th><?= htmlspecialchars(t('midterm')) ?></th>
                    <th><?= htmlspecialchars(t('final')) ?></th>
                    <th><?= htmlspecialchars(t('total')) ?></th>
                    <th><?= htmlspecialchars(t('letter')) ?></th>
                    <th><?= htmlspecialchars(t('gpa')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="9"><?= lang() === 'vi' ? 'Chưa có điểm.' : 'No grades.' ?></td></tr>
            <?php else: foreach ($rows as $r):
                $fail = ($r['letter_grade'] === 'F' || ($r['gpa'] !== null && (float) $r['gpa'] < 4));
            ?>
            <tr>
                <td><?= htmlspecialchars($r['course_name']) ?></td>
                <td><?= htmlspecialchars($r['class_name']) ?></td>
                <td><?= htmlspecialchars($r['semester_name'] ?? '—') ?></td>
                <td><?= $r['regular_score'] ?? '—' ?></td>
                <td><?= $r['midterm_score'] ?? '—' ?></td>
                <td><?= $r['final_score'] ?? '—' ?></td>
                <td class="<?= $fail ? 'grade-f' : 'grade-pass' ?>"><?= $r['total_score'] ?? '—' ?></td>
                <td class="<?= $fail ? 'grade-f' : 'grade-pass' ?>"><?= htmlspecialchars($r['letter_grade'] ?? '—') ?></td>
                <td class="<?= $fail ? 'grade-f' : 'grade-pass' ?>"><?= $r['gpa'] ?? '—' ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
