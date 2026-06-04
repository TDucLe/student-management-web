<?php
require_once dirname(__DIR__) . '/config.php';
requireLogout();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login_input === '') {
        $error = 'Username or email is required.';
    } elseif ($password === '') {
        $error = 'Password is required.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role FROM users WHERE (username = ? OR email = ?) AND deleted_at IS NULL');
            $stmt->execute([$login_input, $login_input]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($password, $row['password_hash'])) {
                $error = 'Invalid username/email or password.';
            } else {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'] ?? '';
                $_SESSION['role'] = $row['role'];
                header('Location: ' . app_path('index.php'));
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Database error. Ensure schema is up to date (email column on users).';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Student Management</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(app_path('frontend/css/style.css')) ?>">
    <style>:root { --bg-image: url('<?= htmlspecialchars(app_path('background.jpg')) ?>'); }</style>
    <script src="<?= htmlspecialchars(app_path('frontend/js/validation.js')) ?>" defer></script>
</head>
<body class="role-guest auth-page" style="background-image: var(--bg-image);">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:20px;">
            <img src="<?= htmlspecialchars(app_path('logo_slogan.png')) ?>" alt="Logo" style="height:60px;object-fit:contain;" onerror="this.style.display='none'">
        </div>
        <h1>🔐 Sign in</h1>
        <p class="subtitle">Hệ thống Quản lý Sinh viên</p>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" data-validate>
            <div class="form-group">
                <label for="login_input">Username or email</label>
                <input type="text" id="login_input" name="login_input" value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p class="subtitle" style="margin-top:20px">
            <a href="register.php">Create account</a> ·
            <a href="forgot_password.php">Forgot password?</a>
        </p>
    </div>
</body>
</html>
