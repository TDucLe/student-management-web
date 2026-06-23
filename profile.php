<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('student');

$user = getCurrentUser();
$student_id = getStudentId($pdo, $user['id'], $user['username']);
$pageTitle = 'My Profile';

$stmt = $pdo->prepare("
    SELECT s.full_name, s.student_code, s.dob, s.major, s.contact, s.address, u.username, u.email
    FROM students s JOIN users u ON s.user_id = u.id
    WHERE s.user_id = ? AND s.deleted_at IS NULL
");
$stmt->execute([$user['id']]);
$student = $stmt->fetch();
if (!$student) {
    die('Student profile not found.');
}

renderHeader($pageTitle, $user);
?>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <tr><th>Username</th><td><?= htmlspecialchars($student['username']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($student['email'] ?? '—') ?></td></tr>
            <tr><th>Student code</th><td><?= htmlspecialchars($student['student_code']) ?></td></tr>
            <tr><th>Full name</th><td><?= htmlspecialchars($student['full_name']) ?></td></tr>
            <tr><th>Date of birth</th><td><?= htmlspecialchars($student['dob'] ?? '—') ?></td></tr>
            <tr><th>Major</th><td><?= htmlspecialchars($student['major'] ?? '—') ?></td></tr>
            <tr><th>Contact</th><td><?= htmlspecialchars($student['contact'] ?? '—') ?></td></tr>
            <tr><th>Address</th><td><?= htmlspecialchars($student['address'] ?? '—') ?></td></tr>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
