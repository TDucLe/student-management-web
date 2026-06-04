<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'Class Management';

if ($pdo->query('SELECT COUNT(*) FROM semesters')->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO semesters (name, term_type, school_year, start_date, end_date) VALUES ('Spring 2026', 'semester2', '2025-2026', '2026-01-01', '2026-06-30')");
}
if ($pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO rooms (room_number, building, capacity) VALUES ('A101', 'Main', 40)");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $pdo->prepare('DELETE FROM classes WHERE id = ?')->execute([(int) $_POST['delete_id']]);
        flash('success', 'Class removed.');
    } elseif (isset($_POST['add_class'])) {
        $pdo->prepare('INSERT INTO classes (class_name, course_id, semester_id, teacher_id, room_id) VALUES (?, ?, ?, ?, ?)')
            ->execute([
                trim($_POST['class_name']),
                (int) $_POST['course_id'],
                (int) $_POST['semester_id'],
                $_POST['teacher_id'] ? (int) $_POST['teacher_id'] : null,
                $_POST['room_id'] ? (int) $_POST['room_id'] : null,
            ]);
        flash('success', 'Class created.');
    }
    header('Location: classes_manage.php');
    exit;
}

$classes = $pdo->query("
    SELECT cl.id, cl.class_name, c.course_name, t.full_name AS teacher_name, s.name AS semester, r.room_number
    FROM classes cl
    LEFT JOIN courses c ON cl.course_id = c.id
    LEFT JOIN teachers t ON cl.teacher_id = t.id
    LEFT JOIN semesters s ON cl.semester_id = s.id
    LEFT JOIN rooms r ON cl.room_id = r.id
    ORDER BY cl.id DESC
")->fetchAll();

$courses = $pdo->query('SELECT id, course_code, course_name FROM courses')->fetchAll();
$teachers = $pdo->query('SELECT id, full_name FROM teachers WHERE deleted_at IS NULL')->fetchAll();
$semesters = $pdo->query('SELECT id, name FROM semesters')->fetchAll();
$rooms = $pdo->query('SELECT id, room_number FROM rooms')->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2>Create class</h2>
    <form method="POST" data-validate>
        <div class="form-grid">
            <div class="form-group"><label>Class name</label><input name="class_name" required></div>
            <div class="form-group"><label>Course</label>
                <select name="course_id" required>
                    <?php foreach ($courses as $c): ?><option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Teacher</label>
                <select name="teacher_id"><option value="">— None —</option>
                    <?php foreach ($teachers as $t): ?><option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Semester</label>
                <select name="semester_id" required><?php foreach ($semesters as $s): ?><option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="form-group"><label>Room</label>
                <select name="room_id"><?php foreach ($rooms as $r): ?><option value="<?= (int) $r['id'] ?>"><?= htmlspecialchars($r['room_number']) ?></option><?php endforeach; ?></select>
            </div>
        </div>
        <button type="submit" name="add_class" class="btn btn-primary">Create</button>
    </form>
</div>
<div class="card">
    <h2>🏫 <?= lang() === 'vi' ? 'Danh sách lớp học' : 'All classes' ?></h2>
    <div class="search-filter-bar" style="margin-bottom:18px;">
        <input type="text" id="classSearch" placeholder="<?= lang() === 'vi' ? '🔍 Tìm lớp, môn, giáo viên...' : '🔍 Search class, course, teacher...' ?>" class="filter-input">
        <select id="classSemFilter" class="filter-select">
            <option value=""><?= lang() === 'vi' ? 'Tất cả kỳ' : 'All semesters' ?></option>
            <?php foreach ($semesters as $s): ?>
            <option value="<?= htmlspecialchars($s['name']) ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="classTeacherFilter" class="filter-select">
            <option value=""><?= lang() === 'vi' ? 'Tất cả GV' : 'All teachers' ?></option>
            <?php
            $tNames = array_unique(array_filter(array_column($classes, 'teacher_name')));
            sort($tNames);
            foreach ($tNames as $tn): ?>
            <option value="<?= htmlspecialchars($tn) ?>"><?= htmlspecialchars($tn) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="table-wrap">
        <table class="data-table" id="classTable">
            <thead><tr><th>Class</th><th>Course</th><th>Teacher</th><th>Semester</th><th>Room</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($classes as $row): ?>
            <tr data-search="<?= htmlspecialchars(strtolower($row['class_name'] . ' ' . ($row['course_name'] ?? '') . ' ' . ($row['teacher_name'] ?? '') . ' ' . ($row['room_number'] ?? ''))) ?>" data-sem="<?= htmlspecialchars($row['semester'] ?? '') ?>" data-teacher="<?= htmlspecialchars($row['teacher_name'] ?? '') ?>">
                <td><?= htmlspecialchars($row['class_name']) ?></td>
                <td><?= htmlspecialchars($row['course_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['teacher_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($row['semester'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['room_number'] ?? '') ?></td>
                <td style="white-space:nowrap">
                    <a href="class_detail.php?class_id=<?= (int) $row['id'] ?>" class="btn btn-primary btn-sm">Manage</a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete class?');">
                        <input type="hidden" name="delete_id" value="<?= (int) $row['id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="filter-count" id="classCount"></div>
</div>
<script>
(function(){
    var input = document.getElementById('classSearch');
    var sem = document.getElementById('classSemFilter');
    var teacher = document.getElementById('classTeacherFilter');
    var table = document.getElementById('classTable');
    var countEl = document.getElementById('classCount');
    if (!input || !table) return;
    function filter() {
        var q = input.value.toLowerCase().trim();
        var s = sem ? sem.value : '';
        var t = teacher ? teacher.value : '';
        var rows = table.querySelectorAll('tbody tr');
        var shown = 0;
        rows.forEach(function(row) {
            var search = row.getAttribute('data-search') || '';
            var rs = row.getAttribute('data-sem') || '';
            var rt = row.getAttribute('data-teacher') || '';
            var matchQ = !q || search.indexOf(q) !== -1;
            var matchS = !s || rs === s;
            var matchT = !t || rt === t;
            row.style.display = (matchQ && matchS && matchT) ? '' : 'none';
            if (matchQ && matchS && matchT) shown++;
        });
        if (countEl) countEl.textContent = shown + ' / ' + rows.length + (q || s || t ? ' (filtered)' : '');
    }
    input.addEventListener('input', filter);
    if (sem) sem.addEventListener('change', filter);
    if (teacher) teacher.addEventListener('change', filter);
    filter();
})();
</script>
<?php renderFooter(); ?>
