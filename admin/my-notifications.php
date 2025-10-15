<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

$page_title = 'My Notifications';

// Mark notification as read if requested
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    header('Location: my-notifications.php');
    exit;
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$_SESSION['user_id']]);
    header('Location: my-notifications.php');
    exit;
}

// Delete notification
if (isset($_POST['delete']) && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    header('Location: my-notifications.php');
    exit;
}

// Fetch notifications for current user
try {
    $stmt = $pdo->prepare("
        SELECT n.*, 
               d.disaster_name,
               d.tracking_id,
               d.city,
               dt.type_name as disaster_type
        FROM notifications n
        LEFT JOIN disasters d ON COALESCE(n.related_disaster_id, n.related_id) = d.disaster_id
        LEFT JOIN disaster_types dt ON d.type_id = dt.type_id
        WHERE n.user_id = ?
        AND n.is_active = TRUE
        ORDER BY n.is_read ASC, n.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get stats
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN is_read = FALSE THEN 1 END) as unread
        FROM notifications
        WHERE user_id = ? AND is_active = TRUE
    ");
    $stats_stmt->execute([$_SESSION['user_id']]);
    $stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    error_log("My notifications fetch error: " . $e->getMessage());
    $notifications = [];
    $stats = ['total' => 0, 'unread' => 0];
}

include 'includes/header.php';
?>

<div class="notifications-page-container">
    <!-- Header Section -->
    <div class="notifications-header">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="header-text">
                    <h1>Notifications</h1>
                    <p>Stay updated with important alerts and messages</p>
                </div>
            </div>
            <div class="header-right">
                <?php if ($stats['unread'] > 0): ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="mark_all_read" class="btn-mark-all">
                        <i class="fas fa-check-double"></i>
                        <span>Mark All Read</span>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="notification-stats">
        <div class="stat-box stat-total">
            <div class="stat-icon-wrapper">
                <i class="fas fa-bell"></i>
            </div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $stats['total']; ?></span>
                <span class="stat-label">Total</span>
            </div>
        </div>
        
        <div class="stat-box stat-unread">
            <div class="stat-icon-wrapper">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $stats['unread']; ?></span>
                <span class="stat-label">Unread</span>
            </div>
        </div>
        
        <div class="stat-box stat-read">
            <div class="stat-icon-wrapper">
                <i class="fas fa-envelope-open"></i>
            </div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $stats['total'] - $stats['unread']; ?></span>
                <span class="stat-label">Read</span>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="notifications-container">
        <div class="notifications-list-header">
            <h2>Recent Activity</h2>
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterNotifications('all')">All</button>
                <button class="filter-tab" onclick="filterNotifications('unread')">Unread</button>
                <button class="filter-tab" onclick="filterNotifications('read')">Read</button>
            </div>
        </div>
        
        <div class="notifications-list-content">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                <p>No notifications yet</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): 
                    $type_icons = [
                        'alert' => 'fa-exclamation-triangle',
                        'warning' => 'fa-exclamation-circle',
                        'info' => 'fa-info-circle',
                        'disaster_assigned' => 'fa-clipboard-check',
                        'status_update' => 'fa-sync-alt',
                        'system' => 'fa-cog',
                        'escalation' => 'fa-level-up-alt',
                        'deadline_warning' => 'fa-clock'
                    ];
                    
                    $type_classes = [
                        'alert' => 'notification-alert',
                        'warning' => 'notification-warning',
                        'info' => 'notification-info',
                        'system' => 'notification-default'
                    ];
                    
                    $icon = $type_icons[$notification['type']] ?? 'fa-bell';
                    $class = $type_classes[$notification['type']] ?? 'notification-default';
                    
                    $disaster_id = $notification['related_disaster_id'] ?? $notification['related_id'];
                    $link = $disaster_id ? "disaster-details.php?id={$disaster_id}" : null;
                ?>
                <div class="notif-card <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>" data-status="<?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <div class="notif-card-left">
                        <div class="notif-icon <?php echo $class; ?>">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <?php if (!$notification['is_read']): ?>
                            <div class="unread-indicator-line"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notif-card-content">
                        <div class="notif-card-header">
                            <div class="notif-title-row">
                                <h3 class="notif-title"><?php echo htmlspecialchars($notification['title']); ?></h3>
                                <?php if (!$notification['is_read']): ?>
                                    <span class="unread-dot-badge">New</span>
                                <?php endif; ?>
                            </div>
                            <span class="notif-time">
                                <i class="far fa-clock"></i>
                                <?php echo timeAgo($notification['created_at']); ?>
                            </span>
                        </div>
                        
                        <p class="notif-message"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                        
                        <?php if ($notification['disaster_name'] || $notification['tracking_id']): ?>
                            <div class="notif-tags">
                                <?php if ($notification['disaster_name']): ?>
                                    <span class="notif-tag tag-disaster">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <?php echo htmlspecialchars($notification['disaster_name']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($notification['tracking_id']): ?>
                                    <span class="notif-tag tag-tracking">
                                        <i class="fas fa-hashtag"></i>
                                        <?php echo htmlspecialchars($notification['tracking_id']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($notification['city']): ?>
                                    <span class="notif-tag tag-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($notification['city']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="notif-actions">
                            <?php if ($link): ?>
                                <a href="<?php echo $link; ?>" class="notif-btn notif-btn-primary">
                                    <i class="fas fa-external-link-alt"></i>
                                    View Details
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!$notification['is_read']): ?>
                                <a href="?mark_read=1&id=<?php echo $notification['notification_id']; ?>" class="notif-btn notif-btn-secondary">
                                    <i class="fas fa-check"></i>
                                    Mark Read
                                </a>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this notification?');">
                                <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                <button type="submit" name="delete" class="notif-btn notif-btn-ghost">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Modern Professional Notifications Page */
.notifications-page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0;
}

/* Header Section */
.notifications-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.2);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.header-icon {
    width: 64px;
    height: 64px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
}

.header-text h1 {
    margin: 0 0 8px 0;
    font-size: 32px;
    font-weight: 700;
    color: white;
}

.header-text p {
    margin: 0;
    font-size: 16px;
    color: rgba(255, 255, 255, 0.9);
}

.btn-mark-all {
    background: white;
    color: #667eea;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-mark-all:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

/* Stats Cards */
.notification-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-box {
    background: white;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transition: all 0.3s;
}

.stat-box:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-icon-wrapper {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-total .stat-icon-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-unread .stat-icon-wrapper {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.stat-read .stat-icon-wrapper {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.stat-details {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}

.stat-label {
    font-size: 14px;
    color: #64748b;
    margin-top: 4px;
}

/* Notifications Container */
.notifications-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.notifications-list-header {
    padding: 24px 32px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.notifications-list-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
}

.filter-tabs {
    display: flex;
    gap: 8px;
}

.filter-tab {
    padding: 8px 16px;
    border: none;
    background: #f1f5f9;
    color: #64748b;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-tab:hover {
    background: #e2e8f0;
}

.filter-tab.active {
    background: #667eea;
    color: white;
}

/* Notifications List */
.notifications-list-content {
    padding: 24px 32px;
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Notification Card */
.notif-card {
    display: flex;
    gap: 20px;
    padding: 24px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.3s;
}

.notif-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.notif-card.unread {
    background: linear-gradient(to right, #fffbeb 0%, #fef3c7 100%);
    border-left: 4px solid #f59e0b;
}

.notif-card-left {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.notif-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.notif-icon.notification-alert {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #dc2626;
}

.notif-icon.notification-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
}

.notif-icon.notification-info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
}

.notif-icon.notification-default {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #6b7280;
}

.unread-indicator-line {
    width: 3px;
    flex: 1;
    background: linear-gradient(to bottom, #f59e0b, transparent);
    border-radius: 2px;
}

.notif-card-content {
    flex: 1;
    min-width: 0;
}

.notif-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 16px;
}

.notif-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.notif-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

.unread-dot-badge {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.notif-time {
    font-size: 13px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.notif-message {
    margin: 0 0 16px 0;
    font-size: 15px;
    color: #475569;
    line-height: 1.7;
}

.notif-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.notif-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}

.tag-disaster {
    background: #fee2e2;
    color: #991b1b;
}

.tag-tracking {
    background: #dbeafe;
    color: #1e40af;
}

.tag-location {
    background: #d1fae5;
    color: #065f46;
}

.notif-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.notif-btn {
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    text-decoration: none;
}

.notif-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.notif-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.notif-btn-secondary {
    background: #f1f5f9;
    color: #475569;
}

.notif-btn-secondary:hover {
    background: #e2e8f0;
}

.notif-btn-ghost {
    background: transparent;
    color: #94a3b8;
    padding: 10px;
}

.notif-btn-ghost:hover {
    background: #fee2e2;
    color: #dc2626;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-state i {
    font-size: 64px;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.empty-state p {
    margin: 0;
    font-size: 18px;
    color: #94a3b8;
}

/* Responsive */
@media (max-width: 768px) {
    .notifications-header {
        padding: 24px;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .header-icon {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }
    
    .header-text h1 {
        font-size: 24px;
    }
    
    .notifications-list-header {
        padding: 20px;
    }
    
    .notifications-list-content {
        padding: 20px;
    }
    
    .notif-card {
        flex-direction: column;
        padding: 20px;
    }
    
    .notif-card-left {
        flex-direction: row;
    }
    
    .unread-indicator-line {
        width: 100%;
        height: 3px;
    }
}
</style>

<?php 
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

<script>
function filterNotifications(filter) {
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter notifications
    const cards = document.querySelectorAll('.notif-card');
    cards.forEach(card => {
        const status = card.dataset.status;
        if (filter === 'all') {
            card.style.display = 'flex';
        } else {
            card.style.display = status === filter ? 'flex' : 'none';
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>

