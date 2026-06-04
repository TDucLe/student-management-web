<?php

function gradeWeights(): array
{
    return ['regular' => 0.10, 'midterm' => 0.30, 'final' => 0.60];
}

function calculateTotalScore(?float $regular, ?float $midterm, ?float $final): ?float
{
    if ($regular === null || $midterm === null || $final === null) {
        return null;
    }
    $w = gradeWeights();
    $total = $w['regular'] * $regular + $w['midterm'] * $midterm + $w['final'] * $final;
    return round($total, 2);
}

function letterFromTotal(float $total): string
{
    if ($total < 4.0) {
        return 'F';
    }
    if ($total >= 9.0) {
        return 'A+';
    }
    if ($total >= 8.5) {
        return 'A';
    }
    if ($total >= 8.0) {
        return 'B+';
    }
    if ($total >= 7.0) {
        return 'B';
    }
    if ($total >= 6.5) {
        return 'C+';
    }
    if ($total >= 5.5) {
        return 'C';
    }
    if ($total >= 5.0) {
        return 'D+';
    }
    return 'D';
}

function gpaFromTotal(?float $total): ?float
{
    return $total;
}

function saveStudentGrade(PDO $pdo, int $enrollmentId, ?float $regular, ?float $midterm, ?float $final): void
{
    $total = calculateTotalScore($regular, $midterm, $final);
    $letter = $total !== null ? letterFromTotal($total) : null;
    $gpa = gpaFromTotal($total);

    $pdo->prepare("
        INSERT INTO student_grades (enrollment_id, regular_score, midterm_score, final_score, total_score, letter_grade, gpa)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            regular_score = VALUES(regular_score),
            midterm_score = VALUES(midterm_score),
            final_score = VALUES(final_score),
            total_score = VALUES(total_score),
            letter_grade = VALUES(letter_grade),
            gpa = VALUES(gpa)
    ")->execute([$enrollmentId, $regular, $midterm, $final, $total, $letter, $gpa]);
}

function verifyTeacherOwnsClass(PDO $pdo, int $teacherId, int $classId): bool
{
    $stmt = $pdo->prepare('SELECT id FROM classes WHERE id = ? AND teacher_id = ?');
    $stmt->execute([$classId, $teacherId]);
    return (bool) $stmt->fetchColumn();
}

function getClassEnrollments(PDO $pdo, int $classId): array
{
    $stmt = $pdo->prepare("
        SELECT e.id AS enrollment_id, s.id AS student_id, s.student_code, s.full_name
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        WHERE e.class_id = ? AND e.status = 'active' AND s.deleted_at IS NULL
        ORDER BY s.full_name
    ");
    $stmt->execute([$classId]);
    return $stmt->fetchAll();
}
