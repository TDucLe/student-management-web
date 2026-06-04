<?php
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'],
    ];
}

function app_path(string $file = ''): string
{
    static $base = null;
    if ($base === null) {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
        $root = realpath(APP_ROOT) ?: APP_ROOT;
        $relative = $docRoot !== ''
            ? str_replace('\\', '/', str_replace($docRoot, '', $root))
            : '';
        $base = rtrim($relative, '/') . '/';
        if ($base === '/') {
            $base = '';
        }
    }

    return $base . ltrim(str_replace('\\', '/', $file), '/');
}

function auth_path(string $file): string
{
    return app_path('auth/' . ltrim($file, '/'));
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . auth_path('login.php'));
        exit();
    }
}

function requireLogout(): void
{
    if (isLoggedIn()) {
        header('Location: ' . app_path('index.php'));
        exit();
    }
}

function requireRole(string ...$roles): void
{
    requireLogin();
    $user = getCurrentUser();
    if ($user === null || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function getTeacherId(PDO $pdo, int $userId, string $username = ''): int
{
    $stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? AND deleted_at IS NULL');
    $stmt->execute([$userId]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    $code = 'TCH' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);
    $pdo->prepare('INSERT INTO teachers (user_id, teacher_code, full_name, department) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $code, $username ?: 'Teacher', 'General']);
    return (int) $pdo->lastInsertId();
}

function getStudentId(PDO $pdo, int $userId, string $username = ''): int
{
    $stmt = $pdo->prepare('SELECT id FROM students WHERE user_id = ? AND deleted_at IS NULL');
    $stmt->execute([$userId]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    $code = 'STU' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);
    $pdo->prepare('INSERT INTO students (user_id, student_code, full_name) VALUES (?, ?, ?)')
        ->execute([$userId, $code, $username ?: 'Student']);
    return (int) $pdo->lastInsertId();
}

function getNavItems(string $role): array
{
    $base = app_path();
    $items = [
        ['label' => t('nav.dashboard'), 'url' => $base . 'index.php', 'icon' => 'home'],
    ];

    if ($role === 'admin') {
        $items = array_merge($items, [
            ['label' => t('nav.users'), 'url' => $base . 'admin/users_manage.php', 'icon' => 'users'],
            ['label' => t('nav.students'), 'url' => $base . 'admin/students_manage.php', 'icon' => 'user'],
            ['label' => t('nav.teachers'), 'url' => $base . 'admin/teachers_manage.php', 'icon' => 'user'],
            ['label' => t('nav.courses'), 'url' => $base . 'admin/courses_manage.php', 'icon' => 'book'],
            ['label' => t('nav.classes'), 'url' => $base . 'admin/classes_manage.php', 'icon' => 'class'],
            ['label' => t('nav.rooms'), 'url' => $base . 'admin/rooms_manage.php', 'icon' => 'room'],
            ['label' => t('nav.semesters'), 'url' => $base . 'admin/semesters_manage.php', 'icon' => 'calendar'],
            ['label' => t('nav.attendance'), 'url' => $base . 'admin/attendance_manage.php', 'icon' => 'check'],
            ['label' => t('nav.grades'), 'url' => $base . 'admin/grades_manage.php', 'icon' => 'chart'],
            ['label' => t('nav.assignments'), 'url' => $base . 'admin/assignments_manage.php', 'icon' => 'task'],
            ['label' => t('nav.leave'), 'url' => $base . 'admin/leave_manage.php', 'icon' => 'mail'],
            ['label' => t('nav.schedule'), 'url' => $base . 'admin/schedule_manage.php', 'icon' => 'calendar'],
            ['label' => t('nav.stats'), 'url' => $base . 'admin/stats_manage.php', 'icon' => 'stats'],
        ]);
    } elseif ($role === 'teacher') {
        $items = array_merge($items, [
            ['label' => t('nav.classes'), 'url' => $base . 'teacher/classes.php', 'icon' => 'class'],
            ['label' => t('nav.attendance'), 'url' => $base . 'teacher/attendance.php', 'icon' => 'check'],
            ['label' => t('nav.grades'), 'url' => $base . 'teacher/grades.php', 'icon' => 'chart'],
            ['label' => t('nav.assignments'), 'url' => $base . 'teacher/assignments.php', 'icon' => 'task'],
            ['label' => t('nav.schedule'), 'url' => $base . 'teacher/schedule.php', 'icon' => 'calendar'],
            ['label' => t('nav.leave'), 'url' => $base . 'teacher/leave_approval.php', 'icon' => 'mail'],
        ]);
    } elseif ($role === 'student') {
        $items = array_merge($items, [
            ['label' => t('nav.grades'), 'url' => $base . 'student/grades_view.php', 'icon' => 'chart'],
            ['label' => t('nav.classes'), 'url' => $base . 'student/classes_view.php', 'icon' => 'class'],
            ['label' => t('nav.attendance'), 'url' => $base . 'student/attendance_view.php', 'icon' => 'check'],
            ['label' => t('nav.assignments'), 'url' => $base . 'student/assignments_view.php', 'icon' => 'task'],
            ['label' => t('nav.schedule'), 'url' => $base . 'student/schedule_view.php', 'icon' => 'calendar'],
            ['label' => t('nav.leave'), 'url' => $base . 'student/leave_request.php', 'icon' => 'mail'],
            ['label' => t('nav.profile'), 'url' => $base . 'student/profile.php', 'icon' => 'user'],
        ]);
    }

    return $items;
}

function fetchUserNotifications(PDO $pdo, int $userId, int $limit = 20): array
{
    $stmt = $pdo->prepare('SELECT id, type, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function renderHeader(string $pageTitle, ?array $user = null): void
{
    global $pdo;
    $user = $user ?? getCurrentUser();
    $navItems = getNavItems($user['role'] ?? '');
    $flash = getFlash();
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $notifications = [];
    if ($user && isset($pdo)) {
        $notifications = fetchUserNotifications($pdo, $user['id']);
    }
    $logoUrl = app_path('logo_slogan.png');
    $bgUrl = app_path('background.jpg');

    require APP_ROOT . '/frontend/templates/header.php';
}

function renderFooter(): void
{
    require APP_ROOT . '/frontend/templates/footer.php';
}

function renderPage(string $pageTitle, callable $content): void
{
    renderHeader($pageTitle);
    $content();
    renderFooter();
}
