<?php
session_start();
require_once 'config/database.php';

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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3) {
        $error_message = 'Username must be at least 3 characters long.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (!empty($phone) && !preg_match('/^[\+]?[0-9\-\s\(\)]{7,20}$/', $phone)) {
        $error_message = 'Please enter a valid phone number format.';
    } else {
        try {
            // Check if username already exists
            $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $check_stmt->execute([$username, $email]);
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = 'Username or email already exists. Please choose different credentials.';
            } else {
                // Hash password and create user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $insert_stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, first_name, last_name, role, phone, is_active, email_verified) 
                    VALUES (?, ?, ?, ?, ?, 'reporter', ?, 1, 0)
                ");
                
                $insert_stmt->execute([
                    $username,
                    $email,
                    $password_hash,
                    $first_name,
                    $last_name,
                    !empty($phone) ? $phone : null
                ]);
                
                // Log the registration activity
                $user_id = $pdo->lastInsertId();
                $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, 'register', ?, ?)");
                $log_stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
                
                $success_message = 'Registration successful! You can now log in with your credentials and report emergencies in your area.';
                
                // Clear form data
                $first_name = $last_name = $username = $email = $phone = '';
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error_message = 'An error occurred during registration. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporter Registration - iMSafe System</title>
    <link rel="icon" type="image/x-icon" href="assets/images/icon2.png">
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
            padding: 20px;
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
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            position: relative;
        }
        
        .register-left {
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
            overflow-y: auto;
            max-height: 600px;
        }
        
        .register-right {
            background: linear-gradient(135deg, #4c63d2 0%, #5a67d8 50%, #667eea 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
        }
        
        .register-right::before {
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
        
        .brand-logo img {
            width: 200px;
            height: 200px;
            object-fit: contain;
        }
        
        .brand-logo i {
            font-size: 3.5rem;
            color: white;
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
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }
        
        .register-header p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
            font-size: 14px;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            font-size: 13px;
        }
        
        .form-group label .required {
            color: #dc2626;
        }
        
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-group i {
            position: absolute;
            left: 14px;
            color: #9ca3af;
            z-index: 2;
            font-size: 14px;
        }
        
        .input-group input,
        .input-group select {
            width: 100%;
            padding: 14px 14px 14px 42px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .input-group select {
            cursor: pointer;
        }
        
        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: #4c63d2;
            box-shadow: 0 0 0 4px rgba(76, 99, 210, 0.1);
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            width: 36px;
            height: 36px;
            padding: 0;
            border-radius: 8px;
            transition: color 0.18s ease, box-shadow 0.12s ease;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .password-toggle:hover {
            color: #4c63d2;
        }

        .password-toggle:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(76,99,210,0.14);
            color: #4c63d2;
        }
        
        .btn {
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 25px 0 20px 0;
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
        
        .register-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .register-footer a {
            color: #4c63d2;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            font-size: 14px;
        }
        
        .register-footer a:hover {
            color: #3730a3;
            text-decoration: underline;
        }
        
        .register-footer p {
            margin-bottom: 10px;
            color: #6b7280;
            font-size: 14px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
                align-items: flex-start;
                padding-top: 30px;
                overflow: auto; /* allow page scrolling on small screens */
                -webkit-overflow-scrolling: touch; /* smooth momentum scrolling on iOS */
            }

            .register-container {
                grid-template-columns: 1fr;
                max-width: 420px;
                min-height: auto;
                border-radius: 16px;
                box-shadow: 0 12px 30px rgba(0,0,0,0.12);
                max-height: none; /* ensure container doesn't block page scroll */
            }
            
            .register-right {
                display: none;
            }
            
            .register-left {
                padding: 20px 18px;
                max-height: none;
                overflow: visible;
            }
            

            .form-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .register-header h1 {
                font-size: 1.6rem;
            }

            .register-header p {
                font-size: 0.9rem;
            }

            .form-group {
                margin-bottom: 14px;
            }
            .input-group i { left: 10px; }
            .input-group input, .input-group select { padding: 12px 12px 12px 38px; }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
                padding-top: 20px;
                overflow: auto; /* ensure scrolling works on very small screens */
                -webkit-overflow-scrolling: touch;
            }

            .register-container {
                border-radius: 12px;
                max-height: none;
            }

            .register-left {
                padding: 16px 14px;
            }

            .register-header {
                margin-bottom: 25px;
            }

            .register-header h1 {
                font-size: 1.35rem;
            }

            .register-header p {
                font-size: 0.85rem;
            }
            
            .input-group input,
            .input-group select {
                padding: 10px 10px 10px 34px;
                font-size: 14px;
            }

            .input-group i {
                left: 10px;
                font-size: 13px;
            }

            .password-toggle {
                right: 10px;
                font-size: 13px;
            }

            .form-group label {
                font-size: 12.5px;
                margin-bottom: 5px;
            }

            .form-group {
                margin-bottom: 12px;
            }
            
            .btn {
                padding: 11px 14px;
                font-size: 14px;
                margin: 18px 0 14px 0;
            }

            .alert {
                padding: 12px 14px;
                font-size: 13px;
                margin-bottom: 18px;
            }

            .register-footer {
                padding-top: 18px;
            }

            .register-footer p,
            .register-footer a {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Left Panel - Registration Form -->
        <div class="register-left">
            <div class="register-header">
                <h1>Join as Reporter</h1>
                <p>Register to report emergencies and disasters in your area</p>
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
            
            <form method="POST" class="register-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($first_name ?? ''); ?>"
                                   placeholder="Enter first name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($last_name ?? ''); ?>"
                                   placeholder="Enter last name" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-at"></i>
                            <input type="text" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($username ?? ''); ?>"
                                   placeholder="Choose username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   placeholder="Enter email address" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="phone">Phone Number</label>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                               placeholder="e.g. +63-912-345-6789">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" 
                                   placeholder="Enter password (min 6 chars)" required aria-describedby="toggle-password">
                            <button type="button" id="toggle-password" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePassword('password','password-icon','toggle-password')">
                                <i class="fas fa-eye" id="password-icon" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm password" required aria-describedby="toggle-confirm-password">
                            <button type="button" id="toggle-confirm-password" class="password-toggle" aria-label="Show confirm password" aria-pressed="false" onclick="togglePassword('confirm_password','confirm-password-icon','toggle-confirm-password')">
                                <i class="fas fa-eye" id="confirm-password-icon" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Create Reporter Account
                </button>
            </form>
            
            <div class="register-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
                <p><a href="index.php">Back to Main Site</a></p>
            </div>
        </div>
        
        <!-- Right Panel - Branding -->
        <div class="register-right">
            <div class="brand-section">
                <div class="brand-logo">
                    <img src="assets/images/icon2.png" alt="iMSafe Logo">
                </div>
                <h2 class="brand-title">iMSafe</h2>
                <p class="brand-subtitle">Disaster Monitoring System<br>Reporter Registration</p>
                
                <ul class="feature-list">
                    <li><i class="fas fa-exclamation-triangle"></i> Report Emergencies</li>
                    <li><i class="fas fa-camera"></i> Upload Evidence Photos</li>
                    <li><i class="fas fa-map-marked-alt"></i> Location-based Reporting</li>
                    <li><i class="fas fa-clock"></i> Real-time Updates</li>
                    <li><i class="fas fa-users"></i> Community Safety</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId, iconId, toggleId) {
            const passwordField = document.getElementById(fieldId);
            const passwordIcon = document.getElementById(iconId);
            const toggleBtn = document.getElementById(toggleId);

            if (!passwordField || !passwordIcon) return;

            const isHidden = passwordField.type === 'password';
            if (isHidden) {
                passwordField.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
                if (toggleBtn) {
                    toggleBtn.setAttribute('aria-label', 'Hide password');
                    toggleBtn.setAttribute('aria-pressed', 'true');
                }
            } else {
                passwordField.type = 'password';
                passwordIcon.className = 'fas fa-eye';
                if (toggleBtn) {
                    toggleBtn.setAttribute('aria-label', 'Show password');
                    toggleBtn.setAttribute('aria-pressed', 'false');
                }
            }
            // Keep focus on the input to avoid dismissing mobile keyboards
            try { passwordField.focus(); } catch (e) { /* ignore */ }
        }
        
        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            password.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        });
    </script>
</body>
</html>