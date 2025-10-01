<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

$page_title = 'Disaster Reports Management';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $disaster_id = intval($_POST['disaster_id']);
    $new_status = sanitizeInput($_POST['status']);
    $comments = sanitizeInput($_POST['comments'] ?? '');
    
    try {
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
        
                // Set acknowledged timestamp if status is IN PROGRESS
        if ($new_status === 'IN PROGRESS') {
            $stmt = $pdo->prepare("UPDATE disasters SET acknowledged_at = NOW() WHERE disaster_id = ? AND acknowledged_at IS NULL");
            $stmt->execute([$disaster_id]);
        }
        
        // Set resolved timestamp if status is COMPLETED
        if ($new_status === 'COMPLETED') {
            $stmt = $pdo->prepare("UPDATE disasters SET resolved_at = NOW() WHERE disaster_id = ? AND resolved_at IS NULL");
            $stmt->execute([$disaster_id]);
        }
        
        $success_message = "Disaster status updated successfully.";
    } catch (Exception $e) {
        error_log("Status update error: " . $e->getMessage());
        $error_message = "Error updating status. Please try again.";
    }
}

// Get filter parameters
$status_filter = sanitizeInput($_GET['status'] ?? '');
$severity_filter = sanitizeInput($_GET['severity'] ?? '');
$type_filter = sanitizeInput($_GET['type'] ?? '');
$search_query = sanitizeInput($_GET['search'] ?? '');

// Build WHERE clause
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "d.status = ?";
    $params[] = $status_filter;
}

if (!empty($severity_filter)) {
    $where_conditions[] = "d.severity_level LIKE ?";
    $params[] = $severity_filter . '%';
}

if (!empty($type_filter)) {
    $where_conditions[] = "d.type_id = ?";
    $params[] = $type_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(d.tracking_id LIKE ? OR d.disaster_name LIKE ? OR d.description LIKE ? OR d.city LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fetch disasters
try {
    $sql = "
        SELECT d.*, dt.type_name, lgu.lgu_name,
               CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
               TIMESTAMPDIFF(HOUR, d.reported_at, NOW()) as hours_since_report
        FROM disasters d
        JOIN disaster_types dt ON d.type_id = dt.type_id
        LEFT JOIN lgus lgu ON d.assigned_lgu_id = lgu.lgu_id
        LEFT JOIN users u ON d.assigned_user_id = u.user_id
        {$where_clause}
        ORDER BY d.priority DESC, d.reported_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $disasters = $stmt->fetchAll();
    
    // Get filter options
    $types_stmt = $pdo->query("SELECT type_id, type_name FROM disaster_types WHERE is_active = TRUE ORDER BY type_name");
    $disaster_types = $types_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Disasters fetch error: " . $e->getMessage());
    $disasters = [];
    $disaster_types = [];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-exclamation-triangle"></i> Disaster Reports</h2>
        <p>Monitor and manage emergency reports from citizens</p>
    </div>
    <div class="page-actions">
        <button onclick="exportTable('disasters-table', 'disaster-reports.csv')" class="btn btn-secondary">
            <i class="fas fa-download"></i> Export CSV
        </button>
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

<!-- Filters -->
<div class="filters-card">
    <form method="GET" class="filters-form">
        <div class="filter-group">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="">All Statuses</option>
                <option value="ON GOING" <?php echo $status_filter === 'ON GOING' ? 'selected' : ''; ?>>On Going</option>
                <option value="IN PROGRESS" <?php echo $status_filter === 'IN PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                <option value="COMPLETED" <?php echo $status_filter === 'COMPLETED' ? 'selected' : ''; ?>>Completed</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="severity">Severity</label>
            <select name="severity" id="severity">
                <option value="">All Severities</option>
                <option value="red" <?php echo $severity_filter === 'red' ? 'selected' : ''; ?>>Critical (Red)</option>
                <option value="orange" <?php echo $severity_filter === 'orange' ? 'selected' : ''; ?>>Moderate (Orange)</option>
                <option value="green" <?php echo $severity_filter === 'green' ? 'selected' : ''; ?>>Minor (Green)</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="type">Disaster Type</label>
            <select name="type" id="type">
                <option value="">All Types</option>
                <?php foreach ($disaster_types as $type): ?>
                    <option value="<?php echo $type['type_id']; ?>" 
                            <?php echo $type_filter == $type['type_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['type_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="search">Search</label>
            <input type="text" name="search" id="search" 
                   value="<?php echo htmlspecialchars($search_query); ?>"
                   placeholder="Search by tracking ID, name, or location...">
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filter
            </button>
            <a href="disasters.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
        </div>
    </form>
</div>

<!-- Statistics Summary -->
<div class="summary-stats">
    <div class="stat-item">
        <span class="stat-number"><?php echo count($disasters); ?></span>
        <span class="stat-label">Total Results</span>
    </div>
    <div class="stat-item critical">
        <span class="stat-number">
            <?php echo count(array_filter($disasters, fn($d) => $d['priority'] === 'critical')); ?>
        </span>
        <span class="stat-label">Critical</span>
    </div>
    <div class="stat-item pending">
        <span class="stat-number">
            <?php echo count(array_filter($disasters, fn($d) => in_array($d['status'], ['pending', 'assigned']))); ?>
        </span>
        <span class="stat-label">Pending Response</span>
    </div>
    <div class="stat-item overdue">
        <span class="stat-number">
            <?php echo count(array_filter($disasters, fn($d) => strtotime($d['escalation_deadline']) < time() && !in_array($d['status'], ['resolved', 'closed']))); ?>
        </span>
        <span class="stat-label">Overdue</span>
    </div>
</div>

<!-- Disasters Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>Emergency Reports</h3>
        <div class="view-options">
            <button class="btn btn-sm btn-secondary active" data-view="table">
                <i class="fas fa-table"></i> Table View
            </button>
            <button class="btn btn-sm btn-secondary" data-view="cards">
                <i class="fas fa-th-large"></i> Card View
            </button>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($disasters)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No disasters found</h3>
                <p>No emergency reports match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="disasters-table" class="table disasters-data-table">
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Tracking ID</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Reporter</th>
                            <th>Assigned To</th>
                            <th>Time Ago</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($disasters as $disaster): ?>
                            <tr class="<?php echo $disaster['priority'] === 'critical' ? 'priority-critical' : ''; ?>">
                                <td>
                                    <span class="priority-badge priority-<?php echo $disaster['priority']; ?>">
                                        <?php echo ucfirst($disaster['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="tracking-id"><?php echo htmlspecialchars($disaster['tracking_id']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($disaster['type_name']); ?></td>
                                <td>
                                    <div class="location-info">
                                        <div class="city"><?php echo htmlspecialchars($disaster['city'] ?? 'N/A'); ?></div>
                                        <?php if ($disaster['landmark']): ?>
                                            <small class="landmark">Near <?php echo htmlspecialchars($disaster['landmark']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="severity-badge severity-<?php echo substr($disaster['severity_level'], 0, strpos($disaster['severity_level'], '-')); ?>">
                                        <?php echo htmlspecialchars($disaster['severity_display']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="status-container">
                                        <span class="status-badge status-<?php echo $disaster['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $disaster['status'])); ?>
                                        </span>
                                        <?php if (strtotime($disaster['escalation_deadline']) < time() && !in_array($disaster['status'], ['resolved', 'closed'])): ?>
                                            <span class="overdue-indicator" title="Past deadline">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="reporter-info">
                                        <?php if ($disaster['reporter_name']): ?>
                                            <div class="name"><?php echo htmlspecialchars($disaster['reporter_name']); ?></div>
                                        <?php else: ?>
                                            <div class="name">Anonymous</div>
                                        <?php endif; ?>
                                        <small class="phone"><?php echo htmlspecialchars($disaster['reporter_phone']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($disaster['lgu_name']): ?>
                                        <div class="assignment-info">
                                            <div class="lgu"><?php echo htmlspecialchars($disaster['lgu_name']); ?></div>
                                            <?php if ($disaster['assigned_user_name']): ?>
                                                <small class="user"><?php echo htmlspecialchars($disaster['assigned_user_name']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="unassigned">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="time-ago" title="<?php echo $disaster['reported_at']; ?>">
                                        <?php echo $disaster['hours_since_report']; ?>h ago
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view-disaster.php?id=<?php echo urlencode($disaster['disaster_id']); ?>" 
                                           class="btn btn-xs btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if (hasRole(['admin', 'lgu_admin', 'lgu_staff'])): ?>
                                            <button onclick="showStatusModal(<?php echo $disaster['disaster_id']; ?>, '<?php echo $disaster['status']; ?>')" 
                                                    class="btn btn-xs btn-warning" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($disaster['status'] !== 'resolved' && $disaster['status'] !== 'closed'): ?>
                                            <a href="disaster-resources.php?id=<?php echo $disaster['disaster_id']; ?>" 
                                               class="btn btn-xs btn-success" title="Manage Resources">
                                                <i class="fas fa-boxes"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Disaster Status</h3>
            <button class="modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="disaster_id" id="modal-disaster-id">
            
            <div class="form-group">
                <label for="modal-status">New Status</label>
                <select name="status" id="modal-status" required>
                    <option value="ON GOING">On Going</option>
                    <option value="IN PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="modal-comments">Comments (Optional)</label>
                <textarea name="comments" id="modal-comments" rows="3" 
                          placeholder="Add notes about this status change..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
}

.page-title h2 {
    margin: 0 0 5px 0;
    font-size: 1.8rem;
    color: var(--text-color);
}

.page-title p {
    color: var(--text-muted);
    margin: 0;
}

.page-actions {
    display: flex;
    gap: 10px;
}

.filters-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 20px;
    margin-bottom: 20px;
}

.filters-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    margin-bottom: 5px;
    font-weight: 500;
    color: var(--text-color);
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-item {
    background: white;
    padding: 15px 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    text-align: center;
    border-left: 4px solid var(--info-color);
}

.stat-item.critical {
    border-left-color: var(--danger-color);
}

.stat-item.pending {
    border-left-color: var(--warning-color);
}

.stat-item.overdue {
    border-left-color: #dc2626;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-color);
}

.stat-label {
    font-size: 12px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.priority-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-low {
    background-color: #e5e7eb;
    color: #374151;
}

.priority-medium {
    background-color: #fef3c7;
    color: #92400e;
}

.priority-high {
    background-color: #fee2e2;
    color: #991b1b;
}

.priority-critical {
    background-color: #dc2626;
    color: white;
}

.priority-critical {
    background-color: #fef2f2;
    border-left: 3px solid var(--danger-color);
}

.location-info .city {
    font-weight: 500;
}

.location-info .landmark {
    color: var(--text-muted);
    font-size: 11px;
}

.status-container {
    display: flex;
    align-items: center;
    gap: 5px;
}

.overdue-indicator {
    color: var(--danger-color);
    font-size: 12px;
}

.reporter-info .name {
    font-weight: 500;
}

.reporter-info .phone {
    color: var(--text-muted);
    font-size: 11px;
}

.assignment-info .lgu {
    font-weight: 500;
}

.assignment-info .user {
    color: var(--text-muted);
    font-size: 11px;
}

.unassigned {
    color: var(--text-muted);
    font-style: italic;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.view-options {
    display: flex;
    gap: 5px;
}

.view-options .btn.active {
    background-color: var(--primary-color);
    color: white;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: var(--border-radius);
    max-width: 500px;
    margin: 100px auto;
    box-shadow: var(--shadow-lg);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-muted);
}

.modal-form {
    padding: 20px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}
</style>

<script>
function showStatusModal(disasterId, currentStatus) {
    document.getElementById('modal-disaster-id').value = disasterId;
    document.getElementById('modal-status').value = currentStatus;
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});

// Export table to CSV
function exportTable(tableId, filename) {
    const table = document.getElementById(tableId);
    let csv = [];
    
    // Get headers
    const headers = [];
    const headerCells = table.querySelectorAll('thead tr th');
    headerCells.forEach(cell => {
        if (cell.textContent.trim() !== 'Actions') { // Skip actions column
            headers.push(cell.textContent.trim());
        }
    });
    csv.push(headers.join(','));
    
    // Get data rows
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('td');
        cells.forEach((cell, index) => {
            if (index < cells.length - 1) { // Skip last column (actions)
                let cellText = cell.textContent.trim().replace(/,/g, ';');
                rowData.push('"' + cellText + '"');
            }
        });
        csv.push(rowData.join(','));
    });
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Print page
function printPage() {
    window.print();
}

// Initialize DataTable for disasters specifically
$(document).ready(function() {
    // Wait a bit for admin.js to finish loading
    setTimeout(function() {
        if ($('#disasters-table').length && $.fn.DataTable) {
            $('#disasters-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [9] } // Actions column
                ],
                language: {
                    search: "Search disasters:",
                    lengthMenu: "Show _MENU_ disasters per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ disasters"
                }
            });
        }
    }, 500);
});
</script>

<?php include 'includes/footer.php'; ?>