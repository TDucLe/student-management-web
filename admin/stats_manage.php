<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'System Statistics';

$roleData = $pdo->query("SELECT role, COUNT(*) AS cnt FROM users WHERE deleted_at IS NULL GROUP BY role")->fetchAll();
$labels = array_column($roleData, 'role');
$values = array_map('intval', array_column($roleData, 'cnt'));

$monthly = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM users WHERE deleted_at IS NULL
    GROUP BY ym ORDER BY ym DESC LIMIT 6
")->fetchAll();
$monthLabels = array_reverse(array_column($monthly, 'ym'));
$monthValues = array_reverse(array_map('intval', array_column($monthly, 'cnt')));

renderHeader($pageTitle, $user);
?>
<div class="stat-grid">
    <div class="stat-card"><div class="label">Total users</div><div class="value"><?= array_sum($values) ?></div></div>
    <div class="stat-card"><div class="label">Courses</div><div class="value"><?= (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn() ?></div></div>
    <div class="stat-card"><div class="label">Classes</div><div class="value"><?= (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn() ?></div></div>
    <div class="stat-card"><div class="label">Submissions</div><div class="value"><?= (int) $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn() ?></div></div>
</div>
<div class="two-col">
    <div class="card">
        <h3>Users by role</h3>
        <div class="chart-box">
            <canvas id="roleChart" data-labels='<?= json_encode(array_values($labels)) ?>'
                data-values='<?= json_encode(array_map('intval', $values)) ?>'></canvas>
        </div>
    </div>
    <div class="card">
        <h3>New registrations (monthly)</h3>
        <div class="chart-box">
            <canvas id="statsChart" data-labels='<?= json_encode($monthLabels) ?>'
                data-values='<?= json_encode($monthValues) ?>' data-label="Users"></canvas>
        </div>
    </div>
</div>
<?php renderFooter(); ?>
