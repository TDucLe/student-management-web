<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = t('nav.assignments');

if (isset($_POST['delete_id'])) {
    $pdo->prepare('DELETE FROM assignments WHERE id = ?')->execute([(int) $_POST['delete_id']]);
    flash('success', 'Assignment deleted.');
    header('Location: assignments_manage.php?' . http_build_query(array_filter([
        'semester_id' => $_POST['semester_id'] ?? '',
        'class_id' => $_POST['class_id'] ?? '',
    ])));
    exit;
}

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

// Build assignments query with filters
$rows = [];
$hasFilter = ($semester_id || $class_id);

if ($hasFilter) {
    $sql = "
        SELECT a.id, a.title, a.deadline, a.max_score, c.class_name, t.full_name AS teacher_name,
               (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS sub_count
        FROM assignments a
        LEFT JOIN classes c ON a.class_id = c.id
        LEFT JOIN teachers t ON a.teacher_id = t.id
        WHERE 1=1
    ";
    $params = [];

    if ($semester_id) {
        $sql .= ' AND c.semester_id = ?';
        $params[] = $semester_id;
    }
    if ($class_id) {
        $sql .= ' AND a.class_id = ?';
        $params[] = $class_id;
    }

    $sql .= ' ORDER BY a.deadline DESC';
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
    <div class="empty-icon">📝</div>
    <p><?= htmlspecialchars(t('filter_hint')) ?></p>
</div>
<?php elseif (empty($rows)): ?>
<div class="card empty-state">
    <div class="empty-icon">📭</div>
    <p><?= htmlspecialchars(t('no_data')) ?></p>
</div>
<?php else: ?>
<div class="card">
    <div class="result-count">
        <span class="result-badge"><?= htmlspecialchars(t('showing_results', ['count' => count($rows)])) ?></span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr>
                <th><?= lang() === 'vi' ? 'Tiêu đề' : 'Title' ?></th>
                <th><?= htmlspecialchars(t('nav.classes')) ?></th>
                <th><?= lang() === 'vi' ? 'Giáo viên' : 'Teacher' ?></th>
                <th>Deadline</th>
                <th><?= lang() === 'vi' ? 'Bài nộp' : 'Submissions' ?></th>
                <th></th>
            </tr></thead>
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
                        <input type="hidden" name="semester_id" value="<?= $semester_id ?? '' ?>">
                        <input type="hidden" name="class_id" value="<?= $class_id ?? '' ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
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
