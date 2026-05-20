<?php
// ==========================================
// LEAVE REQUEST CRUD OPERATIONS
// ==========================================

/**
 * Create a new leave request
 */
function createLeaveRequest($pdo, $student_id, $class_id, $reason, $leave_date)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO leave_requests
            (student_id, class_id, reason, leave_date, status)
            VALUES
            (:student_id, :class_id, :reason, :leave_date, 'pending')
        ");

        return $stmt->execute([
            ':student_id' => $student_id,
            ':class_id' => $class_id,
            ':reason' => $reason,
            ':leave_date' => $leave_date
        ]);

    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get leave request by ID
 */
function getLeaveRequestById($pdo, $id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT
                lr.*,
                s.full_name AS student_name,
                c.course_name,
                cl.class_name
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            JOIN classes cl ON lr.class_id = cl.id
            JOIN courses c ON cl.course_id = c.id
            WHERE lr.id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get all leave requests of a student
 */
function getLeaveRequestsByStudent($pdo, $student_id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT
                lr.*,
                c.course_name,
                cl.class_name,
                cl.schedule
            FROM leave_requests lr
            JOIN classes cl ON lr.class_id = cl.id
            JOIN courses c ON cl.course_id = c.id
            WHERE lr.student_id = :student_id
            ORDER BY lr.leave_date DESC
        ");

        $stmt->execute([
            ':student_id' => $student_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get leave requests by teacher
 */
function getLeaveRequestsByTeacher($pdo, $teacher_id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT
                lr.*,
                s.full_name AS student_name,
                c.course_name,
                cl.class_name
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            JOIN classes cl ON lr.class_id = cl.id
            JOIN courses c ON cl.course_id = c.id
            WHERE c.teacher_id = :teacher_id
            ORDER BY lr.leave_date DESC
        ");

        $stmt->execute([
            ':teacher_id' => $teacher_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get all leave requests (Admin)
 */
function getAllLeaveRequests($pdo)
{
    try {
        $stmt = $pdo->prepare("
            SELECT
                lr.*,
                s.full_name AS student_name,
                c.course_name,
                cl.class_name,
                t.full_name AS teacher_name
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            JOIN classes cl ON lr.class_id = cl.id
            JOIN courses c ON cl.course_id = c.id
            LEFT JOIN teachers t ON lr.teacher_id = t.id
            ORDER BY lr.leave_date DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Approve / Reject leave request
 */
function updateLeaveRequestStatus($pdo, $id, $status, $teacher_id)
{
    $allowed_status = ['pending', 'approved', 'rejected'];

    if (!in_array($status, $allowed_status)) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE leave_requests
            SET
                status = :status,
                teacher_id = :teacher_id
            WHERE id = :id
        ");

        return $stmt->execute([
            ':status' => $status,
            ':teacher_id' => $teacher_id,
            ':id' => $id
        ]);

    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update leave request
 * Only allowed when status is pending
 */
function updateLeaveRequest(
    $pdo,
    $id,
    $class_id,
    $reason,
    $leave_date
) {
    try {
        $stmt = $pdo->prepare("
            UPDATE leave_requests
            SET
                class_id = :class_id,
                reason = :reason,
                leave_date = :leave_date
            WHERE id = :id
            AND status = 'pending'
        ");

        return $stmt->execute([
            ':class_id' => $class_id,
            ':reason' => $reason,
            ':leave_date' => $leave_date,
            ':id' => $id
        ]);

    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete leave request
 * Only delete pending requests
 */
function deleteLeaveRequest($pdo, $id)
{
    try {
        $stmt = $pdo->prepare("
            DELETE FROM leave_requests
            WHERE id = :id
            AND status = 'pending'
        ");

        return $stmt->execute([
            ':id' => $id
        ]);

    } catch (PDOException $e) {
        return false;
    }
}

?>