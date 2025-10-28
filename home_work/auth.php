<?php
// auth_check.php
session_start();

function require_login($role) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Check if user has the correct role
    if ($_SESSION['role'] != $role) {
        // Log them out or send to login, as they are trying to access the wrong area
        session_destroy();
        header("Location: login.php?error=unauthorized");
        exit;
    }

    // User is logged in and has the correct role
    return true;
}
?>
