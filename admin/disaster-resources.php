<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

// Allow access to all authenticated users for disaster resource management
$can_edit = true; // All users can deploy and manage disaster resources

$page_title = 'Disaster Resource Deployment';
$user_lgu_id = $_SESSION['user_data']['lgu_id'] ?? null;

// Handle disaster resource operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['deploy_resource'])) {
        $disaster_id = intval($_POST['disaster_id']);
        $resource_id = intval($_POST['resource_id']);
        $quantity_deployed = intval($_POST['quantity_deployed']);
        $deployment_notes = sanitizeInput($_POST['deployment_notes']);
        
        try {
            // Check resource availability
            $check_stmt = $pdo->prepare("
                SELECT r.quantity_available, 
                       COALESCE(SUM(CASE WHEN dr.status = 'active' THEN dr.quantity_deployed ELSE 0 END), 0) as currently_deployed
                FROM resources r
                LEFT JOIN disaster_resource_deployments dr ON r.resource_id = dr.resource_id
                WHERE r.resource_id = ?
                GROUP BY r.resource_id
            ");
            $check_stmt->execute([$resource_id]);
            $resource_info = $check_stmt->fetch();
            
            $available_quantity = $resource_info['quantity_available'] - $resource_info['currently_deployed'];
            
            if ($quantity_deployed > $available_quantity) {
                throw new Exception("Insufficient quantity available. Only $available_quantity units available.");
            }
            
            // Deploy resource using disaster_resource_deployments table
            $stmt = $pdo->prepare("
                INSERT INTO disaster_resource_deployments (disaster_id, resource_id, quantity_deployed, deployment_notes, deployed_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$disaster_id, $resource_id, $quantity_deployed, $deployment_notes, $_SESSION['user_data']['user_id']]);
            
            // Update resource status if fully deployed
            if ($available_quantity == $quantity_deployed) {
                $update_stmt = $pdo->prepare("UPDATE resources SET availability_status = 'deployed' WHERE resource_id = ?");
                $update_stmt->execute([$resource_id]);
            }
            
            $success_message = "Resource deployed successfully.";
        } catch (Exception $e) {
            error_log("Resource deployment error: " . $e->getMessage());
            $error_message = $e->getMessage();
        }
    }
    
    if (isset($_POST['update_deployment'])) {
        $deployment_id = intval($_POST['deployment_id']);
        $quantity_deployed = intval($_POST['quantity_deployed']);
        $deployment_notes = sanitizeInput($_POST['deployment_notes']);
        $status = sanitizeInput($_POST['status']);
        
        try {
            $stmt = $pdo->prepare("
                UPDATE disaster_resource_deployments 
                SET quantity_deployed = ?, deployment_notes = ?, status = ?, updated_at = NOW()
                WHERE deployment_id = ?
            ");
            $stmt->execute([$quantity_deployed, $deployment_notes, $status, $deployment_id]);
            
            // If returning resource, update resource availability
            if ($status === 'returned') {
                $return_stmt = $pdo->prepare("
                    UPDATE resources r
                    JOIN disaster_resources dr ON r.resource_id = dr.resource_id
                    SET r.availability_status = 'available'
                    WHERE dr.deployment_id = ?
                ");
                $return_stmt->execute([$deployment_id]);
            }
            
            $success_message = "Deployment updated successfully.";
        } catch (Exception $e) {
            error_log("Deployment update error: " . $e->getMessage());
            $error_message = "Error updating deployment.";
        }
    }
    
    if (isset($_POST['return_resource'])) {
        $deployment_id = intval($_POST['deployment_id']);
        $return_notes = sanitizeInput($_POST['return_notes']);
        
        try {
            $stmt = $pdo->prepare("
                UPDATE disaster_resource_deployments 
                SET status = 'returned', return_notes = ?, returned_at = NOW(), updated_at = NOW()
                WHERE deployment_id = ?
            ");
            $stmt->execute([$return_notes, $deployment_id]);
            
            // Update resource status
            $update_stmt = $pdo->prepare("
                UPDATE resources r
                JOIN disaster_resource_deployments dr ON r.resource_id = dr.resource_id
                SET r.availability_status = 'available'
                WHERE dr.deployment_id = ?
            ");
            $update_stmt->execute([$deployment_id]);
            
            $success_message = "Resource returned successfully.";
        } catch (Exception $e) {
            error_log("Resource return error: " . $e->getMessage());
            $error_message = "Error returning resource.";
        }
    }
}

// Build query based on user permissions
$deployment_query = "
    SELECT dr.*, d.title as disaster_title, d.disaster_type, d.location as disaster_location,
           r.resource_name, r.resource_type, r.unit, r.location as resource_location,
           l.lgu_name, u.full_name as deployed_by_name,
           TIMESTAMPDIFF(DAY, dr.deployed_at, COALESCE(dr.returned_at, NOW())) as deployment_days
    FROM disaster_resource_deployments dr
    JOIN disasters d ON dr.disaster_id = d.disaster_id
    JOIN resources r ON dr.resource_id = r.resource_id
    LEFT JOIN lgus l ON r.owner_lgu_id = l.lgu_id
    LEFT JOIN users u ON dr.deployed_by = u.user_id
";

$params = [];
if (!hasRole(['admin'])) {
    $deployment_query .= " WHERE (d.assigned_lgu_id = ? OR r.owner_lgu_id = ?)";
    $params = [$user_lgu_id, $user_lgu_id];
}

$deployment_query .= " ORDER BY dr.deployed_at DESC";

try {
    $stmt = $pdo->prepare($deployment_query);
    $stmt->execute($params);
    $deployments = $stmt->fetchAll();
    
    // Fetch active disasters for deployment
    $disaster_query = "
        SELECT d.disaster_id, d.title, d.disaster_type, d.location, d.status, l.lgu_name
        FROM disasters d
        LEFT JOIN lgus l ON d.assigned_lgu_id = l.lgu_id
        WHERE d.status IN ('pending', 'investigating', 'responding')
    ";
    
    if (!hasRole(['admin'])) {
        $disaster_query .= " AND d.assigned_lgu_id = ?";
        $disaster_params = [$user_lgu_id];
    } else {
        $disaster_params = [];
    }
    
    $disaster_query .= " ORDER BY d.created_at DESC";
    
    $disaster_stmt = $pdo->prepare($disaster_query);
    $disaster_stmt->execute($disaster_params);
    $disasters = $disaster_stmt->fetchAll();
    
    // Fetch deployment statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_deployments,
            COUNT(CASE WHEN dr.status = 'active' THEN 1 END) as active_deployments,
            COUNT(CASE WHEN dr.status = 'returned' THEN 1 END) as returned_deployments,
            COUNT(DISTINCT dr.resource_id) as unique_resources_deployed,
            SUM(CASE WHEN dr.status = 'active' THEN dr.quantity_deployed ELSE 0 END) as total_quantity_deployed
        FROM disaster_resource_deployments dr
        JOIN resources r ON dr.resource_id = r.resource_id
    ";
    
    if (!hasRole(['admin'])) {
        $stats_query .= " WHERE r.owner_lgu_id = ?";
        $stats_params = [$user_lgu_id];
    } else {
        $stats_params = [];
    }
    
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute($stats_params);
    $stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    error_log("Disaster resources fetch error: " . $e->getMessage());
    $deployments = [];
    $disasters = [];
    $stats = ['total_deployments' => 0, 'active_deployments' => 0, 'returned_deployments' => 0, 'unique_resources_deployed' => 0, 'total_quantity_deployed' => 0];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-shipping-fast"></i> Disaster Resource Deployment</h2>
        <p>Manage resource deployment for disaster response</p>
    </div>
    <div class="page-actions">
        <button onclick="showDeployModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Deploy Resource
        </button>
        <button onclick="showBulkReturnModal()" class="btn btn-secondary">
            <i class="fas fa-undo"></i> Bulk Return
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

<!-- Deployment Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-shipping-fast"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['total_deployments']; ?></div>
            <div class="stat-label">Total Deployments</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['active_deployments']; ?></div>
            <div class="stat-label">Active Deployments</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['returned_deployments']; ?></div>
            <div class="stat-label">Returned</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-boxes"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['unique_resources_deployed']; ?></div>
            <div class="stat-label">Unique Resources</div>
        </div>
    </div>
</div>

<!-- Deployments Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>Resource Deployments</h3>
        <div class="filters">
            <select id="statusFilter" onchange="filterDeployments()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="returned">Returned</option>
            </select>
            <select id="typeFilter" onchange="filterDeployments()">
                <option value="">All Types</option>
                <option value="vehicle">Vehicles</option>
                <option value="equipment">Equipment</option>
                <option value="medical">Medical</option>
                <option value="food">Food & Water</option>
                <option value="shelter">Shelter</option>
                <option value="communication">Communication</option>
            </select>
        </div>
    </div>
    <div class="card-content">
        <div class="table-container">
            <table class="data-table" id="deploymentsTable">
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Disaster</th>
                        <th>Quantity</th>
                        <th>Owner LGU</th>
                        <th>Status</th>
                        <th>Deployed At</th>
                        <th>Duration</th>
                        <th>Deployed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deployments as $deployment): ?>
                        <tr>
                            <td>
                                <div class="resource-info">
                                    <strong><?php echo htmlspecialchars($deployment['resource_name']); ?></strong>
                                    <div class="resource-details">
                                        <span class="type-badge type-<?php echo $deployment['resource_type']; ?>">
                                            <?php echo ucfirst($deployment['resource_type']); ?>
                                        </span>
                                        <small class="text-muted">@ <?php echo htmlspecialchars($deployment['resource_location']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="disaster-info">
                                    <strong><?php echo htmlspecialchars($deployment['disaster_title']); ?></strong>
                                    <div class="disaster-details">
                                        <small><?php echo ucfirst($deployment['disaster_type']); ?></small>
                                        <small class="text-muted">@ <?php echo htmlspecialchars($deployment['disaster_location']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="quantity-info">
                                    <span class="quantity"><?php echo $deployment['quantity_deployed']; ?></span>
                                    <small><?php echo htmlspecialchars($deployment['unit']); ?></small>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($deployment['lgu_name']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $deployment['status']; ?>">
                                    <?php echo ucfirst($deployment['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($deployment['deployed_at'])); ?></td>
                            <td>
                                <div class="duration-info">
                                    <?php echo $deployment['deployment_days']; ?> days
                                    <?php if ($deployment['status'] === 'active'): ?>
                                        <small class="text-warning">(ongoing)</small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($deployment['deployed_by_name']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewDeployment(<?php echo $deployment['deployment_id']; ?>)" 
                                            class="btn btn-xs btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($deployment['status'] === 'active'): ?>
                                        <button onclick="updateDeployment(<?php echo $deployment['deployment_id']; ?>)" 
                                                class="btn btn-xs btn-secondary" title="Update">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="returnResource(<?php echo $deployment['deployment_id']; ?>)" 
                                                class="btn btn-xs btn-success" title="Return Resource">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Deploy Resource Modal -->
<div id="deployResourceModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Deploy Resource</h3>
            <button class="modal-close" onclick="closeDeployModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="disaster_id">Select Disaster *</label>
                    <select name="disaster_id" id="disaster_id" required onchange="loadDisasterInfo()">
                        <option value="">Choose a disaster...</option>
                        <?php foreach ($disasters as $disaster): ?>
                            <option value="<?php echo $disaster['disaster_id']; ?>">
                                <?php echo htmlspecialchars($disaster['title']); ?> 
                                (<?php echo ucfirst($disaster['disaster_type']); ?>)
                                <?php if ($disaster['lgu_name']): ?>
                                    - <?php echo htmlspecialchars($disaster['lgu_name']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="resource_id">Select Resource *</label>
                    <select name="resource_id" id="resource_id" required onchange="loadResourceInfo()">
                        <option value="">Choose a resource...</option>
                    </select>
                </div>
            </div>
            
            <div id="resource-info" class="info-panel" style="display: none;">
                <h4>Resource Information</h4>
                <div id="resource-details"></div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="quantity_deployed">Quantity to Deploy *</label>
                    <input type="number" name="quantity_deployed" id="quantity_deployed" min="1" required>
                    <small id="available-quantity" class="form-help"></small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="deployment_notes">Deployment Notes</label>
                <textarea name="deployment_notes" id="deployment_notes" rows="3" 
                          placeholder="Any special instructions or notes about this deployment..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeDeployModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="deploy_resource" class="btn btn-primary">
                    <i class="fas fa-shipping-fast"></i> Deploy Resource
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Return Resource Modal -->
<div id="returnModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Return Resource</h3>
            <button class="modal-close" onclick="closeReturnModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="deployment_id" id="return-deployment-id">
            
            <div class="form-group">
                <label for="return_notes">Return Notes</label>
                <textarea name="return_notes" id="return_notes" rows="3" 
                          placeholder="Condition of returned resource, any damage, etc."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeReturnModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="return_resource" class="btn btn-success">
                    <i class="fas fa-undo"></i> Return Resource
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.resource-info strong,
.disaster-info strong {
    color: var(--text-color);
    display: block;
    margin-bottom: 4px;
}

.resource-details,
.disaster-details {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.quantity-info {
    text-align: center;
}

.quantity {
    font-size: 16px;
    font-weight: 700;
    color: var(--primary-color);
}

.duration-info {
    text-align: center;
}

.info-panel {
    background: #f8f9fa;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 15px;
    margin: 15px 0;
}

.info-panel h4 {
    margin: 0 0 10px 0;
    color: var(--text-color);
}

.type-badge {
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.type-vehicle { background: #e3f2fd; color: #1976d2; }
.type-equipment { background: #f3e5f5; color: #7b1fa2; }
.type-medical { background: #ffebee; color: #d32f2f; }
.type-food { background: #e8f5e8; color: #2e7d32; }
.type-shelter { background: #fff3e0; color: #f57c00; }
.type-communication { background: #e0f2f1; color: #00695c; }

.status-active { background: #fff3e0; color: #f57c00; }
.status-returned { background: #e8f5e8; color: #2e7d32; }
</style>

<script>
function showDeployModal() {
    document.getElementById('deployResourceModal').style.display = 'block';
    loadAvailableResources();
}

function closeDeployModal() {
    document.getElementById('deployResourceModal').style.display = 'none';
    document.querySelector('#deployResourceModal form').reset();
    document.getElementById('resource-info').style.display = 'none';
}

function loadAvailableResources() {
    // This would fetch available resources via AJAX
    // For now, we'll populate with a basic list
    const resourceSelect = document.getElementById('resource_id');
    resourceSelect.innerHTML = '<option value="">Loading resources...</option>';
    
    // Simulate loading - in real implementation, use AJAX
    setTimeout(() => {
        resourceSelect.innerHTML = '<option value="">Choose a resource...</option>';
        // Add resource options dynamically
    }, 1000);
}

function loadResourceInfo() {
    const resourceId = document.getElementById('resource_id').value;
    if (!resourceId) {
        document.getElementById('resource-info').style.display = 'none';
        return;
    }
    
    // This would fetch resource details via AJAX
    document.getElementById('resource-info').style.display = 'block';
    document.getElementById('resource-details').innerHTML = 'Loading resource information...';
}

function viewDeployment(deploymentId) {
    alert('View deployment details - ID: ' + deploymentId);
}

function updateDeployment(deploymentId) {
    alert('Update deployment - ID: ' + deploymentId);
}

function returnResource(deploymentId) {
    document.getElementById('return-deployment-id').value = deploymentId;
    document.getElementById('returnModal').style.display = 'block';
}

function closeReturnModal() {
    document.getElementById('returnModal').style.display = 'none';
}

function showBulkReturnModal() {
    alert('Bulk return functionality would be implemented here');
}

function filterDeployments() {
    const statusFilter = document.getElementById('statusFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    const rows = document.querySelectorAll('#deploymentsTable tbody tr');
    
    rows.forEach(row => {
        const status = row.querySelector('.status-badge').textContent.toLowerCase();
        const type = row.querySelector('.type-badge').textContent.toLowerCase();
        
        const statusMatch = !statusFilter || status === statusFilter;
        const typeMatch = !typeFilter || type === typeFilter;
        
        row.style.display = (statusMatch && typeMatch) ? '' : 'none';
    });
}

// Initialize DataTable
$(document).ready(function() {
    $('#deploymentsTable').DataTable({
        "order": [[ 5, "desc" ]],
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": [8] }
        ]
    });
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// ====================================
// REAL-TIME INTEGRATION
// ====================================
if (window.realtimeSystem) {
    // Listen for resource allocation updates
    window.realtimeSystem.registerCallback('onUpdate', (data) => {
        if (data.resource_update) {
            showResourceUpdateNotification(data.resource_update);
        }
    });
    
    console.log('✅ Real-time updates enabled for disaster-resources page');
}

function showResourceUpdateNotification(updateInfo) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        padding: 14px 18px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        min-width: 300px;
        animation: slideInRight 0.3s ease-out;
        font-family: 'Inter', sans-serif;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-box" style="font-size: 20px;"></i>
            <div style="flex: 1;">
                <strong style="display: block; margin-bottom: 4px;">Resource Updated</strong>
                <span style="font-size: 13px; opacity: 0.95;">Resource allocation has changed.</span>
            </div>
            <button onclick="location.reload()" style="
                background: white;
                color: #f59e0b;
                border: none;
                padding: 6px 12px;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                font-size: 12px;
            ">Refresh</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 8000);
}
</script>

<?php include 'includes/footer.php'; ?>