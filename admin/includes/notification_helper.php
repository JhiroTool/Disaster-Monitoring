<?php
/**
 * Notification Helper Functions
 * Creates automatic notifications for disaster reports
 */

/**
 * Create notification for new disaster report
 */
function createDisasterNotification($pdo, $disaster_id) {
    try {
        // Get disaster details
        $stmt = $pdo->prepare("
            SELECT d.*, dt.type_name, 
                   CONCAT(COALESCE(d.city, ''), ', ', COALESCE(d.province, ''), ', ', COALESCE(d.state, '')) as location
            FROM disasters d
            JOIN disaster_types dt ON d.type_id = dt.type_id
            WHERE d.disaster_id = ?
        ");
        $stmt->execute([$disaster_id]);
        $disaster = $stmt->fetch();
        
        if (!$disaster) {
            return false;
        }
        
        // Prepare notification details
        $title = "New " . ucfirst($disaster['priority']) . " Disaster Report: " . $disaster['type_name'];
        $message = "A new disaster report has been submitted.\n";
        $message .= "Type: " . $disaster['type_name'] . "\n";
        $message .= "Location: " . $disaster['location'] . "\n";
        $message .= "Severity: " . $disaster['severity_display'] . "\n";
        $message .= "Status: " . $disaster['status'] . "\n";
        $message .= "Tracking ID: " . $disaster['tracking_id'];
        
        // Determine notification type based on priority
        $type = match($disaster['priority']) {
            'critical' => 'alert',
            'high' => 'warning',
            default => 'info'
        };
        
        // Get all admin and LGU admin users
        $user_stmt = $pdo->prepare("
            SELECT user_id FROM users 
            WHERE is_active = TRUE 
            AND role IN ('admin', 'lgu_admin')
        ");
        $user_stmt->execute();
        $users = $user_stmt->fetchAll();
        
        // Insert notification for each admin user
        $notification_stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, title, message, type, related_disaster_id, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        
        $count = 0;
        foreach ($users as $user) {
            $notification_stmt->execute([
                $user['user_id'],
                $title,
                $message,
                $type,
                $disaster_id
            ]);
            $count++;
        }
        
        return $count;
        
    } catch (Exception $e) {
        error_log("Notification creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check for unnotified disaster reports and create notifications
 */
function checkAndNotifyNewReports($pdo) {
    try {
        // Find disasters that don't have notifications yet
        $stmt = $pdo->query("
            SELECT d.disaster_id 
            FROM disasters d
            LEFT JOIN notifications n ON d.disaster_id = n.related_disaster_id
            WHERE n.notification_id IS NULL
            AND d.reported_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY d.reported_at DESC
            LIMIT 50
        ");
        
        $disasters = $stmt->fetchAll();
        $total_notified = 0;
        
        foreach ($disasters as $disaster) {
            $count = createDisasterNotification($pdo, $disaster['disaster_id']);
            if ($count) {
                $total_notified += $count;
            }
        }
        
        return $total_notified;
        
    } catch (Exception $e) {
        error_log("Check new reports error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotificationCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? 
            AND is_read = FALSE 
            AND is_active = TRUE
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Get notification count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($pdo, $notification_id, $user_id) {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE, read_at = NOW()
            WHERE notification_id = ? AND user_id = ?
        ");
        return $stmt->execute([$notification_id, $user_id]);
    } catch (Exception $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create notification for status update
 */
function createStatusUpdateNotification($pdo, $disaster_id, $old_status, $new_status, $updated_by) {
    try {
        // Get disaster details
        $stmt = $pdo->prepare("
            SELECT d.*, dt.type_name, d.tracking_id
            FROM disasters d
            JOIN disaster_types dt ON d.type_id = dt.type_id
            WHERE d.disaster_id = ?
        ");
        $stmt->execute([$disaster_id]);
        $disaster = $stmt->fetch();
        
        if (!$disaster) {
            return false;
        }
        
        $title = "Disaster Status Updated: " . $disaster['type_name'];
        $message = "Status changed from '{$old_status}' to '{$new_status}'\n";
        $message .= "Tracking ID: " . $disaster['tracking_id'] . "\n";
        $message .= "Type: " . $disaster['type_name'];
        
        // Get all admin users
        $user_stmt = $pdo->prepare("
            SELECT user_id FROM users 
            WHERE is_active = TRUE 
            AND role IN ('admin', 'lgu_admin')
            AND user_id != ?
        ");
        $user_stmt->execute([$updated_by]);
        $users = $user_stmt->fetchAll();
        
        // Insert notification for each admin user
        $notification_stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, title, message, type, related_disaster_id, is_active, created_at)
            VALUES (?, ?, ?, 'info', ?, 1, NOW())
        ");
        
        $count = 0;
        foreach ($users as $user) {
            $notification_stmt->execute([
                $user['user_id'],
                $title,
                $message,
                $disaster_id
            ]);
            $count++;
        }
        
        return $count;
        
    } catch (Exception $e) {
        error_log("Status update notification error: " . $e->getMessage());
        return false;
    }
}
?>
