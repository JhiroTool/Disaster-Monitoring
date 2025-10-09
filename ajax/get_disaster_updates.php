<?php
/**
 * AJAX Endpoint: Get Real-time Disaster Updates
 * Method: GET
 * Returns real-time updates for a specific disaster report
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once('../config/database.php');

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if tracking ID is provided
if (!isset($_GET['tracking_id']) || empty(trim($_GET['tracking_id']))) {
    $response['message'] = 'Tracking ID is required.';
    echo json_encode($response);
    exit;
}

$tracking_id = sanitizeInput($_GET['tracking_id']);

try {
    // First, get the disaster_id from tracking_id
    $disaster_stmt = $pdo->prepare("
        SELECT disaster_id, status, priority, severity_level
        FROM disasters
        WHERE tracking_id = ?
        LIMIT 1
    ");
    $disaster_stmt->execute([$tracking_id]);
    $disaster = $disaster_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$disaster) {
        $response['message'] = 'Report not found.';
        echo json_encode($response);
        exit;
    }
    
    // Get the latest updates
    $updates_stmt = $pdo->prepare("
        SELECT 
            du.*,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            u.role as user_role,
            DATE_FORMAT(du.created_at, '%M %d, %Y at %h:%i %p') as formatted_date,
            UNIX_TIMESTAMP(du.created_at) as timestamp
        FROM disaster_updates du
        LEFT JOIN users u ON du.user_id = u.user_id
        WHERE du.disaster_id = ?
        ORDER BY du.created_at DESC
        LIMIT 20
    ");
    $updates_stmt->execute([$disaster['disaster_id']]);
    $updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get update count
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM disaster_updates
        WHERE disaster_id = ?
    ");
    $count_stmt->execute([$disaster['disaster_id']]);
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['message'] = 'Updates retrieved successfully.';
    $response['data'] = [
        'disaster' => $disaster,
        'updates' => $updates,
        'update_count' => $count_result['total'] ?? 0,
        'latest_update_time' => !empty($updates) ? $updates[0]['timestamp'] : null
    ];
    
} catch (PDOException $e) {
    error_log("Database error in get_disaster_updates.php: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log("Error in get_disaster_updates.php: " . $e->getMessage());
    $response['message'] = 'An error occurred.';
}

echo json_encode($response);
exit;
