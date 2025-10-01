
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
                <a href="track_report.php" class="nav-link">Track Report</a>
                <a href="admin/dashboard.php" class="nav-link btn-login">Admin Panel</a>
            </div>
            <div class="hamburger" id="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">
                        Empowering Local Government Units with 
                        <span class="gradient-text">Comprehensive Control</span>
                    </h1>
                    <p class="hero-description">
                        Advanced disaster monitoring and emergency response system designed to protect communities 
                        through real-time alerts, coordinated response, and comprehensive resource management.
                    </p>
                    <div class="hero-buttons">
                        <a href="report_emergency.php" class="btn btn-primary">
                            <i class="fas fa-exclamation-triangle"></i>
                            Report Emergency
                        </a>
                        <a href="track_report.php" class="btn btn-secondary">
                            <i class="fas fa-search"></i>
                            Track Your Report
                        </a>
                        <a href="#features" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i>
                            Learn More
                        </a>
                    </div>
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
                        <img src="assets/images/dashboard-preview.png" alt="Dashboard Preview" class="dashboard-image">
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

    <!-- Emergency CTA Section -->
    <section id="emergency-report" class="emergency-cta">
        <div class="container">
            <div class="emergency-cta-content">
                <div class="emergency-cta-text">
                    <h2><i class="fas fa-exclamation-triangle"></i> Need to Report an Emergency?</h2>
                    <p>Get immediate assistance from your Local Government Unit. Our system ensures your report reaches the right authorities within 24-48 hours.</p>
                    <div class="emergency-features">
                        <div class="emergency-feature">
                            <i class="fas fa-clock"></i>
                            <span>24-48 Hour Response</span>
                        </div>
                        <div class="emergency-feature">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Location-Based Assignment</span>
                        </div>
                        <div class="emergency-feature">
                            <i class="fas fa-phone"></i>
                            <span>Direct LGU Contact</span>
                        </div>
                        <div class="emergency-feature">
                            <i class="fas fa-eye"></i>
                            <span>Real-time Tracking</span>
                        </div>
                    </div>
                </div>
                <div class="emergency-cta-action">
                    <a href="report_emergency.php" class="btn btn-emergency-large">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="btn-text">
                            <strong>REPORT EMERGENCY</strong>
                            <small>Quick & Anonymous Reporting</small>
                        </span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <div class="emergency-contact">
                        <p><strong>For Life-Threatening Emergencies:</strong></p>
                        <a href="tel:911" class="emergency-hotline">
                            <i class="fas fa-phone"></i>
                            Call 911 Immediately
                        </a>
                    </div>
                </div>
            </div>
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
                        <li><a href="#home">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="admin/dashboard.php">Admin Panel</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Emergency</h4>
                    <ul>
                        <li><a href="report_emergency.php">Report Emergency</a></li>
                        <li><a href="track_report.php">Track Your Report</a></li>
                        <li><a href="tel:911">Call 911</a></li>
                        <li><a href="#contact">Contact Support</a></li>
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
</body>
</html>