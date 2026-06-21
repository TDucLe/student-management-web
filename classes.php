<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = t('nav.classes');

$stmt = $pdo->prepare("
    SELECT cl.id, cl.class_name, c.course_name, s.name AS semester_name, r.room_number,
           (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id AND e.status = 'active') AS student_count
    FROM classes cl
    JOIN courses c ON cl.course_id = c.id
    LEFT JOIN semesters s ON cl.semester_id = s.id
    LEFT JOIN rooms r ON cl.room_id = r.id
    WHERE cl.teacher_id = ? ORDER BY cl.class_name
");
$stmt->execute([$teacher_id]);
$classes_data = $stmt->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="quick-grid">
    <?php foreach ($classes_data as $row): $cid = (int) $row['id']; ?>
    <div class="quick-tile" style="cursor:default">
        <span class="tile-icon">🏫</span>
        <strong><?= htmlspecialchars($row['class_name']) ?></strong>
        <span><?= htmlspecialchars($row['course_name']) ?> · <?= (int) $row['student_count'] ?> <?= htmlspecialchars(t('student')) ?></span>
        <?php if (!empty($row['semester_name'])): ?>
        <span style="font-size:0.9rem;color:var(--text-muted)"><?= htmlspecialchars($row['semester_name']) ?><?= !empty($row['room_number']) ? ' · ' . htmlspecialchars($row['room_number']) : '' ?></span>
        <?php endif; ?>
        <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px">
            <a href="class_students.php?class_id=<?= $cid ?>" class="btn btn-secondary btn-sm"><?= lang() === 'vi' ? 'Danh sách SV' : 'Students' ?></a>
            <a href="attendance.php?class_id=<?= $cid ?>" class="btn btn-gold btn-sm"><?= htmlspecialchars(t('nav.attendance')) ?></a>
            <a href="grades.php?class_id=<?= $cid ?>" class="btn btn-primary btn-sm"><?= htmlspecialchars(t('nav.grades')) ?></a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($classes_data)): ?>
<div class="card" style="text-align:center;padding:40px;">
    <p style="font-size:1.2rem;color:var(--text-muted)">
        <?= lang() === 'vi' ? '📭 Bạn chưa được phân công lớp nào. Liên hệ Admin để được phân công.' : '📭 No classes assigned. Contact Admin to get assigned.' ?>
    </p>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
