<?php
// Include database connection
require_once 'config/database.php';

// Optional overrides file (create config/overrides.php to force static values)
if (file_exists(__DIR__ . '/config/overrides.php')) {
    include __DIR__ . '/config/overrides.php';
}

// Get disaster types for dropdown
try {
    // Only load active disaster types by default. Admins can toggle is_active in settings.
    $stmt = $pdo->query("SELECT type_id, type_name, description FROM disaster_types WHERE is_active = 1 ORDER BY type_name");
    $disaster_types = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching disaster types: " . $e->getMessage());
    $disaster_types = [];
}

// Optional: allow an override list to be set earlier (e.g. from a config file)
// Example usage (uncomment and customize if you want static types):
// $override_disaster_types = [ ['type_id'=>17,'type_name'=>'Earthquake'], ['type_id'=>18,'type_name'=>'Flood'] ];
if (!empty($override_disaster_types) && is_array($override_disaster_types)) {
    $disaster_types = $override_disaster_types;
} else {
    // Clean and deduplicate results coming from the database to avoid showing blanks/duplicates
    if (!empty($disaster_types) && is_array($disaster_types)) {
        $cleaned = [];
        $seen = [];
        foreach ($disaster_types as $dt) {
            $name = trim($dt['type_name'] ?? '');
            if ($name === '') continue; // skip empty names
            $lower = mb_strtolower($name);
            if (in_array($lower, $seen, true)) continue; // skip duplicates by name
            $seen[] = $lower;
            $cleaned[] = $dt;
        }
        // Sort alphabetically by type_name
        usort($cleaned, function($a, $b) {
            return strcmp($a['type_name'] ?? '', $b['type_name'] ?? '');
        });
        $disaster_types = $cleaned;
    }
}

// Handle form submission
$submission_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    try {
        // Update the address handling section
        $street = sanitizeInput($_POST['street'] ?? '');
        $barangay = sanitizeInput($_POST['barangay'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $province = sanitizeInput($_POST['province'] ?? '');
        $region = sanitizeInput($_POST['region'] ?? '');
        // severity_color may come from the old field name or the new particular_color select
        $severity_color = sanitizeInput($_POST['severity_color'] ?? $_POST['particular_color'] ?? '');

        // Basic contact/description fields
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');

        // Particular selections (will be normalized later as well)
        $selected_particular = sanitizeInput($_POST['particular'] ?? '');
        $selected_particular_color = sanitizeInput($_POST['particular_color'] ?? '');
        $selected_particular_detail_raw = $_POST['particular_detail'] ?? '';

        // Combine address components
        $full_address = trim("$street, $barangay, $city, $province");

        // Validate required fields - require the new rapid-assessment fields and address/contact
        $required_fields = [
            'particular' => $selected_particular,
            'particular_color' => $selected_particular_color,
            // normalize potential array into a string for validation
            'particular_detail' => is_array($selected_particular_detail_raw) ? implode(', ', array_map('sanitizeInput', $selected_particular_detail_raw)) : sanitizeInput($selected_particular_detail_raw),
            'street_address' => $street,
            'barangay' => $barangay,
            'city' => $city,
            'province' => $province,
            'phone' => $phone,
            'description' => $description
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
            // Particular selection: one of the 9 assessment items
            $selected_particular = sanitizeInput($_POST['particular'] ?? '');
            $selected_particular_color = sanitizeInput($_POST['particular_color'] ?? '');
            // particular_detail can be a single string or an array (when multiple green options selected)
            $selected_particular_detail_raw = $_POST['particular_detail'] ?? '';
            if (is_array($selected_particular_detail_raw)) {
                $sanitized_details = array_map('sanitizeInput', $selected_particular_detail_raw);
                // store as comma-separated for backward compatibility
                $selected_particular_detail = implode(', ', array_filter($sanitized_details));
            } else {
                $selected_particular_detail = sanitizeInput($selected_particular_detail_raw);
            }
            // Capture structured rapid assessment choices (optional)
            $assessments = [];
            if (!empty($_POST['assessments']) && is_array($_POST['assessments'])) {
                foreach ($_POST['assessments'] as $k => $v) {
                    $clean_k = preg_replace('/[^a-z0-9_\-]/i', '', $k);
                    $clean_v = sanitizeInput($v);
                    if (in_array($clean_v, ['green','orange','red'], true)) {
                        $assessments[$clean_k] = $clean_v;
                    }
                }
            }
            
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

            // Include selected particular into assessments payload
            if (!empty($selected_particular)) {
                $assessments['selected_particular'] = $selected_particular;
            }
            if (!empty($selected_particular_color)) {
                $assessments['selected_particular_color'] = $selected_particular_color;
            }
            if (!empty($selected_particular_detail)) {
                $assessments['selected_particular_detail'] = $selected_particular_detail;
            }

            // Encode assessments if any
            $assessments_json = !empty($assessments) ? json_encode($assessments) : null;
            
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
            
            // Determine severity_level: prefer explicit severity POST, otherwise derive from selected color
            $severity_level = sanitizeInput($_POST['severity'] ?? '');
            $color_for_severity = $severity_color ?: $selected_particular_color;
            if (empty($severity_level) && in_array($color_for_severity, ['green','orange','red'], true)) {
                // default to a mid-level severity per color (e.g. -2)
                $severity_level = $color_for_severity . '-2';
            }

            // Normalize severity display using map
            $severity_display = $severity_level ? ($severity_display_map[$severity_level] ?? $severity_level) : '';

            // Normalize color
            if (!empty($severity_color)) {
                $severity_color = in_array($severity_color, ['green','orange','red']) ? $severity_color : '';
            }

            // Disaster name/title: prefer explicit field, otherwise derive from description or particular
            $disaster_name = sanitizeInput($_POST['disaster_name'] ?? '');
            if (empty($disaster_name)) {
                $disaster_name = $selected_particular ? ucfirst(str_replace('_', ' ', $selected_particular)) : '';
            }
            if (empty($disaster_name)) {
                $disaster_name = substr($description, 0, 200);
            }
            $disaster_name = $disaster_name ?: 'Emergency Report';
            
            // tracking id already generated above

            // Ensure type_id and user_id exist (may be null for anonymous/web submissions)
            $type_id = !empty($_POST['disaster_type']) ? (int)$_POST['disaster_type'] : null;
            $user_id = isset($user_id) ? $user_id : null;
            // If no type_id was provided by the form, try to pick a sensible default
            if (empty($type_id)) {
                // Prefer an override list if provided earlier in the script
                if (!empty($override_disaster_types) && is_array($override_disaster_types)) {
                    $first = reset($override_disaster_types);
                    if (!empty($first['type_id'])) {
                        $type_id = (int)$first['type_id'];
                    }
                }
                // Otherwise use the first active disaster type we loaded at the top
                if (empty($type_id) && !empty($disaster_types) && is_array($disaster_types)) {
                    $first = reset($disaster_types);
                    if (!empty($first['type_id'])) {
                        $type_id = (int)$first['type_id'];
                    }
                }
                // As a last attempt, query the DB for any active disaster_type
                if (empty($type_id)) {
                    try {
                        $row = $pdo->query("SELECT type_id FROM disaster_types WHERE is_active = 1 LIMIT 1")->fetchColumn();
                        if ($row !== false) {
                            $type_id = (int)$row;
                        }
                    } catch (Exception $e) {
                        // ignore - we'll fallback below
                    }
                }
                // Final fallback: use 1 (common default in many schemas) and log a warning
                if (empty($type_id)) {
                    $type_id = 1;
                    error_log("report_emergency: no disaster_type provided and no default found; falling back to type_id=1");
                }
            }

            // Use previously composed full_address and append optional postal and country
            if (!empty($_POST['postal_code'])) {
                $full_address .= ' ' . sanitizeInput($_POST['postal_code']);
            }
            $full_address = trim($full_address);
            if ($full_address !== '') {
                $full_address .= ', Philippines';
            }
            
            // Begin transaction
            $pdo->beginTransaction();

            // Insert into database with separate address columns
            // Ensure severity_level is set (may have been derived earlier)
            $severity_level = isset($severity_level) ? $severity_level : null;

            // If the database has a separate severity_color column, attempt to include it; otherwise we store color in severity_display
            $has_severity_color_column = false; // conservative default
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM disasters LIKE 'severity_color'")->fetchAll();
                if (!empty($cols)) {
                    $has_severity_color_column = true;
                }
            } catch (Exception $e) {
                // ignore
            }
            // Check for assessments column
            $has_assessments_column = false;
            try {
                $cols2 = $pdo->query("SHOW COLUMNS FROM disasters LIKE 'assessments'")->fetchAll();
                if (!empty($cols2)) {
                    $has_assessments_column = true;
                }
            } catch (Exception $e) {
                // ignore
            }

            if ($has_severity_color_column) {
                if ($has_assessments_column) {
                    $sql = "INSERT INTO disasters (
                        tracking_id, disaster_name, type_id, severity_level, severity_display,
                        severity_color, assessments, address, city, province, state,
                        reporter_phone, description, image_path, source, status, created_at
                    ) VALUES (
                        :tracking_id, :disaster_name, :type_id, :severity_level, :severity_display,
                        :severity_color, :assessments, :address, :city, :province, :state,
                        :reporter_phone, :description, :image_path, :source, 'pending', NOW()
                    )";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':tracking_id' => $tracking_id,
                        ':disaster_name' => $disaster_name,
                        ':type_id' => $type_id,
                        ':severity_level' => $severity_level,
                        ':severity_display' => $severity_display,
                        ':severity_color' => $severity_color,
                        ':assessments' => $assessments_json,
                        ':address' => $full_address,
                        ':city' => $city,
                        ':province' => $province,
                        ':state' => $region,
                        ':reporter_phone' => $phone,
                        ':description' => $description,
                        ':image_path' => $image_path,
                        ':source' => 'web_form'
                    ]);
                } else {
                    $sql = "INSERT INTO disasters (
                        tracking_id, disaster_name, type_id, severity_level, severity_display,
                        severity_color, address, city, province, state,
                        reporter_phone, description, image_path, source, status, created_at
                    ) VALUES (
                        :tracking_id, :disaster_name, :type_id, :severity_level, :severity_display,
                        :severity_color, :address, :city, :province, :state,
                        :reporter_phone, :description, :image_path, :source, 'pending', NOW()
                    )";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':tracking_id' => $tracking_id,
                        ':disaster_name' => $disaster_name,
                        ':type_id' => $type_id,
                        ':severity_level' => $severity_level,
                        ':severity_display' => $severity_display,
                        ':severity_color' => $severity_color,
                        ':address' => $full_address,
                        ':city' => $city,
                        ':province' => $province,
                        ':state' => $region,
                        ':reporter_phone' => $phone,
                        ':description' => $description,
                        ':image_path' => $image_path,
                        ':source' => 'web_form'
                    ]);
                }
            } else {
                // Fallback: include color in severity_display to preserve information
                if ($severity_color) {
                    $severity_display = $severity_display . ' (' . ucfirst($severity_color) . ')';
                }
                if ($has_assessments_column) {
                    $sql = "INSERT INTO disasters (
                        tracking_id, disaster_name, type_id, severity_level, severity_display,
                        assessments, address, city, province, state,
                        reporter_phone, description, image_path, source, status, created_at
                    ) VALUES (
                        :tracking_id, :disaster_name, :type_id, :severity_level, :severity_display,
                        :assessments, :address, :city, :province, :state,
                        :reporter_phone, :description, :image_path, :source, 'pending', NOW()
                    )";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':tracking_id' => $tracking_id,
                        ':disaster_name' => $disaster_name,
                        ':type_id' => $type_id,
                        ':severity_level' => $severity_level,
                        ':severity_display' => $severity_display,
                        ':assessments' => $assessments_json,
                        ':address' => $full_address,
                        ':city' => $city,
                        ':province' => $province,
                        ':state' => $region,
                        ':reporter_phone' => $phone,
                        ':description' => $description,
                        ':image_path' => $image_path,
                        ':source' => 'web_form'
                    ]);
                } else {
                    $sql = "INSERT INTO disasters (
                        tracking_id, disaster_name, type_id, severity_level, severity_display,
                        address, city, province, state,
                        reporter_phone, description, image_path, source, status, created_at
                    ) VALUES (
                        :tracking_id, :disaster_name, :type_id, :severity_level, :severity_display,
                        :address, :city, :province, :state,
                        :reporter_phone, :description, :image_path, :source, 'pending', NOW()
                    )";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':tracking_id' => $tracking_id,
                        ':disaster_name' => $disaster_name,
                        ':type_id' => $type_id,
                        ':severity_level' => $severity_level,
                        ':severity_display' => $severity_display,
                        ':address' => $full_address,
                        ':city' => $city,
                        ':province' => $province,
                        ':state' => $region,
                        ':reporter_phone' => $phone,
                        ':description' => $description,
                        ':image_path' => $image_path,
                        ':source' => 'web_form'
                    ]);
                }
            }
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
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Log full exception for debugging
        error_log("Report submission error: " . $e->getMessage());
        error_log($e->getTraceAsString());
        // Return the exception message to the page temporarily to help debugging on local/dev
        $submission_result = [
            'success' => false,
            'message' => 'An error occurred while submitting your report: ' . $e->getMessage()
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
    <link rel="stylesheet" href="assets/css/report.css">
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
                    <div class="form-section section-incident">
                        <h3 class="section-title">Rapid assessment</h3>
                        <div class="form-row">
                        <div class="form-group">
                            <label for="particular">Particular</label>
                            <select id="particular" name="particular" required>
                                <option value="">Select particular...</option>
                                <option value="home_state" <?php echo (isset($_POST['particular']) && $_POST['particular']=='home_state') ? 'selected' : ''; ?>>Current state of home/building after the typhoon</option>
                                <option value="accessibility" <?php echo (isset($_POST['particular']) && $_POST['particular']=='accessibility') ? 'selected' : ''; ?>>Accessibility to road</option>
                                <option value="power" <?php echo (isset($_POST['particular']) && $_POST['particular']=='power') ? 'selected' : ''; ?>>Power Supply Status</option>
                                <option value="water" <?php echo (isset($_POST['particular']) && $_POST['particular']=='water') ? 'selected' : ''; ?>>Clean Water Supply</option>
                                <option value="food" <?php echo (isset($_POST['particular']) && $_POST['particular']=='food') ? 'selected' : ''; ?>>Food and essential supplies availability</option>
                                <option value="flooding" <?php echo (isset($_POST['particular']) && $_POST['particular']=='flooding') ? 'selected' : ''; ?>>Level of flooding</option>
                                <option value="safety" <?php echo (isset($_POST['particular']) && $_POST['particular']=='safety') ? 'selected' : ''; ?>>Level of safety</option>
                                <option value="readiness" <?php echo (isset($_POST['particular']) && $_POST['particular']=='readiness') ? 'selected' : ''; ?>>Readiness to go back to school</option>
                                <option value="transport" <?php echo (isset($_POST['particular']) && $_POST['particular']=='transport') ? 'selected' : ''; ?>>Transportation Status</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="particular_color">Color</label>
                            <select id="particular_color" name="particular_color" required>
                                <option value="">Select color...</option>
                                <option value="green" <?php echo (isset($_POST['particular_color']) && $_POST['particular_color']=='green') ? 'selected' : ''; ?>>Green</option>
                                <option value="orange" <?php echo (isset($_POST['particular_color']) && $_POST['particular_color']=='orange') ? 'selected' : ''; ?>>Orange</option>
                                <option value="red" <?php echo (isset($_POST['particular_color']) && $_POST['particular_color']=='red') ? 'selected' : ''; ?>>Red</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="particular_detail">Detail</label>
                            <select id="particular_detail" name="particular_detail" required>
                                <option value="">Choose detail...</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section section-address">
                        <h3 class="section-title">Address</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="region">Region</label>
                                <select id="region" name="region" required>
                                    <option value="">Select Region</option>
                                    <option value="CALABARZON" <?php echo (isset($_POST['region']) && $_POST['region'] == 'CALABARZON') ? 'selected' : ''; ?>>CALABARZON</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="province">Province</label>
                                <select id="province" name="province" required>
                                    <option value="">Select Province</option>
                                    <option value="Batangas" <?php echo (isset($_POST['province']) && $_POST['province'] == 'Batangas') ? 'selected' : ''; ?>>Batangas</option>
                                    <option value="Cavite" <?php echo (isset($_POST['province']) && $_POST['province'] == 'Cavite') ? 'selected' : ''; ?>>Cavite</option>
                                    <option value="Laguna" <?php echo (isset($_POST['province']) && $_POST['province'] == 'Laguna') ? 'selected' : ''; ?>>Laguna</option>
                                    <option value="Quezon" <?php echo (isset($_POST['province']) && $_POST['province'] == 'Quezon') ? 'selected' : ''; ?>>Quezon</option>
                                    <option value="Rizal" <?php echo (isset($_POST['province']) && $_POST['province'] == 'Rizal') ? 'selected' : ''; ?>>Rizal</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City/Municipality</label>
                                <select id="city" name="city" required>
                                    <option value="">Select City/Municipality</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="barangay">Barangay</label>
                                <select id="barangay" name="barangay" required>
                                    <option value="">Select Barangay</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="street">Street Address</label>
                            <input type="text" id="street" name="street" 
                                   value="<?php echo htmlspecialchars($_POST['street'] ?? ''); ?>"
                                   placeholder="House number and street name" required>
                        </div>
                    </div>
                    
                    <div class="form-section section-contact">
                        <h3 class="section-title">Contact</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Contact Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       placeholder="+63 9XX XXX XXXX" required>
                            </div>
                            <div class="form-group">
                                <!-- Empty for balance -->
                            </div>
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
                    </div>

                    <div class="form-section section-details">
                        <h3 class="section-title">Details</h3>
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
                                    <input type="checkbox" name="needs[]" value="shelter_repair_materials"
                                           <?php echo (isset($_POST['needs']) && in_array('shelter_repair_materials', $_POST['needs'])) ? 'checked' : ''; ?>>
                                    Shelter repair materials
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="needs[]" value="electricity_restoration"
                                           <?php echo (isset($_POST['needs']) && in_array('electricity_restoration', $_POST['needs'])) ? 'checked' : ''; ?>>
                                    Electricity restoration
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="needs[]" value="communication_services"
                                           <?php echo (isset($_POST['needs']) && in_array('communication_services', $_POST['needs'])) ? 'checked' : ''; ?>>
                                    Communication services
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="needs[]" value="internet_connection_services"
                                           <?php echo (isset($_POST['needs']) && in_array('internet_connection_services', $_POST['needs'])) ? 'checked' : ''; ?>>
                                    Internet connection services
                                </label>
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
                    </div>
                    
                    <!-- particular/color/detail moved to top of form -->
                    
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
                    <ul class="footer-links">
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
        // Set form defaults for JavaScript restoration
        window.formDefaults = {
            province: '<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>',
            city: '<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>',
            barangay: '<?php echo htmlspecialchars($_POST['barangay'] ?? ''); ?>',
            severity_color: '<?php echo htmlspecialchars($_POST['severity_color'] ?? ''); ?>',
            particular: '<?php echo htmlspecialchars($_POST['particular'] ?? ''); ?>',
            particular_color: '<?php echo htmlspecialchars($_POST['particular_color'] ?? ''); ?>',
            particular_detail: '<?php echo htmlspecialchars($_POST['particular_detail'] ?? ''); ?>'
        };
    </script>
    <script src="assets/js/particulars.js"></script>
    <script src="assets/js/report.js"></script>
</body>
</html>