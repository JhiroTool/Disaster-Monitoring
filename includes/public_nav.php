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

$nav_return_to = $_SERVER['REQUEST_URI'] ?? '/index.php';

$db_path = __DIR__ . '/../config/database.php';
if (!isset($pdo) && file_exists($db_path)) {
    require_once $db_path;
}

$nav_reporter_status_value = null;
$nav_reporter_status_updated_at_value = null;

if ($nav_user_role === 'reporter') {
    if (isset($nav_reporter_status)) {
        $nav_reporter_status_value = $nav_reporter_status;
    } elseif (isset($reporter_status_value)) {
        $nav_reporter_status_value = $reporter_status_value;
    }

    if (isset($nav_reporter_status_updated_at)) {
        $nav_reporter_status_updated_at_value = $nav_reporter_status_updated_at;
    } elseif (isset($reporter_status_updated_at)) {
        $nav_reporter_status_updated_at_value = $reporter_status_updated_at;
    }

    if ($nav_reporter_status_value === null && isset($pdo) && $is_logged_in) {
        try {
            $status_stmt = $pdo->prepare("SELECT status, updated_at FROM users WHERE user_id = ?");
            $status_stmt->execute([$_SESSION['user_id']]);
            $status_row = $status_stmt->fetch(PDO::FETCH_ASSOC);
            if ($status_row) {
                $nav_reporter_status_value = $status_row['status'] ?? null;
                $nav_reporter_status_updated_at_value = $status_row['updated_at'] ?? null;
            }
        } catch (Exception $e) {
            error_log('Nav reporter status fetch error: ' . $e->getMessage());
        }
    }

    $nav_reporter_status_value = $nav_reporter_status_value ?: "I'm fine";
}
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
            <img src="assets/images/icon2.png" alt="iMSafe Logo" class="nav-logo-img">
            <span>iMSafe Disaster Monitoring System</span>
        </a>
        <div class="nav-menu" id="nav-menu">
            <?php if ($is_logged_in): ?>
                <?php if ($nav_user_role === 'reporter'): ?>
                    <?php
                        $nav_status_suffix = ($nav_reporter_status_value === 'Need help') ? 'help' : 'fine';
                        $nav_status_icon = $nav_reporter_status_value === 'Need help' ? 'fa-life-ring' : 'fa-user-check';
                        $nav_status_updated_label = $nav_reporter_status_updated_at_value
                            ? date('M j, Y g:i A', strtotime($nav_reporter_status_updated_at_value))
                            : '';
                    ?>
                    <div class="nav-reporter-status nav-reporter-status-<?php echo $nav_status_suffix; ?>"<?php echo $nav_status_updated_label ? ' title="Last updated ' . htmlspecialchars($nav_status_updated_label, ENT_QUOTES) . '"' : ''; ?>>
                        <i class="fas <?php echo $nav_status_icon; ?>" aria-hidden="true"></i>
                        <form method="POST" action="index.php" class="nav-status-form">
                            <input type="hidden" name="nav_update_status" value="1">
                            <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($nav_return_to, ENT_QUOTES); ?>">
                            <div class="nav-status-control" data-current-status="<?php echo htmlspecialchars($nav_reporter_status_value, ENT_QUOTES); ?>">
                                <button type="button"
                                        class="nav-status-trigger nav-status-trigger-<?php echo $nav_status_suffix; ?>"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                        aria-controls="nav-status-menu">
                                    <span class="nav-status-trigger-label"><?php echo htmlspecialchars($nav_reporter_status_value); ?></span>
                                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                </button>
                                <div class="nav-status-dropdown nav-status-dropdown-<?php echo $nav_status_suffix; ?>" id="nav-status-menu" role="menu" aria-hidden="true">
                                    <button type="button" class="nav-status-option nav-status-option-fine" data-value="I'm fine" role="menuitem">
                                        <i class="fas fa-user-check" aria-hidden="true"></i>
                                        <span>I'm fine</span>
                                    </button>
                                    <button type="button" class="nav-status-option nav-status-option-help" data-value="Need help" role="menuitem">
                                        <i class="fas fa-life-ring" aria-hidden="true"></i>
                                        <span>Need help</span>
                                    </button>
                                </div>
                                <input type="hidden" name="status_value" value="<?php echo htmlspecialchars($nav_reporter_status_value, ENT_QUOTES); ?>">
                            </div>
                            <noscript>
                                <label for="nav-status-select" class="visually-hidden">Update my well-being status</label>
                                <select id="nav-status-select"
                                        name="status_value"
                                        class="nav-status-select-fallback nav-status-select-<?php echo $nav_status_suffix; ?>"
                                        onchange="this.form.submit()">
                                    <option value="I'm fine" <?php echo $nav_reporter_status_value === "I'm fine" ? 'selected' : ''; ?>>I'm fine</option>
                                    <option value="Need help" <?php echo $nav_reporter_status_value === 'Need help' ? 'selected' : ''; ?>>Need help</option>
                                </select>
                                <button type="submit" class="nav-status-submit">Update</button>
                            </noscript>
                        </form>
                    </div>
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
                            <a href="my_reports.php" class="nav-dropdown-item" role="menuitem">
                                <i class="fas fa-list-alt"></i>
                                View All My Reports
                            </a>
                            <a href="track_report.php" class="nav-dropdown-item" role="menuitem">
                                <i class="fas fa-search"></i>
                                Track Report by ID
                            </a>
                            <div class="nav-dropdown-divider" role="separator"></div>
                            <a href="logout.php" class="nav-dropdown-item nav-dropdown-item-danger" role="menuitem">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
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
                <?php if ($nav_user_role !== 'reporter'): ?>
                    <a href="logout.php" class="nav-link btn-logout">Logout</a>
                <?php endif; ?>
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
