<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$pageTitle = 'Course Catalog';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        try {
            $pdo->prepare('INSERT INTO courses (course_code, course_name, credits, department) VALUES (?, ?, ?, ?)')
                ->execute([strtoupper(trim($_POST['course_code'])), trim($_POST['course_name']), (int) $_POST['credits'], trim($_POST['department'])]);
            flash('success', 'Course added.');
        } catch (PDOException $e) {
            flash('error', 'Course code may already exist.');
        }
    } elseif (isset($_POST['delete_course'])) {
        try {
            $pdo->prepare('DELETE FROM courses WHERE id = ?')->execute([(int) $_POST['course_id']]);
            flash('success', 'Course deleted.');
        } catch (PDOException $e) {
            flash('error', 'Cannot delete — linked to classes.');
        }
    }
    header('Location: courses.php');
    exit;
}

$courses = $pdo->query('SELECT * FROM courses ORDER BY created_at DESC')->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2>Add to catalog</h2>
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
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Code</th><th>Name</th><th>Credits</th><th>Dept</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($courses as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['course_code']) ?></strong></td>
                <td><?= htmlspecialchars($r['course_name']) ?></td>
                <td><?= (int) $r['credits'] ?></td>
                <td><?= htmlspecialchars($r['department']) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete?');">
                        <input type="hidden" name="course_id" value="<?= (int) $r['id'] ?>">
                        <button type="submit" name="delete_course" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
