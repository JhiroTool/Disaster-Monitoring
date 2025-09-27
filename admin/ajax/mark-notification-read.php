<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$notification_id = intval($_POST['notification_id']);

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating notification'
    ]);
}
?>