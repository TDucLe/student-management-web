<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = t('nav.grades');

// Fetch all semesters for filter
$semesters = $pdo->query('SELECT id, name FROM semesters ORDER BY start_date DESC')->fetchAll();

// Selected filters
$semester_id = isset($_GET['semester_id']) && $_GET['semester_id'] !== '' ? (int) $_GET['semester_id'] : null;
$class_id = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int) $_GET['class_id'] : null;

// Fetch classes for the selected semester (or all classes if no semester selected)
$classQuery = 'SELECT c.id, c.class_name FROM classes c';
$classParams = [];
if ($semester_id) {
    $classQuery .= ' WHERE c.semester_id = ?';
    $classParams[] = $semester_id;
}
$classQuery .= ' ORDER BY c.class_name';
$classStmt = $pdo->prepare($classQuery);
$classStmt->execute($classParams);
$classes = $classStmt->fetchAll();

// Build grades query with filters
$rows = [];
$hasFilter = ($semester_id || $class_id);

if ($hasFilter) {
    $sql = "
        SELECT g.*, s.full_name, s.student_code, c.course_name, cl.class_name, sem.name AS semester_name
        FROM student_grades g
        JOIN enrollments e ON g.enrollment_id = e.id
        JOIN students s ON e.student_id = s.id
        JOIN classes cl ON e.class_id = cl.id
        JOIN courses c ON cl.course_id = c.id
        LEFT JOIN semesters sem ON cl.semester_id = sem.id
        WHERE 1=1
    ";
    $params = [];

    if ($semester_id) {
        $sql .= ' AND cl.semester_id = ?';
        $params[] = $semester_id;
    }
    if ($class_id) {
        $sql .= ' AND cl.id = ?';
        $params[] = $class_id;
    }

    $sql .= ' ORDER BY cl.class_name, s.full_name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

renderHeader($pageTitle, $user);
?>
<div class="card filter-card">
    <div class="filter-header">
        <span class="filter-icon">🔍</span>
        <h3><?= htmlspecialchars(t('filter_hint')) ?></h3>
    </div>
    <form method="GET" class="filter-form" id="filterForm">
        <div class="filter-row">
            <div class="filter-group">
                <label for="semester_id"><?= htmlspecialchars(t('select_semester')) ?></label>
                <select name="semester_id" id="semester_id" onchange="onSemesterChange(this)">
                    <option value=""><?= htmlspecialchars(t('all_semesters')) ?></option>
                    <?php foreach ($semesters as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= $semester_id === (int) $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="class_id"><?= htmlspecialchars(t('select_class')) ?></label>
                <select name="class_id" id="class_id" onchange="this.form.submit()">
                    <option value=""><?= htmlspecialchars(t('all_classes')) ?></option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $class_id === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
</div>

<?php if (!$hasFilter): ?>
<div class="card empty-state">
    <div class="empty-icon">📊</div>
    <p><?= htmlspecialchars(t('filter_hint')) ?></p>
</div>
<?php elseif (empty($rows)): ?>
<div class="card empty-state">
    <div class="empty-icon">📭</div>
    <p><?= htmlspecialchars(t('no_data')) ?></p>
</div>
<?php else: ?>
<p class="alert alert-info"><?= htmlspecialchars(t('fail_note')) ?></p>
<div class="card">
    <div class="result-count">
        <span class="result-badge"><?= htmlspecialchars(t('showing_results', ['count' => count($rows)])) ?></span>
    </div>
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
<?php endif; ?>

<script>
function onSemesterChange(sel) {
    document.getElementById('class_id').value = '';
    sel.form.submit();
}
</script>
<?php renderFooter(); ?>
