<?php
// auth.php - Authentication helper functions
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function checkRole($required_role) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != $required_role) {
        echo "Access denied. You need $required_role role.";
        exit();
    }
}
?>