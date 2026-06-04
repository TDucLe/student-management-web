<?php
require_once __DIR__ . '/config.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser();

try {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';

    if ($q === '') {
        echo json_encode([]);
        exit;
    }

    $searchParam = '%' . $q . '%';
    $results = [];
    $limit = 20;

    // Search Students
    if (($type === 'all' || $type === 'student') && $limit > 0) {
        $stmt = $pdo->prepare(
            "SELECT full_name, student_code 
             FROM students 
             WHERE deleted_at IS NULL 
               AND (full_name LIKE :q1 OR student_code LIKE :q2) 
             LIMIT :lim"
        );
        $stmt->bindValue(':q1', $searchParam, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $searchParam, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($students as $row) {
            $url = '';
            if ($user['role'] === 'admin') {
                $url = app_path('admin/students_manage.php');
            }
            $results[] = [
                'type'   => 'student',
                'name'   => $row['full_name'],
                'code'   => $row['student_code'],
                'detail' => 'Mã SV: ' . $row['student_code'],
                'url'    => $url
            ];
        }
        $limit -= count($students);
    }

    // Search Teachers
    if (($type === 'all' || $type === 'teacher') && $limit > 0) {
        $stmt = $pdo->prepare(
            "SELECT full_name, teacher_code, department 
             FROM teachers 
             WHERE deleted_at IS NULL 
               AND (full_name LIKE :q1 OR teacher_code LIKE :q2 OR department LIKE :q3) 
             LIMIT :lim"
        );
        $stmt->bindValue(':q1', $searchParam, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $searchParam, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $searchParam, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($teachers as $row) {
            $url = '';
            if ($user['role'] === 'admin') {
                $url = app_path('admin/teachers_manage.php');
            }
            $results[] = [
                'type'   => 'teacher',
                'name'   => $row['full_name'],
                'code'   => $row['teacher_code'],
                'detail' => 'Khoa: ' . $row['department'],
                'url'    => $url
            ];
        }
        $limit -= count($teachers);
    }

    // Search Classes
    if (($type === 'all' || $type === 'class') && $limit > 0) {
        $stmt = $pdo->prepare(
            "SELECT c.class_name, co.course_name 
             FROM classes c 
             JOIN courses co ON c.course_id = co.id 
             WHERE c.class_name LIKE :q1 OR co.course_name LIKE :q2 
             LIMIT :lim"
        );
        $stmt->bindValue(':q1', $searchParam, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $searchParam, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($classes as $row) {
            $url = '';
            if ($user['role'] === 'admin') {
                $url = app_path('admin/classes_manage.php');
            } elseif ($user['role'] === 'teacher') {
                $url = app_path('teacher/classes.php');
            } elseif ($user['role'] === 'student') {
                $url = app_path('student/classes_view.php');
            }
            $results[] = [
                'type'   => 'class',
                'name'   => $row['class_name'],
                'code'   => '',
                'detail' => 'Môn: ' . $row['course_name'],
                'url'    => $url
            ];
        }
        $limit -= count($classes);
    }

    // Search Courses
    if (($type === 'all' || $type === 'course') && $limit > 0) {
        $stmt = $pdo->prepare(
            "SELECT course_code, course_name, department 
             FROM courses 
             WHERE course_code LIKE :q1 OR course_name LIKE :q2 OR department LIKE :q3 
             LIMIT :lim"
        );
        $stmt->bindValue(':q1', $searchParam, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $searchParam, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $searchParam, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($courses as $row) {
            $url = '';
            if ($user['role'] === 'admin') {
                $url = app_path('admin/courses_manage.php');
            }
            $results[] = [
                'type'   => 'course',
                'name'   => $row['course_name'],
                'code'   => $row['course_code'],
                'detail' => 'Khoa: ' . $row['department'],
                'url'    => $url
            ];
        }
    }

    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Đã xảy ra lỗi trong quá trình tìm kiếm.']);
}
