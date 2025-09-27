<?php
session_start();
require_once '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

// Get LGUs for dropdown
try {
    $stmt = $pdo->query("SELECT lgu_id, lgu_name, lgu_type FROM lgus WHERE is_active = TRUE ORDER BY lgu_name");
    $lgus = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching LGUs: " . $e->getMessage());
    $lgus = [];
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? 'lgu_staff');
    $lgu_assigned = sanitizeInput($_POST['lgu_assigned'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($username)) $errors[] = 'Username is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    
    if (!validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!empty($phone) && !validatePhone($phone)) {
        $errors[] = 'Invalid phone number format';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists';
            }
        } catch (Exception $e) {
            $errors[] = 'Error checking existing accounts';
        }
    }
    
    if (empty($errors)) {
        try {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Get LGU name
            $lgu_name = '';
            if (!empty($lgu_assigned)) {
                $lgu_stmt = $pdo->prepare("SELECT lgu_name FROM lgus WHERE lgu_id = ?");
                $lgu_stmt->execute([$lgu_assigned]);
                $lgu_result = $lgu_stmt->fetch();
                $lgu_name = $lgu_result ? $lgu_result['lgu_name'] : '';
            }
            
            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, first_name, last_name, role, lgu_assigned, phone, is_active, email_verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, FALSE, FALSE)
            ");
            
            $stmt->execute([
                $username, $email, $password_hash, $first_name, $last_name, 
                $role, $lgu_name, $phone
            ]);
            
            $success_message = 'Registration successful! Your account is pending approval by an administrator.';
            
            // Clear form data
            $_POST = [];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'An error occurred during registration. Please try again.';
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - iMSafe Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="register-page">
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <h1>Register Account</h1>
                </div>
                <p>Join the iMSafe Disaster Monitoring System</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
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
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <small>Minimum 8 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                               placeholder="+63 9XX XXX XXXX">
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role">
                            <option value="lgu_staff" <?php echo (($_POST['role'] ?? 'lgu_staff') === 'lgu_staff') ? 'selected' : ''; ?>>LGU Staff</option>
                            <option value="lgu_admin" <?php echo (($_POST['role'] ?? '') === 'lgu_admin') ? 'selected' : ''; ?>>LGU Administrator</option>
                            <option value="responder" <?php echo (($_POST['role'] ?? '') === 'responder') ? 'selected' : ''; ?>>Emergency Responder</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="lgu_assigned">Assigned LGU</label>
                    <select id="lgu_assigned" name="lgu_assigned">
                        <option value="">Select LGU...</option>
                        <?php foreach ($lgus as $lgu): ?>
                            <option value="<?php echo $lgu['lgu_id']; ?>" 
                                    <?php echo (($_POST['lgu_assigned'] ?? '') == $lgu['lgu_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lgu['lgu_name'] . ' (' . ucfirst($lgu['lgu_type']) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Register Account
                </button>
            </form>
            
            <div class="register-footer">
                <p>Already have an account? <a href="login.php">Sign In</a> | <a href="../index.php">Back to Main Site</a></p>
            </div>
        </div>
    </div>
</body>
</html>