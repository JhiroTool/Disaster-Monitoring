<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

// Only admins can access system settings
if (!hasRole(['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'System Settings';

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $settings = [
            'system_name' => sanitizeInput($_POST['system_name']),
            'admin_email' => sanitizeInput($_POST['admin_email']),
            'emergency_hotline' => sanitizeInput($_POST['emergency_hotline']),
            'response_time_target' => intval($_POST['response_time_target']),
            'escalation_hours' => intval($_POST['escalation_hours']),
            'auto_assignment' => isset($_POST['auto_assignment']) ? 1 : 0,
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'sms_notifications' => isset($_POST['sms_notifications']) ? 1 : 0
        ];
        
        try {
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, updated_by) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value), 
                    updated_by = VALUES(updated_by), 
                    updated_at = NOW()
                ");
                $stmt->execute([$key, $value, $_SESSION['user_id']]);
            }
            
            $success_message = "Settings updated successfully.";
        } catch (Exception $e) {
            error_log("Settings update error: " . $e->getMessage());
            $error_message = "Error updating settings. Please try again.";
        }
    }
    
    if (isset($_POST['manage_disaster_types'])) {
        if (isset($_POST['add_type'])) {
            $type_name = sanitizeInput($_POST['new_type_name']);
            $description = sanitizeInput($_POST['new_type_description']);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO disaster_types (type_name, description) VALUES (?, ?)");
                $stmt->execute([$type_name, $description]);
                $success_message = "Disaster type added successfully.";
            } catch (Exception $e) {
                error_log("Add disaster type error: " . $e->getMessage());
                $error_message = "Error adding disaster type.";
            }
        }
        
        if (isset($_POST['toggle_type'])) {
            $type_id = intval($_POST['type_id']);
            $is_active = intval($_POST['is_active']);
            
            try {
                $stmt = $pdo->prepare("UPDATE disaster_types SET is_active = ? WHERE type_id = ?");
                $stmt->execute([$is_active, $type_id]);
                $success_message = "Disaster type status updated.";
            } catch (Exception $e) {
                error_log("Toggle disaster type error: " . $e->getMessage());
                $error_message = "Error updating disaster type status.";
            }
        }
    }
    
    if (isset($_POST['backup_database'])) {
        try {
            $backup_file = 'backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Create backups directory if it doesn't exist
            if (!file_exists('backups')) {
                mkdir('backups', 0755, true);
            }
            
            // Generate backup command (simplified version)
            $command = "/opt/lampp/bin/mysqldump -u root disaster_monitoring > $backup_file";
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                $success_message = "Database backup created successfully: " . $backup_file;
            } else {
                $error_message = "Error creating database backup.";
            }
        } catch (Exception $e) {
            error_log("Database backup error: " . $e->getMessage());
            $error_message = "Error creating database backup.";
        }
    }
}

// Fetch current settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settings_rows = $stmt->fetchAll();
    
    $current_settings = [];
    foreach ($settings_rows as $row) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Set default values if not exists
    $default_settings = [
        'system_name' => 'iMSafe Disaster Monitoring System',
        'admin_email' => 'admin@imsafe.local',
        'emergency_hotline' => '911',
        'response_time_target' => 6,
        'escalation_hours' => 24,
        'auto_assignment' => 0,
        'email_notifications' => 1,
        'sms_notifications' => 0
    ];
    
    $current_settings = array_merge($default_settings, $current_settings);
    
    // Fetch disaster types
    $types_stmt = $pdo->query("SELECT * FROM disaster_types ORDER BY type_name");
    $disaster_types = $types_stmt->fetchAll();
    
    // Fetch system statistics
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT d.disaster_id) as total_disasters,
            COUNT(DISTINCT u.user_id) as total_users,
            COUNT(DISTINCT l.lgu_id) as total_lgus,
            AVG(CASE 
                WHEN d.acknowledged_at IS NOT NULL 
                THEN TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at) 
            END) as avg_response_time
        FROM disasters d
        CROSS JOIN users u
        CROSS JOIN lgus l
        WHERE d.reported_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $system_stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    error_log("Settings fetch error: " . $e->getMessage());
    $current_settings = [];
    $disaster_types = [];
    $system_stats = [];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-cog"></i> System Settings</h2>
        <p>Configure system parameters and manage disaster types</p>
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

<div class="settings-grid">
    <!-- General Settings -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-sliders-h"></i> General Settings</h3>
        </div>
        <div class="card-content">
            <form method="POST" class="settings-form">
                <div class="form-group">
                    <label for="system_name">System Name</label>
                    <input type="text" name="system_name" id="system_name" 
                           value="<?php echo htmlspecialchars($current_settings['system_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Administrator Email</label>
                    <input type="email" name="admin_email" id="admin_email" 
                           value="<?php echo htmlspecialchars($current_settings['admin_email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="emergency_hotline">Emergency Hotline</label>
                    <input type="text" name="emergency_hotline" id="emergency_hotline" 
                           value="<?php echo htmlspecialchars($current_settings['emergency_hotline']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="response_time_target">Response Time Target (Hours)</label>
                        <input type="number" name="response_time_target" id="response_time_target" 
                               value="<?php echo $current_settings['response_time_target']; ?>" 
                               min="1" max="72" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="escalation_hours">Auto-Escalation After (Hours)</label>
                        <input type="number" name="escalation_hours" id="escalation_hours" 
                               value="<?php echo $current_settings['escalation_hours']; ?>" 
                               min="1" max="168" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>System Features</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="auto_assignment" 
                                   <?php echo $current_settings['auto_assignment'] ? 'checked' : ''; ?>>
                            <span>Automatic LGU Assignment</span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="email_notifications" 
                                   <?php echo $current_settings['email_notifications'] ? 'checked' : ''; ?>>
                            <span>Email Notifications</span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="sms_notifications" 
                                   <?php echo $current_settings['sms_notifications'] ? 'checked' : ''; ?>>
                            <span>SMS Notifications</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" name="update_settings" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </form>
        </div>
    </div>
    
    <!-- System Statistics -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> System Statistics</h3>
        </div>
        <div class="card-content">
            <div class="stats-list">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($system_stats['total_disasters'] ?? 0); ?></div>
                        <div class="stat-label">Total Disasters (30 days)</div>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($system_stats['total_users'] ?? 0); ?></div>
                        <div class="stat-label">Registered Users</div>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($system_stats['total_lgus'] ?? 0); ?></div>
                        <div class="stat-label">Active LGUs</div>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">
                            <?php echo $system_stats['avg_response_time'] ? number_format($system_stats['avg_response_time'], 1) . 'h' : 'N/A'; ?>
                        </div>
                        <div class="stat-label">Avg Response Time</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Disaster Types Management -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Disaster Types</h3>
            <button onclick="showAddTypeModal()" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Add Type
            </button>
        </div>
        <div class="card-content">
            <div class="disaster-types-list">
                <?php foreach ($disaster_types as $type): ?>
                    <div class="disaster-type-item">
                        <div class="type-info">
                            <div class="type-name"><?php echo htmlspecialchars($type['type_name']); ?></div>
                            <div class="type-description"><?php echo htmlspecialchars($type['description'] ?? ''); ?></div>
                        </div>
                        <div class="type-actions">
                            <span class="status-badge <?php echo $type['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $type['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="type_id" value="<?php echo $type['type_id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $type['is_active'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_type" class="btn btn-xs btn-secondary">
                                    <?php echo $type['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- System Maintenance -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-tools"></i> System Maintenance</h3>
        </div>
        <div class="card-content">
            <div class="maintenance-actions">
                <div class="maintenance-item">
                    <div class="maintenance-info">
                        <h4>Database Backup</h4>
                        <p>Create a backup of the entire database for safety</p>
                    </div>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="backup_database" class="btn btn-warning" 
                                onclick="return confirm('Create database backup? This may take a few moments.')">
                            <i class="fas fa-database"></i> Create Backup
                        </button>
                    </form>
                </div>
                
                <div class="maintenance-item">
                    <div class="maintenance-info">
                        <h4>Clear Cache</h4>
                        <p>Clear system cache and temporary files</p>
                    </div>
                    <button onclick="clearCache()" class="btn btn-secondary">
                        <i class="fas fa-broom"></i> Clear Cache
                    </button>
                </div>
                
                <div class="maintenance-item">
                    <div class="maintenance-info">
                        <h4>System Logs</h4>
                        <p>View system error and activity logs</p>
                    </div>
                    <button onclick="viewLogs()" class="btn btn-info">
                        <i class="fas fa-file-alt"></i> View Logs
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Disaster Type Modal -->
<div id="addTypeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Disaster Type</h3>
            <button class="modal-close" onclick="closeAddTypeModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <div class="form-group">
                <label for="new_type_name">Type Name *</label>
                <input type="text" name="new_type_name" id="new_type_name" required>
            </div>
            
            <div class="form-group">
                <label for="new_type_description">Description</label>
                <textarea name="new_type_description" id="new_type_description" rows="3" 
                          placeholder="Optional description of this disaster type"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeAddTypeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="add_type" class="btn btn-primary">Add Type</button>
            </div>
        </form>
    </div>
</div>

<style>
.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.settings-form {
    max-width: 500px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 10px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin: 0;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: var(--border-radius);
}

.stat-icon {
    width: 48px;
    height: 48px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-color);
}

.stat-label {
    color: var(--text-muted);
    font-size: 14px;
}

.disaster-types-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.disaster-type-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
}

.type-info {
    flex: 1;
}

.type-name {
    font-weight: 600;
    color: var(--text-color);
}

.type-description {
    color: var(--text-muted);
    font-size: 14px;
}

.type-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.maintenance-actions {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.maintenance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
}

.maintenance-info h4 {
    margin: 0 0 5px 0;
    color: var(--text-color);
}

.maintenance-info p {
    margin: 0;
    color: var(--text-muted);
    font-size: 14px;
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .maintenance-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
</style>

<script>
function showAddTypeModal() {
    document.getElementById('addTypeModal').style.display = 'block';
}

function closeAddTypeModal() {
    document.getElementById('addTypeModal').style.display = 'none';
}

function clearCache() {
    if (confirm('Clear system cache? This will remove temporary files and may affect performance temporarily.')) {
        // Implementation would be here
        alert('Cache clearing functionality would be implemented here');
    }
}

function viewLogs() {
    // Implementation would be here
    alert('Log viewing functionality would be implemented here');
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// ====================================
// REAL-TIME INTEGRATION (Minimal - Low Priority)
// ====================================
if (window.realtimeSystem) {
    console.log('✅ Real-time system available for settings page');
    // Settings page has minimal need for real-time updates
}
</script>

<?php include 'includes/footer.php'; ?>