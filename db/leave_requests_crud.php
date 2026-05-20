<?php
// CRUD operations for leave_requests table

/**
 * Create a new leave request
 * Status is 'pending' by default in the database
 */
function createLeaveRequest($pdo, $student_id, $class_id, $reason, $date) {
    $stmt = $pdo->prepare("INSERT INTO leave_requests (student_id, class_id, reason, date, status) VALUES (:student_id, :class_id, :reason, :date, 'pending')");
    return $stmt->execute([
        ':student_id' => $student_id,
        ':class_id' => $class_id,
        ':reason' => $reason,
        ':date' => $date
    ]);
}

/**
 * Get a specific leave request by ID
 */
function getLeaveRequestById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all leave requests for a specific student
 */
function getLeaveRequestsByStudent($pdo, $student_id) {
    $stmt = $pdo->prepare("
        SELECT lr.*, c.name as course_name, cl.schedule 
        FROM leave_requests lr
        JOIN classes cl ON lr.class_id = cl.id
        JOIN courses c ON cl.course_id = c.id
        WHERE lr.student_id = :student_id
        ORDER BY lr.date DESC
    ");
    $stmt->execute([':student_id' => $student_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all leave requests for classes taught by a specific teacher
 */
function getLeaveRequestsByTeacher($pdo, $teacher_id) {
    $stmt = $pdo->prepare("
        SELECT lr.*, s.name as student_name, c.name as course_name 
        FROM leave_requests lr
        JOIN students s ON lr.student_id = s.id
        JOIN classes cl ON lr.class_id = cl.id
        JOIN courses c ON cl.course_id = c.id
        WHERE c.teacher_id = :teacher_id
        ORDER BY lr.date DESC
    ");
    $stmt->execute([':teacher_id' => $teacher_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all leave requests (for admin)
 */
function getAllLeaveRequests($pdo) {
    $stmt = $pdo->prepare("
        SELECT lr.*, s.name as student_name, c.name as course_name, t.name as teacher_name
        FROM leave_requests lr
        JOIN students s ON lr.student_id = s.id
        JOIN classes cl ON lr.class_id = cl.id
        JOIN courses c ON cl.course_id = c.id
        LEFT JOIN teachers t ON lr.teacher_id = t.id
        ORDER BY lr.date DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update the status of a leave request (approved/rejected) and record the teacher who processed it
 */
function updateLeaveRequestStatus($pdo, $id, $status, $teacher_id) {
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = :status, teacher_id = :teacher_id WHERE id = :id");
    return $stmt->execute([
        ':status' => $status,
        ':teacher_id' => $teacher_id,
        ':id' => $id
    ]);
}

/**
 * Update a leave request (usually allowed only if it is still 'pending')
 */
function updateLeaveRequest($pdo, $id, $class_id, $reason, $date) {
    $stmt = $pdo->prepare("UPDATE leave_requests SET class_id = :class_id, reason = :reason, date = :date WHERE id = :id AND status = 'pending'");
    return $stmt->execute([
        ':class_id' => $class_id,
        ':reason' => $reason,
        ':date' => $date,
        ':id' => $id
    ]);
}

/**
 * Delete a leave request
 */
function deleteLeaveRequest($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}
?>
