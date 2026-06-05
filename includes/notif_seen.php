<?php
require_once dirname(__DIR__) . '/config.php';

$now = date('Y-m-d H:i:s');

// Save in session
$_SESSION['notif_last_seen'] = $now;

// Also save in database for persistence
if (isset($_SESSION['user_id'])) {
    try {
        $pdo->prepare('UPDATE users SET notif_last_seen = ? WHERE id = ?')
            ->execute([$now, (int) $_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Column might not exist yet, try adding it
        try {
            $pdo->exec('ALTER TABLE users ADD COLUMN notif_last_seen DATETIME DEFAULT NULL');
            $pdo->prepare('UPDATE users SET notif_last_seen = ? WHERE id = ?')
                ->execute([$now, (int) $_SESSION['user_id']]);
        } catch (PDOException $e2) {
            // ignore
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
