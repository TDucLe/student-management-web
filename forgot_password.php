<?php
require_once 'config.php';
requireLogout(); // Redirect to home if already logged in

$error = '';
$success = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // Validation
    if (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    }

    if (empty($error)) {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Email not found in our system.';
            } else {
                // Generate reset token (valid for 1 hour)
                $reset_token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token to database
                $stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE email = ?");
                $stmt->execute([$reset_token, $expires, $email]);

                $success = true;
                $step = 2;

                // In production, you would send an email here:
                // $reset_link = "http://yoursite.com/reset_password.php?token=$reset_token";
                // mail($email, "Password Reset Request", "Click here to reset your password: $reset_link");
                
                // For development, we'll display the token or reset link
            }
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
    <title>Forgot Password - Student Management System</title>
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
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3c3;
        }

        .reset-link {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border: 1px solid #667eea;
        }

        .reset-link p {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .reset-link a {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            word-break: break-all;
            font-size: 12px;
        }

        .reset-link a:hover {
            opacity: 0.9;
        }

        .info-box {
            background: #fffbf0;
            padding: 12px;
            border-radius: 5px;
            border-left: 4px solid #ff9800;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
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

        .step-indicator {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
        }

        .copy-btn:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <p class="subtitle">Enter your email to receive password reset instructions</p>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                ✓ Reset link has been generated!
            </div>

            <div class="info-box">
                <strong>📧 Email Integration:</strong> In production, a password reset email would be sent to your address. For development, use the reset link below (valid for 1 hour).
            </div>

            <?php
                // Generate the reset link for development
                $reset_token = $reset_token ?? '';
                $reset_link = "http://localhost/Student%20Management/reset_password.php?token=" . urlencode($reset_token);
            ?>

            <div class="reset-link">
                <p>📎 Password Reset Link:</p>
                <a href="<?php echo $reset_link; ?>" target="_blank"><?php echo htmlspecialchars($reset_link); ?></a>
                <button class="copy-btn" onclick="copyToClipboard('<?php echo addslashes($reset_link); ?>')">Copy Link</button>
            </div>

            <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;">
                <p style="color: #666; font-size: 13px; margin-bottom: 10px;">
                    <strong>Or click the button below to proceed:</strong>
                </p>
                <a href="<?php echo $reset_link; ?>" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: 600;">Continue to Reset Password →</a>
            </div>

            <div class="form-footer" style="margin-top: 30px;">
                Remember your password? <a href="login.php">Login here</a>
            </div>
        <?php else: ?>
            <div class="info-box">
                We'll send you a link to reset your password. Check your email and click the link within 1 hour.
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
                </div>

                <button type="submit">Send Reset Link</button>
            </form>

            <div class="form-footer">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Reset link copied to clipboard!');
            }).catch(() => {
                alert('Failed to copy. Please copy manually.');
            });
        }
    </script>
</body>
</html>
