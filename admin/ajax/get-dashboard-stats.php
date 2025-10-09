<?php
/**
 * AJAX Endpoint: Get Real-Time Dashboard Stats
 * Method: GET
 * Updates dashboard statistics without refresh
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
    $stats = [];
    
    // Total disasters
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters");
    $stats['total_disasters'] = (int)$stmt->fetch()['total'];
    
    // Active disasters (not completed)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status != 'COMPLETED'");
    $stats['active_disasters'] = (int)$stmt->fetch()['total'];
    
    // Critical disasters
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE priority = 'critical' AND status != 'COMPLETED'");
    $stats['critical_disasters'] = (int)$stmt->fetch()['total'];
    
    // Pending disasters
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status = 'ON GOING'");
    $stats['pending_disasters'] = (int)$stmt->fetch()['total'];
    
    // In progress disasters
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status = 'IN PROGRESS'");
    $stats['in_progress_disasters'] = (int)$stmt->fetch()['total'];
    
    // Completed today
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status = 'COMPLETED' AND DATE(resolved_at) = CURDATE()");
    $stats['completed_today'] = (int)$stmt->fetch()['total'];
    
    // New reports today
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE DATE(reported_at) = CURDATE()");
    $stats['new_today'] = (int)$stmt->fetch()['total'];
    
    // Overdue disasters
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE escalation_deadline < NOW() AND status != 'COMPLETED'");
    $stats['overdue_disasters'] = (int)$stmt->fetch()['total'];
    
    // Recent reports (last 10)
    $stmt = $pdo->prepare("
        SELECT d.disaster_id, d.tracking_id, d.disaster_name, dt.type_name, 
               d.severity_level, d.severity_display, d.city, d.status, d.priority, 
               d.reported_at, lgu.lgu_name,
               TIMESTAMPDIFF(MINUTE, d.reported_at, NOW()) as minutes_ago,
               TIMESTAMPDIFF(HOUR, d.reported_at, NOW()) as hours_ago
        FROM disasters d
        JOIN disaster_types dt ON d.type_id = dt.type_id
        LEFT JOIN lgus lgu ON d.assigned_lgu_id = lgu.lgu_id
        ORDER BY d.reported_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format time ago
    foreach ($recent_reports as &$report) {
        if ($report['minutes_ago'] < 60) {
            $report['time_ago'] = $report['minutes_ago'] . ' minutes ago';
        } else if ($report['hours_ago'] < 24) {
            $report['time_ago'] = $report['hours_ago'] . ' hours ago';
        } else {
            $days = floor($report['hours_ago'] / 24);
            $report['time_ago'] = $days . ' days ago';
        }
    }
    
    $stats['recent_reports'] = $recent_reports;
    
    // Disaster by severity
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN severity_level LIKE 'red-%' THEN 'critical'
                WHEN severity_level LIKE 'orange-%' THEN 'moderate'
                WHEN severity_level LIKE 'yellow-%' THEN 'low'
                WHEN severity_level LIKE 'green-%' THEN 'minor'
                ELSE 'unknown'
            END as severity_category,
            COUNT(*) as count
        FROM disasters
        WHERE status != 'COMPLETED'
        GROUP BY severity_category
    ");
    $severity_stats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $severity_stats[$row['severity_category']] = (int)$row['count'];
    }
    $stats['severity_distribution'] = $severity_stats;
    
    // Disaster by type (active only)
    $stmt = $pdo->query("
        SELECT dt.type_name, COUNT(d.disaster_id) as count
        FROM disaster_types dt
        LEFT JOIN disasters d ON dt.type_id = d.type_id AND d.status != 'COMPLETED'
        WHERE dt.is_active = TRUE
        GROUP BY dt.type_id, dt.type_name
        ORDER BY count DESC
        LIMIT 5
    ");
    $type_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['type_distribution'] = $type_stats;
    
    // Response time average (last 30 days)
    $stmt = $pdo->query("
        SELECT AVG(TIMESTAMPDIFF(HOUR, reported_at, acknowledged_at)) as avg_hours
        FROM disasters
        WHERE acknowledged_at IS NOT NULL 
        AND reported_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $avg_response = $stmt->fetch();
    $stats['avg_response_time_hours'] = $avg_response['avg_hours'] ? round($avg_response['avg_response_hours'], 1) : 0;
    
    // Completion rate (last 30 days)
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as completed
        FROM disasters
        WHERE reported_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $completion = $stmt->fetch();
    $stats['completion_rate'] = $completion['total'] > 0 
        ? round(($completion['completed'] / $completion['total']) * 100, 1) 
        : 0;
    
    $response['success'] = true;
    $response['message'] = 'Statistics retrieved successfully.';
    $response['data'] = $stats;
    $response['timestamp'] = date('Y-m-d H:i:s');
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $response['message'] = 'Error fetching statistics: ' . $e->getMessage();
}

echo json_encode($response);
