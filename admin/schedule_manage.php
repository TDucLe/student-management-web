<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/schedule_ui.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = lang() === 'vi' ? 'Lịch học toàn trường' : 'All Schedules';

// View mode
$view = $_GET['view'] ?? 'week';
if (!in_array($view, ['week', 'month'])) $view = 'week';

$curMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$curYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

// Filters
$filterClass = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$filterTeacher = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
$filterSem = isset($_GET['semester_id']) ? (int) $_GET['semester_id'] : 0;

// Fetch filter options
$allClasses = $pdo->query("SELECT c.id, c.class_name, co.course_name FROM classes c JOIN courses co ON c.course_id = co.id ORDER BY c.class_name")->fetchAll();
$allTeachers = $pdo->query("SELECT id, full_name FROM teachers WHERE deleted_at IS NULL ORDER BY full_name")->fetchAll();
$allSemesters = $pdo->query("SELECT id, name, school_year FROM semesters ORDER BY start_date DESC")->fetchAll();

// Build schedule query
$query = "
    SELECT sch.day_of_week, sch.start_time, sch.end_time, cl.class_name, c.course_name,
           COALESCE(r.room_number, cr.room_number) AS room_number,
           t.full_name AS teacher_name, s.name AS semester_name,
           cl.id AS class_id
    FROM schedules sch
    JOIN classes cl ON sch.class_id = cl.id
    JOIN courses c ON cl.course_id = c.id
    LEFT JOIN rooms r ON sch.room_id = r.id
    LEFT JOIN rooms cr ON cl.room_id = cr.id
    LEFT JOIN teachers t ON cl.teacher_id = t.id
    LEFT JOIN semesters s ON cl.semester_id = s.id
    WHERE 1=1
";
$params = [];

if ($filterClass > 0) { $query .= " AND cl.id = ?"; $params[] = $filterClass; }
if ($filterTeacher > 0) { $query .= " AND cl.teacher_id = ?"; $params[] = $filterTeacher; }
if ($filterSem > 0) { $query .= " AND cl.semester_id = ?"; $params[] = $filterSem; }

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Calendar helpers
$dayMap = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];

// Get semester date limits if filtered
$semStart = null; $semEnd = null; $semInfo = '';
if ($filterSem > 0) {
    $semQ = $pdo->prepare("SELECT name, start_date, end_date FROM semesters WHERE id = ?");
    $semQ->execute([$filterSem]);
    $semRow = $semQ->fetch();
    if ($semRow) {
        $semStart = $semRow['start_date'];
        $semEnd = $semRow['end_date'];
        $semInfo = $semRow['name'] . ' (' . date('d/m/Y', strtotime($semStart)) . ' → ' . date('d/m/Y', strtotime($semEnd)) . ')';
    }
}

// Clamp month to semester range
if ($semStart && $semEnd) {
    $smM = (int) date('m', strtotime($semStart)); $smY = (int) date('Y', strtotime($semStart));
    $emM = (int) date('m', strtotime($semEnd)); $emY = (int) date('Y', strtotime($semEnd));
    if ($curYear < $smY || ($curYear === $smY && $curMonth < $smM)) { $curMonth = $smM; $curYear = $smY; }
    if ($curYear > $emY || ($curYear === $emY && $curMonth > $emM)) { $curMonth = $emM; $curYear = $emY; }
}

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

$calEvents = [];
foreach ($rows as $r) {
    $dow = $dayMap[$r['day_of_week']] ?? 0;
    if ($dow === 0) continue;
    $calEvents[] = ['dow' => $dow, 'label' => $r['class_name'], 'time' => date('H:i', strtotime($r['start_time'])) . '–' . date('H:i', strtotime($r['end_time'])), 'teacher' => $r['teacher_name'] ?? '', 'room' => $r['room_number'] ?? ''];
}

// Stats
$totalSlots = count($rows);
$uniqueClasses = count(array_unique(array_column($rows, 'class_name')));

$filterUrl = 'schedule_manage.php?view=' . $view . '&month=' . $curMonth . '&year=' . $curYear;
if ($filterClass > 0) $filterUrl .= '&class_id=' . $filterClass;
if ($filterTeacher > 0) $filterUrl .= '&teacher_id=' . $filterTeacher;
if ($filterSem > 0) $filterUrl .= '&semester_id=' . $filterSem;

renderHeader($pageTitle, $user);
?>

<!-- Stats -->
<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card"><div class="label"><?= lang() === 'vi' ? 'Tiết học' : 'Slots' ?></div><div class="value"><?= $totalSlots ?></div></div>
    <div class="stat-card"><div class="label"><?= lang() === 'vi' ? 'Lớp' : 'Classes' ?></div><div class="value"><?= $uniqueClasses ?></div></div>
</div>

<!-- Filters + View tabs -->
<div class="card" style="margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
        <h2 style="margin:0">🔍 <?= lang() === 'vi' ? 'Bộ lọc' : 'Filters' ?></h2>
        <div class="view-tabs">
            <a href="schedule_manage.php?view=week&class_id=<?= $filterClass ?>&teacher_id=<?= $filterTeacher ?>&semester_id=<?= $filterSem ?>" class="view-tab <?= $view === 'week' ? 'active' : '' ?>">🗓️ <?= lang() === 'vi' ? 'Tuần' : 'Week' ?></a>
            <a href="schedule_manage.php?view=month&month=<?= $curMonth ?>&year=<?= $curYear ?>&class_id=<?= $filterClass ?>&teacher_id=<?= $filterTeacher ?>&semester_id=<?= $filterSem ?>" class="view-tab <?= $view === 'month' ? 'active' : '' ?>">📅 <?= lang() === 'vi' ? 'Tháng' : 'Month' ?></a>
        </div>
    </div>
    <form method="GET" class="search-filter-bar">
        <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
        <input type="hidden" name="month" value="<?= $curMonth ?>">
        <input type="hidden" name="year" value="<?= $curYear ?>">
        <select name="semester_id" class="filter-select">
            <option value="0"><?= lang() === 'vi' ? '— Tất cả kỳ —' : '— All semesters —' ?></option>
            <?php foreach ($allSemesters as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= $filterSem === (int) $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name'] . ' (' . $s['school_year'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
        <select name="class_id" class="filter-select">
            <option value="0"><?= lang() === 'vi' ? '— Tất cả lớp —' : '— All classes —' ?></option>
            <?php foreach ($allClasses as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $filterClass === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name'] . ' – ' . $c['course_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="teacher_id" class="filter-select">
            <option value="0"><?= lang() === 'vi' ? '— Tất cả GV —' : '— All teachers —' ?></option>
            <?php foreach ($allTeachers as $t): ?>
            <option value="<?= (int) $t['id'] ?>" <?= $filterTeacher === (int) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><?= lang() === 'vi' ? '🔍 Lọc' : '🔍 Filter' ?></button>
        <?php if ($filterClass || $filterTeacher || $filterSem): ?>
        <a href="schedule_manage.php?view=<?= $view ?>&month=<?= $curMonth ?>&year=<?= $curYear ?>" class="btn btn-ghost btn-sm"><?= lang() === 'vi' ? 'Xóa lọc' : 'Clear' ?></a>
        <?php endif; ?>
    </form>
</div>

<?php if ($view === 'week'): ?>
<!-- ═══ WEEK VIEW ═══ -->
<div class="card">
    <h3>🗓️ <?= lang() === 'vi' ? 'Thời khóa biểu tuần' : 'Weekly Timetable' ?></h3>
    <?php if ($semInfo): ?>
    <div class="alert alert-info" style="margin-bottom:12px;font-size:0.95rem;">📅 <?= htmlspecialchars($semInfo) ?></div>
    <?php endif; ?>
    <?php renderWeeklyTimetable($rows, lang() === 'vi' ? 'Không có lịch nào phù hợp bộ lọc.' : 'No schedules match filters.'); ?>
</div>

<?php else: ?>
<!-- ═══ MONTH VIEW ═══ -->
<div class="card">
    <?php if ($semInfo): ?>
    <div class="alert alert-info" style="margin-bottom:12px;font-size:0.95rem;">📅 <?= htmlspecialchars($semInfo) ?></div>
    <?php endif; ?>
    <div class="cal-header">
        <?php if ($canPrev): ?>
        <a href="schedule_manage.php?view=month&month=<?= $prevMonth ?>&year=<?= $prevYear ?>&class_id=<?= $filterClass ?>&teacher_id=<?= $filterTeacher ?>&semester_id=<?= $filterSem ?>" class="btn btn-ghost btn-sm">◀</a>
        <?php else: ?>
        <span class="btn btn-ghost btn-sm" style="opacity:0.3;cursor:not-allowed">◀</span>
        <?php endif; ?>
        <h2 style="margin:0;flex:1;text-align:center"><?= htmlspecialchars($monthLabel) ?></h2>
        <?php if ($canNext): ?>
        <a href="schedule_manage.php?view=month&month=<?= $nextMonth ?>&year=<?= $nextYear ?>&class_id=<?= $filterClass ?>&teacher_id=<?= $filterTeacher ?>&semester_id=<?= $filterSem ?>" class="btn btn-ghost btn-sm">▶</a>
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
                    <?php foreach (array_slice($dayEvts, 0, 3) as $ev): ?>
                    <div class="cal-event" title="<?= htmlspecialchars($ev['label'] . ' ' . $ev['time'] . ' – GV: ' . $ev['teacher'] . ($ev['room'] ? ' – P.' . $ev['room'] : '')) ?>">
                        <?= htmlspecialchars($ev['label']) ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($dayEvts) > 3): ?><div class="cal-more">+<?= count($dayEvts) - 3 ?></div><?php endif; ?>
                </td>
            <?php $day++; endif; endfor; ?>
        </tr>
        <?php endfor; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Detail table always shown -->
<div class="card" style="margin-top:20px;">
    <h3>📋 <?= lang() === 'vi' ? 'Chi tiết tất cả lịch' : 'All Schedule Details' ?> (<?= $totalSlots ?>)</h3>
    <div class="table-wrap">
        <table class="data-table" id="adminScheduleTable">
            <thead><tr>
                <th><?= lang() === 'vi' ? 'Lớp' : 'Class' ?></th>
                <th><?= lang() === 'vi' ? 'Môn' : 'Course' ?></th>
                <th><?= lang() === 'vi' ? 'GV' : 'Teacher' ?></th>
                <th><?= lang() === 'vi' ? 'Thứ' : 'Day' ?></th>
                <th><?= lang() === 'vi' ? 'Giờ' : 'Time' ?></th>
                <th><?= lang() === 'vi' ? 'Phòng' : 'Room' ?></th>
                <th><?= lang() === 'vi' ? 'Kỳ' : 'Semester' ?></th>
            </tr></thead>
            <tbody>
            <?php if (empty($rows)): ?><tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted)"><?= lang() === 'vi' ? 'Không có dữ liệu' : 'No data' ?></td></tr>
            <?php else: foreach ($rows as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['class_name']) ?></strong></td>
                <td><?= htmlspecialchars($r['course_name']) ?></td>
                <td><?= htmlspecialchars($r['teacher_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($r['day_of_week']) ?></td>
                <td><?= date('H:i', strtotime($r['start_time'])) ?> – <?= date('H:i', strtotime($r['end_time'])) ?></td>
                <td><?= htmlspecialchars($r['room_number'] ?? '—') ?></td>
                <td><?= htmlspecialchars($r['semester_name'] ?? '') ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderFooter(); ?>
