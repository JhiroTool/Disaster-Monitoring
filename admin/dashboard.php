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
    
    // Active disasters (not completed)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status != 'COMPLETED'");
    $active_disasters = $stmt->fetch()['total'];
    
    // Critical disasters
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE priority = 'critical' AND status != 'COMPLETED'");
    $critical_disasters = $stmt->fetch()['total'];
    
    // Pending disasters (awaiting assignment/acknowledgment)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status = 'ON GOING'");
    $pending_disasters = $stmt->fetch()['total'];
    
    // Overdue disasters (past escalation deadline)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE escalation_deadline < NOW() AND status != 'COMPLETED'");
    $overdue_disasters = $stmt->fetch()['total'];
    
    // Recent disasters (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE reported_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recent_disasters = $stmt->fetch()['total'];
    
    // Calculate trends (compare with previous period)
    // Total disasters trend
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE reported_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND reported_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $prev_month_total = $stmt->fetch()['total'];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE reported_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $current_month_total = $stmt->fetch()['total'];
    $total_trend = $prev_month_total > 0 ? round((($current_month_total - $prev_month_total) / $prev_month_total) * 100, 1) : 0;
    
    // Active disasters trend
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status != 'COMPLETED' AND reported_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $active_current = $stmt->fetch()['total'];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status != 'COMPLETED' AND reported_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND reported_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $active_prev = $stmt->fetch()['total'];
    $active_trend = $active_prev > 0 ? round((($active_current - $active_prev) / $active_prev) * 100, 1) : 0;
    
    // Critical disasters trend
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE priority = 'critical' AND reported_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $critical_current = $stmt->fetch()['total'];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE priority = 'critical' AND reported_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND reported_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $critical_prev = $stmt->fetch()['total'];
    $critical_trend = $critical_prev > 0 ? round((($critical_current - $critical_prev) / $critical_prev) * 100, 1) : 0;
    
    // Completion rate trend
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status = 'COMPLETED' AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $completed_current = $stmt->fetch()['total'];
    $current_completion_rate = $current_month_total > 0 ? round(($completed_current / $current_month_total) * 100, 1) : 0;
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disasters WHERE status = 'COMPLETED' AND resolved_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND resolved_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $completed_prev = $stmt->fetch()['total'];
    $prev_completion_rate = $prev_month_total > 0 ? round(($completed_prev / $prev_month_total) * 100, 1) : 0;
    $completion_trend = $current_completion_rate - $prev_completion_rate;
    
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
    
    // Get status distribution
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM disasters 
        GROUP BY status
        ORDER BY count DESC
    ");
    $status_stats = $stmt->fetchAll();
    
    // Get monthly trend (last 12 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(reported_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM disasters 
        WHERE reported_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(reported_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_trend = $stmt->fetchAll();
    
    // Get response time statistics
    $stmt = $pdo->query("
        SELECT 
            AVG(TIMESTAMPDIFF(HOUR, reported_at, acknowledged_at)) as avg_acknowledgment_time,
            AVG(TIMESTAMPDIFF(HOUR, reported_at, resolved_at)) as avg_resolution_time,
            COUNT(CASE WHEN acknowledged_at IS NOT NULL THEN 1 END) as acknowledged_count,
            COUNT(CASE WHEN resolved_at IS NOT NULL THEN 1 END) as resolved_count
        FROM disasters 
        WHERE acknowledged_at IS NOT NULL OR resolved_at IS NOT NULL
    ");
    $response_data = $stmt->fetch();
    $avg_acknowledgment_time = $response_data['avg_acknowledgment_time'] ?? 0;
    $avg_resolution_time = $response_data['avg_resolution_time'] ?? 0;
    $acknowledged_count = $response_data['acknowledged_count'] ?? 0;
    $resolved_count = $response_data['resolved_count'] ?? 0;
    
    // Calculate completion rate
    $completion_rate = $total_disasters > 0 ? round(($resolved_count / $total_disasters) * 100, 1) : 0;
    
    // Get LGU distribution for pie chart
    $stmt = $pdo->query("
        SELECT 
            COALESCE(l.lgu_name, 'Unassigned') as lgu_name,
            COUNT(d.disaster_id) as count
        FROM disasters d
        LEFT JOIN lgus l ON d.assigned_lgu_id = l.lgu_id
        GROUP BY l.lgu_id, l.lgu_name
        ORDER BY count DESC
        LIMIT 8
    ");
    $lgu_distribution = $stmt->fetchAll();
    
    // Get response time breakdown for bar graph
    $stmt = $pdo->query("
        SELECT 
            'Acknowledgment' as metric,
            ROUND(AVG(TIMESTAMPDIFF(HOUR, reported_at, acknowledged_at)), 1) as avg_hours
        FROM disasters 
        WHERE acknowledged_at IS NOT NULL
        UNION ALL
        SELECT 
            'In Progress' as metric,
            ROUND(AVG(TIMESTAMPDIFF(HOUR, acknowledged_at, resolved_at)), 1) as avg_hours
        FROM disasters 
        WHERE acknowledged_at IS NOT NULL AND resolved_at IS NOT NULL
        UNION ALL
        SELECT 
            'Resolution' as metric,
            ROUND(AVG(TIMESTAMPDIFF(HOUR, reported_at, resolved_at)), 1) as avg_hours
        FROM disasters 
        WHERE resolved_at IS NOT NULL
    ");
    $response_time_breakdown = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    // Set default values
    $total_disasters = $active_disasters = $critical_disasters = $pending_disasters = $overdue_disasters = $recent_disasters = 0;
    $completion_rate = $avg_acknowledgment_time = $avg_resolution_time = $acknowledged_count = $resolved_count = 0;
    $total_trend = $active_trend = $critical_trend = $completion_trend = 0;
    $recent_reports = $disaster_types_stats = $severity_stats = $status_stats = $monthly_trend = [];
}

include 'includes/header.php';
?>

<div class="dashboard-header">
    <div class="header-content">
        <h1>Dashboard Overview</h1>
        <p>Welcome back! Here's what's happening with disaster reports today.</p>
        <p id="realtime-status" style="color: #10b981; font-size: 0.875rem; margin-top: 0.5rem;">
            <i class="fas fa-circle" style="font-size: 0.5rem; animation: pulse 2s infinite;"></i> 
            Real-time updates active (optimized)
        </p>
    </div>
    <button class="btn-refresh" id="refresh-dashboard" onclick="manualRefresh()" style="display: none;">
        <i class="fas fa-sync-alt"></i> Refresh Data
    </button>
</div>

<div class="dashboard-grid">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Reports</span>
                <div class="stat-icon-container blue">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
            <div class="stat-body">
                <h2 class="stat-number" id="total-disasters"><?php echo number_format($total_disasters); ?></h2>
                <div class="stat-trend <?php echo $total_trend >= 0 ? 'trend-up' : 'trend-down'; ?>">
                    <i class="fas fa-arrow-<?php echo $total_trend >= 0 ? 'up' : 'down'; ?>"></i>
                    <span><?php echo abs($total_trend); ?>% from last month</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Active Disasters</span>
                <div class="stat-icon-container orange">
                    <i class="fas fa-fire"></i>
                </div>
            </div>
            <div class="stat-body">
                <h2 class="stat-number" id="active-disasters"><?php echo number_format($active_disasters); ?></h2>
                <div class="stat-trend <?php echo $active_trend >= 0 ? 'trend-up' : 'trend-down'; ?>">
                    <i class="fas fa-arrow-<?php echo $active_trend >= 0 ? 'up' : 'down'; ?>"></i>
                    <span><?php echo abs($active_trend); ?>% from last month</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Critical Alerts</span>
                <div class="stat-icon-container red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="stat-body">
                <h2 class="stat-number" id="critical-disasters"><?php echo number_format($critical_disasters); ?></h2>
                <div class="stat-trend <?php echo $critical_trend >= 0 ? 'trend-up' : 'trend-down'; ?>">
                    <i class="fas fa-arrow-<?php echo $critical_trend >= 0 ? 'up' : 'down'; ?>"></i>
                    <span><?php echo abs($critical_trend); ?>% from last month</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Completion Rate</span>
                <div class="stat-icon-container green">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-body">
                <h2 class="stat-number" id="pending-disasters"><?php echo number_format($completion_rate, 1); ?>%</h2>
                <div class="stat-trend <?php echo $completion_trend >= 0 ? 'trend-up' : 'trend-down'; ?>">
                    <i class="fas fa-arrow-<?php echo $completion_trend >= 0 ? 'up' : 'down'; ?>"></i>
                    <span><?php echo abs($completion_trend); ?>% from last month</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="charts-row">
        <div class="dashboard-card chart-large">
            <div class="card-header">
                <div>
                    <h3>Reports Overview</h3>
                    <p class="card-subtitle">Monthly trend for the last 12 months</p>
                </div>
                <select class="chart-filter" id="trendFilter">
                    <option value="12">Last 12 months</option>
                    <option value="6">Last 6 months</option>
                    <option value="3">Last 3 months</option>
                </select>
            </div>
            <div class="card-content">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
        
        <div class="dashboard-card chart-small">
            <div class="card-header">
                <h3>Status Distribution</h3>
                <p class="card-subtitle">Live status</p>
            </div>
            <div class="card-content">
                <canvas id="statusChart"></canvas>
                <div class="status-legend">
                    <?php foreach ($status_stats as $status): ?>
                        <div class="legend-item">
                            <span class="legend-color status-<?php echo strtolower(str_replace(' ', '-', $status['status'])); ?>"></span>
                            <span class="legend-label"><?php echo $status['status']; ?></span>
                            <span class="legend-value"><?php echo $status['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Disasters -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Recent Reports</h3>
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
                    <table class="table" id="recent-reports-table">
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
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $report['status'])); ?>">
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
    
    <!-- Secondary Charts -->
    <div class="charts-row-secondary">
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Disaster Types</h3>
            </div>
            <div class="card-content">
                <canvas id="disasterTypesChart"></canvas>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Severity Levels</h3>
                <p class="card-subtitle">Last 30 days</p>
            </div>
            <div class="card-content">
                <canvas id="severityChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Additional Charts -->
    <div class="charts-row-secondary">
        <div class="dashboard-card">
            <div class="card-header">
                <h3>LGU Distribution</h3>
                <p class="card-subtitle">Reports by Local Government Unit</p>
            </div>
            <div class="card-content">
                <canvas id="lguChart"></canvas>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Response Time Metrics</h3>
                <p class="card-subtitle">Average hours per stage</p>
            </div>
            <div class="card-content">
                <canvas id="responseTimeChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Debug: Check if Chart.js is loaded
console.log('Chart.js loaded:', typeof Chart !== 'undefined');

// Disaster Types Chart
const disasterTypesData = <?php echo json_encode($disaster_types_stats); ?>;
console.log('Disaster Types Data:', disasterTypesData);
const disasterTypesCtx = document.getElementById('disasterTypesChart').getContext('2d');
new Chart(disasterTypesCtx, {
    type: 'doughnut',
    data: {
        labels: disasterTypesData.map(item => item.type_name),
        datasets: [{
            data: disasterTypesData.map(item => item.count),
            backgroundColor: [
                '#667eea',
                '#764ba2',
                '#f093fb',
                '#4facfe',
                '#00f2fe',
                '#43e97b',
                '#38f9d7',
                '#fa709a',
                '#fee140'
            ],
            borderWidth: 4,
            borderColor: '#fff',
            hoverOffset: 15,
            hoverBorderWidth: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#334155',
                    font: {
                        size: 13,
                        weight: '600'
                    },
                    padding: 15,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                borderColor: '#667eea',
                borderWidth: 2,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Severity Chart
const severityData = <?php echo json_encode($severity_stats); ?>;
const severityCtx = document.getElementById('severityChart').getContext('2d');

// Create gradients for bars
const severityGradients = severityData.map((_, index) => {
    const gradient = severityCtx.createLinearGradient(0, 0, 0, 300);
    const colors = [
        ['rgba(220, 53, 69, 0.8)', 'rgba(220, 53, 69, 0.2)'],
        ['rgba(253, 126, 20, 0.8)', 'rgba(253, 126, 20, 0.2)'],
        ['rgba(40, 167, 69, 0.8)', 'rgba(40, 167, 69, 0.2)'],
        ['rgba(102, 126, 234, 0.8)', 'rgba(102, 126, 234, 0.2)']
    ];
    gradient.addColorStop(0, colors[index % colors.length][0]);
    gradient.addColorStop(1, colors[index % colors.length][1]);
    return gradient;
});

new Chart(severityCtx, {
    type: 'bar',
    data: {
        labels: severityData.map(item => item.severity_category),
        datasets: [{
            label: 'Reports',
            data: severityData.map(item => item.count),
            backgroundColor: severityGradients,
            borderColor: ['#dc3545', '#fd7e14', '#28a745', '#667eea'],
            borderWidth: 2,
            borderRadius: 10,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                borderColor: '#667eea',
                borderWidth: 2,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Reports: ' + context.parsed.y;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    color: '#64748b',
                    font: {
                        size: 12,
                        weight: '600'
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#64748b',
                    font: {
                        size: 12,
                        weight: '600'
                    }
                }
            }
        }
    }
});

// Status Distribution Chart
const statusData = <?php echo json_encode($status_stats); ?>;
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusData.map(item => item.status.replace('_', ' ')),
        datasets: [{
            data: statusData.map(item => item.count),
            backgroundColor: [
                '#fbbf24', // ON GOING - amber
                '#60a5fa', // IN PROGRESS - blue  
                '#34d399'  // COMPLETED - green
            ],
            borderWidth: 4,
            borderColor: '#fff',
            hoverOffset: 12,
            hoverBorderWidth: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                borderColor: '#667eea',
                borderWidth: 2,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Monthly Trend Chart
const monthlyData = <?php echo json_encode($monthly_trend); ?>;
const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');

// Create gradient
const gradientMonthly = monthlyCtx.createLinearGradient(0, 0, 0, 300);
gradientMonthly.addColorStop(0, 'rgba(102, 126, 234, 0.4)');
gradientMonthly.addColorStop(0.5, 'rgba(118, 75, 162, 0.2)');
gradientMonthly.addColorStop(1, 'rgba(102, 126, 234, 0.0)');

new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Reports',
            data: monthlyData.map(item => item.count),
            borderColor: '#667eea',
            backgroundColor: gradientMonthly,
            borderWidth: 4,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#667eea',
            pointBorderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointHoverBackgroundColor: '#667eea',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                borderColor: '#667eea',
                borderWidth: 2,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Reports: ' + context.parsed.y;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    color: '#64748b',
                    font: {
                        size: 12,
                        weight: '600'
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#64748b',
                    font: {
                        size: 12,
                        weight: '600'
                    }
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// LGU Distribution Pie Chart
const lguData = <?php echo json_encode($lgu_distribution); ?>;
const lguCtx = document.getElementById('lguChart').getContext('2d');
new Chart(lguCtx, {
    type: 'pie',
    data: {
        labels: lguData.map(item => item.lgu_name),
        datasets: [{
            data: lguData.map(item => item.count),
            backgroundColor: [
                '#667eea',
                '#764ba2',
                '#f093fb',
                '#4facfe',
                '#00f2fe',
                '#43e97b',
                '#38f9d7',
                '#fa709a'
            ],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 10,
            hoverBorderWidth: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#334155',
                    font: {
                        size: 12,
                        weight: '600'
                    },
                    padding: 12,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    boxWidth: 8,
                    boxHeight: 8
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                borderColor: '#667eea',
                borderWidth: 2,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' reports (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Response Time Bar Graph
const responseTimeData = <?php echo json_encode($response_time_breakdown); ?>;
const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');

// Create gradients for response time bars
const responseGradients = responseTimeData.map((_, index) => {
    const gradient = responseTimeCtx.createLinearGradient(0, 0, 0, 300);
    const colors = [
        ['rgba(102, 126, 234, 0.8)', 'rgba(102, 126, 234, 0.2)'],
        ['rgba(118, 75, 162, 0.8)', 'rgba(118, 75, 162, 0.2)'],
        ['rgba(67, 233, 123, 0.8)', 'rgba(67, 233, 123, 0.2)']
    ];
    gradient.addColorStop(0, colors[index % colors.length][0]);
    gradient.addColorStop(1, colors[index % colors.length][1]);
    return gradient;
});

new Chart(responseTimeCtx, {
    type: 'bar',
    data: {
        labels: responseTimeData.map(item => item.metric),
        datasets: [{
            label: 'Hours',
            data: responseTimeData.map(item => item.avg_hours || 0),
            backgroundColor: responseGradients,
            borderColor: ['#667eea', '#764ba2', '#43e97b'],
            borderWidth: 2,
            borderRadius: 10,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                borderColor: '#667eea',
                borderWidth: 2,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' hours average';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    color: '#64748b',
                    font: {
                        size: 12,
                        weight: '600'
                    },
                    callback: function(value) {
                        return value + 'h';
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#64748b',
                    font: {
                        size: 12,
                        weight: '600'
                    }
                }
            }
        }
    }
});

// Dashboard-specific real-time handlers using global RealtimeSystem

// Register dashboard update handler
window.onRealtimeUpdate = function(data) {
    console.log('📊 Dashboard received update:', data);
    
    // Update recent reports table
    if (data.stats.recent_reports) {
        updateRecentReportsTable(data.stats.recent_reports);
    }
    
    // Update last update time
    updateLastUpdateTime(new Date(data.timestamp * 1000));
};

// Register new report handler
window.onNewReport = function(count, stats) {
    console.log('🚨 Dashboard: New report notification', count);
    // Toast is already shown by global system
};

// Update last update time in dashboard header
function updateLastUpdateTime(date) {
    const timeString = date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    });
    
    let indicator = document.getElementById('last-updated-indicator');
    if (!indicator) {
        const statusEl = document.getElementById('realtime-status');
        if (statusEl) {
            indicator = document.createElement('span');
            indicator.id = 'last-updated-indicator';
            indicator.style.cssText = 'color: #9ca3af; font-size: 0.75rem; margin-left: 0.5rem;';
            statusEl.appendChild(indicator);
        }
    }
    
    if (indicator) {
        indicator.textContent = `(${timeString})`;
    }
}

// Note: stat card updates and animations are handled by global RealtimeSystem



// Update recent reports table
function updateRecentReportsTable(reports) {
    const tbody = document.querySelector('#recent-reports-table tbody');
    if (!tbody || reports.length === 0) return;
    
    // Check if there are new reports
    const existingIds = Array.from(tbody.querySelectorAll('tr')).map(tr => 
        tr.querySelector('.tracking-id').textContent
    );
    
    const hasNewReports = reports.some(report => 
        !existingIds.includes(report.tracking_id)
    );
    
    if (hasNewReports) {
        // Rebuild table with new data
        tbody.innerHTML = reports.map(report => `
            <tr class="new-row">
                <td>
                    <span class="tracking-id">${escapeHtml(report.tracking_id)}</span>
                </td>
                <td>${escapeHtml(report.type_name)}</td>
                <td>${escapeHtml(report.city || 'N/A')}</td>
                <td>
                    <span class="severity-badge severity-${report.severity_level.split('-')[0]}">
                        ${escapeHtml(report.severity_display)}
                    </span>
                </td>
                <td>
                    <span class="status-badge status-${report.status.toLowerCase().replace(/\s+/g, '-')}">
                        ${report.status.charAt(0) + report.status.slice(1).toLowerCase().replace(/_/g, ' ')}
                    </span>
                </td>
                <td>
                    <span class="time-ago">${report.hours_ago}h ago</span>
                </td>
                <td>
                    <a href="disaster-details.php?id=${report.disaster_id}" class="btn btn-xs btn-primary">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
        `).join('');
        
        // Highlight new rows briefly
        setTimeout(() => {
            tbody.querySelectorAll('.new-row').forEach(row => {
                row.classList.remove('new-row');
            });
        }, 2000);
    }
}

// Note: New report notifications are handled by global RealtimeSystem

// HTML escape function
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Dashboard is now powered by global RealtimeSystem (loaded via header.php)
// No need for page-specific initialization
console.log('📊 Dashboard ready - using global RealtimeSystem');

</script>

<?php include 'includes/footer.php'; ?>