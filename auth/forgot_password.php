<?php
require_once dirname(__DIR__) . '/config.php';
requireLogout();

$error = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Valid email is required.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL');
            $stmt->execute([$email]);
            if (!$stmt->fetch()) {
                $error = 'Email not found.';
            } else {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $pdo->prepare('UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE email = ?')
                    ->execute([$token, $expires, $email]);
                $reset_link = app_path('auth/reset_password.php?token=' . urlencode($token));
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
    <title>Forgot Password</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(app_path('frontend/css/style.css')) ?>">
</head>
<body class="role-guest auth-page">
<div class="auth-card">
    <h1>Reset password</h1>
    <p class="subtitle">Enter your registered email</p>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($reset_link): ?>
        <div class="alert alert-success">Reset link generated (dev mode):</div>
        <p style="word-break:break-all;font-size:0.85rem"><a href="<?= htmlspecialchars($reset_link) ?>"><?= htmlspecialchars($reset_link) ?></a></p>
    <?php else: ?>
    <form method="POST" data-validate>
        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
    </form>
    <?php endif; ?>
    <p class="subtitle" style="margin-top:16px"><a href="login.php">Back to login</a></p>
</div>
</body>
</html>
