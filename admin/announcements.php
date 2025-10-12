<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

$page_title = 'Announcements Management';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_announcement'])) {
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']);
        $type = sanitizeInput($_POST['type']);
        $priority = sanitizeInput($_POST['priority']);
        $target_audience = sanitizeInput($_POST['target_audience']);
        $expires_at = $_POST['expires_at'] ? $_POST['expires_at'] : null;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO announcements (title, content, type, priority, target_audience, expires_at, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $content, $type, $priority, $target_audience, $expires_at, $_SESSION['user_id']]);
            
            $success_message = "Announcement created successfully.";
        } catch (Exception $e) {
            error_log("Announcement creation error: " . $e->getMessage());
            $error_message = "Error creating announcement. Please try again.";
        }
    }
    
    if (isset($_POST['update_status'])) {
        $announcement_id = intval($_POST['announcement_id']);
        $status = sanitizeInput($_POST['status']);
        
        try {
            $stmt = $pdo->prepare("UPDATE announcements SET status = ?, updated_at = NOW() WHERE announcement_id = ?");
            $stmt->execute([$status, $announcement_id]);
            
            $success_message = "Announcement status updated successfully.";
        } catch (Exception $e) {
            error_log("Announcement update error: " . $e->getMessage());
            $error_message = "Error updating announcement status.";
        }
    }
}

// Fetch announcements
try {
    $stmt = $pdo->query("
        SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as created_by_name
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        ORDER BY a.created_at DESC
    ");
    $announcements = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Announcements fetch error: " . $e->getMessage());
    $announcements = [];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-bullhorn"></i> Announcements</h2>
        <p>Manage public announcements and emergency notifications</p>
    </div>
    <div class="page-actions">
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Announcement
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

<!-- Announcements List -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>All Announcements</h3>
    </div>
    <div class="card-content">
        <?php if (empty($announcements)): ?>
            <div class="empty-state">
                <i class="fas fa-bullhorn"></i>
                <h3>No announcements yet</h3>
                <p>Create your first announcement to notify the public</p>
            </div>
        <?php else: ?>
            <div class="announcements-grid">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <div class="announcement-meta">
                                <span class="announcement-type type-<?php echo $announcement['type']; ?>">
                                    <?php echo ucfirst($announcement['type']); ?>
                                </span>
                                <span class="announcement-priority priority-<?php echo $announcement['priority']; ?>">
                                    <?php echo ucfirst($announcement['priority']); ?>
                                </span>
                                <span class="announcement-status status-<?php echo $announcement['status']; ?>">
                                    <?php echo ucfirst($announcement['status']); ?>
                                </span>
                            </div>
                            <div class="announcement-actions">
                                <button onclick="editAnnouncement(<?php echo $announcement['announcement_id']; ?>)" 
                                        class="btn btn-xs btn-secondary">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="toggleStatus(<?php echo $announcement['announcement_id']; ?>, '<?php echo $announcement['status']; ?>')" 
                                        class="btn btn-xs btn-warning">
                                    <i class="fas fa-toggle-<?php echo $announcement['status'] === 'active' ? 'off' : 'on'; ?>"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="announcement-content">
                            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                            <p><?php echo nl2br(htmlspecialchars(substr($announcement['content'], 0, 200))); ?>
                               <?php if (strlen($announcement['content']) > 200): ?>...<?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="announcement-footer">
                            <div class="announcement-info">
                                <small><i class="fas fa-user"></i> <?php echo htmlspecialchars($announcement['created_by_name']); ?></small>
                                <small><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?></small>
                                <?php if ($announcement['expires_at']): ?>
                                    <small><i class="fas fa-clock"></i> Expires: <?php echo date('M j, Y', strtotime($announcement['expires_at'])); ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="target-audience">
                                <small><i class="fas fa-users"></i> <?php echo ucfirst(str_replace('_', ' ', $announcement['target_audience'])); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Announcement Modal -->
<div id="createAnnouncementModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Create New Announcement</h3>
            <button class="modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" name="title" id="title" required 
                           placeholder="Enter announcement title">
                </div>
                <div class="form-group">
                    <label for="type">Type *</label>
                    <select name="type" id="type" required>
                        <option value="">Select type...</option>
                        <option value="general">General Information</option>
                        <option value="emergency">Emergency Alert</option>
                        <option value="weather">Weather Update</option>
                        <option value="safety">Safety Advisory</option>
                        <option value="service">Service Update</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="priority">Priority *</label>
                    <select name="priority" id="priority" required>
                        <option value="">Select priority...</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="target_audience">Target Audience *</label>
                    <select name="target_audience" id="target_audience" required>
                        <option value="">Select audience...</option>
                        <option value="public">General Public</option>
                        <option value="lgus">LGUs</option>
                        <option value="responders">Emergency Responders</option>
                        <option value="specific_area">Specific Area</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="content">Content *</label>
                <textarea name="content" id="content" rows="6" required 
                          placeholder="Enter the announcement content..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="expires_at">Expiration Date (Optional)</label>
                <input type="datetime-local" name="expires_at" id="expires_at">
                <small class="form-help">Leave empty for no expiration</small>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeCreateModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="create_announcement" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Announcement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Announcement Status</h3>
            <button class="modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="announcement_id" id="status-announcement-id">
            
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<style>
.announcements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.announcement-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.announcement-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.announcement-header {
    padding: 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.announcement-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.announcement-type,
.announcement-priority,
.announcement-status {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.type-general { background-color: #e3f2fd; color: #1976d2; }
.type-emergency { background-color: #ffebee; color: #d32f2f; }
.type-weather { background-color: #f3e5f5; color: #7b1fa2; }
.type-safety { background-color: #fff3e0; color: #f57c00; }
.type-service { background-color: #e8f5e8; color: #388e3c; }

.priority-low { background-color: #e8f5e8; color: #388e3c; }
.priority-medium { background-color: #fff3e0; color: #f57c00; }
.priority-high { background-color: #fff8e1; color: #f9a825; }
.priority-critical { background-color: #ffebee; color: #d32f2f; }

.status-active { background-color: #e8f5e8; color: #388e3c; }
.status-inactive { background-color: #f5f5f5; color: #757575; }
.status-archived { background-color: #e0e0e0; color: #424242; }

.announcement-actions {
    display: flex;
    gap: 5px;
}

.announcement-content {
    padding: 15px;
}

.announcement-content h4 {
    margin: 0 0 10px 0;
    color: var(--text-color);
    font-size: 16px;
    line-height: 1.4;
}

.announcement-content p {
    margin: 0;
    color: var(--text-muted);
    line-height: 1.5;
}

.announcement-footer {
    padding: 15px;
    background-color: #f8f9fa;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.announcement-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.announcement-info small {
    color: var(--text-muted);
    font-size: 11px;
}

.announcement-info i {
    margin-right: 4px;
    width: 12px;
}

.target-audience small {
    color: var(--text-muted);
    font-size: 11px;
}

.modal-lg {
    max-width: 700px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-help {
    color: var(--text-muted);
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

@media (max-width: 768px) {
    .announcements-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .announcement-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<script>
function showCreateModal() {
    document.getElementById('createAnnouncementModal').style.display = 'block';
}

function closeCreateModal() {
    document.getElementById('createAnnouncementModal').style.display = 'none';
}

function toggleStatus(announcementId, currentStatus) {
    document.getElementById('status-announcement-id').value = announcementId;
    document.getElementById('status').value = currentStatus;
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

function editAnnouncement(id) {
    // This would open an edit modal - for now, just show message
    alert('Edit functionality would be implemented here');
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// Set minimum datetime for expiration
document.getElementById('expires_at').min = new Date().toISOString().slice(0, 16);

// ====================================
// REAL-TIME INTEGRATION
// ====================================
if (window.realtimeSystem) {
    // Listen for new announcements or updates
    window.realtimeSystem.registerCallback('onUpdate', (data) => {
        if (data.announcement_update) {
            showAnnouncementUpdate();
        }
    });
    
    console.log('✅ Real-time updates enabled for announcements page');
}

function showAnnouncementUpdate() {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: #6366f1;
        color: white;
        padding: 12px 18px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    
    notification.innerHTML = `
        <i class="fas fa-bullhorn"></i>
        <span style="font-size: 14px;">Announcements updated</span>
        <button onclick="location.reload()" style="
            background: white;
            color: #6366f1;
            border: none;
            padding: 4px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        ">Refresh</button>
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 6000);
}
</script>

<?php include 'includes/footer.php'; ?>