<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect unauthenticated users to login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

// Redirect if not a specific role
function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header("Location: " . BASE_URL . "/404.php"); // or a 'not authorized' page
        exit();
    }
}

// Log out
function logout() {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "/login.php");
    exit();
}
?>
