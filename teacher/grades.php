<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = t('nav.grades');

$classes = $pdo->prepare('SELECT cl.id, cl.class_name, c.course_name FROM classes cl JOIN courses c ON cl.course_id = c.id WHERE cl.teacher_id = ? ORDER BY cl.class_name');
$classes->execute([$teacher_id]);
$classList = $classes->fetchAll();

$class_id = (int) ($_GET['class_id'] ?? ($classList[0]['id'] ?? 0));
if ($class_id && !verifyTeacherOwnsClass($pdo, $teacher_id, $class_id)) {
    $class_id = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_class_grades'])) {
    $class_id = (int) $_POST['class_id'];
    if (verifyTeacherOwnsClass($pdo, $teacher_id, $class_id)) {
        // Get class name for notification
        $cnStmt = $pdo->prepare('SELECT class_name FROM classes WHERE id = ?');
        $cnStmt->execute([$class_id]);
        $className = $cnStmt->fetchColumn() ?: '';

        foreach ($_POST['regular'] ?? [] as $enrollment_id => $regular) {
            $eid = (int) $enrollment_id;
            $reg = $regular !== '' ? (float) $regular : null;
            $mid = ($_POST['midterm'][$eid] ?? '') !== '' ? (float) $_POST['midterm'][$eid] : null;
            $fin = ($_POST['final'][$eid] ?? '') !== '' ? (float) $_POST['final'][$eid] : null;
            if ($reg !== null || $mid !== null || $fin !== null) {
                saveStudentGrade($pdo, $eid, $reg, $mid, $fin);

                // Notify the student
                $stuStmt = $pdo->prepare('SELECT s.user_id FROM enrollments e JOIN students s ON e.student_id = s.id WHERE e.id = ?');
                $stuStmt->execute([$eid]);
                $stuRow = $stuStmt->fetch();
                if ($stuRow && $stuRow['user_id']) {
                    sendNotification($pdo, (int) $stuRow['user_id'], 'exam',
                        (lang() === 'vi' ? "Điểm lớp $className đã được cập nhật" : "Grades updated for class $className")
                    );
                }
            }
        }
        flash('success', lang() === 'vi' ? 'Đã lưu điểm cả lớp.' : 'Grades saved.');
    }
    header('Location: grades.php?class_id=' . $class_id);
    exit;
}

$rows = [];
if ($class_id) {
    $stmt = $pdo->prepare("
        SELECT e.id AS enrollment_id, s.student_code, s.full_name,
               g.regular_score, g.midterm_score, g.final_score, g.total_score, g.letter_grade, g.gpa
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        LEFT JOIN student_grades g ON g.enrollment_id = e.id
        WHERE e.class_id = ? AND e.status = 'active' AND s.deleted_at IS NULL
        ORDER BY s.full_name
    ");
    $stmt->execute([$class_id]);
    $rows = $stmt->fetchAll();
}

renderHeader($pageTitle, $user);
?>
<p class="alert alert-info"><?= htmlspecialchars(t('fail_note')) ?></p>
<div class="card class-picker">
    <form method="GET">
        <div class="form-group">
            <label><?= htmlspecialchars(t('select_class')) ?></label>
            <select name="class_id" onchange="this.form.submit()">
                <?php foreach ($classList as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $class_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['class_name'] . ' — ' . $c['course_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($class_id && !empty($rows)): ?>
<form method="POST">
    <input type="hidden" name="class_id" value="<?= $class_id ?>">
    <div class="card">
        <h2><?= htmlspecialchars(t('all_students')) ?></h2>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(t('student')) ?></th>
                        <th><?= htmlspecialchars(t('regular')) ?></th>
                        <th><?= htmlspecialchars(t('midterm')) ?></th>
                        <th><?= htmlspecialchars(t('final')) ?></th>
                        <th><?= htmlspecialchars(t('total')) ?></th>
                        <th><?= htmlspecialchars(t('letter')) ?></th>
                        <th><?= htmlspecialchars(t('gpa')) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $eid = (int) $r['enrollment_id'];
                    $letter = $r['letter_grade'] ?? '';
                    $letterClass = ($letter === 'F' || ($r['gpa'] !== null && (float) $r['gpa'] < 4)) ? 'grade-f' : 'grade-pass';
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['full_name']) ?></strong><br><small><?= htmlspecialchars($r['student_code']) ?></small></td>
                    <td><input type="number" step="0.01" min="0" max="10" name="regular[<?= $eid ?>]" value="<?= $r['regular_score'] !== null ? htmlspecialchars($r['regular_score']) : '' ?>"></td>
                    <td><input type="number" step="0.01" min="0" max="10" name="midterm[<?= $eid ?>]" value="<?= $r['midterm_score'] !== null ? htmlspecialchars($r['midterm_score']) : '' ?>"></td>
                    <td><input type="number" step="0.01" min="0" max="10" name="final[<?= $eid ?>]" value="<?= $r['final_score'] !== null ? htmlspecialchars($r['final_score']) : '' ?>"></td>
                    <td class="<?= $letterClass ?>"><?= $r['total_score'] !== null ? htmlspecialchars($r['total_score']) : '—' ?></td>
                    <td class="<?= $letterClass ?>"><?= htmlspecialchars($letter ?: '—') ?></td>
                    <td class="<?= $letterClass ?>"><?= $r['gpa'] !== null ? htmlspecialchars($r['gpa']) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" name="save_class_grades" class="btn btn-gold" style="margin-top:20px"><?= htmlspecialchars(t('save_grades')) ?></button>
    </div>
</form>
<?php elseif ($class_id): ?>
<div class="alert alert-info"><?= lang() === 'vi' ? 'Chưa có sinh viên trong lớp.' : 'No students in class.' ?></div>
<?php endif;
renderFooter();
