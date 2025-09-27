<?php
// Include database connection
require_once 'config/database.php';

// Get disaster types for dropdown
try {
    $stmt = $pdo->query("SELECT type_id, type_name, description FROM disaster_types ORDER BY type_name");
    $disaster_types = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching disaster types: " . $e->getMessage());
    $disaster_types = [];
}

// Handle form submission
$submission_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    try {
        // Validate required fields
        $required_fields = [
            'disaster_type' => sanitizeInput($_POST['disaster_type'] ?? ''),
            'severity' => sanitizeInput($_POST['severity'] ?? ''),
            'location' => sanitizeInput($_POST['location'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? '')
        ];
        
        $validation_errors = validateRequired($required_fields);
        
        // Validate phone number
        if (!empty($required_fields['phone']) && !validatePhone($required_fields['phone'])) {
            $validation_errors[] = "Invalid phone number format";
        }
        
        if (empty($validation_errors)) {
            // Generate tracking ID
            $tracking_id = generateTrackingId();
            
            // Prepare optional fields
            $reporter_name = sanitizeInput($_POST['reporter_name'] ?? '');
            $alternate_contact = sanitizeInput($_POST['alternate_contact'] ?? '');
            $landmark = sanitizeInput($_POST['landmark'] ?? '');
            $people_affected = sanitizeInput($_POST['people_affected'] ?? '');
            $current_situation = sanitizeInput($_POST['current_situation'] ?? '');
            
            // Handle image upload
            $image_path = null;
            $upload_error = null;
            
            if (isset($_FILES['emergency_image']) && $_FILES['emergency_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/emergency_images/';
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                $file_type = $_FILES['emergency_image']['type'];
                $file_size = $_FILES['emergency_image']['size'];
                $original_name = $_FILES['emergency_image']['name'];
                
                // Validate file type and size
                if (!in_array($file_type, $allowed_types)) {
                    $upload_error = "Invalid file type. Please upload JPG, PNG, or GIF files only.";
                } elseif ($file_size > $max_size) {
                    $upload_error = "File too large. Maximum size is 5MB.";
                } else {
                    // Create unique filename
                    $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                    $filename = 'emergency_' . time() . '_' . uniqid() . '.' . strtolower($file_extension);
                    $upload_path = $upload_dir . $filename;
                    
                    // Check if upload directory exists and is writable
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    if (!is_writable($upload_dir)) {
                        $upload_error = "Upload directory is not writable.";
                        error_log("Upload directory not writable: " . $upload_dir);
                    } else {
                        // Attempt to move uploaded file
                        if (move_uploaded_file($_FILES['emergency_image']['tmp_name'], $upload_path)) {
                            $image_path = $upload_path;
                            error_log("Image uploaded successfully: " . $upload_path);
                        } else {
                            $upload_error = "Failed to save uploaded image.";
                            error_log("Failed to move uploaded file to: " . $upload_path);
                        }
                    }
                }
            } elseif (isset($_FILES['emergency_image']) && $_FILES['emergency_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Handle upload errors
                switch ($_FILES['emergency_image']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $upload_error = "File too large.";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $upload_error = "File upload was incomplete.";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $upload_error = "Missing temporary upload directory.";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $upload_error = "Failed to write file to disk.";
                        break;
                    default:
                        $upload_error = "Unknown upload error.";
                        break;
                }
            }
            
            // Add upload error to validation errors if it exists
            if ($upload_error) {
                $validation_errors[] = $upload_error;
                error_log("Image upload error: " . $upload_error);
            }
            
            // Handle immediate needs (checkboxes)
            $immediate_needs = [];
            if (!empty($_POST['needs']) && is_array($_POST['needs'])) {
                $immediate_needs = array_map('sanitizeInput', $_POST['needs']);
            }
            $immediate_needs_json = json_encode($immediate_needs);
            
            // Map severity levels to display names
            $severity_display_map = [
                'green-1' => 'Favorable circumstances',
                'green-2' => 'Intact homes & accessible roads',
                'green-3' => 'Functional power & supplies',
                'green-4' => 'No flooding or major damage',
                'green-5' => 'Rebuilt infrastructure',
                'orange-1' => 'Moderate problems',
                'orange-2' => 'Minor structural damage',
                'orange-3' => 'Partially accessible roads',
                'orange-4' => 'Limited supplies & sporadic outages',
                'orange-5' => 'Minor floods & safety issues',
                'red-1' => 'Critical situations',
                'red-2' => 'Heavy devastation',
                'red-3' => 'Widespread power loss',
                'red-4' => 'Resource unavailability',
                'red-5' => 'Significant security problems'
            ];
            
            $severity_display = $severity_display_map[$required_fields['severity']] ?? $required_fields['severity'];
            
            // Extract city from address
            $city = explode(',', $required_fields['location'])[0];
            
            // Begin transaction
            $pdo->beginTransaction();
            
            // Insert into disasters table
            $sql = "INSERT INTO disasters (
                disaster_name, type_id, severity_level, severity_display, address, city, state,
                description, reporter_name, reporter_phone, alternate_contact,
                landmark, people_affected, immediate_needs, current_situation, image_path, tracking_id, status
            ) VALUES (
                :disaster_name, :type_id, :severity_level, :severity_display, :address, :city, :state,
                :description, :reporter_name, :reporter_phone, :alternate_contact,
                :landmark, :people_affected, :immediate_needs, :current_situation, :image_path, :tracking_id, 'pending'
            )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':disaster_name' => substr($required_fields['description'], 0, 100),
                ':type_id' => intval($required_fields['disaster_type']),
                ':severity_level' => $required_fields['severity'],
                ':severity_display' => $severity_display,
                ':address' => $required_fields['location'],
                ':city' => trim($city),
                ':state' => 'Philippines',
                ':description' => $required_fields['description'],
                ':reporter_name' => $reporter_name,
                ':reporter_phone' => $required_fields['phone'],
                ':alternate_contact' => $alternate_contact,
                ':landmark' => $landmark,
                ':people_affected' => $people_affected,
                ':immediate_needs' => $immediate_needs_json,
                ':current_situation' => $current_situation,
                ':image_path' => $image_path,
                ':tracking_id' => $tracking_id
            ]);
            
            $disaster_id = $pdo->lastInsertId();
            
            // Create initial disaster update entry
            $update_sql = "INSERT INTO disaster_updates (
                disaster_id, user_id, update_type, title, description, is_public
            ) VALUES (
                :disaster_id, 1, 'general', 'Report Received', :description, TRUE
            )";
            
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                ':disaster_id' => $disaster_id,
                ':description' => "Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level."
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            $submission_result = [
                'success' => true,
                'tracking_id' => $tracking_id,
                'message' => 'Report submitted successfully!'
            ];
            
        } else {
            $submission_result = [
                'success' => false,
                'errors' => $validation_errors
            ];
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Report submission error: " . $e->getMessage());
        $submission_result = [
            'success' => false,
            'message' => 'An error occurred while submitting your report. Please try again.'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Emergency - iMSafe Disaster Monitoring System</title>
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
            color: white !important;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .tracking-info h3,
        .tracking-info p {
            color: white !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        .tracking-id {
            font-size: 1.5em;
            font-weight: bold;
            margin: 10px 0;
            letter-spacing: 2px;
            color: white !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }
        .back-button:hover {
            color: #5a67d8;
        }
        .emergency-page {
            min-height: 100vh;
            padding: 80px 0 40px;
            background: linear-gradient(135deg, #f8faff 0%, #e6f3ff 100%);
        }
        
        /* Override form styles for better visibility */
        .emergency-form {
            background: lightblue !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
        }
        
        .emergency-form .form-group label {
            color: #374151 !important;
            font-weight: 600;
        }
        
        .emergency-form input,
        .emergency-form select,
        .emergency-form textarea {
            border: 2px solid #ebe5e5ff;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: white;
            width: 100%;
            color: #374151;
            font-family: inherit;
        }
        
        .emergency-form input:focus,
        .emergency-form select:focus,
        .emergency-form textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151 !important;
            font-weight: 500;
            cursor: pointer;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .btn-emergency {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            margin: 20px 0;
        }
        
        .btn-emergency:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }
        
        .emergency-note {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .emergency-note p {
            color: #1e40af;
            margin: 0;
        }
        
        /* Ensure dropdowns show their selection properly */
        .emergency-form select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
            cursor: pointer;
        }
        
        .emergency-form select:invalid {
            color: #9ca3af;
        }
        
        .emergency-form select option {
            color: #374151;
            background: white;
        }
        
        .emergency-form select option:first-child {
            color: #9ca3af;
        }
        
        /* Image Upload Styles */
        .image-upload-container {
            position: relative;
            margin-top: 8px;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }
        
        .file-upload-wrapper {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            background: #f9fafb;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-wrapper:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .upload-icon {
            font-size: 3em;
            color: #6b7280;
            margin-bottom: 15px;
        }
        
        .upload-text {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .upload-title {
            font-weight: 600;
            color: #374151;
            font-size: 16px;
        }
        
        .upload-subtitle {
            font-size: 14px;
            color: #6b7280;
        }
        
        .image-preview {
            position: relative;
            margin-top: 15px;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .remove-image {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
        }
        
        .remove-image:hover {
            background: #dc2626;
        }
        
        .form-help {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        .form-help i {
            color: #3b82f6;
            flex-shrink: 0;
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
                <a href="index.php" class="nav-link">Home</a>
                <a href="index.php#features" class="nav-link">Features</a>
                <a href="index.php#about" class="nav-link">About</a>
                <a href="index.php#contact" class="nav-link">Contact</a>
                <a href="admin/dashboard.php" class="nav-link btn-login">Admin Panel</a>
                <a href="admin/register.php" class="nav-link btn-register">Register</a>
            </div>
            <div class="hamburger" id="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- Emergency Report Section -->
    <section class="emergency-page">
        <div class="container">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
            
            <div class="emergency-header">
                <h1><i class="fas fa-exclamation-triangle"></i> Report an Emergency</h1>
                <p>Quick report for immediate LGU response within 24-48 hours</p>
            </div>
            
            <!-- Display submission result -->
            <?php if ($submission_result): ?>
                <?php if ($submission_result['success']): ?>
                    <div class="tracking-info">
                        <i class="fas fa-check-circle" style="font-size: 2em; margin-bottom: 10px;"></i>
                        <h3>Report Submitted Successfully!</h3>
                        <div class="tracking-id"><?php echo htmlspecialchars($submission_result['tracking_id']); ?></div>
                        <p>Your emergency report has been submitted and assigned to the appropriate LGU. You will receive acknowledgment within 24-48 hours. Save your tracking ID for reference.</p>
                        <p><strong>Next Steps:</strong> The LGU will contact you at the provided phone number. Keep your phone accessible.</p>
                        <div style="margin-top: 20px;">
                            <a href="index.php" class="btn btn-secondary">Return to Home</a>
                            <a href="track_report.php?tracking_id=<?php echo urlencode($submission_result['tracking_id']); ?>" class="btn btn-primary" style="margin-left: 10px;">Track This Report</a>
                            <a href="report_emergency.php" class="btn btn-primary" style="margin-left: 10px;">Submit Another Report</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php if (isset($submission_result['errors'])): ?>
                            <strong>Please fix the following errors:</strong>
                            <ul style="margin: 10px 0 0 20px;">
                                <?php foreach ($submission_result['errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <?php echo htmlspecialchars($submission_result['message']); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="emergency-form-container">
                <form method="POST" action="report_emergency.php" class="emergency-form" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="disasterType">Disaster Type</label>
                            <select id="disasterType" name="disaster_type" required>
                                <option value="">Select disaster type...</option>
                                <?php 
                                if (!empty($disaster_types)) {
                                    foreach ($disaster_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['type_id']); ?>" 
                                                <?php echo (isset($_POST['disaster_type']) && $_POST['disaster_type'] == $type['type_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['type_name']); ?>
                                        </option>
                                    <?php endforeach;
                                } else {
                                    echo '<option value="">No disaster types available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="severity">Current Situation</label>
                            <select id="severity" name="severity" required>
                                <option value="">Describe the current situation...</option>
                                <option value="green-1" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'green-1') ? 'selected' : ''; ?>>Favorable circumstances</option>
                                <option value="green-2" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'green-2') ? 'selected' : ''; ?>>Intact homes & accessible roads</option>
                                <option value="green-3" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'green-3') ? 'selected' : ''; ?>>Functional power & supplies</option>
                                <option value="green-4" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'green-4') ? 'selected' : ''; ?>>No flooding or major damage</option>
                                <option value="green-5" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'green-5') ? 'selected' : ''; ?>>Rebuilt infrastructure</option>
                                <option value="orange-1" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'orange-1') ? 'selected' : ''; ?>>Moderate problems</option>
                                <option value="orange-2" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'orange-2') ? 'selected' : ''; ?>>Minor structural damage</option>
                                <option value="orange-3" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'orange-3') ? 'selected' : ''; ?>>Partially accessible roads</option>
                                <option value="orange-4" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'orange-4') ? 'selected' : ''; ?>>Limited supplies & sporadic outages</option>
                                <option value="orange-5" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'orange-5') ? 'selected' : ''; ?>>Minor floods & safety issues</option>
                                <option value="red-1" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'red-1') ? 'selected' : ''; ?>>Critical situations</option>
                                <option value="red-2" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'red-2') ? 'selected' : ''; ?>>Heavy devastation</option>
                                <option value="red-3" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'red-3') ? 'selected' : ''; ?>>Widespread power loss</option>
                                <option value="red-4" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'red-4') ? 'selected' : ''; ?>>Resource unavailability</option>
                                <option value="red-5" <?php echo (isset($_POST['severity']) && $_POST['severity'] == 'red-5') ? 'selected' : ''; ?>>Significant security problems</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Your Address</label>
                            <input type="text" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                                   placeholder="Complete address (required for response)" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Contact Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                   placeholder="+63 9XX XXX XXXX" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" 
                                  placeholder="Describe the situation..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="emergency_image">Emergency Photo (Optional)</label>
                        <div class="image-upload-container">
                            <input type="file" id="emergency_image" name="emergency_image" 
                                   accept="image/*" class="file-input">
                            <div class="file-upload-wrapper">
                                <div class="upload-icon">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <div class="upload-text">
                                    <span class="upload-title">Click to upload photo</span>
                                    <span class="upload-subtitle">JPG, PNG, or GIF up to 5MB</span>
                                </div>
                            </div>
                            <div class="image-preview" id="imagePreview" style="display: none;">
                                <img id="previewImg" src="" alt="Preview">
                                <button type="button" class="remove-image" onclick="removeImage()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-help">
                            <i class="fas fa-info-circle"></i>
                            Upload a photo of the emergency situation to help responders assess the severity and plan appropriate response.
                        </small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reporterName">Your Name (Optional)</label>
                            <input type="text" id="reporterName" name="reporter_name" 
                                   value="<?php echo htmlspecialchars($_POST['reporter_name'] ?? ''); ?>"
                                   placeholder="Full name (optional for anonymous reporting)">
                        </div>
                        <div class="form-group">
                            <label for="alternateContact">Alternate Contact (Optional)</label>
                            <input type="tel" id="alternateContact" name="alternate_contact" 
                                   value="<?php echo htmlspecialchars($_POST['alternate_contact'] ?? ''); ?>"
                                   placeholder="Alternate phone number">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="landmark">Nearby Landmark</label>
                            <input type="text" id="landmark" name="landmark" 
                                   value="<?php echo htmlspecialchars($_POST['landmark'] ?? ''); ?>"
                                   placeholder="e.g., Near SM Mall, City Hall, etc.">
                        </div>
                        <div class="form-group">
                            <label for="peopleAffected">People Affected</label>
                            <select id="peopleAffected" name="people_affected">
                                <option value="">Select range...</option>
                                <option value="1-5" <?php echo (isset($_POST['people_affected']) && $_POST['people_affected'] == '1-5') ? 'selected' : ''; ?>>1-5 people</option>
                                <option value="6-10" <?php echo (isset($_POST['people_affected']) && $_POST['people_affected'] == '6-10') ? 'selected' : ''; ?>>6-10 people</option>
                                <option value="11-25" <?php echo (isset($_POST['people_affected']) && $_POST['people_affected'] == '11-25') ? 'selected' : ''; ?>>11-25 people</option>
                                <option value="26-50" <?php echo (isset($_POST['people_affected']) && $_POST['people_affected'] == '26-50') ? 'selected' : ''; ?>>26-50 people</option>
                                <option value="51-100" <?php echo (isset($_POST['people_affected']) && $_POST['people_affected'] == '51-100') ? 'selected' : ''; ?>>51-100 people</option>
                                <option value="100+" <?php echo (isset($_POST['people_affected']) && $_POST['people_affected'] == '100+') ? 'selected' : ''; ?>>More than 100 people</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Immediate Needs</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="needs[]" value="medical_assistance" 
                                       <?php echo (isset($_POST['needs']) && in_array('medical_assistance', $_POST['needs'])) ? 'checked' : ''; ?>>
                                Medical Assistance
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="needs[]" value="food_water" 
                                       <?php echo (isset($_POST['needs']) && in_array('food_water', $_POST['needs'])) ? 'checked' : ''; ?>>
                                Food & Water
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="needs[]" value="shelter" 
                                       <?php echo (isset($_POST['needs']) && in_array('shelter', $_POST['needs'])) ? 'checked' : ''; ?>>
                                Shelter/Evacuation
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="needs[]" value="rescue" 
                                       <?php echo (isset($_POST['needs']) && in_array('rescue', $_POST['needs'])) ? 'checked' : ''; ?>>
                                Search & Rescue
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="needs[]" value="transportation" 
                                       <?php echo (isset($_POST['needs']) && in_array('transportation', $_POST['needs'])) ? 'checked' : ''; ?>>
                                Transportation
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="needs[]" value="security" 
                                       <?php echo (isset($_POST['needs']) && in_array('security', $_POST['needs'])) ? 'checked' : ''; ?>>
                                Security/Safety
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="currentSituation">Current Situation & Hazards</label>
                        <textarea id="currentSituation" name="current_situation" rows="2" 
                                  placeholder="Describe current conditions, ongoing hazards, accessibility issues..."><?php echo htmlspecialchars($_POST['current_situation'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="submit_report" class="btn btn-emergency">
                        <i class="fas fa-paper-plane"></i>
                        Submit Emergency Report
                    </button>
                    
                    <div class="emergency-note">
                        <i class="fas fa-clock"></i>
                        <p>Your report will be assigned to the appropriate LGU and acknowledged within 2-48 hours depending on severity. A tracking ID will be provided for follow-up.</p>
                    </div>
                </form>
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#features">Features</a></li>
                        <li><a href="index.php#about">About</a></li>
                        <li><a href="admin/dashboard.php">Admin Panel</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Emergency</h4>
                    <ul>
                        <li><a href="report_emergency.php">Report Emergency</a></li>
                        <li><a href="tel:911">Call 911</a></li>
                        <li><a href="index.php#contact">Contact Support</a></li>
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
    <script>
        // Debug and improve form functionality
        document.addEventListener('DOMContentLoaded', function() {
            const disasterTypeSelect = document.getElementById('disasterType');
            const severitySelect = document.getElementById('severity');
            
            // Debug: Log what's happening with selections
            disasterTypeSelect.addEventListener('change', function() {
                console.log('Disaster type selected:', this.value, 'Text:', this.options[this.selectedIndex].text);
            });
            
            severitySelect.addEventListener('change', function() {
                console.log('Severity selected:', this.value, 'Text:', this.options[this.selectedIndex].text);
            });
            
            // Ensure selections are visible
            function refreshSelect(select) {
                const value = select.value;
                select.blur();
                select.focus();
                select.value = value;
            }
            
            // Apply to all selects
            document.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', function() {
                    refreshSelect(this);
                });
            });
            
            // Check initial state
            console.log('Initial disaster type value:', disasterTypeSelect.value);
            console.log('Initial severity value:', severitySelect.value);
            
            // Image upload functionality
            const imageInput = document.getElementById('emergency_image');
            const imagePreview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            const fileUploadWrapper = document.querySelector('.file-upload-wrapper');
            
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!validTypes.includes(file.type)) {
                        alert('Please select a valid image file (JPG, PNG, or GIF).');
                        this.value = '';
                        return;
                    }
                    
                    // Validate file size (5MB max)
                    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                    if (file.size > maxSize) {
                        alert('File size must be less than 5MB.');
                        this.value = '';
                        return;
                    }
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        imagePreview.style.display = 'block';
                        fileUploadWrapper.style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
        
        function removeImage() {
            const imageInput = document.getElementById('emergency_image');
            const imagePreview = document.getElementById('imagePreview');
            const fileUploadWrapper = document.querySelector('.file-upload-wrapper');
            
            imageInput.value = '';
            imagePreview.style.display = 'none';
            fileUploadWrapper.style.display = 'block';
        }
    </script>
</body>
</html>