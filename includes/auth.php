<?php
// Session/authentication helper functions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user']);
}

// Get current user info (array or null)
function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

// Require login (redirect or exit if not logged in)
function require_login() {
    if (!is_logged_in()) {
        header('Location: /views/login.php');
        exit();
    }
}

// Require specific role (redirect or exit if not authorized)
function require_role($role) {
    if (!is_logged_in() || $_SESSION['user']['role'] !== $role) {
        header('Location: /views/login.php');
        exit();
    }
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
} 