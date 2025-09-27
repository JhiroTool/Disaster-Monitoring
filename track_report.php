<?php
require_once 'config/database.php';

$tracking_result = null;
$disaster_data = null;
$updates = [];

// Get tracking ID from URL parameter or form submission
$auto_tracking_id = sanitizeInput($_GET['tracking_id'] ?? '');

// Handle tracking form submission or auto-track from URL parameter
if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_report'])) || !empty($auto_tracking_id)) {
    $tracking_id = !empty($auto_tracking_id) ? $auto_tracking_id : sanitizeInput($_POST['tracking_id'] ?? '');
    
    if (empty($tracking_id)) {
        $tracking_result = [
            'success' => false,
            'message' => 'Please enter a tracking ID.'
        ];
    } else {
        try {
            // Fetch disaster information
            $disaster_stmt = $pdo->prepare("
                SELECT d.*, dt.type_name, l.lgu_name, l.contact_phone as lgu_phone,
                       CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name
                FROM disasters d
                LEFT JOIN disaster_types dt ON d.type_id = dt.type_id
                LEFT JOIN lgus l ON d.assigned_lgu_id = l.lgu_id
                LEFT JOIN users u ON d.assigned_user_id = u.user_id
                WHERE d.tracking_id = ?
            ");
            $disaster_stmt->execute([$tracking_id]);
            $disaster_data = $disaster_stmt->fetch();
            
            if ($disaster_data) {
                // Fetch updates for this disaster
                $updates_stmt = $pdo->prepare("
                    SELECT du.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
                    FROM disaster_updates du
                    LEFT JOIN users u ON du.user_id = u.user_id
                    WHERE du.disaster_id = ? AND du.is_public = TRUE
                    ORDER BY du.created_at DESC
                ");
                $updates_stmt->execute([$disaster_data['disaster_id']]);
                $updates = $updates_stmt->fetchAll();
                
                $tracking_result = [
                    'success' => true,
                    'message' => 'Report found successfully!'
                ];
            } else {
                $tracking_result = [
                    'success' => false,
                    'message' => 'No report found with this tracking ID. Please check and try again.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Tracking error: " . $e->getMessage());
            $tracking_result = [
                'success' => false,
                'message' => 'An error occurred while searching for your report. Please try again.'
            ];
        }
    }
}

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'status-pending';
        case 'assigned': return 'status-assigned';
        case 'acknowledged': return 'status-acknowledged';
        case 'in_progress': return 'status-progress';
        case 'resolved': return 'status-resolved';
        case 'closed': return 'status-closed';
        case 'escalated': return 'status-escalated';
        default: return 'status-pending';
    }
}

// Function to get status display text
function getStatusDisplayText($status) {
    switch ($status) {
        case 'pending': return 'Pending Review';
        case 'assigned': return 'Assigned to LGU';
        case 'acknowledged': return 'Acknowledged by LGU';
        case 'in_progress': return 'Response in Progress';
        case 'resolved': return 'Resolved';
        case 'closed': return 'Closed';
        case 'escalated': return 'Escalated to Higher Authority';
        default: return ucfirst($status);
    }
}

// Function to get priority badge class
function getPriorityBadgeClass($priority) {
    switch ($priority) {
        case 'low': return 'priority-low';
        case 'medium': return 'priority-medium';
        case 'high': return 'priority-high';
        case 'critical': return 'priority-critical';
        default: return 'priority-medium';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Report - iMSafe Disaster Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .tracking-page {
            min-height: 100vh;
            padding: 80px 0 40px;
            background: linear-gradient(135deg, #f8faff 0%, #e6f3ff 100%);
        }
        
        .tracking-form {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .tracking-input {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .tracking-input input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .tracking-input input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn-track {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-track:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        
        .report-details {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .report-tracking-id {
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: 2px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        }
        
        .report-header h2 {
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .report-header p {
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .report-info {
            padding: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            padding: 20px;
            background: #f8faff;
            border-radius: 12px;
            border-left: 4px solid #3b82f6;
        }
        
        .info-item h4 {
            margin: 0 0 8px 0;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-item p {
            margin: 0;
            color: #1f2937;
            font-size: 16px;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-assigned { background: #dbeafe; color: #1e40af; }
        .status-acknowledged { background: #d1fae5; color: #065f46; }
        .status-progress { background: #e0e7ff; color: #3730a3; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .status-closed { background: #f3f4f6; color: #374151; }
        .status-escalated { background: #fecaca; color: #991b1b; }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .priority-low { background: #f0f9ff; color: #0369a1; }
        .priority-medium { background: #fef3c7; color: #92400e; }
        .priority-high { background: #fed7aa; color: #c2410c; }
        .priority-critical { background: #fecaca; color: #991b1b; }
        
        .updates-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .updates-header {
            background: #f8faff;
            padding: 20px 30px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .updates-list {
            padding: 20px 0;
        }
        
        .update-item {
            padding: 20px 30px;
            border-bottom: 1px solid #f3f4f6;
            position: relative;
        }
        
        .update-item:last-child {
            border-bottom: none;
        }
        
        .update-item::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 30px;
            width: 10px;
            height: 10px;
            background: #3b82f6;
            border-radius: 50%;
        }
        
        .update-item::after {
            content: '';
            position: absolute;
            left: 19px;
            top: 40px;
            width: 2px;
            height: calc(100% - 20px);
            background: #e5e7eb;
        }
        
        .update-item:last-child::after {
            display: none;
        }
        
        .update-content {
            margin-left: 30px;
        }
        
        .update-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .update-description {
            color: #6b7280;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .update-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #9ca3af;
        }
        
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        
        .alert-error {
            background-color: #fecaca;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }
        
        .back-button:hover {
            color: #1d4ed8;
        }
        
        .no-updates {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .contact-lgu {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .contact-lgu h4 {
            color: #0369a1;
            margin: 0 0 10px 0;
        }
        
        .contact-lgu p {
            margin: 0;
            color: #075985;
        }
        
        .lgu-phone {
            color: #0369a1;
            font-weight: 600;
            text-decoration: none;
        }
        
        .lgu-phone:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .tracking-input {
                flex-direction: column;
            }
            
            .update-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
        
        .auto-fill-notice {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .report-description,
        .report-image {
            margin: 25px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .report-description h4,
        .report-image h4 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: white;
            font-weight: 600;
        }
        
        .report-description p {
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            margin: 0;
        }
        
        .image-container {
            position: relative;
            display: inline-block;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .image-container:hover {
            transform: scale(1.02);
        }
        
        .emergency-photo {
            max-width: 100%;
            max-height: 400px;
            width: auto;
            height: auto;
            display: block;
            border-radius: 12px;
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: white;
            font-size: 14px;
        }
        
        .image-container:hover .image-overlay {
            opacity: 1;
        }
        
        .image-overlay i {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        /* Image Modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            padding: 20px;
            cursor: pointer;
        }
        
        .image-modal img {
            max-width: 100%;
            max-height: 100%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 8px;
        }
        
        .image-modal .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            z-index: 10000;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-shield-alt"></i>
                <span>iMSafe System</span>
            </div>
            <div class="nav-menu" id="nav-menu">
                <a href="index.php" class="nav-link">Home</a>
                <a href="index.php#features" class="nav-link">Features</a>
                <a href="index.php#about" class="nav-link">About</a>
                <a href="index.php#contact" class="nav-link">Contact</a>
                <a href="admin/dashboard.php" class="nav-link btn-login">Admin Panel</a>
            </div>
            <div class="hamburger" id="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- Tracking Page -->
    <section class="tracking-page">
        <div class="container">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
            
            <div class="tracking-form">
                <h1><i class="fas fa-search"></i> Track Your Emergency Report</h1>
                <p>Enter your tracking ID to check the status and updates of your emergency report.</p>
                
                <?php if (!empty($auto_tracking_id)): ?>
                    <div class="auto-fill-notice">
                        <i class="fas fa-info-circle"></i>
                        Tracking ID automatically filled from your recent report submission.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="track_report.php">
                    <div class="tracking-input">
                        <input type="text" 
                               name="tracking_id" 
                               placeholder="Enter Tracking ID (e.g., DM20250927XXXX)"
                               value="<?php echo htmlspecialchars($auto_tracking_id ?: ($_POST['tracking_id'] ?? '')); ?>"
                               maxlength="20" 
                               required>
                        <button type="submit" name="track_report" class="btn-track">
                            <i class="fas fa-search"></i>
                            Track Report
                        </button>
                    </div>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #3b82f6;">
                    <p style="margin: 0; color: #1e40af; font-size: 14px;">
                        <i class="fas fa-info-circle"></i>
                        Your tracking ID was provided when you submitted your emergency report. It starts with "DM" followed by the date and a unique code.
                    </p>
                </div>
            </div>
            
            <!-- Display tracking result -->
            <?php if ($tracking_result): ?>
                <?php if ($tracking_result['success']): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($tracking_result['message']); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($tracking_result['message']); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Display report details if found -->
            <?php if ($disaster_data): ?>
                <div class="report-details">
                    <div class="report-header">
                        <div class="report-tracking-id"><?php echo htmlspecialchars($disaster_data['tracking_id']); ?></div>
                        <h2 style="margin: 10px 0; font-size: 1.3em; font-weight: 600;"><?php echo htmlspecialchars($disaster_data['type_name'] ?? 'Emergency Report'); ?></h2>
                        <p style="margin: 8px 0 0 0; opacity: 1; font-size: 1.1em; font-weight: 500; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);"><?php echo htmlspecialchars($disaster_data['disaster_name']); ?></p>
                        <div style="margin-top: 15px; display: flex; gap: 20px; flex-wrap: wrap; font-size: 0.95em;">
                            <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 15px;">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($disaster_data['city'] ?? 'Location'); ?>
                            </span>
                            <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 15px;">
                                <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($disaster_data['reported_at'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="report-info">
                        <div class="info-grid">
                            <div class="info-item">
                                <h4>Current Status</h4>
                                <p>
                                    <span class="status-badge <?php echo getStatusBadgeClass($disaster_data['status']); ?>">
                                        <i class="fas fa-circle"></i>
                                        <?php echo getStatusDisplayText($disaster_data['status']); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="info-item">
                                <h4>Priority Level</h4>
                                <p>
                                    <span class="priority-badge <?php echo getPriorityBadgeClass($disaster_data['priority']); ?>">
                                        <i class="fas fa-flag"></i>
                                        <?php echo ucfirst($disaster_data['priority']); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="info-item">
                                <h4>Disaster Type</h4>
                                <p><?php echo htmlspecialchars($disaster_data['type_name'] ?? 'N/A'); ?></p>
                            </div>
                            
                            <div class="info-item">
                                <h4>Severity Level</h4>
                                <p><?php echo htmlspecialchars($disaster_data['severity_display']); ?></p>
                            </div>
                            
                            <div class="info-item">
                                <h4>Reported Date</h4>
                                <p><?php echo date('M d, Y \a\t g:i A', strtotime($disaster_data['reported_at'])); ?></p>
                            </div>
                            
                            <div class="info-item">
                                <h4>Location</h4>
                                <p><?php echo htmlspecialchars($disaster_data['address']); ?></p>
                            </div>
                            
                            <?php if ($disaster_data['assigned_lgu_id']): ?>
                                <div class="info-item">
                                    <h4>Assigned LGU</h4>
                                    <p><?php echo htmlspecialchars($disaster_data['lgu_name']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($disaster_data['assigned_user_name']): ?>
                                <div class="info-item">
                                    <h4>Assigned Officer</h4>
                                    <p><?php echo htmlspecialchars($disaster_data['assigned_user_name']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($disaster_data['acknowledged_at']): ?>
                                <div class="info-item">
                                    <h4>Acknowledged Date</h4>
                                    <p><?php echo date('M d, Y \a\t g:i A', strtotime($disaster_data['acknowledged_at'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($disaster_data['resolved_at']): ?>
                                <div class="info-item">
                                    <h4>Resolved Date</h4>
                                    <p><?php echo date('M d, Y \a\t g:i A', strtotime($disaster_data['resolved_at'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Description Section -->
                        <?php if ($disaster_data['description']): ?>
                            <div class="report-description">
                                <h4><i class="fas fa-file-alt"></i> Report Description</h4>
                                <p><?php echo nl2br(htmlspecialchars($disaster_data['description'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Emergency Image Section -->
                        <?php if ($disaster_data['image_path'] && file_exists($disaster_data['image_path'])): ?>
                            <div class="report-image">
                                <h4><i class="fas fa-camera"></i> Emergency Photo</h4>
                                <div class="image-container">
                                    <img src="<?php echo htmlspecialchars($disaster_data['image_path']); ?>" 
                                         alt="Emergency Photo" 
                                         class="emergency-photo"
                                         onclick="openImageModal(this.src)">
                                    <div class="image-overlay">
                                        <i class="fas fa-expand-alt"></i>
                                        <span>Click to enlarge</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($disaster_data['lgu_name'] && $disaster_data['lgu_phone']): ?>
                            <div class="contact-lgu">
                                <h4><i class="fas fa-phone"></i> Contact Assigned LGU</h4>
                                <p>
                                    <strong><?php echo htmlspecialchars($disaster_data['lgu_name']); ?></strong><br>
                                    Phone: <a href="tel:<?php echo htmlspecialchars($disaster_data['lgu_phone']); ?>" class="lgu-phone"><?php echo htmlspecialchars($disaster_data['lgu_phone']); ?></a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Updates Section -->
                <div class="updates-section">
                    <div class="updates-header">
                        <h3><i class="fas fa-clock"></i> Status Updates & Communications</h3>
                        <p>Timeline of actions and communications regarding your report</p>
                    </div>
                    
                    <div class="updates-list">
                        <?php if (empty($updates)): ?>
                            <div class="no-updates">
                                <i class="fas fa-clock" style="font-size: 2em; color: #d1d5db; margin-bottom: 15px;"></i>
                                <p>No public updates available yet. You will be notified when there are updates from the LGU.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($updates as $update): ?>
                                <div class="update-item">
                                    <div class="update-content">
                                        <div class="update-title">
                                            <?php echo htmlspecialchars($update['title']); ?>
                                        </div>
                                        <div class="update-description">
                                            <?php echo htmlspecialchars($update['description']); ?>
                                        </div>
                                        <div class="update-meta">
                                            <span>
                                                <?php if ($update['user_name']): ?>
                                                    By: <?php echo htmlspecialchars($update['user_name']); ?>
                                                <?php else: ?>
                                                    System Update
                                                <?php endif; ?>
                                            </span>
                                            <span>
                                                <?php echo date('M d, Y \a\t g:i A', strtotime($update['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-shield-alt"></i>
                        <span>iMSafe System</span>
                    </div>
                    <p>Protecting communities through advanced disaster monitoring and coordinated emergency response.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#features">Features</a></li>
                        <li><a href="index.php#about">About</a></li>
                        <li><a href="admin/dashboard.php">Admin Panel</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Emergency</h4>
                    <ul>
                        <li><a href="report_emergency.php">Report Emergency</a></li>
                        <li><a href="tel:911">Call 911</a></li>
                        <li><a href="index.php#contact">Contact Support</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 iMSafe Disaster Monitoring System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-format tracking ID input
            const trackingInput = document.querySelector('input[name="tracking_id"]');
            
            trackingInput.addEventListener('input', function(e) {
                // Allow letters, numbers, and dashes - preserve the original format
                let value = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
                e.target.value = value;
            });
            
            // Auto-submit form if tracking ID is provided via URL parameter and no results are shown yet
            const urlParams = new URLSearchParams(window.location.search);
            const trackingIdFromUrl = urlParams.get('tracking_id');
            
            if (trackingIdFromUrl && trackingInput.value && !document.querySelector('.report-details')) {
                // Auto-submit the form after a short delay
                setTimeout(function() {
                    document.querySelector('form').submit();
                }, 500);
            }
            
            // Save tracking ID to local storage when searching
            document.querySelector('form').addEventListener('submit', function() {
                const trackingId = trackingInput.value.trim();
                if (trackingId) {
                    // Save to recent tracking IDs (keep last 5)
                    let recentIds = JSON.parse(localStorage.getItem('recentTrackingIds') || '[]');
                    recentIds = recentIds.filter(id => id !== trackingId); // Remove if exists
                    recentIds.unshift(trackingId); // Add to beginning
                    recentIds = recentIds.slice(0, 5); // Keep only last 5
                    localStorage.setItem('recentTrackingIds', JSON.stringify(recentIds));
                }
            });
            
            // Show recent tracking IDs (optional feature for future)
            // This could be implemented as a dropdown suggestion feature
        });
        
        // Image modal functionality
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = imageSrc;
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        // Close modal when clicking outside the image
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('image-modal')) {
                closeImageModal();
            }
        });
    </script>
    
    <!-- Image Modal -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <span class="close-modal" onclick="closeImageModal()">&times;</span>
        <img id="modalImage" src="" alt="Emergency Photo - Full Size">
    </div>
</body>
</html>