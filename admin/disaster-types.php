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
            <button class="modal-close" type="button" onclick="closeCreateModal()" aria-label="Close create disaster type modal">&times;</button>
        </div>
        <form method="POST" class="modal-form" id="create-type-form">
            <div class="modal-body">
                <div class="modal-intro">
                    <div class="modal-intro-icon">
                        <i class="fas fa-mountain"></i>
                    </div>
                    <div class="modal-intro-content">
                        <h4>Define a new disaster type</h4>
                        <p>Give the hazard a clear name, categorize it, and add guidance responders can follow.</p>
                    </div>
                </div>

                <div class="modal-grid">
                    <div class="form-group">
                        <label for="type_name">Type Name *</label>
                        <input type="text" name="type_name" id="type_name" placeholder="e.g., Typhoon, Flash Flood" required>
                        <small class="form-help">Use concise, recognizable terms citizens and LGUs already use.</small>
                    </div>
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select name="category" id="category" required>
                            <option value="natural">Natural</option>
                            <option value="man-made">Man-made</option>
                            <option value="technological">Technological</option>
                            <option value="biological">Biological</option>
                        </select>
                        <small class="form-help">Categories help dashboards group related incidents.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="4" placeholder="Add triggers, visual cues, and recommended response notes."></textarea>
                    <small class="form-help">Summarize what responders should watch for and how to escalate.</small>
                </div>

                <div class="severity-control">
                    <div class="severity-header">
                        <div class="severity-labels">
                            <label for="severity_weight_slider">Severity Weight *</label>
                            <span class="severity-help">Higher weights push this type to the top of priority queues.</span>
                        </div>
                        <span class="severity-indicator" data-severity-label>
                            <i class="fas fa-chart-line"></i>
                            <span data-severity-text>Low impact</span>
                        </span>
                    </div>

                    <div class="severity-meter">
                        <div class="severity-meter-fill" data-severity-meter></div>
                    </div>

                    <div class="severity-inputs">
                        <input type="range" id="severity_weight_slider" min="0.1" max="5.0" step="0.1" value="1.0" class="severity-slider" aria-label="Severity weight slider" data-default="1.0">
                        <input type="number" name="severity_weight" id="severity_weight" min="0.1" max="5.0" step="0.1" value="1.0" class="severity-number" data-default="1.0" required>
                    </div>

                    <small class="form-help">Scale from <strong>0.1 (minor)</strong> to <strong>5.0 (critical)</strong>. Severity influences automated alerts.</small>
                </div>

                <div class="modal-hints">
                    <div class="hint-item">
                        <i class="fas fa-lightbulb"></i>
                        <span>Stick to one hazard per type so analytics stay accurate.</span>
                    </div>
                    <div class="hint-item">
                        <i class="fas fa-sitemap"></i>
                        <span>Align severity weights with your LGU’s response protocols.</span>
                    </div>
                </div>
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
            <button class="modal-close" type="button" onclick="closeDeleteModal()" aria-label="Close delete modal">&times;</button>
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

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(6px);
    z-index: 2000;
    padding: clamp(1.5rem, 3vw, 3rem);
    overflow-y: auto;
    align-items: center;
    justify-content: center;
}

.modal.open,
.modal[style*="display: block"],
.modal[style*="display:block"],
.modal[style*="display: flex"],
.modal[style*="display:flex"] {
    display: flex !important;
}

.modal-content {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
    width: min(540px, 100%);
}

.modal.open .modal-content {
    animation: modal-fade-in 0.32s ease forwards;
}

.modal-content.modal-lg {
    width: min(860px, 100%);
}

@keyframes modal-fade-in {
    from {
        transform: translateY(24px) scale(0.98);
        opacity: 0;
    }
    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 32px;
    border-radius: 20px 20px 0 0;
    background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
    color: #ffffff;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 700;
}

.modal-close {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #ffffff;
    font-size: 1.6rem;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.25s ease, background 0.25s ease;
}

.modal-close:hover,
.modal-close:focus {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-form {
    padding: 0 32px 32px;
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.modal-body {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.modal-intro {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    background: #f1f5f9;
    border-radius: 16px;
    padding: 16px 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
}

.modal-intro-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: linear-gradient(135deg, #6366f1 0%, #2563eb 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.modal-intro-content h4 {
    margin: 0 0 6px;
    color: #0f172a;
    font-size: 1.15rem;
    font-weight: 700;
}

.modal-intro-content p {
    margin: 0;
    color: #475569;
    font-size: 0.95rem;
    line-height: 1.6;
}

.modal-grid {
    display: grid;
    gap: 20px;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.modal-grid .form-group {
    margin-bottom: 0;
}

.modal-form label {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.95rem;
}

.modal-form input,
.modal-form select,
.modal-form textarea {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 12px 14px;
    font-size: 0.95rem;
    background: #ffffff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.modal-form input:focus,
.modal-form select:focus,
.modal-form textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    outline: none;
}

.modal-form textarea {
    resize: vertical;
    min-height: 120px;
}

.modal-form .form-help {
    color: #64748b;
    font-size: 0.85rem;
    margin-top: 8px;
    display: block;
    line-height: 1.6;
}

.severity-control {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.severity-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.severity-labels {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.severity-labels label {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
}

.severity-help {
    color: #64748b;
    font-size: 0.88rem;
}

.severity-indicator {
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.severity-meter {
    position: relative;
    width: 100%;
    height: 12px;
    border-radius: 999px;
    background: linear-gradient(90deg, #22c55e 0%, #facc15 50%, #ef4444 100%);
    overflow: hidden;
}

.severity-meter-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    border-radius: inherit;
    background: rgba(255, 255, 255, 0.75);
    width: var(--severity-progress, 20%);
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
    transition: width 0.3s ease;
}

.severity-inputs {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 14px;
    align-items: center;
}

.severity-slider {
    width: 100%;
    accent-color: #2563eb;
}

.severity-number {
    max-width: 120px;
}

.modal-hints {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.hint-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 12px;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.9rem;
    flex: 1 1 220px;
}

.hint-item i {
    color: #2563eb;
    font-size: 1rem;
}

.modal-form .form-actions {
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.modal-form .form-actions .btn {
    min-width: 150px;
    font-weight: 600;
}

body.modal-open {
    overflow: hidden;
}

#deleteModal .modal-header {
    background: linear-gradient(135deg, #fb7185 0%, #ef4444 100%);
}

#deleteModal .modal-form {
    padding: 0 28px 28px;
    gap: 20px;
}

#deleteModal .modal-form p {
    color: #475569;
    font-size: 0.95rem;
    line-height: 1.6;
}

@media (max-width: 640px) {
    .modal {
        padding: 1.5rem;
    }

    .modal-header {
        padding: 20px 24px;
    }

    .modal-form {
        padding: 0 20px 24px;
    }

    .severity-inputs {
        grid-template-columns: 1fr;
    }

    .severity-number {
        max-width: 100%;
    }

    .modal-intro {
        flex-direction: column;
    }
}
</style>

<script>
const severityLevels = [
    { threshold: 1.5, label: 'Low impact', chipBg: 'rgba(34, 197, 94, 0.18)', chipColor: '#166534', icon: 'fa-leaf' },
    { threshold: 3.0, label: 'Moderate', chipBg: 'rgba(250, 204, 21, 0.22)', chipColor: '#92400e', icon: 'fa-adjust' },
    { threshold: 4.2, label: 'High', chipBg: 'rgba(248, 113, 113, 0.22)', chipColor: '#b91c1c', icon: 'fa-fire' },
    { threshold: Infinity, label: 'Critical', chipBg: 'rgba(220, 38, 38, 0.28)', chipColor: '#7f1d1d', icon: 'fa-radiation' }
];

window.syncSeverityDisplay = function(value) {
    const normalized = Math.max(0.1, Math.min(5, parseFloat(value) || 0.1));
    const percentage = ((normalized - 0.1) / (5 - 0.1)) * 100;
    const indicator = document.querySelector('[data-severity-label]');
    const indicatorText = indicator ? indicator.querySelector('[data-severity-text]') : null;
    const indicatorIcon = indicator ? indicator.querySelector('i') : null;
    const meter = document.querySelector('[data-severity-meter]');
    const level = severityLevels.find(item => normalized <= item.threshold) || severityLevels[severityLevels.length - 1];

    if (indicator) {
        indicator.style.background = level.chipBg;
        indicator.style.color = level.chipColor;
    }
    if (indicatorText) {
        indicatorText.textContent = level.label;
    }
    if (indicatorIcon && level.icon) {
        indicatorIcon.className = 'fas ' + level.icon;
    }
    if (meter) {
        meter.style.setProperty('--severity-progress', percentage + '%');
    }
};

function openModal(modal) {
    if (!modal) return;
    modal.style.display = 'flex';
    modal.classList.add('open');
    document.body.classList.add('modal-open');
}

function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('open');
    modal.style.display = 'none';
    if (!document.querySelector('.modal.open')) {
        document.body.classList.remove('modal-open');
    }
}

function safeFocus(element) {
    if (!element || typeof element.focus !== 'function') {
        return;
    }
    try {
        element.focus({ preventScroll: true });
    } catch (error) {
        element.focus();
    }
}

function resetCreateTypeForm() {
    const form = document.getElementById('create-type-form');
    if (!form) return;
    form.reset();

    const slider = document.getElementById('severity_weight_slider');
    const numberInput = document.getElementById('severity_weight');
    const fallback = parseFloat((numberInput && numberInput.dataset.default) || (slider && slider.dataset.default) || '1') || 1;

    if (slider) {
        slider.value = fallback;
    }
    if (numberInput) {
        numberInput.value = fallback.toFixed(1);
    }

    if (typeof window.syncSeverityDisplay === 'function') {
        window.syncSeverityDisplay(fallback);
    }
}

function showCreateModal() {
    const modal = document.getElementById('createTypeModal');
    if (!modal) return;

    resetCreateTypeForm();
    openModal(modal);

    requestAnimationFrame(() => {
        const typeNameField = document.getElementById('type_name');
        safeFocus(typeNameField);
    });
}

function closeCreateModal() {
    const modal = document.getElementById('createTypeModal');
    if (!modal) return;

    closeModal(modal);
    resetCreateTypeForm();
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
    const modal = document.getElementById('deleteModal');
    const hiddenInput = document.getElementById('delete-type-id');

    if (hiddenInput) {
        hiddenInput.value = typeId;
    }

    if (modal) {
        openModal(modal);
        requestAnimationFrame(() => {
            const deleteButton = modal.querySelector('button[type="submit"]');
            safeFocus(deleteButton);
        });
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (!modal) return;

    closeModal(modal);
    const hiddenInput = document.getElementById('delete-type-id');
    if (hiddenInput) {
        hiddenInput.value = '';
    }
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
document.addEventListener('click', function(event) {
    if (!event.target.classList || !event.target.classList.contains('modal') || !event.target.classList.contains('open')) {
        return;
    }

    if (event.target.id === 'createTypeModal') {
        closeCreateModal();
    } else if (event.target.id === 'deleteModal') {
        closeDeleteModal();
    } else {
        closeModal(event.target);
    }
});

// Close active modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') {
        return;
    }

    const openModals = document.querySelectorAll('.modal.open');
    openModals.forEach(modal => {
        if (modal.id === 'createTypeModal') {
            closeCreateModal();
        } else if (modal.id === 'deleteModal') {
            closeDeleteModal();
        } else {
            closeModal(modal);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('severity_weight_slider');
    const numberInput = document.getElementById('severity_weight');

    if (slider && numberInput) {
        const initial = parseFloat(numberInput.value) || parseFloat(slider.value) || 1;
        slider.value = initial;
        numberInput.value = initial.toFixed(1);
        window.syncSeverityDisplay(initial);

        slider.addEventListener('input', function() {
            const value = parseFloat(slider.value) || 1;
            numberInput.value = value.toFixed(1);
            window.syncSeverityDisplay(value);
        });

        numberInput.addEventListener('input', function() {
            const value = parseFloat(numberInput.value);
            if (Number.isNaN(value)) {
                return;
            }
            const bounded = Math.max(0.1, Math.min(5, value));
            slider.value = bounded;
            window.syncSeverityDisplay(bounded);
        });

        numberInput.addEventListener('change', function() {
            let value = parseFloat(numberInput.value);
            if (Number.isNaN(value)) {
                value = parseFloat(numberInput.dataset.default || slider.dataset.default || '1') || 1;
            }
            const bounded = Math.max(0.1, Math.min(5, value));
            numberInput.value = bounded.toFixed(1);
            slider.value = bounded;
            window.syncSeverityDisplay(bounded);
        });
    }
});

// ====================================
// REAL-TIME INTEGRATION (Minimal - Low Priority)
// ====================================
if (window.realtimeSystem) {
    console.log('✅ Real-time system available for disaster-types page');
    // Disaster types page has minimal need for real-time updates
}
</script>

<?php include 'includes/footer.php'; ?>