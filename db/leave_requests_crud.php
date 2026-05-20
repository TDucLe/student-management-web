function getLeaveRequestsByStudent($pdo, $student_id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT
                lr.*,
                c.course_name,
                cl.class_name
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
            WHERE cl.teacher_id = :teacher_id
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