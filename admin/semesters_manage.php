<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = t('nav.semesters');

$termLabels = [
    'semester1' => t('term.semester1'),
    'semester2' => t('term.semester2'),
    'summer' => t('term.summer'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_semester'])) {
        $pdo->prepare('INSERT INTO semesters (name, term_type, school_year, start_date, end_date) VALUES (?, ?, ?, ?, ?)')
            ->execute([
                trim($_POST['name']),
                $_POST['term_type'],
                trim($_POST['school_year']),
                $_POST['start_date'],
                $_POST['end_date'],
            ]);
        flash('success', lang() === 'vi' ? 'Đã thêm kỳ học.' : 'Semester added.');
    } elseif (isset($_POST['delete_id'])) {
        $pdo->prepare('DELETE FROM semesters WHERE id = ?')->execute([(int) $_POST['delete_id']]);
        flash('success', lang() === 'vi' ? 'Đã xóa kỳ.' : 'Deleted.');
    }
    header('Location: semesters_manage.php');
    exit;
}

$semesters = $pdo->query('SELECT * FROM semesters ORDER BY start_date DESC')->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2><?= lang() === 'vi' ? 'Thêm kỳ học (kỳ 1, kỳ 2, kỳ hè)' : 'Add semester' ?></h2>
    <form method="POST" data-validate>
        <div class="form-grid">
            <div class="form-group"><label><?= lang() === 'vi' ? 'Tên hiển thị' : 'Display name' ?></label><input name="name" required placeholder="HK1 2025-2026"></div>
            <div class="form-group"><label><?= lang() === 'vi' ? 'Loại kỳ' : 'Term type' ?></label>
                <select name="term_type" required>
                    <?php foreach ($termLabels as $k => $lbl): ?>
                    <option value="<?= $k ?>"><?= htmlspecialchars($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label><?= lang() === 'vi' ? 'Năm học' : 'School year' ?></label><input name="school_year" required value="2025-2026"></div>
            <div class="form-group"><label><?= lang() === 'vi' ? 'Bắt đầu' : 'Start' ?></label><input type="date" name="start_date" required></div>
            <div class="form-group"><label><?= lang() === 'vi' ? 'Kết thúc' : 'End' ?></label><input type="date" name="end_date" required></div>
        </div>
        <button type="submit" name="add_semester" class="btn btn-primary"><?= htmlspecialchars(t('save')) ?></button>
    </form>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Name</th><th><?= lang() === 'vi' ? 'Loại' : 'Type' ?></th><th><?= lang() === 'vi' ? 'Năm học' : 'Year' ?></th><th>Start</th><th>End</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($semesters as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($termLabels[$s['term_type'] ?? 'semester1'] ?? $s['term_type']) ?></td>
                <td><?= htmlspecialchars($s['school_year'] ?? '') ?></td>
                <td><?= htmlspecialchars($s['start_date']) ?></td>
                <td><?= htmlspecialchars($s['end_date']) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete?');">
                        <input type="hidden" name="delete_id" value="<?= (int) $s['id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
