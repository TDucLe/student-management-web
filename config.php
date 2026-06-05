<?php
define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/db/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once APP_ROOT . '/includes/security_helper.php';
require_once APP_ROOT . '/includes/i18n.php';
i18n_init();
require_once APP_ROOT . '/includes/grades_helper.php';
require_once APP_ROOT . '/includes/notification_helper.php';
require_once APP_ROOT . '/auth/auth_helper.php';
