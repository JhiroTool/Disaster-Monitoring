<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch recent notifications for the user with disaster details
    $stmt = $pdo->prepare("
        SELECT 
            n.notification_id,
            n.title,
            n.message,
            n.type,
            n.is_read,
            n.created_at,
            n.related_disaster_id,
            n.related_id,
            d.tracking_id,
            d.disaster_name,
            dt.type_name as disaster_type,
            d.priority,
            d.status
        FROM notifications n
        LEFT JOIN disasters d ON COALESCE(n.related_disaster_id, n.related_id) = d.disaster_id
        LEFT JOIN disaster_types dt ON d.type_id = dt.type_id
        WHERE n.user_id = ?
        AND n.is_active = TRUE
        AND (n.expires_at IS NULL OR n.expires_at > NOW())
        ORDER BY n.is_read ASC, n.created_at DESC
        LIMIT 20
    ");
    
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format notifications for display
    $formatted_notifications = [];
    foreach ($notifications as $notification) {
        $time_ago = timeAgo($notification['created_at']);
        
        // Determine icon and class based on type
        $icon = match($notification['type']) {
            'alert' => 'fa-exclamation-triangle',
            'warning' => 'fa-exclamation-circle',
            'info' => 'fa-info-circle',
            'disaster_assigned' => 'fa-clipboard-check',
            'status_update' => 'fa-sync-alt',
            'escalation' => 'fa-level-up-alt',
            'deadline_warning' => 'fa-clock',
            'system' => 'fa-cog',
            default => 'fa-bell'
        };
        
        $class = match($notification['type']) {
            'alert' => 'notification-alert',
            'warning' => 'notification-warning',
            'info' => 'notification-info',
            default => 'notification-default'
        };
        
        // Create link if disaster is related
        $link = null;
        $disaster_id = $notification['related_disaster_id'] ?? $notification['related_id'];
        if ($disaster_id) {
            $link = 'disaster-details.php?id=' . $disaster_id;
        }
        
        $formatted_notifications[] = [
            'id' => $notification['notification_id'],
            'title' => $notification['title'],
            'message' => substr($notification['message'], 0, 100) . (strlen($notification['message']) > 100 ? '...' : ''),
            'full_message' => $notification['message'],
            'icon' => $icon,
            'class' => $class,
            'is_read' => (bool)$notification['is_read'],
            'time_ago' => $time_ago,
            'link' => $link,
            'disaster_tracking_id' => $notification['tracking_id'],
            'disaster_type' => $notification['disaster_type'],
            'priority' => $notification['priority']
        ];
    }
    
    // Get total unread count
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE user_id = ? 
        AND is_read = FALSE 
        AND is_active = TRUE
        AND (expires_at IS NULL OR expires_at > NOW())
    ");
    $count_stmt->execute([$user_id]);
    $count_result = $count_stmt->fetch();
    $unread_count = $count_result['count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $formatted_notifications,
        'unread_count' => $unread_count
    ]);
    
} catch (Exception $e) {
    error_log("Get notifications error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch notifications',
        'message' => $e->getMessage()
    ]);
}

/**
 * Convert timestamp to time ago format
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>