<?php
/**
 * AJAX Endpoint: Get Filtered Disasters List
 * Method: GET
 * Fast filtering and search without page reload
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

try {
    // Get filter parameters
    $status = sanitizeInput($_GET['status'] ?? '');
    $severity = sanitizeInput($_GET['severity'] ?? '');
    $type = sanitizeInput($_GET['type'] ?? '');
    $priority = sanitizeInput($_GET['priority'] ?? '');
    $lgu_id = isset($_GET['lgu_id']) && $_GET['lgu_id'] !== '' ? intval($_GET['lgu_id']) : null;
    $search = sanitizeInput($_GET['search'] ?? '');
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($status)) {
        $where_conditions[] = "d.status = ?";
        $params[] = $status;
    }
    
    if (!empty($severity)) {
        $where_conditions[] = "d.severity_level LIKE ?";
        $params[] = $severity . '%';
    }
    
    if (!empty($type)) {
        $where_conditions[] = "d.type_id = ?";
        $params[] = $type;
    }
    
    if (!empty($priority)) {
        $where_conditions[] = "d.priority = ?";
        $params[] = $priority;
    }
    
    if ($lgu_id) {
        $where_conditions[] = "d.assigned_lgu_id = ?";
        $params[] = $lgu_id;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(d.tracking_id LIKE ? OR d.disaster_name LIKE ? OR d.description LIKE ? OR d.city LIKE ?)";
        $search_param = '%' . $search . '%';
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count
    $count_sql = "
        SELECT COUNT(*) as total
        FROM disasters d
        $where_clause
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = (int)$count_stmt->fetch()['total'];
    
    // Fetch disasters
    $sql = "
        SELECT 
            d.disaster_id,
            d.tracking_id,
            d.disaster_name,
            d.type_id,
            dt.type_name,
            d.severity_level,
            d.severity_display,
            d.city,
            d.province,
            d.status,
            d.priority,
            d.reported_at,
            d.acknowledged_at,
            d.resolved_at,
            d.assigned_lgu_id,
            l.lgu_name,
            d.assigned_user_id,
            CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
            TIMESTAMPDIFF(HOUR, d.reported_at, NOW()) as hours_since_report,
            TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at) as response_hours,
            DATE_FORMAT(d.reported_at, '%M %d, %Y %h:%i %p') as formatted_reported
        FROM disasters d
        LEFT JOIN disaster_types dt ON d.type_id = dt.type_id
        LEFT JOIN lgus l ON d.assigned_lgu_id = l.lgu_id
        LEFT JOIN users u ON d.assigned_user_id = u.user_id
        {$where_clause}
        ORDER BY 
            CASE d.priority 
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                ELSE 5
            END,
            d.reported_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $disasters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data
    foreach ($disasters as &$disaster) {
        $disaster['status_badge'] = match($disaster['status']) {
            'ON GOING' => 'warning',
            'IN PROGRESS' => 'info',
            'COMPLETED' => 'success',
            default => 'secondary'
        };
        
        $disaster['priority_badge'] = match($disaster['priority']) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'success',
            default => 'secondary'
        };
        
        $severity_prefix = explode('-', $disaster['severity_level'] ?? '')[0] ?? 'green';
        $disaster['severity_badge'] = match($severity_prefix) {
            'red' => 'danger',
            'orange' => 'warning',
            'yellow' => 'info',
            'green' => 'success',
            default => 'secondary'
        };
        
        // Time since report
        $hours = (int)$disaster['hours_since_report'];
        if ($hours < 1) {
            $disaster['time_ago'] = 'Just now';
        } else if ($hours < 24) {
            $disaster['time_ago'] = $hours . ' hours ago';
        } else {
            $days = floor($hours / 24);
            $disaster['time_ago'] = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'Disasters retrieved successfully.';
    $response['data'] = [
        'disasters' => $disasters,
        'pagination' => [
            'total' => $total_count,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ]
    ];
    
} catch (Exception $e) {
    error_log("Get disasters error: " . $e->getMessage());
    $response['message'] = 'Error fetching disasters: ' . $e->getMessage();
}

echo json_encode($response);
