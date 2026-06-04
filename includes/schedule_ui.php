<?php

function scheduleDayOrder(): array
{
    return [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7,
    ];
}

function sortScheduleRows(array &$rows): void
{
    $order = scheduleDayOrder();
    usort($rows, static function ($a, $b) use ($order) {
        $da = $order[$a['day_of_week'] ?? ''] ?? 99;
        $db = $order[$b['day_of_week'] ?? ''] ?? 99;
        if ($da !== $db) {
            return $da <=> $db;
        }
        return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
    });
}

function renderWeeklyTimetable(array $rows, string $emptyMessage = 'Chưa có lịch học.'): void
{
    sortScheduleRows($rows);
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $byDay = array_fill_keys($days, []);
    foreach ($rows as $r) {
        $d = $r['day_of_week'] ?? '';
        if (isset($byDay[$d])) {
            $byDay[$d][] = $r;
        }
    }
    ?>
    <div class="timetable-grid">
        <?php foreach ($days as $day): ?>
        <div class="timetable-day">
            <div class="timetable-day-title"><?= htmlspecialchars($day) ?></div>
            <?php if (empty($byDay[$day])): ?>
                <p class="timetable-empty">—</p>
            <?php else: foreach ($byDay[$day] as $slot): ?>
                <div class="timetable-slot">
                    <strong><?= htmlspecialchars($slot['class_name'] ?? '') ?></strong>
                    <?php if (!empty($slot['course_name'])): ?>
                        <span class="timetable-meta"><?= htmlspecialchars($slot['course_name']) ?></span>
                    <?php endif; ?>
                    <span class="timetable-time">
                        <?= date('H:i', strtotime($slot['start_time'])) ?> – <?= date('H:i', strtotime($slot['end_time'])) ?>
                    </span>
                    <?php if (!empty($slot['room_number'])): ?>
                        <span class="timetable-meta">Phòng <?= htmlspecialchars($slot['room_number']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if (empty($rows)): ?>
        <p class="alert alert-info"><?= htmlspecialchars($emptyMessage) ?></p>
    <?php endif;
}
