<?php
require_once __DIR__ . '/config.php';
requireLogin();

$user = getCurrentUser();
$pageTitle = t('nav.dashboard');
$stats = [];
$schedulePreview = [];
$quickLinks = [];
$dashboardExtra = '';

try {
    if ($user['role'] === 'admin') {
        $stats = [
            ['label' => t('nav.users'), 'value' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn()],
            ['label' => t('nav.students'), 'value' => (int) $pdo->query('SELECT COUNT(*) FROM students WHERE deleted_at IS NULL')->fetchColumn()],
            ['label' => t('nav.teachers'), 'value' => (int) $pdo->query('SELECT COUNT(*) FROM teachers WHERE deleted_at IS NULL')->fetchColumn()],
            ['label' => t('nav.classes'), 'value' => (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn()],
        ];
        $quickLinks = [
            ['title' => t('nav.users'), 'desc' => lang() === 'vi' ? 'Quản lý tài khoản' : 'User accounts', 'url' => app_path('admin/users_manage.php')],
            ['title' => t('nav.students'), 'desc' => lang() === 'vi' ? 'Hồ sơ sinh viên' : 'Student profiles', 'url' => app_path('admin/students_manage.php')],
            ['title' => t('nav.teachers'), 'desc' => lang() === 'vi' ? 'Quản lý giáo viên' : 'Teacher management', 'url' => app_path('admin/teachers_manage.php')],
            ['title' => t('nav.courses'), 'desc' => lang() === 'vi' ? 'Danh sách môn học' : 'Course catalog', 'url' => app_path('admin/courses_manage.php')],
            ['title' => t('nav.classes'), 'desc' => lang() === 'vi' ? 'Lớp, lịch, sinh viên' : 'Classes, schedule, students', 'url' => app_path('admin/classes_manage.php')],
            ['title' => t('nav.rooms'), 'desc' => lang() === 'vi' ? 'Phòng học & sức chứa' : 'Room management', 'url' => app_path('admin/rooms_manage.php')],
            ['title' => t('nav.semesters'), 'desc' => lang() === 'vi' ? 'Kỳ 1, 2, hè' : 'Semesters', 'url' => app_path('admin/semesters_manage.php')],
            ['title' => t('nav.attendance'), 'desc' => lang() === 'vi' ? 'Điểm danh toàn trường' : 'School-wide attendance', 'url' => app_path('admin/attendance_manage.php')],
            ['title' => t('nav.grades'), 'desc' => lang() === 'vi' ? 'Điểm toàn hệ thống' : 'All grades', 'url' => app_path('admin/grades_manage.php')],
            ['title' => t('nav.assignments'), 'desc' => lang() === 'vi' ? 'Bài tập & nộp bài' : 'Assignments & submissions', 'url' => app_path('admin/assignments_manage.php')],
            ['title' => t('nav.leave'), 'desc' => lang() === 'vi' ? 'Duyệt đơn xin nghỉ' : 'Leave management', 'url' => app_path('admin/leave_manage.php')],
            ['title' => t('nav.stats'), 'desc' => lang() === 'vi' ? 'Biểu đồ thống kê' : 'Analytics', 'url' => app_path('admin/stats_manage.php')],
        ];
    } elseif ($user['role'] === 'teacher') {
        $tid = getTeacherId($pdo, $user['id'], $user['username']);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM classes WHERE teacher_id = ?');
        $stmt->execute([$tid]);
        $stats = [
            ['label' => t('nav.classes'), 'value' => (int) $stmt->fetchColumn()],
            ['label' => t('nav.attendance'), 'desc' => '', 'value' => lang() === 'vi' ? 'Cả lớp' : 'By class'],
            ['label' => t('nav.grades'), 'value' => lang() === 'vi' ? 'Bảng' : 'Grid'],
            ['label' => t('nav.assignments'), 'value' => (int) $pdo->prepare('SELECT COUNT(*) FROM assignments WHERE teacher_id = ?')->execute([$tid]) ? 0 : 0],
        ];
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM assignments WHERE teacher_id = ?');
        $stmt->execute([$tid]);
        $stats[3]['value'] = (int) $stmt->fetchColumn();

        $sched = $pdo->prepare('SELECT c.class_name, s.day_of_week, s.start_time, s.end_time FROM schedules s JOIN classes c ON s.class_id = c.id WHERE c.teacher_id = ? LIMIT 6');
        $sched->execute([$tid]);
        $schedulePreview = $sched->fetchAll();

        $quickLinks = [
            ['title' => t('nav.classes'), 'desc' => lang() === 'vi' ? 'Quản lý từng lớp' : 'Per-class hub', 'url' => app_path('teacher/classes.php')],
            ['title' => t('nav.attendance'), 'desc' => lang() === 'vi' ? 'Điểm danh một chạm' : 'One-screen attendance', 'url' => app_path('teacher/attendance.php')],
            ['title' => t('nav.grades'), 'desc' => lang() === 'vi' ? 'Nhập điểm cả lớp' : 'Class grade sheet', 'url' => app_path('teacher/grades.php')],
            ['title' => t('nav.assignments'), 'desc' => lang() === 'vi' ? 'Giao & tải bài nộp' : 'Assign & download', 'url' => app_path('teacher/assignments.php')],
            ['title' => t('nav.schedule'), 'desc' => lang() === 'vi' ? 'Lịch dạy tuần/tháng' : 'Teaching schedule', 'url' => app_path('teacher/schedule.php')],
            ['title' => t('nav.leave'), 'desc' => lang() === 'vi' ? 'Duyệt đơn nghỉ' : 'Leave requests', 'url' => app_path('teacher/leave_approval.php')],
            ['title' => t('nav.courses'), 'desc' => lang() === 'vi' ? 'Môn học đang dạy' : 'My courses', 'url' => app_path('teacher/courses.php')],
        ];
    } else {
        $sid = getStudentId($pdo, $user['id'], $user['username']);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = "active"');
        $stmt->execute([$sid]);
        $enrolled = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT AVG(total_score) FROM student_grades g JOIN enrollments e ON g.enrollment_id = e.id WHERE e.student_id = ? AND g.total_score IS NOT NULL');
        $stmt->execute([$sid]);
        $avgScore = $stmt->fetchColumn();
        $stats = [
            ['label' => t('nav.classes'), 'value' => $enrolled],
            ['label' => lang() === 'vi' ? 'Điểm TB (hệ 10)' : 'Avg Score /10', 'value' => $avgScore ? number_format((float) $avgScore, 2) : '—'],
            ['label' => t('nav.assignments'), 'value' => '→'],
            ['label' => t('nav.schedule'), 'value' => '→'],
        ];
        $quickLinks = [
            ['title' => t('nav.grades'), 'desc' => lang() === 'vi' ? 'Biểu đồ GPA & điểm chi tiết' : 'GPA charts & details', 'url' => app_path('student/grades_view.php')],
            ['title' => t('nav.classes'), 'desc' => lang() === 'vi' ? 'Lớp đang học' : 'My classes', 'url' => app_path('student/classes_view.php')],
            ['title' => t('nav.assignments'), 'desc' => lang() === 'vi' ? 'Nộp bài & xem deadline' : 'Submit & track', 'url' => app_path('student/assignments_view.php')],
            ['title' => t('nav.attendance'), 'desc' => lang() === 'vi' ? 'Điểm danh & nhận xét' : 'Attendance history', 'url' => app_path('student/attendance_view.php')],
            ['title' => t('nav.schedule'), 'desc' => lang() === 'vi' ? 'Lịch học tuần/tháng' : 'Weekly schedule', 'url' => app_path('student/schedule_view.php')],
            ['title' => t('nav.courses'), 'desc' => lang() === 'vi' ? 'Danh sách môn học' : 'Course list', 'url' => app_path('student/courses_view.php')],
            ['title' => t('nav.leave'), 'desc' => lang() === 'vi' ? 'Gửi đơn xin nghỉ' : 'Request leave', 'url' => app_path('student/leave_request.php')],
            ['title' => t('nav.profile'), 'desc' => lang() === 'vi' ? 'Thông tin cá nhân' : 'My profile', 'url' => app_path('student/profile.php')],
        ];
        $dashboardExtra = '';
        if ($enrolled > 0) {
            ob_start();
            $chartStmt = $pdo->prepare("
                SELECT c.course_code, g.gpa FROM enrollments e
                JOIN classes cl ON e.class_id = cl.id JOIN courses c ON cl.course_id = c.id
                LEFT JOIN student_grades g ON g.enrollment_id = e.id
                WHERE e.student_id = ? AND g.gpa IS NOT NULL LIMIT 8
            ");
            $chartStmt->execute([$sid]);
            $chartRows = $chartStmt->fetchAll();
            if ($chartRows) {
                $labels = array_column($chartRows, 'course_code');
                $values = array_map('floatval', array_column($chartRows, 'gpa'));
                echo '<div class="card"><h2>' . htmlspecialchars(t('gpa_chart_title')) . '</h2><div class="chart-box">';
                echo '<canvas id="homeGpaChart" data-labels=\'' . json_encode($labels) . '\' data-values=\'' . json_encode($values) . '\'></canvas></div></div>';
                echo '<script>document.addEventListener("DOMContentLoaded",function(){var el=document.getElementById("homeGpaChart");if(!el||typeof Chart==="undefined")return;new Chart(el,{type:"bar",data:{labels:JSON.parse(el.dataset.labels),datasets:[{label:"GPA",data:JSON.parse(el.dataset.values),backgroundColor:"#e7b91699",borderColor:"#06254d"}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{min:0,max:10}}}});});</script>';
            }
            $dashboardExtra = ob_get_clean();
        }
    }
} catch (PDOException $e) {
    flash('error', $e->getMessage());
}

renderHeader($pageTitle, $user);
require APP_ROOT . '/frontend/templates/dashboard.php';
renderFooter();
