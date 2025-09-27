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
            <div class="stat-number"><?php echo $stats['total_notifications']; ?></div>
            <div class="stat-label">Total Notifications</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['active_notifications']; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['valid_notifications']; ?></div>
            <div class="stat-label">Valid</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['expired_notifications']; ?></div>
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
                        <tr>
                            <td>
                                <input type="checkbox" class="notification-checkbox" 
                                       value="<?php echo $notification['notification_id']; ?>">
                            </td>
                            <td>
                                <div class="notification-title">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                    <?php if ($notification['message']): ?>
                                        <div class="notification-preview">
                                            <?php echo htmlspecialchars(substr($notification['message'], 0, 100)) . '...'; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="type-badge type-<?php echo $notification['type']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($notification['target_role'] && $notification['lgu_name']): ?>
                                    <?php echo ucfirst($notification['target_role']) . ' @ ' . $notification['lgu_name']; ?>
                                <?php elseif ($notification['target_role']): ?>
                                    <?php echo ucfirst($notification['target_role']) . ' (All LGUs)'; ?>
                                <?php elseif ($notification['lgu_name']): ?>
                                    <?php echo $notification['lgu_name'] . ' (All Roles)'; ?>
                                <?php else: ?>
                                    All Users
                                <?php endif; ?>
                            </td>
                            <td><?php echo $notification['total_recipients']; ?></td>
                            <td>
                                <?php 
                                $read_rate = $notification['total_recipients'] > 0 
                                    ? ($notification['read_count'] / $notification['total_recipients']) * 100 
                                    : 0;
                                ?>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $read_rate; ?>%"></div>
                                </div>
                                <small><?php echo number_format($read_rate, 1); ?>% (<?php echo $notification['read_count']; ?>/<?php echo $notification['total_recipients']; ?>)</small>
                            </td>
                            <td>
                                <?php
                                $is_expired = $notification['expires_at'] && strtotime($notification['expires_at']) <= time();
                                $status_class = $notification['is_active'] ? ($is_expired ? 'expired' : 'active') : 'inactive';
                                $status_text = $notification['is_active'] ? ($is_expired ? 'Expired' : 'Active') : 'Inactive';
                                ?>
                                <span class="status-badge status-<?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></td>
                            <td>
                                <?php if ($notification['expires_at']): ?>
                                    <?php echo date('M d, Y H:i', strtotime($notification['expires_at'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewNotification(<?php echo $notification['notification_id']; ?>)" 
                                            class="btn btn-xs btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editNotification(<?php echo $notification['notification_id']; ?>)" 
                                            class="btn btn-xs btn-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteNotification(<?php echo $notification['notification_id']; ?>)" 
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
    </div>
</div>

<!-- Create Notification Modal -->
<div id="createNotificationModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Create New Notification</h3>
            <button class="modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" name="title" id="title" required>
            </div>
            
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea name="message" id="message" rows="4" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="type">Type *</label>
                    <select name="type" id="type" required>
                        <option value="system">System</option>
                        <option value="disaster_assigned">Disaster Assigned</option>
                        <option value="status_update">Status Update</option>
                        <option value="escalation">Escalation</option>
                        <option value="deadline_warning">Deadline Warning</option>
                    </select>
                </div>

            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="target_role">Target Role</label>
                    <select name="target_role" id="target_role">
                        <option value="all">All Roles</option>
                        <option value="admin">Admins</option>
                        <option value="moderator">Moderators</option>
                        <option value="lgu_user">LGU Users</option>
                        <option value="citizen">Citizens</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="target_lgu_id">Target LGU</label>
                    <select name="target_lgu_id" id="target_lgu_id">
                        <option value="all">All LGUs</option>
                        <?php foreach ($lgus as $lgu): ?>
                            <option value="<?php echo $lgu['lgu_id']; ?>">
                                <?php echo htmlspecialchars($lgu['lgu_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="expires_at">Expiry Date (Optional)</label>
                <input type="datetime-local" name="expires_at" id="expires_at">
                <small class="form-help">Leave empty for no expiry</small>
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
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="notification_id" id="delete-notification-id">
            
            <p>Are you sure you want to delete this notification? This action cannot be undone.</p>
            
            <div class="form-actions">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="delete_notification" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.notification-title {
    font-weight: 600;
    color: var(--text-color);
}

.notification-preview {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 2px;
}

.type-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.type-info { background: #e3f2fd; color: #1976d2; }
.type-alert { background: #fff3e0; color: #f57c00; }
.type-warning { background: #fff8e1; color: #f9a825; }
.type-emergency { background: #ffebee; color: #d32f2f; }

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
    height: 6px;
    background-color: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 4px;
}

.progress-fill {
    height: 100%;
    background-color: #4caf50;
    transition: width 0.3s ease;
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
</style>

<script>
function showCreateModal() {
    document.getElementById('createNotificationModal').style.display = 'block';
}

function closeCreateModal() {
    document.getElementById('createNotificationModal').style.display = 'none';
    document.querySelector('#createNotificationModal form').reset();
}

function viewNotification(notificationId) {
    // Implementation for viewing notification details
    alert('View notification details - ID: ' + notificationId);
}

function editNotification(notificationId) {
    // Implementation for editing notification
    alert('Edit notification - ID: ' + notificationId);
}

function deleteNotification(notificationId) {
    document.getElementById('delete-notification-id').value = notificationId;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function filterNotifications() {
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#notificationsTable tbody tr');
    
    rows.forEach(row => {
        const typeBadge = row.querySelector('.type-badge');
        const statusBadge = row.querySelector('.status-badge');
        
        const type = typeBadge ? typeBadge.textContent.toLowerCase().replace(/ /g, '_') : '';
        const status = statusBadge ? statusBadge.textContent.toLowerCase() : '';
        
        const typeMatch = !typeFilter || type.includes(typeFilter);
        const statusMatch = !statusFilter || status === statusFilter;
        
        row.style.display = (typeMatch && statusMatch) ? '' : 'none';
    });
}

function showBulkActionsModal() {
    const selected = document.querySelectorAll('.notification-checkbox:checked');
    if (selected.length === 0) {
        alert('Please select notifications to perform bulk actions.');
        return;
    }
    
    // Implementation for bulk actions
    alert('Bulk actions for ' + selected.length + ' notifications');
}

// Initialize DataTable
$(document).ready(function() {
    $('#notificationsTable').DataTable({
        "order": [[ 7, "desc" ]],
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": [0, 9] }
        ]
    });
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>