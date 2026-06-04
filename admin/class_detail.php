<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/schedule_ui.php';
requireRole('admin');

$user = getCurrentUser();
$class_id = (int) ($_GET['class_id'] ?? 0);
if ($class_id <= 0) {
    header('Location: ' . app_path('admin/classes_manage.php'));
    exit;
}

$cls = $pdo->prepare("
    SELECT cl.*, c.course_name, c.course_code, t.full_name AS teacher_name,
           s.name AS semester_name, s.start_date AS sem_start, s.end_date AS sem_end,
           r.room_number
    FROM classes cl
    LEFT JOIN courses c ON cl.course_id = c.id
    LEFT JOIN teachers t ON cl.teacher_id = t.id
    LEFT JOIN semesters s ON cl.semester_id = s.id
    LEFT JOIN rooms r ON cl.room_id = r.id
    WHERE cl.id = ?
");
$cls->execute([$class_id]);
$class = $cls->fetch();
if (!$class) {
    flash('error', 'Class not found.');
    header('Location: ' . app_path('admin/classes_manage.php'));
    exit;
}

$pageTitle = 'Class: ' . $class['class_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_enrollment'])) {
        $sid = (int) $_POST['student_id'];
        try {
            $pdo->prepare("INSERT INTO enrollments (student_id, class_id, status) VALUES (?, ?, 'active')")
                ->execute([$sid, $class_id]);
            flash('success', 'Student added to class.');
        } catch (PDOException $e) {
            flash('error', 'Student already in class or invalid.');
        }
    } elseif (isset($_POST['remove_enrollment'])) {
        $pdo->prepare('DELETE FROM enrollments WHERE id = ? AND class_id = ?')
            ->execute([(int) $_POST['enrollment_id'], $class_id]);
        flash('success', 'Student removed from class.');
    } elseif (isset($_POST['add_schedule'])) {
        $pdo->prepare('INSERT INTO schedules (class_id, room_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?)')
            ->execute([
                $class_id,
                $_POST['room_id'] ? (int) $_POST['room_id'] : null,
                $_POST['day'],
                $_POST['start'],
                $_POST['end'],
            ]);
        flash('success', 'Schedule slot added.');
    } elseif (isset($_POST['delete_schedule'])) {
        $pdo->prepare('DELETE FROM schedules WHERE id = ? AND class_id = ?')
            ->execute([(int) $_POST['schedule_id'], $class_id]);
        flash('success', 'Schedule slot removed.');
    } elseif (isset($_POST['update_class'])) {
        $pdo->prepare('UPDATE classes SET class_name = ?, course_id = ?, semester_id = ?, teacher_id = ?, room_id = ? WHERE id = ?')
            ->execute([
                trim($_POST['class_name']),
                (int) $_POST['course_id'],
                (int) $_POST['semester_id'],
                $_POST['teacher_id'] ? (int) $_POST['teacher_id'] : null,
                $_POST['room_id'] ? (int) $_POST['room_id'] : null,
                $class_id,
            ]);
        flash('success', 'Class updated.');
    }
    header('Location: class_detail.php?class_id=' . $class_id);
    exit;
}

$enrolled = $pdo->prepare("
    SELECT e.id AS enrollment_id, s.id AS student_id, s.student_code, s.full_name, e.status, e.enrolled_at
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    WHERE e.class_id = ? AND s.deleted_at IS NULL
    ORDER BY s.full_name
");
$enrolled->execute([$class_id]);
$enrolledList = $enrolled->fetchAll();

$notEnrolled = $pdo->prepare("
    SELECT s.id, s.student_code, s.full_name
    FROM students s
    WHERE s.deleted_at IS NULL
      AND s.id NOT IN (SELECT student_id FROM enrollments WHERE class_id = ?)
    ORDER BY s.full_name
");
$notEnrolled->execute([$class_id]);
$availableStudents = $notEnrolled->fetchAll();

$schedStmt = $pdo->prepare("
    SELECT sch.id, sch.day_of_week, sch.start_time, sch.end_time,
           COALESCE(r.room_number, cr.room_number) AS room_number
    FROM schedules sch
    LEFT JOIN rooms r ON sch.room_id = r.id
    LEFT JOIN classes cl ON sch.class_id = cl.id
    LEFT JOIN rooms cr ON cl.room_id = cr.id
    WHERE sch.class_id = ?
");
$schedStmt->execute([$class_id]);
$scheduleSlots = $schedStmt->fetchAll();

$scheduleView = $pdo->prepare("
    SELECT sch.day_of_week, sch.start_time, sch.end_time, cl.class_name, c.course_name,
           COALESCE(r.room_number, cr.room_number) AS room_number
    FROM schedules sch
    JOIN classes cl ON sch.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    LEFT JOIN rooms r ON sch.room_id = r.id
    LEFT JOIN rooms cr ON cl.room_id = cr.id
    WHERE sch.class_id = ?
");
$scheduleView->execute([$class_id]);
$timetableRows = $scheduleView->fetchAll();

$courses = $pdo->query('SELECT id, course_code, course_name FROM courses')->fetchAll();
$teachers = $pdo->query('SELECT id, full_name FROM teachers WHERE deleted_at IS NULL')->fetchAll();
$semesters = $pdo->query('SELECT id, name FROM semesters')->fetchAll();
$rooms = $pdo->query('SELECT id, room_number FROM rooms')->fetchAll();

renderHeader($pageTitle, $user);
?>
<p><a href="<?= htmlspecialchars(app_path('admin/classes_manage.php')) ?>">&larr; Back to classes</a></p>

<div class="card">
    <h2>Class information</h2>
    <form method="POST" data-validate>
        <div class="form-grid">
            <div class="form-group"><label>Class name</label>
                <input name="class_name" value="<?= htmlspecialchars($class['class_name']) ?>" required></div>
            <div class="form-group"><label>Course</label>
                <select name="course_id" required>
                    <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $class['course_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Teacher</label>
                <select name="teacher_id">
                    <option value="">— None —</option>
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === (int) $class['teacher_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Semester</label>
                <select name="semester_id" required>
                    <?php foreach ($semesters as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === (int) $class['semester_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Default room</label>
                <select name="room_id">
                    <?php foreach ($rooms as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" <?= (int) $r['id'] === (int) $class['room_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['room_number']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" name="update_class" class="btn btn-primary">Save class</button>
    </form>
</div>

<div class="card">
    <h2>Weekly timetable</h2>
    <?php renderWeeklyTimetable($timetableRows, 'Admin chưa xếp lịch cho lớp này.'); ?>
</div>

<div class="card">
    <h2>Manage schedule (per class)</h2>
    <?php if (!empty($class['sem_start']) && !empty($class['sem_end'])): ?>
    <div class="alert alert-info" style="margin-bottom:16px;">
        📅 <strong><?= htmlspecialchars($class['semester_name'] ?? '') ?></strong>: 
        <?= date('d/m/Y', strtotime($class['sem_start'])) ?> → <?= date('d/m/Y', strtotime($class['sem_end'])) ?>
        — Lịch học chỉ có hiệu lực trong khoảng thời gian kỳ học.
    </div>
    <?php endif; ?>
    <form method="POST" data-validate>
        <div class="form-grid">
            <div class="form-group"><label>Day</label>
                <select name="day" required>
                    <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?>
                    <option><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Start</label><input type="time" name="start" required></div>
            <div class="form-group"><label>End</label><input type="time" name="end" required></div>
            <div class="form-group"><label>Room (optional)</label>
                <select name="room_id"><option value="">Use class default</option>
                    <?php foreach ($rooms as $r): ?>
                    <option value="<?= (int) $r['id'] ?>"><?= htmlspecialchars($r['room_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" name="add_schedule" class="btn btn-primary">Add slot</button>
    </form>
    <div class="table-wrap" style="margin-top:20px">
        <table class="data-table">
            <thead><tr><th>Day</th><th>Time</th><th>Room</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($scheduleSlots as $sch): ?>
            <tr>
                <td><?= htmlspecialchars($sch['day_of_week']) ?></td>
                <td><?= date('H:i', strtotime($sch['start_time'])) ?> – <?= date('H:i', strtotime($sch['end_time'])) ?></td>
                <td><?= htmlspecialchars($sch['room_number'] ?? '—') ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete slot?');">
                        <input type="hidden" name="schedule_id" value="<?= (int) $sch['id'] ?>">
                        <button name="delete_schedule" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Students in class</h2>
    <form method="POST" style="margin-bottom:20px">
        <div class="form-grid">
            <div class="form-group"><label>Add student</label>
                <select name="student_id" required>
                    <option value="">— Select student —</option>
                    <?php foreach ($availableStudents as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['student_code'] . ' — ' . $s['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" name="add_enrollment" class="btn btn-primary" <?= empty($availableStudents) ? 'disabled' : '' ?>>Add to class</button>
    </form>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Enrolled</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($enrolledList)): ?>
            <tr><td colspan="5">No students in this class.</td></tr>
            <?php else: foreach ($enrolledList as $e): ?>
            <tr>
                <td><?= htmlspecialchars($e['student_code']) ?></td>
                <td><?= htmlspecialchars($e['full_name']) ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($e['status']) ?>"><?= htmlspecialchars($e['status']) ?></span></td>
                <td><?= htmlspecialchars($e['enrolled_at']) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Remove student from class?');">
                        <input type="hidden" name="enrollment_id" value="<?= (int) $e['enrollment_id'] ?>">
                        <button name="remove_enrollment" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
