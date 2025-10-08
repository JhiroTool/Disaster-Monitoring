<?php
// Get notifications count
$notifications_count = 0;
try {
    $notif_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $notif_stmt->execute([$_SESSION['user_id']]);
    $notif_result = $notif_stmt->fetch();
    $notifications_count = $notif_result['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
}

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Dashboard'; ?> - iMSafe System</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/icon2.png">
    <link rel="stylesheet" href="assets/css/admin.css">
    <?php if ($current_page === 'dashboard.php'): ?>
    <link rel="stylesheet" href="assets/css/dashboard-modern.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Chart.js for Dashboard Charts -->
    <?php if ($current_page === 'dashboard.php'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../assets/images/icon2.png" alt="">
                    <span>iMSafe Admin</span>
                </div>
                <button class="sidebar-toggle" type="button" aria-label="Toggle sidebar" onclick="toggleSidebar()">
                    <i class="fas fa-bars" data-sidebar-toggle-icon></i>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <a href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="menu-section">Emergency Management</li>
                    <li class="<?php echo $current_page === 'disasters.php' ? 'active' : ''; ?>">
                        <a href="disasters.php">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Disaster Reports</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'disaster-types.php' ? 'active' : ''; ?>">
                        <a href="disaster-types.php">
                            <i class="fas fa-list"></i>
                            <span>Disaster Types</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'resources.php' ? 'active' : ''; ?>">
                        <a href="resources.php">
                            <i class="fas fa-boxes"></i>
                            <span>Resources</span>
                        </a>
                    </li>
                    
                    <li class="menu-section">Communications</li>
                    <li class="<?php echo $current_page === 'announcements.php' ? 'active' : ''; ?>">
                        <a href="announcements.php">
                            <i class="fas fa-bullhorn"></i>
                            <span>Announcements</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
                        <a href="notifications.php">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                            <?php if ($notifications_count > 0): ?>
                                <span class="badge"><?php echo $notifications_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if (isAdmin() || isLguAdmin()): ?>
                    <li class="menu-section">Administration</li>
                    <li class="<?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                        <a href="users.php">
                            <i class="fas fa-users"></i>
                            <span>User</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'lgus.php' ? 'active' : ''; ?>">
                        <a href="lgus.php">
                            <i class="fas fa-building"></i>
                            <span>LGU Management</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                    <li class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                        <a href="reports.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports & Analytics</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            <span>System Settings</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo getUserInitials(); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars(getUserName()); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $_SESSION['role']))); ?></div>
                    </div>
                </div>
            </div>
        </nav>

    <div class="sidebar-hover-zone" aria-hidden="true"></div>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="sidebar-toggle" type="button" aria-label="Toggle sidebar" onclick="toggleSidebar()">
                        <i class="fas fa-bars" data-sidebar-toggle-icon></i>
                    </button>
                    <h1><?php echo $page_title ?? 'Dashboard'; ?></h1>
                </div>
                
                <div class="header-right">
                    <div class="header-actions">
                        <!-- Quick Emergency Button -->
                        <a href="../index.php#emergency-report" class="btn btn-emergency btn-sm" target="_blank">
                            <i class="fas fa-exclamation-triangle"></i>
                            Emergency Report
                        </a>
                        
                        <!-- Notifications -->
                        <div class="dropdown notifications-dropdown">
                            <button class="btn btn-icon" onclick="toggleDropdown('notifications')">
                                <i class="fas fa-bell"></i>
                                <?php if ($notifications_count > 0): ?>
                                    <span class="notification-badge"><?php echo $notifications_count; ?></span>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-menu" id="notifications-dropdown">
                                <div class="dropdown-header">
                                    <span>Notifications</span>
                                    <?php if ($notifications_count > 0): ?>
                                        <a href="#" class="mark-all-read" onclick="markAllRead()">Mark all as read</a>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown-body">
                                    <!-- Notifications will be loaded via AJAX -->
                                    <div class="loading">Loading notifications...</div>
                                </div>
                                <div class="dropdown-footer">
                                    <a href="notifications.php">View all notifications</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Menu -->
                        <div class="dropdown user-dropdown">
                            <button class="user-menu-btn" onclick="toggleDropdown('user')">
                                <div class="user-avatar">
                                    <?php echo getUserInitials(); ?>
                                </div>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu" id="user-dropdown">
                                <div class="dropdown-header">
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars(getUserName()); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($_SESSION['email']); ?></div>
                                    </div>
                                </div>
                                <div class="dropdown-body">
                                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                                    <hr>
                                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="page-content">