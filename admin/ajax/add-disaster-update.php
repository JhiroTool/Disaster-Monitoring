<?php
/**
 * AJAX Endpoint: Add Disaster Update
 * Method: POST
 * Add updates/comments to disasters in real-time
 */

session_start();
header('Content-Type: application/json');

require_once('../../config/database.php');
require_once('../includes/auth.php');

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'lgu_staff'])) {
    $response['message'] = 'Unauthorized access.';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// Validate input
if (!isset($_POST['disaster_id']) || !isset($_POST['update_text'])) {
    $response['message'] = 'Missing required parameters.';
    echo json_encode($response);
    exit;
}

$disaster_id = intval($_POST['disaster_id']);
$update_text = trim($_POST['update_text']);
$update_type = sanitizeInput($_POST['update_type'] ?? 'general');
$title = sanitizeInput($_POST['title'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($update_text)) {
    $response['message'] = 'Update text cannot be empty.';
    echo json_encode($response);
    exit;
}

// Validate update type
$valid_types = ['general', 'status_change', 'assignment', 'resource_deployed', 'situation_update', 'completion'];
if (!in_array($update_type, $valid_types)) {
    $update_type = 'general';
}

try {
    // Verify disaster exists
    $stmt = $pdo->prepare("SELECT tracking_id, disaster_name FROM disasters WHERE disaster_id = ?");
    $stmt->execute([$disaster_id]);
    $disaster = $stmt->fetch();
    
    if (!$disaster) {
        throw new Exception('Disaster not found.');
    }
    
    // Get user info
    $user_stmt = $pdo->prepare("
        SELECT CONCAT(first_name, ' ', last_name) as full_name, role 
        FROM users 
        WHERE user_id = ?
    ");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();
    
    // If no title provided, generate one
    if (empty($title)) {
        $title = match($update_type) {
            'status_change' => 'Status Update',
            'assignment' => 'Assignment Change',
            'resource_deployed' => 'Resources Deployed',
            'situation_update' => 'Situation Update',
            'completion' => 'Completion Update',
            default => 'Update'
        };
    }
    
    // Insert update
    $insert_stmt = $pdo->prepare("
        INSERT INTO disaster_updates 
        (disaster_id, user_id, update_type, title, description, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insert_stmt->execute([
        $disaster_id,
        $user_id,
        $update_type,
        $title,
        $update_text
    ]);
    
    $update_id = $pdo->lastInsertId();
    
    // Update disaster's updated_at timestamp
    $pdo->prepare("UPDATE disasters SET updated_at = NOW() WHERE disaster_id = ?")
        ->execute([$disaster_id]);
    
    // Log activity
    $log_stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, table_name, record_id, new_values, ip_address, user_agent)
        VALUES (?, 'insert', 'disaster_updates', ?, ?, ?, ?)
    ");
    $log_stmt->execute([
        $user_id,
        $update_id,
        json_encode(['update_text' => $update_text, 'type' => $update_type]),
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $response['success'] = true;
    $response['message'] = 'Update added successfully!';
    $response['data'] = [
        'update_id' => $update_id,
        'disaster_id' => $disaster_id,
        'tracking_id' => $disaster['tracking_id'],
        'title' => $title,
        'update_text' => $update_text,
        'update_type' => $update_type,
        'user_name' => $user['full_name'],
        'user_role' => $user['role'],
        'created_at' => date('Y-m-d H:i:s'),
        'formatted_date' => date('F d, Y \a\t h:i A')
    ];
    
} catch (Exception $e) {
    error_log("Add update error: " . $e->getMessage());
    $response['message'] = 'Error adding update: ' . $e->getMessage();
}

echo json_encode($response);
