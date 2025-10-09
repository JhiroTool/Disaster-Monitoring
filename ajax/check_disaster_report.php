<?php
/**
 * AJAX Endpoint: Check Disaster Report Status
 * Method: POST
 * Returns real-time information about a disaster report by tracking ID
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, log instead
ini_set('log_errors', 1);

// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check if database config exists
if (!file_exists('../config/database.php')) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Database configuration file not found',
        'data' => null
    ]);
    exit;
}

require_once('../config/database.php');

// Check if PDO connection exists
if (!isset($pdo)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Database connection failed',
        'data' => null
    ]);
    exit;
}

// Note: sanitizeInput() function is already defined in database.php

// Initialize response
$response = [
    'success' => false,
    'exists' => false,
    'message' => '',
    'data' => null
];

// Check if tracking ID is provided
if (!isset($_POST['tracking_id']) || empty(trim($_POST['tracking_id']))) {
    $response['message'] = 'Please enter a tracking ID.';
    echo json_encode($response);
    exit;
}

$tracking_id = sanitizeInput($_POST['tracking_id']);

try {
    // Fetch disaster information with related data
    $stmt = $pdo->prepare("
        SELECT 
            d.*,
            dt.type_name,
            l.lgu_name,
            l.contact_phone as lgu_phone,
            l.contact_email as lgu_email,
            CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
            u.phone as assigned_user_phone,
            DATE_FORMAT(d.reported_at, '%M %d, %Y at %h:%i %p') as formatted_date,
            DATE_FORMAT(d.acknowledged_at, '%M %d, %Y at %h:%i %p') as acknowledged_date,
            DATE_FORMAT(d.resolved_at, '%M %d, %Y at %h:%i %p') as resolved_date,
            TIMESTAMPDIFF(HOUR, d.reported_at, NOW()) as hours_elapsed
        FROM disasters d
        LEFT JOIN disaster_types dt ON d.type_id = dt.type_id
        LEFT JOIN lgus l ON d.assigned_lgu_id = l.lgu_id
        LEFT JOIN users u ON d.assigned_user_id = u.user_id
        WHERE d.tracking_id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$tracking_id]);
    $disaster = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($disaster) {
        // Report exists
        $response['success'] = true;
        $response['exists'] = true;
        $response['message'] = 'Report found successfully!';
        
        // Get updates/timeline for this disaster
        $updates_stmt = $pdo->prepare("
            SELECT 
                du.*,
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                u.role as user_role,
                DATE_FORMAT(du.created_at, '%M %d, %Y at %h:%i %p') as formatted_update_date
            FROM disaster_updates du
            LEFT JOIN users u ON du.user_id = u.user_id
            WHERE du.disaster_id = ?
            ORDER BY du.created_at DESC
            LIMIT 10
        ");
        $updates_stmt->execute([$disaster['disaster_id']]);
        $updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse immediate needs if it's JSON
        if (!empty($disaster['immediate_needs'])) {
            $needs = json_decode($disaster['immediate_needs'], true);
            $disaster['immediate_needs_array'] = is_array($needs) ? $needs : [];
        } else {
            $disaster['immediate_needs_array'] = [];
        }
        
        // Add human-readable status
        $disaster['status_display'] = match($disaster['status']) {
            'ON GOING' => 'Under Review',
            'IN PROGRESS' => 'In Progress',
            'COMPLETED' => 'Resolved',
            default => $disaster['status']
        };
        
        // Add priority badge color
        $disaster['priority_color'] = match($disaster['priority']) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'success',
            default => 'secondary'
        };
        
        // Add severity color
        $severity_prefix = explode('-', $disaster['severity_level'] ?? '')[0] ?? 'green';
        $disaster['severity_color'] = match($severity_prefix) {
            'red' => 'danger',
            'orange' => 'warning',
            'yellow' => 'info',
            'green' => 'success',
            default => 'secondary'
        };
        
        $response['data'] = [
            'disaster' => $disaster,
            'updates' => $updates,
            'update_count' => count($updates)
        ];
        
    } else {
        // Report not found
        $response['message'] = 'No report found with this tracking ID. Please check and try again.';
    }
    
} catch (PDOException $e) {
    error_log("Database error in check_disaster_report.php: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("General error in check_disaster_report.php: " . $e->getMessage());
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Clear any unexpected output
ob_end_clean();

// Return JSON response
echo json_encode($response);
exit;
