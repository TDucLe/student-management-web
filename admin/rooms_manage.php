<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = t('nav.rooms');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_room'])) {
        $pdo->prepare('INSERT INTO rooms (room_number, building, capacity) VALUES (?, ?, ?)')
            ->execute([trim($_POST['room_number']), trim($_POST['building'] ?? ''), $_POST['capacity'] ? (int) $_POST['capacity'] : null]);
        flash('success', lang() === 'vi' ? 'Đã thêm phòng.' : 'Room added.');
    } elseif (isset($_POST['delete_id'])) {
        $pdo->prepare('DELETE FROM rooms WHERE id = ?')->execute([(int) $_POST['delete_id']]);
        flash('success', lang() === 'vi' ? 'Đã xóa phòng.' : 'Room deleted.');
    }
    header('Location: rooms_manage.php');
    exit;
}

$rooms = $pdo->query('SELECT * FROM rooms ORDER BY room_number')->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="card">
    <h2><?= lang() === 'vi' ? 'Thêm phòng học' : 'Add room' ?></h2>
    <form method="POST" data-validate>
        <div class="form-grid">
            <div class="form-group"><label><?= lang() === 'vi' ? 'Mã phòng' : 'Room code' ?></label><input name="room_number" required placeholder="A101"></div>
            <div class="form-group"><label><?= lang() === 'vi' ? 'Tòa nhà' : 'Building' ?></label><input name="building"></div>
            <div class="form-group"><label><?= lang() === 'vi' ? 'Sức chứa' : 'Capacity' ?></label><input type="number" name="capacity" min="1"></div>
        </div>
        <button type="submit" name="add_room" class="btn btn-primary"><?= htmlspecialchars(t('save')) ?></button>
    </form>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th><?= lang() === 'vi' ? 'Mã phòng' : 'Code' ?></th><th><?= lang() === 'vi' ? 'Tòa' : 'Building' ?></th><th><?= lang() === 'vi' ? 'Sức chứa' : 'Capacity' ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rooms as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['room_number']) ?></strong></td>
                <td><?= htmlspecialchars($r['building'] ?? '—') ?></td>
                <td><?= htmlspecialchars((string) ($r['capacity'] ?? '—')) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete?');">
                        <input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>">
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
