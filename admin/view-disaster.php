<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

$disaster_id = intval($_GET['id'] ?? 0);
$page_title = 'View Disaster Report';

if (!$disaster_id) {
    echo '<div style="color:red;font-weight:bold;padding:20px;">Error: No disaster ID provided in URL.</div>';
    exit;
}

try {
    $stmt = $pdo->prepare("
     SELECT d.*, dt.type_name, dt.description as type_description,
         lgu.lgu_name, lgu.contact_person,
         CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
         u.phone as user_phone, u.email as user_email
        FROM disasters d
        JOIN disaster_types dt ON d.type_id = dt.type_id
        LEFT JOIN lgus lgu ON d.assigned_lgu_id = lgu.lgu_id
        LEFT JOIN users u ON d.assigned_user_id = u.user_id
        WHERE d.disaster_id = ?
    ");
    $stmt->execute([$disaster_id]);
    $disaster = $stmt->fetch();
    if (!$disaster) {
        echo '<div style="color:red;font-weight:bold;padding:20px;">Error: No disaster found in database for ID: ' . htmlspecialchars($disaster_id) . '</div>';
        exit;
    }
} catch (Exception $e) {
    error_log("Disaster view error: " . $e->getMessage());
    echo '<div style="color:red;font-weight:bold;padding:20px;">SQL Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

include 'includes/header.php';
?>
<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-file-alt"></i> Disaster Report Details</h2>
        <p>Tracking ID: <?php echo htmlspecialchars($disaster['tracking_id']); ?></p>
    </div>
    <div class="page-actions">
        <a href="disasters.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>
<div class="dashboard-card" style="max-width:700px;margin:auto;">
    <div class="card-header">
        <h3><i class="fas fa-info-circle"></i> Report Information</h3>
    </div>
    <div class="card-content">
        <div class="detail-section" style="justify-content:center;">
            <div class="value" style="width:100%;text-align:center;">
                <?php 
                $img_rel_path = !empty($disaster['image_path']) ? '../' . ltrim($disaster['image_path'], '/') : '';
                if ($disaster['image_path'] && file_exists($img_rel_path)) : ?>
                    <img src="<?php echo htmlspecialchars($img_rel_path); ?>" alt="Emergency Photo" style="max-width:320px;max-height:220px;border-radius:8px;box-shadow:0 2px 8px 0 rgba(0,0,0,0.10);margin-bottom:12px;">
                <?php else: ?>
                    <img src="https://via.placeholder.com/320x220?text=No+Image" alt="No Photo" style="max-width:320px;max-height:220px;border-radius:8px;opacity:0.7;margin-bottom:12px;">
                    <div style="color:#888;font-size:0.98rem;margin-top:2px;">No photo available for this report</div>
                <?php endif; ?>
            </div>
        </div>
    <div class="detail-section"><label><i class="fas fa-layer-group"></i> Type</label><div class="value"><?php echo htmlspecialchars($disaster['type_name']); ?></div></div>
    <div class="detail-section"><label><i class="fas fa-bolt"></i> Severity</label><div class="value"><?php echo htmlspecialchars($disaster['severity_display']); ?></div></div>
    <div class="detail-section"><label><i class="fas fa-flag"></i> Status</label><div class="value"><?php echo ucfirst(str_replace('_', ' ', $disaster['status'])); ?></div></div>
    <div class="detail-section"><label><i class="fas fa-exclamation-triangle"></i> Priority</label><div class="value">
        <?php 
            $priority = strtolower($disaster['priority']);
            $priorityClass = 'priority-medium';
            if ($priority === 'low') $priorityClass = 'priority-low';
            elseif ($priority === 'high' || $priority === 'critical') $priorityClass = 'priority-high';
            elseif ($priority === 'medium') $priorityClass = 'priority-medium';
        ?>
        <span class="priority-badge <?php echo $priorityClass; ?>"><?php echo ucfirst($disaster['priority']); ?></span>
    </div></div>
    <div class="detail-section"><label><i class="fas fa-calendar-alt"></i> Reported At</label><div class="value"><?php echo date('M j, Y g:i A', strtotime($disaster['reported_at'])); ?></div></div>
    <div class="detail-section"><label><i class="fas fa-map-marker-alt"></i> Location</label><div class="value"><?php echo htmlspecialchars($disaster['address']); ?></div></div>
    <?php if ($disaster['landmark']): ?>
    <div class="detail-section"><label><i class="fas fa-landmark"></i> Landmark</label><div class="value"><?php echo htmlspecialchars($disaster['landmark']); ?></div></div>
    <?php endif; ?>
    <div class="detail-section"><label><i class="fas fa-align-left"></i> Description</label><div class="value"><?php echo nl2br(htmlspecialchars($disaster['description'])); ?></div></div>
    <?php if ($disaster['current_situation']): ?>
    <div class="detail-section"><label><i class="fas fa-info-circle"></i> Current Situation</label><div class="value"><?php echo nl2br(htmlspecialchars($disaster['current_situation'])); ?></div></div>
    <?php endif; ?>
    <?php if ($disaster['people_affected']): ?>
    <div class="detail-section"><label><i class="fas fa-users"></i> People Affected</label><div class="value"><?php echo htmlspecialchars($disaster['people_affected']); ?></div></div>
    <?php endif; ?>
    <?php if ($disaster['immediate_need']): ?>
    <div class="detail-section"><label><i class="fas fa-ambulance"></i> Immediate Needs</label><div class="value"><?php echo htmlspecialchars($disaster['immediate_need']); ?></div></div>
    <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<link rel="stylesheet" href="assets/css/admin.css?v=2">
