<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('student');

$user = getCurrentUser();
$student_id = getStudentId($pdo, $user['id'], $user['username']);
$pageTitle = 'Leave Request';

$cls = $pdo->prepare("
    SELECT cl.id, cl.class_name, c.course_name
    FROM enrollments e
    JOIN classes cl ON e.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    WHERE e.student_id = ?
");
$cls->execute([$student_id]);
$myClasses = $cls->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $class_id = (int) ($_POST['class_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $leave_date = $_POST['leave_date'] ?? '';
    if ($reason === '') {
        flash('error', 'Reason is required.');
    } else {
        $chk = $pdo->prepare('SELECT id FROM enrollments WHERE student_id = ? AND class_id = ?');
        $chk->execute([$student_id, $class_id]);
        if (!$chk->fetch()) {
            flash('error', 'Invalid class selection.');
        } else {
            $pdo->prepare("INSERT INTO leave_requests (student_id, class_id, reason, leave_date, status) VALUES (?, ?, ?, ?, 'pending')")
                ->execute([$student_id, $class_id, $reason, $leave_date]);
            flash('success', 'Leave request submitted.');
            header('Location: leave_status.php');
            exit;
        }
    }
}

renderHeader($pageTitle, $user);
?>
<div class="card">
    <form method="POST" data-validate>
        <div class="form-group">
            <label>Class</label>
            <select name="class_id" required>
                <?php foreach ($myClasses as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['class_name'] . ' (' . $c['course_name'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Reason</label><textarea name="reason" required></textarea></div>
        <div class="form-group"><label>Leave date</label><input type="date" name="leave_date" required></div>
        <button type="submit" name="submit" class="btn btn-primary">Submit request</button>
        <a href="leave_status.php" class="btn btn-secondary" style="margin-left:8px">View status</a>
    </form>
</div>
<?php renderFooter(); ?>
