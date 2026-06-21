<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/schedule_ui.php';
requireRole('teacher');

$user = getCurrentUser();
$teacher_id = getTeacherId($pdo, $user['id'], $user['username']);
$pageTitle = lang() === 'vi' ? 'Lịch dạy' : 'Teaching Schedule';

// View mode: day / week / month / semester
$view = $_GET['view'] ?? 'week';
if (!in_array($view, ['day', 'week', 'month', 'semester'])) $view = 'week';

// Date navigation
$curDate = $_GET['date'] ?? date('Y-m-d');
$curMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m', strtotime($curDate));
$curYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y', strtotime($curDate));

// Current semester filter
$selectedSem = isset($_GET['semester_id']) ? (int) $_GET['semester_id'] : 0;

// Fetch all semesters teacher is involved in
$semStmt = $pdo->prepare("
    SELECT DISTINCT s.id, s.name, s.school_year, s.start_date, s.end_date
    FROM semesters s JOIN classes c ON c.semester_id = s.id
    WHERE c.teacher_id = ? ORDER BY s.start_date DESC
");
$semStmt->execute([$teacher_id]);
$semesterList = $semStmt->fetchAll();

if ($selectedSem === 0 && !empty($semesterList)) $selectedSem = (int) $semesterList[0]['id'];

// Fetch schedules
$query = "
    SELECT sch.day_of_week, sch.start_time, sch.end_time, cl.class_name, c.course_name,
           COALESCE(r.room_number, cr.room_number) AS room_number,
           s.name AS semester_name, cl.id AS class_id
    FROM schedules sch
    JOIN classes cl ON sch.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    LEFT JOIN rooms r ON sch.room_id = r.id
    LEFT JOIN rooms cr ON cl.room_id = cr.id
    LEFT JOIN semesters s ON cl.semester_id = s.id
    WHERE cl.teacher_id = ?
";
$params = [$teacher_id];
if ($selectedSem > 0 && $view === 'semester') {
    $query .= " AND cl.semester_id = ?";
    $params[] = $selectedSem;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Day of week map
$dayMap = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];

// Get unique classes for filter
$classNames = array_unique(array_column($rows, 'class_name'));
sort($classNames);

// Get semester date limits
$semStart = null; $semEnd = null; $semInfo = '';
foreach ($semesterList as $sem) {
    if ((int) $sem['id'] === $selectedSem) {
        $semStart = $sem['start_date'];
        $semEnd = $sem['end_date'];
        $semInfo = $sem['name'] . ' (' . date('d/m/Y', strtotime($semStart)) . ' → ' . date('d/m/Y', strtotime($semEnd)) . ')';
        break;
    }
}

// Clamp month to semester range
if ($semStart && $semEnd) {
    $smM = (int) date('m', strtotime($semStart)); $smY = (int) date('Y', strtotime($semStart));
    $emM = (int) date('m', strtotime($semEnd)); $emY = (int) date('Y', strtotime($semEnd));
    if ($curYear < $smY || ($curYear === $smY && $curMonth < $smM)) { $curMonth = $smM; $curYear = $smY; }
    if ($curYear > $emY || ($curYear === $emY && $curMonth > $emM)) { $curMonth = $emM; $curYear = $emY; }
}

// Calendar helpers for month view
$firstDay = mktime(0, 0, 0, $curMonth, 1, $curYear);
$daysInMonth = (int) date('t', $firstDay);
$startDow = (int) date('N', $firstDay);
$prevMonth = $curMonth - 1; $prevYear = $curYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $curMonth + 1; $nextYear = $curYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Disable nav at semester boundaries
$canPrev = true; $canNext = true;
if ($semStart) {
    $smM2 = (int) date('m', strtotime($semStart)); $smY2 = (int) date('Y', strtotime($semStart));
    if ($curYear === $smY2 && $curMonth === $smM2) $canPrev = false;
}
if ($semEnd) {
    $emM2 = (int) date('m', strtotime($semEnd)); $emY2 = (int) date('Y', strtotime($semEnd));
    if ($curYear === $emY2 && $curMonth === $emM2) $canNext = false;
}

$monthNames = ['','Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];
$monthLabel = (lang() === 'vi' ? $monthNames[$curMonth] : date('F', $firstDay)) . ' ' . $curYear;

// Day view helpers
$dayOfWeekToday = date('l', strtotime($curDate));
$dayInRange = true;
if ($semStart && $curDate < $semStart) $dayInRange = false;
if ($semEnd && $curDate > $semEnd) $dayInRange = false;
$dayRows = $dayInRange ? array_filter($rows, function($r) use ($dayOfWeekToday) {
    return $r['day_of_week'] === $dayOfWeekToday;
}) : [];
sortScheduleRows($dayRows);
$prevDay = date('Y-m-d', strtotime($curDate . ' -1 day'));
$nextDay = date('Y-m-d', strtotime($curDate . ' +1 day'));
// Clamp day navigation to semester
if ($semStart && $prevDay < $semStart) $prevDay = $semStart;
if ($semEnd && $nextDay > $semEnd) $nextDay = $semEnd;

// Week view: current week dates
$weekStart = date('Y-m-d', strtotime('monday this week', strtotime($curDate)));
$weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($curDate)));
$prevWeek = date('Y-m-d', strtotime($weekStart . ' -7 days'));
$nextWeek = date('Y-m-d', strtotime($weekStart . ' +7 days'));

// Calendar events
$calEvents = [];
foreach ($rows as $r) {
    $dow = $dayMap[$r['day_of_week']] ?? 0;
    if ($dow === 0) continue;
    $calEvents[] = ['dow' => $dow, 'label' => $r['class_name'], 'time' => date('H:i', strtotime($r['start_time'])) . '–' . date('H:i', strtotime($r['end_time'])), 'room' => $r['room_number'] ?? ''];
}

$baseUrl = 'schedule.php?semester_id=' . $selectedSem;

renderHeader($pageTitle, $user);
?>

<div class="alert alert-info" style="margin-bottom:16px;">
    📌 <?= lang() === 'vi' ? 'Lịch dạy do Admin xếp. Liên hệ Admin nếu cần thay đổi.' : 'Schedule managed by Admin.' ?>
</div>

<!-- View mode tabs -->
<div class="view-tabs" style="margin-bottom:20px;">
    <?php
    $views = ['day' => '📆 ' . (lang() === 'vi' ? 'Ngày' : 'Day'), 'week' => '🗓️ ' . (lang() === 'vi' ? 'Tuần' : 'Week'), 'month' => '📅 ' . (lang() === 'vi' ? 'Tháng' : 'Month'), 'semester' => '🎓 ' . (lang() === 'vi' ? 'Kỳ' : 'Semester')];
    foreach ($views as $vk => $vl): ?>
    <a href="<?= $baseUrl ?>&view=<?= $vk ?>&date=<?= $curDate ?>&month=<?= $curMonth ?>&year=<?= $curYear ?>" class="view-tab <?= $view === $vk ? 'active' : '' ?>"><?= $vl ?></a>
    <?php endforeach; ?>
</div>

<?php if ($view === 'day'): ?>
<!-- ═══ DAY VIEW ═══ -->
<div class="card">
    <div class="cal-header">
        <a href="<?= $baseUrl ?>&view=day&date=<?= $prevDay ?>" class="btn btn-ghost btn-sm">◀</a>
        <h2 style="margin:0;flex:1;text-align:center"><?= date('d/m/Y', strtotime($curDate)) ?> — <?= $dayOfWeekToday ?></h2>
        <a href="<?= $baseUrl ?>&view=day&date=<?= $nextDay ?>" class="btn btn-ghost btn-sm">▶</a>
    </div>
    <?php if (!$dayInRange): ?>
    <p style="text-align:center;padding:30px;color:var(--text-muted);font-size:1.1rem">
        ⚠️ <?= lang() === 'vi' ? 'Ngày này nằm ngoài kỳ học' : 'This date is outside the semester' ?>
        <?php if ($semInfo): ?><br><small><?= htmlspecialchars($semInfo) ?></small><?php endif; ?>
    </p>
    <?php elseif (empty($dayRows)): ?>
    <p style="text-align:center;padding:30px;color:var(--text-muted);font-size:1.1rem">
        <?= lang() === 'vi' ? '😊 Không có tiết dạy hôm nay' : '😊 No classes today' ?>
    </p>
    <?php else: ?>
    <div class="day-slots">
        <?php foreach ($dayRows as $r): ?>
        <div class="day-slot-item">
            <div class="day-slot-time"><?= date('H:i', strtotime($r['start_time'])) ?> – <?= date('H:i', strtotime($r['end_time'])) ?></div>
            <div class="day-slot-info">
                <strong><?= htmlspecialchars($r['class_name']) ?></strong>
                <span><?= htmlspecialchars($r['course_name']) ?></span>
                <?php if (!empty($r['room_number'])): ?><span class="slot-tag">📍 <?= htmlspecialchars($r['room_number']) ?></span><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php elseif ($view === 'week'): ?>
<!-- ═══ WEEK VIEW ═══ -->
<div class="card">
    <div class="cal-header">
        <a href="<?= $baseUrl ?>&view=week&date=<?= $prevWeek ?>" class="btn btn-ghost btn-sm">◀</a>
        <h2 style="margin:0;flex:1;text-align:center"><?= date('d/m', strtotime($weekStart)) ?> – <?= date('d/m/Y', strtotime($weekEnd)) ?></h2>
        <a href="<?= $baseUrl ?>&view=week&date=<?= $nextWeek ?>" class="btn btn-ghost btn-sm">▶</a>
    </div>
    <?php renderWeeklyTimetable($rows, lang() === 'vi' ? 'Chưa có lịch dạy.' : 'No teaching schedule.'); ?>
</div>

<?php elseif ($view === 'month'): ?>
<!-- ═══ MONTH VIEW ═══ -->
<div class="card">
    <?php if ($semInfo): ?>
    <div class="alert alert-info" style="margin-bottom:12px;font-size:0.95rem;">📅 <?= htmlspecialchars($semInfo) ?></div>
    <?php endif; ?>
    <div class="cal-header">
        <?php if ($canPrev): ?>
        <a href="<?= $baseUrl ?>&view=month&month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-ghost btn-sm">◀</a>
        <?php else: ?>
        <span class="btn btn-ghost btn-sm" style="opacity:0.3;cursor:not-allowed">◀</span>
        <?php endif; ?>
        <h2 style="margin:0;flex:1;text-align:center"><?= htmlspecialchars($monthLabel) ?></h2>
        <?php if ($canNext): ?>
        <a href="<?= $baseUrl ?>&view=month&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-ghost btn-sm">▶</a>
        <?php else: ?>
        <span class="btn btn-ghost btn-sm" style="opacity:0.3;cursor:not-allowed">▶</span>
        <?php endif; ?>
    </div>
    <table class="cal-table">
        <thead><tr><th>T2</th><th>T3</th><th>T4</th><th>T5</th><th>T6</th><th>T7</th><th>CN</th></tr></thead>
        <tbody>
        <?php
        $day = 1; $today = date('Y-m-d');
        for ($week = 0; $week < 6 && $day <= $daysInMonth; $week++): ?>
        <tr>
            <?php for ($d = 1; $d <= 7; $d++):
                if (($week === 0 && $d < $startDow) || $day > $daysInMonth): ?>
                <td class="cal-empty"></td>
            <?php else:
                $dateStr = sprintf('%04d-%02d-%02d', $curYear, $curMonth, $day);
                $isToday = ($dateStr === $today);
                $inRange = true;
                if ($semStart && $dateStr < $semStart) $inRange = false;
                if ($semEnd && $dateStr > $semEnd) $inRange = false;
                $dayEvts = $inRange ? array_filter($calEvents, function($e) use ($d) { return $e['dow'] === $d; }) : [];
            ?>
                <td class="cal-day <?= $isToday ? 'cal-today' : '' ?> <?= !empty($dayEvts) ? 'cal-has-event' : '' ?> <?= !$inRange ? 'cal-out-of-range' : '' ?>">
                    <span class="cal-num"><?= $day ?></span>
                    <?php foreach (array_slice($dayEvts, 0, 2) as $ev): ?>
                    <div class="cal-event" title="<?= htmlspecialchars($ev['label'] . ' ' . $ev['time']) ?>"><?= htmlspecialchars($ev['label']) ?></div>
                    <?php endforeach; ?>
                    <?php if (count($dayEvts) > 2): ?><div class="cal-more">+<?= count($dayEvts) - 2 ?></div><?php endif; ?>
                </td>
            <?php $day++; endif; endfor; ?>
        </tr>
        <?php endfor; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
<!-- ═══ SEMESTER VIEW ═══ -->
<div class="schedule-layout">
    <div class="schedule-sidebar">
        <div class="card">
            <h3>🎓 <?= lang() === 'vi' ? 'Chọn kỳ' : 'Semester' ?></h3>
            <div class="semester-list">
                <?php foreach ($semesterList as $sem): ?>
                <a href="?view=semester&semester_id=<?= (int) $sem['id'] ?>" class="semester-item <?= (int) $sem['id'] === $selectedSem ? 'active' : '' ?>">
                    <strong><?= htmlspecialchars($sem['name']) ?></strong>
                    <span><?= htmlspecialchars($sem['school_year']) ?></span>
                    <span class="semester-dates"><?= date('d/m/Y', strtotime($sem['start_date'])) ?> → <?= date('d/m/Y', strtotime($sem['end_date'])) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card" style="margin-top:16px">
            <h3>📚 <?= lang() === 'vi' ? 'Lớp đang dạy' : 'My classes' ?> (<?= count($classNames) ?>)</h3>
            <?php foreach ($classNames as $cn): ?>
            <div class="class-summary-item"><strong><?= htmlspecialchars($cn) ?></strong></div>
            <?php endforeach; ?>
            <?php if (empty($classNames)): ?><p style="color:var(--text-muted)">—</p><?php endif; ?>
        </div>
    </div>
    <div class="schedule-main">
        <div class="card">
            <h3>🗓️ <?= lang() === 'vi' ? 'Thời khóa biểu' : 'Timetable' ?></h3>
            <?php renderWeeklyTimetable($rows, lang() === 'vi' ? 'Chưa có lịch dạy trong kỳ này.' : 'No schedule.'); ?>
        </div>
        <div class="card" style="margin-top:16px">
            <h3>📋 <?= lang() === 'vi' ? 'Chi tiết' : 'Details' ?></h3>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th><?= lang() === 'vi' ? 'Lớp' : 'Class' ?></th><th><?= lang() === 'vi' ? 'Môn' : 'Course' ?></th><th><?= lang() === 'vi' ? 'Thứ' : 'Day' ?></th><th><?= lang() === 'vi' ? 'Giờ' : 'Time' ?></th><th><?= lang() === 'vi' ? 'Phòng' : 'Room' ?></th></tr></thead>
                    <tbody>
                    <?php if (empty($rows)): ?><tr><td colspan="5" style="text-align:center">—</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['class_name']) ?></td>
                        <td><?= htmlspecialchars($r['course_name']) ?></td>
                        <td><?= htmlspecialchars($r['day_of_week']) ?></td>
                        <td><?= date('H:i', strtotime($r['start_time'])) ?> – <?= date('H:i', strtotime($r['end_time'])) ?></td>
                        <td><?= htmlspecialchars($r['room_number'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
