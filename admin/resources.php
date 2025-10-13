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

<!-- View Toggle and Filters -->
<div class="view-controls-card">
    <div class="view-toggle">
        <button class="view-btn active" data-view="cards" onclick="switchView('cards')">
            <i class="fas fa-th-large"></i>
            <span>Cards</span>
        </button>
        <button class="view-btn" data-view="table" onclick="switchView('table')">
            <i class="fas fa-table"></i>
            <span>Table</span>
        </button>
    </div>
    
    <div class="filters-section">
        <div class="filter-item">
            <i class="fas fa-layer-group"></i>
            <select id="typeFilter" onchange="filterResources()">
                <option value="">All Types</option>
                <option value="vehicle">🚗 Vehicles</option>
                <option value="equipment">🔧 Equipment</option>
                <option value="medical">💊 Medical Supplies</option>
                <option value="food">🍱 Food & Water</option>
                <option value="shelter">🏠 Shelter Materials</option>
                <option value="communication">📡 Communication</option>
                <option value="other">📦 Other</option>
            </select>
        </div>
        <div class="filter-item">
            <i class="fas fa-circle"></i>
            <select id="statusFilter" onchange="filterResources()">
                <option value="">All Status</option>
                <option value="available">✅ Available</option>
                <option value="deployed">🚀 Deployed</option>
                <option value="maintenance">🔧 Maintenance</option>
                <option value="unavailable">❌ Unavailable</option>
            </select>
        </div>
        <div class="filter-item">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search resources..." onkeyup="searchResources()">
        </div>
    </div>
</div>

<!-- Cards View -->
<div id="cardsView" class="resources-grid">
    <?php foreach ($resources as $resource): ?>
        <div class="resource-card" data-type="<?php echo $resource['resource_type']; ?>" 
             data-status="<?php echo $resource['availability_status']; ?>">
            <div class="resource-card-header type-<?php echo $resource['resource_type']; ?>">
                <div class="resource-icon">
                    <?php
                    $icons = [
                        'vehicle' => 'fa-truck',
                        'equipment' => 'fa-tools',
                        'medical' => 'fa-medkit',
                        'food' => 'fa-utensils',
                        'shelter' => 'fa-home',
                        'communication' => 'fa-broadcast-tower',
                        'other' => 'fa-box'
                    ];
                    $icon = $icons[$resource['resource_type']] ?? 'fa-box';
                    ?>
                    <i class="fas <?php echo $icon; ?>"></i>
                </div>
                <span class="status-indicator status-<?php echo $resource['availability_status']; ?>">
                    <?php echo ucfirst($resource['availability_status']); ?>
                </span>
            </div>
            
            <div class="resource-card-body">
                <h3 class="resource-title"><?php echo htmlspecialchars($resource['resource_name']); ?></h3>
                
                <?php if ($resource['description']): ?>
                    <p class="resource-desc">
                        <?php echo htmlspecialchars(substr($resource['description'], 0, 100)) . (strlen($resource['description']) > 100 ? '...' : ''); ?>
                    </p>
                <?php endif; ?>
                
                <div class="resource-details">
                    <div class="detail-item">
                        <i class="fas fa-cube"></i>
                        <div class="detail-content">
                            <span class="detail-label">Quantity</span>
                            <span class="detail-value">
                                <strong><?php echo $resource['quantity_available'] - $resource['currently_deployed']; ?></strong>
                                / <?php echo $resource['quantity_available']; ?> <?php echo htmlspecialchars($resource['unit']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="detail-content">
                            <span class="detail-label">Location</span>
                            <span class="detail-value"><?php echo htmlspecialchars($resource['location']); ?></span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-building"></i>
                        <div class="detail-content">
                            <span class="detail-label">Owner</span>
                            <span class="detail-value"><?php echo htmlspecialchars($resource['lgu_name']); ?></span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-user"></i>
                        <div class="detail-content">
                            <span class="detail-label">Contact</span>
                            <span class="detail-value"><?php echo htmlspecialchars($resource['contact_person']); ?></span>
                            <span class="detail-sub"><?php echo htmlspecialchars($resource['contact_phone']); ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if ($resource['currently_deployed'] > 0): ?>
                    <div class="deployment-badge">
                        <i class="fas fa-shipping-fast"></i>
                        <?php echo $resource['currently_deployed']; ?> currently deployed
                    </div>
                <?php endif; ?>
                
                <div class="deployment-stats">
                    <i class="fas fa-history"></i>
                    Deployed <?php echo $resource['deployment_count']; ?> time<?php echo $resource['deployment_count'] != 1 ? 's' : ''; ?>
                </div>
            </div>
            
            <div class="resource-card-footer">
                <button onclick="viewResourceModal(<?php echo htmlspecialchars(json_encode($resource), ENT_QUOTES, 'UTF-8'); ?>)" 
                        class="btn-card btn-view">
                    <i class="fas fa-eye"></i> View
                </button>
                <?php if (hasRole(['admin']) || $resource['owner_lgu_id'] == $user_lgu_id): ?>
                    <button onclick="editResourceModal(<?php echo htmlspecialchars(json_encode($resource), ENT_QUOTES, 'UTF-8'); ?>)" 
                            class="btn-card btn-edit">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button onclick="deleteResource(<?php echo $resource['resource_id']; ?>)" 
                            class="btn-card btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Table View (Hidden by default) -->
<div id="tableView" class="dashboard-card" style="display: none;">
    <div class="card-header">
        <h3>Resource Inventory</h3>
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
/* ====================================
   MODERN RESOURCES PAGE STYLING
   ==================================== */

/* View Controls */
.view-controls-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 20px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.view-toggle {
    display: flex;
    background: #f3f4f6;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
}

.view-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.3s ease;
    font-size: 14px;
}

.view-btn i {
    font-size: 16px;
}

.view-btn.active {
    background: white;
    color: #3b82f6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-btn:hover:not(.active) {
    color: #374151;
}

.filters-section {
    display: flex;
    gap: 15px;
    flex: 1;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.filter-item {
    position: relative;
    display: flex;
    align-items: center;
    background: #f9fafb;
    border-radius: 10px;
    padding: 0 12px;
    gap: 10px;
    transition: all 0.3s ease;
}

.filter-item:focus-within {
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-item i {
    color: #9ca3af;
    font-size: 14px;
}

.filter-item select,
.filter-item input {
    border: none;
    background: transparent;
    padding: 10px 8px;
    font-size: 14px;
    color: #374151;
    font-weight: 500;
    outline: none;
    min-width: 160px;
}

.filter-item input {
    min-width: 200px;
}

/* Resources Grid (Cards View) */
.resources-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

/* Resource Card */
.resource-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.resource-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-4px);
}

.resource-card-header {
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.resource-card-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
}

.resource-card-header.type-vehicle {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.resource-card-header.type-equipment {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.resource-card-header.type-medical {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.resource-card-header.type-food {
    background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
}

.resource-card-header.type-shelter {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
}

.resource-card-header.type-communication {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
}

.resource-card-header.type-other {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
}

.resource-icon {
    width: 50px;
    height: 50px;
    background: rgba(255,255,255,0.25);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.resource-icon i {
    font-size: 24px;
    color: white;
}

.status-indicator {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: rgba(255,255,255,0.3);
    color: white;
    backdrop-filter: blur(10px);
}

.resource-card-body {
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.resource-title {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    line-height: 1.3;
}

.resource-desc {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.6;
    margin: 0;
}

.resource-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.detail-item i {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 14px;
    margin-top: 2px;
    flex-shrink: 0;
}

.detail-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.detail-label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.detail-value {
    font-size: 14px;
    color: #374151;
    font-weight: 500;
}

.detail-value strong {
    color: #10b981;
    font-size: 16px;
}

.detail-sub {
    font-size: 12px;
    color: #9ca3af;
}

.deployment-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: #fef3c7;
    color: #92400e;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 8px;
}

.deployment-badge i {
    font-size: 14px;
}

.deployment-stats {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #f3f4f6;
    border-radius: 8px;
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
    margin-top: auto;
}

.deployment-stats i {
    color: #9ca3af;
}

.resource-card-footer {
    display: flex;
    gap: 8px;
    padding: 16px;
    background: #f9fafb;
    border-top: 1px solid #f3f4f6;
}

.btn-card {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-view {
    background: #eff6ff;
    color: #1e40af;
}

.btn-view:hover {
    background: #dbeafe;
    transform: translateY(-1px);
}

.btn-edit {
    background: #f0fdf4;
    color: #166534;
}

.btn-edit:hover {
    background: #dcfce7;
    transform: translateY(-1px);
}

.btn-delete {
    background: #fef2f2;
    color: #991b1b;
}

.btn-delete:hover {
    background: #fee2e2;
    transform: translateY(-1px);
}

/* Table View Styles */
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

/* Mobile Responsive */
@media (max-width: 768px) {
    .resources-grid {
        grid-template-columns: 1fr;
    }
    
    .view-controls-card {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters-section {
        justify-content: stretch;
        flex-direction: column;
    }
    
    .filter-item {
        width: 100%;
    }
    
    .filter-item select,
    .filter-item input {
        width: 100%;
    }
}

/* Empty State */
.empty-resources {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.empty-resources i {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 20px;
}

.empty-resources h3 {
    font-size: 20px;
    color: #374151;
    margin: 0 0 8px 0;
}

.empty-resources p {
    color: #9ca3af;
    margin: 0;
}
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

// ====================================
// REAL-TIME INTEGRATION
// ====================================
if (window.realtimeSystem) {
    // Listen for resource inventory updates
    window.realtimeSystem.registerCallback('onUpdate', (data) => {
        if (data.resource_update || data.stats?.resource_changes) {
            showResourceInventoryUpdate();
        }
    });
    
    console.log('✅ Real-time updates enabled for resources page');
}

function showResourceInventoryUpdate() {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 12px 18px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: bounceIn 0.4s ease-out;
        display: flex;
        align-items: center;
        gap: 12px;
    `;
    
    notification.innerHTML = `
        <i class="fas fa-warehouse" style="font-size: 18px;"></i>
        <span style="font-size: 14px; font-weight: 500;">Resource inventory updated</span>
        <button onclick="location.reload()" style="
            background: white;
            color: #10b981;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 12px;
            margin-left: 8px;
        ">Refresh</button>
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 7000);
}

// Add bounce animation
if (!document.querySelector('#resources-animations')) {
    const style = document.createElement('style');
    style.id = 'resources-animations';
    style.textContent = `
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
}

// ====================================
// VIEW SWITCHING AND FILTERING
// ====================================
let currentView = 'cards';

function switchView(view) {
    currentView = view;
    
    // Toggle view buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    if (view === 'cards') {
        document.getElementById('cardsView').style.display = 'grid';
        document.getElementById('tableView').style.display = 'none';
        document.querySelector('.view-btn[onclick="switchView(\'cards\')"]').classList.add('active');
    } else {
        document.getElementById('cardsView').style.display = 'none';
        document.getElementById('tableView').style.display = 'block';
        document.querySelector('.view-btn[onclick="switchView(\'table\')"]').classList.add('active');
    }
}

// Filter Resources (for card view)
function filterResources() {
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    document.querySelectorAll('.resource-card').forEach(card => {
        const cardType = card.dataset.type;
        const cardStatus = card.dataset.status;
        const cardText = card.textContent.toLowerCase();
        
        let show = true;
        
        if (typeFilter && cardType !== typeFilter) show = false;
        if (statusFilter && cardStatus !== statusFilter) show = false;
        if (searchTerm && !cardText.includes(searchTerm)) show = false;
        
        card.style.display = show ? 'flex' : 'none';
    });
}

// View Resource Modal
function viewResourceModal(resourceId, resourceData) {
    try {
        const resource = typeof resourceData === 'string' ? JSON.parse(resourceData) : resourceData;
        
        const deployments = resource.deployments || [];
        let deploymentsHtml = '';
        
        if (deployments.length > 0) {
            deploymentsHtml = `
                <div class="deployment-list" style="margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 10px;">
                    <h5 style="margin: 0 0 15px 0; color: #374151;"><i class="fas fa-map-marked-alt"></i> Current Deployments</h5>
                    ${deployments.map(dep => `
                        <div class="deployment-item" style="padding: 10px; background: white; border-radius: 8px; margin-bottom: 10px;">
                            <strong style="color: #111827;">${dep.disaster_name}</strong>
                            <div style="margin-top: 5px;">Quantity: <span class="badge badge-info">${dep.quantity_deployed}</span></div>
                            <div style="color: #6b7280; font-size: 12px;">Deployed: ${new Date(dep.deployment_date).toLocaleDateString()}</div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        $('#viewResourceModal .modal-body').html(`
            <div class="resource-view-content">
                <div class="resource-view-header" style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid #f3f4f6;">
                    <div class="resource-view-icon type-${resource.resource_type}" style="width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: white; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas ${getResourceIcon(resource.resource_type)}"></i>
                    </div>
                    <div class="resource-view-title" style="flex: 1;">
                        <h4 style="margin: 0 0 8px 0; color: #111827; font-size: 20px;">${resource.resource_name}</h4>
                        <span class="badge status-${resource.availability_status}" style="padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">${resource.availability_status}</span>
                    </div>
                </div>
                
                <div class="resource-view-details">
                    <div class="detail-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; color: #6b7280; font-size: 12px; margin-bottom: 8px;"><i class="fas fa-align-left"></i> Description</label>
                        <p style="margin: 0; color: #374151; line-height: 1.6;">${resource.description || 'No description available'}</p>
                    </div>
                    
                    <div class="detail-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="detail-group">
                            <label style="display: block; font-weight: 600; color: #6b7280; font-size: 12px; margin-bottom: 8px;"><i class="fas fa-tag"></i> Type</label>
                            <p style="margin: 0; color: #374151; font-weight: 500;">${resource.resource_type}</p>
                        </div>
                        <div class="detail-group">
                            <label style="display: block; font-weight: 600; color: #6b7280; font-size: 12px; margin-bottom: 8px;"><i class="fas fa-cubes"></i> Quantity</label>
                            <p style="margin: 0; color: #374151; font-weight: 500;"><strong style="color: #10b981; font-size: 18px;">${resource.quantity_available}/${resource.quantity_total}</strong> Available</p>
                        </div>
                    </div>
                    
                    <div class="detail-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="detail-group">
                            <label style="display: block; font-weight: 600; color: #6b7280; font-size: 12px; margin-bottom: 8px;"><i class="fas fa-map-marker-alt"></i> Location</label>
                            <p style="margin: 0; color: #374151;">${resource.location || 'Not specified'}</p>
                        </div>
                        <div class="detail-group">
                            <label style="display: block; font-weight: 600; color: #6b7280; font-size: 12px; margin-bottom: 8px;"><i class="fas fa-user"></i> Owner/Manager</label>
                            <p style="margin: 0; color: #374151;">${resource.owner_manager || 'Not specified'}</p>
                        </div>
                    </div>
                    
                    <div class="detail-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; color: #6b7280; font-size: 12px; margin-bottom: 8px;"><i class="fas fa-phone"></i> Contact Information</label>
                        <p style="margin: 0; color: #374151;">${resource.contact_info || 'Not specified'}</p>
                    </div>
                    
                    ${deploymentsHtml}
                </div>
            </div>
        `);
        
        $('#viewResourceModal').modal('show');
    } catch (error) {
        console.error('Error viewing resource:', error);
        alert('Error loading resource details');
    }
}

// Edit Resource Modal
function editResourceModal(resourceId, resourceData) {
    try {
        const resource = typeof resourceData === 'string' ? JSON.parse(resourceData) : resourceData;
        
        $('#editResourceModal #edit_resource_id').val(resource.resource_id);
        $('#editResourceModal #edit_resource_name').val(resource.resource_name);
        $('#editResourceModal #edit_resource_type').val(resource.resource_type);
        $('#editResourceModal #edit_description').val(resource.description);
        $('#editResourceModal #edit_quantity_total').val(resource.quantity_total);
        $('#editResourceModal #edit_quantity_available').val(resource.quantity_available);
        $('#editResourceModal #edit_location').val(resource.location);
        $('#editResourceModal #edit_owner_manager').val(resource.owner_manager);
        $('#editResourceModal #edit_contact_info').val(resource.contact_info);
        $('#editResourceModal #edit_availability_status').val(resource.availability_status);
        
        $('#editResourceModal').modal('show');
    } catch (error) {
        console.error('Error editing resource:', error);
        alert('Error loading resource for editing');
    }
}

// Get icon for resource type
function getResourceIcon(type) {
    const icons = {
        'vehicle': 'fa-truck',
        'equipment': 'fa-tools',
        'medical': 'fa-medkit',
        'food': 'fa-utensils',
        'shelter': 'fa-home',
        'communication': 'fa-satellite-dish',
        'other': 'fa-box'
    };
    return icons[type] || 'fa-box';
}
</script>

<?php include 'includes/footer.php'; ?>