<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'count' => $result['count']
    ]);
    
} catch (Exception $e) {
    error_log("Get notification count error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching count'
    ]);
}
?>