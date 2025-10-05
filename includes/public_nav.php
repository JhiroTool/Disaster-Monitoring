<?php
if (!isset($is_logged_in)) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $is_logged_in = isset($_SESSION['user_id']);
}

$nav_user_role = $user_role ?? ($is_logged_in ? ($_SESSION['role'] ?? '') : '');
$nav_user_name = $user_name ?? '';

if ($is_logged_in && $nav_user_name === '') {
    $first_name = $_SESSION['first_name'] ?? '';
    $last_name = $_SESSION['last_name'] ?? '';
    $nav_user_name = trim($first_name . ' ' . $last_name);
    if ($nav_user_name === '') {
        $nav_user_name = $_SESSION['username'] ?? '';
    }
}

$nav_user_name = $nav_user_name ?: 'User';
?>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <i class="fas fa-shield-alt"></i>
            <span>iMSafe Disaster Monitoring System</span>
        </div>
        <div class="nav-menu" id="nav-menu">
            <?php if ($is_logged_in): ?>
                <span class="nav-welcome">Welcome, <?php echo htmlspecialchars($nav_user_name); ?></span>
                <?php if ($nav_user_role === 'reporter'): ?>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-toggle" type="button" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-shield"></i>
                            Your Reporter Dashboard
                            <i class="fas fa-chevron-down chevron" aria-hidden="true"></i>
                        </button>
                        <div class="nav-dropdown-menu" role="menu">
                            <div class="nav-dropdown-header">Quick Actions</div>
                            <a href="report_emergency.php" class="nav-dropdown-item" role="menuitem">
                                <i class="fas fa-exclamation-triangle"></i>
                                Report New Emergency
                            </a>
                            <a href="track_report.php" class="nav-dropdown-item" role="menuitem">
                                <i class="fas fa-list-alt"></i>
                                View All My Reports
                            </a>
                            <a href="track_report.php#track-report-form" class="nav-dropdown-item" role="menuitem">
                                <i class="fas fa-search"></i>
                                Track Specific Report
                            </a>
                            <div class="nav-dropdown-footer">Stay updated with your latest submissions anytime.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="track_report.php" class="nav-link">Track Report</a>
                    <a href="report_emergency.php" class="nav-link">Report Emergency</a>
                <?php endif; ?>
                <?php if ($nav_user_role === 'admin'): ?>
                    <a href="admin/dashboard.php" class="nav-link">Admin Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="nav-link btn-logout">Logout</a>
            <?php else: ?>
                <a href="track_report.php" class="nav-link">Track Report</a>
                <a href="report_emergency.php" class="nav-link">Report Emergency</a>
                <a href="register.php" class="nav-link">Create Account</a>
                <a href="login.php" class="nav-link btn-login">Login</a>
            <?php endif; ?>
        </div>
        <div class="hamburger" id="hamburger">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
    </div>
</nav>
