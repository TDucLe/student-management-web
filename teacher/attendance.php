<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = 'Class Attendance';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_bulk_att'])) {
    $class_id = (int) $_POST['class_id'];
    $sched_id = (int) $_POST['sched_id'];
    $date = $_POST['date'];
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO attendance (enrollment_id, schedule_id, attendance_date, status, note) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status), note=VALUES(note)');
        
        if (isset($_POST['attendance']) && is_array($_POST['attendance'])) {
            foreach ($_POST['attendance'] as $enroll_id => $data) {
                $status = $data['status'] ?? 'present';
                $note = trim($data['note'] ?? '');
                $stmt->execute([(int)$enroll_id, $sched_id, $date, $status, $note]);
            }
        }
        $pdo->commit();
        flash('success', 'Attendance saved for the entire class.');
    } catch (PDOException $e) {
        $pdo->rollBack();
        flash('error', 'Error saving attendance: ' . $e->getMessage());
    }
    header("Location: attendance.php?class_id=$class_id&sched_id=$sched_id&date=$date");
    exit;
}

$classes = $pdo->prepare('SELECT id, class_name FROM classes WHERE teacher_id = ?');
$classes->execute([$teacher_id]);
$classes = $classes->fetchAll();

$filter_class = (int)($_GET['class_id'] ?? 0);
$filter_sched = (int)($_GET['sched_id'] ?? 0);
$filter_date = $_GET['date'] ?? date('Y-m-d');

$schedules = [];
$students = [];

if ($filter_class > 0) {
    $sched_stmt = $pdo->prepare('SELECT id, day_of_week, start_time, end_time FROM schedules WHERE class_id = ?');
    $sched_stmt->execute([$filter_class]);
    $schedules = $sched_stmt->fetchAll();

    if ($filter_sched > 0 && $filter_date) {
        $stu_stmt = $pdo->prepare("
            SELECT e.id AS enrollment_id, s.student_code, s.full_name, a.status, a.note 
            FROM enrollments e 
            JOIN students s ON e.student_id = s.id 
            LEFT JOIN attendance a ON a.enrollment_id = e.id AND a.schedule_id = ? AND a.attendance_date = ?
            WHERE e.class_id = ? AND e.status = 'active'
            ORDER BY s.full_name ASC
        ");
        $stu_stmt->execute([$filter_sched, $filter_date, $filter_class]);
        $students = $stu_stmt->fetchAll();
    }
}

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2>1. Select Class & Schedule Slot</h2>
    <form method="GET" class="form-grid" style="align-items: end;">
        <div class="form-group"><label>Class</label>
            <select name="class_id" required onchange="this.form.submit()">
                <option value="">— Select Class —</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $filter_class === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($filter_class > 0): ?>
        <div class="form-group"><label>Schedule slot</label>
            <select name="sched_id" required onchange="this.form.submit()">
                <option value="">— Select Schedule —</option>
                <?php foreach ($schedules as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= $filter_sched === (int) $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['day_of_week']) ?> (<?= date('H:i', strtotime($s['start_time'])) ?> - <?= date('H:i', strtotime($s['end_time'] ?? $s['start_time'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Date</label>
            <input type="date" name="date" required value="<?= htmlspecialchars($filter_date) ?>" onchange="this.form.submit()">
        </div>
        <noscript><button type="submit" class="btn btn-secondary" style="width: 100%;">Load Class List</button></noscript>
        <?php endif; ?>
    </form>
</div>

<?php if ($filter_class > 0 && $filter_sched > 0): ?>
<div class="card">
    <h2>2. Class Attendance Roster</h2>
    <?php if (empty($students)): ?>
        <div class="alert alert-info">No active students found in this class.</div>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="class_id" value="<?= $filter_class ?>">
            <input type="hidden" name="sched_id" value="<?= $filter_sched ?>">
            <input type="hidden" name="date" value="<?= htmlspecialchars($filter_date) ?>">
            
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $row): 
                        $status = $row['status'] ?? 'present';
                        $eid = $row['enrollment_id'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_code']) ?></td>
                            <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                            <td style="white-space:nowrap">
                                <label style="margin-right:15px; color:#10b981; font-weight:bold;"><input type="radio" name="attendance[<?= $eid ?>][status]" value="present" <?= $status === 'present' ? 'checked' : '' ?>> Present</label>
                                <label style="margin-right:15px; color:#f59e0b; font-weight:bold;"><input type="radio" name="attendance[<?= $eid ?>][status]" value="late" <?= $status === 'late' ? 'checked' : '' ?>> Late</label>
                                <label style="color:#ef4444; font-weight:bold;"><input type="radio" name="attendance[<?= $eid ?>][status]" value="absent" <?= $status === 'absent' ? 'checked' : '' ?>> Absent</label>
                            </td>
                            <td><input type="text" name="attendance[<?= $eid ?>][note]" value="<?= htmlspecialchars($row['note'] ?? '') ?>" placeholder="Optional note..." style="width:100%; border:1px solid #ddd; padding:6px; border-radius:4px;"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:20px; text-align:left;">
                <button type="submit" name="mark_bulk_att" class="btn btn-primary">Save All Attendance</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php renderFooter(); ?>