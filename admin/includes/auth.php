<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Function to check if user has required role
function hasRole($required_roles) {
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }
    return in_array($_SESSION['role'] ?? '', $required_roles);
}

// Function to check if user is admin
function isAdmin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}

// Function to check if user is LGU admin
function isLguAdmin() {
    return in_array($_SESSION['role'] ?? '', ['admin', 'lgu_admin']);
}

// Function to get user's full name
function getUserName() {
    return ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
}

// Function to get user's initials
function getUserInitials() {
    $first = $_SESSION['first_name'] ?? '';
    $last = $_SESSION['last_name'] ?? '';
    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}

// Auto-logout after inactivity (optional)
$timeout_duration = 3600; // 1 hour
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();
?>