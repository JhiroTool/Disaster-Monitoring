<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

$page_title = 'Reports & Analytics';

// Handle report generation
$report_data = [];
$report_generated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = sanitizeInput($_POST['report_type']);
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];
    $lgu_filter = intval($_POST['lgu_filter']) ?: null;
    
    try {
        switch ($report_type) {
            case 'disaster_summary':
                $report_data = generateDisasterSummaryReport($pdo, $date_from, $date_to, $lgu_filter);
                break;
            case 'response_time':
                $report_data = generateResponseTimeReport($pdo, $date_from, $date_to, $lgu_filter);
                break;
            case 'lgu_performance':
                $report_data = generateLGUPerformanceReport($pdo, $date_from, $date_to);
                break;
            case 'disaster_trends':
                $report_data = generateDisasterTrendsReport($pdo, $date_from, $date_to);
                break;
        }
        $report_generated = true;
    } catch (Exception $e) {
        error_log("Report generation error: " . $e->getMessage());
        $error_message = "Error generating report. Please try again.";
    }
}

// Fetch LGUs for filter
try {
    $lgus_stmt = $pdo->query("SELECT lgu_id, lgu_name FROM lgus WHERE is_active = TRUE ORDER BY lgu_name");
    $lgus = $lgus_stmt->fetchAll();
} catch (Exception $e) {
    $lgus = [];
}

// Report generation functions
function generateDisasterSummaryReport($pdo, $date_from, $date_to, $lgu_filter = null) {
    $where_clause = "WHERE d.reported_at BETWEEN ? AND ?";
    $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
    
    if ($lgu_filter) {
        $where_clause .= " AND d.assigned_lgu_id = ?";
        $params[] = $lgu_filter;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            dt.type_name,
            COUNT(d.disaster_id) as total_reports,
            COUNT(CASE WHEN d.status = 'resolved' THEN 1 END) as resolved_count,
            COUNT(CASE WHEN d.priority = 'critical' THEN 1 END) as critical_count,
            AVG(CASE 
                WHEN d.acknowledged_at IS NOT NULL 
                THEN TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at) 
            END) as avg_response_hours
        FROM disasters d
        JOIN disaster_types dt ON d.type_id = dt.type_id
        {$where_clause}
        GROUP BY dt.type_id, dt.type_name
        ORDER BY total_reports DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function generateResponseTimeReport($pdo, $date_from, $date_to, $lgu_filter = null) {
    $where_clause = "WHERE d.reported_at BETWEEN ? AND ? AND d.acknowledged_at IS NOT NULL";
    $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
    
    if ($lgu_filter) {
        $where_clause .= " AND d.assigned_lgu_id = ?";
        $params[] = $lgu_filter;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            d.tracking_id,
            dt.type_name,
            d.severity_display,
            l.lgu_name,
            d.reported_at,
            d.acknowledged_at,
            TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at) as response_hours,
            CASE 
                WHEN TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at) <= 2 THEN 'Excellent'
                WHEN TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at) <= 6 THEN 'Good'
                WHEN TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at) <= 24 THEN 'Fair'
                ELSE 'Poor'
            END as performance_rating
        FROM disasters d
        JOIN disaster_types dt ON d.type_id = dt.type_id
        LEFT JOIN lgus l ON d.assigned_lgu_id = l.lgu_id
        {$where_clause}
        ORDER BY response_hours ASC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function generateLGUPerformanceReport($pdo, $date_from, $date_to) {
    $stmt = $pdo->prepare("
        SELECT 
            l.lgu_name,
            COUNT(d.disaster_id) as total_assigned,
            COUNT(CASE WHEN d.status = 'resolved' THEN 1 END) as resolved_count,
            COUNT(CASE WHEN d.acknowledged_at IS NOT NULL THEN 1 END) as acknowledged_count,
            AVG(CASE 
                WHEN d.acknowledged_at IS NOT NULL 
                THEN TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at) 
            END) as avg_response_hours,
            COUNT(CASE WHEN d.escalation_deadline < NOW() AND d.status NOT IN ('resolved', 'closed') THEN 1 END) as overdue_count
        FROM lgus l
        LEFT JOIN disasters d ON l.lgu_id = d.assigned_lgu_id 
            AND d.reported_at BETWEEN ? AND ?
        WHERE l.is_active = TRUE
        GROUP BY l.lgu_id, l.lgu_name
        HAVING total_assigned > 0
        ORDER BY avg_response_hours ASC
    ");
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    return $stmt->fetchAll();
}

function generateDisasterTrendsReport($pdo, $date_from, $date_to) {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(d.reported_at) as report_date,
            COUNT(d.disaster_id) as daily_reports,
            COUNT(CASE WHEN d.priority = 'critical' THEN 1 END) as critical_reports,
            COUNT(CASE WHEN d.status = 'resolved' THEN 1 END) as resolved_reports
        FROM disasters d
        WHERE d.reported_at BETWEEN ? AND ?
        GROUP BY DATE(d.reported_at)
        ORDER BY report_date DESC
    ");
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    return $stmt->fetchAll();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
        <p>Generate detailed reports and analyze disaster response data</p>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Report Generation Form -->
<div class="dashboard-card">
    <div class="card-header">
        <h3><i class="fas fa-cogs"></i> Generate Report</h3>
    </div>
    <div class="card-content">
        <form method="POST" class="report-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="report_type">Report Type *</label>
                    <select name="report_type" id="report_type" required>
                        <option value="">Select report type...</option>
                        <option value="disaster_summary">Disaster Summary</option>
                        <option value="response_time">Response Time Analysis</option>
                        <option value="lgu_performance">LGU Performance</option>
                        <option value="disaster_trends">Disaster Trends</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="lgu_filter">Filter by LGU (Optional)</label>
                    <select name="lgu_filter" id="lgu_filter">
                        <option value="">All LGUs</option>
                        <?php foreach ($lgus as $lgu): ?>
                            <option value="<?php echo $lgu['lgu_id']; ?>">
                                <?php echo htmlspecialchars($lgu['lgu_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date_from">Date From *</label>
                    <input type="date" name="date_from" id="date_from" required 
                           value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                </div>
                <div class="form-group">
                    <label for="date_to">Date To *</label>
                    <input type="date" name="date_to" id="date_to" required 
                           value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="generate_report" class="btn btn-primary">
                    <i class="fas fa-chart-line"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Report Results -->
<?php if ($report_generated && !empty($report_data)): ?>
<div class="dashboard-card">
    <div class="card-header">
        <h3><i class="fas fa-table"></i> Report Results</h3>
        <div class="report-actions">
            <button onclick="exportReport()" class="btn btn-secondary">
                <i class="fas fa-download"></i> Export CSV
            </button>
            <button onclick="printReport()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-content">
        <?php 
        $report_type = $_POST['report_type'];
        switch ($report_type):
            case 'disaster_summary': ?>
                <div class="report-summary">
                    <h4>Disaster Summary Report</h4>
                    <p>Period: <?php echo $_POST['date_from'] . ' to ' . $_POST['date_to']; ?></p>
                </div>
                <table id="report-table" class="table">
                    <thead>
                        <tr>
                            <th>Disaster Type</th>
                            <th>Total Reports</th>
                            <th>Resolved</th>
                            <th>Critical Cases</th>
                            <th>Avg Response Time (Hours)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                                <td><?php echo $row['total_reports']; ?></td>
                                <td><?php echo $row['resolved_count']; ?></td>
                                <td><?php echo $row['critical_count']; ?></td>
                                <td><?php echo $row['avg_response_hours'] ? number_format($row['avg_response_hours'], 1) : 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php break;
            
            case 'response_time': ?>
                <div class="report-summary">
                    <h4>Response Time Analysis</h4>
                    <p>Period: <?php echo $_POST['date_from'] . ' to ' . $_POST['date_to']; ?></p>
                </div>
                <table id="report-table" class="table">
                    <thead>
                        <tr>
                            <th>Tracking ID</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>LGU</th>
                            <th>Reported</th>
                            <th>Acknowledged</th>
                            <th>Response Time</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['tracking_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['severity_display']); ?></td>
                                <td><?php echo htmlspecialchars($row['lgu_name'] ?? 'Unassigned'); ?></td>
                                <td><?php echo date('M j, H:i', strtotime($row['reported_at'])); ?></td>
                                <td><?php echo date('M j, H:i', strtotime($row['acknowledged_at'])); ?></td>
                                <td><?php echo $row['response_hours']; ?> hours</td>
                                <td>
                                    <span class="rating-badge rating-<?php echo strtolower($row['performance_rating']); ?>">
                                        <?php echo $row['performance_rating']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php break;
            
            case 'lgu_performance': ?>
                <div class="report-summary">
                    <h4>LGU Performance Report</h4>
                    <p>Period: <?php echo $_POST['date_from'] . ' to ' . $_POST['date_to']; ?></p>
                </div>
                <table id="report-table" class="table">
                    <thead>
                        <tr>
                            <th>LGU Name</th>
                            <th>Total Assigned</th>
                            <th>Acknowledged</th>
                            <th>Resolved</th>
                            <th>Overdue</th>
                            <th>Avg Response (Hours)</th>
                            <th>Efficiency</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <?php $efficiency = $row['total_assigned'] > 0 ? ($row['resolved_count'] / $row['total_assigned']) * 100 : 0; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['lgu_name']); ?></td>
                                <td><?php echo $row['total_assigned']; ?></td>
                                <td><?php echo $row['acknowledged_count']; ?></td>
                                <td><?php echo $row['resolved_count']; ?></td>
                                <td><?php echo $row['overdue_count']; ?></td>
                                <td><?php echo $row['avg_response_hours'] ? number_format($row['avg_response_hours'], 1) : 'N/A'; ?></td>
                                <td><?php echo number_format($efficiency, 1); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php break;
            
            case 'disaster_trends': ?>
                <div class="report-summary">
                    <h4>Disaster Trends Report</h4>
                    <p>Period: <?php echo $_POST['date_from'] . ' to ' . $_POST['date_to']; ?></p>
                </div>
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
                <table id="report-table" class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Reports</th>
                            <th>Critical Reports</th>
                            <th>Resolved Reports</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($row['report_date'])); ?></td>
                                <td><?php echo $row['daily_reports']; ?></td>
                                <td><?php echo $row['critical_reports']; ?></td>
                                <td><?php echo $row['resolved_reports']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
        <?php endswitch; ?>
    </div>
</div>
<?php elseif ($report_generated && empty($report_data)): ?>
<div class="dashboard-card">
    <div class="card-content">
        <div class="empty-state">
            <i class="fas fa-chart-bar"></i>
            <h3>No data found</h3>
            <p>No data available for the selected criteria and date range.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.report-form {
    max-width: 800px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.report-actions {
    display: flex;
    gap: 10px;
}

.report-summary {
    margin-bottom: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: var(--border-radius);
}

.report-summary h4 {
    margin: 0 0 5px 0;
    color: var(--text-color);
}

.report-summary p {
    margin: 0;
    color: var(--text-muted);
}

.rating-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.rating-excellent {
    background-color: #e8f5e8;
    color: #388e3c;
}

.rating-good {
    background-color: #e3f2fd;
    color: #1976d2;
}

.rating-fair {
    background-color: #fff3e0;
    color: #f57c00;
}

.rating-poor {
    background-color: #ffebee;
    color: #d32f2f;
}

.chart-container {
    margin: 20px 0;
    height: 400px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function exportReport() {
    const table = document.getElementById('report-table');
    if (!table) return;
    
    let csv = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead tr th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Get data rows
    table.querySelectorAll('tbody tr').forEach(row => {
        const rowData = [];
        row.querySelectorAll('td').forEach(td => {
            let cellText = td.textContent.trim().replace(/,/g, ';');
            rowData.push('"' + cellText + '"');
        });
        csv.push(rowData.join(','));
    });
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'disaster-report-' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printReport() {
    window.print();
}

// Initialize trends chart if data exists
<?php if ($report_generated && $_POST['report_type'] === 'disaster_trends' && !empty($report_data)): ?>
const trendsData = <?php echo json_encode(array_reverse($report_data)); ?>;
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: trendsData.map(item => new Date(item.report_date).toLocaleDateString()),
        datasets: [
            {
                label: 'Total Reports',
                data: trendsData.map(item => item.daily_reports),
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4
            },
            {
                label: 'Critical Reports',
                data: trendsData.map(item => item.critical_reports),
                borderColor: '#FF6384',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4
            },
            {
                label: 'Resolved Reports',
                data: trendsData.map(item => item.resolved_reports),
                borderColor: '#4BC0C0',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'Daily Disaster Report Trends'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
<?php endif; ?>

// ====================================
// REAL-TIME INTEGRATION
// ====================================
if (window.realtimeSystem) {
    // Listen for new reports
    window.realtimeSystem.registerCallback('onNewReport', (data) => {
        showNewReportNotification(data);
    });
    
    // Listen for general updates
    window.realtimeSystem.registerCallback('onUpdate', (data) => {
        if (data.stats && data.stats.total_disasters !== undefined) {
            updateReportIndicator(data.stats);
        }
    });
    
    console.log('✅ Real-time updates enabled for reports page');
} else {
    console.warn('⚠️ RealtimeSystem not available on reports page');
}

function showNewReportNotification(data) {
    const banner = document.createElement('div');
    banner.className = 'realtime-notification-banner';
    banner.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 10000;
        min-width: 320px;
        animation: slideInRight 0.4s ease-out;
        font-family: 'Inter', sans-serif;
    `;
    
    banner.innerHTML = `
        <div style="display: flex; align-items: start; gap: 12px;">
            <i class="fas fa-file-alt" style="font-size: 24px; margin-top: 2px;"></i>
            <div style="flex: 1;">
                <strong style="display: block; margin-bottom: 4px; font-size: 16px;">
                    📊 New Disaster Report
                </strong>
                <div style="font-size: 14px; opacity: 0.95; margin-bottom: 8px;">
                    ${data.disaster_name || 'New disaster'} reported in ${data.city || 'Unknown location'}
                </div>
                <button onclick="location.reload()" style="
                    background: white;
                    color: #667eea;
                    border: none;
                    padding: 6px 12px;
                    border-radius: 6px;
                    font-weight: 600;
                    cursor: pointer;
                    font-size: 13px;
                    margin-right: 8px;
                " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <i class="fas fa-sync-alt"></i> Refresh Reports
                </button>
                <button onclick="this.closest('.realtime-notification-banner').remove()" style="
                    background: transparent;
                    color: white;
                    border: 1px solid white;
                    padding: 6px 12px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 13px;
                ">
                    Dismiss
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(banner);
    
    // Auto-remove after 15 seconds
    setTimeout(() => {
        if (banner.parentElement) {
            banner.style.animation = 'slideOutRight 0.4s ease-out';
            setTimeout(() => banner.remove(), 400);
        }
    }, 15000);
}

function updateReportIndicator(stats) {
    // Add a subtle indicator that new data is available
    const pageHeader = document.querySelector('.page-header h1, .content-header h1');
    if (pageHeader && !document.querySelector('.report-update-indicator')) {
        const indicator = document.createElement('span');
        indicator.className = 'report-update-indicator';
        indicator.innerHTML = '<i class="fas fa-circle" style="color: #10b981; font-size: 8px; margin-left: 8px; animation: blink 1s infinite;"></i>';
        indicator.title = 'New reports available';
        pageHeader.appendChild(indicator);
        
        setTimeout(() => indicator.remove(), 5000);
    }
}

// Add animations
if (!document.querySelector('#reports-page-animations')) {
    const style = document.createElement('style');
    style.id = 'reports-page-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
    `;
    document.head.appendChild(style);
}
</script>

<?php include 'includes/footer.php'; ?>