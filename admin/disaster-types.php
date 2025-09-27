<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

// Allow access to admins and LGU users for viewing, restrict editing to admins only
$can_edit = isAdmin();
$can_view = true; // All authenticated users can view

$page_title = 'Disaster Types Management';

// Handle disaster type operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    if (isset($_POST['create_type'])) {
        $type_name = sanitizeInput($_POST['type_name']);
        $description = sanitizeInput($_POST['description']);
        $category = sanitizeInput($_POST['category']);
        $severity_weight = floatval($_POST['severity_weight']);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO disaster_types (type_name, description, category, severity_weight)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $type_name, $description, $category, $severity_weight
            ]);
            
            $success_message = "Disaster type created successfully.";
        } catch (Exception $e) {
            error_log("Disaster type creation error: " . $e->getMessage());
            $error_message = "Error creating disaster type. Please try again.";
        }
    }
    
    if (isset($_POST['update_type'])) {
        $type_id = intval($_POST['type_id']);
        $type_name = sanitizeInput($_POST['type_name']);
        $description = sanitizeInput($_POST['description']);
        $category = sanitizeInput($_POST['category']);
        $severity_weight = floatval($_POST['severity_weight']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE disaster_types SET 
                    type_name = ?, description = ?, category = ?, severity_weight = ?,
                    is_active = ?, updated_at = NOW()
                WHERE type_id = ?
            ");
            $stmt->execute([
                $type_name, $description, $category, $severity_weight, $is_active, $type_id
            ]);
            
            $success_message = "Disaster type updated successfully.";
        } catch (Exception $e) {
            error_log("Disaster type update error: " . $e->getMessage());
            $error_message = "Error updating disaster type.";
        }
    }
    
    if (isset($_POST['delete_type'])) {
        $type_id = intval($_POST['type_id']);
        
        try {
            // Check if type is used in any disasters
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM disasters WHERE disaster_type = (SELECT type_name FROM disaster_types WHERE type_id = ?)");
            $check_stmt->execute([$type_id]);
            $usage_count = $check_stmt->fetchColumn();
            
            if ($usage_count > 0) {
                throw new Exception("Cannot delete disaster type. It is currently used in $usage_count disaster reports.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM disaster_types WHERE type_id = ?");
            $stmt->execute([$type_id]);
            
            $success_message = "Disaster type deleted successfully.";
        } catch (Exception $e) {
            error_log("Disaster type deletion error: " . $e->getMessage());
            $error_message = $e->getMessage();
        }
    }
}

// Fetch disaster types with statistics
try {
    $stmt = $pdo->query("
        SELECT dt.*, 
               COUNT(d.disaster_id) as total_disasters,
               COUNT(CASE WHEN d.status = 'resolved' THEN 1 END) as resolved_disasters,
               AVG(CASE 
                   WHEN d.resolved_at IS NOT NULL 
                   THEN TIMESTAMPDIFF(HOUR, d.reported_at, d.resolved_at) 
               END) as avg_resolution_hours,
               MAX(d.reported_at) as last_occurrence
        FROM disaster_types dt
        LEFT JOIN disasters d ON dt.type_id = d.type_id
        GROUP BY dt.type_id
        ORDER BY dt.severity_weight DESC, dt.type_name
    ");
    $disaster_types = $stmt->fetchAll();
    
    // Get overall statistics
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_types,
            COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_types,
            AVG(severity_weight) as avg_severity,
            COUNT(CASE WHEN category = 'natural' THEN 1 END) as natural_types
        FROM disaster_types
    ");
    $stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    error_log("Disaster types fetch error: " . $e->getMessage());
    $disaster_types = [];
    $stats = ['total_types' => 0, 'active_types' => 0, 'avg_severity' => 0, 'natural_types' => 0];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-exclamation-triangle"></i> Disaster Types Management</h2>
        <p>Configure disaster types and response protocols</p>
    </div>
    <div class="page-actions">
        <?php if ($can_edit): ?>
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Disaster Type
        </button>
        <?php endif; ?>
        <button onclick="exportTypes()" class="btn btn-secondary">
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

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-list"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['total_types']; ?></div>
            <div class="stat-label">Total Types</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['active_types']; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-weight"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo number_format($stats['avg_severity'], 2); ?></div>
            <div class="stat-label">Avg Severity</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-leaf"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['natural_types']; ?></div>
            <div class="stat-label">Natural Types</div>
        </div>
    </div>
</div>

<!-- Disaster Types Grid -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>Disaster Types</h3>
        <div class="filters">
            <select id="categoryFilter" onchange="filterTypes()">
                <option value="">All Categories</option>
                <option value="natural">Natural</option>
                <option value="man-made">Man-made</option>
                <option value="technological">Technological</option>
                <option value="biological">Biological</option>
            </select>
            <select id="statusFilter" onchange="filterTypes()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>
    <div class="card-content">
        <div class="types-grid">
            <?php foreach ($disaster_types as $type): ?>
                <div class="type-card">
                    <div class="type-header">
                        <div class="type-info">
                            <h4><?php echo htmlspecialchars($type['type_name']); ?></h4>
                            <div class="type-badges">
                                <span class="severity-badge severity-<?php echo $type['category']; ?>">
                                    <?php echo ucfirst($type['category']); ?>
                                </span>
                                <span class="weight-badge">
                                    Weight: <?php echo $type['severity_weight']; ?>
                                </span>
                                <span class="status-badge status-<?php echo $type['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $type['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="type-actions">
                            <button onclick="viewType(<?php echo $type['type_id']; ?>)" 
                                    class="btn btn-xs btn-info" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($can_edit): ?>
                            <button onclick="editType(<?php echo $type['type_id']; ?>)" 
                                    class="btn btn-xs btn-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteType(<?php echo $type['type_id']; ?>)" 
                                    class="btn btn-xs btn-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="type-description">
                        <?php echo htmlspecialchars(substr($type['description'], 0, 120)) . '...'; ?>
                    </div>
                    
                    <div class="type-details">
                        <div class="detail-item">
                            <i class="fas fa-tag"></i>
                            <span>Category: <?php echo ucfirst($type['category']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-weight-hanging"></i>
                            <span>Severity Weight: <?php echo $type['severity_weight']; ?></span>
                        </div>
                        <?php if ($type['last_occurrence']): ?>
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Last: <?php echo date('M d, Y', strtotime($type['last_occurrence'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="type-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $type['total_disasters']; ?></span>
                            <span class="stat-label">Total Cases</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $type['resolved_disasters']; ?></span>
                            <span class="stat-label">Resolved</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">
                                <?php 
                                if ($type['total_disasters'] > 0) {
                                    echo number_format(($type['resolved_disasters'] / $type['total_disasters']) * 100, 1) . '%';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </span>
                            <span class="stat-label">Success Rate</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">
                                <?php echo $type['avg_resolution_hours'] ? number_format($type['avg_resolution_hours'], 1) . 'h' : 'N/A'; ?>
                            </span>
                            <span class="stat-label">Avg Resolution</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Create Disaster Type Modal -->
<div id="createTypeModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Add New Disaster Type</h3>
            <button class="modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="type_name">Type Name *</label>
                    <input type="text" name="type_name" id="type_name" required>
                </div>
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select name="category" id="category" required>
                        <option value="natural">Natural</option>
                        <option value="man-made">Man-made</option>
                        <option value="technological">Technological</option>
                        <option value="biological">Biological</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="severity_weight">Severity Weight *</label>
                <input type="number" name="severity_weight" id="severity_weight" 
                       min="0.1" max="5.0" step="0.1" value="1.0" required>
                <small class="form-help">Weight factor for severity calculation (0.1 to 5.0)</small>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeCreateModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="create_type" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Disaster Type
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Disaster Type</h3>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="type_id" id="delete-type-id">
            
            <p>Are you sure you want to delete this disaster type? This action cannot be undone.</p>
            <p><strong>Warning:</strong> This will fail if the type is currently used in any disaster reports.</p>
            
            <div class="form-actions">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="delete_type" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.type-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.type-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.type-header {
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.type-info h4 {
    margin: 0 0 8px 0;
    color: var(--text-color);
    font-size: 18px;
}

.type-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.severity-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.severity-natural { background: #e8f5e8; color: #2e7d32; }
.severity-man-made { background: #fff3e0; color: #f57c00; }
.severity-technological { background: #fff8e1; color: #f9a825; }
.severity-biological { background: #ffebee; color: #d32f2f; }

.weight-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    background: #f0f0f0;
    color: #666;
}

.type-actions {
    display: flex;
    gap: 5px;
}

.type-description {
    padding: 20px;
    color: var(--text-color);
    line-height: 1.5;
}

.type-details {
    padding: 0 20px 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: var(--text-muted);
    font-size: 14px;
}

.detail-item i {
    width: 16px;
}

.type-stats {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid var(--border-color);
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.type-stats .stat-item {
    text-align: center;
}

.type-stats .stat-number {
    display: block;
    font-size: 16px;
    font-weight: 700;
    color: var(--text-color);
}

.type-stats .stat-label {
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 768px) {
    .types-grid {
        grid-template-columns: 1fr;
    }
    
    .type-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function showCreateModal() {
    document.getElementById('createTypeModal').style.display = 'block';
}

function closeCreateModal() {
    document.getElementById('createTypeModal').style.display = 'none';
    document.querySelector('#createTypeModal form').reset();
}

function viewType(typeId) {
    // Implementation for viewing type details
    alert('View disaster type details - ID: ' + typeId);
}

function editType(typeId) {
    // Implementation for editing type
    alert('Edit disaster type - ID: ' + typeId);
}

function deleteType(typeId) {
    document.getElementById('delete-type-id').value = typeId;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function filterTypes() {
    const categoryFilter = document.getElementById('categoryFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const cards = document.querySelectorAll('.type-card');
    
    cards.forEach(card => {
        const category = card.querySelector('.severity-badge').textContent.toLowerCase();
        const status = card.querySelector('.status-badge').textContent.toLowerCase();
        
        const categoryMatch = !categoryFilter || category === categoryFilter;
        const statusMatch = !statusFilter || status === statusFilter;
        
        card.style.display = (categoryMatch && statusMatch) ? '' : 'none';
    });
}

function exportTypes() {
    // Implementation for exporting disaster types
    alert('Export disaster types functionality would be implemented here');
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>