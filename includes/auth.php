<?php
session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /users/login.php');
        exit();
    }
}

function require_admin() {
    if (!is_admin()) {
        header('Location: /index.php');
        exit();
    }
}
?>
