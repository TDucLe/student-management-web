<?php

/**
 * Generate or retrieve the current CSRF token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate the submitted CSRF token. Regenerates after validation.
 */
function csrfValidate(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if ($stored === '' || !hash_equals($stored, $submitted)) {
        return false;
    }
    // Regenerate token after successful validation
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

/**
 * Check login attempts for brute-force protection.
 * Returns true if the user is allowed to attempt login.
 */
function loginRateLimitCheck(string $identifier): bool
{
    $key = 'login_attempts_' . md5($identifier);
    $lockKey = 'login_lock_' . md5($identifier);

    // Check if locked out
    if (isset($_SESSION[$lockKey]) && $_SESSION[$lockKey] > time()) {
        return false;
    }

    return true;
}

/**
 * Record a failed login attempt. Lock after 5 failures.
 */
function loginAttemptFailed(string $identifier): int
{
    $key = 'login_attempts_' . md5($identifier);
    $lockKey = 'login_lock_' . md5($identifier);

    $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
    $attempts = $_SESSION[$key];

    if ($attempts >= 5) {
        // Lock for 15 minutes
        $_SESSION[$lockKey] = time() + (15 * 60);
        $_SESSION[$key] = 0;
    }

    return $attempts;
}

/**
 * Get remaining lockout seconds. Returns 0 if not locked.
 */
function loginLockRemaining(string $identifier): int
{
    $lockKey = 'login_lock_' . md5($identifier);
    if (isset($_SESSION[$lockKey]) && $_SESSION[$lockKey] > time()) {
        return $_SESSION[$lockKey] - time();
    }
    return 0;
}

/**
 * Clear login attempts after successful login.
 */
function loginAttemptClear(string $identifier): void
{
    $key = 'login_attempts_' . md5($identifier);
    $lockKey = 'login_lock_' . md5($identifier);
    unset($_SESSION[$key], $_SESSION[$lockKey]);
}
