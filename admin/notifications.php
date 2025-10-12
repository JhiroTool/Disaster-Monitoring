<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

// Allow viewing for all users, restrict creation to admins
$can_edit = isAdmin();

$page_title = 'Notifications Management';

// Handle notification operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    if (isset($_POST['create_notification'])) {
        $title = sanitizeInput($_POST['title']);
        $message = sanitizeInput($_POST['message']);
        $type = sanitizeInput($_POST['type']);
        $target_role = $_POST['target_role'] === 'all' ? null : sanitizeInput($_POST['target_role']);
        $target_lgu_id = $_POST['target_lgu_id'] === 'all' ? null : intval($_POST['target_lgu_id']);
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        
        try {
            // Create notification for each target user
            $user_query = "SELECT user_id FROM users WHERE is_active = TRUE";
            $params = [];
            
            if ($target_role) {
                $user_query .= " AND role = ?";
                $params[] = $target_role;
            }
            
            if ($target_lgu_id) {
                $user_query .= " AND lgu_id = ?";
                $params[] = $target_lgu_id;
            }
            
            $user_stmt = $pdo->prepare($user_query);
            $user_stmt->execute($params);
            $users = $user_stmt->fetchAll();
            
            $notification_stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, target_role, target_lgu_id, expires_at, created_by, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $count = 0;
            foreach ($users as $user) {
                $notification_stmt->execute([
                    $user['user_id'], $title, $message, $type, $target_role, 
                    $target_lgu_id, $expires_at, $_SESSION['user_id']
                ]);
                $count++;
            }
            
            $success_message = "Notification sent to " . $count . " users successfully.";
        } catch (Exception $e) {
            error_log("Notification creation error: " . $e->getMessage());
            $error_message = "Error creating notification. Please try again.";
        }
    }
    
    if (isset($_POST['update_notification'])) {
        $notification_id = intval($_POST['notification_id']);
        $title = sanitizeInput($_POST['title']);
        $message = sanitizeInput($_POST['message']);
        $type = sanitizeInput($_POST['type']);
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET title = ?, message = ?, type = ?, expires_at = ?, is_active = ?
                WHERE notification_id = ?
            ");
            $stmt->execute([$title, $message, $type, $expires_at, $is_active, $notification_id]);
            
            $success_message = "Notification updated successfully.";
        } catch (Exception $e) {
            error_log("Notification update error: " . $e->getMessage());
            $error_message = "Error updating notification.";
        }
    }
    
    if (isset($_POST['delete_notification'])) {
        $notification_id = intval($_POST['notification_id']);
        
        try {
            // Delete recipients first
            $stmt = $pdo->prepare("DELETE FROM notification_recipients WHERE notification_id = ?");
            $stmt->execute([$notification_id]);
            
            // Delete notification
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE notification_id = ?");
            $stmt->execute([$notification_id]);
            
            $success_message = "Notification deleted successfully.";
        } catch (Exception $e) {
            error_log("Notification deletion error: " . $e->getMessage());
            $error_message = "Error deleting notification.";
        }
    }

    if (isset($_POST['bulk_action_submit'])) {
        $bulk_action_type = sanitizeInput($_POST['bulk_action_type'] ?? '');
        $bulk_notification_ids = $_POST['bulk_notification_ids'] ?? [];
        $bulk_notification_ids = array_unique(array_map('intval', (array) $bulk_notification_ids));

        if (empty($bulk_notification_ids)) {
            $error_message = "No notifications selected for bulk action.";
        } elseif (!in_array($bulk_action_type, ['activate', 'deactivate', 'delete'], true)) {
            $error_message = "Invalid bulk action selected.";
        } else {
            $placeholders = implode(',', array_fill(0, count($bulk_notification_ids), '?'));

            try {
                if ($bulk_action_type === 'activate' || $bulk_action_type === 'deactivate') {
                    $is_active_value = $bulk_action_type === 'activate' ? 1 : 0;
                    $stmt = $pdo->prepare(
                        "UPDATE notifications SET is_active = ? WHERE notification_id IN ($placeholders)"
                    );
                    $stmt->execute(array_merge([$is_active_value], $bulk_notification_ids));
                    $success_message = "Updated status for " . $stmt->rowCount() . " notifications.";
                } elseif ($bulk_action_type === 'delete') {
                    $pdo->beginTransaction();
                    $recipient_stmt = $pdo->prepare(
                        "DELETE FROM notification_recipients WHERE notification_id IN ($placeholders)"
                    );
                    $recipient_stmt->execute($bulk_notification_ids);

                    $notification_stmt = $pdo->prepare(
                        "DELETE FROM notifications WHERE notification_id IN ($placeholders)"
                    );
                    $notification_stmt->execute($bulk_notification_ids);

                    $pdo->commit();
                    $success_message = "Deleted " . $notification_stmt->rowCount() . " notifications.";
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Bulk notification action error: " . $e->getMessage());
                $error_message = "Error performing bulk action. Please try again.";
            }
        }
    }
}

// Fetch notifications with statistics
try {
    $stmt = $pdo->query("
        SELECT n.*, 
               l.lgu_name,
               CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
               (SELECT COUNT(*) FROM notifications n2 WHERE n2.notification_id = n.notification_id AND n2.is_read = 1) as read_count,
               (SELECT COUNT(*) FROM notifications n3 WHERE n3.notification_id = n.notification_id) as total_recipients
        FROM notifications n
        LEFT JOIN lgus l ON n.target_lgu_id = l.lgu_id
        LEFT JOIN users u ON n.created_by = u.user_id
        ORDER BY n.created_at DESC
    ");
    $notifications = $stmt->fetchAll();
    
    // Fetch LGUs for targeting
    $lgu_stmt = $pdo->query("SELECT lgu_id, lgu_name FROM lgus WHERE is_active = TRUE ORDER BY lgu_name");
    $lgus = $lgu_stmt->fetchAll();
    
    // Fetch notification stats
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT notification_id) as total_notifications,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_notifications,
            COUNT(CASE WHEN expires_at IS NULL OR expires_at > NOW() THEN 1 END) as valid_notifications,
            COUNT(CASE WHEN expires_at IS NOT NULL AND expires_at <= NOW() THEN 1 END) as expired_notifications
        FROM notifications
    ");
    $stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    error_log("Notifications fetch error: " . $e->getMessage());
    $notifications = [];
    $lgus = [];
    $stats = ['total_notifications' => 0, 'active_notifications' => 0, 'valid_notifications' => 0, 'expired_notifications' => 0];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-bell"></i> Notifications Management</h2>
        <p>Create and manage system notifications</p>
    </div>
    <div class="page-actions">
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Notification
        </button>
        <button onclick="showBulkActionsModal()" class="btn btn-secondary">
            <i class="fas fa-tasks"></i> Bulk Actions
        </button>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Notification Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-bell"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number" data-stat-total><?php echo $stats['total_notifications']; ?></div>
            <div class="stat-label">Total Notifications</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number" data-stat-active><?php echo $stats['active_notifications']; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number" data-stat-valid><?php echo $stats['valid_notifications']; ?></div>
            <div class="stat-label">Valid</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number" data-stat-expired><?php echo $stats['expired_notifications']; ?></div>
            <div class="stat-label">Expired</div>
        </div>
    </div>
</div>

<!-- Notifications Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>All Notifications</h3>
        <div class="filters">
            <select id="typeFilter" onchange="filterNotifications()">
                <option value="">All Types</option>
                <option value="system">System</option>
                <option value="disaster_assigned">Disaster Assigned</option>
                <option value="status_update">Status Update</option>
                <option value="escalation">Escalation</option>
                <option value="deadline_warning">Deadline Warning</option>
            </select>
            <select id="statusFilter" onchange="filterNotifications()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="expired">Expired</option>
            </select>
        </div>
    </div>
    <div class="card-content">
        <div class="table-container">
            <table id="notificationsTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()"></th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Target</th>
                        <th>Recipients</th>
                        <th>Read Rate</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                            $target_description = 'All Users';
                            if (!empty($notification['target_role']) && !empty($notification['lgu_name'])) {
                                $target_description = ucfirst($notification['target_role']) . ' @ ' . $notification['lgu_name'];
                            } elseif (!empty($notification['target_role'])) {
                                $target_description = ucfirst($notification['target_role']) . ' (All LGUs)';
                            } elseif (!empty($notification['lgu_name'])) {
                                $target_description = $notification['lgu_name'] . ' (All Roles)';
                            }

                            $type_label = ucfirst(str_replace('_', ' ', $notification['type']));
                            $read_rate = $notification['total_recipients'] > 0
                                ? ($notification['read_count'] / $notification['total_recipients']) * 100
                                : 0;
                            $is_expired = $notification['expires_at'] && strtotime($notification['expires_at']) <= time();
                            $status_class = $notification['is_active'] ? ($is_expired ? 'expired' : 'active') : 'inactive';
                            $status_text = $notification['is_active'] ? ($is_expired ? 'Expired' : 'Active') : 'Inactive';

                            $type_colors = [
                                'system' => '#2563eb',
                                'disaster_assigned' => '#0f766e',
                                'status_update' => '#ca8a04',
                                'escalation' => '#dc2626',
                                'deadline_warning' => '#d97706'
                            ];
                            $accent_color = $type_colors[$notification['type']] ?? '#2563eb';

                            $type_icons = [
                                'system' => 'fas fa-bullhorn',
                                'disaster_assigned' => 'fas fa-people-arrows',
                                'status_update' => 'fas fa-clipboard-check',
                                'escalation' => 'fas fa-fire-alt',
                                'deadline_warning' => 'fas fa-hourglass-half'
                            ];
                            $type_icon = $type_icons[$notification['type']] ?? 'fas fa-bell';

                            if (!empty($notification['target_role']) && !empty($notification['lgu_name'])) {
                                $target_icon = 'fas fa-people-group';
                            } elseif (!empty($notification['target_role'])) {
                                $target_icon = 'fas fa-user-shield';
                            } elseif (!empty($notification['lgu_name'])) {
                                $target_icon = 'fas fa-map-marker-alt';
                            } else {
                                $target_icon = 'fas fa-users';
                            }

                            if ($status_class === 'active') {
                                $status_icon = 'fas fa-circle-check';
                            } elseif ($status_class === 'expired') {
                                $status_icon = 'fas fa-hourglass-end';
                            } else {
                                $status_icon = 'fas fa-pause-circle';
                            }

                            $raw_message = trim($notification['message'] ?? '');
                            $formatted_message_lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw_message))));
                            $max_visible_lines = 3;
                            $display_message_lines = array_slice($formatted_message_lines, 0, $max_visible_lines);
                            $has_additional_lines = count($formatted_message_lines) > $max_visible_lines;

                            $notification_payload = [
                                'id' => (int) $notification['notification_id'],
                                'title' => $notification['title'],
                                'message' => $notification['message'],
                                'type' => $notification['type'],
                                'type_label' => $type_label,
                                'target_role' => $notification['target_role'] ?? 'all',
                                'target_lgu_id' => $notification['target_lgu_id'] ?? 'all',
                                'target_description' => $target_description,
                                'expires_at' => $notification['expires_at'],
                                'expires_at_formatted' => $notification['expires_at'] ? date('M d, Y H:i', strtotime($notification['expires_at'])) : null,
                                'expires_at_local' => $notification['expires_at'] ? date('Y-m-d\TH:i', strtotime($notification['expires_at'])) : '',
                                'is_active' => (int) $notification['is_active'],
                                'status_text' => $status_text,
                                'status_class' => $status_class,
                                'created_at' => $notification['created_at'],
                                'created_at_formatted' => date('M d, Y H:i', strtotime($notification['created_at'])),
                                'created_by' => $notification['created_by_name'] ?? 'System',
                                'read_count' => (int) $notification['read_count'],
                                'total_recipients' => (int) $notification['total_recipients'],
                                'read_rate' => $notification['total_recipients'] > 0 ? round($read_rate, 1) : 0,
                                'message_lines' => $formatted_message_lines,
                                'type_icon' => $type_icon,
                                'target_icon' => $target_icon,
                                'status_icon' => $status_icon,
                                'accent_color' => $accent_color
                            ];
                            $notification_json = htmlspecialchars(json_encode($notification_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="notification-row" data-notification="<?php echo $notification_json; ?>" data-notification-type="<?php echo htmlspecialchars($notification['type']); ?>" data-notification-status="<?php echo htmlspecialchars($status_class); ?>" style="--notification-accent: <?php echo htmlspecialchars($accent_color); ?>;">
                            <td>
                                <input type="checkbox" class="notification-checkbox" 
                                       value="<?php echo $notification['notification_id']; ?>">
                            </td>
                            <td class="notification-main">
                                <article class="notification-card" tabindex="0">
                                    <span class="notification-card-accent" aria-hidden="true"></span>
                                    <header class="notification-card-header">
                                        <div class="notification-card-heading">
                                            <span class="notification-type-chip type-<?php echo htmlspecialchars($notification['type']); ?>">
                                                <i class="<?php echo htmlspecialchars($type_icon); ?>" aria-hidden="true"></i>
                                                <?php echo htmlspecialchars($type_label); ?>
                                            </span>
                                            <h4 class="notification-card-title"><?php echo htmlspecialchars($notification['title']); ?></h4>
                                        </div>
                                        <div class="notification-card-meta">
                                            <span class="notification-issued"><i class="fas fa-user-shield"></i>
                                                <?php echo htmlspecialchars($notification['created_by_name'] ?? 'System'); ?>
                                            </span>
                                            <span class="notification-timestamp"><i class="fas fa-calendar-day"></i>
                                                <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                            </span>
                                        </div>
                                    </header>

                                    <div class="notification-card-body">
                                        <?php if (!empty($formatted_message_lines)): ?>
                                            <?php if (count($formatted_message_lines) === 1): ?>
                                                <p class="notification-card-message single-line">
                                                    <?php echo htmlspecialchars(reset($formatted_message_lines)); ?>
                                                </p>
                                            <?php else: ?>
                                                <ul class="notification-card-list">
                                                    <?php foreach ($display_message_lines as $line): ?>
                                                        <li><?php echo htmlspecialchars($line); ?></li>
                                                    <?php endforeach; ?>
                                                    <?php if ($has_additional_lines): ?>
                                                        <li class="notification-card-list-more">+<?php echo count($formatted_message_lines) - $max_visible_lines; ?> more details</li>
                                                    <?php endif; ?>
                                                </ul>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="notification-card-message empty">No message details provided.</p>
                                        <?php endif; ?>
                                    </div>

                                    <footer class="notification-card-footer">
                                        <div class="notification-card-tags">
                                            <span class="notification-card-tag"><i class="fas fa-users"></i><?php echo htmlspecialchars($target_description); ?></span>
                                            <?php if ($notification['expires_at']): ?>
                                                <span class="notification-card-tag <?php echo $is_expired ? 'expired' : 'active'; ?>">
                                                    <i class="fas fa-hourglass-end"></i>
                                                    <?php echo $is_expired ? 'Expired ' : 'Expires '; ?><?php echo date('M d, Y H:i', strtotime($notification['expires_at'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="notification-card-tag">
                                                    <i class="fas fa-infinity"></i>No expiry set
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </footer>
                                </article>
                            </td>
                            <td>
                                <span class="type-badge type-<?php echo htmlspecialchars($notification['type']); ?>">
                                    <i class="<?php echo htmlspecialchars($type_icon); ?>" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($type_label); ?>
                                </span>
                            </td>
                            <td>
                                <span class="data-chip target-chip">
                                    <i class="<?php echo htmlspecialchars($target_icon); ?>" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($target_description); ?>
                                </span>
                            </td>
                            <td>
                                <div class="metric-block">
                                    <span class="metric-value"><?php echo $notification['total_recipients']; ?></span>
                                    <span class="metric-label">Recipients</span>
                                </div>
                            </td>
                            <td>
                                <div class="read-rate-card">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $read_rate; ?>%"></div>
                                    </div>
                                    <span class="read-rate-label"><?php echo number_format($read_rate, 1); ?>% <span>(<?php echo $notification['read_count']; ?>/<?php echo $notification['total_recipients']; ?>)</span></span>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $status_class; ?>">
                                    <i class="<?php echo htmlspecialchars($status_icon); ?>" aria-hidden="true"></i>
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <div class="timestamp-stack">
                                    <time datetime="<?php echo date('c', strtotime($notification['created_at'])); ?>"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></time>
                                    <span class="timestamp-label">Created</span>
                                </div>
                            </td>
                            <td>
                                <?php if ($notification['expires_at']): ?>
                                    <div class="timestamp-stack">
                                        <time datetime="<?php echo date('c', strtotime($notification['expires_at'])); ?>"><?php echo date('M d, Y H:i', strtotime($notification['expires_at'])); ?></time>
                                        <span class="timestamp-label"><?php echo $is_expired ? 'Expired' : 'Expires'; ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="timestamp-stack no-expiry">
                                        <i class="fas fa-infinity" aria-hidden="true"></i>
                                        <span>No expiry</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewNotification(this)" 
                                            class="btn btn-xs btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editNotification(this)" 
                                            class="btn btn-xs btn-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteNotification(this)" 
                                            class="btn btn-xs btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-pagination" aria-label="Notifications pagination">
            <button type="button" class="pagination-btn prev" data-pagination-prev disabled>
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                <span>Previous</span>
            </button>
            <span class="pagination-status" data-pagination-status>Showing 0</span>
            <button type="button" class="pagination-btn next" data-pagination-next disabled>
                <span>Next</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>

<!-- Create Notification Modal -->
<div id="createNotificationModal" class="modal">
    <div class="modal-content modal-xl">
        <div class="modal-header">
            <h3>Create New Notification</h3>
            <button class="modal-close" type="button" onclick="closeCreateModal()" aria-label="Close create notification modal">&times;</button>
        </div>
        <form method="POST" class="modal-form" id="create-notification-form">
            <div class="modal-body">
                <div class="modal-intro">
                    <div class="modal-intro-icon notification">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="modal-intro-content">
                        <h4>Craft a clear alert</h4>
                        <p>Write a concise headline, share the key details, and target the right teams. We’ll show you a live preview as you type.</p>
                    </div>
                </div>

                <div class="modal-grid">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" name="title" id="title" placeholder="e.g., Typhoon Alert: High Winds Expected" maxlength="120" required data-title-input>
                        <small class="form-help">Keep it short and action-oriented. <span data-title-count>0/120</span></small>
                    </div>
                    <div class="form-group">
                        <label for="type">Type *</label>
                        <div class="type-pill-group" role="radiogroup" aria-label="Notification type">
                            <?php
                                $notification_types = [
                                    'system' => 'System',
                                    'disaster_assigned' => 'Disaster Assigned',
                                    'status_update' => 'Status Update',
                                    'escalation' => 'Escalation',
                                    'deadline_warning' => 'Deadline Warning'
                                ];
                                foreach ($notification_types as $value => $label):
                            ?>
                            <label class="type-pill">
                                <input type="radio" name="type" value="<?php echo $value; ?>" <?php echo $value === 'system' ? 'checked' : ''; ?> required>
                                <span><?php echo $label; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <select name="type" id="type" class="visually-hidden" aria-hidden="true" tabindex="-1">
                            <?php foreach ($notification_types as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $value === 'system' ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Choose the channel to categorize urgency.</small>
                    </div>
                </div>

                <div class="message-stack">
                    <div class="form-group message-editor">
                        <div class="label-row">
                            <label for="message">Message *</label>
                            <span class="char-count" data-message-count>0 / 600</span>
                        </div>
                        <textarea name="message" id="message" rows="6" placeholder="Bullet out the situation, affected zones, and immediate next steps." maxlength="600" required data-message-input></textarea>
                        <div class="message-tips">
                            <span><i class="fas fa-list-ul"></i> Use bullet-style sentences to keep it scannable.</span>
                            <span><i class="fas fa-clock"></i> Include timing or deadlines where possible.</span>
                            <span><i class="fas fa-phone"></i> Add contact or hotline for urgent coordination.</span>
                        </div>
                    </div>
                    <aside class="message-preview" aria-live="polite">
                        <div class="preview-header">
                            <span class="preview-chip" data-preview-type>System</span>
                            <span class="preview-timestamp">Preview • <?php echo date('M d, Y H:i'); ?></span>
                        </div>
                        <h4 class="preview-title" data-preview-title>Typhoon Alert: High Winds Expected</h4>
                        <ul class="preview-list" data-preview-body>
                            <li>Outline the key impact areas.</li>
                            <li>Add a response action or escalation path.</li>
                            <li>Share contact details for coordination.</li>
                        </ul>
                        <div class="preview-footer" data-preview-target>
                            <i class="fas fa-users"></i> All roles • All LGUs
                        </div>
                    </aside>
                </div>

                <div class="targeting-grid">
                    <div class="targeting-card">
                        <div class="targeting-header">
                            <h4><i class="fas fa-users-cog"></i> Target Audience</h4>
                            <span class="target-summary-chip" data-target-summary>All roles • All LGUs</span>
                        </div>
                        <div class="targeting-controls">
                            <div class="form-group">
                                <span class="control-label">Target Role</span>
                                <div class="chip-select" role="radiogroup" aria-label="Target role">
                                    <label class="chip-option">
                                        <input type="radio" name="target_role_chip" value="all" checked>
                                        <span>All Roles</span>
                                    </label>
                                    <label class="chip-option">
                                        <input type="radio" name="target_role_chip" value="admin">
                                        <span>Admins</span>
                                    </label>
                                    <label class="chip-option">
                                        <input type="radio" name="target_role_chip" value="reporter">
                                        <span>Reporters</span>
                                    </label>
                                </div>
                                <select name="target_role" id="target_role" class="visually-hidden" aria-hidden="true" tabindex="-1">
                                    <option value="all" selected>All Roles</option>
                                    <option value="admin">Admins</option>
                                    <option value="reporter">Reporters</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <span class="control-label">Target LGU</span>
                                <div class="chip-select lgu">
                                    <label class="chip-option">
                                        <input type="radio" name="target_lgu_chip" value="all" checked>
                                        <span>All LGUs</span>
                                    </label>
                                    <?php foreach ($lgus as $lgu): ?>
                                        <label class="chip-option">
                                            <input type="radio" name="target_lgu_chip" value="<?php echo $lgu['lgu_id']; ?>">
                                            <span><?php echo htmlspecialchars($lgu['lgu_name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <select name="target_lgu_id" id="target_lgu_id" class="visually-hidden" aria-hidden="true" tabindex="-1">
                                    <option value="all" selected>All LGUs</option>
                                    <?php foreach ($lgus as $lgu): ?>
                                        <option value="<?php echo $lgu['lgu_id']; ?>"><?php echo htmlspecialchars($lgu['lgu_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="schedule-card">
                        <h4><i class="fas fa-hourglass-start"></i> Expiry &amp; Delivery</h4>
                        <div class="schedule-grid">
                            <div class="form-group">
                                <label for="expires_at">Expiry Date</label>
                                <input type="datetime-local" name="expires_at" id="expires_at" data-expiry-input>
                                <small class="form-help">Leave blank to keep the notification active until manually archived.</small>
                            </div>
                            <div class="form-hint-block">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>Need reminders?</strong>
                                    <p>Send a follow-up by duplicating this alert later. Expiry automatically hides notifications from dashboards.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-hints">
                    <div class="hint-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Track read rates in the table above to see who acted.</span>
                    </div>
                    <div class="hint-item">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Re-use common templates by copying prior notifications.</span>
                    </div>
                    <div class="hint-item">
                        <i class="fas fa-bell"></i>
                        <span>Active alerts surface on the reporter dashboard instantly.</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" onclick="closeCreateModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="create_notification" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Notification
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Notification</h3>
            <button class="modal-close" type="button" onclick="closeDeleteModal()" aria-label="Close delete notification modal">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="notification_id" id="delete-notification-id">
            
            <p id="delete-notification-message">
                You're about to permanently remove <strong id="delete-notification-title">this notification</strong>.
                This action cannot be undone.
            </p>
            
            <div class="form-actions">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="delete_notification" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- View Notification Modal -->
    <div id="viewNotificationModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header view-header">
                <h3>Notification Details</h3>
                <button class="modal-close" type="button" onclick="closeViewNotificationModal()" aria-label="Close notification details modal">&times;</button>
            </div>
            <div class="modal-body view-notification">
                <div class="view-notification-top">
                    <span class="view-type-chip" data-view-type>System</span>
                    <span class="view-status-chip" data-view-status>Active</span>
                </div>
                <h4 class="view-title" data-view-title>Notification title</h4>
                <div class="view-meta">
                    <span><i class="fas fa-user-shield"></i> <span data-view-created-by>System</span></span>
                    <span><i class="fas fa-calendar-day"></i> <span data-view-created-at>Oct 05, 2025 17:20</span></span>
                    <span data-view-expiry-wrapper><i class="fas fa-hourglass-half"></i> <span data-view-expiry>No expiry</span></span>
                </div>
                <div class="view-target-card">
                    <span class="view-target-label"><i class="fas fa-users"></i> Target</span>
                    <span class="view-target-value" data-view-target>All Users</span>
                </div>
                <div class="view-message-card">
                    <h5><i class="fas fa-align-left"></i> Message</h5>
                    <div data-view-message></div>
                </div>
                <div class="view-stats">
                    <div class="view-stat">
                        <span class="view-stat-value" data-view-read-rate>0%</span>
                        <span class="view-stat-label">Read Rate</span>
                    </div>
                    <div class="view-stat">
                        <span class="view-stat-value" data-view-read-count>0/0</span>
                        <span class="view-stat-label">Readers</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewNotificationModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Notification Modal -->
    <div id="editNotificationModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header edit-header">
                <h3>Edit Notification</h3>
                <button class="modal-close" type="button" onclick="closeEditNotificationModal()" aria-label="Close edit notification modal">&times;</button>
            </div>
            <form method="POST" class="modal-form" id="edit-notification-form">
                <input type="hidden" name="notification_id" id="edit-notification-id">
                <div class="modal-body edit-notification">
                    <div class="form-group">
                        <label for="edit-title">Title *</label>
                        <input type="text" name="title" id="edit-title" required maxlength="120">
                    </div>
                    <div class="form-group">
                        <label for="edit-type">Type *</label>
                        <select name="type" id="edit-type" required>
                            <option value="system">System</option>
                            <option value="disaster_assigned">Disaster Assigned</option>
                            <option value="status_update">Status Update</option>
                            <option value="escalation">Escalation</option>
                            <option value="deadline_warning">Deadline Warning</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-message">Message *</label>
                        <textarea name="message" id="edit-message" rows="6" maxlength="600" required></textarea>
                        <small class="form-help">Use line breaks to create bullet-style points.</small>
                    </div>
                    <div class="form-group">
                        <label for="edit-expires-at">Expiry Date</label>
                        <input type="datetime-local" name="expires_at" id="edit-expires-at">
                        <small class="form-help">Leave blank to keep the notification active indefinitely.</small>
                    </div>
                    <div class="form-group toggle-inline">
                        <label for="edit-is-active">Active</label>
                        <label class="switch">
                            <input type="checkbox" name="is_active" id="edit-is-active" value="1">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditNotificationModal()">Cancel</button>
                    <button type="submit" name="update_notification" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions Modal -->
    <div id="bulkActionsModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header bulk-header">
                <h3>Bulk Actions</h3>
                <button class="modal-close" type="button" onclick="closeBulkActionsModal()" aria-label="Close bulk actions modal">&times;</button>
            </div>
            <form method="POST" class="modal-form" id="bulk-actions-form">
                <div class="modal-body bulk-body">
                    <p class="bulk-summary">Applying to <strong data-bulk-count>0</strong> selected notifications.</p>
                    <div class="bulk-options">
                        <label class="bulk-option">
                            <input type="radio" name="bulk_action_type" value="activate" required>
                            <span><i class="fas fa-toggle-on"></i> Mark as Active</span>
                        </label>
                        <label class="bulk-option">
                            <input type="radio" name="bulk_action_type" value="deactivate">
                            <span><i class="fas fa-toggle-off"></i> Mark as Inactive</span>
                        </label>
                        <label class="bulk-option destructive">
                            <input type="radio" name="bulk_action_type" value="delete">
                            <span><i class="fas fa-trash"></i> Delete Permanently</span>
                        </label>
                    </div>
                    <div class="bulk-warning" data-bulk-warning hidden>
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>This action cannot be undone. Deleted notifications are removed for all recipients.</span>
                    </div>
                    <div id="bulk-selected-ids"></div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeBulkActionsModal()">Cancel</button>
                    <button type="submit" name="bulk_action_submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

<style>
.notification-main {
    min-width: 280px;
}

.notification-card {
    position: relative;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 18px;
    padding: 18px 20px 16px 24px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    display: flex;
    flex-direction: column;
    gap: 0;
    overflow: hidden;
    z-index: 0;
    cursor: pointer;
    transition: box-shadow 0.25s ease, transform 0.2s ease;
}

.notification-card-accent {
    position: absolute;
    inset: 0 auto 0 0;
    width: 6px;
    background: var(--notification-accent, #2563eb);
    border-radius: 18px 0 0 18px;
    z-index: 0;
}

.notification-card > *:not(.notification-card-accent) {
    position: relative;
    z-index: 1;
}

.notification-card:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.35), 0 18px 40px rgba(15, 23, 42, 0.16);
}

.notification-card:is(:hover, :focus-within) {
    transform: translateY(-4px);
    box-shadow: 0 22px 46px rgba(15, 23, 42, 0.16);
}

.notification-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}

.notification-card-heading {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1 1 260px;
}

.notification-type-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border-radius: 999px;
    background: rgba(37, 99, 235, 0.12);
    color: var(--notification-accent, #2563eb);
}

.notification-type-chip i {
    font-size: 0.85rem;
}

.notification-card-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.45;
}

.notification-card-meta {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 10px 14px;
    font-size: 0.85rem;
    color: #475569;
}

.notification-card-meta span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.notification-issued,
.notification-timestamp {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #1e293b;
}

.notification-issued i,
.notification-timestamp i {
    color: var(--notification-accent, #2563eb);
    font-size: 0.9rem;
}

.notification-card-body {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.notification-card-message {
    margin: 0;
    color: #1f2937;
    font-size: 0.95rem;
    line-height: 1.55;
    display: -webkit-box;
    line-clamp: 4;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.notification-card-message.single-line {
    display: block;
    line-clamp: unset;
    -webkit-line-clamp: unset;
}

.notification-card-message.empty {
    color: #94a3b8;
    font-style: italic;
}

.notification-card-list {
    margin: 0;
    padding-left: 18px;
    color: #1f2937;
    font-size: 0.92rem;
    line-height: 1.5;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.notification-card-list li::marker {
    color: var(--notification-accent, #2563eb);
}

.notification-card-list-more {
    font-style: italic;
    color: #64748b;
    list-style: none;
    padding-left: 0;
    margin-left: -18px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.notification-card-list-more::before {
    content: '\2022';
    color: var(--notification-accent, #2563eb);
}

.notification-card-footer {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.notification-card-meta,
.notification-card-body,
.notification-card-footer {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    pointer-events: none;
    transform: translateY(-10px);
    transition: max-height 0.35s ease, opacity 0.25s ease, transform 0.3s ease, margin-top 0.3s ease;
    margin-top: 0;
}

.notification-card:is(:hover, :focus-within) .notification-card-meta,
.notification-card:is(:hover, :focus-within) .notification-card-body,
.notification-card:is(:hover, :focus-within) .notification-card-footer {
    max-height: 550px;
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}

.notification-card:is(:hover, :focus-within) .notification-card-meta {
    margin-top: 8px;
}

.notification-card:is(:hover, :focus-within) .notification-card-body {
    margin-top: 10px;
}

.notification-card:is(:hover, :focus-within) .notification-card-footer {
    margin-top: 12px;
}

.notification-card-tags {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 8px;
}

.notification-card-tag {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 12px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #1e293b;
    font-size: 0.83rem;
    font-weight: 600;
}

.notification-card-tag i {
    color: var(--notification-accent, #2563eb);
}

.notification-card-tag.expired {
    background: rgba(248, 113, 113, 0.12);
    color: #b91c1c;
}

.notification-card-tag.expired i {
    color: #dc2626;
}

.notification-card-tag.active {
    background: rgba(59, 130, 246, 0.12);
    color: #1d4ed8;
}
.view-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}

.edit-header {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.bulk-header {
    background: linear-gradient(135deg, #14b8a6 0%, #0f766e 100%);
}

.modal-footer {
    padding: 0 36px 28px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.view-notification {
    padding: 26px 36px 32px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.view-notification-top {
    display: flex;
    align-items: center;
    gap: 12px;
}

.view-type-chip,
.view-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.view-type-chip {
    background: rgba(37, 99, 235, 0.15);
    color: #1d4ed8;
}

.view-status-chip {
    background: rgba(34, 197, 94, 0.15);
    color: #15803d;
}

.view-status-chip.status-active {
    background: rgba(59, 130, 246, 0.15);
    color: #1d4ed8;
}

.view-status-chip.status-expired {
    background: rgba(248, 113, 113, 0.18);
    color: #b91c1c;
}

.view-status-chip.status-inactive {
    background: rgba(148, 163, 184, 0.2);
    color: #475569;
}

.view-title {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 700;
    color: #0f172a;
}

.view-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 18px;
    font-size: 0.9rem;
    color: #475569;
}

.view-meta span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.view-target-card,
.view-message-card {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 18px 20px;
    background: #ffffff;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
}

.view-target-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
}

.view-target-value {
    display: block;
    margin-top: 10px;
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

.view-message-card h5 {
    margin: 0 0 12px;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.view-message-card ul,
.view-message-card p {
    margin: 0;
    padding-left: 18px;
    color: #1f2937;
    line-height: 1.6;
}

.view-message-card ul {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.view-message-card p.single-line {
    padding-left: 0;
}

.view-message-card .empty-message {
    padding-left: 0;
    font-style: italic;
    color: #94a3b8;
}

.view-stats {
    display: flex;
    gap: 18px;
}

.view-stat {
    flex: 1;
    border-radius: 16px;
    padding: 16px;
    background: rgba(37, 99, 235, 0.08);
    display: flex;
    flex-direction: column;
    gap: 6px;
    text-align: center;
}

.view-stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1d4ed8;
}

.view-stat-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
}

.edit-notification {
    padding: 26px 36px 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.edit-notification .form-group,
.bulk-body .form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.toggle-inline {
    flex-direction: row !important;
    align-items: center;
    justify-content: space-between;
}

.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: #cbd5f5;
    border-radius: 999px;
    transition: background-color 0.2s ease;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    top: 3px;
    background-color: #ffffff;
    border-radius: 50%;
    transition: transform 0.2s ease;
    box-shadow: 0 3px 8px rgba(15, 23, 42, 0.2);
}

.switch input:checked + .slider {
    background-color: #2563eb;
}

.switch input:checked + .slider:before {
    transform: translateX(24px);
}

.bulk-body {
    padding: 26px 36px 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.bulk-summary {
    font-size: 0.95rem;
    color: #1f2937;
}

.bulk-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.bulk-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #ffffff;
    cursor: pointer;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.bulk-option input {
    transform: scale(1.2);
}

.bulk-option span {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #1e293b;
}

.bulk-option:hover {
    border-color: #2563eb;
    box-shadow: 0 10px 26px rgba(37, 99, 235, 0.12);
}

.bulk-option.destructive {
    border-color: rgba(248, 113, 113, 0.4);
}

.bulk-option.destructive span {
    color: #b91c1c;
}

.bulk-warning {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 14px;
    background: rgba(248, 113, 113, 0.12);
    color: #b91c1c;
    font-size: 0.88rem;
}

.bulk-warning i {
    margin-top: 2px;
}

.modal .form-actions {
    padding: 0 36px 32px;
}


.notification-card-tag.active i {
    color: #2563eb;
}

.notification-row {
    --notification-accent: #2563eb;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.notification-row:hover {
    transform: translateY(-2px);
}

.notification-row:hover .notification-card {
    box-shadow: 0 18px 38px rgba(15, 23, 42, 0.12);
}

.notification-row td {
    vertical-align: top;
}

.table-container tbody tr:nth-child(even) {
    background: transparent;
}

.data-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 0.83rem;
    font-weight: 600;
    background: rgba(148, 163, 184, 0.15);
    color: #1f2937;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.15);
    transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.data-chip i {
    color: var(--notification-accent, #2563eb);
}

.target-chip {
    background: rgba(37, 99, 235, 0.08);
    color: #1d4ed8;
    box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.12);
}

.metric-block {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
    padding: 8px 12px;
    border-radius: 16px;
    background: rgba(148, 163, 184, 0.12);
    color: #0f172a;
    min-width: 110px;
}

.metric-value {
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1;
}

.metric-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
}

.read-rate-card {
    display: inline-flex;
    flex-direction: column;
    gap: 4px;
    padding: 8px 0;
    min-width: 120px;
}

.read-rate-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #0f172a;
}

.read-rate-label span {
    font-weight: 500;
    color: #475569;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.78rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    background: rgba(148, 163, 184, 0.15);
    color: #1e293b;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.2);
}

.status-badge i {
    color: var(--notification-accent, #2563eb);
}

.status-badge.status-active {
    background: rgba(59, 130, 246, 0.12);
    color: #1d4ed8;
    box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.25);
}

.status-badge.status-expired {
    background: rgba(248, 113, 113, 0.12);
    color: #b91c1c;
    box-shadow: inset 0 0 0 1px rgba(248, 113, 113, 0.28);
}

.status-badge.status-inactive {
    background: rgba(148, 163, 184, 0.18);
    color: #475569;
}

.timestamp-stack {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
    padding: 8px 0;
    min-width: 120px;
}

.timestamp-stack time,
.timestamp-stack span {
    display: block;
}

.timestamp-stack time {
    font-weight: 600;
    color: #0f172a;
    font-size: 0.9rem;
}

.timestamp-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
}

.timestamp-stack.no-expiry {
    padding: 8px 12px;
    border-radius: 16px;
    background: rgba(148, 163, 184, 0.12);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #334155;
    font-weight: 600;
}

.timestamp-stack.no-expiry i {
    color: var(--notification-accent, #2563eb);
}

.table-container tbody td {
    border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

.table-pagination {
    margin-top: 18px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
    flex-wrap: wrap;
}

.pagination-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    border: 1px solid rgba(37, 99, 235, 0.25);
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, opacity 0.2s ease;
}

.pagination-btn i {
    font-size: 0.85rem;
}

.pagination-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.25);
}

.pagination-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.35);
}

.pagination-btn:disabled {
    cursor: not-allowed;
    opacity: 0.6;
    box-shadow: none;
}

.pagination-status {
    font-size: 0.85rem;
    color: #475569;
    font-weight: 600;
}

.type-badge {
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(148, 163, 184, 0.18);
    color: #1e293b;
}

.type-badge i {
    font-size: 0.85rem;
}

.notification-type-chip.type-system,
.type-badge.type-system {
    background: rgba(37, 99, 235, 0.15);
    color: #1d4ed8;
}

.notification-type-chip.type-disaster_assigned,
.type-badge.type-disaster_assigned {
    background: rgba(14, 116, 144, 0.18);
    color: #0f766e;
}

.notification-type-chip.type-status_update,
.type-badge.type-status_update {
    background: rgba(202, 138, 4, 0.18);
    color: #b45309;
}

.notification-type-chip.type-escalation,
.type-badge.type-escalation {
    background: rgba(220, 38, 38, 0.16);
    color: #b91c1c;
}

.notification-type-chip.type-deadline_warning,
.type-badge.type-deadline_warning {
    background: rgba(217, 119, 6, 0.16);
    color: #b45309;
}

.priority-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-low { background: #e8f5e8; color: #2e7d32; }
.priority-medium { background: #fff3e0; color: #f57c00; }
.priority-high { background: #fff8e1; color: #f9a825; }
.priority-critical { background: #ffebee; color: #d32f2f; }

.progress-bar {
    width: 100%;
    height: 8px;
    background: rgba(148, 163, 184, 0.25);
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 6px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--notification-accent, #2563eb) 0%, rgba(14, 165, 233, 0.85) 100%);
    border-radius: inherit;
    transition: width 0.35s ease;
}

.filters {
    display: flex;
    gap: 10px;
}

.filters select {
    padding: 6px 12px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
}

.status-expired {
    background-color: #ffebee;
    color: #d32f2f;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(7px);
    z-index: 2500;
    padding: clamp(1.75rem, 3vw, 3.5rem);
    overflow-y: auto;
    align-items: center;
    justify-content: center;
}

.modal.open,
.modal[style*="display: block"],
.modal[style*="display:block"],
.modal[style*="display: flex"],
.modal[style*="display:flex"] {
    display: flex !important;
}

.modal-content {
    background: #ffffff;
    border-radius: 24px;
    box-shadow: 0 30px 70px rgba(15, 23, 42, 0.35);
    width: min(560px, 100%);
}

.modal-content.modal-lg {
    width: min(760px, 100%);
}

.modal-content.modal-xl {
    width: min(960px, 100%);
}

.modal.open .modal-content {
    animation: modal-bloom 0.32s ease forwards;
}

@keyframes modal-bloom {
    from {
        transform: translateY(24px) scale(0.97);
        opacity: 0;
    }
    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 26px 36px;
    border-radius: 24px 24px 0 0;
    background: linear-gradient(135deg, #1d4ed8 0%, #0ea5e9 100%);
    color: #ffffff;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.modal-close {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff;
    font-size: 1.65rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.25s ease, background 0.25s ease;
}

.modal-close:hover,
.modal-close:focus {
    background: rgba(255, 255, 255, 0.28);
    transform: rotate(90deg);
}

.modal-form {
    padding: 0 36px 32px;
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.modal-body {
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.modal-intro {
    display: flex;
    gap: 18px;
    align-items: flex-start;
    background: #f1f5f9;
    border-radius: 18px;
    padding: 18px 22px;
    border: 1px solid rgba(148, 163, 184, 0.25);
}

.modal-intro-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    color: #ffffff;
    flex-shrink: 0;
}

.modal-intro-icon.notification {
    background: linear-gradient(135deg, #6366f1 0%, #2563eb 100%);
}

.modal-intro-content h4 {
    margin: 0 0 6px;
    color: #0f172a;
    font-size: 1.18rem;
    font-weight: 700;
}

.modal-intro-content p {
    margin: 0;
    color: #475569;
    font-size: 0.95rem;
    line-height: 1.6;
}

.modal-grid {
    display: grid;
    gap: 24px;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.modal-form label,
.modal-form .control-label {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.95rem;
}

.modal-form input,
.modal-form select,
.modal-form textarea {
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 12px 14px;
    font-size: 0.95rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background: #ffffff;
}

.modal-form input:focus,
.modal-form select:focus,
.modal-form textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    outline: none;
}

.modal-form textarea {
    resize: vertical;
    min-height: 120px;
}

.modal-form .form-help {
    color: #64748b;
    font-size: 0.85rem;
    margin-top: 8px;
    display: block;
    line-height: 1.6;
}

.type-pill-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.type-pill {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 10px 16px;
    font-weight: 600;
    font-size: 0.9rem;
    color: #1d4ed8;
    background: rgba(37, 99, 235, 0.08);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease;
}

.type-pill input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.type-pill:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
}

.type-pill input:checked ~ span {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    padding: 8px 14px;
    border-radius: inherit;
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);
}

.type-pill input:checked ~ span::after {
    content: '\2713';
    margin-left: 8px;
    font-size: 0.75rem;
}

.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.message-stack {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
    gap: 24px;
}

.label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.char-count {
    font-size: 0.85rem;
    color: #64748b;
}

.message-tips {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
    color: #475569;
    font-size: 0.85rem;
}

.message-tips span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    background: #f1f5f9;
}

.message-preview {
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
    border-radius: 18px;
    color: #e2e8f0;
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
}

.preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 0.8rem;
    color: rgba(226, 232, 240, 0.75);
}

.preview-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    background: rgba(14, 165, 233, 0.15);
    color: #38bdf8;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.04em;
}

.preview-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #f8fafc;
}

.preview-list {
    margin: 0;
    padding-left: 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 0.9rem;
}

.preview-list li::marker {
    color: #38bdf8;
}

.preview-footer {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: rgba(226, 232, 240, 0.8);
}

.targeting-grid {
    display: grid;
    gap: 24px;
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
}

.targeting-card,
.schedule-card {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 22px;
    background: #ffffff;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.targeting-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
}

.targeting-header h4,
.schedule-card h4 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}

.target-summary-chip {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 999px;
    background: rgba(37, 99, 235, 0.1);
    color: #1d4ed8;
    font-weight: 600;
    font-size: 0.85rem;
}

.targeting-controls {
    display: grid;
    gap: 18px;
}

.chip-select {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.chip-select.lgu {
    max-height: 220px;
    overflow-y: auto;
    padding-right: 4px;
}

.chip-option {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 16px;
    border-radius: 12px;
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.chip-option input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.chip-option:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.chip-option input:checked ~ span {
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    color: #ffffff;
    padding: 8px 14px;
    border-radius: inherit;
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);
}

.schedule-grid {
    display: grid;
    gap: 18px;
}

.form-hint-block {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px dashed rgba(37, 99, 235, 0.25);
    color: #1e293b;
    font-size: 0.92rem;
    align-items: flex-start;
}

.form-hint-block i {
    color: #2563eb;
    font-size: 1.1rem;
    margin-top: 2px;
}

.modal-hints {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.hint-item {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 14px;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.88rem;
    flex: 1 1 220px;
}

.hint-item i {
    color: #2563eb;
    font-size: 1rem;
}

.modal-form .form-actions {
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.modal-form .form-actions .btn {
    min-width: 160px;
    font-weight: 600;
}

body.modal-open {
    overflow: hidden;
}

#deleteModal .modal-header {
    background: linear-gradient(135deg, #fb7185 0%, #ef4444 100%);
}

#deleteModal .modal-form {
    padding: 0 32px 28px;
}

#deleteModal .modal-form p {
    color: #475569;
    font-size: 0.95rem;
    line-height: 1.6;
}

@media (max-width: 992px) {
    .notification-card {
        box-shadow: none;
        border-radius: 12px;
        border-left-width: 4px;
    }
    .notification-card-message {
        line-clamp: 3;
        -webkit-line-clamp: 3;
    }
    .message-stack {
        grid-template-columns: 1fr;
    }
    .message-preview {
        order: -1;
    }
    .targeting-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .modal {
        padding: 1.5rem;
    }
    .modal-header {
        padding: 22px 24px;
    }
    .modal-form {
        padding: 0 24px 24px;
    }
    .modal-intro {
        flex-direction: column;
    }
    .chip-select.lgu {
        max-height: 180px;
    }
    .targeting-card,
    .schedule-card {
        padding: 18px;
    }
}
</style>

<script>
const NOTIFICATION_TYPES = {
    system: 'System',
    disaster_assigned: 'Disaster Assigned',
    status_update: 'Status Update',
    escalation: 'Escalation',
    deadline_warning: 'Deadline Warning'
};

let notificationsDataTable;
let activeTypeFilter = '';
let activeStatusFilter = '';
let updatePaginationStatus = function() {};
const NOTIFICATIONS_REFRESH_INTERVAL = 30000;
let notificationsRefreshTimer = null;
let isRefreshingNotifications = false;

if (typeof $ !== 'undefined' && $.fn && $.fn.dataTable) {
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!settings.nTable || settings.nTable.id !== 'notificationsTable') {
            return true;
        }
        if (!notificationsDataTable) {
            return true;
        }
        const row = notificationsDataTable.row(dataIndex).node();
        if (!row) {
            return true;
        }
        const rowType = (row.getAttribute('data-notification-type') || '').toLowerCase();
        const rowStatus = (row.getAttribute('data-notification-status') || '').toLowerCase();

        if (activeTypeFilter && rowType !== activeTypeFilter) {
            return false;
        }
        if (activeStatusFilter && rowStatus !== activeStatusFilter) {
            return false;
        }
        return true;
    });
}

function safeFocus(element) {
    if (!element || typeof element.focus !== 'function') {
        return;
    }
    try {
        element.focus({ preventScroll: true });
    } catch (error) {
        element.focus();
    }
}

function openModal(modal) {
    if (!modal) return;
    modal.style.display = 'flex';
    modal.classList.add('open');
    document.body.classList.add('modal-open');
}

function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('open');
    modal.style.display = 'none';
    if (!document.querySelector('.modal.open')) {
        document.body.classList.remove('modal-open');
    }
}

function getNotificationData(trigger) {
    if (!trigger) return null;
    const row = trigger.closest('tr[data-notification]');
    if (!row) return null;
    const raw = row.getAttribute('data-notification');
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch (error) {
        console.error('Unable to parse notification payload', error);
        return null;
    }
}

function renderMessageInto(container, lines, fallbackText) {
    if (!container) return;
    container.innerHTML = '';

    const sanitizedLines = Array.isArray(lines)
        ? lines.filter(line => typeof line === 'string' && line.trim() !== '')
        : [];

    if (sanitizedLines.length === 0) {
        const paragraph = document.createElement('p');
        paragraph.className = 'empty-message';
        paragraph.textContent = fallbackText || 'No message details provided.';
        container.appendChild(paragraph);
        return;
    }

    if (sanitizedLines.length === 1) {
        const paragraph = document.createElement('p');
        paragraph.className = 'single-line';
        paragraph.textContent = sanitizedLines[0];
        container.appendChild(paragraph);
        return;
    }

    const list = document.createElement('ul');
    sanitizedLines.forEach(line => {
        const li = document.createElement('li');
        li.textContent = line;
        list.appendChild(li);
    });
    container.appendChild(list);
}

function renderPreviewList(container, lines) {
    if (!container) return;
    container.innerHTML = '';

    const sanitizedLines = lines.filter(line => line.trim() !== '');
    const fallback = [
        'Outline the key impact areas.',
        'Add a response action or escalation path.',
        'Share contact details for coordination.'
    ];
    const items = sanitizedLines.length > 0 ? sanitizedLines : fallback;

    items.forEach(value => {
        const li = document.createElement('li');
        li.textContent = value.trim();
        container.appendChild(li);
    });
}

function updatePreviewType(value) {
    const select = document.getElementById('type');
    const previewChip = document.querySelector('[data-preview-type]');
    if (select) {
        select.value = value;
    }
    if (previewChip) {
        previewChip.textContent = NOTIFICATION_TYPES[value] || 'Notification';
    }
}

function escapeHTML(value) {
    if (value === null || value === undefined) {
        return '';
    }
    return value.toString().replace(/[&<>"']/g, match => {
        switch (match) {
            case '&': return '&amp;';
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '"': return '&quot;';
            case "'": return '&#39;';
            default: return match;
        }
    });
}

function escapeAttribute(value) {
    return escapeHTML(value).replace(/`/g, '&#96;');
}

function createNotificationRow(entry) {
    const payload = entry.payload || {};
    const mergedPayload = {
        ...payload,
        message_mode: entry.message_mode ?? payload.message_mode ?? 'empty',
        message_preview_lines: entry.message_preview_lines ?? payload.message_preview_lines ?? [],
        additional_lines_count: entry.additional_lines_count ?? payload.additional_lines_count ?? 0,
        message_single: entry.message_single ?? payload.message_single ?? ''
    };

    const accentColor = entry.accent_color || mergedPayload.accent_color || '#2563eb';
    const type = (entry.type || mergedPayload.type || 'system').toLowerCase();
    const typeLabel = entry.type_label || mergedPayload.type_label || NOTIFICATION_TYPES[type] || 'Notification';
    const statusClass = (entry.status_class || mergedPayload.status_class || '').toLowerCase();
    const statusText = entry.status_text || mergedPayload.status_text || '';
    const typeIcon = entry.type_icon || mergedPayload.type_icon || 'fas fa-bell';
    const targetIcon = entry.target_icon || mergedPayload.target_icon || 'fas fa-users';
    const statusIcon = entry.status_icon || mergedPayload.status_icon || 'fas fa-info-circle';
    const createdBy = entry.created_by || mergedPayload.created_by || 'System';
    const createdAtFormatted = entry.created_at_formatted || mergedPayload.created_at_formatted || '';
    const createdAtIso = entry.created_at_iso || mergedPayload.created_at || '';

    const rawReadRate = Number.isFinite(Number(entry.read_rate)) ? Number(entry.read_rate) : Number(mergedPayload.read_rate) || 0;
    const clampedReadRate = Math.min(100, Math.max(0, rawReadRate));
    const readCount = Number.isFinite(Number(entry.read_count)) ? Number(entry.read_count) : Number(mergedPayload.read_count) || 0;
    const totalRecipients = Number.isFinite(Number(entry.total_recipients)) ? Number(entry.total_recipients) : Number(mergedPayload.total_recipients) || 0;
    const targetDescription = entry.target_description || mergedPayload.target_description || 'All Users';
    const readRatePercentText = `${clampedReadRate.toFixed(1)}%`;
    const readRateDetailText = `(${readCount}/${totalRecipients})`;

    const payloadMessageLines = Array.isArray(mergedPayload.message_lines) ? mergedPayload.message_lines : [];
    const previewLines = Array.isArray(entry.message_preview_lines) && entry.message_preview_lines.length > 0
        ? entry.message_preview_lines
        : (Array.isArray(mergedPayload.message_preview_lines) ? mergedPayload.message_preview_lines : payloadMessageLines.slice(0, 3));
    const additionalLinesCount = Number(entry.additional_lines_count ?? mergedPayload.additional_lines_count ?? Math.max(payloadMessageLines.length - previewLines.length, 0));

    let messageHtml = '<p class="notification-card-message empty">No message details provided.</p>';
    if ((entry.message_mode || mergedPayload.message_mode) === 'single' && (entry.message_single || mergedPayload.message_single)) {
        const singleLine = entry.message_single || mergedPayload.message_single || '';
        if (singleLine.trim() !== '') {
            messageHtml = `<p class="notification-card-message single-line">${escapeHTML(singleLine)}</p>`;
        }
    } else if ((entry.message_mode || mergedPayload.message_mode) === 'list' && previewLines.length > 0) {
        const items = previewLines.map(line => `<li>${escapeHTML(line)}</li>`);
        if (additionalLinesCount > 0) {
            items.push(`<li class="notification-card-list-more">+${escapeHTML(additionalLinesCount)} more details</li>`);
        }
        messageHtml = `<ul class="notification-card-list">${items.join('')}</ul>`;
    } else if (payloadMessageLines.length === 1) {
        messageHtml = `<p class="notification-card-message single-line">${escapeHTML(payloadMessageLines[0])}</p>`;
    } else if (payloadMessageLines.length > 1) {
        const items = payloadMessageLines.slice(0, 3).map(line => `<li>${escapeHTML(line)}</li>`);
        if (payloadMessageLines.length > 3) {
            items.push(`<li class="notification-card-list-more">+${escapeHTML(payloadMessageLines.length - 3)} more details</li>`);
        }
        messageHtml = `<ul class="notification-card-list">${items.join('')}</ul>`;
    }

    const hasExpiry = Boolean(entry.has_expiry ?? (mergedPayload.expires_at ? true : false));
    const isExpired = Boolean(entry.is_expired ?? (mergedPayload.status_class === 'expired'));
    const expiresFormatted = entry.expires_at_formatted || mergedPayload.expires_at_formatted || '';
    const expiresIso = entry.expires_at_iso || mergedPayload.expires_at || '';

    let footerExpiryTag = '<span class="notification-card-tag"><i class="fas fa-infinity" aria-hidden="true"></i>No expiry set</span>';
    if (hasExpiry) {
        const tagClass = isExpired ? 'expired' : 'active';
        const tagLabel = isExpired ? 'Expired ' : 'Expires ';
        footerExpiryTag = `<span class="notification-card-tag ${tagClass}"><i class="fas fa-hourglass-end" aria-hidden="true"></i>${tagLabel}${escapeHTML(expiresFormatted)}</span>`;
    }

    const expiresCellContent = hasExpiry
        ? `<div class="timestamp-stack"><time datetime="${escapeAttribute(expiresIso)}">${escapeHTML(expiresFormatted)}</time><span class="timestamp-label">${isExpired ? 'Expired' : 'Expires'}</span></div>`
        : '<span class="timestamp-stack no-expiry"><i class="fas fa-infinity" aria-hidden="true"></i><span>No expiry</span></span>';

    const datasetString = JSON.stringify(mergedPayload)
        .replace(/</g, '\\u003c')
        .replace(/>/g, '\\u003e')
        .replace(/&/g, '\\u0026');

    const row = document.createElement('tr');
    row.className = 'notification-row';
    row.style.setProperty('--notification-accent', accentColor);
    row.setAttribute('data-notification', datasetString);
    row.dataset.notificationType = type;
    row.dataset.notificationStatus = statusClass;

    row.innerHTML = `
        <td>
            <input type="checkbox" class="notification-checkbox" value="${escapeAttribute(entry.id ?? mergedPayload.id ?? '')}">
        </td>
        <td class="notification-main">
            <article class="notification-card" tabindex="0">
                <span class="notification-card-accent" aria-hidden="true"></span>
                <header class="notification-card-header">
                    <div class="notification-card-heading">
                        <span class="notification-type-chip type-${escapeAttribute(type)}">
                            <i class="${escapeAttribute(typeIcon)}" aria-hidden="true"></i>
                            ${escapeHTML(typeLabel)}
                        </span>
                        <h4 class="notification-card-title">${escapeHTML(entry.title || mergedPayload.title || '')}</h4>
                    </div>
                    <div class="notification-card-meta">
                        <span class="notification-issued"><i class="fas fa-user-shield"></i>
                            ${escapeHTML(createdBy)}
                        </span>
                        <span class="notification-timestamp"><i class="fas fa-calendar-day"></i>
                            ${escapeHTML(createdAtFormatted)}
                        </span>
                    </div>
                </header>

                <div class="notification-card-body">
                    ${messageHtml}
                </div>

                <footer class="notification-card-footer">
                    <div class="notification-card-tags">
                        <span class="notification-card-tag"><i class="${escapeAttribute(targetIcon)}" aria-hidden="true"></i>${escapeHTML(targetDescription)}</span>
                        ${footerExpiryTag}
                    </div>
                </footer>
            </article>
        </td>
        <td>
            <span class="type-badge type-${escapeAttribute(type)}">
                <i class="${escapeAttribute(typeIcon)}" aria-hidden="true"></i>
                ${escapeHTML(typeLabel)}
            </span>
        </td>
        <td>
            <span class="data-chip target-chip">
                <i class="${escapeAttribute(targetIcon)}" aria-hidden="true"></i>
                ${escapeHTML(targetDescription)}
            </span>
        </td>
        <td>
            <div class="metric-block">
                <span class="metric-value">${escapeHTML(String(totalRecipients))}</span>
                <span class="metric-label">Recipients</span>
            </div>
        </td>
        <td>
            <div class="read-rate-card">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${clampedReadRate}%"></div>
                </div>
                <span class="read-rate-label">${escapeHTML(readRatePercentText)} <span>${escapeHTML(readRateDetailText)}</span></span>
            </div>
        </td>
        <td>
            <span class="status-badge status-${escapeAttribute(statusClass)}">
                <i class="${escapeAttribute(statusIcon)}" aria-hidden="true"></i>
                ${escapeHTML(statusText)}
            </span>
        </td>
        <td>
            <div class="timestamp-stack">
                <time datetime="${escapeAttribute(createdAtIso)}">${escapeHTML(createdAtFormatted)}</time>
                <span class="timestamp-label">Created</span>
            </div>
        </td>
        <td>
            ${expiresCellContent}
        </td>
        <td>
            <div class="action-buttons">
                <button onclick="viewNotification(this)" class="btn btn-xs btn-info" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button onclick="editNotification(this)" class="btn btn-xs btn-secondary" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteNotification(this)" class="btn btn-xs btn-danger" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    `;

    return row;
}

function rebuildNotificationsTable(notifications) {
    const rows = [];
    notifications.forEach(entry => {
        try {
            rows.push(createNotificationRow(entry));
        } catch (error) {
            console.error('Failed to render notification row', error, entry);
        }
    });

    if (notificationsDataTable && typeof notificationsDataTable.clear === 'function') {
        notificationsDataTable.clear();
        if (rows.length > 0) {
            notificationsDataTable.rows.add($(rows));
        }
        notificationsDataTable.draw(false);
        if (typeof updatePaginationStatus === 'function') {
            updatePaginationStatus();
        }
    } else {
        const tbody = document.querySelector('#notificationsTable tbody');
        if (tbody) {
            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
        }
    }

    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.checked = false;
    }

    filterNotifications();
}

function updateStats(stats) {
    if (!stats || typeof stats !== 'object') {
        return;
    }
    const totalEl = document.querySelector('[data-stat-total]');
    const activeEl = document.querySelector('[data-stat-active]');
    const validEl = document.querySelector('[data-stat-valid]');
    const expiredEl = document.querySelector('[data-stat-expired]');

    if (totalEl) totalEl.textContent = stats.total_notifications ?? 0;
    if (activeEl) activeEl.textContent = stats.active_notifications ?? 0;
    if (validEl) validEl.textContent = stats.valid_notifications ?? 0;
    if (expiredEl) expiredEl.textContent = stats.expired_notifications ?? 0;
}

function refreshNotifications() {
    if (document.hidden) {
        return;
    }
    if (isRefreshingNotifications) {
        return;
    }
    isRefreshingNotifications = true;

    fetch(`../api/get_notifications.php?t=${Date.now()}`, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
    })
        .then(response => {
            if (response.status === 401) {
                if (notificationsRefreshTimer) {
                    clearInterval(notificationsRefreshTimer);
                    notificationsRefreshTimer = null;
                }
            
                window.location.href = '../login.php?timeout=1';
                return null;
            }
            if (!response.ok) {
                throw new Error(`Failed to load notifications (${response.status})`);
            }
            return response.json();
        })
        .then(body => {
            if (!body) {
                return;
            }
            if (!body.success || !body.data) {
                throw new Error(body.message || 'Unable to load notifications.');
            }
            rebuildNotificationsTable(body.data.notifications || []);
            updateStats(body.data.stats || {});
        })
        .catch(error => {
            console.error('refreshNotifications error:', error);
        })
        .finally(() => {
            isRefreshingNotifications = false;
        });
}

function updateTargetSummary() {
    const roleSelect = document.getElementById('target_role');
    const lguSelect = document.getElementById('target_lgu_id');
    const summary = document.querySelector('[data-target-summary]');
    const previewFooter = document.querySelector('[data-preview-target]');

    const roleLabel = roleSelect && roleSelect.value !== 'all'
        ? (roleSelect.value === 'admin' ? 'Admins' : 'Reporters')
        : 'All roles';

    let lguLabel = 'All LGUs';
    if (lguSelect && lguSelect.value !== 'all') {
        const option = lguSelect.querySelector(`option[value="${lguSelect.value}"]`);
        lguLabel = option ? option.textContent.trim() : 'Selected LGU';
    }

    const text = `${roleLabel} • ${lguLabel}`;
    if (summary) {
        summary.textContent = text;
    }
    if (previewFooter) {
        previewFooter.innerHTML = `<i class="fas fa-users"></i> ${text}`;
    }
}

function syncChipGroup(chipName, select) {
    const chips = document.querySelectorAll(`input[name="${chipName}"]`);
    chips.forEach(chip => {
        chip.addEventListener('change', () => {
            if (select) {
                select.value = chip.value;
                select.dispatchEvent(new Event('change'));
            }
        });
    });
}

function resetCreateNotificationForm() {
    const form = document.getElementById('create-notification-form');
    const editForm = document.getElementById('edit-notification-form');
    const bulkForm = document.getElementById('bulk-actions-form');
    if (!form) return;

    form.reset();

    // Reset type pill selection
    const defaultType = 'system';
    document.querySelectorAll('.type-pill input').forEach(input => {
        input.checked = input.value === defaultType;
    });
    updatePreviewType(defaultType);

    // Reset chips for targeting
    document.querySelectorAll('.chip-option input[name="target_role_chip"]').forEach(input => {
        input.checked = input.value === 'all';
    });
    const roleSelect = document.getElementById('target_role');
    if (roleSelect) {
        roleSelect.value = 'all';
    }

    document.querySelectorAll('.chip-option input[name="target_lgu_chip"]').forEach(input => {
        input.checked = input.value === 'all';
    });
    const lguSelect = document.getElementById('target_lgu_id');
    if (lguSelect) {
        lguSelect.value = 'all';
    }

    updateTargetSummary();

    // Reset title and message previews
    const titleInput = document.querySelector('[data-title-input]');
    const titleCounter = document.querySelector('[data-title-count]');
    const previewTitle = document.querySelector('[data-preview-title]');
    if (titleInput) {
        titleInput.value = '';
    }
    if (titleCounter) {
        titleCounter.textContent = '0/120';
    }
    if (previewTitle) {
        previewTitle.textContent = 'Typhoon Alert: High Winds Expected';
    }

    const messageInput = document.querySelector('[data-message-input]');
    const messageCounter = document.querySelector('[data-message-count]');
    const previewBody = document.querySelector('[data-preview-body]');
    if (messageInput) {
        messageInput.value = '';
    }
    if (messageCounter) {
        messageCounter.textContent = '0 / 600';
    }
    renderPreviewList(previewBody, []);
}

function showCreateModal() {
    const modal = document.getElementById('createNotificationModal');
    if (!modal) return;

    resetCreateNotificationForm();
    openModal(modal);

    requestAnimationFrame(() => {
        const titleField = document.getElementById('title');
        safeFocus(titleField);
    });
}

function closeCreateModal() {
    const modal = document.getElementById('createNotificationModal');
    if (!modal) return;

    closeModal(modal);
    resetCreateNotificationForm();
}

function viewNotification(trigger) {
    const data = getNotificationData(trigger);
    const modal = document.getElementById('viewNotificationModal');
    if (!data || !modal) {
        return;
    }

    const typeChip = modal.querySelector('[data-view-type]');
    const statusChip = modal.querySelector('[data-view-status]');
    const titleEl = modal.querySelector('[data-view-title]');
    const createdByEl = modal.querySelector('[data-view-created-by]');
    const createdAtEl = modal.querySelector('[data-view-created-at]');
    const expiryWrapper = modal.querySelector('[data-view-expiry-wrapper]');
    const expiryEl = modal.querySelector('[data-view-expiry]');
    const targetEl = modal.querySelector('[data-view-target]');
    const messageContainer = modal.querySelector('[data-view-message]');
    const readRateEl = modal.querySelector('[data-view-read-rate]');
    const readCountEl = modal.querySelector('[data-view-read-count]');

    if (typeChip) {
        typeChip.textContent = data.type_label || NOTIFICATION_TYPES[data.type] || 'Notification';
    }
    if (statusChip) {
        statusChip.textContent = data.status_text || 'Status';
        statusChip.classList.remove('status-active', 'status-inactive', 'status-expired');
        if (data.status_class) {
            statusChip.classList.add(`status-${data.status_class}`);
        }
    }
    if (titleEl) {
        titleEl.textContent = data.title || 'Untitled notification';
    }
    if (createdByEl) {
        createdByEl.textContent = data.created_by || 'System';
    }
    if (createdAtEl) {
        createdAtEl.textContent = data.created_at_formatted || data.created_at || '';
    }
    if (targetEl) {
        targetEl.textContent = data.target_description || 'All Users';
    }
    if (readRateEl) {
        const readRateValue = Number(data.read_rate ?? 0);
        readRateEl.textContent = `${readRateValue.toFixed(1)}%`;
    }
    if (readCountEl) {
        readCountEl.textContent = `${data.read_count ?? 0}/${data.total_recipients ?? 0}`;
    }

    if (expiryWrapper && expiryEl) {
        if (data.expires_at) {
            expiryWrapper.style.display = '';
            const formattedExpiry = data.expires_at_formatted || data.expires_at;
            expiryEl.textContent = data.status_class === 'expired'
                ? `Expired ${formattedExpiry}`
                : `Expires ${formattedExpiry}`;
        } else {
            expiryWrapper.style.display = 'none';
        }
    }

    renderMessageInto(messageContainer, data.message_lines || [], 'No message details provided.');

    openModal(modal);
    requestAnimationFrame(() => {
        const closeButton = modal.querySelector('.modal-close');
        safeFocus(closeButton);
    });
}

function closeViewNotificationModal() {
    const modal = document.getElementById('viewNotificationModal');
    closeModal(modal);
}

function editNotification(trigger) {
    const data = getNotificationData(trigger);
    const modal = document.getElementById('editNotificationModal');
    if (!data || !modal) {
        return;
    }

    const idInput = document.getElementById('edit-notification-id');
    const titleInput = document.getElementById('edit-title');
    const typeSelect = document.getElementById('edit-type');
    const messageInput = document.getElementById('edit-message');
    const expiresInput = document.getElementById('edit-expires-at');
    const activeInput = document.getElementById('edit-is-active');

    if (idInput) {
        idInput.value = data.id ?? '';
    }
    if (titleInput) {
        titleInput.value = data.title || '';
    }
    if (typeSelect) {
        typeSelect.value = data.type || 'system';
    }
    if (messageInput) {
        messageInput.value = (data.message || '').trim();
    }
    if (expiresInput) {
        expiresInput.value = data.expires_at_local || '';
    }
    if (activeInput) {
        activeInput.checked = Boolean(Number(data.is_active));
    }

    openModal(modal);
    requestAnimationFrame(() => safeFocus(titleInput));
}

function closeEditNotificationModal() {
    const modal = document.getElementById('editNotificationModal');
    if (!modal) return;
    closeModal(modal);
    const form = document.getElementById('edit-notification-form');
    if (form) {
        form.reset();
    }
}

function deleteNotification(trigger) {
    const data = getNotificationData(trigger);
    const modal = document.getElementById('deleteModal');
    const hiddenInput = document.getElementById('delete-notification-id');
    const titleEl = document.getElementById('delete-notification-title');

    if (hiddenInput) {
        hiddenInput.value = data?.id ?? '';
    }
    if (titleEl) {
        titleEl.textContent = data?.title || 'this notification';
    }

    if (modal) {
        openModal(modal);
        requestAnimationFrame(() => {
            const deleteButton = modal.querySelector('button[type="submit"]');
            safeFocus(deleteButton);
        });
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (!modal) return;

    closeModal(modal);
    const hiddenInput = document.getElementById('delete-notification-id');
    if (hiddenInput) {
        hiddenInput.value = '';
    }
}

function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function filterNotifications() {
    const typeSelect = document.getElementById('typeFilter');
    const statusSelect = document.getElementById('statusFilter');

    activeTypeFilter = typeSelect && typeSelect.value ? typeSelect.value.toLowerCase() : '';
    activeStatusFilter = statusSelect && statusSelect.value ? statusSelect.value.toLowerCase() : '';

    if (notificationsDataTable) {
        notificationsDataTable.page('first').draw('page');
        return;
    }

    // Fallback filtering without DataTables
    const rows = document.querySelectorAll('#notificationsTable tbody tr');
    rows.forEach(row => {
        const rowType = (row.getAttribute('data-notification-type') || '').toLowerCase();
        const rowStatus = (row.getAttribute('data-notification-status') || '').toLowerCase();
        const typeMatch = !activeTypeFilter || rowType === activeTypeFilter;
        const statusMatch = !activeStatusFilter || rowStatus === activeStatusFilter;
        row.style.display = (typeMatch && statusMatch) ? '' : 'none';
    });
}

function showBulkActionsModal() {
    const modal = document.getElementById('bulkActionsModal');
    if (!modal) return;

    const selected = Array.from(document.querySelectorAll('.notification-checkbox:checked'));
    if (selected.length === 0) {
        alert('Please select notifications to perform bulk actions.');
        return;
    }

    const idsContainer = document.getElementById('bulk-selected-ids');
    const countEl = modal.querySelector('[data-bulk-count]');
    const warning = modal.querySelector('[data-bulk-warning]');
    const radios = modal.querySelectorAll('input[name="bulk_action_type"]');

    if (idsContainer) {
        idsContainer.innerHTML = '';
        selected.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'bulk_notification_ids[]';
            input.value = checkbox.value;
            idsContainer.appendChild(input);
        });
    }

    if (countEl) {
        countEl.textContent = selected.length;
    }

    if (warning) {
        warning.hidden = true;
    }

    radios.forEach(radio => {
        radio.checked = false;
    });

    openModal(modal);
    requestAnimationFrame(() => {
        const firstRadio = modal.querySelector('input[name="bulk_action_type"]');
        safeFocus(firstRadio);
    });
}

function closeBulkActionsModal() {
    const modal = document.getElementById('bulkActionsModal');
    if (!modal) return;
    closeModal(modal);
    const idsContainer = document.getElementById('bulk-selected-ids');
    if (idsContainer) {
        idsContainer.innerHTML = '';
    }
    const warning = modal.querySelector('[data-bulk-warning]');
    if (warning) {
        warning.hidden = true;
    }
}

document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        if (notificationsRefreshTimer) {
            clearInterval(notificationsRefreshTimer);
            notificationsRefreshTimer = null;
        }
        return;
    }

    refreshNotifications();
    if (!notificationsRefreshTimer) {
        notificationsRefreshTimer = setInterval(refreshNotifications, NOTIFICATIONS_REFRESH_INTERVAL);
    }
});

$(document).ready(function() {
    const prevBtn = document.querySelector('[data-pagination-prev]');
    const nextBtn = document.querySelector('[data-pagination-next]');
    const statusEl = document.querySelector('[data-pagination-status]');

    notificationsDataTable = $('#notificationsTable').DataTable({
        order: [[7, 'desc']],
        pageLength: 10,
        lengthChange: false,
        dom: 'rt',
        columnDefs: [
            { orderable: false, targets: [0, 9] }
        ]
    });

    updatePaginationStatus = function() {
        if (!notificationsDataTable || !statusEl) {
            return;
        }
        const info = notificationsDataTable.page.info();
        if (!info) {
            statusEl.textContent = 'Showing 0';
            if (prevBtn) prevBtn.disabled = true;
            if (nextBtn) nextBtn.disabled = true;
            return;
        }

        if (info.recordsDisplay === 0) {
            statusEl.textContent = 'No notifications to show';
        } else {
            statusEl.textContent = `Showing ${info.start + 1}-${info.end} of ${info.recordsDisplay}`;
        }

        if (prevBtn) {
            prevBtn.disabled = info.page === 0 || info.recordsDisplay === 0;
        }
        if (nextBtn) {
            nextBtn.disabled = info.page >= info.pages - 1 || info.recordsDisplay === 0;
        }
    };

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            notificationsDataTable.page('previous').draw('page');
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            notificationsDataTable.page('next').draw('page');
        });
    }

    notificationsDataTable.on('draw', updatePaginationStatus);
    updatePaginationStatus();
    filterNotifications();

    if (!document.hidden) {
        refreshNotifications();
        if (notificationsRefreshTimer) {
            clearInterval(notificationsRefreshTimer);
        }
        notificationsRefreshTimer = setInterval(refreshNotifications, NOTIFICATIONS_REFRESH_INTERVAL);
    }
});

document.addEventListener('click', function(event) {
    if (!event.target.classList || !event.target.classList.contains('modal') || !event.target.classList.contains('open')) {
        return;
    }
    if (event.target.id === 'createNotificationModal') {
        closeCreateModal();
    } else if (event.target.id === 'deleteModal') {
        closeDeleteModal();
    } else if (event.target.id === 'viewNotificationModal') {
        closeViewNotificationModal();
    } else if (event.target.id === 'editNotificationModal') {
        closeEditNotificationModal();
    } else if (event.target.id === 'bulkActionsModal') {
        closeBulkActionsModal();
    } else {
        closeModal(event.target);
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') {
        return;
    }
    const openModals = document.querySelectorAll('.modal.open');
    openModals.forEach(modal => {
        if (modal.id === 'createNotificationModal') {
            closeCreateModal();
        } else if (modal.id === 'deleteModal') {
            closeDeleteModal();
        } else if (modal.id === 'viewNotificationModal') {
            closeViewNotificationModal();
        } else if (modal.id === 'editNotificationModal') {
            closeEditNotificationModal();
        } else if (modal.id === 'bulkActionsModal') {
            closeBulkActionsModal();
        } else {
            closeModal(modal);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create-notification-form');
    const editForm = document.getElementById('edit-notification-form');
    const bulkForm = document.getElementById('bulk-actions-form');
    const titleInput = document.querySelector('[data-title-input]');
    const titleCounter = document.querySelector('[data-title-count]');
    const previewTitle = document.querySelector('[data-preview-title]');
    const messageInput = document.querySelector('[data-message-input]');
    const messageCounter = document.querySelector('[data-message-count]');
    const previewBody = document.querySelector('[data-preview-body]');

    if (titleInput && titleCounter && previewTitle) {
        titleInput.addEventListener('input', () => {
            const length = titleInput.value.length;
            titleCounter.textContent = `${length}/120`;
            previewTitle.textContent = length > 0
                ? titleInput.value.trim()
                : 'Typhoon Alert: High Winds Expected';
        });
    }

    if (messageInput && messageCounter && previewBody) {
        messageInput.addEventListener('input', () => {
            const length = messageInput.value.length;
            messageCounter.textContent = `${length} / 600`;
            const lines = messageInput.value.split(/\r?\n/).map(line => line.trim());
            renderPreviewList(previewBody, lines);
        });
    }

    const typePills = document.querySelectorAll('.type-pill input');
    typePills.forEach(input => {
        input.addEventListener('change', () => {
            if (!input.checked) return;
            typePills.forEach(other => {
                if (other !== input) {
                    other.checked = false;
                    const span = other.parentElement.querySelector('span');
                    if (span) {
                        span.classList.remove('active');
                    }
                }
            });
            const select = document.getElementById('type');
            updatePreviewType(input.value);
        });
    });

    const hiddenTypeSelect = document.getElementById('type');
    if (hiddenTypeSelect) {
        hiddenTypeSelect.addEventListener('change', () => {
            updatePreviewType(hiddenTypeSelect.value);
        });
    }

    syncChipGroup('target_role_chip', document.getElementById('target_role'));
    syncChipGroup('target_lgu_chip', document.getElementById('target_lgu_id'));

    const roleSelect = document.getElementById('target_role');
    const lguSelect = document.getElementById('target_lgu_id');
    if (roleSelect) {
        roleSelect.addEventListener('change', updateTargetSummary);
    }
    if (lguSelect) {
        lguSelect.addEventListener('change', updateTargetSummary);
    }

    resetCreateNotificationForm();

    if (form) {
        form.addEventListener('submit', () => {
            closeCreateModal();
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', () => {
            closeEditNotificationModal();
        });
    }

    if (bulkForm) {
        bulkForm.addEventListener('change', event => {
            if (event.target.name !== 'bulk_action_type') {
                return;
            }
            const warning = bulkForm.querySelector('[data-bulk-warning]');
            if (warning) {
                warning.hidden = event.target.value !== 'delete';
            }
        });
    }
    
    // Initialize real-time integration
    initializeRealtimeNotifications();
});

// ====================================
// REAL-TIME INTEGRATION FOR NOTIFICATIONS
// ====================================
function initializeRealtimeNotifications() {
    if (!window.realtimeSystem) {
        console.warn('⚠️ RealtimeSystem not available on notifications page');
        return;
    }
    
    let lastNotificationUpdate = Date.now();
    
    // Listen for notification updates
    window.realtimeSystem.registerCallback('onUpdate', (data) => {
        if (data.notification_count !== undefined) {
            const currentTime = Date.now();
            
            // Throttle updates to avoid excessive refreshes (max once every 5 seconds)
            if (currentTime - lastNotificationUpdate > 5000) {
                lastNotificationUpdate = currentTime;
                showNotificationUpdateBanner(data.notification_count);
            }
        }
    });
    
    console.log('✅ Real-time updates enabled for notifications page');
}

function showNotificationUpdateBanner(newCount) {
    // Check if banner already exists
    if (document.getElementById('notification-realtime-banner')) {
        return;
    }
    
    const banner = document.createElement('div');
    banner.id = 'notification-realtime-banner';
    banner.style.cssText = `
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        padding: 14px 20px;
        border-radius: 12px;
        box-shadow: 0 6px 25px rgba(99, 102, 241, 0.4);
        z-index: 10000;
        min-width: 400px;
        animation: notificationSlideDown 0.4s ease-out;
        display: flex;
        align-items: center;
        gap: 14px;
        font-family: 'Inter', sans-serif;
    `;
    
    banner.innerHTML = `
        <div style="
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        ">
            <i class="fas fa-bell"></i>
        </div>
        <div style="flex: 1;">
            <strong style="display: block; font-size: 15px; margin-bottom: 4px;">
                New Notifications Available
            </strong>
            <span style="font-size: 13px; opacity: 0.95;">
                There are new notifications to view
            </span>
        </div>
        <button onclick="location.reload()" style="
            background: white;
            color: #6366f1;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        " onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" 
           onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
            <i class="fas fa-sync-alt"></i> Refresh List
        </button>
        <button onclick="this.parentElement.remove()" style="
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.5);
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        " onmouseover="this.style.borderColor='white'" onmouseout="this.style.borderColor='rgba(255,255,255,0.5)'">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(banner);
    
    // Auto-remove after 15 seconds
    setTimeout(() => {
        if (banner.parentElement) {
            banner.style.animation = 'notificationSlideUp 0.4s ease-out';
            setTimeout(() => banner.remove(), 400);
        }
    }, 15000);
}

// Add notification-specific animations
if (!document.querySelector('#notification-page-animations')) {
    const style = document.createElement('style');
    style.id = 'notification-page-animations';
    style.textContent = `
        @keyframes notificationSlideDown {
            from {
                transform: translate(-50%, -150%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }
        
        @keyframes notificationSlideUp {
            from {
                transform: translate(-50%, 0);
                opacity: 1;
            }
            to {
                transform: translate(-50%, -150%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}
</script>

<?php include 'includes/footer.php'; ?>