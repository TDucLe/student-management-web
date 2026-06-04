<?php
require_once dirname(__DIR__) . '/config.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'User Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $pdo->prepare('UPDATE users SET deleted_at = NOW() WHERE id = ?')->execute([(int) $_POST['delete_id']]);
        flash('success', 'User deactivated.');
    } elseif (isset($_POST['add_user'])) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        if ($username && $email && strlen($password) >= 6 && in_array($role, ['admin', 'teacher', 'student'], true)) {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)')
                    ->execute([$username, $email, $hash, $role]);
                $uid = (int) $pdo->lastInsertId();
                if ($role === 'student') {
                    getStudentId($pdo, $uid, $username);
                } elseif ($role === 'teacher') {
                    getTeacherId($pdo, $uid, $username);
                }
                flash('success', 'User created.');
            } catch (PDOException $e) {
                flash('error', 'Could not create user (duplicate username/email?).');
            }
        } else {
            flash('error', 'Invalid user data.');
        }
    }
    header('Location: users_manage.php');
    exit;
}

$users = $pdo->query("SELECT id, username, email, role, created_at FROM users WHERE deleted_at IS NULL ORDER BY id DESC")->fetchAll();

renderHeader($pageTitle, $user);
?>
<div class="alert alert-info">
    Tạo <strong>student/teacher đầy đủ thông tin</strong> tại
    <a href="<?= htmlspecialchars(app_path('admin/students_manage.php')) ?>">Students</a> /
    <a href="<?= htmlspecialchars(app_path('admin/teachers_manage.php')) ?>">Teachers</a>.
    Thêm/xóa SV trong lớp: <a href="<?= htmlspecialchars(app_path('admin/classes_manage.php')) ?>">Classes → Manage</a>.
</div>
<div class="card">
    <h2>Add user (account only)</h2>
    <form method="POST" data-validate>
        <div class="form-grid">
            <div class="form-group"><label>Username</label><input name="username" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6"></div>
            <div class="form-group"><label>Role</label>
                <select name="role" required>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <button type="submit" name="add_user" class="btn btn-primary">Create user</button>
    </form>
</div>
<div class="card">
    <h2>All users</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>#<?= (int) $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td>
                    <?php if ((int) $u['id'] !== $user['id']): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Deactivate user?');">
                        <input type="hidden" name="delete_id" value="<?= (int) $u['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Deactivate</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderFooter(); ?>
