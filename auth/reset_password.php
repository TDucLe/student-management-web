<?php
require_once dirname(__DIR__) . '/config.php';
requireLogout();

$error = '';
$success = false;
$token = $_GET['token'] ?? '';
$valid = false;

if ($token !== '') {
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()');
        $stmt->execute([$token]);
        $valid = (bool) $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Database error.';
    }
} else {
    $error = 'Invalid reset link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($new) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE password_reset_token = ?')
            ->execute([$hash, $token]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(app_path('frontend/css/style.css')) ?>">
    <script src="<?= htmlspecialchars(app_path('frontend/js/validation.js')) ?>" defer></script>
</head>
<body class="role-guest auth-page">
<div class="auth-card">
    <h1>New password</h1>
    <?php if ($success): ?>
        <div class="alert alert-success">Password updated. You can login now.</div>
        <a href="login.php" class="btn btn-primary btn-block">Go to login</a>
    <?php elseif (!$valid): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error ?: 'Link expired or invalid.') ?></div>
        <a href="forgot_password.php">Request new link</a>
    <?php else: ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" data-validate>
            <div class="form-group"><label>New password</label><input type="password" name="new_password" required minlength="6"></div>
            <div class="form-group"><label>Confirm</label><input type="password" name="confirm_password" required></div>
            <button type="submit" class="btn btn-primary btn-block">Update password</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
