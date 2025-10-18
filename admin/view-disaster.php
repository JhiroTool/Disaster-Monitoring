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

$assessments = [];
if (!empty($disaster['assessments'])) {
    $decodedAssessments = json_decode($disaster['assessments'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedAssessments)) {
        $assessments = $decodedAssessments;
    }
}

$particularLabels = [
    'home_state' => 'Current state of home/building after the typhoon',
    'accessibility' => 'Accessibility to road',
    'power' => 'Power Supply Status',
    'water' => 'Clean Water Supply',
    'food' => 'Food and essential supplies availability',
    'flooding' => 'Level of flooding',
    'safety' => 'Level of safety',
    'readiness' => 'Readiness to go back to school',
    'transport' => 'Transportation Status',
    'eq_structural' => 'Structural integrity after the earthquake',
    'eq_road_access' => 'Road access after the earthquake',
    'eq_utilities' => 'Utility services after the earthquake',
    'eq_casualties' => 'Casualty and injury status',
    'eq_evacuation' => 'Evacuation and shelter needs',
    'eq_aftershocks' => 'Aftershock activity and risk',
    'volc_ashfall' => 'Ashfall condition in community',
    'volc_lava_flow' => 'Lava or pyroclastic flow threat',
    'volc_air_quality' => 'Air quality and respiratory safety',
    'volc_water' => 'Water supply and contamination status',
    'volc_evacuation' => 'Evacuation progress and shelter status',
    'volc_infrastructure' => 'Critical infrastructure condition'
];

$colorLabels = [
    'green' => 'Green (Good)',
    'orange' => 'Orange (Moderate)',
    'red' => 'Red (Critical)'
];

$selectedParticularKey = $assessments['selected_particular'] ?? '';
$selectedParticularColor = $assessments['selected_particular_color'] ?? '';
$selectedParticularDetail = $assessments['selected_particular_detail'] ?? '';

$selectedParticularLabel = '';
if (!empty($selectedParticularKey)) {
    if (isset($particularLabels[$selectedParticularKey])) {
        $selectedParticularLabel = $particularLabels[$selectedParticularKey];
    } else {
        $selectedParticularLabel = ucwords(str_replace('_', ' ', $selectedParticularKey));
    }
}
$selectedColorLabel = '';
if (!empty($selectedParticularColor)) {
    $selectedColorLabel = $colorLabels[$selectedParticularColor] ?? ucfirst($selectedParticularColor);
}

$immediateNeedsRaw = $disaster['immediate_needs'] ?? $disaster['immediate_need'] ?? null;
$immediateNeedsList = [];
$immediateNeedsText = '';

if (!empty($immediateNeedsRaw)) {
    if (is_string($immediateNeedsRaw)) {
        $decodedNeeds = json_decode($immediateNeedsRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedNeeds)) {
            foreach ($decodedNeeds as $need) {
                $need = trim((string) $need);
                if ($need !== '') {
                    $immediateNeedsList[] = $need;
                }
            }
        } else {
            $immediateNeedsText = trim($immediateNeedsRaw);
        }
    } elseif (is_array($immediateNeedsRaw)) {
        foreach ($immediateNeedsRaw as $need) {
            $need = trim((string) $need);
            if ($need !== '') {
                $immediateNeedsList[] = $need;
            }
        }
    } else {
        $immediateNeedsText = trim((string) $immediateNeedsRaw);
    }
}

if (empty($immediateNeedsList) && $immediateNeedsText === '' && is_string($immediateNeedsRaw)) {
    $immediateNeedsText = trim($immediateNeedsRaw);
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
    <?php if ($selectedParticularLabel || $selectedColorLabel || $selectedParticularDetail): ?>
    <div class="detail-section">
        <label><i class="fas fa-clipboard-list"></i> Rapid Assessment</label>
        <div class="value" style="display:flex;flex-direction:column;gap:4px;">
            <?php if ($selectedParticularLabel): ?>
                <div><strong>Particular:</strong> <?php echo htmlspecialchars($selectedParticularLabel); ?></div>
            <?php endif; ?>
            <?php if ($selectedColorLabel): ?>
                <div><strong>Color:</strong> <?php echo htmlspecialchars($selectedColorLabel); ?></div>
            <?php endif; ?>
            <?php if (!empty($selectedParticularDetail)): ?>
                <div><strong>Detail:</strong> <?php echo htmlspecialchars($selectedParticularDetail); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
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
    <?php if (!empty($immediateNeedsList) || $immediateNeedsText !== ''): ?>
    <div class="detail-section">
        <label><i class="fas fa-ambulance"></i> Immediate Needs</label>
        <div class="value">
            <?php if (!empty($immediateNeedsList)): ?>
                <ul style="margin: 0; padding-left: 18px; list-style: disc;">
                    <?php foreach ($immediateNeedsList as $need): ?>
                        <li><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $need))); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <?php echo nl2br(htmlspecialchars($immediateNeedsText)); ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    </div>
</div>

<script>
// ====================================
// REAL-TIME INTEGRATION
// ====================================
const currentDisasterId = <?php echo $disaster_id; ?>;

if (window.realtimeSystem) {
    // Listen for updates to this disaster
    window.realtimeSystem.registerCallback('onUpdate', (data) => {
        if (data.disaster_id == currentDisasterId) {
            showUpdateNotification();
        }
    });
    
    // Listen for status changes
    window.realtimeSystem.registerCallback('onStatusChange', (data) => {
        if (data.disaster_id == currentDisasterId) {
            showStatusChangeNotification(data.new_status);
        }
    });
    
    console.log('✅ Real-time updates enabled for view-disaster #' + currentDisasterId);
} else {
    console.warn('⚠️ RealtimeSystem not available on view-disaster page');
}

function showUpdateNotification() {
    const banner = document.createElement('div');
    banner.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #3b82f6;
        color: white;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideUp 0.4s ease-out;
        display: flex;
        align-items: center;
        gap: 12px;
        font-family: 'Inter', sans-serif;
    `;
    
    banner.innerHTML = `
        <i class="fas fa-info-circle" style="font-size: 20px;"></i>
        <div>
            <strong style="display: block;">Information Updated</strong>
            <span style="font-size: 14px;">This disaster has been modified.</span>
        </div>
        <button onclick="location.reload()" style="
            background: white;
            color: #3b82f6;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 12px;
        ">
            Reload
        </button>
    `;
    
    document.body.appendChild(banner);
    setTimeout(() => banner.remove(), 10000);
}

function showStatusChangeNotification(newStatus) {
    const banner = document.createElement('div');
    banner.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideUp 0.4s ease-out;
        display: flex;
        align-items: center;
        gap: 12px;
        font-family: 'Inter', sans-serif;
    `;
    
    banner.innerHTML = `
        <i class="fas fa-check-circle" style="font-size: 20px;"></i>
        <div>
            <strong style="display: block;">Status Changed</strong>
            <span style="font-size: 14px;">New status: ${newStatus}</span>
        </div>
        <button onclick="location.reload()" style="
            background: white;
            color: #10b981;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 12px;
        ">
            Reload
        </button>
    `;
    
    document.body.appendChild(banner);
    setTimeout(() => banner.remove(), 10000);
}
</script>

<?php include 'includes/footer.php'; ?>
<link rel="stylesheet" href="assets/css/admin.css?v=2">
