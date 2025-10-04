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
        $house_no = sanitizeInput($_POST['house_no'] ?? '');
        $purok = sanitizeInput($_POST['purok'] ?? '');
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

        // Handle particular detail as single selection (consistent for all colors)
        $selected_particular_detail = sanitizeInput($selected_particular_detail_raw);

        // Combine address components
        $full_address = trim("$house_no, $purok, $barangay, $city, $province, $region");

        // Validate required fields - require the new rapid-assessment fields and address/contact
        $required_fields = [
            'particular' => $selected_particular,
            'particular_color' => $selected_particular_color,
            'particular_detail' => $selected_particular_detail,
            'purok' => $purok,
            'house_no' => $house_no,
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
            // $selected_particular, $selected_particular_color, $selected_particular_detail already set above

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

            // Add landmark, people_affected, current_situation, immediate_needs to insert if columns exist
            $has_landmark = false;
            $has_people_affected = false;
            $has_current_situation = false;
            $has_immediate_needs = false;
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM disasters LIKE 'landmark'")->fetchAll();
                if (!empty($cols)) $has_landmark = true;
            } catch (Exception $e) {}
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM disasters LIKE 'people_affected'")->fetchAll();
                if (!empty($cols)) $has_people_affected = true;
            } catch (Exception $e) {}
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM disasters LIKE 'current_situation'")->fetchAll();
                if (!empty($cols)) $has_current_situation = true;
            } catch (Exception $e) {}
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM disasters LIKE 'immediate_needs'")->fetchAll();
                if (!empty($cols)) $has_immediate_needs = true;
            } catch (Exception $e) {}

            // Build dynamic insert
            $fields = [
                'tracking_id', 'disaster_name', 'type_id', 'severity_level', 'severity_display',
                $has_severity_color_column ? 'severity_color' : null,
                $has_assessments_column ? 'assessments' : null,
                'address', 'purok', 'house_no', 'city', 'province', 'state',
                'reporter_name', 'reporter_phone', 'alternate_contact', 'description', 'image_path', 'source',
                $has_landmark ? 'landmark' : null,
                $has_people_affected ? 'people_affected' : null,
                $has_current_situation ? 'current_situation' : null,
                $has_immediate_needs ? 'immediate_needs' : null,
                'status', 'created_at'
            ];
            $fields = array_filter($fields); // remove nulls
            $placeholders = array_map(function($f) { return ':' . $f; }, $fields);
            $sql = "INSERT INTO disasters (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $params = [
                ':tracking_id' => $tracking_id,
                ':disaster_name' => $disaster_name,
                ':type_id' => $type_id,
                ':severity_level' => $severity_level,
                ':severity_display' => $severity_display,
                ':address' => $full_address,
                ':purok' => $purok,
                ':house_no' => $house_no,
                ':city' => $city,
                ':province' => $province,
                ':state' => $region,
                ':reporter_name' => $reporter_name,
                ':reporter_phone' => $phone,
                ':alternate_contact' => $alternate_contact,
                ':description' => $description,
                ':image_path' => $image_path,
                ':source' => 'web_form',
                ':status' => 'ON GOING',
                ':created_at' => date('Y-m-d H:i:s')
            ];
            if ($has_severity_color_column) $params[':severity_color'] = $severity_color;
            if ($has_assessments_column) $params[':assessments'] = $assessments_json;
            if ($has_landmark) $params[':landmark'] = $landmark;
            if ($has_people_affected) $params[':people_affected'] = $people_affected;
            if ($has_current_situation) $params[':current_situation'] = $current_situation;
            if ($has_immediate_needs) $params[':immediate_needs'] = $immediate_needs_json;
            $stmt->execute($params);
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
            
            // Create notifications for admins about the new report
            try {
                require_once 'admin/includes/notification_helper.php';
                $notified_count = createDisasterNotification($pdo, $disaster_id);
                if ($notified_count) {
                    error_log("Created notifications for {$notified_count} admin users for disaster report #{$disaster_id}");
                }
            } catch (Exception $notif_error) {
                // Log error but don't stop the submission process
                error_log("Error creating notifications: " . $notif_error->getMessage());
            }
            
            // Redirect to success page
            header('Location: success.php?tracking_id=' . urlencode($tracking_id));
            exit;
            
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
                <a href="track_report.php" class="nav-link">Track Report</a>
                <a href="login.php" class="nav-link btn-login">Admin Login</a>
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
            
            <div class="emergency-header">
                <h1><i class="fas fa-exclamation-triangle"></i> Report an Emergency</h1>
                <p>Quick report for immediate LGU response within 24-48 hours</p>
            </div>
            
            <!-- Display submission result -->
            <?php if ($submission_result): ?>
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
            
            <div class="emergency-form-container">
                <form method="POST" action="report_emergency.php" class="emergency-form" enctype="multipart/form-data">
                    <div class="form-section section-incident">
                        <h3 class="section-title">Rapid Assessment</h3>
                        
                        <!-- Color Legend -->
                        <div class="color-legend">
                            <h4 class="legend-title">LEGEND (COLORS)</h4>
                            <div class="legend-items">
                                <div class="legend-item">
                                    <div class="color-indicator green"></div>
                                    <span class="color-label"><strong>GREEN</strong> - GOOD</span>
                                </div>
                                <div class="legend-item">
                                    <div class="color-indicator orange"></div>
                                    <span class="color-label"><strong>ORANGE</strong> - MODERATE</span>
                                </div>
                                <div class="legend-item">
                                    <div class="color-indicator red"></div>
                                    <span class="color-label"><strong>RED</strong> - CRITICAL</span>
                                </div>
                            </div>
                        </div>
                        
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
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="particular_detail">Detail</label>
                                <select id="particular_detail" name="particular_detail" required>
                                    <option value="">Choose detail...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Location Information -->
                    <div class="form-section section-location">
                        <h3 class="section-title">📍 Location Information</h3>
                        
                        <!-- Region & Province -->
                        <div class="form-grid-2">
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

                        <!-- City & Barangay -->
                        <div class="form-grid-2">
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

                        <!-- Purok & House No. -->
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="purok">Purok</label>
                                <input type="text" id="purok" name="purok" 
                                       value="<?php echo htmlspecialchars($_POST['purok'] ?? ''); ?>"
                                       placeholder="Purok name or number" required>
                            </div>
                            <div class="form-group">
                                <label for="house_no">House No.</label>
                                <input type="text" id="house_no" name="house_no" 
                                       value="<?php echo htmlspecialchars($_POST['house_no'] ?? ''); ?>"
                                       placeholder="House number" required>
                            </div>
                        </div>

                        <!-- Nearby Landmark -->
                        <div class="form-group">
                            <label for="landmark">Nearby Landmark</label>
                            <input type="text" id="landmark" name="landmark" 
                                   value="<?php echo htmlspecialchars($_POST['landmark'] ?? ''); ?>"
                                   placeholder="e.g., Near SM Mall, City Hall, etc.">
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="form-section section-contact">
                        <h3 class="section-title">📞 Contact Information</h3>
                        
                        <!-- Primary & Alternate Contact -->
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="phone">Primary Contact Number *</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       placeholder="+63 9XX XXX XXXX" required>
                            </div>
                            <div class="form-group">
                                <label for="alternateContact">Alternate Contact (Optional)</label>
                                <input type="tel" id="alternateContact" name="alternate_contact" 
                                       value="<?php echo htmlspecialchars($_POST['alternate_contact'] ?? ''); ?>"
                                       placeholder="Alternate phone number">
                            </div>
                        </div>

                        <!-- Reporter Name -->
                        <div class="form-group">
                            <label for="reporterName">Your Name (Optional)</label>
                            <input type="text" id="reporterName" name="reporter_name" 
                                   value="<?php echo htmlspecialchars($_POST['reporter_name'] ?? ''); ?>"
                                   placeholder="Full name (optional for anonymous reporting)">
                            <small class="form-help">Leave blank for anonymous reporting</small>
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
                            <label for="current_situation">Current Situation</label>
                            <textarea id="current_situation" name="current_situation" rows="2" placeholder="Describe the current situation (optional)"><?php echo htmlspecialchars($_POST['current_situation'] ?? ''); ?></textarea>
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
                    <p>Protecting communities through advanced disaster monitoring and coordinated emergency response.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php#home">Home</a></li>
                        <li><a href="index.php#features">Features</a></li>
                        <li><a href="index.php#about">About</a></li>
                        <li><a href="login.php">Admin Login</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Emergency</h4>
                    <ul>
                        <li><a href="report_emergency.php">Report Emergency</a></li>
                        <li><a href="track_report.php">Track Your Report</a></li>
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
            purok: '<?php echo htmlspecialchars($_POST['purok'] ?? ''); ?>',
            house_no: '<?php echo htmlspecialchars($_POST['house_no'] ?? ''); ?>',
            severity_color: '<?php echo htmlspecialchars($_POST['severity_color'] ?? ''); ?>',
            particular: '<?php echo htmlspecialchars($_POST['particular'] ?? ''); ?>',
            particular_color: '<?php echo htmlspecialchars($_POST['particular_color'] ?? ''); ?>',
            particular_detail: '<?php echo htmlspecialchars($_POST['particular_detail'] ?? ''); ?>'
        };
    </script>
    <script src="assets/js/particulars.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/report.js?v=<?php echo time(); ?>"></script>
</body>
</html>