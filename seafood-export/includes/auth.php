<?php
require_once 'config.php';

// Check if user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isUserLoggedIn()) {
        header('Location: ' . USER_URL . 'login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . ADMIN_URL . 'index.php');
        exit();
    }
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current admin ID
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

// Check if user has permission
function hasPermission($required_role) {
    if (!isset($_SESSION['admin_role'])) return false;
    
    $role_hierarchy = [
        'staff' => 1,
        'quality_controller' => 2,
        'manager' => 3,
        'super_admin' => 4
    ];
    
    return $role_hierarchy[$_SESSION['admin_role']] >= $role_hierarchy[$required_role];
}
?>