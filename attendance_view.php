<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('student');

$user = getCurrentUser();
$student_id = getStudentId($pdo, $user['id'], $user['username']);
$pageTitle = lang() === 'vi' ? 'Điểm danh' : 'Attendance';

// Get all enrolled classes for filter
$classesStmt = $pdo->prepare("
    SELECT c.id, c.class_name, co.course_name
    FROM enrollments e
    JOIN classes c ON e.class_id = c.id
    JOIN courses co ON c.course_id = co.id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY c.class_name
");
$classesStmt->execute([$student_id]);
$enrolledClasses = $classesStmt->fetchAll();

// Selected class filter
$selectedClass = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

// Fetch attendance records
$query = "
    SELECT a.attendance_date, a.status, a.teacher_comment, c.class_name, c.id AS class_id, co.course_name
    FROM attendance a
    JOIN enrollments e ON a.enrollment_id = e.id
    JOIN classes c ON e.class_id = c.id
    JOIN courses co ON c.course_id = co.id
    WHERE e.student_id = ?
";
$params = [$student_id];

if ($selectedClass > 0) {
    $query .= " AND c.id = ?";
    $params[] = $selectedClass;
}

$query .= " ORDER BY a.attendance_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Compute stats per class
$classStats = [];
foreach ($rows as $r) {
    $cn = $r['class_name'];
    if (!isset($classStats[$cn])) {
        $classStats[$cn] = ['course' => $r['course_name'], 'total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'class_id' => $r['class_id']];
    }
    $classStats[$cn]['total']++;
    $classStats[$cn][$r['status']]++;
}

// Also get stats for all classes even if no records yet
foreach ($enrolledClasses as $ec) {
    $cn = $ec['class_name'];
    if (!isset($classStats[$cn])) {
        $classStats[$cn] = ['course' => $ec['course_name'], 'total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'class_id' => $ec['id']];
    }
}

// Overall totals
$totalAll = count($rows);
$presentAll = 0; $absentAll = 0; $lateAll = 0;
foreach ($rows as $r) {
    if ($r['status'] === 'present') $presentAll++;
    elseif ($r['status'] === 'absent') $absentAll++;
    elseif ($r['status'] === 'late') $lateAll++;
}
$rateAll = $totalAll > 0 ? round(($presentAll + $lateAll) / $totalAll * 100) : 0;

renderHeader($pageTitle, $user);
?>

<div class="attendance-layout">
    <!-- Overview stats -->
    <div class="stat-grid" style="margin-bottom:24px;">
        <div class="stat-card">
            <div class="label"><?= lang() === 'vi' ? 'Tổng buổi' : 'Total sessions' ?></div>
            <div class="value"><?= $totalAll ?></div>
        </div>
        <div class="stat-card">
            <div class="label" style="color:#059669"><?= lang() === 'vi' ? '✅ Có mặt' : '✅ Present' ?></div>
            <div class="value" style="color:#059669"><?= $presentAll ?></div>
        </div>
        <div class="stat-card">
            <div class="label" style="color:#dc2626"><?= lang() === 'vi' ? '❌ Nghỉ' : '❌ Absent' ?></div>
            <div class="value" style="color:#dc2626"><?= $absentAll ?></div>
        </div>
        <div class="stat-card">
            <div class="label" style="color:#d97706"><?= lang() === 'vi' ? '⏰ Muộn' : '⏰ Late' ?></div>
            <div class="value" style="color:#d97706"><?= $lateAll ?></div>
        </div>
    </div>

    <!-- Per-class breakdown -->
    <div class="card" style="margin-bottom:24px;">
        <h2>📊 <?= lang() === 'vi' ? 'Thống kê theo lớp' : 'By Class' ?></h2>
        <div class="attendance-class-grid">
            <?php foreach ($classStats as $cn => $cs):
                $csRate = $cs['total'] > 0 ? round(($cs['present'] + $cs['late']) / $cs['total'] * 100) : 0;
                $barColor = $csRate >= 80 ? '#059669' : ($csRate >= 50 ? '#d97706' : '#dc2626');
                $isSelected = ($selectedClass > 0 && $selectedClass === $cs['class_id']);
            ?>
            <a href="?class_id=<?= (int) $cs['class_id'] ?>" class="attendance-class-card <?= $isSelected ? 'att-selected' : '' ?>">
                <div class="att-class-header">
                    <strong><?= htmlspecialchars($cn) ?></strong>
                    <span class="att-rate" style="color:<?= $barColor ?>"><?= $csRate ?>%</span>
                </div>
                <span class="att-course"><?= htmlspecialchars($cs['course']) ?></span>
                <div class="att-bar-wrap">
                    <div class="att-bar" style="width:<?= $csRate ?>%;background:<?= $barColor ?>"></div>
                </div>
                <div class="att-mini-stats">
                    <span class="att-stat-present"><?= $cs['present'] ?> ✅</span>
                    <span class="att-stat-absent"><?= $cs['absent'] ?> ❌</span>
                    <span class="att-stat-late"><?= $cs['late'] ?> ⏰</span>
                    <span style="color:var(--text-muted)"><?= $cs['total'] ?> <?= lang() === 'vi' ? 'buổi' : 'sessions' ?></span>
                </div>
            </a>
            <?php endforeach; ?>
            <?php if (empty($classStats)): ?>
            <p style="color:var(--text-muted);padding:20px;"><?= lang() === 'vi' ? 'Chưa có dữ liệu điểm danh' : 'No attendance data' ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail table with filter -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
            <h2 style="margin:0;">📋 <?= lang() === 'vi' ? 'Chi tiết điểm danh' : 'Details' ?><?= $selectedClass > 0 ? ' — ' . htmlspecialchars(array_column($enrolledClasses, 'class_name', 'id')[$selectedClass] ?? '') : '' ?></h2>
            <div style="display:flex;gap:10px;align-items:center;">
                <?php if ($selectedClass > 0): ?>
                <a href="attendance_view.php" class="btn btn-ghost btn-sm"><?= lang() === 'vi' ? 'Xem tất cả' : 'Show all' ?></a>
                <?php endif; ?>
                <select id="attMonthFilter" class="filter-select" style="min-width:140px;">
                    <option value=""><?= lang() === 'vi' ? 'Tất cả tháng' : 'All months' ?></option>
                    <?php
                    $months = array_unique(array_map(function($r) { return substr($r['attendance_date'], 0, 7); }, $rows));
                    sort($months);
                    foreach ($months as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table" id="attTable">
                <thead><tr>
                    <th><?= lang() === 'vi' ? 'Ngày' : 'Date' ?></th>
                    <th><?= lang() === 'vi' ? 'Lớp' : 'Class' ?></th>
                    <th><?= lang() === 'vi' ? 'Môn' : 'Course' ?></th>
                    <th><?= lang() === 'vi' ? 'Trạng thái' : 'Status' ?></th>
                    <th><?= lang() === 'vi' ? 'Nhận xét' : 'Comment' ?></th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted)">
                    <?= lang() === 'vi' ? 'Chưa có dữ liệu điểm danh' : 'No attendance records' ?>
                </td></tr>
                <?php else: foreach ($rows as $r): ?>
                <tr data-month="<?= substr($r['attendance_date'], 0, 7) ?>">
                    <td><?= date('d/m/Y', strtotime($r['attendance_date'])) ?></td>
                    <td><?= htmlspecialchars($r['class_name']) ?></td>
                    <td><?= htmlspecialchars($r['course_name']) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($r['status']) ?>"><?= $r['status'] === 'present' ? '✅ Có mặt' : ($r['status'] === 'absent' ? '❌ Nghỉ' : '⏰ Muộn') ?></span></td>
                    <td><?= htmlspecialchars($r['teacher_comment'] ?? '—') ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="filter-count" id="attCount"></div>
    </div>
</div>

<script>
(function(){
    var mFilter = document.getElementById('attMonthFilter');
    var table = document.getElementById('attTable');
    var countEl = document.getElementById('attCount');
    if (!mFilter || !table) return;
    function filter() {
        var m = mFilter.value;
        var rows = table.querySelectorAll('tbody tr[data-month]');
        var shown = 0;
        rows.forEach(function(row) {
            var rm = row.getAttribute('data-month') || '';
            row.style.display = (!m || rm === m) ? '' : 'none';
            if (!m || rm === m) shown++;
        });
        if (countEl) countEl.textContent = shown + ' / ' + rows.length + (m ? ' (filtered)' : '');
    }
    mFilter.addEventListener('change', filter);
})();
</script>

<?php renderFooter(); ?>
