<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/schedule_ui.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = 'Teaching Schedule';

$stmt = $pdo->prepare("
    SELECT sch.day_of_week, sch.start_time, sch.end_time, cl.class_name, c.course_name,
           COALESCE(r.room_number, cr.room_number) AS room_number
    FROM schedules sch
    JOIN classes cl ON sch.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    LEFT JOIN rooms r ON sch.room_id = r.id
    LEFT JOIN rooms cr ON cl.room_id = cr.id
    WHERE cl.teacher_id = ?
");
$stmt->execute([$teacher_id]);
$rows = $stmt->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="alert alert-info">Lịch dạy do Admin xếp. Liên hệ Admin nếu cần đổi lịch.</div>
<div class="card">
    <h2>Weekly teaching timetable</h2>
    <?php renderWeeklyTimetable($rows, 'Chưa có lịch dạy. Admin cần xếp lịch tại Class → Manage.'); ?>
</div>
<div class="card">
    <h2>Detail list</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Class</th><th>Course</th><th>Day</th><th>Time</th><th>Room</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="5">No slots.</td></tr>
            <?php else: foreach ($rows as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['class_name']) ?></td>
                <td><?= htmlspecialchars($row['course_name']) ?></td>
                <td><?= htmlspecialchars($row['day_of_week']) ?></td>
                <td><?= date('H:i', strtotime($row['start_time'])) ?> – <?= date('H:i', strtotime($row['end_time'])) ?></td>
                <td><?= htmlspecialchars($row['room_number'] ?? '—') ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
