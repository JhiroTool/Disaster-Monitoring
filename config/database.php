<?php
/**
 * Database Configuration for Disaster Monitoring System
 * Created: September 27, 2025
 */

// Database configuration
$host = 'localhost';
$dbname = 'disaster_monitoring';
$username = 'root'; // Default XAMPP MySQL username
$password = ''; // Default XAMPP MySQL password (empty)

// Set default timezone
date_default_timezone_set('Asia/Manila');

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Disable emulated prepared statements
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch(PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

/**
 * Function to generate unique tracking ID
 */
function generateTrackingId() {
    return 'DM' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Function to sanitize input data
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Function to validate required fields
 */
function validateRequired($fields) {
    $errors = [];
    foreach ($fields as $field => $value) {
        if (empty($value)) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
    }
    return $errors;
}

/**
 * Function to validate phone number
 */
function validatePhone($phone) {
    // Remove spaces, dashes, and parentheses
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    // Check if it's a valid Philippine mobile number
    if (preg_match('/^(\+63|63|0)?9\d{9}$/', $phone)) {
        return true;
    }
    
    // Check if it's a valid landline number
    if (preg_match('/^(\+63|63|0)?[2-8]\d{6,7}$/', $phone)) {
        return true;
    }
    
    return false;
}

/**
 * Function to validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>