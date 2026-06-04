<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'Teacher Profiles';
$edit_id = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_teacher'])) {
        try {
            $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)')
                ->execute([trim($_POST['username']), trim($_POST['email']), $hash, 'teacher']);
            $uid = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO teachers (user_id, teacher_code, full_name, department, contact) VALUES (?, ?, ?, ?, ?)')
                ->execute([
                    $uid,
                    trim($_POST['teacher_code']),
                    trim($_POST['full_name']),
                    trim($_POST['department'] ?? ''),
                    trim($_POST['contact'] ?? ''),
                ]);
            flash('success', 'Teacher created.');
        } catch (PDOException $e) {
            flash('error', 'Could not create teacher.');
        }
    } elseif (isset($_POST['update_teacher'])) {
        $tid = (int) $_POST['teacher_id'];
        $t = $pdo->prepare('SELECT user_id FROM teachers WHERE id = ?');
        $t->execute([$tid]);
        $row = $t->fetch();
        if ($row) {
            $pdo->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([trim($_POST['email']), $row['user_id']]);
            $pdo->prepare('UPDATE teachers SET teacher_code = ?, full_name = ?, department = ?, contact = ? WHERE id = ?')
                ->execute([
                    trim($_POST['teacher_code']),
                    trim($_POST['full_name']),
                    trim($_POST['department'] ?? ''),
                    trim($_POST['contact'] ?? ''),
                    $tid,
                ]);
            flash('success', 'Teacher updated.');
        }
    }
    header('Location: teachers_manage.php');
    exit;
}

$teachers = $pdo->query("
    SELECT t.*, u.username, u.email
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.deleted_at IS NULL AND u.deleted_at IS NULL
    ORDER BY t.full_name
")->fetchAll();

$editTeacher = null;
if ($edit_id > 0) {
    $st = $pdo->prepare("SELECT t.*, u.username, u.email FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $st->execute([$edit_id]);
    $editTeacher = $st->fetch();
}

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2><?= $editTeacher ? 'Edit teacher' : 'Add new teacher' ?></h2>
    <form method="POST" data-validate>
        <?php if ($editTeacher): ?><input type="hidden" name="teacher_id" value="<?= (int) $editTeacher['id'] ?>"><?php endif; ?>
        <div class="form-grid">
            <?php if (!$editTeacher): ?>
            <div class="form-group"><label>Username</label><input name="username" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6"></div>
            <?php else: ?>
            <div class="form-group"><label>Username</label><input value="<?= htmlspecialchars($editTeacher['username']) ?>" disabled></div>
            <?php endif; ?>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($editTeacher['email'] ?? '') ?>" required></div>
            <div class="form-group"><label>Teacher code</label><input name="teacher_code" value="<?= htmlspecialchars($editTeacher['teacher_code'] ?? '') ?>" required></div>
            <div class="form-group"><label>Full name</label><input name="full_name" value="<?= htmlspecialchars($editTeacher['full_name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Department</label><input name="department" value="<?= htmlspecialchars($editTeacher['department'] ?? '') ?>"></div>
            <div class="form-group"><label>Contact</label><input name="contact" value="<?= htmlspecialchars($editTeacher['contact'] ?? '') ?>"></div>
        </div>
        <button type="submit" name="<?= $editTeacher ? 'update_teacher' : 'add_teacher' ?>" class="btn btn-primary">
            <?= $editTeacher ? 'Save changes' : 'Create teacher' ?>
        </button>
        <?php if ($editTeacher): ?><a href="teachers_manage.php" class="btn btn-secondary" style="margin-left:8px">Cancel</a><?php endif; ?>
    </form>
</div>
<div class="card">
    <h2>👨‍🏫 <?= lang() === 'vi' ? 'Danh sách giáo viên' : 'All teachers' ?></h2>
    <div class="search-filter-bar" style="margin-bottom:18px;">
        <input type="text" id="teacherSearch" placeholder="<?= lang() === 'vi' ? '🔍 Tìm theo tên, mã GV, liên hệ...' : '🔍 Search name, code, contact...' ?>" class="filter-input">
        <select id="teacherDeptFilter" class="filter-select">
            <option value=""><?= lang() === 'vi' ? 'Tất cả khoa' : 'All departments' ?></option>
            <?php
            $depts = array_unique(array_filter(array_column($teachers, 'department')));
            sort($depts);
            foreach ($depts as $d): ?>
            <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="table-wrap">
        <table class="data-table" id="teacherTable">
            <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Contact</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($teachers as $t): ?>
            <tr data-search="<?= htmlspecialchars(strtolower($t['teacher_code'] . ' ' . $t['full_name'] . ' ' . ($t['department'] ?? '') . ' ' . ($t['contact'] ?? ''))) ?>" data-dept="<?= htmlspecialchars($t['department'] ?? '') ?>">
                <td><?= htmlspecialchars($t['teacher_code']) ?></td>
                <td><?= htmlspecialchars($t['full_name']) ?></td>
                <td><?= htmlspecialchars($t['department'] ?? '—') ?></td>
                <td><?= htmlspecialchars($t['contact'] ?? '—') ?></td>
                <td><a href="?edit=<?= (int) $t['id'] ?>" class="btn btn-secondary btn-sm">Edit</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="filter-count" id="teacherCount"></div>
</div>
<script>
(function(){
    var input = document.getElementById('teacherSearch');
    var dept = document.getElementById('teacherDeptFilter');
    var table = document.getElementById('teacherTable');
    var countEl = document.getElementById('teacherCount');
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
