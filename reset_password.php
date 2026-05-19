<?php
require_once 'config.php';
requireLogout(); // Redirect to home if already logged in

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$valid_token = false;
$user_email = '';

// Validate token from URL
if (empty($token)) {
    $error = 'Invalid reset link. Token is missing.';
} else {
    try {
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Invalid or expired reset link.';
        } else {
            $valid_token = true;
            $user_email = $user['email'];
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Process password reset form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($new_password)) {
        $error = 'New password is required.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    }

    if (empty($error)) {
        try {
            // Hash new password and update
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE password_reset_token = ?");
            $stmt->execute([$password_hash, $token]);

            $success = true;
            $valid_token = false;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Student Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.2);
        }

        .error {
            background-color: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }

        .success-message {
            background-color: #efe;
            color: #3c3;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3c3;
            text-align: center;
        }

        .success-message h2 {
            margin-bottom: 10px;
            font-size: 22px;
        }

        .info-box {
            background: #f0f7ff;
            padding: 12px;
            border-radius: 5px;
            border-left: 4px solid #2196F3;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
        }

        .password-requirements {
            background: #fafafa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #666;
        }

        .password-requirements h4 {
            margin-bottom: 8px;
            color: #333;
        }

        .password-requirements ul {
            margin-left: 20px;
        }

        .password-requirements li {
            margin-bottom: 4px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="success-message">
                <h2>✓ Password Reset Successful!</h2>
                <p style="margin-bottom: 15px;">Your password has been successfully changed.</p>
                <p>You can now login with your new password.</p>
            </div>

            <div class="form-footer">
                <a href="login.php" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: 600;">Go to Login →</a>
            </div>

        <?php elseif (!$valid_token && $error): ?>
            <h1>Reset Password</h1>
            <p class="subtitle">Password Reset Failed</p>

            <div class="error"><?php echo htmlspecialchars($error); ?></div>

            <div class="info-box">
                <strong>What to do next:</strong>
                <ul style="margin-left: 15px; margin-top: 10px;">
                    <li>Go back to the login page and try requesting a new password reset</li>
                    <li>Make sure you're using the most recent reset link</li>
                    <li>Reset links expire after 1 hour</li>
                </ul>
            </div>

            <div class="form-footer">
                <a href="forgot_password.php">← Request New Reset Link</a> | 
                <a href="login.php">Back to Login</a>
            </div>

        <?php else: ?>
            <h1>Create New Password</h1>
            <p class="subtitle">Enter your new password below</p>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="info-box">
                <strong>📧 Resetting for:</strong> <?php echo htmlspecialchars($user_email); ?>
            </div>

            <form method="POST">
                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li>✓ At least 6 characters long</li>
                        <li>✓ Must match confirmation</li>
                    </ul>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required autofocus>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit">Reset Password</button>
            </form>

            <div class="form-footer">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
