<?php
require_once dirname(__DIR__) . '/config.php';
requireLogout();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validation
    if (empty($username)) {
        $error = 'Username is required.';
    } elseif (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (empty($password)) {
        $error = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (empty($role) || !in_array($role, ['admin', 'teacher', 'student'])) {
        $error = 'Invalid role selected.';
    }

    if (empty($error)) {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already taken.';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already registered.';
                } else {
                    // Hash password and insert user
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$username, $email, $password_hash, $role]);
                    $uid = (int) $pdo->lastInsertId();
                    if ($role === 'student') {
                        getStudentId($pdo, $uid, $username);
                    } elseif ($role === 'teacher') {
                        getTeacherId($pdo, $uid, $username);
                    }
                    $success = 'Registration successful! You can now login.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(app_path('frontend/css/style.css')) ?>">
    <script src="<?= htmlspecialchars(app_path('frontend/js/validation.js')) ?>" defer></script>
</head>
<body class="role-guest auth-page">
<div class="auth-card">
    <h1>Create account</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="login.php" class="btn btn-primary btn-block">Go to login</a>
    <?php else: ?>
    <form method="POST" data-validate>
        <div class="form-group"><label>Username</label><input name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Confirm password</label><input type="password" name="confirm_password" required></div>
        <div class="form-group"><label>Role</label>
            <select name="role" required>
                <option value="">Select role</option>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Register</button>
    </form>
    <p class="subtitle" style="margin-top:16px"><a href="login.php">Already have an account?</a></p>
    <?php endif; ?>
</div>
</body>
</html>
