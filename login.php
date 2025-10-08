<?php
session_start();
require_once 'config/database.php';
require_once 'admin/includes/notification_helper.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: index.php?logged_in=1');
    }
    exit;
}

$error_message = '';
$success_message = '';

// Handle logout message
if (isset($_GET['logged_out'])) {
    $success_message = 'You have been logged out successfully.';
}

// Handle timeout message
if (isset($_GET['timeout'])) {
    $error_message = 'Your session has expired. Please log in again.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            // Check user credentials
            $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, first_name, last_name, role, is_active FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['is_active']) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Update last login
                    $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    $update_stmt->execute([$user['user_id']]);
                    
                    // Log activity
                    $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, 'login', ?, ?)");
                    $log_stmt->execute([$user['user_id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
                    
                    // Check for new disaster reports and create notifications (for admins only)
                    if ($user['role'] === 'admin') {
                        try {
                            $notified = checkAndNotifyNewReports($pdo);
                            if ($notified > 0) {
                                error_log("Created {$notified} notifications for new disaster reports");
                            }
                        } catch (Exception $e) {
                            error_log("Error creating notifications on login: " . $e->getMessage());
                        }
                        
                        // Redirect admin to dashboard
                        header('Location: admin/dashboard.php');
                    } else {
                        // Redirect reporters to main page
                        header('Location: index.php?logged_in=1');
                    }
                    exit;
                } else {
                    $error_message = 'Your account has been deactivated. Please contact the administrator.';
                }
            } else {
                $error_message = 'Invalid username/email or password.';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error_message = 'An error occurred during login. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iMSafe System</title>
    <link rel="icon" type="image/x-icon" href="assets/images/icon2.png">
    <link rel="stylesheet" href="admin/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            pointer-events: none;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            min-height: 600px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            position: relative;
        }
        
        .login-left {
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }
        
        .login-right {
            background: linear-gradient(135deg, #4c63d2 0%, #5a67d8 50%, #667eea 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
        }
        
        .login-right::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><circle cx="30" cy="30" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .brand-section {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .brand-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .brand-logo i {
            font-size: 3.5rem;
            color: white;
        }
        
        .brand-logo img {
            width: 200px;
            height: 200px;
            object-fit: contain;
        }
        
        .brand-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .brand-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .feature-list {
            list-style: none;
            text-align: left;
        }
        
        .feature-list li {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 1rem;
            opacity: 0.95;
        }
        
        .feature-list i {
            margin-right: 12px;
            width: 20px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #6b7280;
            font-size: 1rem;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        

        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-group i {
            position: absolute;
            left: 16px;
            color: #9ca3af;
            z-index: 2;
        }
        
        .input-group input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            background: white;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #4c63d2;
            box-shadow: 0 0 0 4px rgba(76, 99, 210, 0.1);
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: color 0.3s ease;
            z-index: 2;
        }
        
        .password-toggle:hover {
            color: #4c63d2;
        }
        
        .btn {
            width: 100%;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 32px 0 24px 0;
            text-decoration: none;
            font-family: inherit;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4c63d2 0%, #5a67d8 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(76, 99, 210, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(76, 99, 210, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .login-footer a {
            color: #4c63d2;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .login-footer a:hover {
            color: #3730a3;
            text-decoration: underline;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 400px;
                margin: 20px;
            }
            
            .login-right {
                display: none;
            }
            
            .login-left {
                padding: 40px 30px;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .login-left {
                padding: 30px 20px;
            }
            
            .input-group input {
                padding: 14px 14px 14px 44px;
                font-size: 16px;
            }
            
            .btn {
                padding: 14px 20px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Panel - Login Form -->
        <div class="login-left">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Sign in to your account</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               placeholder="Enter username or email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="Enter password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Register as Reporter</a></p>
                <p><a href="index.php">Back to Main Site</a></p>
            </div>
        </div>
        
        <!-- Right Panel - Branding -->
        <div class="login-right">
            <div class="brand-section">
                <div class="brand-logo">
                    <img src="assets/images/icon2.png" alt="iMSafe Logo" class="nav-logo-img">
                </div>
                <h2 class="brand-title">iMSafe</h2>
                <p class="brand-subtitle">Disaster Monitoring System<br>Administration Portal</p>
                
                <ul class="feature-list">
                    <li><i class="fas fa-chart-line"></i> Real-time Monitoring</li>
                    <li><i class="fas fa-users"></i> Emergency Response</li>
                    <li><i class="fas fa-map-marked-alt"></i> LGU Coordination</li>
                    <li><i class="fas fa-bell"></i> Alert Management</li>
                    <li><i class="fas fa-file-alt"></i> Comprehensive Reports</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>