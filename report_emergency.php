<?php
session_start();

// Load Composer autoloader for PHPMailer
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// Include database connection
require_once 'config/database.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_role = $is_logged_in ? $_SESSION['role'] : '';
$user_name = $is_logged_in ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : '';

// Get user's saved address if logged in
$user_address = null;
if ($is_logged_in) {
    try {
        $addr_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND is_primary = 1 LIMIT 1");
        $addr_stmt->execute([$user_id]);
        $user_address = $addr_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching user address: " . $e->getMessage());
    }
}

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

$selected_disaster_type = isset($_POST['disaster_type']) ? (int)$_POST['disaster_type'] : null;

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

        $selected_disaster_type_value = isset($_POST['disaster_type']) ? sanitizeInput($_POST['disaster_type']) : '';

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
            'disaster_type' => $selected_disaster_type_value,
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
        
        // Validate email if provided
        $reporter_email = sanitizeInput($_POST['reporter_email'] ?? '');
        if (!empty($reporter_email) && !filter_var($reporter_email, FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "Invalid email address format";
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
                $upload_base_dir = __DIR__ . '/uploads/emergency_images/';
                $relative_upload_dir = 'uploads/emergency_images/';
                $allowed_mime_types = [
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/pjpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp'
                ];
                $allowed_extension_types = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp'
                ];
                $max_size = 5 * 1024 * 1024; // 5MB

                $tmp_name = $_FILES['emergency_image']['tmp_name'];
                $file_type = $_FILES['emergency_image']['type'];
                $file_size = $_FILES['emergency_image']['size'];
                $original_name = $_FILES['emergency_image']['name'];

                // Detect mime using finfo for additional safety
                $detected_type = null;
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $detected_type = finfo_file($finfo, $tmp_name) ?: null;
                        finfo_close($finfo);
                    }
                }
                $mime_to_check = $detected_type ?: $file_type;
                $normalized_mime = $mime_to_check ? strtolower((string)$mime_to_check) : '';
                $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                $final_extension = null;
                if ($normalized_mime && isset($allowed_mime_types[$normalized_mime])) {
                    $final_extension = $allowed_mime_types[$normalized_mime];
                }

                if ($final_extension === null && $file_extension && isset($allowed_extension_types[$file_extension])) {
                    $final_extension = $file_extension === 'jpeg' ? 'jpg' : $file_extension;
                    if (empty($normalized_mime)) {
                        $normalized_mime = $allowed_extension_types[$file_extension];
                    }
                    error_log('Image upload accepted via extension fallback. Reported MIME: ' . ($mime_to_check ?: 'none') . ' using extension .' . $final_extension);
                }

                // Validate file type and size
                if ($final_extension === null) {
                    $upload_error = "Invalid file type. Please upload JPG, PNG, GIF, or WEBP files only.";
                    error_log('Upload rejected due to unsupported MIME/extension. Reported MIME: ' . ($mime_to_check ?: 'none') . ' original name: ' . $original_name);
                } elseif ($file_size > $max_size) {
                    $upload_error = "File too large. Maximum size is 5MB.";
                } elseif (function_exists('getimagesize') && @getimagesize($tmp_name) === false) {
                    $upload_error = "Invalid image file.";
                    error_log('Upload rejected because getimagesize failed for file: ' . $original_name);
                } else {
                    // Create unique filename
                    $filename = 'emergency_' . time() . '_' . uniqid() . '.' . $final_extension;
                    $target_path = $upload_base_dir . $filename;

                    // Ensure upload directory exists and is writable
                    if (!is_dir($upload_base_dir)) {
                        if (!mkdir($upload_base_dir, 0775, true) && !is_dir($upload_base_dir)) {
                            $upload_error = "Failed to create upload directory.";
                            error_log('Failed to create directory: ' . $upload_base_dir);
                        }
                    }

                    if (empty($upload_error) && !is_writable($upload_base_dir)) {
                        $attempt_chmod = @chmod($upload_base_dir, 0775);
                        clearstatcache(true, $upload_base_dir);

                        if ((!$attempt_chmod || !is_writable($upload_base_dir)) && @chmod($upload_base_dir, 0777)) {
                            clearstatcache(true, $upload_base_dir);
                        }

                        if (!is_writable($upload_base_dir)) {
                            $upload_error = "Upload directory is not writable.";
                            error_log('Upload directory not writable: ' . $upload_base_dir);
                        } else {
                            error_log('Upload directory permissions adjusted automatically: ' . $upload_base_dir . ' (current mode: ' . substr(sprintf('%o', fileperms($upload_base_dir)), -4) . ')');
                        }
                    }

                    if (empty($upload_error)) {
                        if (move_uploaded_file($tmp_name, $target_path)) {
                            $image_path = $relative_upload_dir . $filename;
                            @chmod($target_path, 0644);
                            error_log('Image uploaded successfully: ' . $target_path);
                        } else {
                            $upload_error = "Failed to save uploaded image.";
                            error_log('Failed to move uploaded file to: ' . $target_path . ' (tmp: ' . $tmp_name . ')');
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

            // Add landmark, people_affected, current_situation, immediate_needs, reporter_email to insert if columns exist
            $has_landmark = false;
            $has_people_affected = false;
            $has_current_situation = false;
            $has_immediate_needs = false;
            $has_reporter_email = false;
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
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM disasters LIKE 'reporter_email'")->fetchAll();
                if (!empty($cols)) $has_reporter_email = true;
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
                $has_reporter_email ? 'reporter_email' : null,
                'reported_by_user_id', // Add this field to link disasters to users
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
                ':reported_by_user_id' => $user_id, // Link to logged-in user, or NULL for anonymous
                ':status' => 'ON GOING',
                ':created_at' => date('Y-m-d H:i:s')
            ];
            if ($has_severity_color_column) $params[':severity_color'] = $severity_color;
            if ($has_assessments_column) $params[':assessments'] = $assessments_json;
            if ($has_landmark) $params[':landmark'] = $landmark;
            if ($has_people_affected) $params[':people_affected'] = $people_affected;
            if ($has_current_situation) $params[':current_situation'] = $current_situation;
            if ($has_immediate_needs) $params[':immediate_needs'] = $immediate_needs_json;
            if ($has_reporter_email) $params[':reporter_email'] = $reporter_email;
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
            
            // Send tracking email to reporter if email is provided
            if (!empty($reporter_email)) {
                try {
                    // Get disaster type name for email
                    $disaster_type_stmt = $pdo->prepare("SELECT type_name FROM disaster_types WHERE type_id = ?");
                    $disaster_type_stmt->execute([$type_id]);
                    $type_name = $disaster_type_stmt->fetchColumn();
                    
                    // Prepare disaster data for email
                    $disaster_data = [
                        'tracking_id' => $tracking_id,
                        'disaster_name' => $disaster_name,
                        'type_name' => $type_name ?: 'Emergency Report',
                        'city' => $city,
                        'province' => $province,
                        'severity_display' => $severity_display,
                        'description' => $description,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $email_sent = false;
                    
                    // Try PHPMailer first, fallback to simple mail() function
                    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                        require_once 'includes/email_helper.php';
                        $email_sent = sendTrackingEmail($reporter_email, $reporter_name, $tracking_id, $disaster_data);
                        error_log("Used PHPMailer for email to: {$reporter_email}");
                    } else {
                        require_once 'includes/simple_email_helper.php';
                        $email_sent = sendTrackingEmailSimple($reporter_email, $reporter_name, $tracking_id, $disaster_data);
                        error_log("Used simple mail() function for email to: {$reporter_email}");
                    }
                    
                    if ($email_sent) {
                        error_log("Tracking email sent successfully to: {$reporter_email} for disaster #{$disaster_id}");
                    } else {
                        error_log("Failed to send tracking email to: {$reporter_email} for disaster #{$disaster_id}");
                    }
                } catch (Exception $email_error) {
                    // Log error but don't stop the submission process
                    error_log("Error sending tracking email to {$reporter_email}: " . $email_error->getMessage());
                }
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
    <link rel="icon" type="image/x-icon" href="assets/images/icon2.png">
    <link rel="stylesheet" href="assets/css/report.css">
</head>
<body>
    <!-- Navigation -->
    <?php require_once __DIR__ . '/includes/public_nav.php'; ?>

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
                                <label for="disaster_type">Disaster Type</label>
                                <select id="disaster_type" name="disaster_type" required>
                                    <option value="">Select disaster type...</option>
                                    <?php if (!empty($disaster_types)): ?>
                                        <?php foreach ($disaster_types as $type): ?>
                                            <?php $type_id_val = (int)($type['type_id'] ?? 0); ?>
                                            <option value="<?php echo htmlspecialchars($type_id_val); ?>" <?php echo ($selected_disaster_type === $type_id_val) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['type_name'] ?? ''); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

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
                        </div>

                        <div class="form-row">
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
                    </div>

                    <!-- Location Information -->
                    <div class="form-section section-location">
                        <h3 class="section-title">📍 Location Information</h3>
                        
                        <?php if ($is_logged_in && $user_address): ?>
                        <!-- Address Choice Buttons -->
                        <div class="address-choice-container" style="margin-bottom: 20px;">
                            <p style="margin-bottom: 10px; font-weight: 500; color: #374151;">
                                <i class="fas fa-info-circle" style="color: #4c63d2;"></i> 
                                Would you like to use your saved address or report from a different location?
                            </p>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button type="button" id="useSavedAddress" class="address-choice-btn saved-address-btn" style="flex: 1; min-width: 200px; padding: 12px 20px; background: #4c63d2; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                                    <i class="fas fa-home"></i> Use My Saved Address
                                </button>
                                <button type="button" id="useNewAddress" class="address-choice-btn new-address-btn" style="flex: 1; min-width: 200px; padding: 12px 20px; background: #e5e7eb; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                                    <i class="fas fa-map-marker-alt"></i> Report Different Location
                                </button>
                            </div>
                            <div id="savedAddressPreview" style="display: none; margin-top: 15px; padding: 15px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; color: #16a34a;">
                                <p style="font-weight: 600; margin-bottom: 8px;"><i class="fas fa-check-circle"></i> Using Your Saved Address:</p>
                                <p style="margin: 0; line-height: 1.6;">
                                    <?php 
                                    $addr_parts = array_filter([
                                        $user_address['house_no'] ?? '',
                                        $user_address['purok'] ?? '',
                                        $user_address['barangay'] ?? '',
                                        $user_address['city'] ?? '',
                                        $user_address['province'] ?? '',
                                        $user_address['region'] ?? ''
                                    ]);
                                    echo htmlspecialchars(implode(', ', $addr_parts));
                                    ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
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

                        <!-- Reporter Name & Email -->
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="reporterName">Your Name (Optional)</label>
                                <input type="text" id="reporterName" name="reporter_name" 
                                       value="<?php echo htmlspecialchars($_POST['reporter_name'] ?? ''); ?>"
                                       placeholder="Full name (optional for anonymous reporting)">
                                <small class="form-help">Leave blank for anonymous reporting</small>
                            </div>
                            <div class="form-group">
                                <label for="reporterEmail">Email Address (Optional)</label>
                                <input type="email" id="reporterEmail" name="reporter_email" 
                                       value="<?php echo htmlspecialchars($_POST['reporter_email'] ?? ''); ?>"
                                       placeholder="your.email@example.com">
                                <small class="form-help">We'll send you a tracking ID and updates via email</small>
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
    <?php require_once __DIR__ . '/includes/public_footer.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
    <script>
        // Set form defaults for JavaScript restoration
        window.formDefaults = {
            disaster_type: '<?php echo htmlspecialchars($_POST['disaster_type'] ?? ''); ?>',
            province: '<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>',
            city: '<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>',
            barangay: '<?php echo htmlspecialchars($_POST['barangay'] ?? ''); ?>',
            purok: '<?php echo htmlspecialchars($_POST['purok'] ?? ''); ?>',
            house_no: '<?php echo htmlspecialchars($_POST['house_no'] ?? ''); ?>',
            reporter_email: '<?php echo htmlspecialchars($_POST['reporter_email'] ?? ''); ?>',
            severity_color: '<?php echo htmlspecialchars($_POST['severity_color'] ?? ''); ?>',
            particular: '<?php echo htmlspecialchars($_POST['particular'] ?? ''); ?>',
            particular_color: '<?php echo htmlspecialchars($_POST['particular_color'] ?? ''); ?>',
            particular_detail: '<?php echo htmlspecialchars($_POST['particular_detail'] ?? ''); ?>'
        };
        
        <?php if ($is_logged_in && $user_address): ?>
        // User's saved address data
        window.userSavedAddress = {
            house_no: '<?php echo htmlspecialchars($user_address['house_no'] ?? ''); ?>',
            purok: '<?php echo htmlspecialchars($user_address['purok'] ?? ''); ?>',
            barangay: '<?php echo htmlspecialchars($user_address['barangay'] ?? ''); ?>',
            city: '<?php echo htmlspecialchars($user_address['city'] ?? ''); ?>',
            province: '<?php echo htmlspecialchars($user_address['province'] ?? ''); ?>',
            region: '<?php echo htmlspecialchars($user_address['region'] ?? ''); ?>',
            postal_code: '<?php echo htmlspecialchars($user_address['postal_code'] ?? ''); ?>',
            landmark: '<?php echo htmlspecialchars($user_address['landmark'] ?? ''); ?>'
        };
        
        // Address choice functionality
        document.addEventListener('DOMContentLoaded', function() {
            const useSavedBtn = document.getElementById('useSavedAddress');
            const useNewBtn = document.getElementById('useNewAddress');
            const savedPreview = document.getElementById('savedAddressPreview');
            const addressFields = document.querySelectorAll('#region, #province, #city, #barangay, #purok, #house_no, #landmark');
            
            let usingSavedAddress = false;
            
            // Function to fill address fields
            function fillSavedAddress() {
                // Helper function to find option by case-insensitive match
                function findAndSelectOption(selectElement, targetValue) {
                    if (!selectElement || !targetValue) return false;
                    
                    const targetLower = targetValue.toString().toLowerCase().trim();
                    const options = selectElement.options;
                    
                    for (let i = 0; i < options.length; i++) {
                        const optionValue = options[i].value.toLowerCase().trim();
                        if (optionValue === targetLower) {
                            selectElement.selectedIndex = i;
                            return true;
                        }
                    }
                    return false;
                }
                
                // Set region (uppercase to match CALABARZON format)
                const regionSelect = document.getElementById('region');
                const regionValue = window.userSavedAddress.region.toUpperCase();
                if (regionSelect) {
                    regionSelect.value = regionValue;
                }
                
                // Set province with case-insensitive matching
                const provinceSelect = document.getElementById('province');
                if (provinceSelect) {
                    findAndSelectOption(provinceSelect, window.userSavedAddress.province);
                    // Trigger change event to load cities
                    provinceSelect.dispatchEvent(new Event('change'));
                }
                
                // Wait for cities to load, then set city
                setTimeout(() => {
                    const citySelect = document.getElementById('city');
                    if (citySelect) {
                        findAndSelectOption(citySelect, window.userSavedAddress.city);
                        // Trigger change event to load barangays
                        citySelect.dispatchEvent(new Event('change'));
                    }
                    
                    // Wait for barangays to load
                    setTimeout(() => {
                        const barangaySelect = document.getElementById('barangay');
                        if (barangaySelect) {
                            findAndSelectOption(barangaySelect, window.userSavedAddress.barangay);
                        }
                        
                        document.getElementById('purok').value = window.userSavedAddress.purok;
                        document.getElementById('house_no').value = window.userSavedAddress.house_no;
                        if (document.getElementById('landmark')) {
                            document.getElementById('landmark').value = window.userSavedAddress.landmark;
                        }
                        
                        // Remove required attribute temporarily (they will be re-enabled when switching to new address)
                        const selectFields = document.querySelectorAll('#region, #province, #city, #barangay');
                        selectFields.forEach(field => {
                            if (field.tagName === 'SELECT') {
                                field.removeAttribute('required');
                            }
                        });
                    }, 500);
                }, 500);
                
                // Style address fields to look disabled but keep them enabled for form submission
                addressFields.forEach(field => {
                    field.style.background = '#f3f4f6';
                    field.style.cursor = 'not-allowed';
                    field.style.opacity = '0.7';
                    field.style.pointerEvents = 'none'; // Prevent clicks but keep values
                    // Make them readonly but NOT disabled (so values are submitted)
                    if (field.tagName !== 'SELECT') {
                        field.readOnly = true;
                    } else {
                        // For select elements, we can't use readonly, so we'll use a different approach
                        field.style.userSelect = 'none';
                        field.setAttribute('data-locked', 'true');
                        // Prevent any changes
                        field.addEventListener('mousedown', preventChange);
                        field.addEventListener('keydown', preventChange);
                    }
                });
                
                savedPreview.style.display = 'block';
                usingSavedAddress = true;
            }
            
            // Function to prevent changes to locked fields
            function preventChange(e) {
                if (e.target.hasAttribute('data-locked')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
            
            // Function to clear and enable address fields
            function enableNewAddress() {
                addressFields.forEach(field => {
                    field.style.background = '';
                    field.style.cursor = '';
                    field.style.opacity = '';
                    field.style.pointerEvents = '';
                    field.style.userSelect = '';
                    field.readOnly = false;
                    
                    if (field.tagName === 'SELECT') {
                        field.removeAttribute('data-locked');
                        field.removeEventListener('mousedown', preventChange);
                        field.removeEventListener('keydown', preventChange);
                    }
                    // Clear the field value
                    field.value = '';
                });
                
                // Reset dropdowns to default
                const citySelect = document.getElementById('city');
                const barangaySelect = document.getElementById('barangay');
                const regionSelect = document.getElementById('region');
                const provinceSelect = document.getElementById('province');
                
                if (regionSelect) {
                    regionSelect.value = '';
                }
                if (provinceSelect) {
                    provinceSelect.value = '';
                }
                if (citySelect) {
                    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                }
                if (barangaySelect) {
                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                }
                
                savedPreview.style.display = 'none';
                usingSavedAddress = false;
            }
            
            // Use Saved Address button
            useSavedBtn.addEventListener('click', function() {
                fillSavedAddress();
                
                // Update button styles
                useSavedBtn.style.background = '#4c63d2';
                useSavedBtn.style.color = 'white';
                useNewBtn.style.background = '#e5e7eb';
                useNewBtn.style.color = '#374151';
            });
            
            // Use New Address button
            useNewBtn.addEventListener('click', function() {
                enableNewAddress();
                
                // Update button styles
                useNewBtn.style.background = '#4c63d2';
                useNewBtn.style.color = 'white';
                useSavedBtn.style.background = '#e5e7eb';
                useSavedBtn.style.color = '#374151';
            });
            
            // Hover effects
            document.querySelectorAll('.address-choice-btn').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    const bgColor = window.getComputedStyle(this).backgroundColor;
                    if (bgColor !== 'rgb(76, 99, 210)') {
                        this.style.background = '#d1d5db';
                    } else {
                        this.style.transform = 'translateY(-2px)';
                        this.style.boxShadow = '0 4px 12px rgba(76, 99, 210, 0.3)';
                    }
                });
                
                btn.addEventListener('mouseleave', function() {
                    const bgColor = window.getComputedStyle(this).backgroundColor;
                    if (bgColor !== 'rgb(76, 99, 210)') {
                        this.style.background = '#e5e7eb';
                    } else {
                        this.style.transform = '';
                        this.style.boxShadow = '';
                    }
                });
            });
        });
        <?php endif; ?>
    </script>
    <script src="assets/js/particulars.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/report.js?v=<?php echo time(); ?>"></script>
</body>
</html>