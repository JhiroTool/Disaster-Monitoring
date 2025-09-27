
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
                <a href="#home" class="nav-link active">Home</a>
                <a href="#features" class="nav-link">Features</a>
                <a href="#about" class="nav-link">About</a>
                <a href="#contact" class="nav-link">Contact</a>
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

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2>Admin Modules</h2>
                <p>Empowering Local Government Units with Comprehensive Control</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Dashboard Overview</h3>
                    <p>Real-time status updates and critical dashboards. Heat Map, Numerical Data</p>
                    <ul class="feature-list">
                        <li>Heat Map</li>
                        <li>Numerical Data</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h3>SOS Request Forwarding</h3>
                    <p>Efficiently route distress requests directly to LGUs</p>
                    <ul class="feature-list">
                        <li>LGU Integration</li>
                        <li>Rapid Response</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3>Announcement Control</h3>
                    <p>Manage and disseminate public safety announcements</p>
                    <ul class="feature-list">
                        <li>Public Alerts</li>
                        <li>Trigger Options</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3>User & Access Control</h3>
                    <p>Securely administer user accounts, roles, and system permissions</p>
                    <ul class="feature-list">
                        <li>Account Security</li>
                        <li>Role-Based Access</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>LGU Reporting</h3>
                    <p>Generate critical insights and compliance reports for LGUs</p>
                    <ul class="feature-list">
                        <li>Data Analytics</li>
                        <li>Actionable Insights</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>Seamless Data Flow</h3>
                    <p>Ensure uninterrupted data flow for informed decisions</p>
                    <ul class="feature-list">
                        <li>Real-time Updates</li>
                        <li>Data Integrity</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Response System Section -->
    <section class="response-system">
        <div class="container">
            <div class="section-header">
                <h2>24-48 Hour Response System</h2>
                <p>Automated escalation ensures no report goes unacknowledged</p>
            </div>
            <div class="response-timeline">
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="timeline-content">
                        <h3>Report Submitted</h3>
                        <p>Citizen reports emergency with required address information</p>
                        <span class="timeline-time">0 Hours</span>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="timeline-content">
                        <h3>Auto-Assignment</h3>
                        <p>System assigns report to appropriate LGU based on location</p>
                        <span class="timeline-time">2-12 Hours</span>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="timeline-content">
                        <h3>Response Deadline</h3>
                        <p>LGU must acknowledge and begin response within set timeframe</p>
                        <span class="timeline-time">24-48 Hours</span>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="timeline-content">
                        <h3>Auto-Escalation</h3>
                        <p>Unacknowledged reports escalate to higher authorities automatically</p>
                        <span class="timeline-time">If Overdue</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Timely insights drive effective local governance</h2>
                    <p>Our disaster monitoring system bridges the gap between citizens and local government units, ensuring rapid response and accountability in emergency situations.</p>
                    <div class="about-features">
                        <div class="about-feature">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <h4>Real-time Monitoring</h4>
                                <p>24/7 surveillance and immediate alert systems</p>
                            </div>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-users"></i>
                            <div>
                                <h4>Community Integration</h4>
                                <p>Connecting citizens directly with emergency responders</p>
                            </div>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <h4>Performance Tracking</h4>
                                <p>Measuring and improving LGU response efficiency</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <div class="image-placeholder">
                        <i class="fas fa-globe"></i>
                        <p>Comprehensive Monitoring Network</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-header">
                <h2>Get in Touch</h2>
                <p>Contact us for system information or emergency assistance</p>
            </div>
            <div class="contact-content">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Emergency Hotline</h4>
                            <p>911</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email Support</h4>
                            <p>support@imsafe.gov.ph</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Office Address</h4>
                            <p>DILG Central Office, Quezon City, Philippines</p>
                        </div>
                    </div>
                </div>
                <div class="contact-form">
                    <form id="contactForm">
                        <div class="form-group">
                            <input type="text" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <textarea rows="4" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
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