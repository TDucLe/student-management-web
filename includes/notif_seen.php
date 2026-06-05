<?php
require_once dirname(__DIR__) . '/config.php';

// Mark all notifications as seen by updating session timestamp
$_SESSION['notif_last_seen'] = date('Y-m-d H:i:s');

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
