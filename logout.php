<?php
session_start();
require_once 'config/database.php';

// Log logout activity
if (isset($_SESSION['user_id'])) {
    try {
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, 'logout', ?, ?)");
        $log_stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
    } catch (Exception $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header('Location: login.php?logged_out=1');
exit;
?>