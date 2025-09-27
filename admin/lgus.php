<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

// Allow viewing for all users, restrict editing to admins
$can_edit = isAdmin();

$page_title = 'LGU Management';

// Handle LGU operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    if (isset($_POST['create_lgu'])) {
        $lgu_name = sanitizeInput($_POST['lgu_name']);
        $lgu_type = sanitizeInput($_POST['lgu_type']);
        $region = sanitizeInput($_POST['region']);
        $province = sanitizeInput($_POST['province']);
        $city_municipality = sanitizeInput($_POST['city_municipality']);
        $contact_person = sanitizeInput($_POST['contact_person']);
        $contact_phone = sanitizeInput($_POST['contact_phone']);
        $contact_email = sanitizeInput($_POST['contact_email']);
        $office_address = sanitizeInput($_POST['office_address']);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO lgus (lgu_name, lgu_type, region, province, city_municipality, 
                                contact_person, contact_phone, contact_email, office_address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$lgu_name, $lgu_type, $region, $province, $city_municipality, 
                          $contact_person, $contact_phone, $contact_email, $office_address]);
            
            $success_message = "LGU created successfully.";
        } catch (Exception $e) {
            error_log("LGU creation error: " . $e->getMessage());
            $error_message = "Error creating LGU. Please try again.";
        }
    }
    
    if (isset($_POST['update_lgu'])) {
        $lgu_id = intval($_POST['lgu_id']);
        $lgu_name = sanitizeInput($_POST['lgu_name']);
        $address = sanitizeInput($_POST['address']);
        $contact_person = sanitizeInput($_POST['contact_person']);
        $phone = sanitizeInput($_POST['phone']);
        $email = sanitizeInput($_POST['email']);
        $coverage_area = sanitizeInput($_POST['coverage_area']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE lgus SET lgu_name = ?, address = ?, contact_person = ?, 
                phone = ?, email = ?, coverage_area = ?, is_active = ?, updated_at = NOW()
                WHERE lgu_id = ?
            ");
            $stmt->execute([$lgu_name, $address, $contact_person, $phone, $email, $coverage_area, $is_active, $lgu_id]);
            
            $success_message = "LGU updated successfully.";
        } catch (Exception $e) {
            error_log("LGU update error: " . $e->getMessage());
            $error_message = "Error updating LGU. Please try again.";
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $lgu_id = intval($_POST['lgu_id']);
        $is_active = intval($_POST['is_active']);
        
        try {
            $stmt = $pdo->prepare("UPDATE lgus SET is_active = ?, updated_at = NOW() WHERE lgu_id = ?");
            $stmt->execute([$is_active, $lgu_id]);
            
            $success_message = "LGU status updated successfully.";
        } catch (Exception $e) {
            error_log("LGU status update error: " . $e->getMessage());
            $error_message = "Error updating LGU status.";
        }
    }
}

// Fetch LGUs with statistics
try {
    $stmt = $pdo->query("
        SELECT l.*, 
               COUNT(DISTINCT u.user_id) as staff_count,
               COUNT(DISTINCT d.disaster_id) as assigned_disasters,
               COUNT(CASE WHEN d.status = 'resolved' THEN 1 END) as resolved_disasters,
               AVG(CASE 
                   WHEN d.acknowledged_at IS NOT NULL 
                   THEN TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at) 
               END) as avg_response_hours
        FROM lgus l
        LEFT JOIN users u ON l.lgu_id = u.lgu_id AND u.is_active = TRUE
        LEFT JOIN disasters d ON l.lgu_id = d.assigned_lgu_id
        GROUP BY l.lgu_id
        ORDER BY l.lgu_name
    ");
    $lgus = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("LGUs fetch error: " . $e->getMessage());
    $lgus = [];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-building"></i> LGU Management</h2>
        <p>Manage Local Government Units and their information</p>
    </div>
    <div class="page-actions">
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add LGU
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

<!-- LGU Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-building"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo count($lgus); ?></div>
            <div class="stat-label">Total LGUs</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo count(array_filter($lgus, fn($l) => $l['is_active'])); ?></div>
            <div class="stat-label">Active LGUs</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo array_sum(array_column($lgus, 'staff_count')); ?></div>
            <div class="stat-label">Total Staff</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo array_sum(array_column($lgus, 'assigned_disasters')); ?></div>
            <div class="stat-label">Assigned Disasters</div>
        </div>
    </div>
</div>

<!-- LGUs Grid -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>All LGUs</h3>
        <div class="search-box">
            <input type="text" id="lguSearch" placeholder="Search LGUs..." onkeyup="filterLGUs()">
            <i class="fas fa-search"></i>
        </div>
    </div>
    <div class="card-content">
        <div class="lgus-grid">
            <?php foreach ($lgus as $lgu): ?>
                <div class="lgu-card">
                    <div class="lgu-header">
                        <div class="lgu-info">
                            <h4><?php echo htmlspecialchars($lgu['lgu_name']); ?></h4>
                            <span class="status-badge <?php echo $lgu['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $lgu['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        <div class="lgu-actions">
                            <button onclick="editLGU(<?php echo $lgu['lgu_id']; ?>)" 
                                    class="btn btn-xs btn-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="toggleLGUStatus(<?php echo $lgu['lgu_id']; ?>, <?php echo $lgu['is_active'] ? 0 : 1; ?>)" 
                                    class="btn btn-xs <?php echo $lgu['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                    title="<?php echo $lgu['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas fa-<?php echo $lgu['is_active'] ? 'ban' : 'check'; ?>"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="lgu-details">
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($lgu['office_address'] ?? 'No address'); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($lgu['contact_person'] ?? 'No contact person'); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($lgu['contact_phone'] ?? 'No phone'); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($lgu['contact_email'] ?? 'No email'); ?></span>
                        </div>
                    </div>
                    
                    <div class="lgu-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $lgu['staff_count']; ?></span>
                            <span class="stat-label">Staff</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $lgu['assigned_disasters']; ?></span>
                            <span class="stat-label">Disasters</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $lgu['resolved_disasters']; ?></span>
                            <span class="stat-label">Resolved</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">
                                <?php echo $lgu['avg_response_hours'] ? number_format($lgu['avg_response_hours'], 1) . 'h' : 'N/A'; ?>
                            </span>
                            <span class="stat-label">Avg Response</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Create LGU Modal -->
<div id="createLGUModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Add New LGU</h3>
            <button class="modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="lgu_name">LGU Name *</label>
                    <input type="text" name="lgu_name" id="lgu_name" required>
                </div>
                <div class="form-group">
                    <label for="lgu_type">LGU Type *</label>
                    <select name="lgu_type" id="lgu_type" required>
                        <option value="city">City</option>
                        <option value="municipality">Municipality</option>
                        <option value="province">Province</option>
                        <option value="barangay">Barangay</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="region">Region *</label>
                    <input type="text" name="region" id="region" required>
                </div>
                <div class="form-group">
                    <label for="province">Province *</label>
                    <input type="text" name="province" id="province" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="city_municipality">City/Municipality *</label>
                <input type="text" name="city_municipality" id="city_municipality" required>
            </div>
            
            <div class="form-group">
                <label for="office_address">Office Address *</label>
                <textarea name="office_address" id="office_address" rows="2" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="contact_person">Contact Person *</label>
                    <input type="text" name="contact_person" id="contact_person" required>
                </div>
                <div class="form-group">
                    <label for="contact_phone">Phone Number *</label>
                    <input type="tel" name="contact_phone" id="contact_phone" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="contact_email">Email Address</label>
                <input type="email" name="contact_email" id="contact_email">
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeCreateModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="create_lgu" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create LGU
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Status Toggle Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update LGU Status</h3>
            <button class="modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="lgu_id" id="status-lgu-id">
            <input type="hidden" name="is_active" id="status-is-active">
            
            <p id="status-confirmation-text"></p>
            
            <div class="form-actions">
                <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="toggle_status" class="btn btn-primary">Confirm</button>
            </div>
        </form>
    </div>
</div>

<style>
.lgus-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.lgu-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.lgu-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.lgu-header {
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.lgu-info h4 {
    margin: 0 0 5px 0;
    color: var(--text-color);
    font-size: 18px;
}

.lgu-actions {
    display: flex;
    gap: 5px;
}

.lgu-details {
    padding: 20px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    color: var(--text-color);
}

.detail-item i {
    width: 16px;
    color: var(--text-muted);
}

.lgu-stats {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid var(--border-color);
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.lgu-stats .stat-item {
    text-align: center;
}

.lgu-stats .stat-number {
    display: block;
    font-size: 18px;
    font-weight: 700;
    color: var(--text-color);
}

.lgu-stats .stat-label {
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 768px) {
    .lgus-grid {
        grid-template-columns: 1fr;
    }
    
    .lgu-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function showCreateModal() {
    document.getElementById('createLGUModal').style.display = 'block';
}

function closeCreateModal() {
    document.getElementById('createLGUModal').style.display = 'none';
}

function editLGU(lguId) {
    // This would open an edit modal - implementation needed
    alert('Edit LGU functionality would be implemented here');
}

function toggleLGUStatus(lguId, newStatus) {
    document.getElementById('status-lgu-id').value = lguId;
    document.getElementById('status-is-active').value = newStatus;
    
    const action = newStatus === 1 ? 'activate' : 'deactivate';
    document.getElementById('status-confirmation-text').textContent = 
        `Are you sure you want to ${action} this LGU?`;
    
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

function filterLGUs() {
    const input = document.getElementById('lguSearch');
    const filter = input.value.toLowerCase();
    const cards = document.querySelectorAll('.lgu-card');
    
    cards.forEach(card => {
        const name = card.querySelector('h4').textContent.toLowerCase();
        const address = card.querySelector('.detail-item span').textContent.toLowerCase();
        
        if (name.includes(filter) || address.includes(filter)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>