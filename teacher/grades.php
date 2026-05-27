<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = 'Class Gradebook';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_exam'])) {
        $pdo->prepare('INSERT INTO exams (class_id, exam_name, exam_date, max_score) VALUES (?, ?, ?, ?)')
            ->execute([(int) $_POST['class_id'], trim($_POST['name']), $_POST['date'], $_POST['max']]);
        flash('success', 'Exam created.');
    } elseif (isset($_POST['save_bulk_grades'])) {
        $stmt = $pdo->prepare('INSERT INTO exam_results (exam_id, student_id, score) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE score=VALUES(score)');
        
        if (isset($_POST['grades']) && is_array($_POST['grades'])) {
            foreach ($_POST['grades'] as $exam_id => $students) {
                foreach ($students as $student_id => $score) {
                    if ($score !== '') {
                        $stmt->execute([(int)$exam_id, (int)$student_id, $score]);
                    }
                }
            }
        }
        flash('success', 'Gradebook updated successfully!');
        header("Location: grades.php?class_id=" . (int)$_POST['class_id']);
        exit;
    }
    header('Location: grades.php');
    exit;
}

$classes = $pdo->prepare('SELECT id, class_name FROM classes WHERE teacher_id = ?');
$classes->execute([$teacher_id]);
$classes = $classes->fetchAll();

// Lấy dữ liệu cho Gradebook Matrix
$filter_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$class_exams = [];
$class_students = [];
$results_map = [];

if ($filter_class > 0) {
    $ex_stmt = $pdo->prepare("SELECT id, exam_name, max_score FROM exams WHERE class_id = ? ORDER BY exam_date ASC");
    $ex_stmt->execute([$filter_class]);
    $class_exams = $ex_stmt->fetchAll();

    $stu_stmt = $pdo->prepare("
        SELECT s.id, s.student_code, s.full_name 
        FROM enrollments e 
        JOIN students s ON e.student_id = s.id 
        WHERE e.class_id = ? AND e.status = 'active' 
        ORDER BY s.full_name ASC
    ");
    $stu_stmt->execute([$filter_class]);
    $class_students = $stu_stmt->fetchAll();

    $res_stmt = $pdo->prepare("
        SELECT er.exam_id, er.student_id, er.score 
        FROM exam_results er 
        JOIN exams e ON er.exam_id = e.id 
        WHERE e.class_id = ?
    ");
    $res_stmt->execute([$filter_class]);
    foreach ($res_stmt->fetchAll() as $row) {
        $results_map[$row['student_id']][$row['exam_id']] = $row['score'];
    }
}

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2>1. Create Exam</h2>
    <form method="POST" data-validate>
        <div class="form-grid">
            <div class="form-group"><label>Class</label>
                <select name="class_id" required><option value="">— Select —</option><?php foreach ($classes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                <?php endforeach; ?></select>
            </div>
            <div class="form-group"><label>Exam name (e.g. Midterm, Quiz 1)</label><input name="name" required></div>
            <div class="form-group"><label>Date</label><input type="date" name="date" required value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>Max score</label><input type="number" step="0.1" name="max" required value="10"></div>
        </div>
        <button type="submit" name="add_exam" class="btn btn-primary">Create exam</button>
    </form>
</div>

<div class="card">
    <h2>2. Class Gradebook</h2>
    <form method="GET" class="form-grid" style="margin-bottom: 20px; align-items: end;">
        <div class="form-group"><label>Select Class to Enter/View Grades</label>
            <select name="class_id" required onchange="this.form.submit()">
                <option value="">— Select Class —</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $filter_class === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <noscript><button type="submit" class="btn btn-secondary">Load Gradebook</button></noscript>
    </form>

    <?php if ($filter_class > 0): ?>
        <?php if (empty($class_students)): ?>
            <div class="alert alert-info">No active students found in this class.</div>
        <?php elseif (empty($class_exams)): ?>
            <div class="alert alert-info">No exams created for this class yet. Create one above!</div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="class_id" value="<?= $filter_class ?>">
                <div class="table-wrap" style="overflow-x:auto;">
                    <table class="data-table">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th>Code</th>
                                <th>Student Name</th>
                                <?php foreach ($class_exams as $e): ?>
                                    <th style="text-align:center; min-width:100px;">
                                        <?= htmlspecialchars($e['exam_name']) ?><br>
                                        <small style="font-weight:normal; color:#666;">(Max: <?= (float)$e['max_score'] ?>)</small>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($class_students as $stu): ?>
                                <tr>
                                    <td><?= htmlspecialchars($stu['student_code']) ?></td>
                                    <td><strong><?= htmlspecialchars($stu['full_name']) ?></strong></td>
                                    <?php foreach ($class_exams as $e): 
                                        $score = $results_map[$stu['id']][$e['id']] ?? '';
                                    ?>
                                        <td style="text-align:center;">
                                            <input type="number" step="0.01" max="<?= (float)$e['max_score'] ?>" min="0" 
                                                   name="grades[<?= $e['id'] ?>][<?= $stu['id'] ?>]" 
                                                   value="<?= htmlspecialchars((string)$score) ?>" 
                                                   style="width:80px; text-align:center;" class="form-control">
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:20px; text-align:left;">
                    <button type="submit" name="save_bulk_grades" class="btn btn-primary">Save All Grades</button>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php renderFooter(); ?>