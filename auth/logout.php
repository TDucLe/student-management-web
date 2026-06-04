<?php
require_once dirname(__DIR__) . '/config.php';
session_destroy();
header('Location: ' . auth_path('login.php'));
exit();
