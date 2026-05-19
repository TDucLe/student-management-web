<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
if ($user['role'] !== 'teacher') die("Access denied.");

$teacher_id = $user['id'];
$message = "";

// HANDLE APPROVAL OR REJECTION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_request'])) {
    $req_id = $_POST['request_id'];
    $action = $_POST['status_action']; // 'approved' or 'rejected'

    try {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, teacher_id = ? WHERE id = ?");
        $stmt->execute([$action, $teacher_id, $req_id]);
        $message = "<div class='success'>✅ Request status updated to " . ucfirst($action) . "!</div>";
    } catch (PDOException $e) {
        $message = "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// FETCH PENDING AND HISTORICAL LEAVE REQUESTS FOR THIS TEACHER'S CLASSES
$stmt_requests = $pdo->prepare("SELECT lr.id, s.name AS student_name, c.name AS course_name, cls.schedule, lr.reason, lr.date, lr.status 
                                FROM leave_requests lr 
                                JOIN students s ON lr.student_id = s.id 
                                JOIN classes cls ON lr.class_id = cls.id 
                                JOIN courses c ON cls.course_id = c.id 
                                WHERE c.teacher_id = ? ORDER BY lr.status DESC, lr.date ASC");
$stmt_requests->execute([$teacher_id]);
$requests = $stmt_requests->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Leave Requests Approval</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; margin: 0; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-top: 0; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; } th, td { border-bottom: 1px solid #eee; padding: 12px; text-align: left; } th { background: #f9f9f9; }
        .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #667eea; font-weight: bold; }
        .success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .btn-approve { background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; margin-right: 5px; }
        .btn-reject { background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; }
        
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; text-transform: uppercase; color: white; display: inline-block; }
        .st-pending { background-color: #f59e0b; }
        .st-approved { background-color: #10b981; }
        .st-rejected { background-color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
        <h2>✉️ Student Leave Requests Approval</h2>
        <?= $message ?>

        <?php if (count($requests) > 0): ?>
            <table>
                <tr><th>Student</th><th>Course & Class</th><th>Reason (Lý do)</th><th>Absence Date</th><th>Status</th><th>Action</th></tr>
                <?php foreach ($requests as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                        <td><?= htmlspecialchars($row['course_name']) ?><br><small style="color:#777;"><?= htmlspecialchars($row['schedule']) ?></small></td>
                        <td><em>"<?= htmlspecialchars($row['reason']) ?>"</em></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['date']))) ?></td>
                        <td><span class="status-badge st-<?= $row['status'] ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <div style="display: flex;">
                                    <form method="POST">
                                        <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="status_action" value="approved">
                                        <button type="submit" name="action_request" class="btn-approve">Approve</button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="status_action" value="rejected">
                                        <button type="submit" name="action_request" class="btn-reject">Reject</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span style="color: #999; font-size: 13px;">Processed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p style="color: #666; text-align: center; padding: 20px;">No leave requests found for your classes.</p>
        <?php endif; ?>
    </div>
</body>
</html>