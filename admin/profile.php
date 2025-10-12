<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

$page_title = 'User Profile';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $phone = sanitizeInput($_POST['phone']);
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?");
            $stmt->execute([$first_name, $last_name, $phone, $_SESSION['user_id']]);
            
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            
            $success_message = "Profile updated successfully.";
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error_message = "Error updating profile. Please try again.";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $stored_password = $stmt->fetchColumn();
                
                if (password_verify($current_password, $stored_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $update_stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    
                    $success_message = "Password changed successfully.";
                } else {
                    $error_message = "Current password is incorrect.";
                }
            } catch (Exception $e) {
                error_log("Password change error: " . $e->getMessage());
                $error_message = "Error changing password. Please try again.";
            }
        }
    }
}

// Fetch user data
try {
    $stmt = $pdo->prepare("
        SELECT u.*, l.lgu_name,
               COUNT(d.disaster_id) as assigned_disasters
        FROM users u
        LEFT JOIN lgus l ON u.lgu_id = l.lgu_id
        LEFT JOIN disasters d ON u.user_id = d.assigned_user_id AND d.status NOT IN ('resolved', 'closed')
        WHERE u.user_id = ?
        GROUP BY u.user_id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Fetch recent activity
    $activity_stmt = $pdo->prepare("
        SELECT du.*, d.tracking_id, dt.type_name
        FROM disaster_updates du
        JOIN disasters d ON du.disaster_id = d.disaster_id
        JOIN disaster_types dt ON d.type_id = dt.type_id
        WHERE du.user_id = ?
        ORDER BY du.created_at DESC
        LIMIT 10
    ");
    $activity_stmt->execute([$_SESSION['user_id']]);
    $recent_activity = $activity_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-user"></i> User Profile</h2>
        <p>Manage your account settings and view activity</p>
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

<div class="profile-grid">
    <!-- User Info Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-user-circle"></i> Personal Information</h3>
        </div>
        <div class="card-content">
            <div class="user-avatar-large">
                <?php echo getUserInitials(); ?>
            </div>
            
            <form method="POST" class="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" name="first_name" id="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" name="last_name" id="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    <small class="form-help">Email cannot be changed. Contact administrator if needed.</small>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" name="phone" id="phone" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                           placeholder="+63 9XX XXX XXXX">
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>
    
    <!-- Account Details -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-id-badge"></i> Account Details</h3>
        </div>
        <div class="card-content">
            <div class="detail-list">
                <div class="detail-item">
                    <label>User ID</label>
                    <span><?php echo $user['user_id']; ?></span>
                </div>
                
                <div class="detail-item">
                    <label>Role</label>
                    <span class="role-badge role-<?php echo $user['role']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <label>Assigned LGU</label>
                    <span><?php echo $user['lgu_name'] ?: 'Not assigned'; ?></span>
                </div>
                
                <div class="detail-item">
                    <label>Account Status</label>
                    <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <label>Member Since</label>
                    <span><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                </div>
                
                <div class="detail-item">
                    <label>Last Login</label>
                    <span>
                        <?php if ($user['last_login']): ?>
                            <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                        <?php else: ?>
                            Never
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <label>Active Assignments</label>
                    <span class="assignment-count"><?php echo $user['assigned_disasters']; ?> disasters</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-lock"></i> Change Password</h3>
        </div>
        <div class="card-content">
            <form method="POST" class="password-form">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" required minlength="8">
                    <small class="form-help">Minimum 8 characters required</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="8">
                </div>
                
                <button type="submit" name="change_password" class="btn btn-warning">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="dashboard-card activity-card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Activity</h3>
        </div>
        <div class="card-content">
            <?php if (empty($recent_activity)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>No recent activity</p>
                </div>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo getActivityIcon($activity['update_type']); ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                <div class="activity-details">
                                    <span class="tracking-id"><?php echo htmlspecialchars($activity['tracking_id']); ?></span>
                                    <span class="disaster-type"><?php echo htmlspecialchars($activity['type_name']); ?></span>
                                </div>
                                <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
function getActivityIcon($type) {
    switch ($type) {
        case 'status_change': return 'edit';
        case 'assignment': return 'user-plus';
        case 'resolution': return 'check-circle';
        case 'escalation': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}
?>

<style>
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.activity-card {
    grid-column: 1 / -1;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    margin: 0 auto 20px;
}

.profile-form,
.password-form {
    max-width: 400px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-help {
    color: var(--text-muted);
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

.detail-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item label {
    font-weight: 500;
    color: var(--text-muted);
}

.detail-item span {
    color: var(--text-color);
}

.role-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.role-admin {
    background-color: #fff3e0;
    color: #f57c00;
}

.role-reporter {
    background-color: #e8f5e8;
    color: #388e3c;
}

.role-lgu_staff {
    background-color: #e3f2fd;
    color: #1976d2;
}

.role-responder {
    background-color: #fce4ec;
    color: #c2185b;
}

.assignment-count {
    background-color: var(--info-color);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: var(--border-radius);
}

.activity-icon {
    width: 36px;
    height: 36px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 500;
    color: var(--text-color);
    margin-bottom: 5px;
}

.activity-details {
    display: flex;
    gap: 15px;
    margin-bottom: 5px;
}

.tracking-id {
    font-family: monospace;
    background-color: #e9ecef;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
}

.disaster-type {
    color: var(--text-muted);
    font-size: 12px;
}

.activity-time {
    color: var(--text-muted);
    font-size: 12px;
}

@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .activity-item {
        flex-direction: column;
        text-align: center;
    }
    
    .activity-details {
        justify-content: center;
        flex-wrap: wrap;
    }
}
</style>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Form validation
document.querySelector('.password-form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New passwords do not match');
    }
});

// ====================================
// REAL-TIME INTEGRATION (Minimal - Low Priority)
// ====================================
if (window.realtimeSystem) {
    console.log('✅ Real-time system available for profile page');
    // Profile page has minimal need for real-time updates
}
</script>

<?php include 'includes/footer.php'; ?>