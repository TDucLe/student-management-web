<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = t('nav.attendance');

if (isset($_POST['delete_id'])) {
    $pdo->prepare('DELETE FROM attendance WHERE id = ?')->execute([(int) $_POST['delete_id']]);
    flash('success', 'Record deleted.');
    header('Location: attendance_manage.php?' . http_build_query(array_filter([
        'semester_id' => $_POST['semester_id'] ?? '',
        'class_id' => $_POST['class_id'] ?? '',
        'date' => $_POST['filter_date'] ?? '',
    ])));
    exit;
}

// Fetch all semesters for filter
$semesters = $pdo->query('SELECT id, name FROM semesters ORDER BY start_date DESC')->fetchAll();

// Selected filters
$semester_id = isset($_GET['semester_id']) && $_GET['semester_id'] !== '' ? (int) $_GET['semester_id'] : null;
$class_id = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int) $_GET['class_id'] : null;
$filter_date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : null;

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

// Build attendance query with filters
$rows = [];
$hasFilter = ($semester_id || $class_id || $filter_date);

if ($hasFilter) {
    $sql = "
        SELECT a.id, a.attendance_date, a.status, a.teacher_comment, s.full_name, c.class_name
        FROM attendance a
        JOIN enrollments e ON a.enrollment_id = e.id
        JOIN students s ON e.student_id = s.id
        JOIN classes c ON e.class_id = c.id
        WHERE 1=1
    ";
    $params = [];

    if ($semester_id) {
        $sql .= ' AND c.semester_id = ?';
        $params[] = $semester_id;
    }
    if ($class_id) {
        $sql .= ' AND c.id = ?';
        $params[] = $class_id;
    }
    if ($filter_date) {
        $sql .= ' AND a.attendance_date = ?';
        $params[] = $filter_date;
    }

    $sql .= ' ORDER BY a.attendance_date DESC, c.class_name, s.full_name LIMIT 500';
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
        <div class="filter-row filter-row-3">
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
            <div class="filter-group">
                <label for="date"><?= lang() === 'vi' ? 'Ngày' : 'Date' ?></label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($filter_date ?? '') ?>" onchange="this.form.submit()">
            </div>
        </div>
    </form>
</div>

<?php if (!$hasFilter): ?>
<div class="card empty-state">
    <div class="empty-icon">📋</div>
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
                <th><?= lang() === 'vi' ? 'Ngày' : 'Date' ?></th>
                <th><?= htmlspecialchars(t('student')) ?></th>
                <th><?= htmlspecialchars(t('nav.classes')) ?></th>
                <th>Status</th>
                <th><?= lang() === 'vi' ? 'Nhận xét GV' : 'Teacher comment' ?></th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['attendance_date']) ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['class_name']) ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                <td><?= htmlspecialchars($r['teacher_comment'] ?? '') ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete?');">
                        <input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>">
                        <input type="hidden" name="semester_id" value="<?= $semester_id ?? '' ?>">
                        <input type="hidden" name="class_id" value="<?= $class_id ?? '' ?>">
                        <input type="hidden" name="filter_date" value="<?= htmlspecialchars($filter_date ?? '') ?>">
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
    // Reset class selection when semester changes, then submit
    document.getElementById('class_id').value = '';
    sel.form.submit();
}
</script>
<?php renderFooter(); ?>
