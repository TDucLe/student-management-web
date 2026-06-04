    <?php
/** @var string $pageTitle */
/** @var array $user */
/** @var array $navItems */
/** @var array|null $flash */
/** @var string $currentPath */
/** @var array $notifications */
/** @var string $logoUrl */
/** @var string $bgUrl */
$cssPath = app_path('frontend/css/style.css');
$role = htmlspecialchars($user['role'] ?? 'guest');
$workspaceKey = 'workspace.' . ($user['role'] ?? 'guest');
$notifCount = count($notifications);
$lang = lang();
$otherLang = $lang === 'vi' ? 'en' : 'vi';
$langSwitchUrl = strtok($_SERVER['REQUEST_URI'] ?? '', '?') . '?lang=' . $otherLang;

// Icon map for nav items
$iconMap = [
    'home' => '🏠',
    'users' => '👥',
    'user' => '👤',
    'book' => '📚',
    'class' => '🏫',
    'room' => '🚪',
    'calendar' => '📅',
    'check' => '✅',
    'chart' => '📊',
    'task' => '📝',
    'mail' => '📨',
    'stats' => '📈',
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars(t('app_name')) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">
    <style>:root { --bg-image: url('<?= htmlspecialchars($bgUrl) ?>'); }</style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <script src="<?= htmlspecialchars(app_path('frontend/js/validation.js')) ?>" defer></script>
    <script src="<?= htmlspecialchars(app_path('frontend/js/ajax.js')) ?>" defer></script>
    <script src="<?= htmlspecialchars(app_path('frontend/js/chart.js')) ?>" defer></script>
    <script src="<?= htmlspecialchars(app_path('frontend/js/ui.js')) ?>" defer></script>
</head>
<body class="role-<?= $role ?> has-bg">
<div class="bg-overlay" aria-hidden="true"></div>
<div class="app-shell">
    <aside class="sidebar role-panel-<?= $role ?>">
        <div class="sidebar-brand">
            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" class="brand-logo" onerror="this.style.display='none'">
            <div>
                <strong><?= htmlspecialchars(t('app_name')) ?></strong>
                <span><?= htmlspecialchars(t($workspaceKey)) ?></span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $item):
                $path = parse_url($item['url'], PHP_URL_PATH) ?: '';
                $active = ($currentPath !== '' && $path !== '' && str_ends_with($currentPath, basename($path))) ? ' active' : '';
                $icon = $iconMap[$item['icon'] ?? ''] ?? '📄';
            ?>
            <a href="<?= htmlspecialchars($item['url']) ?>" class="nav-link<?= $active ?>">
                <span class="nav-icon"><?= $icon ?></span>
                <?= htmlspecialchars($item['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="main-wrap">
        <header class="topbar">
            <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
            <div class="topbar-actions">
                <div class="lang-switch">
                    <a href="<?= htmlspecialchars($langSwitchUrl) ?>" class="btn btn-ghost btn-sm"><?= $lang === 'vi' ? '🌐 EN' : '🌐 VI' ?></a>
                </div>
                <div class="notif-wrap">
                    <button type="button" class="notif-btn" id="notifToggle" aria-label="<?= htmlspecialchars(t('notifications')) ?>">
                        <span class="notif-icon">🔔</span>
                        <?php if ($notifCount > 0): ?><span class="notif-badge"><?= $notifCount ?></span><?php endif; ?>
                    </button>
                    <div class="notif-dropdown" id="notifDropdown" hidden>
                        <div class="notif-dropdown-head">🔔 <?= htmlspecialchars(t('notifications')) ?></div>
                        <?php if (empty($notifications)): ?>
                            <p class="notif-empty"><?= htmlspecialchars(t('no_notifications')) ?></p>
                        <?php else: foreach ($notifications as $n): ?>
                            <div class="notif-item">
                                <span class="badge badge-<?= htmlspecialchars($n['type'] ?? 'general') ?>"><?= htmlspecialchars($n['type']) ?></span>
                                <p><?= htmlspecialchars($n['message']) ?></p>
                                <small><?= htmlspecialchars($n['created_at']) ?></small>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                <span class="topbar-user">👤 <?= htmlspecialchars($user['username'] ?? '') ?></span>
                <a href="<?= htmlspecialchars(app_path('auth/logout.php')) ?>" class="btn btn-gold btn-sm">🚪 <?= htmlspecialchars(t('logout')) ?></a>
            </div>
        </header>
        <main class="main-content">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'success' ? 'success' : 'info') ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>
