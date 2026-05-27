<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = 'Assignments';

// Tự động thêm cột file_url vào database nếu Đạt đen chưa tạo
try {
    $pdo->exec("ALTER TABLE assignments ADD COLUMN IF NOT EXISTS file_url VARCHAR(255) NULL AFTER description");
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_asm'])) {
        $file_url = null;
        if (isset($_FILES['asm_file']) && $_FILES['asm_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__) . '/uploads/assignments/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", basename($_FILES['asm_file']['name']));
            if (move_uploaded_file($_FILES['asm_file']['tmp_name'], $uploadDir . $filename)) {
                $file_url = 'uploads/assignments/' . $filename;
            }
        }

        $pdo->prepare('INSERT INTO assignments (class_id, teacher_id, title, description, file_url, deadline, max_score) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([(int) $_POST['class_id'], $teacher_id, trim($_POST['title']), trim($_POST['desc'] ?? ''), $file_url, $_POST['deadline'], $_POST['max_score']]);
        flash('success', 'Assignment published successfully.');
    } elseif (isset($_POST['grade_sub'])) {
        $pdo->prepare('UPDATE submissions SET score = ? WHERE id = ?')->execute([$_POST['score'], (int) $_POST['sub_id']]);
        flash('success', 'Student submission graded.');
    }
    header('Location: assignments.php');
    exit;
}

$classes = $pdo->prepare('SELECT id, class_name FROM classes WHERE teacher_id = ?');
$classes->execute([$teacher_id]);
$classes = $classes->fetchAll();

// Lấy danh sách bài tập đã giao
$assignments = $pdo->prepare('SELECT a.*, c.class_name FROM assignments a JOIN classes c ON a.class_id = c.id WHERE a.teacher_id = ? ORDER BY a.created_at DESC');
$assignments->execute([$teacher_id]);
$assignments = $assignments->fetchAll();

$subs = $pdo->prepare("
    SELECT sub.id, a.title, s.full_name, sub.file_url, sub.score, a.max_score
    FROM submissions sub
    JOIN assignments a ON sub.assignment_id = a.id
    JOIN students s ON sub.student_id = s.id
    WHERE a.teacher_id = ?
");
$subs->execute([$teacher_id]);
$subs = $subs->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2>Publish assignment (Giao bài tập mới)</h2>
    <form method="POST" enctype="multipart/form-data" data-validate>
        <div class="form-grid">
            <div class="form-group"><label>Class</label>
                <select name="class_id" required><?php foreach ($classes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                <?php endforeach; ?></select>
            </div>
            <div class="form-group"><label>Title</label><input name="title" required></div>
            <div class="form-group" style="grid-column:1/-1"><label>Description</label><textarea name="desc" rows="3"></textarea></div>
            <div class="form-group" style="grid-column:1/-1"><label>Assignment File (File Đề bài - Optional)</label><input type="file" name="asm_file" style="padding:6px; border:1px solid #ccc; width:100%; border-radius:4px" accept=".pdf,.doc,.docx,.zip,.rar"></div>
            <div class="form-group"><label>Deadline</label><input type="datetime-local" name="deadline" required value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>"></div>
            <div class="form-group"><label>Max score</label><input type="number" step="0.1" name="max_score" required value="10"></div>
        </div>
        <button type="submit" name="add_asm" class="btn btn-primary">Publish</button>
    </form>
</div>

<div class="card">
    <h2>Assignments List (Danh sách Đề bài đã giao)</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Class</th><th>Title</th><th>Deadline</th><th>Attached File</th></tr></thead>
            <tbody>
            <?php foreach ($assignments as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['class_name']) ?></td>
                <td><?= htmlspecialchars($a['title']) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($a['deadline']))) ?></td>
                <td><?php if (!empty($a['file_url'])): ?><a href="<?= htmlspecialchars(app_path($a['file_url'])) ?>" target="_blank" class="btn btn-secondary btn-sm">Download</a><?php else: ?>—<?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Submissions & Grading (Chấm bài)</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Assignment</th><th>Student</th><th>File Nộp Bài</th><th>Grade</th></tr></thead>
            <tbody>
            <?php foreach ($subs as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['title']) ?></td>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?php if ($s['file_url']): ?><a href="<?= htmlspecialchars(app_path($s['file_url'])) ?>" target="_blank" class="btn btn-secondary btn-sm">View File</a><?php else: ?>—<?php endif; ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:8px;align-items:center">
                        <input type="hidden" name="sub_id" value="<?= (int) $s['id'] ?>">
                        <input type="number" step="0.1" name="score" value="<?= htmlspecialchars((string) $s['score']) ?>" style="width:80px" required>
                        <button name="grade_sub" class="btn btn-primary btn-sm">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
