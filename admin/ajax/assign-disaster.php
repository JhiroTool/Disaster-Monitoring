<?php
/**
 * AJAX Endpoint: Assign Disaster to LGU/User
 * Method: POST
 * Fast assignment without page refresh
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

// Check if user is admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    $response['message'] = 'Unauthorized access. Admin only.';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// Validate input
if (!isset($_POST['disaster_id'])) {
    $response['message'] = 'Missing disaster ID.';
    echo json_encode($response);
    exit;
}

$disaster_id = intval($_POST['disaster_id']);
$lgu_id = isset($_POST['lgu_id']) && $_POST['lgu_id'] !== '' ? intval($_POST['lgu_id']) : null;
$user_id = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? intval($_POST['user_id']) : null;
$comments = sanitizeInput($_POST['comments'] ?? '');
$admin_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    
    // Get current disaster info
    $stmt = $pdo->prepare("
        SELECT d.*, l.lgu_name, CONCAT(u.first_name, ' ', u.last_name) as assigned_user
        FROM disasters d
        LEFT JOIN lgus l ON d.assigned_lgu_id = l.lgu_id
        LEFT JOIN users u ON d.assigned_user_id = u.user_id
        WHERE d.disaster_id = ?
    ");
    $stmt->execute([$disaster_id]);
    $disaster = $stmt->fetch();
    
    if (!$disaster) {
        throw new Exception('Disaster not found.');
    }
    
    // Update assignment
    $update_stmt = $pdo->prepare("
        UPDATE disasters 
        SET assigned_lgu_id = ?, assigned_user_id = ?, updated_at = NOW()
        WHERE disaster_id = ?
    ");
    $update_stmt->execute([$lgu_id, $user_id, $disaster_id]);
    
    // Get new assignment info
    $new_lgu_name = null;
    $new_user_name = null;
    
    if ($lgu_id) {
        $lgu_stmt = $pdo->prepare("SELECT lgu_name FROM lgus WHERE lgu_id = ?");
        $lgu_stmt->execute([$lgu_id]);
        $new_lgu_name = $lgu_stmt->fetchColumn();
    }
    
    if ($user_id) {
        $user_stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE user_id = ?");
        $user_stmt->execute([$user_id]);
        $new_user_name = $user_stmt->fetchColumn();
    }
    
    // Add update entry
    $admin_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $update_title = "Assignment Updated";
    
    $assignment_details = [];
    if ($new_lgu_name) $assignment_details[] = "LGU: $new_lgu_name";
    if ($new_user_name) $assignment_details[] = "Handler: $new_user_name";
    
    $update_description = "Assigned by $admin_name";
    if (!empty($assignment_details)) {
        $update_description .= " - " . implode(', ', $assignment_details);
    }
    if ($comments) {
        $update_description .= "\nNotes: $comments";
    }
    
    $update_insert = $pdo->prepare("
        INSERT INTO disaster_updates (disaster_id, user_id, update_type, title, description, created_at)
        VALUES (?, ?, 'assignment', ?, ?, NOW())
    ");
    $update_insert->execute([
        $disaster_id,
        $admin_id,
        $update_title,
        $update_description
    ]);
    
    // Log activity
    $log_stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, 'update', 'disasters', ?, ?, ?, ?, ?)
    ");
    $log_stmt->execute([
        $admin_id,
        $disaster_id,
        json_encode([
            'lgu_id' => $disaster['assigned_lgu_id'],
            'user_id' => $disaster['assigned_user_id']
        ]),
        json_encode([
            'lgu_id' => $lgu_id,
            'user_id' => $user_id
        ]),
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $pdo->commit();
    
    $response['success'] = true;
    $response['message'] = 'Assignment updated successfully!';
    $response['data'] = [
        'disaster_id' => $disaster_id,
        'tracking_id' => $disaster['tracking_id'],
        'lgu_id' => $lgu_id,
        'lgu_name' => $new_lgu_name,
        'user_id' => $user_id,
        'user_name' => $new_user_name,
        'updated_by' => $admin_name,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Assignment error: " . $e->getMessage());
    $response['message'] = 'Error updating assignment: ' . $e->getMessage();
}

echo json_encode($response);
