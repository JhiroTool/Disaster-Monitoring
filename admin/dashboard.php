<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth.php';

$page_title = 'Dashboard';

// Fetch dashboard statistics
try {
    // Total disasters
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters");
    $total_disasters = $stmt->fetch()['total'];
    
    // Active disasters (not resolved or closed)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status NOT IN ('resolved', 'closed')");
    $active_disasters = $stmt->fetch()['total'];
    
    // Critical disasters
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE priority = 'critical' AND status NOT IN ('resolved', 'closed')");
    $critical_disasters = $stmt->fetch()['total'];
    
    // Pending disasters (awaiting assignment/acknowledgment)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status IN ('pending', 'assigned')");
    $pending_disasters = $stmt->fetch()['total'];
    
    // Overdue disasters (past escalation deadline)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE escalation_deadline < NOW() AND status NOT IN ('resolved', 'closed')");
    $overdue_disasters = $stmt->fetch()['total'];
    
    // Recent disasters (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE reported_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recent_disasters = $stmt->fetch()['total'];
    
    // Get recent disaster reports
    $stmt = $pdo->prepare("
        SELECT d.disaster_id, d.tracking_id, d.disaster_name, dt.type_name, d.severity_level, 
               d.severity_display, d.city, d.status, d.priority, d.reported_at,
               lgu.lgu_name, TIMESTAMPDIFF(HOUR, d.reported_at, NOW()) as hours_ago
        FROM disasters d
        JOIN disaster_types dt ON d.type_id = dt.type_id
        LEFT JOIN lgus lgu ON d.assigned_lgu_id = lgu.lgu_id
        ORDER BY d.reported_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_reports = $stmt->fetchAll();
    
    // Get disaster statistics by type
    $stmt = $pdo->query("
        SELECT dt.type_name, COUNT(d.disaster_id) as count
        FROM disaster_types dt
        LEFT JOIN disasters d ON dt.type_id = d.type_id
        WHERE dt.is_active = TRUE
        GROUP BY dt.type_id, dt.type_name
        ORDER BY count DESC
        LIMIT 6
    ");
    $disaster_types_stats = $stmt->fetchAll();
    
    // Get severity distribution
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN severity_level LIKE 'red-%' THEN 'Critical'
                WHEN severity_level LIKE 'orange-%' THEN 'Moderate'
                WHEN severity_level LIKE 'green-%' THEN 'Minor'
                ELSE 'Unknown'
            END as severity_category,
            COUNT(*) as count
        FROM disasters 
        WHERE reported_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY severity_category
    ");
    $severity_stats = $stmt->fetchAll();
    
    // Get LGU performance (top 5 by response time)
    $stmt = $pdo->query("
        SELECT lgu.lgu_name, 
               COUNT(d.disaster_id) as total_reports,
               AVG(TIMESTAMPDIFF(HOUR, d.reported_at, d.acknowledged_at)) as avg_response_hours,
               COUNT(CASE WHEN d.status = 'resolved' THEN 1 END) as resolved_count
        FROM lgus lgu
        LEFT JOIN disasters d ON lgu.lgu_id = d.assigned_lgu_id AND d.acknowledged_at IS NOT NULL
        WHERE lgu.is_active = TRUE
        GROUP BY lgu.lgu_id, lgu.lgu_name
        HAVING total_reports > 0
        ORDER BY avg_response_hours ASC
        LIMIT 5
    ");
    $lgu_performance = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    // Set default values
    $total_disasters = $active_disasters = $critical_disasters = $pending_disasters = $overdue_disasters = $recent_disasters = 0;
    $recent_reports = $disaster_types_stats = $severity_stats = $lgu_performance = [];
}

include 'includes/header.php';
?>

<div class="dashboard-grid">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon critical">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($critical_disasters); ?></div>
                <div class="stat-label">Critical Alerts</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($overdue_disasters); ?></div>
                <div class="stat-label">Overdue Reports</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon active">
                <i class="fas fa-fire"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($active_disasters); ?></div>
                <div class="stat-label">Active Disasters</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($pending_disasters); ?></div>
                <div class="stat-label">Pending Response</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($recent_disasters); ?></div>
                <div class="stat-label">This Week</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-database"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($total_disasters); ?></div>
                <div class="stat-label">Total Reports</div>
            </div>
        </div>
    </div>
    
    <!-- Recent Disasters -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Recent Disaster Reports</h3>
            <a href="disasters.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="card-content">
            <?php if (empty($recent_reports)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No recent reports</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tracking ID</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_reports as $report): ?>
                                <tr>
                                    <td>
                                        <span class="tracking-id"><?php echo htmlspecialchars($report['tracking_id']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($report['type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($report['city'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="severity-badge severity-<?php echo substr($report['severity_level'], 0, strpos($report['severity_level'], '-')); ?>">
                                            <?php echo htmlspecialchars($report['severity_display']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $report['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-ago"><?php echo $report['hours_ago']; ?>h ago</span>
                                    </td>
                                    <td>
                                        <a href="disaster-details.php?id=<?php echo $report['disaster_id']; ?>" class="btn btn-xs btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Disaster Types Distribution</h3>
        </div>
        <div class="card-content">
            <canvas id="disasterTypesChart" height="300"></canvas>
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar"></i> Severity Levels (30 Days)</h3>
        </div>
        <div class="card-content">
            <canvas id="severityChart" height="300"></canvas>
        </div>
    </div>
    
    <!-- LGU Performance -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-building"></i> LGU Response Performance</h3>
            <small>Top 5 by response time</small>
        </div>
        <div class="card-content">
            <?php if (empty($lgu_performance)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-bar"></i>
                    <p>No performance data available</p>
                </div>
            <?php else: ?>
                <div class="performance-list">
                    <?php foreach ($lgu_performance as $index => $lgu): ?>
                        <div class="performance-item">
                            <div class="performance-rank"><?php echo $index + 1; ?></div>
                            <div class="performance-info">
                                <div class="lgu-name"><?php echo htmlspecialchars($lgu['lgu_name']); ?></div>
                                <div class="performance-stats">
                                    <span class="stat">
                                        <i class="fas fa-clock"></i>
                                        <?php echo number_format($lgu['avg_response_hours'], 1); ?>h avg response
                                    </span>
                                    <span class="stat">
                                        <i class="fas fa-list"></i>
                                        <?php echo $lgu['total_reports']; ?> reports
                                    </span>
                                    <span class="stat">
                                        <i class="fas fa-check"></i>
                                        <?php echo $lgu['resolved_count']; ?> resolved
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        </div>
        <div class="card-content">
            <div class="quick-actions">
                <a href="disasters.php?status=pending" class="quick-action">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Review Pending Reports</span>
                </a>
                <a href="announcements.php" class="quick-action">
                    <i class="fas fa-bullhorn"></i>
                    <span>Create Announcement</span>
                </a>
                <a href="users.php" class="quick-action">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="reports.php" class="quick-action">
                    <i class="fas fa-chart-bar"></i>
                    <span>Generate Report</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Disaster Types Chart
const disasterTypesData = <?php echo json_encode($disaster_types_stats); ?>;
const disasterTypesCtx = document.getElementById('disasterTypesChart').getContext('2d');
new Chart(disasterTypesCtx, {
    type: 'doughnut',
    data: {
        labels: disasterTypesData.map(item => item.type_name),
        datasets: [{
            data: disasterTypesData.map(item => item.count),
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Severity Chart
const severityData = <?php echo json_encode($severity_stats); ?>;
const severityCtx = document.getElementById('severityChart').getContext('2d');
new Chart(severityCtx, {
    type: 'bar',
    data: {
        labels: severityData.map(item => item.severity_category),
        datasets: [{
            label: 'Reports',
            data: severityData.map(item => item.count),
            backgroundColor: ['#dc3545', '#fd7e14', '#28a745', '#6c757d']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Auto-refresh data every 30 seconds
setInterval(() => {
    location.reload();
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>