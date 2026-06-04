<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'Course Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        try {
            $pdo->prepare('DELETE FROM courses WHERE id = ?')->execute([(int) $_POST['delete_id']]);
            flash('success', 'Course deleted.');
        } catch (PDOException $e) {
            flash('error', 'Cannot delete — course may be linked to classes.');
        }
    } elseif (isset($_POST['add_course'])) {
        $pdo->prepare('INSERT INTO courses (course_code, course_name, credits, department) VALUES (?, ?, ?, ?)')
            ->execute([
                strtoupper(trim($_POST['course_code'])),
                trim($_POST['course_name']),
                (int) $_POST['credits'],
                trim($_POST['department']),
            ]);
        flash('success', 'Course added.');
    }
    header('Location: courses_manage.php');
    exit;
}

$courses = $pdo->query('SELECT * FROM courses ORDER BY created_at DESC')->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2>New course</h2>
    <form method="POST" data-validate>
        <div class="form-grid">
            <div class="form-group"><label>Code</label><input name="course_code" required></div>
            <div class="form-group"><label>Name</label><input name="course_name" required></div>
            <div class="form-group"><label>Credits</label><input type="number" name="credits" min="1" max="10" required></div>
            <div class="form-group"><label>Department</label><input name="department" required></div>
        </div>
        <button type="submit" name="add_course" class="btn btn-primary">Add course</button>
    </form>
</div>
<div class="card">
    <h2>📚 <?= lang() === 'vi' ? 'Danh sách môn học' : 'All courses' ?></h2>
    <div class="search-filter-bar" style="margin-bottom:18px;">
        <input type="text" id="courseSearch" placeholder="<?= lang() === 'vi' ? '🔍 Tìm mã môn, tên môn...' : '🔍 Search code, name...' ?>" class="filter-input">
        <select id="courseDeptFilter" class="filter-select">
            <option value=""><?= lang() === 'vi' ? 'Tất cả khoa' : 'All departments' ?></option>
            <?php
            $cDepts = array_unique(array_filter(array_column($courses, 'department')));
            sort($cDepts);
            foreach ($cDepts as $cd): ?>
            <option value="<?= htmlspecialchars($cd) ?>"><?= htmlspecialchars($cd) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="table-wrap">
        <table class="data-table" id="courseTable">
            <thead><tr><th>Code</th><th>Name</th><th>Credits</th><th>Department</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($courses as $c): ?>
            <tr data-search="<?= htmlspecialchars(strtolower($c['course_code'] . ' ' . $c['course_name'] . ' ' . ($c['department'] ?? ''))) ?>" data-dept="<?= htmlspecialchars($c['department'] ?? '') ?>">
                <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
                <td><?= htmlspecialchars($c['course_name']) ?></td>
                <td><?= (int) $c['credits'] ?></td>
                <td><?= htmlspecialchars($c['department']) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete course?');">
                        <input type="hidden" name="delete_id" value="<?= (int) $c['id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="filter-count" id="courseCount"></div>
</div>
<script>
(function(){
    var input = document.getElementById('courseSearch');
    var dept = document.getElementById('courseDeptFilter');
    var table = document.getElementById('courseTable');
    var countEl = document.getElementById('courseCount');
    if (!input || !table) return;
    function filter() {
        var q = input.value.toLowerCase().trim();
        var d = dept ? dept.value : '';
        var rows = table.querySelectorAll('tbody tr');
        var shown = 0;
        rows.forEach(function(row) {
            var s = row.getAttribute('data-search') || '';
            var rd = row.getAttribute('data-dept') || '';
            var matchQ = !q || s.indexOf(q) !== -1;
            var matchD = !d || rd === d;
            row.style.display = (matchQ && matchD) ? '' : 'none';
            if (matchQ && matchD) shown++;
        });
        if (countEl) countEl.textContent = shown + ' / ' + rows.length + (q || d ? ' (filtered)' : '');
    }
    input.addEventListener('input', filter);
    if (dept) dept.addEventListener('change', filter);
    filter();
})();
</script>
<?php renderFooter(); ?>
