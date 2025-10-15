<?php
/**
 * Polling-based Updates Endpoint (Alternative to SSE for Hostinger)
 * This works better on shared hosting with script timeout restrictions
 */

session_start();

// Quick auth check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'lgu_staff'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // Get all stats in one query
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_disasters,
            COUNT(CASE WHEN status != 'COMPLETED' THEN 1 END) as active_disasters,
            COUNT(CASE WHEN priority = 'critical' THEN 1 END) as critical_disasters,
            (SELECT COUNT(*) FROM users WHERE status = 'need_help') as users_need_help,
            (SELECT COUNT(*) FROM users WHERE status = 'safe') as users_safe,
            (SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE) as unread_notifications
        FROM disasters
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get latest disaster (if any new ones)
    $lastCheckTime = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-5 minutes'));
    
    $newDisasterStmt = $pdo->prepare("
        SELECT COUNT(*) as new_count
        FROM disasters 
        WHERE created_at > ?
    ");
    $newDisasterStmt->execute([$lastCheckTime]);
    $newData = $newDisasterStmt->fetch(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'stats' => [
            'total_disasters' => (int)$stats['total_disasters'],
            'active_disasters' => (int)$stats['active_disasters'],
            'critical_disasters' => (int)$stats['critical_disasters'],
            'users_need_help' => (int)$stats['users_need_help'],
            'users_safe' => (int)$stats['users_safe'],
            'notification_count' => (int)$stats['unread_notifications']
        ],
        'new_reports' => (int)$newData['new_count'],
        'timestamp' => time(),
        'server_time' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Poll updates error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch updates'
    ]);
}
?>
