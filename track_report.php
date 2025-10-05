<?php
session_start();
require_once 'config/database.php';

$tracking_result = null;
$disaster_data = null;
$updates = [];
$user_reports = [];

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_role = $is_logged_in ? $_SESSION['role'] : '';
$user_name = $is_logged_in ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : '';

// If logged in as reporter, fetch all their reports
if ($is_logged_in && $user_role === 'reporter') {
    try {
        $user_reports_stmt = $pdo->prepare("
            SELECT d.*, dt.type_name, l.lgu_name, 
                   DATE_FORMAT(d.created_at, '%M %d, %Y at %h:%i %p') as formatted_date,
                   DATE_FORMAT(d.created_at, '%Y-%m-%d') as date_only
            FROM disasters d
            LEFT JOIN disaster_types dt ON d.type_id = dt.type_id
            LEFT JOIN lgus l ON d.assigned_lgu_id = l.lgu_id
            WHERE d.reported_by_user_id = ?
            ORDER BY d.created_at DESC
        ");
        $user_reports_stmt->execute([$user_id]);
        $user_reports = $user_reports_stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching user reports: " . $e->getMessage());
    }
}

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
                    SELECT du.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.role as user_role
                    FROM disaster_updates du
                    LEFT JOIN users u ON du.user_id = u.user_id
                    WHERE du.disaster_id = ?
                    ORDER BY du.created_at ASC
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
        case 'ON GOING': return 'status-on-going';
        case 'IN PROGRESS': return 'status-in-progress';
        case 'COMPLETED': return 'status-completed';
        default: return 'status-on-going';
    }
}

// Function to get status display text
function getStatusDisplayText($status) {
    switch ($status) {
        case 'ON GOING': return 'On Going';
        case 'IN PROGRESS': return 'In Progress';
        case 'COMPLETED': return 'Completed';
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
            padding: 50px 0 0px;
            background: linear-gradient(135deg, #f8faff 0%, #e6f3ff 100%);
        }
        
        /* User Reports Section */
        .user-reports-section {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .user-reports-section h1 {
            color: #1e40af;
            margin-bottom: 10px;
            font-size: 2.2rem;
        }
        
        .section-subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .reports-accordion {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-bottom: 30px;
        }
        
        .accordion-item {
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            background: #f8faff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        
        .accordion-trigger {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);
            color: white;
            padding: 18px 24px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-align: left;
        }
        
        .accordion-trigger:focus-visible {
            outline: 3px solid rgba(59, 130, 246, 0.6);
            outline-offset: 2px;
        }
        
        .trigger-main {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
        
        .trigger-top-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }
        
        .trigger-title {
            font-size: 1.1rem;
            letter-spacing: 1px;
        }
        
        .trigger-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }
        
        .trigger-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .accordion-chevron {
            transition: transform 0.3s ease;
            font-size: 1.3rem;
        }
        
        .accordion-trigger[aria-expanded="true"] .accordion-chevron {
            transform: rotate(180deg);
        }
        
        .accordion-content {
            background: white;
            padding: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .accordion-content[hidden] {
            display: none;
        }
        
        .accordion-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 20px;
        }
        
        .accordion-detail {
            background: #f8faff;
            border-radius: 10px;
            padding: 16px;
            border-left: 4px solid #3b82f6;
        }
        
        .accordion-detail h4 {
            margin: 0 0 6px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1f2937;
        }
        
        .accordion-detail p {
            margin: 0;
            color: #1e293b;
            font-weight: 600;
        }
        
        .accordion-description {
            margin-bottom: 20px;
        }
        
        .accordion-description h4 {
            margin: 0 0 8px;
            font-size: 1rem;
            color: #1f2937;
        }
        
        .accordion-description p {
            margin: 0;
            color: #475569;
            line-height: 1.6;
        }
        
        .accordion-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px 16px;
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
        }
        
        .accordion-footer-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .tracking-inline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .accordion-footer a.btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            background: #1e40af;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s ease;
        }
        
        .accordion-footer a.btn:hover {
            background: #1d4ed8;
        }
        
        @media (max-width: 640px) {
            .accordion-trigger {
                padding: 16px 18px;
                align-items: flex-start;
            }
        
            .accordion-details-grid {
                grid-template-columns: 1fr;
            }
        
            .accordion-content {
                padding: 20px;
            }
        }
        
        .no-reports {
            text-align: center;
            padding: 60px 20px;
        }
        
        .no-reports-content i {
            font-size: 4rem;
            color: #9ca3af;
            margin-bottom: 20px;
        }
        
        .no-reports-content h3 {
            color: #4b5563;
            margin-bottom: 15px;
        }
        
        .reports-summary {
            background: #eff6ff;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #3b82f6;
            margin-top: 20px;
        }
        
        .divider {
            text-align: center;
            margin: 40px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #d1d5db;
        }
        
        .divider span {
            background: #f8faff;
            color: #6b7280;
            padding: 0 20px;
            font-weight: 500;
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
        
        .status-on-going { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #e0e7ff; color: #3730a3; }
        .status-completed { background: #d1fae5; color: #065f46; }
        
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
            margin: 12px 0 18px 0;
            padding: 22px 24px;
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 12px rgba(102, 126, 234, 0.10);
        }
        
        .report-description h4,
        .report-image h4 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #4f46e5;
            font-weight: 700;
        }
        
        .report-description p {
            color: #1e293b;
            line-height: 1.7;
            margin: 0;
            font-size: 1.08em;
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
                <a href="register.php" class="nav-link">Register</a>
                <a href="login.php" class="nav-link btn-login">Admin Login</a>
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
            
            <!-- User Reports Dashboard (shown only for logged-in reporters) -->
            <?php if ($is_logged_in && $user_role === 'reporter'): ?>
                <div class="user-reports-section">
                    <h1><i class="fas fa-list-alt"></i> My Emergency Reports</h1>
                    <p class="section-subtitle">Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong>! Here are all your submitted emergency reports and their current status.</p>
                    
                    <?php if (empty($user_reports)): ?>
                        <div class="no-reports">
                            <div class="no-reports-content">
                                <i class="fas fa-clipboard-list"></i>
                                <h3>No Reports Yet</h3>
                                <p>You haven't submitted any emergency reports yet.</p>
                                <a href="report_emergency.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Submit Your First Report
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="reports-accordion" role="list">
                            <?php foreach ($user_reports as $report): ?>
                                <?php
                                    $reportLocation = trim($report['address'] ?? '') ?: trim($report['city'] ?? '') ?: 'Location not specified';
                                    $reportType = $report['type_name'] ?? 'Emergency Report';
                                    $assignedLgu = !empty($report['lgu_name']) ? $report['lgu_name'] : 'Pending assignment';
                                    $priorityValue = $report['priority'] ?? 'medium';
                                    $panelId = 'report-panel-' . $report['disaster_id'];
                                    $buttonId = 'report-trigger-' . $report['disaster_id'];
                                ?>
                                <div class="accordion-item" role="listitem" data-tracking-id="<?php echo htmlspecialchars($report['tracking_id']); ?>">
                                    <button class="accordion-trigger" type="button"
                                            id="<?php echo htmlspecialchars($buttonId); ?>"
                                            aria-expanded="false"
                                            aria-controls="<?php echo htmlspecialchars($panelId); ?>">
                                        <div class="trigger-main">
                                            <div class="trigger-top-row">
                                                <span class="trigger-title">ID: <?php echo htmlspecialchars($report['tracking_id']); ?></span>
                                                <div class="status-badge <?php echo getStatusBadgeClass($report['status'] ?? 'ON GOING'); ?>">
                                                    <?php echo getStatusDisplayText($report['status'] ?? 'ON GOING'); ?>
                                                </div>
                                            </div>
                                            <div class="trigger-meta">
                                                <span><i class="fas fa-layer-group"></i><?php echo htmlspecialchars($reportType); ?></span>
                                                <span><i class="fas fa-calendar-alt"></i><?php echo htmlspecialchars($report['formatted_date']); ?></span>
                                                <span><i class="fas fa-map-marker-alt"></i><?php echo htmlspecialchars($reportLocation); ?></span>
                                            </div>
                                        </div>
                                        <i class="fas fa-chevron-down accordion-chevron" aria-hidden="true"></i>
                                    </button>
                                    <div class="accordion-content" id="<?php echo htmlspecialchars($panelId); ?>"
                                         role="region" aria-labelledby="<?php echo htmlspecialchars($buttonId); ?>" hidden>
                                        <div class="accordion-details-grid">
                                            <div class="accordion-detail">
                                                <h4>Report Title</h4>
                                                <p><?php echo htmlspecialchars($report['disaster_name'] ?? $reportType); ?></p>
                                            </div>
                                            <div class="accordion-detail">
                                                <h4>Location</h4>
                                                <p><?php echo htmlspecialchars($reportLocation); ?></p>
                                            </div>
                                            <div class="accordion-detail">
                                                <h4>Reported On</h4>
                                                <p><?php echo htmlspecialchars($report['formatted_date']); ?></p>
                                            </div>
                                            <div class="accordion-detail">
                                                <h4>Assigned LGU</h4>
                                                <p><?php echo htmlspecialchars($assignedLgu); ?></p>
                                            </div>
                                        </div>
                                        <?php if (!empty($report['description'])): ?>
                                            <div class="accordion-description">
                                                <h4>What happened</h4>
                                                <p><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <div class="accordion-footer">
                                            <div class="accordion-footer-left">
                                                <div class="tracking-inline">
                                                    <i class="fas fa-hashtag"></i>
                                                    <span>Tracking ID: <strong><?php echo htmlspecialchars($report['tracking_id']); ?></strong></span>
                                                </div>
                                                <div class="priority-badge <?php echo getPriorityBadgeClass($priorityValue); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($priorityValue)); ?> Priority
                                                </div>
                                            </div>
                                            <a href="track_report.php?tracking_id=<?php echo htmlspecialchars($report['tracking_id']); ?>" class="btn">
                                                <i class="fas fa-eye"></i>
                                                View Full Timeline
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="reports-summary">
                            <h3>📊 Summary</h3>
                            <p>You have submitted <strong><?php echo count($user_reports); ?></strong> emergency report(s) in total.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="divider">
                    <span>Or track a specific report by ID</span>
                </div>
            <?php endif; ?>
            
            <div class="tracking-form" id="track-report-form">
                <?php if ($is_logged_in && $user_role === 'reporter'): ?>
                    <h1><i class="fas fa-search"></i> Track a Specific Report</h1>
                    <p>Enter a tracking ID to view detailed information about a specific emergency report.</p>
                <?php else: ?>
                    <h1><i class="fas fa-search"></i> Track Your Emergency Report</h1>
                    <p>Enter your tracking ID to check the status and updates of your emergency report.</p>
                <?php endif; ?>
                
                <?php if (!empty($auto_tracking_id) && $tracking_result === null): ?>
                    <div class="auto-fill-notice">
                        <i class="fas fa-spinner fa-spin"></i>
                        Automatically searching for tracking ID: <?php echo htmlspecialchars($auto_tracking_id); ?>
                    </div>
                    <script>
                        // Immediately submit the form when tracking ID is provided via URL
                        document.addEventListener('DOMContentLoaded', function() {
                            document.querySelector('form').submit();
                        });
                    </script>
                <?php elseif (!empty($auto_tracking_id)): ?>
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
            
            <?php if ($disaster_data): ?>
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
                                            <?php if (!empty($update['user_role']) && $update['user_role'] === 'admin'): ?>
                                                <span style="background:#e0e7ff;color:#3730a3;font-size:12px;padding:2px 8px;border-radius:8px;margin-left:8px;">Admin</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="update-description">
                                            <?php echo nl2br(htmlspecialchars($update['description'])); ?>
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
                        <li><a href="register.php">Register as Reporter</a></li>
                        <li><a href="login.php">Admin Login</a></li>
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
            
            if (trackingInput) {
                trackingInput.addEventListener('input', function(e) {
                    // Allow letters, numbers, and dashes - preserve the original format
                    let value = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
                    e.target.value = value;
                });
            }
            
            const trackingForm = document.querySelector('form');
            if (trackingForm && trackingInput) {
                // Save tracking ID to local storage when searching
                trackingForm.addEventListener('submit', function() {
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
            }
            
            const accordionItems = document.querySelectorAll('.accordion-item');
            const autoTrackingId = <?php echo json_encode($auto_tracking_id); ?>;
            if (accordionItems.length) {
                const accordionArray = Array.from(accordionItems);
                const normalizedTrackingId = autoTrackingId ? autoTrackingId.toUpperCase() : '';

                accordionArray.forEach((item) => {
                    const trigger = item.querySelector('.accordion-trigger');
                    const content = item.querySelector('.accordion-content');
                    if (!trigger || !content) {
                        return;
                    }

                    trigger.addEventListener('click', function() {
                        const isExpanded = trigger.getAttribute('aria-expanded') === 'true';

                        accordionArray.forEach((otherItem) => {
                            if (otherItem === item) {
                                return;
                            }
                            const otherTrigger = otherItem.querySelector('.accordion-trigger');
                            const otherContent = otherItem.querySelector('.accordion-content');
                            if (otherTrigger && otherContent) {
                                otherTrigger.setAttribute('aria-expanded', 'false');
                                otherContent.setAttribute('hidden', '');
                            }
                        });

                        if (isExpanded) {
                            trigger.setAttribute('aria-expanded', 'false');
                            content.setAttribute('hidden', '');
                        } else {
                            trigger.setAttribute('aria-expanded', 'true');
                            content.removeAttribute('hidden');
                        }
                    });
                });

                let defaultItem = null;
                if (normalizedTrackingId) {
                    defaultItem = accordionArray.find((item) => {
                        const datasetId = (item.dataset.trackingId || '').toUpperCase();
                        return datasetId === normalizedTrackingId;
                    });
                }

                if (!defaultItem) {
                    defaultItem = accordionArray[0];
                }

                if (defaultItem) {
                    const defaultTrigger = defaultItem.querySelector('.accordion-trigger');
                    const defaultContent = defaultItem.querySelector('.accordion-content');
                    if (defaultTrigger && defaultContent) {
                        defaultTrigger.setAttribute('aria-expanded', 'true');
                        defaultContent.removeAttribute('hidden');
                    }
                }
            }
            
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