<?php
require_once dirname(__DIR__) . '/config.php';
requireLogout();

$error = '';
$success = '';
$lang = lang();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValidate()) {
        $error = $lang === 'vi' ? 'Phiên làm việc hết hạn. Vui lòng thử lại.' : 'Session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($username)) {
            $error = $lang === 'vi' ? 'Tên đăng nhập là bắt buộc.' : 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $error = $lang === 'vi' ? 'Tên đăng nhập chỉ chứa chữ, số, dấu _ (3-30 ký tự).' : 'Username: 3-30 chars, letters/numbers/underscore only.';
        } elseif (empty($email)) {
            $error = $lang === 'vi' ? 'Email là bắt buộc.' : 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = $lang === 'vi' ? 'Email không hợp lệ.' : 'Invalid email format.';
        } elseif (empty($password)) {
            $error = $lang === 'vi' ? 'Mật khẩu là bắt buộc.' : 'Password is required.';
        } elseif (strlen($password) < 6) {
            $error = $lang === 'vi' ? 'Mật khẩu phải có ít nhất 6 ký tự.' : 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm_password) {
            $error = $lang === 'vi' ? 'Mật khẩu xác nhận không khớp.' : 'Passwords do not match.';
        }

        if (empty($error)) {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = $lang === 'vi' ? 'Tên đăng nhập đã tồn tại.' : 'Username already taken.';
                } else {
                    // Check if email already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = $lang === 'vi' ? 'Email đã được đăng ký.' : 'Email already registered.';
                    } else {
                        // Always register as student — only admin can change roles
                        $password_hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$username, $email, $password_hash, 'student']);
                        $uid = (int) $pdo->lastInsertId();
                        getStudentId($pdo, $uid, $username);
                        $success = $lang === 'vi' ? 'Đăng ký thành công! Bạn có thể đăng nhập.' : 'Registration successful! You can now login.';
                    }
                }
            } catch (PDOException $e) {
                $error = $lang === 'vi' ? 'Lỗi cơ sở dữ liệu.' : 'Database error.';
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
    <title><?= $lang === 'vi' ? 'Đăng ký' : 'Register' ?> — <?= htmlspecialchars(t('app_name')) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(app_path('frontend/css/style.css')) ?>">
    <style>:root { --bg-image: url('<?= htmlspecialchars(app_path('background.jpg')) ?>'); }</style>
    <script src="<?= htmlspecialchars(app_path('frontend/js/validation.js')) ?>" defer></script>
</head>
<body class="role-guest auth-page" style="background-image: var(--bg-image);">
<div class="auth-card">
    <div style="text-align:center;margin-bottom:20px;">
        <img src="<?= htmlspecialchars(app_path('logo_slogan.png')) ?>" alt="Logo" style="height:60px;object-fit:contain;" onerror="this.style.display='none'">
    </div>
    <h1>📝 <?= $lang === 'vi' ? 'Tạo tài khoản' : 'Create account' ?></h1>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="login.php" class="btn btn-primary btn-block"><?= $lang === 'vi' ? 'Đăng nhập' : 'Go to login' ?></a>
    <?php else: ?>
    <form method="POST" data-validate>
        <?= csrfField() ?>
        <div class="form-group">
            <label><?= $lang === 'vi' ? 'Tên đăng nhập' : 'Username' ?></label>
            <input name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required pattern="[a-zA-Z0-9_]{3,30}">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label><?= $lang === 'vi' ? 'Mật khẩu' : 'Password' ?></label>
            <input type="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
            <label><?= $lang === 'vi' ? 'Xác nhận mật khẩu' : 'Confirm password' ?></label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block"><?= $lang === 'vi' ? 'Đăng ký' : 'Register' ?></button>
    </form>
    <p class="subtitle" style="margin-top:16px"><a href="login.php"><?= $lang === 'vi' ? 'Đã có tài khoản?' : 'Already have an account?' ?></a></p>
    <?php endif; ?>
</div>
</body>
</html>
