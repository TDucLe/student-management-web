<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/schedule_ui.php';
requireRole('student');

$user = getCurrentUser();
$student_id = getStudentId($pdo, $user['id'], $user['username']);
$pageTitle = lang() === 'vi' ? 'Lịch học' : 'My Schedule';

// Get semesters that the student is enrolled in
$semesters = $pdo->prepare("
    SELECT DISTINCT s.id, s.name, s.school_year, s.start_date, s.end_date, s.term_type
    FROM semesters s
    JOIN classes c ON c.semester_id = s.id
    JOIN enrollments e ON e.class_id = c.id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY s.start_date DESC
");
$semesters->execute([$student_id]);
$semesterList = $semesters->fetchAll();

// Current semester selection
$selectedSem = isset($_GET['semester_id']) ? (int) $_GET['semester_id'] : 0;
if ($selectedSem === 0 && !empty($semesterList)) {
    $selectedSem = (int) $semesterList[0]['id'];
}

// Current month/year for calendar
$curMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$curYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

// Fetch schedule for selected semester
$scheduleRows = [];
$classSummary = [];
if ($selectedSem > 0) {
    $stmt = $pdo->prepare("
        SELECT sch.day_of_week, sch.start_time, sch.end_time, c.class_name, co.course_name, co.credits,
               COALESCE(r.room_number, cr.room_number) AS room_number,
               s.start_date AS sem_start, s.end_date AS sem_end
        FROM schedules sch
        JOIN classes c ON sch.class_id = c.id
        JOIN courses co ON c.course_id = co.id
        LEFT JOIN rooms r ON sch.room_id = r.id
        LEFT JOIN rooms cr ON c.room_id = cr.id
        JOIN enrollments e ON e.class_id = c.id
        JOIN semesters s ON c.semester_id = s.id
        WHERE e.student_id = ? AND e.status = 'active' AND c.semester_id = ?
    ");
    $stmt->execute([$student_id, $selectedSem]);
    $scheduleRows = $stmt->fetchAll();

    // Build class summary
    foreach ($scheduleRows as $r) {
        $key = $r['class_name'];
        if (!isset($classSummary[$key])) {
            $classSummary[$key] = [
                'course' => $r['course_name'],
                'credits' => $r['credits'] ?? '',
                'slots' => [],
            ];
        }
        $classSummary[$key]['slots'][] = $r['day_of_week'] . ' ' . date('H:i', strtotime($r['start_time'])) . '–' . date('H:i', strtotime($r['end_time']));
    }
}

// Build calendar data
$dayMap = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
$calendarEvents = [];
foreach ($scheduleRows as $r) {
    $dow = $dayMap[$r['day_of_week']] ?? 0;
    if ($dow === 0) continue;
    $calendarEvents[] = [
        'dow' => $dow,
        'label' => $r['class_name'],
        'time' => date('H:i', strtotime($r['start_time'])) . '–' . date('H:i', strtotime($r['end_time'])),
        'room' => $r['room_number'] ?? '',
    ];
}

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

// Clamp month/year to semester range
if ($semStart && $semEnd) {
    $semStartMonth = (int) date('m', strtotime($semStart));
    $semStartYear = (int) date('Y', strtotime($semStart));
    $semEndMonth = (int) date('m', strtotime($semEnd));
    $semEndYear = (int) date('Y', strtotime($semEnd));

    // Clamp current view within semester
    if ($curYear < $semStartYear || ($curYear === $semStartYear && $curMonth < $semStartMonth)) {
        $curMonth = $semStartMonth; $curYear = $semStartYear;
    }
    if ($curYear > $semEndYear || ($curYear === $semEndYear && $curMonth > $semEndMonth)) {
        $curMonth = $semEndMonth; $curYear = $semEndYear;
    }
}

// Calendar helpers
$firstDay = mktime(0, 0, 0, $curMonth, 1, $curYear);
$daysInMonth = (int) date('t', $firstDay);
$startDow = (int) date('N', $firstDay); // 1=Mon, 7=Sun

$prevMonth = $curMonth - 1; $prevYear = $curYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $curMonth + 1; $nextYear = $curYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Disable nav buttons if at semester boundary
$canPrev = true; $canNext = true;
if ($semStart) {
    $smM = (int) date('m', strtotime($semStart)); $smY = (int) date('Y', strtotime($semStart));
    if ($curYear === $smY && $curMonth === $smM) $canPrev = false;
    if ($curYear < $smY || ($curYear === $smY && $curMonth < $smM)) $canPrev = false;
}
if ($semEnd) {
    $emM = (int) date('m', strtotime($semEnd)); $emY = (int) date('Y', strtotime($semEnd));
    if ($curYear === $emY && $curMonth === $emM) $canNext = false;
    if ($curYear > $emY || ($curYear === $emY && $curMonth > $emM)) $canNext = false;
}

$monthNames = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
$monthLabel = (lang() === 'vi' ? $monthNames[$curMonth] : date('F', $firstDay)) . ' ' . $curYear;
$baseUrl = 'schedule_view.php?semester_id=' . $selectedSem;

renderHeader($pageTitle, $user);
?>

<div class="schedule-layout">
    <!-- Left: Semester picker + class summary -->
    <div class="schedule-sidebar">
        <div class="card" style="margin-bottom:16px;">
            <h3>📅 <?= lang() === 'vi' ? 'Chọn kỳ học' : 'Semester' ?></h3>
            <div class="semester-list">
                <?php foreach ($semesterList as $sem): ?>
                <a href="?semester_id=<?= (int) $sem['id'] ?>&month=<?= $curMonth ?>&year=<?= $curYear ?>"
                   class="semester-item <?= (int) $sem['id'] === $selectedSem ? 'active' : '' ?>">
                    <strong><?= htmlspecialchars($sem['name']) ?></strong>
                    <span><?= htmlspecialchars($sem['school_year']) ?></span>
                    <span class="semester-dates"><?= date('d/m/Y', strtotime($sem['start_date'])) ?> → <?= date('d/m/Y', strtotime($sem['end_date'])) ?></span>
                </a>
                <?php endforeach; ?>
                <?php if (empty($semesterList)): ?>
                <p style="color:var(--text-muted);padding:12px;"><?= lang() === 'vi' ? 'Chưa đăng ký kỳ nào' : 'No semesters' ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3>📚 <?= lang() === 'vi' ? 'Lớp đang học' : 'Enrolled classes' ?> (<?= count($classSummary) ?>)</h3>
            <?php if (empty($classSummary)): ?>
            <p style="color:var(--text-muted)"><?= lang() === 'vi' ? 'Chưa có lớp' : 'No classes' ?></p>
            <?php else: foreach ($classSummary as $className => $info): ?>
            <div class="class-summary-item">
                <strong><?= htmlspecialchars($className) ?></strong>
                <span><?= htmlspecialchars($info['course']) ?><?= $info['credits'] ? ' · ' . (int) $info['credits'] . ' TC' : '' ?></span>
                <?php foreach ($info['slots'] as $slot): ?>
                <span class="slot-tag"><?= htmlspecialchars($slot) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Right: Calendar + timetable -->
    <div class="schedule-main">
        <!-- Month calendar -->
        <div class="card" style="margin-bottom:16px;">
            <?php if ($semInfo): ?>
            <div class="alert alert-info" style="margin-bottom:12px;font-size:0.95rem;">
                📅 <?= htmlspecialchars($semInfo) ?>
            </div>
            <?php endif; ?>
            <div class="cal-header">
                <?php if ($canPrev): ?>
                <a href="<?= $baseUrl ?>&month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-ghost btn-sm">◀</a>
                <?php else: ?>
                <span class="btn btn-ghost btn-sm" style="opacity:0.3;cursor:not-allowed">◀</span>
                <?php endif; ?>
                <h3 style="margin:0;flex:1;text-align:center"><?= htmlspecialchars($monthLabel) ?></h3>
                <?php if ($canNext): ?>
                <a href="<?= $baseUrl ?>&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-ghost btn-sm">▶</a>
                <?php else: ?>
                <span class="btn btn-ghost btn-sm" style="opacity:0.3;cursor:not-allowed">▶</span>
                <?php endif; ?>
            </div>
            <table class="cal-table">
                <thead><tr>
                    <th>T2</th><th>T3</th><th>T4</th><th>T5</th><th>T6</th><th>T7</th><th>CN</th>
                </tr></thead>
                <tbody>
                <?php
                $day = 1;
                $today = date('Y-m-d');
                for ($week = 0; $week < 6 && $day <= $daysInMonth; $week++):
                ?>
                <tr>
                    <?php for ($d = 1; $d <= 7; $d++):
                        if (($week === 0 && $d < $startDow) || $day > $daysInMonth):
                    ?>
                        <td class="cal-empty"></td>
                    <?php else:
                        $dateStr = sprintf('%04d-%02d-%02d', $curYear, $curMonth, $day);
                        $isToday = ($dateStr === $today);
                        $inRange = true;
                        if ($semStart && $dateStr < $semStart) $inRange = false;
                        if ($semEnd && $dateStr > $semEnd) $inRange = false;
                        $dayEvents = [];
                        if ($inRange) {
                            foreach ($calendarEvents as $ev) {
                                if ($ev['dow'] === $d) $dayEvents[] = $ev;
                            }
                        }
                        $hasClass = !empty($dayEvents);
                    ?>
                        <td class="cal-day <?= $isToday ? 'cal-today' : '' ?> <?= $hasClass ? 'cal-has-event' : '' ?> <?= !$inRange ? 'cal-out-of-range' : '' ?>">
                            <span class="cal-num"><?= $day ?></span>
                            <?php foreach (array_slice($dayEvents, 0, 2) as $ev): ?>
                            <div class="cal-event" title="<?= htmlspecialchars($ev['label'] . ' ' . $ev['time'] . ($ev['room'] ? ' - ' . $ev['room'] : '')) ?>">
                                <?= htmlspecialchars($ev['label']) ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if (count($dayEvents) > 2): ?>
                            <div class="cal-more">+<?= count($dayEvents) - 2 ?></div>
                            <?php endif; ?>
                        </td>
                    <?php $day++; endif; endfor; ?>
                </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <!-- Weekly timetable -->
        <div class="card">
            <h3>🗓️ <?= lang() === 'vi' ? 'Thời khóa biểu tuần' : 'Weekly timetable' ?></h3>
            <?php renderWeeklyTimetable($scheduleRows, lang() === 'vi' ? 'Chưa có lịch học trong kỳ này.' : 'No schedule for this semester.'); ?>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
