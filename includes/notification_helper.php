<?php

/**
 * Send a notification to a specific user.
 */
function sendNotification(PDO $pdo, int $userId, string $type, string $message): void
{
    $pdo->prepare('INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)')
        ->execute([$userId, $type, $message]);
}

/**
 * Send notifications to all students enrolled in a class.
 */
function notifyClassStudents(PDO $pdo, int $classId, string $type, string $message): void
{
    $stmt = $pdo->prepare("
        SELECT s.user_id
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        WHERE e.class_id = ? AND e.status = 'active' AND s.user_id IS NOT NULL AND s.deleted_at IS NULL
    ");
    $stmt->execute([$classId]);
    $insert = $pdo->prepare('INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)');
    foreach ($stmt->fetchAll() as $row) {
        $insert->execute([$row['user_id'], $type, $message]);
    }
}

/**
 * Send a notification to the teacher who owns a class.
 */
function notifyClassTeacher(PDO $pdo, int $classId, string $type, string $message): void
{
    $stmt = $pdo->prepare("
        SELECT t.user_id
        FROM classes c
        JOIN teachers t ON c.teacher_id = t.id
        WHERE c.id = ? AND t.user_id IS NOT NULL
    ");
    $stmt->execute([$classId]);
    $row = $stmt->fetch();
    if ($row && $row['user_id']) {
        sendNotification($pdo, (int) $row['user_id'], $type, $message);
    }
}
