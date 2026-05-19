# Authentication System - Student Management

## Overview
A complete authentication system with registration, login, logout, password hashing, and secure password reset functionality for the Student Management System.

## Features
✅ **User Registration** - Register as Student, Teacher, or Admin  
✅ **Secure Login** - Username/Email and Password authentication  
✅ **Forgot Password** - Secure password reset with token-based verification  
✅ **Password Hashing** - Uses bcrypt for secure password storage  
✅ **Session Management** - Persistent user sessions  
✅ **Logout** - Secure session destruction  
✅ **Role-Based Dashboard** - Different views for each user role  
✅ **Responsive Design** - Mobile-friendly interface  

## Files Created/Modified

### Database
- **init.sql** - Updated with email field and password_hash column
  - Changed `password` → `password_hash`
  - Added `email` field (unique)
  - Added `password_reset_token` and `password_reset_expires`
  - Added `created_at` timestamp

### Core Files
1. **config.php** - Database connection and session configuration
2. **register.php** - Registration form with validation
3. **login.php** - Login form (supports both username and email)
4. **forgot_password.php** - Password reset request form
5. **reset_password.php** - Password reset form with token validation
6. **logout.php** - Session cleanup
7. **index.php** - Dashboard (role-based content)

## Setup Instructions

### 1. Database Setup
Run the updated `init.sql` file:
```sql
mysql -u root < init.sql
```

Or import via phpMyAdmin

### 2. Update Database Credentials (if needed)
Edit `config.php` lines 2-4:
```php
$host = 'localhost';      // Your host
$dbname = 'student_management'; // Your DB name
$username = 'root';        // Your DB username
$password = '';            // Your DB password
```

## Usage

### Registration Flow
1. Navigate to `http://localhost/Student%20Management/register.php`
2. Fill in the form:
   - **Username** - Unique username (will be checked against database)
   - **Email** - Valid email address (must be unique)
   - **Password** - At least 6 characters
   - **Confirm Password** - Must match password
   - **Role** - Select Student, Teacher, or Admin
3. Click Register

### Login Flow
1. Navigate to `http://localhost/Student%20Management/login.php`
2. Enter either:
   - Username + Password, OR
   - Email + Password
3. Click Login
4. You'll be redirected to the dashboard

### Forgot Password Flow
1. On login page, click **"Forgot your password?"** link
2. Enter your registered email address
3. A password reset link will be generated (valid for 1 hour)
4. Click the reset link or copy it to your browser
5. Enter your new password and confirm it
6. Password has been reset - login with your new password

### Dashboard
- View your profile information
- Role-specific features displayed
- Logout button in top-right corner

## Security Features

### Password Hashing
```php
// During registration
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// During login verification
password_verify($password, $user['password_hash'])
```

### Password Reset Security
- Token-based reset with 1-hour expiration
- Cryptographically secure token generation (`random_bytes()`)
- Tokens are cleared after successful password change
- Reset link is one-time use only

### Session Protection
- Requires login for dashboard access
- Redirects to login if session expires
- Session variables stored server-side only
- Secure logout with session destruction

### Input Validation
- Username/Email validation
- Password strength checks (min 6 characters)
- Email format validation
- SQL injection protection (PDO prepared statements)
- XSS protection (htmlspecialchars)
- CSRF protection ready for implementation

## Database Schema

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## API Functions (in config.php)

```php
// Check if user is logged in
isLoggedIn()

// Get current user info
getCurrentUser()

// Require authentication
requireLogin()

// Require logout (redirect if logged in)
requireLogout()
```

## Testing Accounts

After registration, test with:
- **Student Account**: username/email + password
- **Teacher Account**: username/email + password
- **Admin Account**: username/email + password

## File Structure
```
Student Management/
├── config.php              # Database & session config
├── db.php                 # Original DB connection
├── init.sql               # Updated database schema
├── register.php           # Registration page
├── login.php              # Login page
├── forgot_password.php    # Forgot password page
├── reset_password.php     # Password reset page
├── logout.php             # Logout handler
├── index.php              # Dashboard
└── README_AUTH.md         # This file
```

## Email Configuration

### For Development (Current Setup)
The password reset generates a clickable link that you can copy and use immediately. No email server required.

### For Production
To enable email notifications, add a `send_email.php` file with mail configuration:

```php
<?php
function sendPasswordResetEmail($email, $reset_token) {
    $reset_link = "https://yourdomain.com/reset_password.php?token=" . urlencode($reset_token);
    
    $subject = "Password Reset Request - Student Management System";
    $message = "Click the link below to reset your password:\n\n";
    $message .= $reset_link . "\n\n";
    $message .= "This link expires in 1 hour.\n";
    $message .= "If you didn't request this, please ignore this email.\n";
    
    $headers = "From: noreply@yourdomain.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    return mail($email, $subject, $message, $headers);
}
?>
```

Then update `forgot_password.php` line 37 to:
```php
sendPasswordResetEmail($email, $reset_token);
```

You may need to configure SMTP or mail() function on your server.

## Troubleshooting

### "Connection failed" error
- Check database credentials in config.php
- Ensure MySQL server is running
- Verify database name matches

### "Username already taken"
- Choose a different username
- Email must also be unique

### Password not verifying
- Password must be exactly as set during registration
- Passwords are case-sensitive

### "Invalid or expired reset link"
- Reset links expire after 1 hour
- Request a new password reset if link expired
- Ensure you're using the correct reset link

### Session not working
- Ensure `session.save_path` is writable
- Check browser cookie settings
- Clear browser cookies if issues persist

## Next Steps

Extend the system by creating:
- Email integration for password reset notifications
- User profile edit page
- Email verification on registration
- Two-factor authentication
- User role management admin panel
- Password strength meter
- Login attempt tracking and account lockout
