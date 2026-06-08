<?php
require_once dirname(__DIR__) . '/config.php';
requireLogout();

$error = '';
$lang = lang();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!csrfValidate()) {
        $error = $lang === 'vi' ? 'Phiên làm việc hết hạn. Vui lòng thử lại.' : 'Session expired. Please try again.';
    } else {
        $login_input = trim($_POST['login_input'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($login_input === '') {
            $error = $lang === 'vi' ? 'Vui lòng nhập tên đăng nhập hoặc email.' : 'Username or email is required.';
        } elseif ($password === '') {
            $error = $lang === 'vi' ? 'Vui lòng nhập mật khẩu.' : 'Password is required.';
        } else {
            // Brute-force check
            $lockSec = loginLockRemaining($login_input);
            if ($lockSec > 0) {
                $mins = ceil($lockSec / 60);
                $error = $lang === 'vi'
                    ? "Tài khoản bị khóa tạm thời. Thử lại sau $mins phút."
                    : "Account temporarily locked. Try again in $mins minute(s).";
            } else {
                try {
                    $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role FROM users WHERE (username = ? OR email = ?) AND deleted_at IS NULL');
                    $stmt->execute([$login_input, $login_input]);
                    $row = $stmt->fetch();

                    if (!$row || !password_verify($password, $row['password_hash'])) {
                        $remaining = 5 - loginAttemptFailed($login_input);
                        if ($remaining > 0) {
                            $error = $lang === 'vi'
                                ? "Sai tên đăng nhập hoặc mật khẩu. Còn $remaining lần thử."
                                : "Invalid credentials. $remaining attempt(s) remaining.";
                        } else {
                            $error = $lang === 'vi'
                                ? 'Tài khoản bị khóa 15 phút do nhập sai quá nhiều.'
                                : 'Account locked for 15 minutes due to too many failed attempts.';
                        }
                    } else {
                        // Success — regenerate session ID to prevent fixation
                        session_regenerate_id(true);
                        loginAttemptClear($login_input);

                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['email'] = $row['email'] ?? '';
                        $_SESSION['role'] = $row['role'];
                        header('Location: ' . app_path('index.php'));
                        exit();
                    }
                } catch (PDOException $e) {
                    $error = $lang === 'vi' ? 'Lỗi cơ sở dữ liệu.' : 'Database error.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'vi' ? 'Đăng nhập' : 'Login' ?> — <?= htmlspecialchars(t('app_name')) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(app_path('frontend/css/style.css')) ?>">
    <style>:root { --bg-image: url('<?= htmlspecialchars(app_path('background.jpg')) ?>'); }</style>
    <script src="<?= htmlspecialchars(app_path('frontend/js/validation.js')) ?>" defer></script>
</head>
<body class="role-guest auth-page" style="background-image: var(--bg-image);">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:20px;">
            <img src="<?= htmlspecialchars(app_path('logo_slogan.png')) ?>" alt="Logo" style="height:60px;object-fit:contain;" onerror="this.style.display='none'">
        </div>
        <h1>🔐 <?= $lang === 'vi' ? 'Đăng nhập' : 'Sign in' ?></h1>
        <p class="subtitle"><?= $lang === 'vi' ? 'Hệ thống Quản lý Sinh viên' : 'Student Management System' ?></p>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" data-validate>
            <?= csrfField() ?>
            <div class="form-group">
                <label for="login_input"><?= $lang === 'vi' ? 'Tên đăng nhập hoặc email' : 'Username or email' ?></label>
                <input type="text" id="login_input" name="login_input" value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password"><?= $lang === 'vi' ? 'Mật khẩu' : 'Password' ?></label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= $lang === 'vi' ? 'Đăng nhập' : 'Login' ?></button>
        </form>
        <p class="subtitle" style="margin-top:20px">
            <a href="forgot_password.php"><?= $lang === 'vi' ? 'Quên mật khẩu?' : 'Forgot password?' ?></a>
        </p>
    </div>
</body>
</html>
