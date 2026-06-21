<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$class_id = (int) ($_GET['class_id'] ?? 0);

$chk = $pdo->prepare('SELECT class_name FROM classes WHERE id = ? AND teacher_id = ?');
$chk->execute([$class_id, $teacher_id]);
$class = $chk->fetch();
if (!$class) {
    flash('error', 'Class not found or access denied.');
    header('Location: ' . app_path('teacher/classes.php'));
    exit;
}

$pageTitle = 'Students: ' . $class['class_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_enrollment'])) {
        try {
            $pdo->prepare("INSERT INTO enrollments (student_id, class_id, status) VALUES (?, ?, 'active')")
                ->execute([(int) $_POST['student_id'], $class_id]);
            flash('success', 'Student added.');
        } catch (PDOException $e) {
            flash('error', 'Already enrolled or invalid student.');
        }
    } elseif (isset($_POST['remove_enrollment'])) {
        $pdo->prepare('DELETE FROM enrollments WHERE id = ? AND class_id = ?')
            ->execute([(int) $_POST['enrollment_id'], $class_id]);
        flash('success', 'Student removed.');
    }
    header('Location: class_students.php?class_id=' . $class_id);
    exit;
}

$enrolled = $pdo->prepare("
    SELECT e.id AS enrollment_id, s.student_code, s.full_name, e.status
    FROM enrollments e JOIN students s ON e.student_id = s.id
    WHERE e.class_id = ? AND s.deleted_at IS NULL ORDER BY s.full_name
");
$enrolled->execute([$class_id]);
$list = $enrolled->fetchAll();

$avail = $pdo->prepare("
    SELECT s.id, s.student_code, s.full_name FROM students s
    WHERE s.deleted_at IS NULL AND s.id NOT IN (SELECT student_id FROM enrollments WHERE class_id = ?)
    ORDER BY s.full_name
");
$avail->execute([$class_id]);
$available = $avail->fetchAll();

renderHeader($pageTitle, $user);
?>
<p><a href="<?= htmlspecialchars(app_path('teacher/classes.php')) ?>">&larr; Back to classes</a></p>
<div class="card">
    <h2>Add student to class</h2>
    <form method="POST">
        <div class="form-group">
            <select name="student_id" required>
                <option value="">— Select —</option>
                <?php foreach ($available as $s): ?>
                <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['student_code'] . ' — ' . $s['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="add_enrollment" class="btn btn-primary" <?= empty($available) ? 'disabled' : '' ?>>Add</button>
    </form>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Code</th><th>Name</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($list as $e): ?>
            <tr>
                <td><?= htmlspecialchars($e['student_code']) ?></td>
                <td><?= htmlspecialchars($e['full_name']) ?></td>
                <td><?= htmlspecialchars($e['status']) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Remove from class?');">
                        <input type="hidden" name="enrollment_id" value="<?= (int) $e['enrollment_id'] ?>">
                        <button name="remove_enrollment" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
