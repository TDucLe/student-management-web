<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('student');

$user = getCurrentUser();
$student_id = getStudentId($pdo, $user['id'], $user['username']);
$pageTitle = 'Assignments';

$uploadDir = APP_ROOT . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $assignment_id = (int) ($_POST['assignment_id'] ?? 0);
    $dl = $pdo->prepare('SELECT deadline FROM assignments WHERE id = ?');
    $dl->execute([$assignment_id]);
    $deadline = $dl->fetchColumn();
    if ($deadline && date('Y-m-d H:i:s') > $deadline) {
        flash('error', 'Deadline has passed.');
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Please upload a file.');
    } else {
        $chk = $pdo->prepare('SELECT id FROM submissions WHERE assignment_id = ? AND student_id = ?');
        $chk->execute([$assignment_id, $student_id]);
        if ($chk->fetch()) {
            flash('error', 'Already submitted.');
        } else {
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['file']['name']));
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $file_name)) {
                $url = 'uploads/' . $file_name;
                $pdo->prepare('INSERT INTO submissions (assignment_id, student_id, file_url) VALUES (?, ?, ?)')
                    ->execute([$assignment_id, $student_id, $url]);
                flash('success', 'Assignment submitted.');
            } else {
                flash('error', 'Upload failed.');
            }
        }
    }
    header('Location: assignments_view.php');
    exit;
}

$assignments = $pdo->prepare("
    SELECT a.*, c.course_name, cl.class_name,
           (SELECT id FROM submissions WHERE assignment_id = a.id AND student_id = ?) AS submitted
    FROM assignments a
    JOIN classes cl ON a.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    JOIN enrollments e ON e.class_id = cl.id
    WHERE e.student_id = ?
    ORDER BY a.deadline
");
$assignments->execute([$student_id, $student_id]);
$assignList = $assignments->fetchAll();

$subm = $pdo->prepare("
    SELECT sub.*, a.title, c.course_name, a.max_score
    FROM submissions sub
    JOIN assignments a ON sub.assignment_id = a.id
    JOIN classes cl ON a.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    WHERE sub.student_id = ?
");
$subm->execute([$student_id]);
$submissions = $subm->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2>Pending assignments</h2>
    <?php foreach ($assignList as $a): if ($a['submitted']) continue; ?>
    <form method="POST" enctype="multipart/form-data" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--border)" data-validate>
        <p><strong><?= htmlspecialchars($a['title']) ?></strong> — <?= htmlspecialchars($a['class_name']) ?></p>
        <p style="color:var(--text-muted);font-size:0.85rem">Deadline: <?= htmlspecialchars($a['deadline']) ?></p>
        <input type="hidden" name="assignment_id" value="<?= (int) $a['id'] ?>">
        <div class="form-group"><label>Upload file</label><input type="file" name="file" required></div>
        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
    </form>
    <?php endforeach; ?>
</div>
<div class="card">
    <h2>My submissions</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Assignment</th><th>Course</th><th>Submitted</th><th>Score</th><th>File</th></tr></thead>
            <tbody>
            <?php foreach ($submissions as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['title']) ?></td>
                <td><?= htmlspecialchars($s['course_name']) ?></td>
                <td><?= htmlspecialchars($s['submitted_at']) ?></td>
                <td><?= $s['score'] !== null ? htmlspecialchars((string) $s['score']) . ' / ' . htmlspecialchars((string) $s['max_score']) : 'Pending' ?></td>
                <td><?php if ($s['file_url']): ?><a href="<?= htmlspecialchars(app_path('includes/download.php?submission_id=' . (int) $s['id'])) ?>" target="_blank"><?= htmlspecialchars(t('download')) ?></a><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
