<?php
require_once 'config.php';
requireLogin(); // Redirect to login if not authenticated

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar h1 {
            font-size: 24px;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            text-align: right;
            font-size: 14px;
        }

        .user-info .username {
            font-weight: 600;
            font-size: 15px;
        }

        .role-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: white;
            color: #667eea;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .welcome-card p {
            color: #666;
            font-size: 16px;
        }

        .role-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .role-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .feature-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }

        .feature-card:hover {
            transform: translateX(5px);
        }

        .feature-card h4 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #666;
            font-size: 14px;
        }

        .user-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #333;
        }

        .detail-value {
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>Student Management System</h1>
        <div class="navbar-right">
            <div class="user-info">
                <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                <span class="role-badge"><?php echo htmlspecialchars($user['role']); ?></span>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
            <p>You are logged in as <strong><?php echo ucfirst(htmlspecialchars($user['role'])); ?></strong>.</p>
        </div>

        <div class="role-section">
            <h3><?php echo ucfirst(htmlspecialchars($user['role'])); ?> Dashboard</h3>
            
            <?php if ($user['role'] === 'student'): ?>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h4>📚 My Courses</h4>
                        <p>View and manage your enrolled courses for this semester.</p>
                    </div>
                    <div class="feature-card">
                        <h4>📋 Assignments</h4>
                        <p>Check pending assignments and submission deadlines.</p>
                    </div>
                    <div class="feature-card">
                        <h4>📊 Grades</h4>
                        <p>View your current grades and academic performance.</p>
                    </div>
                    <div class="feature-card">
                        <h4>✓ Attendance</h4>
                        <p>Track your attendance records for all courses.</p>
                    </div>
                </div>
            <?php elseif ($user['role'] === 'teacher'): ?>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h4>📚 My Classes</h4>
                        <p>Manage your assigned courses and class details.</p>
                    </div>
                    <div class="feature-card">
                        <h4>📋 Assignments</h4>
                        <p>Create and manage assignments for your students.</p>
                    </div>
                    <div class="feature-card">
                        <h4>📊 Grades</h4>
                        <p>Enter and manage student grades for assessments.</p>
                    </div>
                    <div class="feature-card">
                        <h4>✓ Attendance</h4>
                        <p>Record attendance for your classes and students.</p>
                    </div>
                </div>
            <?php elseif ($user['role'] === 'admin'): ?>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h4>👥 User Management</h4>
                        <p>Add, edit, or remove users from the system.</p>
                    </div>
                    <div class="feature-card">
                        <h4>📚 Course Management</h4>
                        <p>Manage all courses and class schedules.</p>
                    </div>
                    <div class="feature-card">
                        <h4>📊 Reports</h4>
                        <p>View system reports and analytics.</p>
                    </div>
                    <div class="feature-card">
                        <h4>⚙️ Settings</h4>
                        <p>Configure system settings and preferences.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="user-details">
                <h4>Your Profile Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Username:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Role:</span>
                    <span class="detail-value"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">User ID:</span>
                    <span class="detail-value">#<?php echo htmlspecialchars($user['id']); ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
