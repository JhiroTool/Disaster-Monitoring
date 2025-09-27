<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Mark all notifications read error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating notifications'
    ]);
}
?>