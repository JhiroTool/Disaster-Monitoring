<?php
/**
 * AJAX Endpoint: Update Disaster Status
 * Method: POST
 * Admin fast status updates without page refresh
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

// Check if user is admin or LGU
if (!in_array($_SESSION['role'] ?? '', ['admin', 'lgu_staff'])) {
    $response['message'] = 'Unauthorized access.';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// Validate input
if (!isset($_POST['disaster_id']) || !isset($_POST['status'])) {
    $response['message'] = 'Missing required parameters.';
    echo json_encode($response);
    exit;
}

$disaster_id = intval($_POST['disaster_id']);
$new_status = sanitizeInput($_POST['status']);
$comments = sanitizeInput($_POST['comments'] ?? '');
$user_id = $_SESSION['user_id'];

// Validate status
$valid_statuses = ['ON GOING', 'IN PROGRESS', 'COMPLETED'];
if (!in_array($new_status, $valid_statuses)) {
    $response['message'] = 'Invalid status value.';
    echo json_encode($response);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get current disaster info
    $stmt = $pdo->prepare("SELECT status, tracking_id, disaster_name FROM disasters WHERE disaster_id = ?");
    $stmt->execute([$disaster_id]);
    $disaster = $stmt->fetch();
    
    if (!$disaster) {
        throw new Exception('Disaster not found.');
    }
    
    $old_status = $disaster['status'];
    
    // Update disaster status
    $update_stmt = $pdo->prepare("
        UPDATE disasters 
        SET status = ?, updated_at = NOW()
        WHERE disaster_id = ?
    ");
    $update_stmt->execute([$new_status, $disaster_id]);
    
    // Set acknowledged timestamp if status is IN PROGRESS
    if ($new_status === 'IN PROGRESS' && $old_status === 'ON GOING') {
        $ack_stmt = $pdo->prepare("
            UPDATE disasters 
            SET acknowledged_at = NOW() 
            WHERE disaster_id = ? AND acknowledged_at IS NULL
        ");
        $ack_stmt->execute([$disaster_id]);
    }
    
    // Set resolved timestamp if status is COMPLETED
    if ($new_status === 'COMPLETED') {
        $resolve_stmt = $pdo->prepare("
            UPDATE disasters 
            SET resolved_at = NOW() 
            WHERE disaster_id = ? AND resolved_at IS NULL
        ");
        $resolve_stmt->execute([$disaster_id]);
    }
    
    // Add update entry to disaster_updates
    $user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $update_title = "Status updated to " . str_replace('_', ' ', $new_status);
    $update_description = $comments ?: "Status changed from $old_status to $new_status by $user_name";
    
    $update_insert = $pdo->prepare("
        INSERT INTO disaster_updates (disaster_id, user_id, update_type, title, description, created_at)
        VALUES (?, ?, 'status_change', ?, ?, NOW())
    ");
    $update_insert->execute([
        $disaster_id,
        $user_id,
        $update_title,
        $update_description
    ]);
    
    // Log activity
    $log_stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, 'update', 'disasters', ?, ?, ?, ?, ?)
    ");
    $log_stmt->execute([
        $user_id,
        $disaster_id,
        json_encode(['status' => $old_status]),
        json_encode(['status' => $new_status]),
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $pdo->commit();
    
    $response['success'] = true;
    $response['message'] = 'Status updated successfully!';
    $response['data'] = [
        'disaster_id' => $disaster_id,
        'tracking_id' => $disaster['tracking_id'],
        'old_status' => $old_status,
        'new_status' => $new_status,
        'updated_by' => $user_name,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Status update error: " . $e->getMessage());
    $response['message'] = 'Error updating status: ' . $e->getMessage();
}

echo json_encode($response);
