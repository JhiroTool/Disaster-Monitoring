
<?php
session_start();

// Handle messages
$success_message = '';
$error_message = '';

if (isset($_GET['logged_in'])) {
    $success_message = 'Welcome back! You are now logged in as a reporter.';
}

if (isset($_GET['error']) && $_GET['error'] === 'access_denied') {
    $error_message = 'Access denied. Only administrators can access the admin panel.';
}

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : '';
$user_role = $is_logged_in ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iMSafe - Disaster Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .tracking-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
        }
        .tracking-id {
            font-size: 1.5em;
            font-weight: bold;
            margin: 10px 0;
            letter-spacing: 2px;
        }
        
        .nav-welcome {
            color: #667eea;
            font-weight: 500;
            margin-right: 15px;
            display: flex;
            align-items: center;
        }
        
        .nav-welcome::before {
            content: "👋";
            margin-right: 8px;
        }
        
        .btn-logout {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        .reporter-features {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f9ff 100%);
            padding: 30px;
            border-radius: 12px;
            margin: 30px 0;
            border: 1px solid #16a34a;
        }
        
        .reporter-features h3 {
            color: #16a34a;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .reporter-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .reporter-actions .btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
            justify-content: center;
        }
        
        .reporting-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin: 25px 0;
        }
        
        .reporting-option {
            background: rgba(255, 255, 255, 0.95);
            color: #2c3e50;
            padding: 25px;
            border-radius: 15px;
            border: 3px solid #3498db;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .reporting-option h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .reporting-option p {
            color: #34495e;
            font-size: 1.1rem;
            line-height: 1.6;
            font-weight: 500;
        }
        
        /* Senior-Friendly Accessibility Improvements */
        body {
            font-size: 18px !important;
            line-height: 1.7 !important;
        }
        
        .emergency-cta h2 {
            font-size: 2.5rem !important;
            font-weight: 700 !important;
            color: #2c3e50 !important;
            margin-bottom: 25px !important;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .emergency-cta p {
            font-size: 1.3rem !important;
            line-height: 1.7 !important;
            color: #34495e !important;
            font-weight: 500 !important;
        }
        
        .btn-emergency-large {
            font-size: 1.4rem !important;
            padding: 20px 30px !important;
            min-height: 80px !important;
        }
        
        .btn-emergency-large strong {
            font-size: 1.6rem !important;
        }
        
        .btn-emergency-large small {
            font-size: 1.2rem !important;
        }
        
        .emergency-hotline {
            font-size: 1.3rem !important;
            padding: 15px 25px !important;
            font-weight: 600 !important;
        }
        
        .emergency-feature span {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
        }
        
        /* High Contrast Navigation for Seniors */
        .nav-link {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
        }
        
        .btn-login, .btn-logout {
            font-size: 1.1rem !important;
            padding: 12px 20px !important;
        }
        
        @media (max-width: 768px) {
            .reporting-options {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .emergency-cta h2 {
                font-size: 1.8rem !important;
            }
            
            .emergency-cta p {
                font-size: 1.2rem !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php require_once __DIR__ . '/includes/public_nav.php'; ?>

    <!-- Alert Messages -->
    <?php if ($success_message): ?>
        <div class="container">
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="container">
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        </div>
    <?php endif; ?>



    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-text">
                    <?php if ($is_logged_in && $user_role === 'reporter'): ?>
                        <h1 class="hero-title">
                            Welcome back, 
                            <span class="gradient-text"><?php echo htmlspecialchars($user_name); ?></span>
                        </h1>
                        <p class="hero-description">
                            You're ready to help keep your community safe. Report emergencies, track your submissions, 
                            and stay connected with your Local Government Unit for coordinated disaster response.
                        </p>
                        <div class="emergency-cta-action">
                    <a href="report_emergency.php" class="btn btn-emergency-large">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="btn-text">
                            <strong>REPORT EMERGENCY NOW</strong>
                            <small>Click Here - Works With or Without Account</small>
                        </span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                    <?php else: ?>
                        <h1 class="hero-title">
                            Disaster Tracking and 
                            <span class="gradient-text">Monitoring Platform</span>
                        </h1>
                        <p class="hero-description" style="font-size: 1.3rem; line-height: 1.6; color: #2c3e50; font-weight: 500;">
                            <strong>Report emergencies quickly and easily.</strong> Our system connects you directly with your Local Government Unit for fast emergency response. No technical knowledge required - anyone can report emergencies in just a few clicks.
                        </p>
                        <div class="emergency-cta-action">
                    <a href="report_emergency.php" class="btn btn-emergency-large">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="btn-text">
                            <strong>REPORT EMERGENCY NOW</strong>
                            <small>Click Here - Works With or Without Account</small>
                        </span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                    <?php endif; ?>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Monitoring</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">50+</div>
                            <div class="stat-label">LGUs Connected</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">98%</div>
                            <div class="stat-label">Response Rate</div>
                        </div>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="dashboard-preview">
                        <div class="dashboard-image" role="img" aria-label="iMSafe Disaster Monitoring System logo"></div>
                        <div class="floating-cards">
                            <div class="alert-card active">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Active Alert</span>
                            </div>
                            <div class="status-card">
                                <i class="fas fa-check-circle"></i>
                                <span>System Online</span>
                            </div>
                            <div class="response-card">
                                <i class="fas fa-users"></i>
                                <span>Teams Ready</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-background">
            <div class="bg-gradient"></div>
            <div class="bg-pattern"></div>
        </div>
    </section>

    

    

   

    <!-- Footer -->
    <?php require_once __DIR__ . '/includes/public_footer.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
</body>
</html>