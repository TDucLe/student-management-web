<?php
require_once dirname(__DIR__) . '/config.php';
requireLogin();

$id = (int) ($_GET['submission_id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid request');
}

$stmt = $pdo->prepare("
    SELECT sub.file_url, sub.student_id, a.teacher_id, cl.teacher_id AS class_teacher_id, st.user_id AS student_user_id
    FROM submissions sub
    JOIN assignments a ON sub.assignment_id = a.id
    JOIN classes cl ON a.class_id = cl.id
    JOIN students st ON sub.student_id = st.id
    WHERE sub.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row || empty($row['file_url'])) {
    http_response_code(404);
    exit('File not found');
}

$user = getCurrentUser();
$allowed = false;
if ($user['role'] === 'admin') {
    $allowed = true;
} elseif ($user['role'] === 'teacher') {
    $tid = getTeacherId($pdo, $user['id'], $user['username']);
    $allowed = (int) $row['class_teacher_id'] === $tid || (int) $row['teacher_id'] === $tid;
} elseif ($user['role'] === 'student') {
    $allowed = (int) $row['student_user_id'] === $user['id'];
}

if (!$allowed) {
    http_response_code(403);
    exit('Access denied');
}

$path = realpath(APP_ROOT . '/' . $row['file_url']);
$root = realpath(APP_ROOT . '/uploads');
if (!$path || !$root || strpos($path, $root) !== 0 || !is_file($path)) {
    http_response_code(404);
    exit('File missing on server');
}

$mime = mime_content_type($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
