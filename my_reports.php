<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a reporter
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_role = $is_logged_in ? $_SESSION['role'] : '';
$user_name = $is_logged_in ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : '';

// Redirect if not logged in or not a reporter
if (!$is_logged_in || $user_role !== 'reporter') {
    header('Location: login.php');
    exit();
}

$user_reports = [];

// Fetch all reporter's reports
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
    <title>My Emergency Reports - iMSafe Disaster Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/images/icon2.png">
    <style>
        .my-reports-page {
            min-height: 100vh;
            padding: 50px 0 50px;
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
        
        .accordion-trigger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
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
        
        @media (max-width: 640px) {
            .user-reports-section {
                padding: 30px 20px;
            }
            
            .user-reports-section h1 {
                font-size: 1.8rem;
            }
            
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
        
        .no-reports-content p {
            color: #6b7280;
            margin-bottom: 25px;
        }
        
        .reports-summary {
            background: #eff6ff;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #3b82f6;
            margin-top: 20px;
        }
        
        .reports-summary h3 {
            margin: 0 0 8px 0;
            color: #1e40af;
        }
        
        .reports-summary p {
            margin: 0;
            color: #1e40af;
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
        
        .quick-actions {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .quick-actions h3 {
            color: #1e40af;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .action-btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .action-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        .action-btn-secondary {
            background: #f8faff;
            color: #1e40af;
            border: 2px solid #e5e7eb;
        }
        
        .action-btn-secondary:hover {
            background: #e0e7ff;
            border-color: #3b82f6;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php require_once __DIR__ . '/includes/public_nav.php'; ?>

    <!-- My Reports Page -->
    <section class="my-reports-page">
        <div class="container">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="action-buttons">
                    <a href="report_emergency.php" class="action-btn action-btn-primary">
                        <i class="fas fa-exclamation-triangle"></i>
                        Report New Emergency
                    </a>
                    <a href="track_report.php" class="action-btn action-btn-secondary">
                        <i class="fas fa-search"></i>
                        Track Report by ID
                    </a>
                </div>
            </div>
            
            <!-- User Reports Dashboard -->
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
        </div>
    </section>

    <!-- Footer -->
    <?php require_once __DIR__ . '/includes/public_footer.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const accordionItems = document.querySelectorAll('.accordion-item');
            
            if (accordionItems.length) {
                const accordionArray = Array.from(accordionItems);

                accordionArray.forEach((item) => {
                    const trigger = item.querySelector('.accordion-trigger');
                    const content = item.querySelector('.accordion-content');
                    if (!trigger || !content) {
                        return;
                    }

                    trigger.addEventListener('click', function() {
                        const isExpanded = trigger.getAttribute('aria-expanded') === 'true';

                        // Close all other accordions
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

                        // Toggle current accordion
                        if (isExpanded) {
                            trigger.setAttribute('aria-expanded', 'false');
                            content.setAttribute('hidden', '');
                        } else {
                            trigger.setAttribute('aria-expanded', 'true');
                            content.removeAttribute('hidden');
                        }
                    });
                });

                // Optionally expand the first item by default
                const firstItem = accordionArray[0];
                if (firstItem) {
                    const firstTrigger = firstItem.querySelector('.accordion-trigger');
                    const firstContent = firstItem.querySelector('.accordion-content');
                    if (firstTrigger && firstContent) {
                        firstTrigger.setAttribute('aria-expanded', 'true');
                        firstContent.removeAttribute('hidden');
                    }
                }
            }
        });
    </script>
</body>
</html>
