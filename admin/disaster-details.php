<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';


$disaster_id = intval($_GET['id'] ?? 0);
$page_title = 'Disaster Details';

if (!$disaster_id) {
    echo '<div style="color:red;font-weight:bold;padding:20px;">Error: No disaster ID provided in URL.</div>';
    exit;
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = sanitizeInput($_POST['status']);
    $comments = sanitizeInput($_POST['comments'] ?? '');
    
    try {
        $pdo->beginTransaction();
        
        // Update disaster status
        $stmt = $pdo->prepare("UPDATE disasters SET status = ?, updated_at = NOW() WHERE disaster_id = ?");
        $stmt->execute([$new_status, $disaster_id]);
        
        // Add update entry
        $update_stmt = $pdo->prepare("
            INSERT INTO disaster_updates (disaster_id, user_id, update_type, title, description)
            VALUES (?, ?, 'status_change', ?, ?)
        ");
        $update_stmt->execute([
            $disaster_id,
            $_SESSION['user_id'],
            "Status updated to " . ucfirst(str_replace('_', ' ', $new_status)),
            $comments ?: "Status changed by " . getUserName()
        ]);
        
        // Set timestamps based on status
        if ($new_status === 'acknowledged') {
            $ack_stmt = $pdo->prepare("UPDATE disasters SET acknowledged_at = NOW() WHERE disaster_id = ?");
            $ack_stmt->execute([$disaster_id]);
        } elseif ($new_status === 'resolved') {
            $resolve_stmt = $pdo->prepare("UPDATE disasters SET resolved_at = NOW() WHERE disaster_id = ?");
            $resolve_stmt->execute([$disaster_id]);
        }
        
        $pdo->commit();
        $success_message = "Disaster status updated successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Status update error: " . $e->getMessage());
        $error_message = "Error updating status. Please try again.";
    }
}

// Handle assignment updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_disaster'])) {
    $lgu_id = intval($_POST['lgu_id']);
    $user_id = intval($_POST['user_id']) ?: null;
    
    try {
        $stmt = $pdo->prepare("UPDATE disasters SET assigned_lgu_id = ?, assigned_user_id = ?, status = 'assigned', updated_at = NOW() WHERE disaster_id = ?");
        $stmt->execute([$lgu_id, $user_id, $disaster_id]);
        
        // Add update entry
        $lgu_name = '';
        if ($lgu_id) {
            $lgu_stmt = $pdo->prepare("SELECT lgu_name FROM lgus WHERE lgu_id = ?");
            $lgu_stmt->execute([$lgu_id]);
            $lgu_name = $lgu_stmt->fetchColumn();
        }
        
        $update_stmt = $pdo->prepare("
            INSERT INTO disaster_updates (disaster_id, user_id, update_type, title, description)
            VALUES (?, ?, 'assignment', ?, ?)
        ");
        $update_stmt->execute([
            $disaster_id,
            $_SESSION['user_id'],
            "Assigned to LGU",
            "Disaster assigned to " . $lgu_name . " by " . getUserName()
        ]);
        
        $success_message = "Disaster assigned successfully.";
    } catch (Exception $e) {
        error_log("Assignment error: " . $e->getMessage());
        $error_message = "Error assigning disaster. Please try again.";
    }
}

// Fetch disaster details
try {
    $stmt = $pdo->prepare("
        SELECT d.*, dt.type_name, dt.description as type_description,
               lgu.lgu_name, lgu.contact_person, lgu.phone as lgu_phone,
               CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
               u.phone as user_phone, u.email as user_email,
               TIMESTAMPDIFF(HOUR, d.reported_at, NOW()) as hours_since_report,
               TIMESTAMPDIFF(HOUR, d.reported_at, COALESCE(d.acknowledged_at, NOW())) as response_time_hours
        FROM disasters d
        JOIN disaster_types dt ON d.type_id = dt.type_id
        LEFT JOIN lgus lgu ON d.assigned_lgu_id = lgu.lgu_id
        LEFT JOIN users u ON d.assigned_user_id = u.user_id
        WHERE d.disaster_id = ?
    ");
    $stmt->execute([$disaster_id]);
    $disaster = $stmt->fetch();
    
    if (!$disaster) {
        echo '<div style="color:red;font-weight:bold;padding:20px;">Error: No disaster found in database for ID: ' . htmlspecialchars($disaster_id) . '</div>';
        exit;
    }
    
    // Fetch disaster updates
    $updates_stmt = $pdo->prepare("
        SELECT du.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.role
        FROM disaster_updates du
        LEFT JOIN users u ON du.user_id = u.user_id
        WHERE du.disaster_id = ?
        ORDER BY du.created_at DESC
    ");
    $updates_stmt->execute([$disaster_id]);
    $updates = $updates_stmt->fetchAll();
    
    // Fetch available LGUs for assignment
    $lgus_stmt = $pdo->query("SELECT lgu_id, lgu_name FROM lgus WHERE is_active = TRUE ORDER BY lgu_name");
    $lgus = $lgus_stmt->fetchAll();
    
    // Fetch users for selected LGU
    if ($disaster['assigned_lgu_id']) {
        $users_stmt = $pdo->prepare("
            SELECT user_id, CONCAT(first_name, ' ', last_name) as full_name 
            FROM users 
            WHERE lgu_id = ? AND role IN ('lgu_admin', 'lgu_staff') AND is_active = TRUE 
            ORDER BY full_name
        ");
        $users_stmt->execute([$disaster['assigned_lgu_id']]);
        $lgu_users = $users_stmt->fetchAll();
    } else {
        $lgu_users = [];
    }
    
} catch (Exception $e) {
    error_log("Disaster details error: " . $e->getMessage());
    header('Location: disasters.php');
    exit;
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-file-alt"></i> Disaster Details</h2>
        <p>Tracking ID: <?php echo htmlspecialchars($disaster['tracking_id']); ?></p>
    </div>
    <div class="page-actions">
        <a href="disasters.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
        <button onclick="printPage()" class="btn btn-secondary">
            <i class="fas fa-print"></i> Print
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

<div class="disaster-details-grid">
    <!-- Main Details Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> Disaster Information</h3>
            <div class="status-badges">
                <span class="priority-badge priority-<?php echo $disaster['priority']; ?>">
                    <?php echo ucfirst($disaster['priority']); ?> Priority
                </span>
                <span class="status-badge status-<?php echo $disaster['status']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $disaster['status'])); ?>
                </span>
            </div>
        </div>
        <div class="card-content">
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Disaster Type</label>
                    <div class="value"><?php echo htmlspecialchars($disaster['type_name']); ?></div>
                </div>
                <div class="detail-item">
                    <label>Severity Level</label>
                    <div class="value">
                        <span class="severity-badge severity-<?php echo substr($disaster['severity_level'], 0, strpos($disaster['severity_level'], '-')); ?>">
                            <?php echo htmlspecialchars($disaster['severity_display']); ?>
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <label>Reported At</label>
                    <div class="value"><?php echo date('M j, Y g:i A', strtotime($disaster['reported_at'])); ?></div>
                </div>
                <div class="detail-item">
                    <label>Response Time</label>
                    <div class="value">
                        <?php if ($disaster['acknowledged_at']): ?>
                            <?php echo $disaster['response_time_hours']; ?> hours
                        <?php else: ?>
                            <span class="text-muted">Pending acknowledgment</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <label>Description</label>
                <div class="value"><?php echo nl2br(htmlspecialchars($disaster['description'])); ?></div>
            </div>
            
            <?php if ($disaster['current_situation']): ?>
            <div class="detail-section">
                <label>Current Situation & Hazards</label>
                <div class="value"><?php echo nl2br(htmlspecialchars($disaster['current_situation'])); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($disaster['immediate_needs']): ?>
            <div class="detail-section">
                <label>Immediate Needs</label>
                <div class="value">
                    <?php 
                    $needs = json_decode($disaster['immediate_needs'], true);
                    if ($needs) {
                        echo '<div class="needs-list">';
                        foreach ($needs as $need) {
                            echo '<span class="need-badge">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $need))) . '</span>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Location & Contact Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-map-marker-alt"></i> Location & Contact</h3>
        </div>
        <div class="card-content">
            <div class="detail-section">
                <label>Address</label>
                <div class="value"><?php echo htmlspecialchars($disaster['address']); ?></div>
            </div>
            
            <?php if ($disaster['landmark']): ?>
            <div class="detail-section">
                <label>Nearby Landmark</label>
                <div class="value"><?php echo htmlspecialchars($disaster['landmark']); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="detail-section">
                <label>Reporter</label>
                <div class="value">
                    <?php if ($disaster['reporter_name']): ?>
                        <?php echo htmlspecialchars($disaster['reporter_name']); ?>
                    <?php else: ?>
                        <em>Anonymous</em>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="detail-section">
                <label>Contact Number</label>
                <div class="value">
                    <a href="tel:<?php echo $disaster['reporter_phone']; ?>" class="phone-link">
                        <i class="fas fa-phone"></i>
                        <?php echo htmlspecialchars($disaster['reporter_phone']); ?>
                    </a>
                </div>
            </div>
            
            <?php if ($disaster['alternate_contact']): ?>
            <div class="detail-section">
                <label>Alternate Contact</label>
                <div class="value">
                    <a href="tel:<?php echo $disaster['alternate_contact']; ?>" class="phone-link">
                        <i class="fas fa-phone"></i>
                        <?php echo htmlspecialchars($disaster['alternate_contact']); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($disaster['people_affected']): ?>
            <div class="detail-section">
                <label>People Affected</label>
                <div class="value"><?php echo htmlspecialchars($disaster['people_affected']); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Assignment Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-building"></i> Assignment</h3>
        </div>
        <div class="card-content">
            <?php if ($disaster['lgu_name']): ?>
                <div class="assignment-info">
                    <div class="detail-section">
                        <label>Assigned LGU</label>
                        <div class="value"><?php echo htmlspecialchars($disaster['lgu_name']); ?></div>
                    </div>
                    
                    <?php if ($disaster['assigned_user_name']): ?>
                    <div class="detail-section">
                        <label>Assigned Staff</label>
                        <div class="value">
                            <?php echo htmlspecialchars($disaster['assigned_user_name']); ?>
                            <?php if ($disaster['user_phone']): ?>
                                <br><small><a href="tel:<?php echo $disaster['user_phone']; ?>"><?php echo htmlspecialchars($disaster['user_phone']); ?></a></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="unassigned-notice">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>This disaster has not been assigned to any LGU yet.</p>
                </div>
            <?php endif; ?>
            
            <?php if (hasRole(['admin', 'lgu_admin'])): ?>
            <form method="POST" class="assignment-form">
                <div class="form-group">
                    <label for="lgu_id">Assign to LGU</label>
                    <select name="lgu_id" id="lgu_id" required>
                        <option value="">Select LGU...</option>
                        <?php foreach ($lgus as $lgu): ?>
                            <option value="<?php echo $lgu['lgu_id']; ?>" 
                                    <?php echo $disaster['assigned_lgu_id'] == $lgu['lgu_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lgu['lgu_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="user_id">Assign to Staff (Optional)</label>
                    <select name="user_id" id="user_id">
                        <option value="">Select staff member...</option>
                        <?php foreach ($lgu_users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>" 
                                    <?php echo $disaster['assigned_user_id'] == $user['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" name="assign_disaster" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Assignment
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Status Update Card -->
    <?php if (hasRole(['admin', 'lgu_admin', 'lgu_staff'])): ?>
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-edit"></i> Update Status</h3>
        </div>
        <div class="card-content">
            <form method="POST" class="status-form">
                <div class="form-group">
                    <label for="status">New Status</label>
                    <select name="status" id="status" required>
                        <option value="pending" <?php echo $disaster['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="assigned" <?php echo $disaster['status'] === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="acknowledged" <?php echo $disaster['status'] === 'acknowledged' ? 'selected' : ''; ?>>Acknowledged</option>
                        <option value="in_progress" <?php echo $disaster['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $disaster['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $disaster['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        <option value="escalated" <?php echo $disaster['status'] === 'escalated' ? 'selected' : ''; ?>>Escalated</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="comments">Comments (Optional)</label>
                    <textarea name="comments" id="comments" rows="3" 
                              placeholder="Add notes about this status change..."></textarea>
                </div>
                
                <button type="submit" name="update_status" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Updates Timeline -->
    <div class="dashboard-card timeline-card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Update Timeline</h3>
        </div>
        <div class="card-content">
            <?php if (empty($updates)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>No updates recorded</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($updates as $update): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h4><?php echo htmlspecialchars($update['title']); ?></h4>
                                    <span class="timeline-date"><?php echo date('M j, Y g:i A', strtotime($update['created_at'])); ?></span>
                                </div>
                                <div class="timeline-body">
                                    <p><?php echo nl2br(htmlspecialchars($update['description'])); ?></p>
                                    <?php if ($update['user_name']): ?>
                                        <small class="timeline-user">
                                            By <?php echo htmlspecialchars($update['user_name']); ?>
                                            (<?php echo ucfirst($update['role']); ?>)
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.disaster-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.timeline-card {
    grid-column: 1 / -1;
}

.status-badges {
    display: flex;
    gap: 10px;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-section {
    margin-bottom: 15px;
}

.detail-item label,
.detail-section label {
    font-weight: 600;
    color: var(--text-muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}

.detail-item .value,
.detail-section .value {
    color: var(--text-color);
    font-weight: 500;
}

.needs-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.need-badge {
    background-color: var(--info-color);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.phone-link {
    color: var(--primary-color);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.phone-link:hover {
    text-decoration: underline;
}

.assignment-info {
    margin-bottom: 20px;
}

.unassigned-notice {
    background-color: #fff3cd;
    color: #856404;
    padding: 15px;
    border-radius: var(--border-radius);
    text-align: center;
    margin-bottom: 20px;
}

.assignment-form,
.status-form {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    height: 100%;
    width: 2px;
    background-color: var(--border-color);
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
}

.timeline-marker {
    position: absolute;
    left: -24px;
    top: 5px;
    width: 8px;
    height: 8px;
    background-color: var(--primary-color);
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 2px var(--primary-color);
}

.timeline-content {
    background-color: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 15px;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.timeline-header h4 {
    margin: 0;
    color: var(--text-color);
    font-size: 14px;
}

.timeline-date {
    color: var(--text-muted);
    font-size: 12px;
}

.timeline-body p {
    margin: 0 0 10px 0;
    color: var(--text-color);
}

.timeline-user {
    color: var(--text-muted);
    font-style: italic;
}

@media (max-width: 768px) {
    .disaster-details-grid {
        grid-template-columns: 1fr;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function printPage() {
    window.print();
}

// LGU selection change handler
document.getElementById('lgu_id').addEventListener('change', function() {
    const lguId = this.value;
    const userSelect = document.getElementById('user_id');
    
    // Clear user dropdown
    userSelect.innerHTML = '<option value="">Loading...</option>';
    
    if (lguId) {
        // Fetch users for selected LGU
        fetch(`ajax/get-lgu-users.php?lgu_id=${lguId}`)
            .then(response => response.json())
            .then(data => {
                userSelect.innerHTML = '<option value="">Select staff member...</option>';
                if (data.success && data.users) {
                    data.users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.user_id;
                        option.textContent = user.full_name;
                        userSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching users:', error);
                userSelect.innerHTML = '<option value="">Error loading users</option>';
            });
    } else {
        userSelect.innerHTML = '<option value="">Select staff member...</option>';
    }
});
</script>

<?php include 'includes/footer.php'; ?>