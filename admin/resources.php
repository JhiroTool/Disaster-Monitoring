<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

// Allow access to all authenticated users
$can_edit = true; // All users can manage resources in this system

$page_title = 'Resources Management';

// Get user session data
$user_id = $_SESSION['user_id'] ?? ($_SESSION['user_data']['user_id'] ?? 1);
$user_lgu_id = $_SESSION['lgu_id'] ?? ($_SESSION['user_data']['lgu_id'] ?? null);

// Get user's LGU from database if not in session
if ($user_lgu_id === null) {
    try {
        $lgu_stmt = $pdo->prepare("SELECT lgu_id FROM users WHERE user_id = ?");
        $lgu_stmt->execute([$user_id]);
        $user_data = $lgu_stmt->fetch();
        $user_lgu_id = $user_data['lgu_id'] ?? 1;
    } catch (Exception $e) {
        $user_lgu_id = 1;
    }
}

// Handle resource operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_resource'])) {
        $resource_name = sanitizeInput($_POST['resource_name']);
        $resource_type = sanitizeInput($_POST['resource_type']);
        $description = sanitizeInput($_POST['description']);
        $quantity_available = intval($_POST['quantity_available']);
        $unit = sanitizeInput($_POST['unit']);
        $location = sanitizeInput($_POST['location']);
        $contact_person = sanitizeInput($_POST['contact_person']);
        $contact_phone = sanitizeInput($_POST['contact_phone']);
        $availability_status = sanitizeInput($_POST['availability_status']);
        $owner_lgu_id = hasRole(['admin']) ? intval($_POST['owner_lgu_id']) : $user_lgu_id;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO resources (resource_name, resource_type, description, quantity_available, 
                                     unit, location, contact_person, contact_phone, availability_status, 
                                     owner_lgu_id, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $resource_name, $resource_type, $description, $quantity_available,
                $unit, $location, $contact_person, $contact_phone, $availability_status,
                $owner_lgu_id, $user_id
            ]);
            
            $success_message = "Resource created successfully.";
        } catch (Exception $e) {
            error_log("Resource creation error: " . $e->getMessage());
            $error_message = "Error creating resource. Please try again.";
        }
    }
    
    if (isset($_POST['update_resource'])) {
        $resource_id = intval($_POST['resource_id']);
        $resource_name = sanitizeInput($_POST['resource_name']);
        $resource_type = sanitizeInput($_POST['resource_type']);
        $description = sanitizeInput($_POST['description']);
        $quantity_available = intval($_POST['quantity_available']);
        $unit = sanitizeInput($_POST['unit']);
        $location = sanitizeInput($_POST['location']);
        $contact_person = sanitizeInput($_POST['contact_person']);
        $contact_phone = sanitizeInput($_POST['contact_phone']);
        $availability_status = sanitizeInput($_POST['availability_status']);
        
        try {
            // Check permissions
            $check_stmt = $pdo->prepare("
                SELECT owner_lgu_id FROM resources WHERE resource_id = ?
            ");
            $check_stmt->execute([$resource_id]);
            $resource = $check_stmt->fetch();
            
            if (!hasRole(['admin']) && $resource['owner_lgu_id'] != $user_lgu_id) {
                throw new Exception("Unauthorized access");
            }
            
            $stmt = $pdo->prepare("
                UPDATE resources SET 
                    resource_name = ?, resource_type = ?, description = ?, 
                    quantity_available = ?, unit = ?, location = ?, 
                    contact_person = ?, contact_phone = ?, availability_status = ?,
                    updated_at = NOW()
                WHERE resource_id = ?
            ");
            $stmt->execute([
                $resource_name, $resource_type, $description, $quantity_available,
                $unit, $location, $contact_person, $contact_phone, $availability_status,
                $resource_id
            ]);
            
            $success_message = "Resource updated successfully.";
        } catch (Exception $e) {
            error_log("Resource update error: " . $e->getMessage());
            $error_message = "Error updating resource. Please try again.";
        }
    }
    
    if (isset($_POST['delete_resource'])) {
        $resource_id = intval($_POST['resource_id']);
        
        try {
            // Check permissions
            $check_stmt = $pdo->prepare("
                SELECT owner_lgu_id FROM resources WHERE resource_id = ?
            ");
            $check_stmt->execute([$resource_id]);
            $resource = $check_stmt->fetch();
            
            if (!hasRole(['admin']) && $resource['owner_lgu_id'] != $user_lgu_id) {
                throw new Exception("Unauthorized access");
            }
            
            $stmt = $pdo->prepare("DELETE FROM resources WHERE resource_id = ?");
            $stmt->execute([$resource_id]);
            
            $success_message = "Resource deleted successfully.";
        } catch (Exception $e) {
            error_log("Resource deletion error: " . $e->getMessage());
            $error_message = "Error deleting resource.";
        }
    }
}

// Build query based on user permissions
$resource_query = "
    SELECT r.*, l.lgu_name, CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
           COUNT(DISTINCT dr.disaster_id) as deployment_count,
           COALESCE(SUM(CASE WHEN dr.status = 'active' THEN dr.quantity_deployed ELSE 0 END), 0) as currently_deployed
    FROM resources r
    LEFT JOIN lgus l ON r.owner_lgu_id = l.lgu_id
    LEFT JOIN users u ON r.created_by = u.user_id
    LEFT JOIN disaster_resource_deployments dr ON r.resource_id = dr.resource_id
";

$params = [];
if (!hasRole(['admin'])) {
    $resource_query .= " WHERE r.owner_lgu_id = ?";
    $params[] = $user_lgu_id;
}

$resource_query .= " GROUP BY r.resource_id ORDER BY r.created_at DESC";

try {
    $stmt = $pdo->prepare($resource_query);
    $stmt->execute($params);
    $resources = $stmt->fetchAll();
    
    // Fetch LGUs for admin dropdown
    if (hasRole(['admin'])) {
        $lgu_stmt = $pdo->query("SELECT lgu_id, lgu_name FROM lgus WHERE is_active = TRUE ORDER BY lgu_name");
        $lgus = $lgu_stmt->fetchAll();
    }
    
    // Fetch resource statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_resources,
            COUNT(CASE WHEN availability_status = 'available' THEN 1 END) as available_resources,
            COUNT(CASE WHEN availability_status = 'deployed' THEN 1 END) as deployed_resources,
            COUNT(CASE WHEN availability_status = 'maintenance' THEN 1 END) as maintenance_resources,
            COUNT(DISTINCT resource_type) as resource_types
        FROM resources
    ";
    
    if (!hasRole(['admin'])) {
        $stats_query .= " WHERE owner_lgu_id = ?";
    }
    
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute($params);
    $stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    error_log("Resources fetch error: " . $e->getMessage());
    $resources = [];
    $stats = ['total_resources' => 0, 'available_resources' => 0, 'deployed_resources' => 0, 'maintenance_resources' => 0, 'resource_types' => 0];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-boxes"></i> Resources Management</h2>
        <p>Manage emergency response resources and equipment</p>
    </div>
    <div class="page-actions">
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Resource
        </button>
        <button onclick="exportResources()" class="btn btn-secondary">
            <i class="fas fa-download"></i> Export
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

<!-- Resource Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-boxes"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['total_resources']; ?></div>
            <div class="stat-label">Total Resources</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['available_resources']; ?></div>
            <div class="stat-label">Available</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-shipping-fast"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['deployed_resources']; ?></div>
            <div class="stat-label">Deployed</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['resource_types']; ?></div>
            <div class="stat-label">Resource Types</div>
        </div>
    </div>
</div>

<!-- Resources Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>Resource Inventory</h3>
        <div class="filters">
            <select id="typeFilter" onchange="filterResources()">
                <option value="">All Types</option>
                <option value="vehicle">Vehicles</option>
                <option value="equipment">Equipment</option>
                <option value="medical">Medical Supplies</option>
                <option value="food">Food & Water</option>
                <option value="shelter">Shelter Materials</option>
                <option value="communication">Communication</option>
                <option value="other">Other</option>
            </select>
            <select id="statusFilter" onchange="filterResources()">
                <option value="">All Status</option>
                <option value="available">Available</option>
                <option value="deployed">Deployed</option>
                <option value="maintenance">Maintenance</option>
                <option value="unavailable">Unavailable</option>
            </select>
        </div>
    </div>
    <div class="card-content">
        <div class="table-container">
            <table id="resourcesTable">
                <thead>
                    <tr>
                        <th>Resource Name</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Owner LGU</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Deployments</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $resource): ?>
                        <tr>
                            <td>
                                <div class="resource-info">
                                    <strong><?php echo htmlspecialchars($resource['resource_name']); ?></strong>
                                    <?php if ($resource['description']): ?>
                                        <div class="resource-description">
                                            <?php echo htmlspecialchars(substr($resource['description'], 0, 80)) . '...'; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="type-badge type-<?php echo $resource['resource_type']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $resource['resource_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="quantity-info">
                                    <span class="quantity-available">
                                        <?php echo $resource['quantity_available'] - $resource['currently_deployed']; ?>
                                    </span>
                                    /
                                    <span class="quantity-total"><?php echo $resource['quantity_available']; ?></span>
                                    <small><?php echo htmlspecialchars($resource['unit']); ?></small>
                                    <?php if ($resource['currently_deployed'] > 0): ?>
                                        <div class="deployed-info">
                                            <small class="text-warning">
                                                <?php echo $resource['currently_deployed']; ?> deployed
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($resource['location']); ?></td>
                            <td><?php echo htmlspecialchars($resource['lgu_name']); ?></td>
                            <td>
                                <div class="contact-info">
                                    <div><?php echo htmlspecialchars($resource['contact_person']); ?></div>
                                    <small><?php echo htmlspecialchars($resource['contact_phone']); ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $resource['availability_status']; ?>">
                                    <?php echo ucfirst($resource['availability_status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="deployment-info">
                                    <span class="deployment-count"><?php echo $resource['deployment_count']; ?></span>
                                    <small>times</small>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewResource(<?php echo $resource['resource_id']; ?>)" 
                                            class="btn btn-xs btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (hasRole(['admin']) || $resource['owner_lgu_id'] == $user_lgu_id): ?>
                                        <button onclick="editResource(<?php echo $resource['resource_id']; ?>)" 
                                                class="btn btn-xs btn-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteResource(<?php echo $resource['resource_id']; ?>)" 
                                                class="btn btn-xs btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
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

<!-- Create Resource Modal -->
<div id="createResourceModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Add New Resource</h3>
            <button class="modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="resource_name">Resource Name *</label>
                    <input type="text" name="resource_name" id="resource_name" required>
                </div>
                <div class="form-group">
                    <label for="resource_type">Type *</label>
                    <select name="resource_type" id="resource_type" required>
                        <option value="">Select Type</option>
                        <option value="vehicle">Vehicle</option>
                        <option value="equipment">Equipment</option>
                        <option value="medical">Medical Supplies</option>
                        <option value="food">Food & Water</option>
                        <option value="shelter">Shelter Materials</option>
                        <option value="communication">Communication</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="3"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="quantity_available">Quantity Available *</label>
                    <input type="number" name="quantity_available" id="quantity_available" min="0" required>
                </div>
                <div class="form-group">
                    <label for="unit">Unit *</label>
                    <input type="text" name="unit" id="unit" placeholder="e.g., pieces, liters, boxes" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" name="location" id="location" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="contact_person">Contact Person *</label>
                    <input type="text" name="contact_person" id="contact_person" required>
                </div>
                <div class="form-group">
                    <label for="contact_phone">Contact Phone *</label>
                    <input type="tel" name="contact_phone" id="contact_phone" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="availability_status">Availability Status *</label>
                    <select name="availability_status" id="availability_status" required>
                        <option value="available">Available</option>
                        <option value="maintenance">Under Maintenance</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
                <?php if (hasRole(['admin'])): ?>
                    <div class="form-group">
                        <label for="owner_lgu_id">Owner LGU *</label>
                        <select name="owner_lgu_id" id="owner_lgu_id" required>
                            <option value="">Select LGU</option>
                            <?php foreach ($lgus as $lgu): ?>
                                <option value="<?php echo $lgu['lgu_id']; ?>">
                                    <?php echo htmlspecialchars($lgu['lgu_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeCreateModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="create_resource" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Resource
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Resource</h3>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="resource_id" id="delete-resource-id">
            
            <p>Are you sure you want to delete this resource? This action cannot be undone.</p>
            
            <div class="form-actions">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="delete_resource" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.resource-info strong {
    color: var(--text-color);
    display: block;
    margin-bottom: 2px;
}

.resource-description {
    font-size: 12px;
    color: var(--text-muted);
}

.quantity-info {
    text-align: center;
}

.quantity-available {
    font-weight: 700;
    color: var(--success-color);
}

.quantity-total {
    color: var(--text-color);
}

.deployed-info {
    margin-top: 4px;
}

.contact-info div {
    font-weight: 600;
    color: var(--text-color);
}

.contact-info small {
    color: var(--text-muted);
}

.deployment-info {
    text-align: center;
}

.deployment-count {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-color);
}

.type-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.type-vehicle { background: #e3f2fd; color: #1976d2; }
.type-equipment { background: #f3e5f5; color: #7b1fa2; }
.type-medical { background: #ffebee; color: #d32f2f; }
.type-food { background: #e8f5e8; color: #2e7d32; }
.type-shelter { background: #fff3e0; color: #f57c00; }
.type-communication { background: #e0f2f1; color: #00695c; }
.type-other { background: #f5f5f5; color: #616161; }

.status-available { background: #e8f5e8; color: #2e7d32; }
.status-deployed { background: #fff3e0; color: #f57c00; }
.status-maintenance { background: #fff8e1; color: #f9a825; }
.status-unavailable { background: #ffebee; color: #d32f2f; }
</style>

<script>
function showCreateModal() {
    document.getElementById('createResourceModal').style.display = 'block';
}

function closeCreateModal() {
    document.getElementById('createResourceModal').style.display = 'none';
    document.querySelector('#createResourceModal form').reset();
}

function viewResource(resourceId) {
    // Implementation for viewing resource details
    alert('View resource details - ID: ' + resourceId);
}

function editResource(resourceId) {
    // Implementation for editing resource
    alert('Edit resource - ID: ' + resourceId);
}

function deleteResource(resourceId) {
    document.getElementById('delete-resource-id').value = resourceId;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function filterResources() {
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#resourcesTable tbody tr');
    
    rows.forEach(row => {
        const type = row.querySelector('.type-badge').textContent.toLowerCase().replace(/\s+/g, '_');
        const status = row.querySelector('.status-badge').textContent.toLowerCase();
        
        const typeMatch = !typeFilter || type.includes(typeFilter);
        const statusMatch = !statusFilter || status === statusFilter;
        
        row.style.display = (typeMatch && statusMatch) ? '' : 'none';
    });
}

function exportResources() {
    // Implementation for exporting resources
    alert('Export resources functionality would be implemented here');
}

// Initialize DataTable
$(document).ready(function() {
    // Check if DataTable is already initialized
    if (!$.fn.DataTable.isDataTable('#resourcesTable')) {
        $('#resourcesTable').DataTable({
            "order": [[ 8, "desc" ]],
            "pageLength": 25,
            "columnDefs": [
                { "orderable": false, "targets": [9] }
            ]
        });
    }
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>