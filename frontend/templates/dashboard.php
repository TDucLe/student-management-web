<?php
/** @var array $stats */
/** @var array $quickLinks */
/** @var array $schedulePreview */
/** @var string $dashboardExtra */
/** @var array $user */

// Icon mapping for quick tiles
$tileIconMap = [
    'Tổng quan' => '🏠', 'Tài khoản' => '👥', 'Sinh viên' => '🎓',
    'Giáo viên' => '👨‍🏫', 'Môn học' => '📚', 'Lớp học' => '🏫',
    'Phòng học' => '🚪', 'Kỳ học' => '📅', 'Điểm danh' => '✅',
    'Bảng điểm' => '📊', 'Bài tập' => '📝', 'Đơn nghỉ' => '📨',
    'Thống kê' => '📈', 'Lịch học' => '🗓️', 'Hồ sơ' => '👤',
    'Dashboard' => '🏠', 'Users' => '👥', 'Students' => '🎓',
    'Teachers' => '👨‍🏫', 'Courses' => '📚', 'Classes' => '🏫',
    'Rooms' => '🚪', 'Semesters' => '📅', 'Attendance' => '✅',
    'Grades' => '📊', 'Assignments' => '📝', 'Leave' => '📨',
    'Stats' => '📈', 'Schedule' => '🗓️', 'Profile' => '👤',
];
?>

<!-- Stats -->
<?php if (!empty($stats)): ?>
<div class="stat-grid">
    <?php foreach ($stats as $stat): ?>
    <div class="stat-card">
        <div class="label"><?= htmlspecialchars($stat['label']) ?></div>
        <div class="value"><?= htmlspecialchars((string) $stat['value']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Quick Access -->
<?php if (!empty($quickLinks)): ?>
<h2 class="section-heading"><?= htmlspecialchars(t('quick_access')) ?></h2>
<div class="quick-grid">
    <?php foreach ($quickLinks as $link):
        $tileIcon = $tileIconMap[$link['title']] ?? '📄';
    ?>
    <a href="<?= htmlspecialchars($link['url']) ?>" class="quick-tile">
        <span class="tile-icon"><?= $tileIcon ?></span>
        <strong><?= htmlspecialchars($link['title']) ?></strong>
        <span><?= htmlspecialchars($link['desc']) ?></span>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?= $dashboardExtra ?? '' ?>

<!-- Schedule Preview -->
<?php if (!empty($schedulePreview)): ?>
<div class="card">
    <h3>🗓️ <?= lang() === 'vi' ? 'Lịch sắp tới' : 'Upcoming schedule' ?></h3>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th><?= lang() === 'vi' ? 'Lớp' : 'Class' ?></th><th><?= lang() === 'vi' ? 'Thứ' : 'Day' ?></th><th><?= lang() === 'vi' ? 'Giờ' : 'Time' ?></th></tr></thead>
            <tbody>
            <?php foreach ($schedulePreview as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['class_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['day_of_week'] ?? '') ?></td>
                <td><?= htmlspecialchars(($row['start_time'] ?? '') . ' – ' . ($row['end_time'] ?? '')) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
