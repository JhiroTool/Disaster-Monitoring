<?php
/**
 * AJAX Endpoint: Get Disaster Details
 * Method: GET
 * Fetch complete disaster information
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
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access.';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// Validate input
if (!isset($_GET['disaster_id'])) {
    $response['message'] = 'Missing disaster ID.';
    echo json_encode($response);
    exit;
}

$disaster_id = intval($_GET['disaster_id']);

try {
    // Fetch disaster details
    $stmt = $pdo->prepare("
        SELECT 
            d.*,
            dt.type_name,
            dt.description as type_description,
            l.lgu_name,
            l.contact_phone as lgu_phone,
            l.email as lgu_email,
            CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
            u.phone as assigned_user_phone,
            u.email as assigned_user_email,
            CONCAT(reporter.first_name, ' ', reporter.last_name) as reporter_name,
            TIMESTAMPDIFF(HOUR, d.reported_at, NOW()) as hours_elapsed,
            TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at) as response_time_hours,
            TIMESTAMPDIFF(HOUR, d.acknowledged_at, d.resolved_at) as resolution_time_hours,
            DATE_FORMAT(d.reported_at, '%M %d, %Y at %h:%i %p') as formatted_reported,
            DATE_FORMAT(d.acknowledged_at, '%M %d, %Y at %h:%i %p') as formatted_acknowledged,
            DATE_FORMAT(d.resolved_at, '%M %d, %Y at %h:%i %p') as formatted_resolved
        FROM disasters d
        LEFT JOIN disaster_types dt ON d.type_id = dt.type_id
        LEFT JOIN lgus l ON d.assigned_lgu_id = l.lgu_id
        LEFT JOIN users u ON d.assigned_user_id = u.user_id
        LEFT JOIN users reporter ON d.reported_by_user_id = reporter.user_id
        WHERE d.disaster_id = ?
    ");
    $stmt->execute([$disaster_id]);
    $disaster = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$disaster) {
        throw new Exception('Disaster not found.');
    }
    
    // Parse immediate needs
    if (!empty($disaster['immediate_needs'])) {
        $needs = json_decode($disaster['immediate_needs'], true);
        $disaster['immediate_needs_array'] = is_array($needs) ? $needs : [];
    } else {
        $disaster['immediate_needs_array'] = [];
    }
    
    // Fetch updates
    $updates_stmt = $pdo->prepare("
        SELECT 
            du.*,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            u.role as user_role,
            DATE_FORMAT(du.created_at, '%M %d, %Y at %h:%i %p') as formatted_date
        FROM disaster_updates du
        LEFT JOIN users u ON du.user_id = u.user_id
        WHERE du.disaster_id = ?
        ORDER BY du.created_at DESC
    ");
    $updates_stmt->execute([$disaster_id]);
    $updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch resources if table exists
    $resources = [];
    try {
        $resources_stmt = $pdo->prepare("
            SELECT dr.*, r.resource_name, r.unit
            FROM disaster_resources dr
            JOIN resources r ON dr.resource_id = r.resource_id
            WHERE dr.disaster_id = ?
        ");
        $resources_stmt->execute([$disaster_id]);
        $resources = $resources_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Table might not exist
        $resources = [];
    }
    
    // Calculate statistics
    $stats = [
        'total_updates' => count($updates),
        'hours_since_report' => (int)$disaster['hours_elapsed'],
        'response_time_hours' => $disaster['response_time_hours'] ? (float)$disaster['response_time_hours'] : null,
        'resolution_time_hours' => $disaster['resolution_time_hours'] ? (float)$disaster['resolution_time_hours'] : null,
        'is_overdue' => $disaster['escalation_deadline'] && strtotime($disaster['escalation_deadline']) < time() && $disaster['status'] !== 'COMPLETED',
        'completion_percentage' => match($disaster['status']) {
            'ON GOING' => 25,
            'IN PROGRESS' => 60,
            'COMPLETED' => 100,
            default => 0
        }
    ];
    
    $response['success'] = true;
    $response['message'] = 'Disaster details retrieved successfully.';
    $response['data'] = [
        'disaster' => $disaster,
        'updates' => $updates,
        'resources' => $resources,
        'stats' => $stats
    ];
    
} catch (Exception $e) {
    error_log("Get disaster details error: " . $e->getMessage());
    $response['message'] = 'Error fetching disaster details: ' . $e->getMessage();
}

echo json_encode($response);
