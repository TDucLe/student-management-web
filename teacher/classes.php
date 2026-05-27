<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = 'My Classes';

if ($pdo->query('SELECT COUNT(*) FROM semesters')->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO semesters (name, start_date, end_date) VALUES ('Spring 2026', '2026-01-01', '2026-06-30')");
}
if ($pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO rooms (room_number, building) VALUES ('A101', 'Main')");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_class'])) {
        $pdo->prepare('INSERT INTO classes (class_name, course_id, semester_id, teacher_id, room_id) VALUES (?, ?, ?, ?, ?)')
            ->execute([trim($_POST['class_name']), (int) $_POST['course_id'], (int) $_POST['semester_id'], $teacher_id, (int) $_POST['room_id']]);
        flash('success', 'Class created.');
    } elseif (isset($_POST['delete_class'])) {
        $pdo->prepare('DELETE FROM classes WHERE id = ? AND teacher_id = ?')->execute([(int) $_POST['class_id'], $teacher_id]);
        flash('success', 'Class removed.');
    }
    header('Location: classes.php');
    exit;
}

$courses = $pdo->query('SELECT id, course_code, course_name FROM courses')->fetchAll();
$semesters = $pdo->query('SELECT id, name FROM semesters')->fetchAll();
$rooms = $pdo->query('SELECT id, room_number FROM rooms')->fetchAll();
$stmt = $pdo->prepare("
    SELECT cl.id, cl.class_name, c.course_name, s.name AS sem_name, r.room_number
    FROM classes cl
    JOIN courses c ON cl.course_id = c.id
    LEFT JOIN semesters s ON cl.semester_id = s.id
    LEFT JOIN rooms r ON cl.room_id = r.id
    WHERE cl.teacher_id = ? ORDER BY cl.id DESC
");
$stmt->execute([$teacher_id]);
$classes_data = $stmt->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2>Open new class</h2>
    <form method="POST" data-validate>
        <div class="form-grid">
            <div class="form-group"><label>Class name</label><input name="class_name" required></div>
            <div class="form-group"><label>Course</label>
                <select name="course_id" required><?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name']) ?></option>
                <?php endforeach; ?></select>
            </div>
            <div class="form-group"><label>Semester</label>
                <select name="semester_id" required><?php foreach ($semesters as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?></select>
            </div>
            <div class="form-group"><label>Room</label>
                <select name="room_id" required><?php foreach ($rooms as $r): ?>
                    <option value="<?= (int) $r['id'] ?>"><?= htmlspecialchars($r['room_number']) ?></option>
                <?php endforeach; ?></select>
            </div>
        </div>
        <button type="submit" name="add_class" class="btn btn-primary">Create class</button>
    </form>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Class</th><th>Course</th><th>Semester</th><th>Room</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($classes_data as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['class_name']) ?></td>
                <td><?= htmlspecialchars($row['course_name']) ?></td>
                <td><?= htmlspecialchars($row['sem_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['room_number'] ?? '') ?></td>
                <td style="white-space:nowrap">
                    <a href="class_students.php?class_id=<?= (int) $row['id'] ?>" class="btn btn-primary btn-sm">Students</a>
                    <form method="POST" style="display:inline"><input type="hidden" name="class_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" name="delete_class" class="btn btn-danger btn-sm">Delete</button></form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
