<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'Student Profiles';
$edit_id = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)')
                ->execute([$username, $email, $hash, 'student']);
            $uid = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO students (user_id, student_code, full_name, dob, major, contact, address) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([
                    $uid,
                    trim($_POST['student_code']),
                    trim($_POST['full_name']),
                    $_POST['dob'] ?: null,
                    trim($_POST['major'] ?? ''),
                    trim($_POST['contact'] ?? ''),
                    trim($_POST['address'] ?? ''),
                ]);
            flash('success', 'Student created.');
        } catch (PDOException $e) {
            flash('error', 'Could not create student: ' . $e->getMessage());
        }
    } elseif (isset($_POST['update_student'])) {
        $sid = (int) $_POST['student_id'];
        $stu = $pdo->prepare('SELECT user_id FROM students WHERE id = ?');
        $stu->execute([$sid]);
        $row = $stu->fetch();
        if ($row) {
            $pdo->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([trim($_POST['email']), $row['user_id']]);
            $pdo->prepare('UPDATE students SET student_code = ?, full_name = ?, dob = ?, major = ?, contact = ?, address = ? WHERE id = ?')
                ->execute([
                    trim($_POST['student_code']),
                    trim($_POST['full_name']),
                    $_POST['dob'] ?: null,
                    trim($_POST['major'] ?? ''),
                    trim($_POST['contact'] ?? ''),
                    trim($_POST['address'] ?? ''),
                    $sid,
                ]);
            flash('success', 'Student updated.');
        }
    }
    $redirect = '';
    if (isset($_POST['update_student'])) {
        $redirect = '?edit=' . (int) $_POST['student_id'];
    }
    header('Location: students_manage.php' . $redirect);
    exit;
}

$students = $pdo->query("
    SELECT s.*, u.username, u.email
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.deleted_at IS NULL AND u.deleted_at IS NULL
    ORDER BY s.full_name
")->fetchAll();

$editStudent = null;
if ($edit_id > 0) {
    $st = $pdo->prepare("SELECT s.*, u.username, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $st->execute([$edit_id]);
    $editStudent = $st->fetch();
}

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2><?= $editStudent ? 'Edit student' : 'Add new student' ?></h2>
    <form method="POST" data-validate>
        <?php if ($editStudent): ?>
            <input type="hidden" name="student_id" value="<?= (int) $editStudent['id'] ?>">
        <?php endif; ?>
        <div class="form-grid">
            <?php if (!$editStudent): ?>
            <div class="form-group"><label>Username</label><input name="username" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6"></div>
            <?php else: ?>
            <div class="form-group"><label>Username</label><input value="<?= htmlspecialchars($editStudent['username']) ?>" disabled></div>
            <?php endif; ?>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($editStudent['email'] ?? '') ?>" required></div>
            <div class="form-group"><label>Student code</label><input name="student_code" value="<?= htmlspecialchars($editStudent['student_code'] ?? '') ?>" required></div>
            <div class="form-group"><label>Full name</label><input name="full_name" value="<?= htmlspecialchars($editStudent['full_name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Date of birth</label><input type="date" name="dob" value="<?= htmlspecialchars($editStudent['dob'] ?? '') ?>"></div>
            <div class="form-group"><label>Major</label><input name="major" value="<?= htmlspecialchars($editStudent['major'] ?? '') ?>"></div>
            <div class="form-group"><label>Contact</label><input name="contact" value="<?= htmlspecialchars($editStudent['contact'] ?? '') ?>"></div>
            <div class="form-group" style="grid-column:1/-1"><label>Address</label><input name="address" value="<?= htmlspecialchars($editStudent['address'] ?? '') ?>"></div>
        </div>
        <button type="submit" name="<?= $editStudent ? 'update_student' : 'add_student' ?>" class="btn btn-primary">
            <?= $editStudent ? 'Save changes' : 'Create student' ?>
        </button>
        <?php if ($editStudent): ?>
        <a href="students_manage.php" class="btn btn-secondary" style="margin-left:8px">Cancel</a>
        <?php endif; ?>
    </form>
</div>
<div class="card">
    <h2>🎓 <?= lang() === 'vi' ? 'Danh sách sinh viên' : 'All students' ?></h2>
    <div class="search-filter-bar" style="margin-bottom:18px;">
        <input type="text" id="studentSearch" placeholder="<?= lang() === 'vi' ? '🔍 Tìm theo tên, mã SV, email...' : '🔍 Search name, code, email...' ?>" class="filter-input">
        <select id="studentMajorFilter" class="filter-select">
            <option value=""><?= lang() === 'vi' ? 'Tất cả ngành' : 'All majors' ?></option>
            <?php
            $majors = array_unique(array_filter(array_column($students, 'major')));
            sort($majors);
            foreach ($majors as $m): ?>
            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="table-wrap">
        <table class="data-table" id="studentTable">
            <thead><tr><th>Code</th><th>Name</th><th>Major</th><th>Contact</th><th>Email</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($students as $s): ?>
            <tr data-search="<?= htmlspecialchars(strtolower($s['student_code'] . ' ' . $s['full_name'] . ' ' . ($s['email'] ?? '') . ' ' . ($s['contact'] ?? ''))) ?>" data-major="<?= htmlspecialchars($s['major'] ?? '') ?>">
                <td><?= htmlspecialchars($s['student_code']) ?></td>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['major'] ?? '—') ?></td>
                <td><?= htmlspecialchars($s['contact'] ?? '—') ?></td>
                <td><?= htmlspecialchars($s['email'] ?? '') ?></td>
                <td><a href="?edit=<?= (int) $s['id'] ?>" class="btn btn-secondary btn-sm">Edit</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="filter-count" id="studentCount"></div>
</div>
<script>
(function(){
    var input = document.getElementById('studentSearch');
    var major = document.getElementById('studentMajorFilter');
    var table = document.getElementById('studentTable');
    var countEl = document.getElementById('studentCount');
    if (!input || !table) return;
    function filter() {
        var q = input.value.toLowerCase().trim();
        var m = major ? major.value : '';
        var rows = table.querySelectorAll('tbody tr');
        var shown = 0;
        rows.forEach(function(row) {
            var s = row.getAttribute('data-search') || '';
            var rm = row.getAttribute('data-major') || '';
            var matchQ = !q || s.indexOf(q) !== -1;
            var matchM = !m || rm === m;
            row.style.display = (matchQ && matchM) ? '' : 'none';
            if (matchQ && matchM) shown++;
        });
        if (countEl) countEl.textContent = shown + ' / ' + rows.length + (q || m ? ' (filtered)' : '');
    }
    input.addEventListener('input', filter);
    if (major) major.addEventListener('change', filter);
    filter();
})();
</script>
<?php renderFooter(); ?>
