<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = t('nav.attendance');

$classes = $pdo->prepare('SELECT id, class_name FROM classes WHERE teacher_id = ? ORDER BY class_name');
$classes->execute([$teacher_id]);
$classList = $classes->fetchAll();

$class_id = (int) ($_GET['class_id'] ?? ($classList[0]['id'] ?? 0));
$date = $_GET['date'] ?? date('Y-m-d');
$sched_id = (int) ($_GET['sched_id'] ?? 0);

if ($class_id && !verifyTeacherOwnsClass($pdo, $teacher_id, $class_id)) {
    $class_id = 0;
}

$schedules = [];
if ($class_id) {
    $s = $pdo->prepare('SELECT id, day_of_week, start_time, end_time FROM schedules WHERE class_id = ?');
    $s->execute([$class_id]);
    $schedules = $s->fetchAll();
    if ($sched_id === 0 && !empty($schedules)) {
        $sched_id = (int) $schedules[0]['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_class_attendance'])) {
    $class_id = (int) $_POST['class_id'];
    $sched_id = (int) $_POST['sched_id'];
    $date = $_POST['date'];
    if (verifyTeacherOwnsClass($pdo, $teacher_id, $class_id) && $sched_id > 0) {
        $statuses = $_POST['status'] ?? [];
        $comments = $_POST['teacher_comment'] ?? [];
        foreach ($statuses as $enrollment_id => $status) {
            if (!in_array($status, ['present', 'absent', 'late'], true)) {
                continue;
            }
            $comment = trim($comments[$enrollment_id] ?? '');
            $pdo->prepare('INSERT INTO attendance (enrollment_id, schedule_id, attendance_date, status, teacher_comment) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status), teacher_comment=VALUES(teacher_comment)')
                ->execute([(int) $enrollment_id, $sched_id, $date, $status, $comment]);
        }
        flash('success', lang() === 'vi' ? 'Đã lưu điểm danh cả lớp.' : 'Class attendance saved.');
    }
    header('Location: attendance.php?class_id=' . $class_id . '&date=' . urlencode($date) . '&sched_id=' . $sched_id);
    exit;
}

$roster = [];
$existing = [];
if ($class_id && $sched_id) {
    $roster = getClassEnrollments($pdo, $class_id);
    $stmt = $pdo->prepare('SELECT enrollment_id, status, teacher_comment FROM attendance WHERE schedule_id = ? AND attendance_date = ?');
    $stmt->execute([$sched_id, $date]);
    foreach ($stmt->fetchAll() as $row) {
        $existing[$row['enrollment_id']] = $row;
    }
}

renderHeader($pageTitle, $user);
?>
<div class="card class-picker">
    <form method="GET" class="form-grid">
        <div class="form-group">
            <label><?= htmlspecialchars(t('select_class')) ?></label>
            <select name="class_id" onchange="this.form.submit()">
                <?php foreach ($classList as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $class_id ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label><?= lang() === 'vi' ? 'Ngày' : 'Date' ?></label>
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
        </div>
        <div class="form-group">
            <label><?= lang() === 'vi' ? 'Tiết học' : 'Session' ?></label>
            <select name="sched_id" onchange="this.form.submit()">
                <?php foreach ($schedules as $sch): ?>
                <option value="<?= (int) $sch['id'] ?>" <?= (int) $sch['id'] === $sched_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sch['day_of_week'] . ' ' . date('H:i', strtotime($sch['start_time']))) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if (empty($schedules) && $class_id): ?>
<div class="alert alert-info"><?= lang() === 'vi' ? 'Lớp chưa có lịch. Admin cần xếp lịch trước.' : 'No schedule for this class yet.' ?></div>
<?php elseif ($class_id && $sched_id && !empty($roster)): ?>
<form method="POST">
    <input type="hidden" name="class_id" value="<?= $class_id ?>">
    <input type="hidden" name="sched_id" value="<?= $sched_id ?>">
    <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
    <div class="card">
        <h2><?= htmlspecialchars(t('all_students')) ?> — <?= count($roster) ?> <?= htmlspecialchars(t('student')) ?></h2>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= htmlspecialchars(t('student')) ?></th>
                        <th><?= htmlspecialchars(t('present')) ?> / <?= htmlspecialchars(t('absent')) ?> / <?= htmlspecialchars(t('late')) ?></th>
                        <th><?= lang() === 'vi' ? 'Nhận xét GV' : 'Teacher comment' ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($roster as $i => $stu):
                    $eid = (int) $stu['enrollment_id'];
                    $cur = $existing[$eid] ?? null;
                    $curStatus = $cur['status'] ?? 'present';
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($stu['full_name']) ?></strong><br><small><?= htmlspecialchars($stu['student_code']) ?></small></td>
                    <td>
                        <div class="status-group">
                            <?php foreach (['present', 'absent', 'late'] as $st): ?>
                            <label class="status-btn <?= $st ?>">
                                <input type="radio" name="status[<?= $eid ?>]" value="<?= $st ?>" <?= $curStatus === $st ? 'checked' : '' ?>>
                                <?= htmlspecialchars(t($st)) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td><input type="text" name="teacher_comment[<?= $eid ?>]" value="<?= htmlspecialchars($cur['teacher_comment'] ?? '') ?>" placeholder="..."></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" name="save_class_attendance" class="btn btn-gold" style="margin-top:20px"><?= htmlspecialchars(t('save_attendance')) ?></button>
    </div>
</form>
<?php endif;
renderFooter();
