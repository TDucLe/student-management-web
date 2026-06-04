<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = t('nav.assignments');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_asm'])) {
        $pdo->prepare('INSERT INTO assignments (class_id, teacher_id, title, description, deadline, max_score) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([(int) $_POST['class_id'], $teacher_id, trim($_POST['title']), $_POST['desc'] ?? '', $_POST['deadline'], $_POST['max_score']]);
        flash('success', lang() === 'vi' ? 'Đã giao bài tập.' : 'Assignment published.');
    } elseif (isset($_POST['grade_sub'])) {
        $pdo->prepare('UPDATE submissions SET score = ? WHERE id = ?')->execute([$_POST['score'], (int) $_POST['sub_id']]);
        flash('success', lang() === 'vi' ? 'Đã chấm điểm.' : 'Graded.');
    }
    header('Location: assignments.php');
    exit;
}

$classes = $pdo->prepare('SELECT id, class_name FROM classes WHERE teacher_id = ?');
$classes->execute([$teacher_id]);
$classes = $classes->fetchAll();

$subs = $pdo->prepare("
    SELECT sub.id, sub.submitted_at, a.title, s.full_name, sub.file_url, sub.score, a.max_score, cl.class_name
    FROM submissions sub
    JOIN assignments a ON sub.assignment_id = a.id
    JOIN students s ON sub.student_id = s.id
    JOIN classes cl ON a.class_id = cl.id
    WHERE a.teacher_id = ?
    ORDER BY sub.submitted_at DESC
");
$subs->execute([$teacher_id]);
$subs = $subs->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2><?= lang() === 'vi' ? 'Giao bài tập' : 'Publish assignment' ?></h2>
    <form method="POST" data-validate>
        <div class="form-grid">
            <div class="form-group"><label><?= htmlspecialchars(t('select_class')) ?></label>
                <select name="class_id" required><?php foreach ($classes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                <?php endforeach; ?></select>
            </div>
            <div class="form-group"><label><?= lang() === 'vi' ? 'Tiêu đề' : 'Title' ?></label><input name="title" required></div>
            <div class="form-group" style="grid-column:1/-1"><label><?= lang() === 'vi' ? 'Mô tả' : 'Description' ?></label><textarea name="desc"></textarea></div>
            <div class="form-group"><label>Deadline</label><input type="datetime-local" name="deadline" required value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>"></div>
            <div class="form-group"><label><?= lang() === 'vi' ? 'Điểm tối đa' : 'Max score' ?></label><input type="number" step="0.1" name="max_score" required value="10"></div>
        </div>
        <button type="submit" name="add_asm" class="btn btn-primary"><?= htmlspecialchars(t('save')) ?></button>
    </form>
</div>
<div class="card">
    <h2><?= lang() === 'vi' ? 'Bài đã nộp' : 'Submissions' ?></h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th><?= lang() === 'vi' ? 'Bài tập' : 'Assignment' ?></th><th><?= lang() === 'vi' ? 'Lớp' : 'Class' ?></th><th><?= htmlspecialchars(t('student')) ?></th><th><?= lang() === 'vi' ? 'Nộp lúc' : 'Submitted' ?></th><th><?= htmlspecialchars(t('view_file')) ?></th><th><?= lang() === 'vi' ? 'Điểm' : 'Score' ?></th></tr></thead>
            <tbody>
            <?php if (empty($subs)): ?>
            <tr><td colspan="6"><?= lang() === 'vi' ? 'Chưa có bài nộp.' : 'No submissions yet.' ?></td></tr>
            <?php else: foreach ($subs as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['title']) ?></td>
                <td><?= htmlspecialchars($s['class_name']) ?></td>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['submitted_at']) ?></td>
                <td>
                    <?php if ($s['file_url']): ?>
                    <a href="<?= htmlspecialchars(app_path('includes/download.php?submission_id=' . (int) $s['id'])) ?>" class="btn btn-secondary btn-sm" target="_blank"><?= htmlspecialchars(t('download')) ?></a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <form method="POST" style="display:flex;gap:8px;align-items:center">
                        <input type="hidden" name="sub_id" value="<?= (int) $s['id'] ?>">
                        <input type="number" step="0.1" name="score" value="<?= htmlspecialchars((string) ($s['score'] ?? '')) ?>" style="width:80px">
                        <button name="grade_sub" class="btn btn-gold btn-sm"><?= htmlspecialchars(t('save')) ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
