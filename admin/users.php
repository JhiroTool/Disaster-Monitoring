<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

// Only admins can access user management
if (!hasRole(['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'User Management';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $role = sanitizeInput($_POST['role']);
        $lgu_id = intval($_POST['lgu_id']) ?: null;
        $password = $_POST['password'];
        
        // Validate email uniqueness
        $email_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $email_check->execute([$email]);
        
        if ($email_check->fetchColumn() > 0) {
            $error_message = "Email address already exists.";
        } elseif (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (first_name, last_name, email, phone, password, role, lgu_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password, $role, $lgu_id]);
                
                $success_message = "User created successfully.";
            } catch (Exception $e) {
                error_log("User creation error: " . $e->getMessage());
                $error_message = "Error creating user. Please try again.";
            }
        }
    }
    
    if (isset($_POST['update_status'])) {
        $user_id = intval($_POST['user_id']);
        $is_active = $_POST['is_active'] === '1' ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->execute([$is_active, $user_id]);
            
            $success_message = "User status updated successfully.";
        } catch (Exception $e) {
            error_log("User status update error: " . $e->getMessage());
            $error_message = "Error updating user status.";
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $user_id = intval($_POST['user_id']);
        $new_password = $_POST['new_password'];
        
        if (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $success_message = "Password reset successfully.";
            } catch (Exception $e) {
                error_log("Password reset error: " . $e->getMessage());
                $error_message = "Error resetting password.";
            }
        }
    }
}

// Fetch users
try {
    $stmt = $pdo->query("
        SELECT u.*, l.lgu_name,
               COUNT(d.disaster_id) as assigned_disasters
        FROM users u
        LEFT JOIN lgus l ON u.lgu_id = l.lgu_id
        LEFT JOIN disasters d ON u.user_id = d.assigned_user_id AND d.status NOT IN ('resolved', 'closed')
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
    
    // Fetch LGUs for dropdown
    $lgus_stmt = $pdo->query("SELECT lgu_id, lgu_name FROM lgus WHERE is_active = TRUE ORDER BY lgu_name");
    $lgus = $lgus_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Users fetch error: " . $e->getMessage());
    $users = [];
    $lgus = [];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-users"></i> User Management</h2>
        <p>Manage system users and their permissions</p>
    </div>
    <div class="page-actions">
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add User
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

<!-- Users Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo count($users); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?></div>
            <div class="stat-label">Active Users</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?></div>
            <div class="stat-label">Administrators</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-building"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo count(array_filter($users, fn($u) => in_array($u['role'], ['lgu_admin', 'lgu_staff']))); ?></div>
            <div class="stat-label">LGU Staff</div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>All Users</h3>
        <div class="search-box">
            <input type="text" id="userSearch" placeholder="Search users..." onkeyup="filterUsers()">
            <i class="fas fa-search"></i>
        </div>
    </div>
    <div class="card-content">
        <div class="table-responsive">
            <table id="users-table" class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>LGU</th>
                        <th>Status</th>
                        <th>Assigned Disasters</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                        <small class="user-id">ID: <?php echo $user['user_id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?php echo $user['email']; ?>" class="email-link">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($user['phone']): ?>
                                    <a href="tel:<?php echo $user['phone']; ?>" class="phone-link">
                                        <?php echo htmlspecialchars($user['phone']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $user['lgu_name'] ? htmlspecialchars($user['lgu_name']) : '<span class="text-muted">—</span>'; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['assigned_disasters'] > 0): ?>
                                    <span class="disaster-count"><?php echo $user['assigned_disasters']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <span class="last-login" title="<?php echo $user['last_login']; ?>">
                                        <?php echo date('M j, Y', strtotime($user['last_login'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="editUser(<?php echo $user['user_id']; ?>)" 
                                            class="btn btn-xs btn-secondary" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <button onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, <?php echo $user['is_active'] ? 0 : 1; ?>)" 
                                            class="btn btn-xs <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                            title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> User">
                                        <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                    </button>
                                    
                                    <button onclick="resetPassword(<?php echo $user['user_id']; ?>)" 
                                            class="btn btn-xs btn-info" title="Reset Password">
                                        <i class="fas fa-key"></i>
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

<!-- Create User Modal -->
<div id="createUserModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Create New User</h3>
            <button class="modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" name="phone" id="phone" placeholder="+63 9XX XXX XXXX">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select name="role" id="role" required onchange="toggleLguField()">
                        <option value="">Select role...</option>
                        <option value="admin">System Administrator</option>
                        <option value="lgu_admin">LGU Administrator</option>
                        <option value="lgu_staff">LGU Staff</option>
                        <option value="responder">Emergency Responder</option>
                    </select>
                </div>
                <div class="form-group" id="lgu-field" style="display: none;">
                    <label for="lgu_id">Assigned LGU</label>
                    <select name="lgu_id" id="lgu_id">
                        <option value="">Select LGU...</option>
                        <?php foreach ($lgus as $lgu): ?>
                            <option value="<?php echo $lgu['lgu_id']; ?>">
                                <?php echo htmlspecialchars($lgu['lgu_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" name="password" id="password" required minlength="8">
                <small class="form-help">Minimum 8 characters required</small>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeCreateModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="create_user" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update User Status</h3>
            <button class="modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="user_id" id="status-user-id">
            <input type="hidden" name="is_active" id="status-is-active">
            
            <p id="status-confirmation-text"></p>
            
            <div class="form-actions">
                <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="update_status" class="btn btn-primary">Confirm</button>
            </div>
        </form>
    </div>
</div>

<!-- Password Reset Modal -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reset User Password</h3>
            <button class="modal-close" onclick="closePasswordModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="user_id" id="password-user-id">
            
            <div class="form-group">
                <label for="new_password">New Password *</label>
                <input type="password" name="new_password" id="new_password" required minlength="8">
                <small class="form-help">Minimum 8 characters required</small>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closePasswordModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.search-box {
    position: relative;
    max-width: 300px;
}

.search-box input {
    width: 100%;
    padding: 8px 40px 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 14px;
}

.search-box i {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 32px;
    height: 32px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
}

.user-name {
    font-weight: 500;
    color: var(--text-color);
}

.user-id {
    color: var(--text-muted);
    font-size: 11px;
}

.email-link {
    color: var(--primary-color);
    text-decoration: none;
}

.email-link:hover {
    text-decoration: underline;
}

.phone-link {
    color: var(--text-color);
    text-decoration: none;
}

.phone-link:hover {
    color: var(--primary-color);
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

.role-lgu_admin {
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

.disaster-count {
    background-color: var(--warning-color);
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.last-login {
    color: var(--text-muted);
    font-size: 12px;
}

.action-buttons {
    display: flex;
    gap: 5px;
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
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
}
</style>

<script>
function showCreateModal() {
    document.getElementById('createUserModal').style.display = 'block';
}

function closeCreateModal() {
    document.getElementById('createUserModal').style.display = 'none';
}

function toggleLguField() {
    const role = document.getElementById('role').value;
    const lguField = document.getElementById('lgu-field');
    
    if (role === 'lgu_admin' || role === 'lgu_staff' || role === 'responder') {
        lguField.style.display = 'block';
        document.getElementById('lgu_id').required = true;
    } else {
        lguField.style.display = 'none';
        document.getElementById('lgu_id').required = false;
        document.getElementById('lgu_id').value = '';
    }
}

function toggleUserStatus(userId, newStatus) {
    document.getElementById('status-user-id').value = userId;
    document.getElementById('status-is-active').value = newStatus;
    
    const action = newStatus === 1 ? 'activate' : 'deactivate';
    document.getElementById('status-confirmation-text').textContent = 
        `Are you sure you want to ${action} this user?`;
    
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

function resetPassword(userId) {
    document.getElementById('password-user-id').value = userId;
    document.getElementById('passwordModal').style.display = 'block';
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
}

function editUser(userId) {
    // This would open an edit modal - for now, just show message
    alert('Edit functionality would be implemented here');
}

function filterUsers() {
    const input = document.getElementById('userSearch');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('users-table');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const name = row.cells[0].textContent.toLowerCase();
        const email = row.cells[1].textContent.toLowerCase();
        const role = row.cells[3].textContent.toLowerCase();
        
        if (name.includes(filter) || email.includes(filter) || role.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>