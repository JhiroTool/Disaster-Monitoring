<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT n.*, d.tracking_id, dt.type_name
        FROM notifications n
        LEFT JOIN disasters d ON n.related_id = d.disaster_id
        LEFT JOIN disaster_types dt ON d.type_id = dt.type_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    error_log("Get notifications error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching notifications'
    ]);
}
?>