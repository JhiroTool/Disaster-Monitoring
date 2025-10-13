<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

// Only admins can access user management
if (!hasRole(['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Reporter Management';

// Fetch reporters and their activity metrics
try {
    $stmt = $pdo->prepare("
        SELECT u.*,
               COUNT(d.disaster_id) AS total_reports,
               COALESCE(SUM(CASE WHEN d.status = 'ON GOING' THEN 1 ELSE 0 END), 0) AS ongoing_reports,
               COALESCE(SUM(CASE WHEN d.status = 'IN PROGRESS' THEN 1 ELSE 0 END), 0) AS in_progress_reports,
               COALESCE(SUM(CASE WHEN d.status = 'COMPLETED' THEN 1 ELSE 0 END), 0) AS completed_reports,
               MAX(d.created_at) AS last_report_at
        FROM users u
        LEFT JOIN disasters d ON u.user_id = d.reported_by_user_id
        WHERE u.role = 'reporter'
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Users fetch error: " . $e->getMessage());
    $users = [];
}

$total_reporters = count($users);
$reporters_need_help = count(array_filter($users, fn($u) => ($u['status'] ?? '') === 'Need help'));
$reporters_safe = count(array_filter($users, fn($u) => ($u['status'] ?? '') === "I'm fine"));
$report_totals = array_reduce($users, function ($carry, $user) {
    $carry['total'] += (int)($user['total_reports'] ?? 0);
    $carry['ongoing'] += (int)($user['ongoing_reports'] ?? 0);
    $carry['in_progress'] += (int)($user['in_progress_reports'] ?? 0);
    $carry['completed'] += (int)($user['completed_reports'] ?? 0);
    return $carry;
}, ['total' => 0, 'ongoing' => 0, 'in_progress' => 0, 'completed' => 0]);
$open_reports = max(0, $report_totals['total'] - $report_totals['completed']);

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-users"></i> Reporter Management</h2>
        <p>Monitor reporter activity, workload, and account health</p>
    </div>
</div>

<!-- Reporter Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-id-badge"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $total_reporters; ?></div>
            <div class="stat-label">Total Reporters</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-hands-helping"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $reporters_need_help; ?></div>
            <div class="stat-label">Need Help</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-heart"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $reporters_safe; ?></div>
            <div class="stat-label">I'm Fine</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo number_format($report_totals['total']); ?></div>
            <div class="stat-label">Reports Filed</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo number_format($open_reports); ?></div>
            <div class="stat-label">Open Reports</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo number_format($report_totals['completed']); ?></div>
            <div class="stat-label">Completed Reports</div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>All Reporters</h3>
        <div class="search-box">
            <input type="text" id="userSearch" placeholder="Search reporters..." onkeyup="filterUsers()">
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
                        <th>Reports Filed</th>
                        <th>Status</th>
                        <th>Report Statuses</th>
                        <th>Last Login</th>
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
                                <?php
                                    $totalReports = (int)($user['total_reports'] ?? 0);
                                    $lastReportAt = $user['last_report_at'] ?? null;
                                ?>
                                <div class="report-count-wrapper">
                                    <span class="report-count" title="Total reports filed">
                                        <?php echo number_format($totalReports); ?>
                                    </span>
                                    <div class="report-meta">
                                        <small><?php echo $totalReports === 1 ? 'Report' : 'Reports'; ?></small>
                                        <small class="last-report">
                                            <?php echo $lastReportAt ? 'Last: ' . date('M j, Y', strtotime($lastReportAt)) : 'No reports yet'; ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php $reporterStatus = $user['status'] ?? "I'm fine"; ?>
                                <span class="reporter-status reporter-status-<?php echo $reporterStatus === 'Need help' ? 'help' : 'fine'; ?>">
                                    <i class="fas <?php echo $reporterStatus === 'Need help' ? 'fa-life-ring' : 'fa-user-check'; ?>"></i>
                                    <?php echo htmlspecialchars($reporterStatus); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                    $ongoingReports = (int)($user['ongoing_reports'] ?? 0);
                                    $inProgressReports = (int)($user['in_progress_reports'] ?? 0);
                                    $completedReports = (int)($user['completed_reports'] ?? 0);
                                    $otherReports = $totalReports - ($ongoingReports + $inProgressReports + $completedReports);
                                    if ($otherReports < 0) {
                                        $otherReports = 0;
                                    }
                                ?>
                                <div class="report-statuses">
                                    <?php if ($totalReports === 0): ?>
                                        <span class="text-muted">No reports yet</span>
                                    <?php else: ?>
                                        <?php if ($ongoingReports > 0): ?>
                                            <span class="status-chip status-chip-ongoing" title="On Going reports">
                                                On Going <strong><?php echo $ongoingReports; ?></strong>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($inProgressReports > 0): ?>
                                            <span class="status-chip status-chip-progress" title="In Progress reports">
                                                In Progress <strong><?php echo $inProgressReports; ?></strong>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($completedReports > 0): ?>
                                            <span class="status-chip status-chip-completed" title="Completed reports">
                                                Completed <strong><?php echo $completedReports; ?></strong>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($otherReports > 0): ?>
                                            <span class="status-chip status-chip-other" title="Reports with other statuses">
                                                Other <strong><?php echo $otherReports; ?></strong>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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

.role-reporter {
    background-color: #e8f5e8;
    color: #388e3c;
}

.disaster-count {
    background-color: var(--warning-color);
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.report-count-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.report-count {
    background: var(--primary-color);
    color: #fff;
    padding: 6px 12px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 16px;
    min-width: 44px;
    text-align: center;
    box-shadow: 0 6px 18px rgba(30, 64, 175, 0.18);
}

.report-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 12px;
    color: var(--text-muted);
}

.report-meta .last-report {
    font-size: 11px;
}

.report-statuses {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.reporter-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 600;
    font-size: 12px;
    letter-spacing: 0.01em;
    text-transform: none;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}

.reporter-status i {
    font-size: 13px;
}

.reporter-status-fine {
    background: #ecfdf5;
    color: #047857;
}

.reporter-status-help {
    background: #fef2f2;
    color: #b91c1c;
}

.status-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.status-chip strong {
    font-size: 12px;
}

.status-chip-ongoing {
    background: #fff7e6;
    color: #d97706;
}

.status-chip-progress {
    background: #e0f2fe;
    color: #0369a1;
}

.status-chip-completed {
    background: #e6f4ea;
    color: #1b7d2f;
}

.status-chip-other {
    background: #f3f4f6;
    color: #4b5563;
}

.stat-icon.danger {
    background-color: #fee2e2;
    color: #b91c1c;
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
function filterUsers() {
    const input = document.getElementById('userSearch');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('users-table');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const searchableText = Array.from(row.getElementsByTagName('td'))
            .map(cell => cell.textContent.toLowerCase())
            .join(' ');
        
        row.style.display = searchableText.includes(filter) ? '' : 'none';
    }
}

// ====================================
// REAL-TIME INTEGRATION
// ====================================
console.log('🔍 Checking for RealtimeSystem...', typeof window.RealtimeSystem);

// Wait a bit for the system to initialize
setTimeout(() => {
    if (window.RealtimeSystem) {
        console.log('✅ RealtimeSystem found! Registering callbacks...');
        
        // Listen for specific user status changes (preferred method)
        window.RealtimeSystem.registerCallback('onUserStatusChange', (data) => {
            console.log('👤 User status change detected via dedicated callback', data);
            updateUserStatuses();
        });
        
        // Also listen for general updates as fallback
        window.RealtimeSystem.registerCallback('onUpdate', (data) => {
            console.log('📊 General update received:', data);
            if (data.changes && data.changes.user_status_changed) {
                console.log('👤 User status changed detected via general update');
                updateUserStatuses();
            }
        });
        
        // Listen for connection events
        window.RealtimeSystem.registerCallback('onConnect', (data) => {
            console.log('✅ SSE Connected:', data);
        });
        
        window.RealtimeSystem.registerCallback('onStatusChange', (status) => {
            console.log('🔄 Connection status changed:', status);
        });
        
        console.log('✅ Real-time updates enabled for users page');
        
        // Check current connection status
        const status = window.RealtimeSystem.getStatus();
        console.log('📊 Current RealtimeSystem status:', status);
    } else {
        console.error('❌ Real-time system not available after timeout!');
        console.log('Available on window:', Object.keys(window).filter(k => k.toLowerCase().includes('real')));
    }
}, 1000);

// Fetch and update user statuses in real-time
async function updateUserStatuses() {
    try {
        const response = await fetch('ajax/get-users-data.php');
        const data = await response.json();
        
        if (data.success) {
            console.log('🔄 Updating user statuses...', data);
            
            // Update statistics
            updateStatCards(data.counts);
            
            // Update each user row
            data.users.forEach(user => {
                updateUserRow(user);
            });
            
            // Show notification
            showUserUpdateNotification('Reporter status updated!');
        }
    } catch (error) {
        console.error('Error updating user statuses:', error);
    }
}

function updateStatCards(counts) {
    // Update stat card numbers with animation
    const statCards = {
        'users_need_help': counts.need_help,
        'users_safe': counts.safe
    };
    
    // Update "Need Help" count (2nd stat card)
    const needHelpCard = document.querySelectorAll('.stat-card')[1];
    if (needHelpCard) {
        const numberEl = needHelpCard.querySelector('.stat-number');
        if (numberEl && numberEl.textContent != counts.need_help) {
            animateNumberChange(numberEl, counts.need_help);
            flashElement(needHelpCard);
        }
    }
    
    // Update "I'm Fine" count (3rd stat card)
    const safeCard = document.querySelectorAll('.stat-card')[2];
    if (safeCard) {
        const numberEl = safeCard.querySelector('.stat-number');
        if (numberEl && numberEl.textContent != counts.safe) {
            animateNumberChange(numberEl, counts.safe);
            flashElement(safeCard);
        }
    }
}

function updateUserRow(user) {
    const rows = document.querySelectorAll('#users-table tbody tr');
    
    rows.forEach(row => {
        const userIdElement = row.querySelector('.user-id');
        if (userIdElement && userIdElement.textContent === `ID: ${user.user_id}`) {
            // Update status badge
            const statusCell = row.children[4]; // Status column
            const currentStatus = user.status || "I'm fine";
            const statusClass = currentStatus === 'Need help' ? 'help' : 'fine';
            const statusIcon = currentStatus === 'Need help' ? 'fa-life-ring' : 'fa-user-check';
            
            statusCell.innerHTML = `
                <span class="reporter-status reporter-status-${statusClass}">
                    <i class="fas ${statusIcon}"></i>
                    ${currentStatus}
                </span>
            `;
            
            // Flash the row to indicate update
            flashElement(row);
        }
    });
}

function animateNumberChange(element, newValue) {
    const oldValue = parseInt(element.textContent) || 0;
    const duration = 500;
    const steps = 20;
    const increment = (newValue - oldValue) / steps;
    let currentStep = 0;
    
    const interval = setInterval(() => {
        currentStep++;
        const value = Math.round(oldValue + (increment * currentStep));
        element.textContent = value;
        
        if (currentStep >= steps) {
            clearInterval(interval);
            element.textContent = newValue;
        }
    }, duration / steps);
}

function flashElement(element) {
    element.style.transition = 'background-color 0.3s ease';
    const originalBg = element.style.backgroundColor;
    element.style.backgroundColor = '#fef3c7'; // Light yellow flash
    
    setTimeout(() => {
        element.style.backgroundColor = originalBg;
    }, 600);
}

function showUserUpdateNotification(message = 'User data updated') {
    // Simple notification that user status has changed
    const notification = document.createElement('div');
    notification.className = 'realtime-notification';
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 14px 18px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
        z-index: 9999;
        font-size: 14px;
        font-weight: 500;
        animation: slideInRight 0.3s ease-out;
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    notification.innerHTML = `
        <i class="fas fa-sync-alt" style="animation: spin 1s linear infinite;"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            cursor: pointer;
            margin-left: 8px;
            font-size: 18px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">×</button>
    `;
    
    // Add animation styles
    if (!document.getElementById('notification-animations')) {
        const style = document.createElement('style');
        style.id = 'notification-animations';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideInRight 0.3s ease-out reverse';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}
</script>

<?php include 'includes/footer.php'; ?>