<?php
require_once dirname(__DIR__) . '/config.php';
requireLogout();
$lang = lang();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'vi' ? 'Quên mật khẩu' : 'Forgot Password' ?> — <?= htmlspecialchars(t('app_name')) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(app_path('frontend/css/style.css')) ?>">
    <style>:root { --bg-image: url('<?= htmlspecialchars(app_path('background.jpg')) ?>'); }</style>
</head>
<body class="role-guest auth-page" style="background-image: var(--bg-image);">
<div class="auth-card">
    <div style="text-align:center;margin-bottom:20px;">
        <img src="<?= htmlspecialchars(app_path('logo_slogan.png')) ?>" alt="Logo" style="height:60px;object-fit:contain;" onerror="this.style.display='none'">
    </div>
    <h1>🔑 <?= $lang === 'vi' ? 'Quên mật khẩu?' : 'Forgot password?' ?></h1>
    <div class="alert alert-info" style="text-align:left;">
        <p style="margin:0 0 12px;font-weight:600;">
            <?= $lang === 'vi' ? 'Vui lòng liên hệ Phòng Quản lý để được hỗ trợ đặt lại mật khẩu:' : 'Please contact the Management Office to reset your password:' ?>
        </p>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <div>
                <strong>🏢 <?= $lang === 'vi' ? 'Phòng Quản lý Đào tạo' : 'Academic Affairs Office' ?></strong>
            </div>
            <div>📍 <?= $lang === 'vi' ? '144 Xuân Thủy, Cầu Giấy, Hà Nội' : '144 Xuan Thuy, Cau Giay, Hanoi' ?></div>
            <div>📞 (024) 3754 7461</div>
            <div>✉️ info@is.vnu.edu.vn</div>
            <div>🕐 <?= $lang === 'vi' ? 'Giờ làm việc: T2 – T6, 8:00 – 17:00' : 'Working hours: Mon – Fri, 8:00 – 17:00' ?></div>
        </div>
    </div>
    <div style="background:var(--bg-glass);border-radius:var(--radius);padding:16px;margin-top:16px;border:1px solid var(--border);">
        <p style="margin:0;font-size:0.92rem;color:var(--text-muted);">
            <strong>📋 <?= $lang === 'vi' ? 'Thông tin cần chuẩn bị:' : 'Please prepare:' ?></strong>
        </p>
        <ul style="margin:8px 0 0;padding-left:20px;font-size:0.9rem;color:var(--text-muted);">
            <li><?= $lang === 'vi' ? 'Mã sinh viên / Mã giảng viên' : 'Student ID / Teacher ID' ?></li>
            <li><?= $lang === 'vi' ? 'CMND / CCCD để xác minh danh tính' : 'ID card for identity verification' ?></li>
            <li><?= $lang === 'vi' ? 'Email đã đăng ký trong hệ thống' : 'Registered email address' ?></li>
        </ul>
    </div>
    <p class="subtitle" style="margin-top:20px">
        <a href="login.php" class="btn btn-primary btn-block"><?= $lang === 'vi' ? '← Quay lại đăng nhập' : '← Back to login' ?></a>
    </p>
</div>
</body>
</html>
